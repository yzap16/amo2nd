<?php
declare(strict_types=1);

namespace App\Adapter;

use App\Adapter\Interface\IAmoCrmUser;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class AmoCrmUserAdapter implements IAmoCrmUser
{
    private string $accessToken;
    
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $subdomain
    ) {}

    public function getRandomAmoCrmUser(): ResponseInterface {
        return $this->httpClient->request('GET', "https://{$this->subdomain}/api/v4/users", [
            'headers' => [
                'Authorization' => "Bearer {$this->accessToken}",
            ],
        ]);
    }

    public function setAccessToken(string $accessToken): void {
        $this->accessToken = $accessToken;
    }    
}