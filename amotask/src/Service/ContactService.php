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

        $data = $this->getAgeAndGenderIds();
        $contactData = $this->getContactData($contactCreateDTO, $data['age_field_id'], $data['gender_field_id']);

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

    private function getContactData(ContactCreateDTO $contactCreateDTO, ?int $ageFieldId = null, ?int $genderFieldId = null): array {

        return [
            [
                'name' => $contactCreateDTO->firstName.' '.$contactCreateDTO->lastName,
                'first_name' => $contactCreateDTO->firstName,
                'last_name' => $contactCreateDTO->lastName,
                'custom_fields_values' => [
                    [
                        'field_id' => 1005173,
                        'values' => [
                            [
                                'value' => $contactCreateDTO->phone,
                                'enum_code' => 'WORK',
                            ],
                        ],
                    ],
                    [
                        'field_id' => 1005175,
                        'values' => [
                            [
                                'value' => $contactCreateDTO->email,
                                'enum_code' => 'WORK',
                            ],
                        ],
                    ],
                    [
                        'field_id' => $ageFieldId,
                        'values' => [
                            [
                                'value' => $contactCreateDTO->age
                            ],
                        ],
                    ],
                    [
                        'field_id' => $genderFieldId,
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

    private function getAgeAndGenderIds(): array {

        $ageFieldId = null;
        $genderFieldId = null;

        $response = $this->contactAdapter->getCustomFields();
        $customFields = $response->toArray()['_embedded']['custom_fields'] ?? [];

        foreach ($customFields as $field) {
            if ($field['name'] === 'Возраст') {
                $ageFieldId = $field['id'];
            }
            if ($field['name'] === 'Пол') {
                $genderFieldId = $field['id'];
            }
        }

        if (!$ageFieldId) {
            $response = $this->contactAdapter->addAgeCustomField();
                  
            if ($response->getStatusCode() === 200 || $response->getStatusCode() === 201) {
                $ageFieldId = $response->toArray()['id'];
            } else {
                throw new amoCrmApiException(json_encode($content, JSON_UNESCAPED_UNICODE));
            }       
        }

        if (!$genderFieldId) {
            $response = $this->contactAdapter->addGenderCustomField();
            
            if ($response->getStatusCode() === 200 || $response->getStatusCode() === 201) {
                $genderFieldId = $response->toArray()['id'];
            } else {
                throw new amoCrmApiException(json_encode($content, JSON_UNESCAPED_UNICODE));
            }
        }

        return [
            'age_field_id' => $ageFieldId,
            'gender_field_id' => $genderFieldId
        ];

    }

}