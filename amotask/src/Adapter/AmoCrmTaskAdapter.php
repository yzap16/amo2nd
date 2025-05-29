<?php
declare(strict_types=1);

namespace App\Adapter;

use App\Adapter\Interface\IAmoCrmTask;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class AmoCrmTaskAdapter implements IAmoCrmTask
{
    private string $accessToken;
    
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $subdomain
    ) {}

    public function createAmoCrmTask(int $leadId, int $randomUser, int $completeTill): ResponseInterface
    {
        $taskData = [
            [
                'entity_id' => $leadId,
                'entity_type' => 'leads', // Т.к. задача привязана к сделке
                'task_type_id' => 2, // Тип задачи - письмо
                'text' => 'Написать клиенту для уточнения деталей предстоящего заказа',
                'complete_till' => $completeTill,
                'responsible_user_id' => $randomUser
            ]
        ];

        return $this->httpClient->request('POST', "https://{$this->subdomain}/api/v4/tasks", [
            'headers' => [
                'Authorization' => "Bearer {$this->accessToken}",
                'Content-Type' => 'application/json',
            ],
            'json' => $taskData,
        ]);
      
    }

    public function setAccessToken(string $accessToken): void {

        $this->accessToken = $accessToken;
    }

    
}