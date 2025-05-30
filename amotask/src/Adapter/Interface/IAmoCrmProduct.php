<?php
declare(strict_types=1);

namespace App\Adapter\Interface;

use Symfony\Contracts\HttpClient\ResponseInterface;

interface IAmoCrmProduct {

    public function addProducts(array $productsData): ResponseInterface;
    
    public function linkProductToLead(int $leadId, array $linksData): ResponseInterface;    
    
}