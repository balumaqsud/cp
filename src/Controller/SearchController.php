<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Service\SearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SearchController extends AbstractController
{
    #[Route('/search', name: 'app_search', methods: ['GET'])]
    public function search(Request $request, SearchService $searchService): Response
    {
        $query = trim($request->query->getString('q'));
        $user = $this->getUser();

        return $this->render('search/index.html.twig', [
            'query' => $query,
            'results' => $searchService->search(
                $query,
                $user instanceof User ? $user : null,
            ),
        ]);
    }
}
