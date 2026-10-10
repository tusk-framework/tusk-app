<?php

declare(strict_types=1);

namespace App\Application;

use App\DTO\Product;

final class ProductCatalog
{
    /** @var array<int, Product> */
    private array $products = [];

    private int $nextId = 1;

    /** @return list<Product> */
    public function all(): array
    {
        return array_values($this->products);
    }

    public function find(int $id): ?Product
    {
        return $this->products[$id] ?? null;
    }

    public function create(string $name, string $sku): Product
    {
        $product = new Product($this->nextId++, $name, $sku);
        $this->products[$product->id] = $product;

        return $product;
    }

    public function update(int $id, string $name, string $sku): ?Product
    {
        if (! isset($this->products[$id])) {
            return null;
        }

        return $this->products[$id] = new Product($id, $name, $sku);
    }

    public function delete(int $id): bool
    {
        if (! isset($this->products[$id])) {
            return false;
        }

        unset($this->products[$id]);

        return true;
    }
}
