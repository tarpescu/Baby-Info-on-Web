<?php
/**
 * @author Romila Raluca
 */

declare(strict_types=1);

namespace App\Core;

class Request
{
    public string $method;
    public string $uri;
    public array $headers;
    public array $body;
    public array $files;
    public array $query;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $this->headers = $this->getAllHeaders();
        $this->query = $_GET;
        $this->files = $_FILES;
        $this->body = $this->parseBody();
    }

    private function getAllHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $headers[strtolower($name)] = $value;
            }
        }
        return $headers;
    }

    private function parseBody(): array
    {
        if ($this->method === 'GET') {
            return [];
        }

        $contentType = $this->headers['content-type'] ?? '';

        if (str_contains($contentType, 'application/json')) {
            $input = file_get_contents('php://input');
            return json_decode($input, true) ?? [];
        }

        return $_POST;
    }

    public function getHeader(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public function isJson(): bool
    {
        return str_contains($this->headers['content-type'] ?? '', 'application/json');
    }
}