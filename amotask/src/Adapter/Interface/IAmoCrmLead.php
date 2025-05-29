<?php
declare(strict_types=1);

namespace App\Adapter\Interface;

interface IAmoCrmLead {

    public function createAmoCrmLead(int $contactId);
    
}