<?php

namespace SmartContact\TrustpilotApiService;

use GuzzleHttp\Client;
use Carbon\Carbon;

class TrustpilotApiService
{
    protected Client $client;
    protected ?Client $invitationsClient = null;
    protected ?array $accessToken = null;
    private array $credentials;
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
     *
     * @param array $options 
     * @param string $options['business_unit_id']
     */
    public function __construct(array $options)
    {
        $this->apiVersion = array_key_exists('version', $options) ? $options['version'] : 'v1';

        $this->client = new Client([
            'base_uri' => "https://api.trustpilot.com/{$this->apiVersion}/"
        ]);

        $this->secrets = [
            'api_key' => $options['api_key'],
            'api_secret' => $options['api_secret']
        ];

        $this->credentials = [
            'username' => $options['username'],
            'password' => $options['password']
        ];

        $this->businessUnitId = $options['business_unit_id'];
        $this->privateUri = "private/business-units/{$this->businessUnitId}";

        $this->authenticate($this->secrets, $this->credentials);
    }

    protected function getInvitationsClient()
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
    protected function request(array $config)
    {
        $config = array_merge($this->defaultRequestConfig, $config);
        [
            'uri' => $uri,
            'method' => $method,
            'options' => $options,
        ] = $config;

        if (!array_key_exists('headers', $options)) $options['headers'] = [];

        if ($this->isPrivateRequest($uri)) {
            if (!$this->hasValidAccessToken())
                $this->authenticate($this->secrets, $this->credentials);

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
    protected function authenticate(array $secrets, array $credentials): self
    {
        $uri = "oauth/oauth-business-users-for-applications/accesstoken";

        $authorization = base64_encode(join(':', $secrets));

        $response = $this->client->post(
            $uri,
            [
                'headers' => [
                    'Authorization' => $authorization
                ],
                'form_params' => array_merge(
                    $credentials,
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


    public function getReviews(array $params = [])
    {
        $uri = "{$this->privateUri}/reviews";

        return $this->request([
            'uri' => $uri,
            'options' => [
                'params' => $params
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
}