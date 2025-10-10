<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Carbon\Carbon;

class ValidAgeRange implements Rule
{
    protected $minAge;
    protected $maxAge;

    /**
     * Create a new rule instance.
     *
     * @param  int  $minAge
     * @param  int  $maxAge
     * @return void
     */
    public function __construct($minAge = 5, $maxAge = 25)
    {
        $this->minAge = $minAge;
        $this->maxAge = $maxAge;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (!$value) {
            return false;
        }

        try {
            $birthDate = Carbon::parse($value);
            $age = $birthDate->age;
            
            return $age >= $this->minAge && $age <= $this->maxAge;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return "The age must be between {$this->minAge} and {$this->maxAge} years old.";
    }
}