<?php

declare(strict_types=1);

namespace App\Http;

use JsonException;
use UnexpectedValueException;

final class RequestBodyParser
{
    /**
     * @param  array<string, mixed>  $formBody
     * @return array<string, mixed>
     *
     * @throws JsonException
     * @throws UnexpectedValueException
     */
    public function parse(string $contentType, string $rawBody, array $formBody): array
    {
        $mediaType = strtolower(trim(explode(';', $contentType, 2)[0]));
        if ($mediaType !== 'application/json' && ! str_ends_with($mediaType, '+json')) {
            return $formBody;
        }

        if (trim($rawBody) === '') {
            throw new JsonException('A JSON request body must not be empty.');
        }

        $decoded = json_decode($rawBody, false, 512, JSON_THROW_ON_ERROR);
        if (! $decoded instanceof \stdClass) {
            throw new UnexpectedValueException('A JSON request body must be an object.');
        }

        return get_object_vars($decoded);
    }
}
