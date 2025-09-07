<?php

namespace TPT\ERP\Core;

/**
 * HTTP Response Handler
 *
 * Handles HTTP response formatting, headers, and output.
 */
class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private mixed $content = null;
    private bool $sent = false;

    /**
     * Set HTTP status code
     */
    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Get HTTP status code
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Set HTTP header
     */
    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Get HTTP header
     */
    public function getHeader(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    /**
     * Set multiple headers
     */
    public function setHeaders(array $headers): self
    {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }
        return $this;
    }

    /**
     * Set content
     */
    public function setContent(mixed $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Get content
     */
    public function getContent(): mixed
    {
        return $this->content;
    }

    /**
     * Set JSON content
     */
    public function json(mixed $data, int $statusCode = 200): self
    {
        $this->setStatusCode($statusCode);
        $this->setHeader('Content-Type', 'application/json');
        $this->setContent(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $this;
    }

    /**
     * Set HTML content
     */
    public function html(string $html, int $statusCode = 200): self
    {
        $this->setStatusCode($statusCode);
        $this->setHeader('Content-Type', 'text/html');
        $this->setContent($html);
        return $this;
    }

    /**
     * Set plain text content
     */
    public function text(string $text, int $statusCode = 200): self
    {
        $this->setStatusCode($statusCode);
        $this->setHeader('Content-Type', 'text/plain');
        $this->setContent($text);
        return $this;
    }

    /**
     * Set XML content
     */
    public function xml(string $xml, int $statusCode = 200): self
    {
        $this->setStatusCode($statusCode);
        $this->setHeader('Content-Type', 'application/xml');
        $this->setContent($xml);
        return $this;
    }

    /**
     * Redirect to URL
     */
    public function redirect(string $url, int $statusCode = 302): self
    {
        $this->setStatusCode($statusCode);
        $this->setHeader('Location', $url);
        return $this;
    }

    /**
     * Set cookie
     */
    public function setCookie(
        string $name,
        string $value,
        int $expires = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = true,
        string $sameSite = 'Lax'
    ): self {
        $cookieOptions = [
            'expires' => $expires,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httpOnly,
            'samesite' => $sameSite
        ];

        setcookie($name, $value, $cookieOptions);
        return $this;
    }

    /**
     * Set CORS headers
     */
    public function setCorsHeaders(
        string $origin = '*',
        string $methods = 'GET, POST, PUT, DELETE, OPTIONS',
        string $headers = 'Content-Type, Authorization, X-Requested-With',
        bool $credentials = false
    ): self {
        $this->setHeader('Access-Control-Allow-Origin', $origin);
        $this->setHeader('Access-Control-Allow-Methods', $methods);
        $this->setHeader('Access-Control-Allow-Headers', $headers);

        if ($credentials) {
            $this->setHeader('Access-Control-Allow-Credentials', 'true');
        }

        return $this;
    }

    /**
     * Set cache headers
     */
    public function setCacheHeaders(int $maxAge = 3600, bool $public = true): self
    {
        $cacheControl = $public ? 'public' : 'private';
        $cacheControl .= ', max-age=' . $maxAge;

        $this->setHeader('Cache-Control', $cacheControl);
        $this->setHeader('Expires', gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT');

        return $this;
    }

    /**
     * Disable caching
     */
    public function noCache(): self
    {
        $this->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
        $this->setHeader('Pragma', 'no-cache');
        $this->setHeader('Expires', '0');
        return $this;
    }

    /**
     * Set content disposition for file download
     */
    public function download(string $filename, string $contentType = 'application/octet-stream'): self
    {
        $this->setHeader('Content-Type', $contentType);
        $this->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        return $this;
    }

    /**
     * Send response
     */
    public function send(): void
    {
        if ($this->sent) {
            return;
        }

        $this->sent = true;

        // Send status code
        http_response_code($this->statusCode);

        // Send headers
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }

        // Send content
        if ($this->content !== null) {
            echo $this->content;
        }
    }

    /**
     * Check if response has been sent
     */
    public function isSent(): bool
    {
        return $this->sent;
    }

    /**
     * Get status text for status code
     */
    public static function getStatusText(int $code): string
    {
        $statusTexts = [
            100 => 'Continue',
            101 => 'Switching Protocols',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            307 => 'Temporary Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Payload Too Large',
            414 => 'URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Range Not Satisfiable',
            417 => 'Expectation Failed',
            422 => 'Unprocessable Entity',
            423 => 'Locked',
            424 => 'Failed Dependency',
            425 => 'Too Early',
            426 => 'Upgrade Required',
            428 => 'Precondition Required',
            429 => 'Too Many Requests',
            431 => 'Request Header Fields Too Large',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
        ];

        return $statusTexts[$code] ?? 'Unknown Status';
    }
}
