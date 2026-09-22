<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\PositionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PositionController extends AbstractController
{
    #[Route('/positions', name: 'app_position_index', methods: ['GET'])]
    public function index(PositionRepository $positions): Response
    {
        $user = $this->getUser();
        $rows = $user instanceof User && $user->isRecruiter()
            ? $positions->findAllManaged()
            : $positions->findPublic();

        return $this->render('position/index.html.twig', [
            'positions' => $rows,
        ]);
    }
}
