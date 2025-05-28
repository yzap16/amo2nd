<?php

declare(strict_types=1);

namespace App\Service;

use App\Adapter\Interface\IAmoCrmUser;
use App\Service\AuthService;
use Symfony\Component\HttpClient\HttpClient;

final class UserService
{
    public function __construct(
        private AuthService $authService,
        private IAmoCrmUser $userAdapter
    ) { }

    public function getRandomUser() {

        $this->userAdapter->setAccessToken($this->authService->getAccessToken());

        $response = $this->userAdapter->getRandomAmoCrmUser();

        if ($response->getStatusCode() !== 200) {
            throw new amoCrmApiException(json_encode($response->getContent(false), JSON_UNESCAPED_UNICODE));
        }

        $data = $response->toArray();
        $users = $data['_embedded']['users'] ?? [];
        
        if (empty($users)) {
            throw new amoCrmApiException('Пользователи не найдены');
        }

        return $users[array_rand($users)];

    }

}