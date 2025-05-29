<?php

declare(strict_types=1);

namespace App\Service;

use App\Adapter\Interface\IAmoCrmProduct;
use App\Configuration\AmoCrmApiConfig;
use App\Service\AuthService;
use Symfony\Component\HttpClient\HttpClient;

final class ProductService
{
    public function __construct(
        private AuthService $authService,
        private IAmoCrmProduct $productAdapter
    ) { }

    public function createProducts(int $leadId): void {

        $httpClient = HttpClient::create();
        $subdomain = 'yurza';
        $accessToken = $this->authService->getAccessToken();
        
        $this->productAdapter->setAccessToken($this->authService->getAccessToken());
                
        $productsData = $this->getProductsData();
        $response = $this->productAdapter->getProductsFromCrm($productsData);
        $linksData = $this->linkProduct($response->toArray()['_embedded']['elements']);
           
        $linkResponse = $this->productAdapter->linkProductToLead($leadId, $linksData);

        if ($linkResponse->getStatusCode() !== 200) {
            $error = $linkResponse->toArray(false);
            throw new \Exception("Ошибка привязки: " . print_r($error, true));
        }
    }

    private function getProductsData(): array {
        return [
                [
                    'name' => 'Стол раскладной',
                    'custom_fields_values' => [
                        [
                            'field_id' => 1006449,
                            'values' => [['value' => 4500]]
                        ]
                    ]
                ],
                [
                    'name' => 'Диван',
                    'custom_fields_values' => [
                        [
                            'field_id' => 1006449,
                            'values' => [['value' => 100000]]
                        ]
                    ]
                ],
        ];
    }

    private function linkProduct($createdProducts): array {

        foreach($createdProducts as $product) {
            $linksData[] = [
                'to_entity_id' => $product['id'],
                'to_entity_type' => 'catalog_elements',
                'metadata' => [
                    'quantity' => 1,
                    'catalog_id' => AmoCrmApiConfig::CATALOG_ID
                ]
            ];
        }

        return $linksData;

    }

}