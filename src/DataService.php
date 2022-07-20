<?php

namespace ActiveCollab\Quickbooks;

use ActiveCollab\Quickbooks\Data\BatchResponse;
use ActiveCollab\Quickbooks\Quickbooks;
use ActiveCollab\Quickbooks\Data\Entity;
use Guzzle\Log\LogAdapterInterface;
use Guzzle\Plugin\Log\LogPlugin;
use Guzzle\Service\Client as GuzzleClient;
use ActiveCollab\Quickbooks\Data\QueryResponse;
use Guzzle\Http\Exception\BadResponseException;
use League\OAuth1\Client\Credentials\TokenCredentials;
use League\OAuth1\Client\Credentials\ClientCredentials;

class DataService
{
    const API_VERSION = 3;

    /**
     * @var string
     */
    protected $consumer_key, $consumer_key_secret, $access_token, $access_token_secret, $realmId;

    /**
     * @var LogAdapterInterface
     */
    protected $logAdapter;

    /**
     * @var string|null
     */
    protected $user_agent = null;

    /**
     * @var string
     */
    protected $entity = '';

    /**
     * Construct data service
     *
     * @param string $consumer_key
     * @param string $consumer_key_secret
     * @param string $access_token
     * @param string $access_token_secret
     * @param string $realmId
     * @param LogAdapterInterface $logAdapter
     */
    public function __construct($consumer_key, $consumer_key_secret, $access_token, $access_token_secret, $realmId, LogAdapterInterface $logAdapter = null)
    {
        $this->consumer_key = $consumer_key;
        $this->consumer_key_secret = $consumer_key_secret;
        $this->access_token = $access_token;
        $this->access_token_secret = $access_token_secret;
        $this->realmId = $realmId;
        $this->logAdapter = $logAdapter;
    }

    /**
     * Return api url
     *
     * @return string
     */
    public function getApiUrl()
    {
        return 'https://quickbooks.api.intuit.com/v'.self::API_VERSION;
    }

    /**
     * Return http client
     *
     * @return GuzzleClient
     */
    public function createHttpClient()
    {
        $client = new GuzzleClient();

        if ($this->logAdapter) {
            $client->addSubscriber(new LogPlugin($this->logAdapter));
        }

        return $client;
    }

    /**
     * Return oauth server
     *
     * @return Quickbooks
     */
    public function createServer()
    {
        $client_credentials = new ClientCredentials();
        $client_credentials->setIdentifier($this->consumer_key);
        $client_credentials->setSecret($this->consumer_key_secret);

        return new Quickbooks($client_credentials);
    }

    /**
     * Return token credentials
     *
     * @return TokenCredentials
     */
    public function getTokenCredentials()
    {
        $tokenCredentials = new TokenCredentials();
        $tokenCredentials->setIdentifier($this->access_token);
        $tokenCredentials->setSecret($this->access_token_secret);

        return $tokenCredentials;
    }

    /**
     * Set user agent
     *
     * @param string|null $user_agent
     */
    public function setUserAgent($user_agent = null)
    {
        $this->user_agent = $user_agent;

        return $this;
    }

    /**
     * Return user agent
     *
     * @return string
     */
    public function getUserAgent()
    {
        return $this->user_agent;
    }

    /**
     * Set entity
     *
     * @param string $entity
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * Return entity url
     *
     * @return string
     */
    public function getRequestUrl($slug)
    {
        return $this->getApiUrl() . '/company/' . $this->realmId .  '/' . strtolower($slug);
    }

    /**
     * Send create request
     *
     * @param  array            $payload
     * @return Entity
     */
    public function create(array $payload, $minorVersion = null)
    {
        $uri = $this->getRequestUrl($this->entity);

        if ($minorVersion !== null) {
            $uri = $uri . '?minorversion=' . $minorVersion;
        }

        return $this->request('POST', $uri, $payload);
    }

    /**
     * Send read request
     *
     * @param  int              $id
     * @return Entity
     */
    public function read($id)
    {
        $uri = $this->getRequestUrl($this->entity) . '/' . $id;

        return $this->request('GET', $uri);
    }

    /**
     * Send update request
     *
     * @param  array            $payload
     * @return Entity
     */
    public function update(array $payload, $minorVersion = null)
    {
        $uri = $this->getRequestUrl($this->entity) . '?operation=update';

        if ($minorVersion !== null) {
            $uri = $uri . '&minorversion=' . $minorVersion;
        }

        return $this->request('POST', $uri, $payload);
    }

    /**
     * Send delete request
     *
     * @param  array            $payload
     * @return null
     */
    public function delete(array $payload)
    {
        $uri = $this->getRequestUrl($this->entity) . '?operation=delete';

        $this->request('POST', $uri, $payload);

        return null;
    }

    /**
     * Send query request
     *
     * @param  string|null      $query
     * @return QueryResponse
     */
    public function query($query = null, $minorVersion = null)
    {
        if ($query === null) {
            $query = "select * from {$this->entity}";
        }

        $uri = $this->getRequestUrl('query') . '?query=' . urlencode($query);

        if ($minorVersion !== null) {
            $uri = $uri . '&minorversion=' . $minorVersion;
        }

        return $this->request('GET', $uri);
    }

    /**
     * Creates a batch request
     *
     * @param array $payload
     * @param int $minorVersion
     * @return Entity|QueryResponse
     * @throws \Exception
     */
    public function batch(array $payload, $minorVersion = null)
    {
        $uri = $this->getRequestUrl($this->entity);

        if ($minorVersion !== null) {
            $uri = $uri . '?minorversion=' . $minorVersion;
        }

        return $this->request('POST', $uri, $payload);
    }


    /**
     * Return headers for request
     *
     * @param  string           $method
     * @param  string           $uri
     * @return array
     */
    public function getHeaders($method, $uri)
    {
        $server = $this->createServer();

        $headers = $server->getHeaders($this->getTokenCredentials(), $method, $uri);

        $headers['Accept'] = 'application/json';
        $headers['Content-Type'] = 'application/json';

        if (!empty($this->user_agent)) {
            $headers['User-Agent'] = $this->user_agent;
        }

        return $headers;
    }

    /**
     * Request
     *
     * @param  string $method
     * @param  string $uri
     * @param  string|array      $body
     * @return Entity|QueryResponse
     * @throws \Exception
     */
    public function request($method, $uri, array $body = null)
    {
        $client = $this->createHttpClient();

        $headers = $this->getHeaders($method, $uri);

        if ($body !== null) {
            $body = json_encode($body);
        }

        try {
            $response = $client->createRequest($method, $uri, $headers, $body)->send()->json();

            $keys = array_keys($response);
            $values = array_values($response);

            $data = isset($values[0]) ? $values[0] : [];

            switch($keys[0]) {
                case 'QueryResponse':
                    $response = new QueryResponse($data);
                    break;
                case 'BatchItemResponse':
                    $response = new BatchResponse($data);
                    break;
                default:
                    $response = new Entity($data);
                    break;
            }

            return $response;
        } catch (BadResponseException $e) {
            $response = $e->getResponse();
            $body = $response->getBody();
            $statusCode = $response->getStatusCode();

            throw new \Exception(
                "Received error [$body] with status code [$statusCode] when sending request.",
                $statusCode,
                $e
            );
        }
    }

}