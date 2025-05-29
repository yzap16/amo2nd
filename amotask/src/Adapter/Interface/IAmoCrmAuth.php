<?php
declare(strict_types=1);

namespace App\Adapter\Interface;

interface IAmoCrmAuth {

    public function getTokensByRefreshToken(string $refreshToken);

    public function getTokensByAuthorizationCode();
    
}