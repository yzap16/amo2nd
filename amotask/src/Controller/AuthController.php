<?php

namespace App\Controller;

use App\Service\AuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AuthController extends AbstractController
{
    private AuthService $authService;
    
    public function __construct(AuthService $authService) {
        $this->authService = $authService;
    }
    
    #[Route('/auth/login', name: 'app_auth')]
    public function login(): Response
    {
        $this->authService->login();
           
        return $this->json([
            'status' => 'success',
            'message' => 'Вы успешно залогинились'
        ], 200);
    }
}
