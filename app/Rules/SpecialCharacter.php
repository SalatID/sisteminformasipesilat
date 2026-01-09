<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class SpecialCharacter implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return preg_match("/[!@#$%^&*-+?=]/", $value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return [':attribute' => 'The :attribute must be have Special Characters.'];
    }
}
