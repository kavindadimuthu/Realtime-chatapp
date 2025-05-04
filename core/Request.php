<?php

namespace app\core;

class Request
{
    /**
     * Get the HTTP method of the request.
     * @return string
     */
    public function getMethod(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Get the request URI path (without query string).
     * @return string
     */
    public function getPath(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        return $path ?: '/';
    }

    /**
     * Get all query parameters.
     * @return array
     */
    public function getQueryParams(): array
    {
        return $_GET ?? [];
    }

    /**
     * Get all POST parameters.
     * @return array
     */
    public function getPostParams(): array
    {
        return $_POST ?? [];
    }

    /**
     * Get all request parameters (query + body).
     * @return array
     */
    public function getAllParams(): array
    {
        return array_merge($this->getQueryParams(), $this->getParsedBody() ?? []);
    }

    /**
     * Get a specific parameter from query or body.
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getParam(string $key, $default = null)
    {
        $params = $this->getAllParams();
        return $params[$key] ?? $default;
    }

    /**
     * Get all request headers.
     * @return array
     */
    public function getHeaders(): array
    {
        // Use getallheaders if available, otherwise build headers manually
        if (function_exists('getallheaders')) {
            return getallheaders();
        }
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (str_starts_with($name, 'HTTP_')) {
                $header = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$header] = $value;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];
        }
        if (isset($_SERVER['CONTENT_LENGTH'])) {
            $headers['Content-Length'] = $_SERVER['CONTENT_LENGTH'];
        }
        return $headers;
    }

    /**
     * Get the value of a specific header.
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getHeader(string $name, $default = null)
    {
        $headers = $this->getHeaders();
        foreach ($headers as $key => $value) {
            if (strcasecmp($key, $name) === 0) {
                return $value;
            }
        }
        return $default;
    }

    /**
     * Get the raw body of the request.
     * @return string
     */
    public function getBody(): string
    {
        return file_get_contents('php://input');
    }

    /**
     * Get parsed body data based on content type.
     * @return array|null
     */
    public function getParsedBody(): ?array
    {
        $contentType = $this->getContentType();
        $body = $this->getBody();

        if ($contentType && stripos($contentType, 'application/json') === 0) {
            $data = json_decode($body, true);
            return is_array($data) ? $data : [];
        } elseif ($contentType && stripos($contentType, 'application/x-www-form-urlencoded') === 0) {
            parse_str($body, $data);
            return is_array($data) ? $data : [];
        } elseif ($contentType && stripos($contentType, 'multipart/form-data') === 0) {
            return $_POST ?? [];
        }
        return [];
    }

    /**
     * Get uploaded files.
     * @return array
     */
    public function getFiles(): array
    {
        return $_FILES ?? [];
    }

    /**
     * Get a specific uploaded file.
     * @param string $key
     * @return array|null
     */
    public function getFile(string $key): ?array
    {
        return $_FILES[$key] ?? null;
    }

    /**
     * Get the client's IP address.
     * @return string
     */
    public function getClientIp(): string
    {
        $keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        foreach ($keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ipList = explode(',', $_SERVER[$key]);
                foreach ($ipList as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP)) {
                        return $ip;
                    }
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Get the content type of the request.
     * @return string|null
     */
    public function getContentType(): ?string
    {
        return $_SERVER['CONTENT_TYPE'] ?? $this->getHeader('Content-Type');
    }

    /**
     * Check if the request is an AJAX request.
     * @return bool
     */
    public function isAjax(): bool
    {
        return strtolower($this->getHeader('X-Requested-With') ?? '') === 'xmlhttprequest';
    }

    /**
     * Check if the request is a secure HTTPS request.
     * @return bool
     */
    public function isSecure(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
    }

    /**
     * Get the request scheme (http or https).
     * @return string
     */
    public function getScheme(): string
    {
        return $this->isSecure() ? 'https' : 'http';
    }

    /**
     * Get the host (domain) of the request.
     * @return string
     */
    public function getHost(): string
    {
        return $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
    }

    /**
     * Get the full URL of the request.
     * @return string
     */
    public function getFullUrl(): string
    {
        $scheme = $this->getScheme();
        $host = $this->getHost();
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        return $scheme . '://' . $host . $uri;
    }
}