<?php
declare(strict_types=1);

namespace App\Adapter;

use App\Adapter\Interface\IAmoCrmLead;
use App\Configuration\AmoCrmApiConfig;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class AmoCrmLeadAdapter implements IAmoCrmLead
{
    private string $accessToken;
    
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $subdomain
    ) {}

    public function createAmoCrmLead(int $contactId): ResponseInterface
    {
        return $this->httpClient->request('POST',
            "https://{$this->subdomain}/api/v4/leads",
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    [
                        'name' => 'Новая сделка контакта '.$contactId,
                        'price' => 1256,
                        'pipeline_id' => AmoCrmApiConfig::PIPELINE_ID,
                        'status_id' => AmoCrmApiConfig::LEAD_STATUS_ID,
                        '_embedded' => [
                            'contacts' => [
                                [
                                    'id' => $contactId,
                                    'is_main' => true
                                ]
                            ]
                        ]
                    ]
                ],
            ]
        );        
    }

    public function setAccessToken(string $accessToken): void {

        $this->accessToken = $accessToken;
    }

    
}