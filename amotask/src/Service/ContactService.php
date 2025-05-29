<?php

declare(strict_types=1);

namespace App\Service;

use App\Adapter\Interface\IAmoCrmContact;
use App\Dto\ContactCreateDTO;
use App\Exception\AmoCrmApiException;
use App\Exception\ValidationException;
use App\Service\AuthService;
use App\Service\LeadService;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ContactService
{
    public function __construct(
        private AuthService $authService,
        private LeadService $leadService,
        private IAmoCrmContact $contactAdapter,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) { }

    public function create(ContactCreateDTO $contactCreateDTO): void {

        $this->contactAdapter->setAccessToken($this->authService->getAccessToken());

        $customFieldsIds = $this->getCustomFieldsIds();
        $contactData = $this->getContactData($contactCreateDTO, $customFieldsIds);

        $duplicateContact = $this->findDuplicate($contactCreateDTO->phone);

        $response = $this->contactAdapter->createAmoCrmContact($contactData);

        $content = $response->toArray();
        
        if ($response->getStatusCode() === 200 || $response->getStatusCode() === 201) {
            
            $contactId = $content['_embedded']['contacts'][0]['id'];
            if ($duplicateContact !== false) {
                $this->addNote($contactId, $duplicateContact);
            }
            $this->leadService->create($contactId);
        } else {
            throw new amoCrmApiException(json_encode($content, JSON_UNESCAPED_UNICODE));
        }
    }

    public function getData(array $data): ContactCreateDTO {
        
        $normalizedData = [];

        foreach ($data as $key => $value) {
            $normalizedKey = str_replace(['contact_create_form[', ']'], '', $key);
            $normalizedData[$normalizedKey] = $value;
        }

        return $this->serializer->deserialize(
            json_encode($normalizedData),
            ContactCreateDTO::class,
            'json'
        );
    }

    public function validateData(ContactCreateDTO $contactCreateDTO): void {
        
        $errors = $this->validator->validate($contactCreateDTO);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $field = $error->getPropertyPath();
                $message = $error->getMessage();
                $errorMessages[$field] = $message;
            }

            throw new ValidationException($errorMessages);
        }    
    }

    private function getContactData(ContactCreateDTO $contactCreateDTO, array $customFieldsIds): array {

        return [
            [
                'name' => $contactCreateDTO->firstName.' '.$contactCreateDTO->lastName,
                'first_name' => $contactCreateDTO->firstName,
                'last_name' => $contactCreateDTO->lastName,
                'custom_fields_values' => [
                    [
                        'field_id' => $customFieldsIds['phone_field_id'],
                        'values' => [
                            [
                                'value' => $contactCreateDTO->phone,
                                'enum_code' => 'WORK',
                            ],
                        ],
                    ],
                    [
                        'field_id' => $customFieldsIds['email_field_id'],
                        'values' => [
                            [
                                'value' => $contactCreateDTO->email,
                                'enum_code' => 'WORK',
                            ],
                        ],
                    ],
                    [
                        'field_id' => $customFieldsIds['age_field_id'],
                        'values' => [
                            [
                                'value' => $contactCreateDTO->age
                            ],
                        ],
                    ],
                    [
                        'field_id' => $customFieldsIds['gender_field_id'],
                        'values' => [
                            [
                                'value' => $contactCreateDTO->gender
                            ],
                        ],
                    ],
                ],
            ],
        ];

    }

    private function addNote(int $contactId, int $duplicateId): void {
        
        $response = $this->contactAdapter->addNote($contactId, $duplicateId);

        $content = $response->toArray();

        if (!$response->getStatusCode() === 200 || $response->getStatusCode() === 201) {
            throw new amoCrmApiException(json_encode($content, JSON_UNESCAPED_UNICODE));
        }
    }

    private function findDuplicate(string $phone): int | bool {

        $response = $this->contactAdapter->findDuplicate($phone);

        if ($response->getStatusCode() === 204) {
            return false;
        }

        if ($response->getStatusCode() === 200) {
            return $response->toArray()['_embedded']['contacts'][0]['id'];
        } else {
            throw new AmoCrmApiException($response->getContent(false));
        }
    }

    private function getCustomFieldsIds(): array {

        $customFieldsIds = [];

        $response = $this->contactAdapter->getCustomFields();
        $customFields = $response->toArray()['_embedded']['custom_fields'] ?? [];

        foreach ($customFields as $field) {
            if ($field['name'] === 'Телефон') {
                $customFieldsIds['phone_field_id'] = $field['id'];
            }
            if ($field['name'] === 'Email') {
                $customFieldsIds['email_field_id'] = $field['id'];
            }
            if ($field['name'] === 'Возраст') {
                $customFieldsIds['age_field_id'] = $field['id'];
            }
            if ($field['name'] === 'Пол') {
                $customFieldsIds['gender_field_id'] = $field['id'];
            }
        }

        $fields = [];
                
        if (!array_key_exists('age_field_id', $customFieldsIds)) {
            $fields[] = [
                'name' => 'Возраст',
                'type' => 'text'
            ];   
        }

        if (!array_key_exists('gender_field_id', $customFieldsIds)) {
            $fields[] = [
                'name' => 'Пол',
                'type' => 'text'
            ];
        }

        if (is_array($fields) && !(count($fields) === 0)) {
            $response = $this->contactAdapter->addCustomFields($fields);

            foreach ($response->toArray()['_embedded']['custom_fields'] as $customField) {
                if ($customField['name'] === 'Возраст') {
                    $customFieldsIds['age_field_id'] = $customField['id'];
                }
                if ($customField['name'] === 'Пол') {
                    $customFieldsIds['gender_field_id'] = $customField['id'];
                }
            }
        }

        return $customFieldsIds;
    }

}