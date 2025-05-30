<?php

declare(strict_types=1);

namespace App\Service;

use App\Adapter\Interface\IAmoCrmLead;
use App\Adapter\Interface\IAmoCrmProduct;
use App\Service\AuthService;
use App\Service\ProductService;
use App\Service\TaskService;

final class LeadService
{
    public function __construct(
        private AuthService $authService,
        private ProductService $productService,
        private TaskService $taskService,
        private IAmoCrmLead $leadAdapter,
        private IAmoCrmProduct $productAdapter
    ) { }
    
    public function create(int $contactId): void {

        $this->leadAdapter->setAccessToken($this->authService->getAccessToken());

        $response = $this->leadAdapter->createAmoCrmLead($contactId);
        
        if ($response->getStatusCode() === 200 || $response->getStatusCode() === 201) {
            
            $result = $response->toArray();
            $leadId = $result['_embedded']['leads'][0]['id'];

            $this->taskService->create($leadId);

            $this->productAdapter->setAccessToken($this->authService->getAccessToken());
            if ($this->isProductCatalogAvailable()) {
                $this->productService->createProducts($leadId);
            }

        } else {
            throw new AmoCrmApiException($response->getContent(false));
        }
    }

    private function isProductCatalogAvailable(): bool {

        $response = $this->productAdapter->getCatalogs();

        if ($response->getStatusCode() === 200) {
            foreach ($response->toArray()['_embedded']['catalogs'] as $catalog) {
                if ($catalog['type'] === 'products') {
                    return true;
                }
            }
            return false;
        }
        else {
            throw new AmoCrmApiException($response->getContent(false));
        }

    }

}