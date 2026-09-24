<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\CvLikeRepository;
use App\Service\SearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SearchController extends AbstractController
{
    #[Route('/search', name: 'app_search', methods: ['GET'])]
    public function search(
        Request $request,
        SearchService $searchService,
        CvLikeRepository $likes,
    ): Response {
        $query = trim($request->query->getString('q'));
        $tag = trim($request->query->getString('tag'));
        $user = $this->getUser();
        $results = $searchService->search(
            $query,
            $user instanceof User ? $user : null,
            $tag,
        );

        $cvIds = [];
        foreach ($results['cvs'] as $cv) {
            if ($cv->getId() !== null) {
                $cvIds[] = $cv->getId();
            }
        }

        return $this->render('search/index.html.twig', [
            'query' => $query,
            'tag' => $tag,
            'results' => $results,
            'likeCounts' => $likes->countIndexedByCvIds($cvIds),
        ]);
    }
}
