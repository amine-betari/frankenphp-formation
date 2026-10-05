<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProductPageController extends AbstractController
{
    #[Route('/products', name: 'products_page', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('product/index.html.twig', [
            'execution_mode' => getenv('DEMO_MODE') ?: 'inconnu',
        ]);
    }
}
