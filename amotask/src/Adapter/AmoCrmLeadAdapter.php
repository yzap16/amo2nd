<?php
declare(strict_types=1);

namespace App\Adapter;

use App\Adapter\Interface\IAmoCrmLead;
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
                        'pipeline_id' => $this->getPipelineId()['pipeline_id'],
                        'status_id' => $this->getPipelineId()['lead_status_id'],
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

    private function getPipelineId(): array {

        $response = $this->httpClient->request('GET', "https://{$this->subdomain}/api/v4/leads/pipelines",
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Content-Type' => 'application/json',
                ],
            ]
        );

        if ($response->getStatusCode() === 200) {
            return [
                'pipeline_id' => $response->toArray()['_embedded']['pipelines'][0]['id'],
                'lead_status_id' => $response->toArray()['_embedded']['pipelines'][0]['_embedded']['statuses'][2]['id']
            ];
        } else {
            throw new AmoCrmApiException($response->getContent(false));
        }   
    }
}