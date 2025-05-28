<?php

namespace App\Adapter;

use App\Adapter\Interface\IAmoCrmAuth;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AmoCrmAuthAdapter implements IAmoCrmAuth
{
    private string $accessToken;
    
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $subdomain,
        private string $client_id,
        private string $client_secret,
        private string $redirect_uri,
        private string $auth_code
    ) {}

    public function getTokensByRefreshToken(string $refreshToken) {
        
        return $this->httpClient->request('POST', "https://{$this->subdomain}./oauth2/access_token", [
            'json' => [
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'redirect_uri' => $this->redirect_uri,
            ],
        ]);
    }

    public function getTokensByAuthorizationCode() {

        return $this->httpClient->request('POST', "https://{$this->subdomain}./oauth2/access_token", [
            'json' => [
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'grant_type' => 'authorization_code',
                'code' => $this->auth_code,
                'redirect_uri' => $this->redirect_uri,
            ],
        ]);
    }
}