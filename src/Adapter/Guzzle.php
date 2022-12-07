<?php

namespace Villaflor\Connection\Adapter;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Villaflor\Connection\Auth\AuthInterface;
use Villaflor\Connection\Exception\ResponseException;

class Guzzle implements AdapterInterface
{
    private Client $client;

    public function __construct(AuthInterface $auth, string $baseURI)
    {
        $headers = $auth->getHeaders();

        $this->client = new Client([
            'base_uri' => $baseURI,
            'headers' => $headers,
            'Accept' => 'application/json',
        ]);
    }

    public function get(string $uri, array $data = [], array $headers = []): ResponseInterface
    {
        return $this->request('get', $uri, $data, $headers);
    }

    public function post(string $uri, array $data = [], array $headers = []): ResponseInterface
    {
        return $this->request('post', $uri, $data, $headers);
    }

    public function put(string $uri, array $data = [], array $headers = []): ResponseInterface
    {
        return $this->request('put', $uri, $data, $headers);
    }

    public function patch(string $uri, array $data = [], array $headers = []): ResponseInterface
    {
        return $this->request('patch', $uri, $data, $headers);
    }

    public function delete(string $uri, array $data = [], array $headers = []): ResponseInterface
    {
        return $this->request('delete', $uri, $data, $headers);
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function request(string $method, string $uri, array $data = [], array $headers = [])
    {
        if (! in_array($method, ['get', 'post', 'put', 'patch', 'delete'])) {
            throw new InvalidArgumentException('Request method must be get, post, put, patch, or delete');
        }

        $option = [
            'headers' => $headers,
            ($method === 'get' ? 'query' : 'json') => $data,
        ];

        if (isset($data['form_params'])) {
            $option = array_merge(['headers' => $headers], $data);
        }

        try {
            $response = $this->client->$method($uri, $option);
        } catch (RequestException $err) {
            throw ResponseException::fromRequestException($err);
        }

        return $response;
    }
}
