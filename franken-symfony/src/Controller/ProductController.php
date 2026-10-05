<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/products')]
final class ProductController
{
    #[Route('', name: 'api_products_list', methods: ['GET'])]
    public function list(Request $request, ProductRepository $products): JsonResponse
    {
        $items = $products->search(
            $request->query->get('q'),
            $request->query->getBoolean('in_stock'),
        );

        return new JsonResponse([
            'count' => count($items),
            'items' => array_map($this->normalize(...), $items),
        ]);
    }

    #[Route('/{id}', name: 'api_products_show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function show(Product $product): JsonResponse
    {
        return new JsonResponse($this->normalize($product));
    }

    #[Route('', name: 'api_products_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $this->json($request);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        if ($error = $this->validate($data, true)) {
            return $error;
        }

        $product = new Product(
            trim($data['name']),
            (int) $data['price_cents'],
            (int) $data['stock'],
        );

        $entityManager->persist($product);
        $entityManager->flush();

        return new JsonResponse(
            $this->normalize($product),
            Response::HTTP_CREATED,
            ['Location' => '/api/products/'.$product->getId()],
        );
    }

    #[Route('/{id}', name: 'api_products_update', requirements: ['id' => '\\d+'], methods: ['PATCH'])]
    public function update(Product $product, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $this->json($request);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        if ($error = $this->validate($data, false)) {
            return $error;
        }

        if (array_key_exists('name', $data)) {
            $product->setName(trim($data['name']));
        }
        if (array_key_exists('price_cents', $data)) {
            $product->setPriceCents((int) $data['price_cents']);
        }
        if (array_key_exists('stock', $data)) {
            $product->setStock((int) $data['stock']);
        }

        $entityManager->flush();

        return new JsonResponse($this->normalize($product));
    }

    #[Route('/{id}', name: 'api_products_delete', requirements: ['id' => '\\d+'], methods: ['DELETE'])]
    public function delete(Product $product, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($product);
        $entityManager->flush();

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    /** @return array<string, mixed>|JsonResponse */
    private function json(Request $request): array|JsonResponse
    {
        try {
            return $request->toArray();
        } catch (\Throwable) {
            return new JsonResponse(['error' => 'Le corps doit contenir un objet JSON valide.'], 400);
        }
    }

    /** @param array<string, mixed> $data */
    private function validate(array $data, bool $creation): ?JsonResponse
    {
        $required = ['name', 'price_cents', 'stock'];
        if ($creation) {
            foreach ($required as $field) {
                if (!array_key_exists($field, $data)) {
                    return new JsonResponse(['error' => "Le champ {$field} est obligatoire."], 422);
                }
            }
        }

        if (array_key_exists('name', $data) && (!is_string($data['name']) || '' === trim($data['name']))) {
            return new JsonResponse(['error' => 'Le nom doit être une chaîne non vide.'], 422);
        }
        if (array_key_exists('price_cents', $data) && (!is_int($data['price_cents']) || $data['price_cents'] < 0)) {
            return new JsonResponse(['error' => 'price_cents doit être un entier positif.'], 422);
        }
        if (array_key_exists('stock', $data) && (!is_int($data['stock']) || $data['stock'] < 0)) {
            return new JsonResponse(['error' => 'stock doit être un entier positif.'], 422);
        }

        return null;
    }

    /** @return array<string, mixed> */
    private function normalize(Product $product): array
    {
        return [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'price_cents' => $product->getPriceCents(),
            'price' => number_format($product->getPriceCents() / 100, 2, '.', '').' EUR',
            'stock' => $product->getStock(),
            'created_at' => $product->getCreatedAt()->format(DATE_ATOM),
        ];
    }
}
