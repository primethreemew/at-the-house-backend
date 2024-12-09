<?php

namespace App\Http\Controllers;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Log;


// use App\Http\Requests\CreateAgentServiceRequest;
use App\Http\Requests\RegisterUserRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
// use Illuminate\Support\Facades\Gate;
use App\Models\AgentService;
use Illuminate\Support\Facades\DB;

use App\Models\User;
use App\Models\Role;
use App\Models\Service;
use App\Models\Referral;

class AdminController extends Controller
{
    public function viewAllUsers()
    {
        // Check if the authenticated user is an admin
        if (auth()->user()->roles->contains('name', 'admin')) {
            // Get all users
            $users = User::all();

            // Return a JSON response
            return response()->json(['users' => $users]);
        }

        // If not an admin, return an error response
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    public function viewAgents()
    {
        // Check if the authenticated user is an admin
        if (auth()->user()->roles->contains('name', 'admin')) {
            // Retrieve only users with the 'agent' role
            $agents = User::whereHas('roles', function ($query) {
                $query->where('name', 'agent');
            })->get();

            return response()->json(['agents' => $agents]);
        }

        // If not an admin, return an error response
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    public function updateAgents($agentId){
        if (Auth::user()->roles->contains('name', 'admin')) {

            // Find the service by ID
            $agent = User::find($agentId);
            if (!$agent) {
                return response()->json(['error' => 'Agent not found'], 404);
            }

            // Check if a base64 image string or a file upload is provided
            return response()->json(['agent' => $agent]);
        }

        // If not an admin, return an error response
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    public function registerAgent(RegisterUserRequest $request)
    {
        try {
            // Check if the authenticated user is an admin
            if (Auth::user()->roles->contains('name', 'admin')) {
                // Check if the email already exists
                if (User::where('email', $request->email)->exists()) {
                    return response()->json(['error' => 'Email already exists.'], 422);
                }

                // Create a new user with the agent role
                $agent = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'password' => bcrypt($request->password),
                ]);

                // Assign the agent role to the new user
                $agent->roles()->attach(Role::where('name', 'agent')->first());

                // Send email verification notification (optional)
                // if (!$agent->hasVerifiedEmail()) {
                //     $agent->sendEmailVerificationNotification();
                // }

                return response()->json(['message' => 'Verification Request Sent!']);
            }

            // If not an admin, return an error response
            return response()->json(['error' => 'Unauthorized'], 403);
        } catch (\Exception $e) {
            // Return an error response
            return response()->json([
                'error' => 'Failed to register agent. Please try again later.',
            ], 500);
        }
    }   

    public function registerAgentApp(RegisterUserRequest $request)
    {
        try {
            // Check if the authenticated user is an admin
            if (Auth::user()->roles->contains('name', 'admin')) {
                // Create a new user with the agent role
                $agent = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'password' => bcrypt($request->password),
                ]);

                // Assign the agent role to the new user
                $role = Role::where('name', 'agent')->first();
                if ($role) {
                    $agent->roles()->attach($role);
                } else {
                    return response()->json(['success' => false, 'message' => 'Agent role not found'], 404);
                }

                // Send email verification notification
                if (!$agent->hasVerifiedEmail()) {
                    $agent->sendEmailVerificationNotification();
                }

                return response()->json(['success' => true, 'message' => 'Verification Request Sent!']);
            } else {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
        } catch (\Exception $e) {
            // Handle any errors that occur during the process
            return response()->json(['success' => false, 'message' => 'An error occurred while registering the agent', 'error' => $e->getMessage()], 500);
        }
    }


    //     // If not an admin, return an error response
    //     return response()->json(['error' => 'Unauthorized'], 403);
    // }


    public function verifyEmail(Request $request)
    {
        // Find the user by email
        $user = User::where('email', $request->email)->first();

        // Check if the user exists
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Check if the user has already verified the email
        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified']);
        }

