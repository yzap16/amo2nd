<?php
declare(strict_types=1);

namespace App\Controller;

use App\Dto\ContactCreateDTO;
use App\Exception\AmoCrmApiException;
use App\Exception\ValidationException;
use App\Form\ContactCreateFormType;
use App\Service\ContactService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ContactController extends AbstractController
{
    public function __construct(
        private ContactService $contactService,
        private LoggerInterface $logger
    ) { }
    
    #[Route('/contact/form', name: 'contact_form', methods: ['GET'])]
    public function showForm(): Response
    {
        $form = $this->createForm(ContactCreateFormType::class, new ContactCreateDTO());
        
        return $this->render('contact/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/api/contacts', name: 'contact_create', methods: ['POST'])]
    public function createContact(
        Request $request
    ): JsonResponse {
        
        try {
            $contactCreateDTO = $this->contactService->getData(json_decode($request->getContent(), true));
            $this->contactService->validateData($contactCreateDTO);
            $contact = $this->contactService->create($contactCreateDTO);
            
            return $this->json([
                'status' => 'sucсess',
                'message' => 'Контакт и связанные с ним сущности успешно созданы!'
            ], 201);
        }
        catch (ValidationException $e) {
            $this->logError('Ошибка валидации', ['exception' => $e]);
            
            return new JsonResponse([
                'status' => 'validation_error',
                'errors'  => $e->getErrors(),
                'message' => $e->getMessage()                
            ], 422);

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

    private function logError(string $message, array $errors): void {
        $this->logger->error($message, $errors);
    }
}