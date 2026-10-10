<?php

use App\Application\ProductCatalog;
use Tusk\Core\Container\Container;

return static function (Container $container): void {
    $container->register(ProductCatalog::class, 'singleton');
};
