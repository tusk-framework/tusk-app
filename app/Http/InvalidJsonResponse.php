<?php

declare(strict_types=1);

namespace App\Http;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

final class InvalidJsonResponse
{
    public static function forPath(string $path): ResponseInterface
    {
        $problem = [
            'type' => 'about:blank',
            'title' => 'Bad Request',
            'status' => 400,
            'instance' => $path,
        ];

        return new Response(
            400,
            ['Content-Type' => 'application/problem+json'],
            json_encode($problem, JSON_THROW_ON_ERROR),
        );
    }
}
