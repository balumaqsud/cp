<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TestController extends AbstractController {

    #[Route('/test')]
    public function index(): Response

    {
        $contents = $this->renderView('test/test.html.twig');
        return new Response($contents);
    }
}
