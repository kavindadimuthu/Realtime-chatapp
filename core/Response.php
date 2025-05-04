<?php

namespace app\core;

class Response
{
    /**
     * Set the HTTP status code for the response.
     * @param int $code
     * @return $this
     */
    public function setStatusCode(int $code): self
    {
        http_response_code($code);
        return $this;
    }

    /**
     * Set a response header.
     * @param string $key
     * @param string $value
     * @param bool $replace
     * @return $this
     */
    public function setHeader(string $key, string $value, bool $replace = true): self
    {
        header("$key: $value", $replace);
        return $this;
    }

    /**
     * Send plain text response.
     * @param string $content
     * @param int $code
     * @return void
     */
    public function sendText(string $content, int $code = 200): void
    {
        $this->setStatusCode($code)->setHeader('Content-Type', 'text/plain');
        echo $content;
    }

    /**
     * Send HTML response.
     * @param string $html
     * @param int $code
     * @return void
     */
    public function sendHtml(string $html, int $code = 200): void
    {
        $this->setStatusCode($code)->setHeader('Content-Type', 'text/html');
        echo $html;
    }

    /**
     * Send JSON response.
     * @param mixed $data
     * @param int $code
     * @param int $options
     * @return void
     */
    public function sendJson($data, int $code = 200, int $options = 0): void
    {
        $this->setStatusCode($code)->setHeader('Content-Type', 'application/json');
        echo json_encode($data, $options | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Send a file for download or inline display.
     * @param string $filePath
     * @param string|null $fileName
     * @param bool $asAttachment
     * @return void
     */
    public function sendFile(string $filePath, ?string $fileName = null, bool $asAttachment = true): void
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            $this->sendError('File not found.', 404);
            return;
        }
        $fileName = $fileName ?? basename($filePath);
        $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';
        $disposition = $asAttachment ? 'attachment' : 'inline';

        $this->setHeader('Content-Type', $mimeType)->setHeader('Content-Disposition', "$disposition; filename=\"$fileName\"")->setHeader('Content-Length', (string)filesize($filePath));

        readfile($filePath);
    }

    /**
     * Redirect to another URL.
     * @param string $url
     * @param int $code
     * @return void
     */
    public function redirect(string $url, int $code = 302): void
    {
        $this->setStatusCode($code)->setHeader('Location', $url);
        exit;
    }

    /**
     * Send an error response (JSON by default).
     * @param string $message
     * @param int $code
     * @param array $extra
     * @return void
     */
    public function sendError(string $message, int $code = 400, array $extra = []): void
    {
        $this->sendJson(array_merge(['error' => $message], $extra), $code);
    }

    /**
     * Clear all previously set headers.
     * @return void
     */
    public function clearHeaders(): void
    {
        if (function_exists('header_remove')) {
            header_remove();
        }
    }

    /**
     * Set a cookie.
     * @param string $name
     * @param string $value
     * @param array $options
     * @return void
     */
    public function setCookie(string $name, string $value, array $options = []): void
    {
        setcookie(
            $name,
            $value,
            $options['expires'] ?? 0,
            $options['path'] ?? '/',
            $options['domain'] ?? '',
            $options['secure'] ?? false,
            $options['httponly'] ?? false
        );
    }

    /**
     * Remove a cookie.
     * @param string $name
     * @param array $options
     * @return void
     */
    public function removeCookie(string $name, array $options = []): void
    {
        $this->setCookie($name, '', array_merge($options, ['expires' => time() - 3600]));
    }
}