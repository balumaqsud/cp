<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\CurriculumVitaeRepository;
use App\Security\Voter\CurriculumVitaeVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CvController extends AbstractController
{
    #[Route('/cvs', name: 'app_cv_index', methods: ['GET'])]
    public function index(CurriculumVitaeRepository $cvs): Response
    {
        $this->denyAccessUnlessGranted(CurriculumVitaeVoter::LIST);

        return $this->render('cv/index.html.twig', [
            'cvs' => $cvs->findPublishedForRecruiting(),
        ]);
    }
}
