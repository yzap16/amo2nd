<?php

namespace App\Adapter\Interface;

interface IAmoCrmAuth {

    public function getTokensByRefreshToken();

    public function getTokensByAuthorizationCode();
    
}