<?php

namespace Src\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TestController {

    #[Route('/test')]
    public function index(): Response
    {
        return new Response('test controller');
    }
}
