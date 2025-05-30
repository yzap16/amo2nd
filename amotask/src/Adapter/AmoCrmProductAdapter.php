<?php
declare(strict_types=1);

namespace App\Adapter;

use App\Adapter\Interface\IAmoCrmProduct;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class AmoCrmProductAdapter implements IAmoCrmProduct
{
    private string $accessToken;
    private int $catalogId;
    
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $subdomain
    ) {}

    public function getProductByName(string $productName): ResponseInterface {

        return $this->httpClient->request('GET', "https://{$this->subdomain}/api/v4/catalogs/{$this->catalogId}/elements?query={$productName}", [
            'headers' => [
                'Authorization' => "Bearer $this->accessToken",
                'Content-Type' => 'application/json',
            ]
        ]);
    }

    public function addProducts(array $productsData): ResponseInterface {

        return $this->httpClient->request('POST', "https://{$this->subdomain}/api/v4/catalogs/{$this->catalogId}/elements", [
            'headers' => [
                'Authorization' => "Bearer $this->accessToken",
                'Content-Type' => 'application/json',
            ],
            'json' => $productsData,
        ]);
    }

    public function linkProductToLead(int $leadId, array $linksData): ResponseInterface {

        return $this->httpClient->request('POST', "https://{$this->subdomain}/api/v4/leads/{$leadId}/link", [
            'headers' => [
                'Authorization' => "Bearer $this->accessToken",
                'Content-Type' => 'application/json',
            ],
            'json' => $linksData,
        ]);

    }

    public function getCustomFields() {

        return $this->httpClient->request('GET', "https://{$this->subdomain}/api/v4/catalogs/{$this->catalogId}/custom_fields", [
            'headers' => [
                'Authorization' => "Bearer $this->accessToken",
                'Content-Type' => 'application/json',
            ]
        ]);

    }
    
    public function getCatalogs() {

        return $this->httpClient->request('GET', "https://{$this->subdomain}/api/v4/catalogs", [
            'headers' => [
                'Authorization' => "Bearer $this->accessToken",
                'Content-Type' => 'application/json',
            ]
        ]);

    }
    
    public function setAccessToken(string $accessToken): void {
        $this->accessToken = $accessToken;
    }

    public function setCatalogId(int $catalogId): void {
        $this->catalogId = $catalogId;
    }
}