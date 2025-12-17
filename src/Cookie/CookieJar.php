<?php

namespace Villaflor\Connection\Cookie;

/**
 * Cookie jar for storing and managing cookies across requests.
 */
class CookieJar
{
    /**
     * @var array<string, Cookie>
     */
    private array $cookies = [];

    /**
     * Add a cookie to the jar.
     */
    public function add(Cookie $cookie): void
    {
        // Use name-domain-path as unique key
        $key = $this->getCookieKey($cookie);
        $this->cookies[$key] = $cookie;
    }

    /**
     * Add cookies from Set-Cookie headers.
     *
     * @param  array<string>  $headers
     */
    public function addFromHeaders(array $headers): void
    {
        foreach ($headers as $header) {
            $cookie = Cookie::fromSetCookieHeader($header);
            $this->add($cookie);
        }
    }

    /**
     * Get all cookies that match the given domain and path.
     *
     * @return array<Cookie>
     */
    public function getMatchingCookies(string $domain, string $path): array
    {
        $matching = [];

        foreach ($this->cookies as $cookie) {
            if ($cookie->isExpired()) {
                continue;
            }

            if ($cookie->matches($domain, $path)) {
                $matching[] = $cookie;
            }
        }

        return $matching;
    }

    /**
     * Get the Cookie header value for the given domain and path.
     */
    public function getCookieHeader(string $domain, string $path): ?string
    {
        $cookies = $this->getMatchingCookies($domain, $path);

        if (empty($cookies)) {
            return null;
        }

        $values = array_map(fn (Cookie $cookie) => $cookie->toString(), $cookies);

        return implode('; ', $values);
    }

    /**
     * Clear all cookies from the jar.
     */
    public function clear(): void
    {
        $this->cookies = [];
    }

    /**
     * Remove expired cookies from the jar.
     */
    public function removeExpired(): void
    {
        $this->cookies = array_filter(
            $this->cookies,
            fn (Cookie $cookie) => ! $cookie->isExpired()
        );
    }

    /**
     * Get all cookies in the jar.
     *
     * @return array<Cookie>
     */
    public function all(): array
    {
        return array_values($this->cookies);
    }

    /**
     * Get the number of cookies in the jar.
     */
    public function count(): int
    {
        return count($this->cookies);
    }

    /**
     * Generate a unique key for a cookie.
     */
    private function getCookieKey(Cookie $cookie): string
    {
        return $cookie->getName().'@'.($cookie->getDomain() ?? '').'#'.($cookie->getPath() ?? '/');
    }
}
