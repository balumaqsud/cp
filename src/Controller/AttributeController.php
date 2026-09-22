<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\AttributeRepository;
use App\Security\Voter\AttributeVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AttributeController extends AbstractController
{
    #[Route('/attributes', name: 'app_attribute_index', methods: ['GET'])]
    public function index(AttributeRepository $attributes): Response
    {
        $this->denyAccessUnlessGranted(AttributeVoter::VIEW);

        return $this->render('attribute/index.html.twig', [
            'attributes' => $attributes->search(null, null),
        ]);
    }
}
