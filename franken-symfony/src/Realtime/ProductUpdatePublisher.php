<?php

namespace App\Realtime;

use App\Entity\Product;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

final class ProductUpdatePublisher
{
    public const TOPIC = 'https://frankenphp.local/products';

    public function __construct(private readonly HubInterface $hub)
    {
    }

    public function publish(string $type, Product $product, ?int $productId = null): void
    {
        $this->hub->publish(new Update(
            self::TOPIC,
            json_encode([
                'type' => $type,
                'product' => [
                    'id' => $productId ?? $product->getId(),
                    'name' => $product->getName(),
                    'price_cents' => $product->getPriceCents(),
                    'price' => number_format($product->getPriceCents() / 100, 2, '.', '').' EUR',
                    'stock' => $product->getStock(),
                    'created_at' => $product->getCreatedAt()->format(DATE_ATOM),
                ],
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        ));
    }
}
