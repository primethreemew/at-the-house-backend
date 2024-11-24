<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AgentServiceController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ServiceController;
use App\Models\User;
/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/generate-token', function () {
    $user = User::find(105); // Replace with the user ID
    $token = $user->createToken('argon')->plainTextToken;

    return response()->json(['token' => $token]);
});
// User registration
Route::post('/register', [AuthController::class, 'register']);

// User login
Route::post('/login', [AuthController::class, 'login'])->name('web.login');

// Mobile App
Route::prefix('mobile')->group(function () {
    //change forgot password
    Route::post('/password-reset/send-otp', [PasswordResetController::class, 'sendOtp']);
    Route::post('/password-reset/verify-otp', [PasswordResetController::class, 'verifyOtp']);
    Route::post('/password-reset/change-password', [PasswordResetController::class, 'changePassword']);

    Route::post('/register', [AuthController::class, 'registerApp']);
    Route::post('/logins', [AuthController::class, 'loginApp'])->name('mobile.login');
    Route::post('/verify-otp', [AuthController::class, 'verifyOtpApp']);
    Route::post('/verify-email', [AuthController::class, 'verifyEmailApp']);
    //Route::get('/admin/services', [AdminController::class, 'getAllServicesApp']);
    Route::get('/admin/services/{id}', [ServiceController::class, 'getAgentServiceApp']);
    Route::get('/admin/services/{serviceId}', [AdminController::class, 'viewServiceApp']);
    Route::get('/admin/recommended', [AdminController::class, 'getAllRecommended']);
    Route::get('/admin/relevantsearch/{categoryname}', [AdminController::class, 'getAllRelevantSearch']);
    //Route::get('/agent-services', [ServiceController::class, 'getAllAgentServices']);
    //Route::middleware('auth:sanctum')->get('/agent-services', [ServiceController::class, 'getAgentServicesApp']);
    Route::middleware('auth:sanctum')->get('/admin/services', [ServiceController::class, 'getAgentServicesApp']);

    //Route::put('/referral/{serviceId}', [AdminController::class, 'submitReferral']);
    Route::middleware('role:admin|agent')->group(function () {
        // Admin and Agent route to create agent service
        Route::post('/agent-services/create', [ServiceController::class, 'agentServiceCreate']);

        // Retrieve all agent's services
        //Route::get('/agent-services', [ServiceController::class, 'getAllAgentServices']);

        // Retrieve a specific agent's service by ID
       // Route::get('/agent-services/{id}', [ServiceController::class, 'getAgentService']);

        // Update a specific agent's service by ID
        Route::put('/agent-services/{id}/update', [ServiceController::class, 'updateAgentService']);

        // Delete a specific agent's service by ID
        Route::delete('/agent-services/{id}/delete', [ServiceController::class, 'deleteAgentService']);
    });
});


// Routes requiring authentication and email verification
Route::middleware('auth:sanctum', 'verified')->prefix('mobile')->group(function () {
    
    Route::post('/change-password', [AuthController::class, 'changePasswordApp']);
    Route::put('/profile/{user}', [AuthController::class, 'updateProfileApp']);
    Route::middleware('role:admin')->group(function () {
        // Register an agent
        Route::post('/admin/register-agent', [AdminController::class, 'registerAgentApp']);
    });
    //Route::get('/agent-services/{id}', [ServiceController::class, 'getAgentServiceApp']);
    // Get authenticated user details
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::put('/referral/{serviceId}', [AdminController::class, 'submitReferral']);
    Route::get('/referrals', [AdminController::class, 'allReferralsApp']);
    Route::get('/single-referral/{serviceId}', [AdminController::class, 'singleReferralsApp']);
});

// Send reset password email
Route::post('/send-reset-password-email', [PasswordResetController::class, 'send_reset_password_email']);

// Reset password
Route::post('/reset-password/{token}', [PasswordResetController::class, 'reset']);

// Verify email (you might want to use the email verification feature provided by Laravel)
Route::post('/email/verify', [AdminController::class, 'verifyEmail']);

// Verify OTP
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);

Route::post('/verify-email', [AuthController::class, 'verifyEmail']);


// Admin-only route to get all services
Route::get('/admin/servicess', [AdminController::class, 'getAllServices']);

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum','verified')->group(function () {
    
    // Get authenticated user details
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Routes for regular users
    Route::middleware('role:user')->group(function () {
        // Add user-specific routes if needed
    });

    // Routes for admin
    Route::middleware('role:admin')->group(function () {
        Route::get('/referrals', [AdminController::class, 'allReferrals']);
        Route::get('/admin/pending-referrals', [AdminController::class, 'pendingReferrals']);
        Route::put('/referrals/{referralId}', [AdminController::class, 'statusChange']);
        // Register an agent
        Route::post('/admin/register-agent', [AdminController::class, 'registerAgent']);

        // View all registered users
        Route::get('/admin/users', [AdminController::class, 'viewAllUsers']);

        // View all registered agents
        Route::get('/admin/agents', [AdminController::class, 'viewAgents']);

        // Edit Agents
        Route::get('/admin/agents/{agentId}', [AdminController::class, 'updateAgents']);

        // // Admin-only route to get all services
        Route::get('/admin/services', [AdminController::class, 'getAllServices']);

        // Admin-only route to create a service
        Route::post('/admin/services/create', [AdminController::class, 'createService']);

        // Admin-only route to update a service
        Route::put('/admin/services/{serviceId}/update', [AdminController::class, 'updateService']);

        // Admin-only route to delete a service
        Route::delete('/admin/services/{serviceId}/delete', [AdminController::class, 'deleteService']);

        // Admin-only route to view a specific service
        Route::get('/admin/services/{serviceId}', [AdminController::class, 'viewService']);

        // Admin-only route for to delete users and agents
        Route::delete('/admin/delete-user/{user}', [AdminController::class, 'delete']);
        // Admin-only route to log user activity
        // Route::post('/admin/log-user-activity/{userId}', [AuthController::class, 'logUserActivity']);
    });

    // Routes for both admin and agent
    Route::middleware('role:admin|agent')->group(function () {

        Route::get('/admin/agent-services', [ServiceController::class, 'getAllAgentsServices']);
        // Admin and Agent route to create agent service
        Route::post('/agent-services/create', [ServiceController::class, 'agentServiceCreate']);

        // Retrieve all agent's services
        Route::get('/agent-services', [ServiceController::class, 'getAllAgentServices']);

        // Retrieve a specific agent's service by ID
        Route::get('/agent-services/{id}', [ServiceController::class, 'getAgentService']);

        // Update a specific agent's service by ID
        Route::put('/agent-services/{id}/update', [ServiceController::class, 'updateAgentService']);

        // Delete a specific agent's service by ID
        Route::delete('/agent-services/{id}/delete', [ServiceController::class, 'deleteAgentService']);
    });

    // Change user password 
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    // Route for updating the profile
    Route::put('/profile/{user}', [AuthController::class, 'updateProfile']);
});

// User logout
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Get logged-in user details
Route::get('/logged-user', [AuthController::class, 'logged_user'])->middleware('auth:sanctum');


// Verify OTP
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
