<?php

namespace App\Adapter\Interface;

interface IAmoCrmAuth {

    public function getTokensByRefreshToken(string $refreshToken);

    public function getTokensByAuthorizationCode();
    
}