<?php
declare(strict_types=1);

namespace App\Adapter\Interface;

interface IAmoCrmTask {

    public function createAmoCrmTask(int $leadId, int $randomUser, int $completeTill);
    
}