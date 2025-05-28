<?php

namespace App\Adapter;

use App\Adapter\Interface\IAmoCrmProduct;
use App\Configuration\AmoCrmApiConfig;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AmoCrmProductAdapter implements IAmoCrmProduct
{
    private string $accessToken;
    
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $subdomain
    ) {}

    public function getProductsFromCrm(array $productsData) {

        $catalogId = AmoCrmApiConfig::CATALOG_ID;
        
        return $this->httpClient->request('POST', "https://{$this->subdomain}/api/v4/catalogs/{$catalogId}/elements", [
            'headers' => [
                'Authorization' => "Bearer $this->accessToken",
                'Content-Type' => 'application/json',
            ],
            'json' => $productsData,
        ]);
    }

    public function linkProductToLead(int $leadId, array $linksData) {

        return $this->httpClient->request('POST', "https://{$this->subdomain}/api/v4/leads/{$leadId}/link", [
            'headers' => [
                'Authorization' => "Bearer $this->accessToken",
                'Content-Type' => 'application/json',
            ],
            'json' => $linksData,
        ]);

    }
    
    public function setAccessToken(string $accessToken): void {

        $this->accessToken = $accessToken;
    }
}