<?php

if (!function_exists('generateOTP')) {
    /**
     * Generate a random OTP and store it for the user.
     *
     * @param \App\Models\User $user
     * @return int
     */
    function generateOTP($user)
    {
        $otp = rand(pow(10, 5), pow(10, 6) - 1);  // Adjust the length if needed
        $user->otp = $otp;  // Update the user model directly

        // Log the generated OTP for debugging
        \Illuminate\Support\Facades\Log::info("Generated OTP for user {$user->id}: $otp");

        return $otp;
    }
}