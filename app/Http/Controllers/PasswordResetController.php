<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PasswordReset;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Mail\Message;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PasswordResetController extends Controller
{
    public function sendOtp(Request $request)
    {
        
        $request->validate(['email' => 'required|email']);

        $email = $request->email;
        $otp = '1234'; // static OTP for testing
        $expiresAt = Carbon::now()->addMinutes(5); // OTP expires in 5 minutes
        Log::info("Generated OTP for user $email");

        $user = User::where('email', $request->email)->first();

        // Step 4: Check if the user exists
        if (!$user) {
            return response()->json([
                'status' => 404,
                'success' => false,
                'message' => 'User not found.'
            ], 404);
        }
        
        DB::table('password_resets')->updateOrInsert(
            ['email' => $email],
            ['otp' => $otp, 'otp_expires_at' => $expiresAt]
        );

        // Send OTP via Email
        // Mail::raw("Your OTP is: $otp", function ($message) use ($email) {
        //     $message->to($email)->subject('Password Reset OTP');
        // });

        return response()->json([
            'status' => 200,
            'success' => true,
            'message' => 'OTP sent to your email.'
        ], 200);
    }

    // Step 2: Verify OTP
    public function verifyOtp(Request $request)
    {
        $request->validate(['email' => 'required|email', 'otp' => 'required']);

        $record = DB::table('password_resets')->where('email', $request->email)->first();

        if (!$record || $record->otp !== $request->otp || Carbon::now()->isAfter($record->otp_expires_at)) {
            return response()->json([
                'status' => 400,
                'success' => false,
                'message' => 'Invalid or expired OTP.'
            ], 400);
        }

        return response()->json([
            'status' => 200,
            'success' => true,
            'message' => 'OTP verified.'
        ], 200);
    }

    // Step 3: Change Password
    public function changePassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed',
            'otp' => 'required'
        ]);
        
        // Step 1: Check the OTP record
        $record = DB::table('password_resets')->where('email', $request->email)->first();

        // Step 3: Find the user
        $user = User::where('email', $request->email)->first();

        // Step 4: Check if the user exists
        if (!$user) {
            return response()->json([
                'status' => 404,
                'success' => false,
                'message' => 'User not found.'
            ], 404);
        }

        // Step 2: Validate the OTP and its expiration
        if (!$record || $record->otp !== $request->otp || Carbon::now()->isAfter($record->otp_expires_at)) {
            return response()->json([
                'status' => 400,
                'success' => false,
                'message' => 'Invalid or expired OTP.'
            ], 400);
        }

        
        // Step 5: Update User Password
        $user->password = bcrypt($request->password);
        $user->save();

        // Step 6: Delete the OTP record
        DB::table('password_resets')->where('email', $request->email)->delete();

        return response()->json([
            'status' => 200,
            'success' => true,
            'message' => 'Password changed successfully.'
        ], 200);
    }
    
    public function send_reset_password_email(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);
        $email = $request->email;

        // Check User's Email Exists or Not
        $user = User::where('email', $email)->first();
        if (!$user) {
            return response([
                'message' => 'Email doesnt exists',
                'status' => 'failed'
            ], 404);
        }

        // Generate Token
        $token = Str::random(60);

        // Saving Data to Password Reset Table
        PasswordReset::create([
            'email' => $email,
            'token' => $token,
            'created_at' => Carbon::now()
        ]);

        // dump("http://127.0.0.1:3000/api/user/rest/" . $token);

        // Sending EMail with Password Reset View
        Mail::send('reset', ['token' => $token], function (Message $message) use ($email) {
            $message->subject('Reset Your Password');
            $message->to($email);
        });
        return response([
            'message' => 'Password Reset Email Sent... Check Your Email',
            'status' => 'success'
        ], 200);
    }

    public function reset(Request $request, $token)
    {
        // Delete Token older than 2 minute
        // $formatted = Carbon::now()->subMinutes(2)->toDateTimeString();
        // PasswordReset::where('created_at', '<=', $formatted)->delete();

        $request->validate([
            'password' => 'required|confirmed',
        ]);

        $passwordreset = PasswordReset::where('token', $token)->first();

        if (!$passwordreset) {
            return response([
                'message' => 'Token is Invalid or Expired',
                'status' => 'failed'
            ], 404);
        }

        $user = User::where('email', $passwordreset->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // Delete the token after resetting password
        PasswordReset::where('email', $user->email)->delete();

        return response([
            'message' => 'Password Reset Success',
            'status' => 'success'
        ], 200);
    }
}
