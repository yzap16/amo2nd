<?php
declare(strict_types=1);

namespace App\Adapter;

use App\Adapter\Interface\IAmoCrmContact;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class AmoCrmContactAdapter implements IAmoCrmContact
{
    private string $accessToken;
    
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $subdomain
    ) {}

    public function createAmoCrmContact(array $contactData): ResponseInterface
    {
        return $this->httpClient->request(
            'POST',
            "https://{$this->subdomain}/api/v4/contacts",
            [
                'headers' => [
                    'Authorization' => "Bearer {$this->accessToken}",
                    'Content-Type' => 'application/json',
                ],
                'json' => $contactData,
            ]
        );
    }

    public function setAccessToken(string $accessToken): void {

        $this->accessToken = $accessToken;
    }

    public function findDuplicate(string $phone): ResponseInterface
    {
        return $this->httpClient->request('GET', "https://{$this->subdomain}/api/v4/contacts?query={$phone}", [
            'headers' => [
                'Authorization' => "Bearer {$this->accessToken}",
                'Content-Type' => 'application/json',
            ]
        ]);
    }

    public function addNote(int $contactId, int $duplicateId): ResponseInterface {

        return $this->httpClient->request(
            'POST',
            "https://{$this->subdomain}/api/v4/contacts/{$contactId}/notes",
            [
                'headers' => [
                    'Authorization' => "Bearer {$this->accessToken}",
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    [
                        'note_type' => 'common',
                        'params' => [
                            'text' => 'У данного контакта имеется дубликат: https://'.$this->subdomain.'/contacts/detail/'.$duplicateId
                        ],
                    ]
                ],
            ]
        );

    }

    public function getCustomFields(): ResponseInterface
    {
        return $this->httpClient->request(
            'GET',
            "https://{$this->subdomain}/api/v4/contacts/custom_fields",
            [
                'headers' => [
                    'Authorization' => "Bearer {$this->accessToken}",
                    'Content-Type' => 'application/json',
                ]
            ]
        );
    }

    public function addCustomFields(array $customFields): ResponseInterface {
        return $this->httpClient->request(
            'POST',
            "https://{$this->subdomain}/api/v4/contacts/custom_fields",
            [
                'headers' => [
                    'Authorization' => "Bearer {$this->accessToken}",
                    'Content-Type' => 'application/json',
                ], 
                'json' => $customFields,
            ]);
    }
}