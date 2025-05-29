<?php
declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

final class ValidationException extends HttpException
{
    private array $errors;

    public function __construct(
        array $errors, 
        string $message = 'Validation failed', 
        int $statusCode = 422
    ) {
        parent::__construct($statusCode, $message);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}