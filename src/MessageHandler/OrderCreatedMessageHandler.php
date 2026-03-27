<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Microservices\SharedBundle\Message\OrderCreatedMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class OrderCreatedMessageHandler
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ProductRepository $productRepository,
    ) {
    }

    public function __invoke(OrderCreatedMessage $message): void
    {
        $product = $this->productRepository->find($message->productId);

        if ($product === null) {
            return;
        }

        $product->setQuantity($product->getQuantity() - $message->quantityOrdered);

        $this->em->flush();
    }
}