        // Verify the user's email
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
            return response()->json(['message' => 'Email verified successfully']);
        }

        return response()->json(['error' => 'Email verification failed'], 500);
    }

    public function delete(User $user)
    {
        // Ensure the logged-in user is an admin
        $admin = auth()->user();
        if (!$admin || !$admin->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized access'], 403);
        }

        // Check if the user is not an admin (allow admins to delete all roles)
        if (!$user->hasRole('admin')) {
            // Determine the role(s) of the user before deletion
            $deletedUserRoles = $user->roles->pluck('name')->toArray();

            // Delete the user
            $user->delete();

            // Log the deletion with the user's role(s)
            \Illuminate\Support\Facades\Log::info("Admin {$admin->id} deleted user {$user->id} with role(s): " . implode(', ', $deletedUserRoles));

            return response()->json(['message' => 'User deleted successfully', 'deleted_user_roles' => $deletedUserRoles]);
        }

        // Admins cannot be deleted this way
        \Illuminate\Support\Facades\Log::info('Unauthorized: Admins cannot be deleted this way');
        return response()->json(['error' => 'You are not allowed to delete this user'], 403);
    }


    public function createService(Request $request)
    {
        // Check if the authenticated user is an admin
        if (Auth::user()->roles->contains('name', 'admin')) {
            // Validate the request data, including the image upload
            $request->validate(Service::$rules);

            // Handle image upload
            $imagePath = $request->hasFile('image') ? $request->file('image')->store('service_images', 'public') : null;

            // Create a new service with the image path
            $service = Service::create([
                'category_name' => $request->input('category_name'),
                'image' => $imagePath,
                'category_type' => $request->input('category_type', 'category'), 
                'recommended' => $request->input('recommended'),
            ]);

            return response()->json(['message' => 'Service created successfully', 'service' => $service]);
        }
  
        // If not an admin, return an error response
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    public function getAllServices()
    {

        $allowedCategoryTypes = ['true', 'isRecommended' , 'all'];
        $result = [];

        try {
            foreach ($allowedCategoryTypes as $categoryType) {
                if($categoryType == "all"){
                    $services = DB::select("SELECT * FROM services");    
                }elseif($categoryType == "true"){
                   // $services = DB::select("SELECT * FROM services");
                    $services = DB::select("SELECT * FROM services WHERE category_type = ?", [$categoryType]);
                }else{
                    $services = DB::select("SELECT * FROM services WHERE recommended = ?", [$categoryType]);
                }
                $result[$categoryType] = $services;
            }
            //$services = DB::select("SELECT * FROM services where category_type = '1'");
            
            //$services = DB::select("SELECT * FROM services");
            // Return all services grouped by category type
            return response()->json(['success' => true, 'services' => $result]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred while retrieving services', 'error' => $e->getMessage()], 500);
        }
    }

    public function getAllExploredCategory()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        try {
            $services = DB::select("SELECT * FROM services WHERE category_type = ?", ["true"]);

            // Iterate over each service to adjust the image URL
            foreach ($services as $service) {
                if ($service->image) {
                    $service->image = url('storage/' . $service->image);
                }
            }

            return response()->json(['success' => true, 'result' => $services]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred while retrieving Explored Categories', 'error' => $e->getMessage()], 500);
        }
    }

    public function getAllCategory()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        try {
            $services = DB::select("SELECT * FROM services");

            // Iterate over each service to adjust the image URL
            foreach ($services as $service) {
                if ($service->image) {
                    $service->image = url('storage/' . $service->image);
                }
            }

            return response()->json(['success' => true, 'result' => $services]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred while retrieving Explored Categories', 'error' => $e->getMessage()], 500);
        }
    }

    public function getServicesbyCategoryID($id)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'data' => $user, 'error' => 'Unauthorized'], 403);
        }

        try {
            $services = DB::table('agent_services')
                        ->join('services', 'agent_services.category_id', '=', 'services.id')
                        ->where('agent_services.category_id', $id)
                        ->select('agent_services.*', 'services.category_name')
                        ->get();

            if ($services->isEmpty()) {
                return response()->json(['success' => false, 'error' => 'Service not found'], 404);
            }

            foreach ($services as $service) {
                if ($service->featured_image) {
                    $service->featured_image = url('storage/' . $service->featured_image);
                }

                if ($service->hours) {
                    $hoursData = json_decode($service->hours, true);

                    $formattedHours = [];

                    foreach ($hoursData as $dayHours) {
                        $open = $dayHours['open'] ?? null;
                        $close = $dayHours['close'] ?? null;

                        if (empty($open) && empty($close)) {
                            // If both open and close are empty, set as Closed
                            $formattedHours[$dayHours['day']] = ['Closed'];
                        } else {
                            // Otherwise, format the open and close times
                            $formattedHours[$dayHours['day']] = [
                                'open' => $open ? date("h:i A", strtotime($open)) : '--',
                                'close' => $close ? date("h:i A", strtotime($close)) : '--',
                            ];
                        }
                    }

                    $service->formatted_hours = $formattedHours;
                    unset($service->hours);
                }
            }

            return response()->json(['success' => true, 'result' => $services]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function getAllServicesApp()
    {
        $allowedCategoryTypes = ['popular', 'most_demanding'];
        $result = [];

        try {
            foreach ($allowedCategoryTypes as $categoryType) {
                $services = DB::select("SELECT * FROM services WHERE category_type = ?", [$categoryType]);

                // Process each service to prepend the base URL to the image fields
                foreach ($services as $service) {
                    // Assuming 'image' is the field name for the image in your services table
                    if (isset($service->image)) {
                        $service->image = url('storage/' . $service->image);
                    }
                    // If you have a 'featured_image' field, you can also set it
                    if (isset($service->featured_image)) {
                        $service->featured_image = url('storage/' . $service->featured_image);
                    }
                }

                $result[$categoryType] = $services;
            }

            // Return all services grouped by category type
            return response()->json(['success' => true, 'services' => $result]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred while retrieving services', 'error' => $e->getMessage()], 500);
        }
    }



    public function getAllRecommended()
    {
        // $allowedCategoryTypes = ['popular', 'most_demanding'];
        // $result = [];

        try {
            $isRecommended = 'isRecommended'; // or any dynamic value
            $recommended = DB::select("SELECT category_name FROM services WHERE recommended = ?", [$isRecommended]);    
            // Return all services grouped by category type
            return response()->json(['success' => true, 'recommended' => $recommended]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred while retrieving recommended categories', 'error' => $e->getMessage()], 500);
        }
    }

    public function getAllRelevantSearch($categoryname){
        $relevantCatId = DB::table('services')
                        ->where('category_name', $categoryname)
                        ->value('id');

        $relevantServices = DB::table('agent_services')
                        ->where('category_id', $relevantCatId)->get();
                        
        return response()->json(['success' => true, 'services' => $relevantServices]);
    }

    public function updateService(Request $request, $serviceId) 
    {
        
        // Check if the authenticated user is an admin
        if (Auth::user()->roles->contains('name', 'admin')) {
            // Validate the request data
            $request->validate([
                'category_name' => 'required|unique:services,category_name,' . $serviceId,
                'category_type' => 'required|string',
                'recommended' => 'required|in:notRecommended,isRecommended', // Ensure valid value for 'recommended'
                'category_image' => 'nullable|string', 
            ]);

            // Find the service by ID
            $service = Service::find($serviceId);
            if (!$service) {
                return response()->json(['error' => 'Service not found'], 404);
            }

            // Prepare data to update
            $dataToUpdate = [
                'category_name' => $request->input('category_name'),
                'category_type' => $request->input('category_type'),
                'recommended' => $request->input('recommended'),
            ];
            
            // Check if a base64 image string or a file upload is provided
            if ($request->has('category_image')) {
                $base64Image = $request->input('category_image');
                
                // Remove "data:image/png;base64," part if it exists
                $base64Image = preg_replace('/^data:image\/\w+;base64,/', '', $base64Image);
                $imageData = base64_decode($base64Image);

                // Define the file name and path
                $imageName = uniqid() . '.png';  // you can use png or derive from actual image mime type
                $imagePath = 'images/' . $imageName;
                
                // Store image in the storage/app/public/images directory
                \Storage::disk('public')->put($imagePath, $imageData);

                // Update image path in data array
                $dataToUpdate['category_image'] = $imagePath;
            }

            
            // Update the service with new data
            $service->update($dataToUpdate);

            return response()->json(['message' => 'Service updated successfully', 'service' => $service]);
        }

        // If not an admin, return an error response
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    public function deleteService($serviceId)
    {
        // Check if the authenticated user is an admin
        if (Auth::user()->roles->contains('name', 'admin')) {
            // Find the service by ID
            $service = Service::find($serviceId);

            if (!$service) {
                return response()->json(['error' => 'Service not found'], 404);
            }

            // Delete the service
            $service->delete();

            return response()->json(['message' => 'Service deleted successfully']);
        }

        // If not an admin, return an error response
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    public function viewService($serviceId)
    {
        // Check if the authenticated user is an admin
        if (Auth::user()->roles->contains('name', 'admin')) {
            // Find the service by ID
            $service = Service::find($serviceId);

            if (!$service) {
                return response()->json(['error' => 'Service not found'], 404);
            }

            if ($service->image && !str_starts_with($service->image, 'http')) {
                $service->image = url('storage/' . $service->image);
            }
    

            return response()->json(['service' => $service]);
        }

        // If not an admin, return an error response
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    public function viewServiceApp($serviceId)
    {
        // Check if the authenticated user is an admin
        if (Auth::user()->roles->contains('name', 'admin')) {
            // Find the service by ID
            $service = Service::find($serviceId);

            if (!$service) {
                return response()->json(['error' => 'Service not found'], 404);
            }

            return response()->json(['service' => $service]);
        }

        // If not an admin, return an error response
        return response()->json(['error' => 'Unauthorized'], 403);
    }
    
    public function submitReferral($serviceId)
    {
        // Get the authenticated user
        $user = Auth::user();
        
        // Check if the user is authenticated
        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        // Find the service by ID
        $service = AgentService::find($serviceId);

        // Check if the service exists
        if (!$service) {
            return response()->json(['error' => 'Service not found'], 404);
        } 

        try {
            // Insert the referral into the database
            $referral = DB::table('referrals')->insert([
                'referrer_id' => $user->id, 
                'agent_service_id' => $serviceId, 
                'status' => 'pending', 
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Return success response
            return response()->json([
                'message' => 'Your Referral has been submitted. Our tem At the House App will act on your referral on priority',
                'referral' => $referral,
                'success' => true
            ], 201);

        } catch (\Exception $e) {
            // Return error response if insertion fails
            return response()->json([
                'success' => false,
                'error' => 'Failed to submit referral',
                'message' => $e->getMessage()
            ], 500);
        }
    }
   
    public function allReferrals()
    {
        //$referrals = Referral::all();
        $referrals = DB::table('referrals')
            ->join('agent_services', 'referrals.agent_service_id', '=', 'agent_services.id')
            ->join('users', 'referrals.referrer_id', '=', 'users.id')
            ->select(
                'referrals.*',
                'users.name as name',
                'users.email as email',
                'agent_services.service_name as service_name',
                'agent_services.phone_number as phone_number',
            )->get();
        
        return response()->json(['success' => true, 'services' => $referrals]);
    }

    public function pendingReferrals()
    {
        //$referrals = Referral::all();
        $referrals = DB::table('referrals')
            ->where('status', "pending")
            ->get();
        
        return response()->json(['success' => true, 'pendindreferrals' => $referrals]);
    }

    public function allReferralsApp()
    {
        try {
            // Get the authenticated user
            $user = Auth::user();

            // Check if the user is authenticated
            if (!$user) {
                return response()->json(['message' => 'User not authenticated', 'success' => false], 401);
            }

            // Query to get referrals with 'approved' status and matching referrer_id
            $referrals = DB::table('referrals')
                ->join('agent_services', 'referrals.agent_service_id', '=', 'agent_services.id')
                ->select(
                    'referrals.*', 
                    'agent_services.service_name as service_name', 
                    'agent_services.short_description as short_description', 
                    'agent_services.email as email', 
                    'agent_services.phone_number as phone_number',
                    'agent_services.featured_image as featured_image',
                )
               // ->where('referrals.status', 'approved')
                ->where('referrals.referrer_id', $user->id) // Check if referrer_id matches the user's id
                ->get();

                foreach ($referrals as $referral) {
                    if ($referral->featured_image && !str_starts_with($referral->featured_image, 'http')) {
                        $referral->featured_image = url('storage/' . $referral->featured_image);
                    }
                }

            // Check if no referrals are found
            if ($referrals->isEmpty()) {
                return response()->json(['message' => 'No referrals found', 'success' => false], 404);
            }

            return response()->json(['success' => true, 'services' => $referrals]);
            
        } catch (\Exception $e) {
            // Catch any unexpected errors and return a generic error response
            return response()->json(['message' => 'An error occurred: ' . $e->getMessage(), 'success' => false], 500);
        }
    }

    public function singleReferralsApp($serviceId)
    {
        // Get the authenticated user
        $user = Auth::user();

        // Check if the user is authenticated
        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        // Find the service by ID
        $service = AgentService::find($serviceId);

        // Check if the service exists
        if (!$service) {
            return response()->json(['error' => 'Service not found'], 404);
        } 

        try {
            // Fetch the referral data based on user ID and service ID
            $referral = DB::table('referrals')
                ->join('agent_services', 'referrals.agent_service_id', '=', 'agent_services.id')
                ->select(
                    'referrals.*',
                    'agent_services.service_name as service_name', 
                    'agent_services.short_description as short_description', 
                    'agent_services.email as email', 
                    'agent_services.phone_number as phone_number',
                    'agent_services.featured_image as featured_image'
                )
                ->where('referrals.referrer_id', $user->id)
                ->where('referrals.agent_service_id', $serviceId)
                ->first();

            if (!$referral) {
                return response()->json([
                    'success' => false,
                    'message' => 'No referral found for this service',
                ], 404);
            }

            // $referral->featured_image = $referral->featured_image
            // ? url('storage/' . $referral->featured_image)
            // : null;
            if ($referral->featured_image && !str_starts_with($referral->featured_image, 'http')) {
                $referral->featured_image = url('storage/' . $referral->featured_image);
            }
            
            // Check if a referral exists
            if (!$referral) {
                return response()->json([
                    'message' => 'No referral found for this service',
                    'success' => false
                ], 404);
            }

            // Return the fetched referral data
            return response()->json([
                'message' => 'Referral found',
                'referral' => $referral,
                'success' => true
            ], 200);

        } catch (\Exception $e) {
            // Return error response if fetching fails
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch referral',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function statusChange(Request $request, $referralId)
    {
        
        $referral = Referral::find($referralId);

        if (!$referral) {
            return response()->json(['error' => 'Referral not found'], 404);
        }
    
        $validatedData = $request->validate([
            'status' => 'required|string|in:pending,approved,denied',
        ]);
    
        $referral->status = $validatedData['status'];
    
        if ($referral->save()) {
            return response()->json(['message' => 'Referral status updated successfully.', 'referral' => $referral, 'success' => true], 200);
        }
    
        return response()->json(['error' => 'Failed to update referral status'], 500);
    }

} 