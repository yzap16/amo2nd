<?php

declare(strict_types=1);

namespace App\Service;

use App\Adapter\Interface\IAmoCrmProduct;
use App\Service\AuthService;
use Symfony\Component\HttpClient\HttpClient;

final class ProductService
{
    private int $catalogId;
    
    public function __construct(
        private AuthService $authService,
        private IAmoCrmProduct $productAdapter
    ) { }

    public function createProducts(int $leadId): void {

        $this->productAdapter->setAccessToken($this->authService->getAccessToken());
        $this->catalogId = $this->getCatalogId();
        $this->productAdapter->setCatalogId($this->catalogId);
                
        $productsData = $this->getProductQueryData();
                
        $newProducts = [];
        if (is_array($productsData['newProducts']) && !(count($productsData['newProducts']) === 0)) {
            $response = $this->productAdapter->addProducts($productsData['newProducts']);
            if ($response->getStatusCode() === 200) {
                $newProducts = $response->toArray()['_embedded']['elements'];
            }
            else {
                throw new AmoCrmApiException($response->getContent(false));
            }            
        }

        $linksData = $this->linkProduct(array_merge($newProducts, $productsData['existingProducts']));
           
        $linkResponse = $this->productAdapter->linkProductToLead($leadId, $linksData);

        if ($linkResponse->getStatusCode() !== 200) {
            $error = $linkResponse->toArray(false);
            throw new AmoCrmApiException($linkResponse->getContent(false));
        }
    }

    private function getProductsData(): array {

        $priceFieldId = $this->getPriceCustomFieldId();
        
        return [
                [
                    'name' => 'Стул',
                    'custom_fields_values' => [
                        [
                            'field_id' => $priceFieldId,
                            'values' => [['value' => 1500]]
                        ]
                    ]
                ],
                [
                    'name' => 'Шкаф',
                    'custom_fields_values' => [
                        [
                            'field_id' => $priceFieldId,
                            'values' => [['value' => 70000]]
                        ]
                    ]
                ],
        ];
    
    }
    
    private function getProductQueryData(): array {
        
        $newProducts = $this->getProductsData();
        
        $existingProducts = [];
        $keysToRemove = [];

        foreach ($newProducts as $key=>$product) {
            $response = $this->productAdapter->getProductByName($product['name']);
            
            if ($response->getStatusCode() === 200) {
                $keysToRemove[] = $key;
                $existingProducts[] = $response->toArray()['_embedded']['elements'][0];
            }
            else if ($response->getStatusCode() === 204) {
                continue;    
            }
            else {
                throw new AmoCrmApiException($response->getContent(false));
            }
        }
        
        foreach ($keysToRemove as $key) {
            unset($newProducts[$key]);
        }

        return [
            'newProducts' => $newProducts,
            'existingProducts' => $existingProducts
        ];
    }

    private function linkProduct(array $createdProducts): array {

        foreach($createdProducts as $product) {
            $linksData[] = [
                'to_entity_id' => $product['id'],
                'to_entity_type' => 'catalog_elements',
                'metadata' => [
                    'quantity' => 1,
                    'catalog_id' => $this->catalogId
                ]
            ];
        }

        return $linksData;

    }

    private function getPriceCustomFieldId(): int {

        $response = $this->productAdapter->getCustomFields();

        if ($response->getStatusCode() === 200) {
            foreach ($response->toArray()['_embedded']['custom_fields'] as $custom_fields) {
                if ($custom_fields['code'] === 'PRICE') {
                    return $custom_fields['id']; 
                }
            }
        }
        else {
            throw new AmoCrmApiException($response->getContent(false));
        }
    }
    
    private function getCatalogId(): int {
        
        $response = $this->productAdapter->getCatalogs();
        
        if ($response->getStatusCode() === 200) {
            foreach ($response->toArray()['_embedded']['catalogs'] as $catalog) {
                if ($catalog['type'] === 'products') {
                    return $catalog['id']; 
                }
            }
        }
        else {
            throw new AmoCrmApiException($response->getContent(false));
        }
    }
}