<?php

namespace App\Controller;

use App\DTO\CreateProductRequest;
use App\Service\ProductService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/products', name: 'product_')]
class ProductController extends AbstractController
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly ValidatorInterface $validator,
    ) {

    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (null === $data) {
            return $this->json(['error' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }

        $dto = CreateProductRequest::fromArray($data);

        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }

            return $this->json(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $product = $this->productService->create($dto);

        return $this->json($product->toArray(), Response::HTTP_CREATED);
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $products = $this->productService->findAll();

        return $this->json([
            'data' => array_map(fn ($p) => $p->toArray(), $products),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        $product = $this->productService->findById($id);

        if (null === $product) {
            return $this->json(['error' => 'Product not found.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($product->toArray());
    }
}
