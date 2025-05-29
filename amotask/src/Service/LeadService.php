<?php

declare(strict_types=1);

namespace App\Service;

use App\Adapter\Interface\IAmoCrmLead;
use App\Service\AuthService;
use App\Service\ProductService;
use App\Service\TaskService;

final class LeadService
{
    public function __construct(
        private AuthService $authService,
        private ProductService $productService,
        private TaskService $taskService,
        private IAmoCrmLead $leadAdapter
    ) { }
    
    public function create(int $contactId): void {

        $this->leadAdapter->setAccessToken($this->authService->getAccessToken());

        $response = $this->leadAdapter->createAmoCrmLead($contactId);
        
        if ($response->getStatusCode() === 200 || $response->getStatusCode() === 201) {
            $result = $response->toArray();
            $leadId = $result['_embedded']['leads'][0]['id'];

            $this->taskService->create($leadId);
            $this->productService->createProducts($leadId);
        } else {
            throw new AmoCrmApiException($response->getContent(false));
        }
    }

}