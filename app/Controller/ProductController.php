<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\ProductCatalog;
use App\DTO\ProductInput;
use Nyholm\Psr7\Response as PsrResponse;
use Psr\Http\Message\ResponseInterface;
use Tusk\Web\Attribute\Controller;
use Tusk\Web\Attribute\Delete;
use Tusk\Web\Attribute\Get;
use Tusk\Web\Attribute\Post;
use Tusk\Web\Attribute\Put;
use Tusk\Web\Http\Response;

#[Controller('/api/products')]
final class ProductController
{
    public function __construct(private ProductCatalog $products) {}

    #[Get]
    public function index(): array
    {
        return $this->products->all();
    }

    #[Post]
    public function create(ProductInput $input): ResponseInterface
    {
        return Response::json($this->products->create($input->name, $input->sku), 201);
    }

    #[Get('/{id}')]
    public function show(int $id): ResponseInterface
    {
        $product = $this->products->find($id);

        return $product === null
            ? Response::json(['message' => 'Product not found'], 404)
            : Response::json($product);
    }

    #[Put('/{id}')]
    public function update(int $id, ProductInput $input): ResponseInterface
    {
        $product = $this->products->update($id, $input->name, $input->sku);

        return $product === null
            ? Response::json(['message' => 'Product not found'], 404)
            : Response::json($product);
    }

    #[Delete('/{id}')]
    public function delete(int $id): ResponseInterface
    {
        return $this->products->delete($id)
            ? new PsrResponse(204)
            : Response::json(['message' => 'Product not found'], 404);
    }
}
