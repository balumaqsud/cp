<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Attribute;
use App\Form\AttributeFormType;
use App\Repository\AttributeRepository;
use App\Repository\CategoryRepository;
use App\Security\Voter\AttributeVoter;
use App\Service\RecentAttributeStore;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AttributeController extends AbstractController
{
    public function __construct(
        private readonly AttributeRepository $attributes,
        private readonly CategoryRepository $categories,
        private readonly RecentAttributeStore $recentAttributes,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/attributes', name: 'app_attribute_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted(AttributeVoter::VIEW);

        $prefix = trim($request->query->getString('prefix'));
        $categoryId = $request->query->getInt('category');

        return $this->render('attribute/index.html.twig', [
            'attributes' => $this->attributes->search(
                $prefix !== '' ? $prefix : null,
                $categoryId > 0 ? $categoryId : null,
            ),
            'categories' => $this->categories->findAllOrdered(),
            'prefix' => $prefix,
            'categoryId' => $categoryId > 0 ? $categoryId : null,
            'recentAttributes' => $this->attributes->findByIds($this->recentAttributes->ids()),
            'canManage' => $this->isGranted(AttributeVoter::MANAGE),
        ]);
    }

    #[Route('/attributes/new', name: 'app_attribute_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted(AttributeVoter::MANAGE);

        $attribute = new Attribute();
        $form = $this->createForm(AttributeFormType::class, $attribute);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $attribute->setName(trim($attribute->getName()));
            $this->entityManager->persist($attribute);
            $this->entityManager->flush();
            $this->addFlash('success', 'attribute.flash.created');

            return $this->redirectToRoute('app_attribute_index');
        }

        return $this->render('attribute/form.html.twig', [
            'form' => $form,
            'attribute' => $attribute,
        ]);
    }

    #[Route('/attributes/{id}/edit', name: 'app_attribute_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Attribute $attribute): Response
    {
        $this->denyAccessUnlessGranted(AttributeVoter::MANAGE);

        if ($attribute->isBuiltIn()) {
            $this->addFlash('danger', 'attribute.flash.builtin_locked');

            return $this->redirectToRoute('app_attribute_index');
        }

        $form = $this->createForm(AttributeFormType::class, $attribute);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $submittedVersion = (int) $form->get('version')->getData();
            if ($submittedVersion !== $attribute->getVersion()) {
                $this->addFlash('danger', 'attribute.flash.conflict');

                return $this->redirectToRoute('app_attribute_edit', ['id' => $attribute->getId()]);
            }

            $attribute->setName(trim($attribute->getName()));
            $this->entityManager->flush();
            $this->addFlash('success', 'attribute.flash.updated');

            return $this->redirectToRoute('app_attribute_index');
        }

        return $this->render('attribute/form.html.twig', [
            'form' => $form,
            'attribute' => $attribute,
        ]);
    }

    #[Route('/attributes/bulk', name: 'app_attribute_bulk', methods: ['POST'])]
    public function bulk(Request $request): Response
    {
        $this->denyAccessUnlessGranted(AttributeVoter::MANAGE);

        if (!$this->isCsrfTokenValid('attribute_bulk', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $ids = array_values(array_unique(array_map(
            static fn (mixed $id): int => (int) $id,
            (array) $request->request->all('ids'),
        )));
        $ids = array_values(array_filter($ids, static fn (int $id) => $id > 0));

        return match ($request->request->getString('toolbar_action')) {
            'edit' => $this->redirectToSingleEdit($ids),
            'delete' => $this->deleteSelected($ids),
            default => $this->redirectToRoute('app_attribute_index'),
        };
    }

    /**
     * @param list<int> $ids
     */
    private function redirectToSingleEdit(array $ids): Response
    {
        if (\count($ids) !== 1) {
            $this->addFlash('danger', 'attribute.flash.select_one');

            return $this->redirectToRoute('app_attribute_index');
        }

        return $this->redirectToRoute('app_attribute_edit', ['id' => $ids[0]]);
    }

    /**
     * @param list<int> $ids
     */
    private function deleteSelected(array $ids): Response
    {
        $selected = $this->attributes->findByIds($ids);
        $deleted = 0;
        $skippedBuiltIn = false;

        foreach ($selected as $attribute) {
            if (!$attribute->isDeletable()) {
                $skippedBuiltIn = true;
                continue;
            }

            $this->entityManager->remove($attribute);
            ++$deleted;
        }

        try {
            $this->entityManager->flush();
        } catch (ForeignKeyConstraintViolationException) {
            $this->entityManager->clear();
            $this->addFlash('danger', 'attribute.flash.in_use');

            return $this->redirectToRoute('app_attribute_index');
        }

        if ($deleted > 0) {
            $this->addFlash('success', 'attribute.flash.deleted');
        }
        if ($skippedBuiltIn) {
            $this->addFlash('warning', 'attribute.flash.builtin_locked');
        }
        if ($deleted === 0 && !$skippedBuiltIn) {
            $this->addFlash('danger', 'attribute.flash.select_one');
        }

        return $this->redirectToRoute('app_attribute_index');
    }
}
