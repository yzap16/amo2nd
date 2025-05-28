<?php

namespace App\Controller;

use App\Exception\AmoCrmApiException;
use App\Service\AuthService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AuthController extends AbstractController
{
    public function __construct(
        private AuthService $authService,
        private LoggerInterface $logger
    ) { }
    
    #[Route('/auth/login', name: 'app_auth')]
    public function login(): Response
    {
        try {
            $this->authService->login();

            return $this->json([
                'status' => 'success',
                'message' => 'Вы успешно залогинились'
            ], 200);
        }
        catch (ClientException $e) {
            
            $errorResponse = $e->getResponse();
            $errorDetails = $errorResponse->toArray(false);

            $this->logError('Ошибка клиента', ['exception' => $e]);
            
            return $this->json([
                'status' => 'server_error',
                'errors' => $e->getMessage(),
                'message' => 'Кажется, что-то пошло не так. Повторите свой запрос позднее.'
            ], 500);

        }
        catch (AmoCrmApiException $e) {
            
            $this->logError('Ошибка API', ['exception' => $e]);
            
            return $this->json([
                'status' => 'api_error',
                'errors' => $e->getMessage(),
                'message' => 'Кажется, что-то пошло не так...'
            ], 500);

        }
        catch (\Throwable $e) {

            $this->logError('Ошибка', ['exception' => $e]);
            
            return $this->json([
                'status' => 'server_error',
                'errors' => $e->getMessage(),
                'message' => 'Кажется, что-то пошло не так...'
            ], 500);
            
        }
    }

    private function logError(string $message, array $errors) {
        return $this->logger->error($message, $errors);
    }
}
