<?php

namespace App\Adapter\Interface;

interface IAmoCrmTask {

    public function createAmoCrmTask(int $leadId, int $randomUser, int $completeTill);
    
}