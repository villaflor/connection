<?php

namespace Villaflor\Connection\Cookie;

/**
 * Represents an HTTP cookie.
 */
class Cookie
{
    public function __construct(
        private readonly string $name,
        private readonly string $value,
        private readonly ?int $expires = null,
        private readonly ?string $path = null,
        private readonly ?string $domain = null,
        private readonly bool $secure = false,
        private readonly bool $httpOnly = false
    ) {}

    /**
     * Parse a Set-Cookie header into a Cookie object.
     */
    public static function fromSetCookieHeader(string $header): self
    {
        // Parse the cookie string
        $parts = array_map('trim', explode(';', $header));
        $nameValue = array_shift($parts);

        [$name, $value] = array_pad(explode('=', $nameValue, 2), 2, '');

        $expires = null;
        $path = null;
        $domain = null;
        $secure = false;
        $httpOnly = false;

        foreach ($parts as $part) {
            if (str_contains($part, '=')) {
                [$attrName, $attrValue] = explode('=', $part, 2);
                $attrName = strtolower(trim($attrName));
                $attrValue = trim($attrValue);

                match ($attrName) {
                    'expires' => $expires = strtotime($attrValue),
                    'max-age' => $expires = time() + (int) $attrValue,
                    'path' => $path = $attrValue,
                    'domain' => $domain = ltrim($attrValue, '.'),
                    default => null,
                };
            } else {
                $flag = strtolower(trim($part));
                match ($flag) {
                    'secure' => $secure = true,
                    'httponly' => $httpOnly = true,
                    default => null,
                };
            }
        }

        return new self($name, $value, $expires, $path, $domain, $secure, $httpOnly);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getExpires(): ?int
    {
        return $this->expires;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function isSecure(): bool
    {
        return $this->secure;
    }

    public function isHttpOnly(): bool
    {
        return $this->httpOnly;
    }

    /**
     * Check if the cookie has expired.
     */
    public function isExpired(): bool
    {
        if ($this->expires === null) {
            return false;
        }

        return time() >= $this->expires;
    }

    /**
     * Check if the cookie matches the given domain and path.
     */
    public function matches(string $domain, string $path): bool
    {
        // Check domain
        if ($this->domain !== null) {
            // Cookie domain must match or be a parent domain
            if (! str_ends_with($domain, $this->domain)) {
                return false;
            }
        }

        // Check path
        if ($this->path !== null) {
            // Request path must start with cookie path
            if (! str_starts_with($path, $this->path)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Convert the cookie to a Cookie header value.
     */
    public function toString(): string
    {
        return "{$this->name}={$this->value}";
    }
}
