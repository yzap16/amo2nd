<?php

declare(strict_types=1);

namespace App\Service;

use App\Adapter\Interface\IAmoCrmTask;
use App\Exception\AmoCrmApiException;
use App\Service\AuthService;
use App\Service\UserService;
use Symfony\Component\HttpClient\HttpClient;
use \DateTime;

final class TaskService
{
    public function __construct(
        private AuthService $authService,
        private UserService $userService,
        private IAmoCrmTask $taskAdapter
    ) { }

    public function create(int $leadId): void {

        $this->taskAdapter->setAccessToken($this->authService->getAccessToken());
        $randomUser = $this->userService->getRandomUser();

        //Крайний срок выполнения задачи - через 4 рабочих дня
        $completeTill = $this->calculateDueDate(4);
        
        $response = $this->taskAdapter->createAmoCrmTask($leadId, $randomUser['id'], $completeTill);

        if ($response->getStatusCode() === 200 || $response->getStatusCode() === 201) {
            $result = $response->toArray();
        } else {
            throw new AmoCrmApiException($response->getContent(false));
        }

    }

    private function calculateDueDate($daysToAdd, $workStartHour = 9, $workEndHour = 18): int {
        
        $now = new DateTime();
        $currentHour = (int)$now->format('H');
        
        // Если текущее время после рабочего времени, начинаем отсчет со следующего дня
        if ($currentHour >= $workEndHour) {
            $now->modify('+1 day');
            $now->setTime($workStartHour, 0);
            $daysToAdd--; //...уже учли переход на следующий день
        }
        // Если текущее время до начала рабочего дня, устанавливаем начало рабочего дня
        elseif ($currentHour < $workStartHour) {
            $now->setTime($workStartHour, 0);
        }
        
        // Добавляем рабочие дни (исключая выходные)
        $addedDays = 0;
        while ($addedDays < $daysToAdd) {
            $now->modify('+1 day');
            // Проверяем, не выходной ли это день (6 - это суббота, 0 - воскресенье)
            if (!in_array($now->format('w'), [0, 6])) {
                $addedDays++;
            }
        }
        
        // Убедимся, что время выбрано в пределах рабочего дня
        $hour = (int)$now->format('H');
        if ($hour < $workStartHour) {
            $now->setTime($workStartHour, 0);
        } elseif ($hour >= $workEndHour) {
            $now->setTime($workEndHour - 1, 59); // 17:59, так как нужно точно попасть в рабочий день
        }
        
        return $now->getTimestamp();
    }
    

}