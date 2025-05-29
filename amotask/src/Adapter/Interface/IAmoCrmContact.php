<?php
declare(strict_types=1);

namespace App\Adapter\Interface;

interface IAmoCrmContact {

    public function createAmoCrmContact(array $contactData);

    public function setAccessToken(string $accessToken): void;

    public function findDuplicate(string $phone);

    public function addNote(int $contactId, int $duplicateId);
    
}