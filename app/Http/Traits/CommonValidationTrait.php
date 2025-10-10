<?php

namespace App\Http\Traits;

trait CommonValidationTrait
{
    /**
     * Get common name validation rules
     *
     * @param bool $required Whether name is required
     * @param int $maxLength Maximum length for name
     * @return array
     */
    protected function getNameValidationRules(bool $required = true, int $maxLength = 255): array
    {
        $rules = ['string', "max:{$maxLength}"];
        
        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }
        
        return ['name' => $rules];
    }

    /**
     * Get phone number validation rules
     *
     * @param bool $required Whether phone is required
     * @param int $maxLength Maximum length for phone
     * @return array
     */
    protected function getPhoneValidationRules(bool $required = false, int $maxLength = 20): array
    {
        $rules = ['string', "max:{$maxLength}", 'regex:/^[0-9+\-\s\(\)]+$/'];
        
        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }
        
        return ['phone' => $rules];
    }

    /**
     * Get address validation rules
     *
     * @param bool $required Whether address is required
     * @param int $maxLength Maximum length for address
     * @return array
     */
    protected function getAddressValidationRules(bool $required = false, int $maxLength = 1000): array
    {
        $rules = ['string', "max:{$maxLength}"];
        
        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }
        
        return ['address' => $rules];
    }

    /**
     * Get description validation rules
     *
     * @param bool $required Whether description is required
     * @param int $maxLength Maximum length for description
     * @return array
     */
    protected function getDescriptionValidationRules(bool $required = false, int $maxLength = 500): array
    {
        $rules = ['string', "max:{$maxLength}"];
        
        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }
        
        return ['description' => $rules];
    }

    /**
     * Get title validation rules
     *
     * @param bool $required Whether title is required
     * @param int $maxLength Maximum length for title
     * @return array
     */
    protected function getTitleValidationRules(bool $required = true, int $maxLength = 255): array
    {
        $rules = ['string', "max:{$maxLength}"];
        
        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }
        
        return ['title' => $rules];
    }

    /**
     * Get reason validation rules (for rejections, cancellations, etc.)
     *
     * @param bool $required Whether reason is required
     * @param int $maxLength Maximum length for reason
     * @return array
     */
    protected function getReasonValidationRules(bool $required = true, int $maxLength = 500): array
    {
        $rules = ['string', "max:{$maxLength}"];
        
        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }
        
        return ['reason' => $rules];
    }

    /**
     * Get comments validation rules
     *
     * @param bool $required Whether comments are required
     * @param int $maxLength Maximum length for comments
     * @return array
     */
    protected function getCommentsValidationRules(bool $required = false, int $maxLength = 1000): array
    {
        $rules = ['string', "max:{$maxLength}"];
        
        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }
        
        return ['comments' => $rules];
    }

    /**
     * Get notes validation rules
     *
     * @param bool $required Whether notes are required
     * @param int $maxLength Maximum length for notes
     * @return array
     */
    protected function getNotesValidationRules(bool $required = false, int $maxLength = 1000): array
    {
        $rules = ['string', "max:{$maxLength}"];
        
        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }
        
        return ['notes' => $rules];
    }

    /**
     * Get academic year validation rules
     *
     * @param bool $required Whether academic year is required
     * @return array
     */
    protected function getAcademicYearValidationRules(bool $required = true): array
    {
        $rules = ['string', 'max:20', 'regex:/^\d{4}-\d{4}$/'];
        
        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }
        
        return ['academic_year' => $rules];
    }

    /**
     * Get status validation rules
     *
     * @param array $allowedStatuses List of allowed status values
     * @param bool $required Whether status is required
     * @return array
     */
    protected function getStatusValidationRules(array $allowedStatuses = ['active', 'inactive'], bool $required = true): array
    {
        $statusList = implode(',', $allowedStatuses);
        $rules = ['string', "in:{$statusList}"];
        
        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }
        
        return ['status' => $rules];
    }

    /**
     * Get priority validation rules
     *
     * @param bool $required Whether priority is required
     * @return array
     */
    protected function getPriorityValidationRules(bool $required = false): array
    {
        $rules = ['string', 'in:low,normal,high,urgent'];
        
        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }
        
        return ['priority' => $rules];
    }

    /**
     * Get numeric amount validation rules
     *
     * @param bool $required Whether amount is required
     * @param float $min Minimum value
     * @param float|null $max Maximum value
     * @return array
     */
    protected function getAmountValidationRules(bool $required = true, float $min = 0, ?float $max = null): array
    {
        $rules = ['numeric', "min:{$min}"];
        
        if ($max !== null) {
            $rules[] = "max:{$max}";
        }
        
        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }
        
        return ['amount' => $rules];
    }

    /**
     * Get quantity validation rules
     *
     * @param bool $required Whether quantity is required
     * @param int $min Minimum value
     * @param int|null $max Maximum value
     * @return array
     */
    protected function getQuantityValidationRules(bool $required = true, int $min = 1, ?int $max = null): array
    {
        $rules = ['integer', "min:{$min}"];
        
        if ($max !== null) {
            $rules[] = "max:{$max}";
        }
        
        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }
        
        return ['quantity' => $rules];
    }

    /**
     * Get city validation rules
     *
     * @param bool $required Whether city is required
     * @param int $maxLength Maximum length for city
     * @return array
     */
    protected function getCityValidationRules(bool $required = false, int $maxLength = 100): array
    {
        $rules = ['string', "max:{$maxLength}"];
        
        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }
        
        return ['city' => $rules];
    }

    /**
     * Get state validation rules
     *
     * @param bool $required Whether state is required
     * @param int $maxLength Maximum length for state
     * @return array
     */
    protected function getStateValidationRules(bool $required = false, int $maxLength = 100): array
    {
        $rules = ['string', "max:{$maxLength}"];
        
        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }
        
        return ['state' => $rules];
    }

    /**
     * Get postal code validation rules
     *
     * @param bool $required Whether postal code is required
     * @param int $maxLength Maximum length for postal code
     * @return array
     */
    protected function getPostalCodeValidationRules(bool $required = false, int $maxLength = 20): array
    {
        $rules = ['string', "max:{$maxLength}"];
        
        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }
        
        return ['postal_code' => $rules];
    }

    /**
     * Get country validation rules
     *
     * @param bool $required Whether country is required
     * @param int $maxLength Maximum length for country
     * @return array
     */
    protected function getCountryValidationRules(bool $required = false, int $maxLength = 100): array
    {
        $rules = ['string', "max:{$maxLength}"];
        
        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }
        
        return ['country' => $rules];
    }

    /**
     * Get contact person validation rules
     *
     * @param bool $required Whether contact person is required
     * @param int $maxLength Maximum length for contact person
     * @return array
     */
    protected function getContactPersonValidationRules(bool $required = false, int $maxLength = 255): array
    {
        $rules = ['string', "max:{$maxLength}"];
        
        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }
        
        return ['contact_person' => $rules];
    }

    /**
     * Get common validation messages
     *
     * @return array
     */
    protected function getCommonValidationMessages(): array
    {
        return [
            'name.required' => 'Name is required.',
            'name.string' => 'Name must be a valid text.',
            'name.max' => 'Name cannot exceed :max characters.',
            
            'phone.string' => 'Phone number must be a valid text.',
            'phone.max' => 'Phone number cannot exceed :max characters.',
            'phone.regex' => 'Phone number format is invalid.',
            
            'address.string' => 'Address must be a valid text.',
            'address.max' => 'Address cannot exceed :max characters.',
            
            'description.string' => 'Description must be a valid text.',
            'description.max' => 'Description cannot exceed :max characters.',
            
            'title.required' => 'Title is required.',
            'title.string' => 'Title must be a valid text.',
            'title.max' => 'Title cannot exceed :max characters.',
            
            'reason.required' => 'Reason is required.',
            'reason.string' => 'Reason must be a valid text.',
            'reason.max' => 'Reason cannot exceed :max characters.',
            
            'comments.string' => 'Comments must be a valid text.',
            'comments.max' => 'Comments cannot exceed :max characters.',
            
            'notes.string' => 'Notes must be a valid text.',
            'notes.max' => 'Notes cannot exceed :max characters.',
            
            'academic_year.required' => 'Academic year is required.',
            'academic_year.string' => 'Academic year must be a valid text.',
            'academic_year.max' => 'Academic year cannot exceed :max characters.',
            'academic_year.regex' => 'Academic year must be in format YYYY-YYYY (e.g., 2023-2024).',
            
            'status.required' => 'Status is required.',
            'status.string' => 'Status must be a valid text.',
            'status.in' => 'Selected status is invalid.',
            
            'priority.string' => 'Priority must be a valid text.',
            'priority.in' => 'Selected priority is invalid.',
            
            'amount.required' => 'Amount is required.',
            'amount.numeric' => 'Amount must be a valid number.',
            'amount.min' => 'Amount must be at least :min.',
            'amount.max' => 'Amount cannot exceed :max.',
            
            'quantity.required' => 'Quantity is required.',
            'quantity.integer' => 'Quantity must be a valid number.',
            'quantity.min' => 'Quantity must be at least :min.',
            'quantity.max' => 'Quantity cannot exceed :max.',
            
            'city.string' => 'City must be a valid text.',
            'city.max' => 'City cannot exceed :max characters.',
            
            'state.string' => 'State must be a valid text.',
            'state.max' => 'State cannot exceed :max characters.',
            
            'postal_code.string' => 'Postal code must be a valid text.',
            'postal_code.max' => 'Postal code cannot exceed :max characters.',
            
            'country.string' => 'Country must be a valid text.',
            'country.max' => 'Country cannot exceed :max characters.',
            
            'contact_person.string' => 'Contact person must be a valid text.',
            'contact_person.max' => 'Contact person cannot exceed :max characters.',
        ];
    }
}