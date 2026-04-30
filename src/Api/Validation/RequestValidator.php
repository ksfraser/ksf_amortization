<?php

namespace App\Api\Validation;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Phase 1: Request Validation Service
 * Standardizes input validation across all API endpoints
 */
class RequestValidator
{
    /**
     * Validate loan creation request
     */
    public static function validateLoanCreation(array $data): array
    {
        return self::validate($data, [
            'borrower_id' => 'required|integer|exists:0_debtors,debtor_no',
            'amount' => 'required|numeric|min:1000|max:1000000',
            'term_months' => 'required|integer|in:12,24,36,48,60',
            'interest_rate' => 'required|numeric|min:0|max:25',
            'loan_type' => 'required|string|in:personal,auto,business',
            'purpose' => 'nullable|string|max:255'
        ]);
    }

    /**
     * Validate payment request
     */
    public static function validatePaymentCreation(array $data): array
    {
        return self::validate($data, [
            'loan_id' => 'required|integer|exists:0_ksf_loans,loan_id',
            'amount' => 'required|numeric|min:0.01|max:999999.99',
            'payment_method' => 'required|string|in:ach,card,wire',
            'reference_number' => 'nullable|string|max:50'
        ]);
    }

    /**
     * Validate user registration
     */
    public static function validateUserRegistration(array $data): array
    {
        return self::validate($data, [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:12|confirmed',
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'role' => 'required|string|in:admin,loan_officer,collector,borrower,finance'
        ]);
    }

    /**
     * Validate login credentials
     */
    public static function validateLogin(array $data): array
    {
        return self::validate($data, [
            'email' => 'required|email',
            'password' => 'required|string'
        ]);
    }

    /**
     * Core validation method
     */
    private static function validate(array $data, array $rules): array
    {
        $validator = \Validator::make($data, $rules);

        if ($validator->fails()) {
            throw ValidationException::withMessages(
                $validator->errors()->toArray()
            );
        }

        return $validator->validated();
    }
}
