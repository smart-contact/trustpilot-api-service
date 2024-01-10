<?php

namespace SmartContact\TrustpilotApiService;

use Exception;
use Carbon\Carbon;
use GuzzleHttp\Client;

class TrustpilotApiService
{
    protected bool $initialized = false;
    protected ?Client $client;
    protected ?Client $invitationsClient = null;
    protected ?array $accessToken = null;
    private array $credentials;
    private array $secrets;
    private string $businessUnitId;
    protected string $privateUri;
    protected string $apiVersion;

    protected array $defaultRequestConfig = [
        'uri' => null,
        'method' => 'GET',
        'options' => [],
        'auth' => true,
        'associativeResponses' => true,
        'useInvitationsClient' => false
    ];

    /**
     * $config = [
     *   'version' => string ('v1'),
     *   'business_unit_id' => string,
     *   'api_key' => string,
     *   'api_secret' => string,
     *   'username' => string,
     *   'password' => string
     * ]
     *
     * @param array<string, string> $config
     */
    private function setConfig(array $config)
    {
        $this->apiVersion = array_key_exists('version', $config) ? $config['version'] : 'v1';

        $this->secrets = [
            'api_key' => $config['api_key'],
            'api_secret' => $config['api_secret']
        ];

        $this->credentials = [
            'username' => $config['username'],
            'password' => $config['password']
        ];

        $this->businessUnitId = $config['business_unit_id'];
    }

    protected function resetConfig()
    {

        $this->apiVersion = null;

        $this->secrets = [
            'api_key' => null,
            'api_secret' => null
        ];

        $this->credentials = [
            'username' => null,
            'password' => null
        ];

        $this->businessUnitId = null;
    }

    public function init(array $config): self
    {
        if ($this->initialized) {
            throw new Exception('Service already initialized. If you want to update the configuration, please call \'clear()\' method first.');
        }

        $this->setConfig($config);

        $this->client = new Client([
            'base_uri' => "https://api.trustpilot.com/{$this->apiVersion}/"
        ]);

        $this->privateUri = "private/business-units/{$this->businessUnitId}";

        $this->initialized = true;

        return $this;
    }

    /**
     * Reset configurations
     *
     * @return self
     */
    public function reset(): self
    {

        $this->resetConfig();

        $this->client = null;

        $this->privateUri = null;

        $this->initialized = false;

        return $this;
    }

    protected function getInvitationsClient(): Client
    {
        if (!$this->invitationsClient) {
            $this->invitationsClient = new Client([
                'base_uri' => "https://invitations-api.trustpilot.com/{$this->apiVersion}/"
            ]);
        }

        return $this->invitationsClient;
    }

    /**
     * Check if the request is a private(true) or public(false) request
     */
    protected function isPrivateRequest(string $uri)
    {
        return str_contains($uri, 'private/');
    }

    /**
     * Wrap on Guzzle's client request.
     * Automatically adds token or authorization to headers option.
     */
    protected function request(array $config): array
    {

        if (!$this->initialized) {
            throw new Exception("Service not initialized, cannot make request. Please call 'init()' method first.");
        }

        $config = array_merge($this->defaultRequestConfig, $config);
        [
            'uri' => $uri,
            'method' => $method,
            'options' => $options,
        ] = $config;

        if (!array_key_exists('headers', $options))
            $options['headers'] = [];

        if ($this->isPrivateRequest($uri)) {
            if (!$this->hasValidAccessToken())
                $this->authenticate();

            $options['headers']['Authorization'] = "Bearer {$this->accessToken['access_token']}";
        } else {
            $options['headers']['apiKey'] = $this->secrets['api_key'];
        }

        $client = $config['useInvitationsClient'] ? $this->getInvitationsClient() : $this->client;
        $response = $client->request($method, $uri, $options);

        return json_decode($response->getBody(), $config['associativeResponses']);
    }


    /**
     * return the accessToken data
     */
    public function getAccessToken(): ?array
    {
        return $this->accessToken;
    }

    /**
     * Performs OAuth Authentication for private requests
     */
    public function authenticate(): self
    {
        $uri = "oauth/oauth-business-users-for-applications/accesstoken";

        $authorization = base64_encode(join(':', $this->secrets));

        $response = $this->client->post(
            $uri,
            [
                'headers' => [
                    'Authorization' => $authorization
                ],
                'form_params' => array_merge(
                    $this->credentials,
                    ['grant_type' => 'password']
                )
            ]
        );


        $data = json_decode($response->getBody(), true);

        $now = Carbon::now();
        $expiresAt = $now->addSeconds((int) $data['expires_in']);

        $this->accessToken = [
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'],
            'expires_at' => $expiresAt
        ];

        return $this;
    }

    /** 
     * Check if access token is valid
     */
    protected function hasValidAccessToken()
    {
        if (is_null($this->accessToken))
            return false;

        $now = Carbon::now();
        return $now->lessThan($this->accessToken['expires_at']);
    }

    /**
     * =============================
     * BUSINESS UNITS API
     * =============================
     */


    public function getPrivateReviews(array $query = [])
    {
        $uri = "{$this->privateUri}/reviews";

        return $this->request([
            'uri' => $uri,
            'options' => [
                'query' => $query
            ]
        ]);
    }

    public function getReviews(array $query = [])
    {
        $uri = "business-units/{$this->businessUnitId}/reviews";

        return $this->request([
            'uri' => $uri,
            'options' => [
                'query' => $query
            ]
        ]);
    }

    public function getReviewsPaginate(array $query = [])
    {
        $uri = "business-units/{$this->businessUnitId}/all-reviews";

        return $this->request([
            'uri' => $uri,
            'options' => [
                'query' => $query
            ]
        ]);
    }

    /**
     * =============================
     * INVITATION API
     * =============================
     */

    public function createInvitation(array $data)
    {
        $uri = "{$this->privateUri}/email-invitations";

        return $this->request([
            'uri' => $uri,
            'method' => 'POST',
            'useInvitationsClient' => true,
            'options' => [
                'json' => $data
            ]
        ]);
    }

    public function generateReviewInvitationLink(array $data)
    {
        $uri = "{$this->privateUri}/invitation-links";

        return $this->request([
            'uri' => $uri,
            'method' => 'POST',
            'useInvitationsClient' => true,
            'options' => [
                'json' => $data
            ]
        ]);
    }

    public function getInvitationTemplates()
    {
        $uri = "{$this->privateUri}/templates";

        return $this->request([
            'uri' => $uri,
            'useInvitationsClient' => true
        ]);
    }

    public function getInvitationsHistory(array $query = [])
    {
        $uri = "{$this->privateUri}/invitations/history";

        return $this->request([
            'uri' => $uri,
            'method' => 'GET',
            'useInvitationsClient' => true,
            'options' => [
                'query' => $query
            ]
        ]);
    }
}
