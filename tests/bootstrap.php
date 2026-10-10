<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$autoload = $projectRoot.'/vendor/autoload.php';
if (! is_file($autoload)) {
    $autoload = getenv('TUSK_TEST_FRAMEWORK_AUTOLOAD') ?: '';
}
if (! is_file($autoload)) {
    throw new RuntimeException('Install Composer dependencies before running the skeleton tests.');
}
require $autoload;

spl_autoload_register(static function (string $class) use ($projectRoot): void {
    if (! str_starts_with($class, 'App\\')) {
        return;
    }
    $path = $projectRoot.'/app/'.str_replace('\\', '/', substr($class, 4)).'.php';
    if (is_file($path)) {
        require $path;
    }
});
