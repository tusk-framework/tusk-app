<?php

declare(strict_types=1);

namespace Tests;

use App\Http\RequestBodyParser;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Tusk\Foundation\Application;

final class SkeletonTest extends TestCase
{
    private const ROOT = __DIR__.'/..';

    public function test_manifest_is_a_composer_project_requiring_stable_tusk_framework(): void
    {
        $manifest = json_decode((string) file_get_contents(self::ROOT.'/composer.json'), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('tusk/app', $manifest['name']);
        self::assertSame('project', $manifest['type']);
        self::assertSame('^8.2', $manifest['require']['php']);
        $frameworkConstraint = $manifest['require']['tusk/framework'];
        self::assertIsString($frameworkConstraint);
        self::assertMatchesRegularExpression('/^\^(?:0\.(?:3\.(?:[2-9]|[1-9][0-9]+)|(?:[4-9]|[1-9][0-9]+)\.[0-9]+)|[1-9][0-9]*\.[0-9]+\.[0-9]+)$/', $frameworkConstraint);
        self::assertSame('app/', $manifest['autoload']['psr-4']['App\\']);
        self::assertArrayNotHasKey('repositories', $manifest);
        self::assertArrayNotHasKey('minimum-stability', $manifest);
    }

    public function test_supported_bootstrap_and_scaffolding_files_exist(): void
    {
        foreach ([
            'bootstrap/app.php',
            'bootstrap/providers.php',
            'public/index.php',
            'routes/web.php',
            'tusk.json',
            '.rr.yaml',
            '.gitignore',
            '.env.example',
            'README.md',
            'LICENSE',
            '.github/workflows/ci.yml',
        ] as $file) {
            self::assertFileExists(self::ROOT.'/'.$file, $file);
        }

        foreach (['app', 'bootstrap', 'public', 'routes'] as $directory) {
            self::assertDirectoryExists(self::ROOT.'/'.$directory, $directory);
        }
    }

    public function test_engine_project_configuration_is_minimal(): void
    {
        $configuration = json_decode((string) file_get_contents(self::ROOT.'/tusk.json'), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(['port' => 8080, 'worker_count' => 4], $configuration);
    }

    public function test_bootstrap_serves_typed_json_and_validation_problem(): void
    {
        $application = require self::ROOT.'/bootstrap/app.php';
        self::assertInstanceOf(Application::class, $application);

        $valid = (new ServerRequest('POST', '/api/products'))
            ->withHeader('Accept', 'application/json')
            ->withParsedBody(['name' => 'Notebook', 'sku' => 'NOTE-1']);
        $response = $application->handle($valid);
        self::assertSame(201, $response->getStatusCode());
        self::assertSame(['id' => 1, 'name' => 'Notebook', 'sku' => 'NOTE-1'], json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR));

        $invalid = (new ServerRequest('POST', '/api/products'))
            ->withHeader('Accept', 'application/json')
            ->withParsedBody(['name' => ' ', 'sku' => 'SECRET-INPUT']);
        $response = $application->handle($invalid);
        $problem = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(422, $response->getStatusCode());
        self::assertSame('application/problem+json', $response->getHeaderLine('Content-Type'));
        self::assertSame('urn:tusk:problem:validation', $problem['type']);
        self::assertArrayHasKey('name', $problem['errors']);
        self::assertStringNotContainsString('SECRET-INPUT', (string) $response->getBody());
    }

    public function test_json_content_type_decodes_the_request_body(): void
    {
        $parser = new RequestBodyParser;

        self::assertSame(
            ['name' => 'Notebook', 'sku' => 'NOTE-1'],
            $parser->parse('application/json; charset=utf-8', '{"name":"Notebook","sku":"NOTE-1"}', []),
        );
    }

    public function test_non_json_content_type_keeps_the_form_body(): void
    {
        $parser = new RequestBodyParser;

        self::assertSame(['name' => 'Notebook'], $parser->parse('application/x-www-form-urlencoded', '', ['name' => 'Notebook']));
    }

    public function test_json_content_type_rejects_malformed_json(): void
    {
        $this->expectException(\JsonException::class);

        (new RequestBodyParser)->parse('application/json', '{"name":', []);
    }

    public function test_legacy_bootstrap_entrypoints_are_absent(): void
    {
        foreach (['worker.php', 'bootstrap/worker.php', 'config.php', 'bootstrap/config.php', 'config/worker.php', 'config/tusk.php', '.tusk/runtime/worker.php'] as $file) {
            self::assertFileDoesNotExist(self::ROOT.'/'.$file, $file);
        }
    }
}
