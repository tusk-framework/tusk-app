<?php

declare(strict_types=1);

namespace Tests\Integration;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use PHPUnit\Framework\TestCase;

final class PublicFrontControllerTest extends TestCase
{
    private const ROOT = __DIR__.'/../..';

    public function test_public_front_controller_parses_json_and_returns_problem_for_invalid_json(): void
    {
        $listener = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);
        self::assertIsResource($listener, $errorMessage);
        $address = stream_socket_get_name($listener, false);
        fclose($listener);
        self::assertIsString($address);
        $port = substr($address, (int) strrpos($address, ':') + 1);

        $logPath = tempnam(sys_get_temp_dir(), 'tusk-app-http-');
        self::assertIsString($logPath);
        $pipes = [];
        $process = proc_open(
            [PHP_BINARY, '-S', '127.0.0.1:'.$port, '-t', self::ROOT.'/public', self::ROOT.'/public/index.php'],
            [0 => ['pipe', 'r'], 1 => ['file', $logPath, 'a'], 2 => ['file', $logPath, 'a']],
            $pipes,
            self::ROOT,
        );
        if (! is_resource($process)) {
            $log = file_get_contents($logPath);
            unlink($logPath);
            self::fail('PHP built-in server did not start: '.$log);
        }
        fclose($pipes[0]);

        try {
            $client = new Client(['base_uri' => 'http://127.0.0.1:'.$port, 'connect_timeout' => 0.2, 'timeout' => 2, 'http_errors' => false, 'proxy' => '']);
            $ready = false;
            $readinessDeadline = microtime(true) + 10;
            while (microtime(true) < $readinessDeadline) {
                try {
                    $probe = $client->get('/api/products');
                    if ($probe->getStatusCode() === 200 && (string) $probe->getBody() === '[]') {
                        $ready = true;
                        break;
                    }
                } catch (ConnectException) {
                    // Retry until the wall-clock deadline to tolerate slow CI startup.
                }
                usleep(100_000);
            }
            self::assertTrue($ready, 'PHP built-in server did not become ready: '.file_get_contents($logPath));

            $created = $client->post('/api/products', [
                'headers' => ['Accept' => 'application/json', 'Content-Type' => 'application/json'],
                'body' => '{"name":"Notebook","sku":"NOTE-1"}',
            ]);
            self::assertSame(201, $created->getStatusCode());
            self::assertSame('Notebook', json_decode((string) $created->getBody(), true, 512, JSON_THROW_ON_ERROR)['name']);

            $invalid = $client->post('/api/products', [
                'headers' => ['Accept' => 'application/json', 'Content-Type' => 'application/json'],
                'body' => '{"name":',
            ]);
            $problem = json_decode((string) $invalid->getBody(), true, 512, JSON_THROW_ON_ERROR);
            self::assertSame(400, $invalid->getStatusCode());
            self::assertSame('application/problem+json', $invalid->getHeaderLine('Content-Type'));
            self::assertSame('/api/products', $problem['instance']);
        } finally {
            proc_terminate($process);
            proc_close($process);
            unlink($logPath);
        }
    }
}
