<?php

namespace App\Service\Validation;

use App\Service\Validation\Exception\NotEnoughPotatoException;

class Validation
{
    public static function amount(int $amount, int $recieversCount)
    {
        if ($amount * $recieversCount > 5) {
            throw new NotEnoughPotatoException('You don\'t have enough potato 😢');
        }
    }
}