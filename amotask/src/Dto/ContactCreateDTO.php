<?php
namespace App\Dto;

use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

class ContactCreateDTO
{
    #[Assert\NotBlank]
    public string $firstName;

    #[Assert\NotBlank]
    public string $lastName;

    #[Assert\NotBlank]
    public string $age;

    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['Мужской', 'Женский'])]
    public string $gender;

    #[Assert\NotBlank]
    public string $phone;

    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;
}