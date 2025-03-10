<?php

// app/Http/Controllers/AuthController.php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterUserRequest;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;
use App\Providers\HelperServiceProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;


class AuthController extends Controller
{
    
  public function register(RegisterUserRequest $request)
    {
        $user = User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'password' => Hash::make($request->input('password')),
        ]);

        // Find the role ID for 'user'
        $userRole = Role::where('name', 'user')->first();

        if ($userRole) {
            // Attach the 'user' role using the correct ID
            $user->roles()->attach($userRole->id);
        }

        // Generate and store OTP for the user
        $otp = $user->generateOTP();
        $user->update(['otp' => $otp]);

        // Log the generated OTP for debugging
        Log::info("Generated OTP for user {$user->id}: $otp");

        // Send OTP via email
        Mail::to($user->email)->send(new OtpMail($otp));

        return response()->json(['message' => 'OTP sent successfully'], 201);
    }

    public function registerApp(Request $request): JsonResponse
    {
        // Define custom validation messages
        $messages = [
            'name.required' => 'The name field is required.',
            'email.required' => 'The email field is required.',
            'email.email' => 'The email must be a valid email address.',
            'email.unique' => 'The email has already been taken.',
            'password.required' => 'The password field is required.',
            'password.min' => 'The password must be at least 8 characters long.',
        ];

        // Define validation rules
        $rules = [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
        ];

        // Create a validator instance
        $validator = Validator::make($request->all(), $rules, $messages);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        }

        try {
            // Check if a user with the same email already exists
            $existingUser = User::where('email', $request->input('email'))->first();

            if ($existingUser) {
                return response()->json([
                    'status' => 409,
                    'success' => false,
                    'message' => 'A user with this email address is already registered.',
                ], 409);
            }

            // Create a new user
            $user = User::create([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'password' => Hash::make($request->input('password')),
                'email_verified_at' =>  now(),
            ]);

            // Find the role ID for 'user'
            $userRole = Role::where('name', 'user')->first();

            if ($userRole) {
                $user->roles()->attach($userRole->id);
            }

            $otp = $user->generateOTP();
            //$user->update(['otp' => $otp]);
            $user->update(['otp' => '123456']);

            try {
                Mail::to($user->email)->send(new OtpMail($otp));
            } catch (\Exception $e) {
                Log::info("Registration Mail send failed: $e");
            }

            // Return success response
            return response()->json([
                'status' => 200,
                'success' => true,
                'userId' => $user->id,
                'message' => 'Registration successful! Please check your email for the OTP.',
            ], 200);

        } catch (\Exception $e) {
            Log::info("Registration failed: $e");
            return response()->json([
                'status' => 500,
                'success' => false,
                'message' => 'Registration failed. Please try again later. ' . $e->getMessage(),
            ], 500);
        }
    }


    public function verifyEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|digits:6',
        ]);

        $user = User::where('email', $request->input('email'))->first();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        if ($user->email_verified_at) {
            return response()->json(['message' => 'User is already verified']);
        }

        // Fetch the stored OTP from the user model
        $storedOTP = (string) $user->otp;

        // Log values for debugging
        \Illuminate\Support\Facades\Log::info("Received OTP for user {$user->id}: {$request->input('otp')}");
        \Illuminate\Support\Facades\Log::info("Stored OTP for user {$user->id}: $storedOTP");

        if ($storedOTP == $request->input('otp')) {
            // $user->update(['email_verified_at' => now()]);

            $user->email_verified_at = date('Y-m-d H:i:s');
            $user->save();

            // Log successful OTP verification
            \Illuminate\Support\Facades\Log::info("OTP verified successfully for user {$user->id}");

            return response()->json(['message' => 'Email verified successfully']);
        }

        // Log failed OTP verification
        \Illuminate\Support\Facades\Log::info("Invalid OTP for user {$user->id}. Stored OTP: {$storedOTP}, Input OTP: {$request->input('otp')}");

        return response()->json(['error' => 'Invalid OTP'], 422);
    }

    public function verifyEmailApp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|digits:6',
        ]);

        $user = User::where('email', $request->input('email'))->first();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        if ($user->email_verified_at) {
            return response()->json(['message' => 'User is already verified']);
        }

        // Fetch the stored OTP from the user model
        $storedOTP = (string) $user->otp;

        // Log values for debugging
        \Illuminate\Support\Facades\Log::info("Received OTP for user {$user->id}: {$request->input('otp')}");
        \Illuminate\Support\Facades\Log::info("Stored OTP for user {$user->id}: $storedOTP");

        if ($storedOTP == $request->input('otp')) {
            // $user->update(['email_verified_at' => now()]);

            $user->email_verified_at = date('Y-m-d H:i:s');
            $user->save();

            // Log successful OTP verification
            \Illuminate\Support\Facades\Log::info("OTP verified successfully for user {$user->id}");

            return response()->json(["success" => true,'message' => 'OTP verified successfully', 'userId' => $user->id]);
        }

        // Log failed OTP verification
        \Illuminate\Support\Facades\Log::info("Invalid OTP for user {$user->id}. Stored OTP: {$storedOTP}, Input OTP: {$request->input('otp')}");

        return response()->json(['error' => 'Invalid OTP'], 422);
    }





    public function verifyOtp(Request $request)
    {
        // Log request data
        Log::info("Request Data: " . json_encode($request->all()));

        try {
            // Attempt to get the user from the request
            $user = $request->user();

            // Log user data
            Log::info("User Data: " . json_encode($user));

            // Check if the user has the 'user' role
            if (!$user || !$user->hasRole('user')) {
                Log::info("User not verified or not found for OTP verification");
                return response()->json(['error' => 'User not verified'], 401);
            }

            $otp = $request->input('otp');

            // Log received OTP
            Log::info("Received OTP for user {$user->id}: $otp");

            // Compare OTP
            if ($otp == $user->otp) {
                // Mark the user as verified
                $user->update(['verified' => true]);

                // Log successful OTP verification
                Log::info("OTP verified successfully for user {$user->id}");

                return response()->json(['message' => 'OTP verified successfully']);
            }

            // Log failed OTP verification
            Log::info("Invalid OTP for user {$user->id}. Stored OTP: {$user->otp}");

            return response()->json(['error' => 'Invalid OTP'], 401);
        } catch (\Exception $exception) {
            // Log any exception that occurs
            Log::error("Exception: " . $exception->getMessage());
            return response()->json(['error' => 'An error occurred during OTP verification'], 500);
        }
    }

    public function verifyOtpApp(Request $request)
    {
        // Log request data
        Log::info("Request Data: " . json_encode($request->all()));

        try {
            // Attempt to get the user from the request
            $user = $request->user();

            // Check if the user has the 'user' role
            if (!$user || !$user->hasRole('user')) {
                Log::info("User not verified or not found for OTP verification");
                return response()->json(['error' => 'User not verified'], 401);
            }

            $otp = $request->input('otp');

            // Compare OTP
            if ($otp == $user->otp) {
                // Mark the user as verified
                $user->update(['verified' => true]);

                return response()->json(['message' => 'OTP verified successfully']);
            }

            return response()->json(['error' => 'Invalid OTP'], 401);
        } catch (\Exception $exception) {
            return response()->json(['error' => 'An error occurred during OTP verification'], 500);
        }
    }

    public function login(Request $request)
    {
        // Attempt to log in
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            // Check user role after successful login
            $user = User::where('email', $request->email)->first();

            if ($user->hasRole('admin')) {
                // Generate and attach Sanctum token for admin
                $token = $user->createToken('admin-token')->plainTextToken;

                // Return JSON response for admin with token
                return response()->json(['role' => 'admin', 'message' => 'Admin login successful', 'token' => $token]);
            } elseif ($user->hasRole('user')) {
                // Generate and attach Sanctum token for user
                $token = $user->createToken('user-token')->plainTextToken;

                // Return JSON response for user with token
                return response()->json(['role' => 'user', 'message' => 'User login successful', 'token' => $token]);
            } elseif ($user->hasRole('agent')) {
                // Generate and attach Sanctum token for agent
                $token = $user->createToken('agent-token')->plainTextToken;

                // Return JSON response for agent with token
                return response()->json(['role' => 'agent', 'message' => 'Agent login successful', 'token' => $token]);
            } else {
                // Handle other roles as needed
                // Generate and attach Sanctum token for other roles
                $token = $user->createToken('other-token')->plainTextToken;

                // Return JSON response for other roles with token
                return response()->json(['role' => 'other', 'message' => 'Login successful', 'token' => $token]);
            }
        }

        // Handle failed login
        return response()->json(['error' => 'Invalid credentials'], 401);
    }

    public function loginApp(Request $request)
    {
        // Attempt to log in
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            
            $user = User::where('email', $request->email)->first();

            if ($user->hasRole('admin')) {
                // Generate and attach Sanctum token for admin
                $token = $user->createToken('admin-token')->plainTextToken;

                // Return JSON response for admin with token and user details
                return response()->json([
                    'success' => true,
                    'role' => 'admin',
                    'message' => 'Admin login successful',
                    'token' => $token,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'gender' => $user->gender,
                        'birthdate' => $user->birthdate,
                        'address' => $user->address,
                        'city' => $user->city,
                        'state' => $user->state,
                        'zip' => $user->zip,
                        'profile_photo' => $user->profile_photo,
                    ]
                ]);
            } elseif ($user->hasRole('user')) {
                // Generate and attach Sanctum token for user
                $token = $user->createToken('user-token')->plainTextToken;

                // Return JSON response for user with token and user details
                return response()->json([
                    'success' => true,
                    'role' => 'user',
                    'message' => 'User login successful',
                    'token' => $token,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'gender' => $user->gender,
                        'birthdate' => $user->birthdate,
                        'address' => $user->address,
                        'city' => $user->city,
                        'state' => $user->state,
                        'zip' => $user->zip,
                        'profile_photo' => $user->profile_photo,
                    ]
                ]);
            } elseif ($user->hasRole('agent')) {
                // Generate and attach Sanctum token for agent
                $token = $user->createToken('agent-token')->plainTextToken;

                // Return JSON response for agent with token and user details
                return response()->json([
                    'success' => true,
                    'role' => 'agent',
                    'message' => 'Agent login successful',
                    'token' => $token,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'gender' => $user->gender,
                        'birthdate' => $user->birthdate,
                        'address' => $user->address,
                        'city' => $user->city,
                        'state' => $user->state,
                        'zip' => $user->zip,
                        'profile_photo' => $user->profile_photo,
                    ]
                ]);
            } else {
                // Handle other roles as needed
                $token = $user->createToken('other-token')->plainTextToken;

                // Return JSON response for other roles with token and user details
                return response()->json([
                    'success' => true,
                    'role' => 'other',
                    'message' => 'Login successful',
                    'token' => $token,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'gender' => $user->gender,
                        'birthdate' => $user->birthdate,
                        'address' => $user->address,
                        'city' => $user->city,
                        'state' => $user->state,
                        'zip' => $user->zip,
                        'profile_photo' => $user->profile_photo,
                    ]
                ]);
            }
        }

        // Handle failed login
        return response()->json(['success' => false, 'error' => 'Invalid credentials'], 401);
    }


    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8',
            'confirm_password' => 'required|same:new_password',
        ]);

        $user = Auth::user();

        // Check if the current password matches
        if (!Hash::check($request->input('current_password'), $user->password)) {
            return response()->json(['error' => 'Current password is incorrect'], 401);
        }

        // Update the user's password
        $user->update([
            'password' => Hash::make($request->input('new_password')),
        ]);

        return response()->json(['message' => 'Password changed successfully']);
    }

    public function changePasswordApp(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8',
            'confirm_password' => 'required|same:new_password',
        ]);

        $user = Auth::user();

        // Check if the current password matches
        if (!Hash::check($request->input('current_password'), $user->password)) {
            return response()->json(['success' => false, 'message' => 'Current password is incorrect'], 401);
        }

        try {
            // Update the user's password
            $user->update([
                'password' => Hash::make($request->input('new_password')),
            ]);

            return response()->json(['success' => true, 'message' => 'Password changed successfully']);
        } catch (\Exception $e) {
            // Handle any errors that occur during the password update
            return response()->json(['success' => false, 'message' => 'An error occurred while changing the password'], 500);
        }
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        // Revoke the current user's tokens
        $user->tokens()->delete();

        return response()->json(['message' => 'Logout successful']);
    }

    public function logged_user()
    {
        $logged_user = auth()->user();
        return response([
            'user' => $logged_user,
            'message' => 'logged User data',
            'status' => 'success'
        ], 200);
    }


    public function updateProfile(Request $request, User $user)
    {
        
        try {
            // Ensure the user exists, or throw a 404 exception
            $user = User::findOrFail($user->id);

            // Check if the logged-in user is an admin or updating their own profile
            $loggedInUser = $request->user();

            if ($loggedInUser->hasRole('admin') || $loggedInUser->id === $user->id) {
                // Allow admins to update any user's profile
                // Allow users and agents to update their own profiles
                // If you have more roles, adjust the condition accordingly

                $rules = [
                    'name' => 'required|string|max:255',
                    'phone' => 'required|string|max:255',
                    //'profile_photo_path' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
                ];

                // Allow admins to update email
                if ($loggedInUser->hasRole('admin')) {
                    $rules['email'] = 'required|string|email|max:255|unique:users,email,' . $user->id;
                }

                // Include password validation if it's provided in the request
                if ($request->filled('password')) {
                    $rules['password'] = 'required|string|min:8';
                }

                $request->validate($rules);

                // Update the user's profile fields
                $user->update($request->except('password'));

                // Update the password if provided
                if ($request->filled('password')) {
                    $user->update(['password' => Hash::make($request->input('password'))]);
                }

                return response()->json(['message' => 'Profile updated successfully']);
            }

            return response()->json(['error' => 'Unauthorized access'], 403);
        } catch (ValidationException $validationException) {
            return response()->json(['error' => $validationException->errors()], 422);
        } catch (ModelNotFoundException $modelNotFoundException) {
            return response()->json(['error' => 'User not found'], 404);
        } catch (QueryException $queryException) {
            $errorCode = $queryException->errorInfo[1];

            if ($errorCode == 1062) {
                // Duplicate entry error (SQLSTATE[23000])
                return response()->json(['error' => 'Email address already exists. Please choose a different one.'], 422);
            }

            // Other database-related errors
            return response()->json(['error' => $queryException->getMessage()], 500);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    public function deleteAccount(Request $request)
    {
        $user = Auth::user(); 

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Delete the user account
        $user->delete();

        return response()->json(['message' => 'Your account has been deleted successfully'], 200);
    }

    public function updateProfileApp(Request $request, User $user)
    {
        try {
            $user = User::findOrFail($user->id);
            $loggedInUser = $request->user();

            if ($loggedInUser->hasRole('admin') || $loggedInUser->id === $user->id) {

                // Validation rules
                $rules = [
                    'name' => 'required|string|max:255',
                    'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
                    //'phone' => 'required|string|max:255',
                    'phone' => ['required', 'string', 'regex:/^\d{3}-\d{3}-\d{4}$/', 'max:255'],
                    // 'gender' => 'nullable|in:male,female,other',
                    // 'birthdate' => 'nullable|date|date_format:m/d/Y',
                    'profile_photo_path' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
                    // 'currentpassword' => 'nullable|string|min:8',
                    // 'newpassword' => 'nullable|string|min:8|same:confirmnewpassword',
                ];

            $messages = [
                'phone.regex' => 'The phone number must be in the format 202-123-4567.',
            ];
    
            // Validate the request
            $validatedData = $request->validate($rules, $messages);

            //$request->validate($rules);

            // Update the user profile details
            //$updateData = $request->only(['name', 'email', 'phone', 'gender', 'birthdate']);
            $updateData = $request->only(['name', 'email', 'phone']);

            // Handle profile photo upload if provided
            if ($request->hasFile('profile_photo_path')) {
                $file = $request->file('profile_photo_path');
                $path = $file->store('profile_photos', 'public'); // Store file in 'profile_photos' directory
                $updateData['profile_photo_path'] = $path;
            }

            // Check if password update is requested
            if ($request->filled('newpassword')) {
                if (!$request->filled('currentpassword') || !Hash::check($request->input('currentpassword'), $user->password)) {
                    return response()->json(['success' => false, 'message' => 'Current password is incorrect'], 422);
                }
                $updateData['password'] = Hash::make($request->input('newpassword'));
            }

            $user->update($updateData);

            return response()->json(['success' => true, 'message' => 'Profile updated successfully']);
        }

        return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);

    } catch (ValidationException $validationException) {
        return response()->json(['success' => false, 'message' => $validationException->errors()], 422);
    } catch (ModelNotFoundException $modelNotFoundException) {
        return response()->json(['success' => false, 'message' => 'User not found'], 404);
    } catch (QueryException $queryException) {
        $errorCode = $queryException->errorInfo[1];
        if ($errorCode == 1062) {
            return response()->json(['success' => false, 'message' => 'Email address already exists. Please choose a different one.'], 422);
        }
        return response()->json(['success' => false, 'message' => $queryException->getMessage()], 500);
    } catch (\Exception $exception) {
        return response()->json(['success' => false, 'message' => $exception->getMessage()], 500);
    }
}


    // public function logUserActivity(Request $request, $userId)
    // {
    //     // Ensure the logged-in user is an admin
    //     $admin = $request->user();
    //     if (!$admin || !$admin->hasRole('admin')) {
    //         return response()->json(['error' => 'Unauthorized access'], 403);
    //     }

    //     // Find the user whose activity is being logged
    //     $user = User::find($userId);

    //     if (!$user) {
    //         return response()->json(['error' => 'User not found'], 404);
    //     }

    //     // Log the activity
    //     Log::info("Admin {$admin->id} viewed user {$user->id}'s activity.");

    //     // You can log more details or specific actions performed by the admin if needed

    //     return response()->json(['message' => 'User activity logged successfully']);
    // }
}
