<?php

namespace App\Adapter\Interface;

interface IAmoCrmProduct {

    public function getProductsFromCrm(array $productsData);

    public function linkProductToLead(int $leadId, array $linksData);

    
    
}