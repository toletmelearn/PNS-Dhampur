<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class AadhaarChecksum implements Rule
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
        // Remove any spaces or non-digit characters
        $aadhaar = preg_replace('/[^0-9]/', '', $value);
        
        // Must be exactly 12 digits
        if (strlen($aadhaar) !== 12) {
            return false;
        }
        
        // First digit cannot be 0 or 1
        if ($aadhaar[0] === '0' || $aadhaar[0] === '1') {
            return false;
        }
        
        // Verhoeff algorithm implementation for Aadhaar validation
        return $this->verhoeffCheck($aadhaar);
    }

    /**
     * Verhoeff algorithm implementation for Aadhaar number validation.
     *
     * @param  string  $number
     * @return bool
     */
    protected function verhoeffCheck($number)
    {
        $d = [
            [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
            [1, 2, 3, 4, 0, 6, 7, 8, 9, 5],
            [2, 3, 4, 0, 1, 7, 8, 9, 5, 6],
            [3, 4, 0, 1, 2, 8, 9, 5, 6, 7],
            [4, 0, 1, 2, 3, 9, 5, 6, 7, 8],
            [5, 9, 8, 7, 6, 0, 4, 3, 2, 1],
            [6, 5, 9, 8, 7, 1, 0, 4, 3, 2],
            [7, 6, 5, 9, 8, 2, 1, 0, 4, 3],
            [8, 7, 6, 5, 9, 3, 2, 1, 0, 4],
            [9, 8, 7, 6, 5, 4, 3, 2, 1, 0]
        ];
        
        $p = [
            [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
            [1, 5, 7, 6, 2, 8, 3, 0, 9, 4],
            [5, 8, 0, 3, 7, 9, 6, 1, 4, 2],
            [8, 9, 1, 6, 0, 4, 3, 5, 2, 7],
            [9, 4, 5, 3, 1, 2, 6, 8, 7, 0],
            [4, 2, 8, 6, 5, 7, 3, 9, 0, 1],
            [2, 7, 9, 3, 8, 0, 6, 4, 1, 5],
            [7, 0, 4, 6, 9, 1, 3, 2, 5, 8]
        ];
        
        $inv = [0, 4, 3, 2, 1, 5, 6, 7, 8, 9];
        
        $c = 0;
        $len = strlen($number);
        
        for ($i = 0; $i < $len; $i++) {
            $c = $d[$c][$p[($i % 8)][$number[$len - $i - 1]]];
        }
        
        return $c === 0;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The Aadhaar number is not valid. Please check the number and try again.';
    }
}