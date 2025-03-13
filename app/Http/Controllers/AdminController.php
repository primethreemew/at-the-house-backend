<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\Verified;

use App\Http\Requests\RegisterUserRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

use App\Models\AgentService;
use Illuminate\Support\Facades\DB;

use App\Models\User;
use App\Models\Role;
use App\Models\Service;
use App\Models\Referral;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public function viewAllUsers()
    {
        if (auth()->user()->roles->contains('name', 'admin')) {
            $users = User::all();
            return response()->json(['users' => $users]);
        }

        return response()->json(['error' => 'Unauthorized'], 403);
    }

    public function viewAgents()
    {
        if (auth()->user()->roles->contains('name', 'admin')) {
            $agents = User::whereHas('roles', function ($query) {
                $query->where('name', 'agent');
            })->get();

            return response()->json(['agents' => $agents]);
        }

        return response()->json(['error' => 'Unauthorized'], 403);
    }

    public function updateAgents($agentId)
    {
        if (Auth::user()->roles->contains('name', 'admin')) {

            $agent = User::find($agentId);
            if (!$agent) {
                return response()->json(['error' => 'Agent not found'], 404);
            }

            return response()->json(['agent' => $agent]);
        }

        return response()->json(['error' => 'Unauthorized'], 403);
    }

    public function registerAgent(RegisterUserRequest $request)
    {
        try {
            if (Auth::user()->roles->contains('name', 'admin')) {
                if (User::where('email', $request->email)->exists()) {
                    return response()->json(['error' => 'Email already exists.'], 422);
                }

                $agent = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'password' => bcrypt($request->password),
                ]);

                $agent->roles()->attach(Role::where('name', 'agent')->first());

                return response()->json(['message' => 'Verification Request Sent!']);
            }

            return response()->json(['error' => 'Unauthorized'], 403);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to register agent. Please try again later.',
            ], 500);
        }
    }

    public function registerAgentApp(RegisterUserRequest $request)
    {
        try {
            if (Auth::user()->roles->contains('name', 'admin')) {
                $agent = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'password' => bcrypt($request->password),
                ]);

                $role = Role::where('name', 'agent')->first();
                if ($role) {
                    $agent->roles()->attach($role);
                } else {
                    return response()->json(['success' => false, 'message' => 'Agent role not found'], 404);
                }

                if (!$agent->hasVerifiedEmail()) {
                    $agent->sendEmailVerificationNotification();
                }

                return response()->json(['success' => true, 'message' => 'Verification Request Sent!']);
            } else {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred while registering the agent', 'error' => $e->getMessage()], 500);
        }
    }

    public function verifyEmail(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified']);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
            return response()->json(['message' => 'Email verified successfully']);
        }

        return response()->json(['error' => 'Email verification failed'], 500);
    }

    public function delete(User $user)
    {
        $admin = auth()->user();
        if (!$admin || !$admin->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized access'], 403);
        }

        if (!$user->hasRole('admin')) {
            $deletedUserRoles = $user->roles->pluck('name')->toArray();
            $user->delete();
            return response()->json(['message' => 'User deleted successfully', 'deleted_user_roles' => $deletedUserRoles]);
        }

        return response()->json(['error' => 'You are not allowed to delete this user'], 403);
    }


    public function createService(Request $request)
    {
        if (Auth::user()->roles->contains('name', 'admin')) {
            $request->validate(Service::$rules);

            $imagePath = $request->hasFile('image') ? $request->file('image')->store('service_images', 'public') : null;

            $service = Service::create([
                'category_name' => $request->input('category_name'),
                'image' => $imagePath,
                'category_type' => $request->input('category_type', 'category'),
                'recommended' => $request->input('recommended'),
            ]);

            return response()->json(['message' => 'Service created successfully', 'service' => $service]);
        }

        return response()->json(['error' => 'Unauthorized'], 403);
    }

    public function getAllServices()
    {

        $allowedCategoryTypes = ['true', 'isRecommended', 'all'];
        $result = [];

        try {
            foreach ($allowedCategoryTypes as $categoryType) {
                if ($categoryType == "all") {
                    $services = DB::select("SELECT * FROM services");
                } elseif ($categoryType == "true") {
                    $services = DB::select("SELECT * FROM services WHERE category_type = ?", [$categoryType]);
                } else {
                    $services = DB::select("SELECT * FROM services WHERE recommended = ?", [$categoryType]);
                }
                $result[$categoryType] = $services;
            }

            return response()->json(['success' => true, 'services' => $result]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred while retrieving services', 'error' => $e->getMessage()], 500);
        }
    }

    public function getAllExploredCategory(Request $request)
    {
        if ($request->hasHeader('Authorization')) {
            $user = Auth::guard('sanctum')->user();

            if (!$user) {
                return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
            }
        }

        try {
            $services = DB::select("SELECT * FROM services WHERE category_type = ?", ["true"]);

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


    public function getAllCategory(Request $request)
    {
        // $user = Auth::user();

        // if (!$user) {
        //     return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        // }

        if ($request->hasHeader('Authorization')) {
            $user = Auth::guard('sanctum')->user();

            // If token is provided but user is not authenticated, return unauthorized response
            if (!$user) {
                return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
            }
        }

        try {
            $services = DB::select("SELECT * FROM services");

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

    public function getServicesbyCategoryID(Request $request)
{
    try {
        // Get query parameters
        $id = $request->query('id');
        $clientLatitude = $request->query('latitude');
        $clientLongitude = $request->query('longitude');

        // Validate required parameters
        if (!$id || !$clientLatitude || !$clientLongitude) {
            return response()->json(['success' => false, 'error' => 'id, latitude, and longitude are required'], 400);
        }

        // Authenticate if Authorization header is present
        if ($request->hasHeader('Authorization')) {
            $user = Auth::guard('sanctum')->user();
            if (!$user) {
                return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
            }
        }

        // Fetch services
        $services = DB::table('agent_services')
            ->join('services', 'agent_services.category_id', '=', 'services.id')
            ->where('agent_services.category_id', $id)
            ->select('agent_services.*', 'services.category_name', 'agent_services.latitude as latitude', 'agent_services.longitude as longitude')
            ->get();

        if ($services->isEmpty()) {
            return response()->json(['success' => false, 'error' => 'Service not found'], 404);
        }

        // Process services
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

                    $formattedHours[$dayHours['day']] = [
                        'open' => $open ? date("h:i A", strtotime($open)) : 'Closed',
                        'close' => $close ? date("h:i A", strtotime($close)) : 'Closed',
                    ];
                }

                $service->formatted_hours = $formattedHours;
                unset($service->hours);
            }

            // Calculate distance
            $serviceLatitude = $service->latitude;
            $serviceLongitude = $service->longitude;
            $distance = ServiceController::getDistance($clientLatitude, $clientLongitude, $serviceLatitude, $serviceLongitude);
            $service->distance = $distance;
        }

        return response()->json(['success' => true, 'services' => $services]);

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

                foreach ($services as $service) {
                    if (isset($service->image)) {
                        $service->image = url('storage/' . $service->image);
                    }
                    if (isset($service->featured_image)) {
                        $service->featured_image = url('storage/' . $service->featured_image);
                    }
                }

                $result[$categoryType] = $services;
            }

            return response()->json(['success' => true, 'services' => $result]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred while retrieving services', 'error' => $e->getMessage()], 500);
        }
    }

    public function getAllRecommended(Request $request)
    {
        if ($request->hasHeader('Authorization')) {
            $user = Auth::guard('sanctum')->user();

            // If token is provided but user is not authenticated, return unauthorized response
            if (!$user) {
                return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
            }
        }

        try {
            $isRecommended = 'isRecommended'; // or any dynamic value
            $recommended = DB::select("SELECT id,category_name FROM services WHERE recommended = ?", [$isRecommended]);
            return response()->json(['success' => true, 'recommended' => $recommended]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred while retrieving recommended categories', 'error' => $e->getMessage()], 500);
        }
    }

    public function  getAllRelevantSearch($categoryname)
    {
        $relevantCatId = DB::table('services')
            ->where('category_name', $categoryname)
            ->value('id');

        $relevantServices = DB::table('agent_services')
            ->where('category_id', $relevantCatId)->get();

        return response()->json(['success' => true, 'services' => $relevantServices]);
    }

    public function updateService(Request $request, $serviceId)
    {

        if (Auth::user()->roles->contains('name', 'admin')) {
            $request->validate([
                'category_name' => 'required|unique:services,category_name,' . $serviceId,
                'category_type' => 'required|string',
                'recommended' => 'required|in:notRecommended,isRecommended',
                'category_image' => 'nullable|string',
            ]);

            $service = Service::find($serviceId);
            if (!$service) {
                return response()->json(['error' => 'Service not found'], 404);
            }

            $dataToUpdate = [
                'category_name' => $request->input('category_name'),
                'category_type' => $request->input('category_type'),
                'recommended' => $request->input('recommended'),
            ];

            if ($request->has('image')) {
                $base64Image = $request->input('image');

                $base64Image = preg_replace('/^data:image\/\w+;base64,/', '', $base64Image);
                $imageData = base64_decode($base64Image);

                $imageName = uniqid() . '.png';
                $imagePath = 'images/' . $imageName;

                Storage::disk('public')->put($imagePath, $imageData);

                $dataToUpdate['image'] = $imagePath;
            }

            $service->update($dataToUpdate);

            return response()->json(['message' => 'Service updated successfully', 'service' => $service]);
        }

        return response()->json(['error' => 'Unauthorized'], 403);
    }

    public function deleteService($serviceId)
    {
        if (Auth::user()->roles->contains('name', 'admin')) {
            $service = Service::find($serviceId);

            if (!$service) {
                return response()->json(['error' => 'Service not found'], 404);
            }

            $service->delete();

            return response()->json(['message' => 'Service deleted successfully']);
        }

        return response()->json(['error' => 'Unauthorized'], 403);
    }

    public function viewService($serviceId)
    {
        if (Auth::user()->roles->contains('name', 'admin')) {
            $service = Service::find($serviceId);

            if (!$service) {
                return response()->json(['error' => 'Service not found'], 404);
            }

            if ($service->image && !str_starts_with($service->image, 'http')) {
                $service->image = url('storage/' . $service->image);
            }


            return response()->json(['service' => $service]);
        }

        return response()->json(['error' => 'Unauthorized'], 403);
    }

    public function viewServiceApp($serviceId)
    {
        if (Auth::user()->roles->contains('name', 'admin')) {
            $service = Service::find($serviceId);

            if (!$service) {
                return response()->json(['error' => 'Service not found'], 404);
            }

            return response()->json(['service' => $service]);
        }

        return response()->json(['error' => 'Unauthorized'], 403);
    }

    public function submitReferral($serviceId)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        $service = AgentService::find($serviceId);

        if (!$service) {
            return response()->json(['error' => 'Service not found'], 404);
        }

        try {
            $referral = DB::table('referrals')->insert([
                'referrer_id' => $user->id,
                'agent_service_id' => $serviceId,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'message' => 'Your Referral has been submitted. Our tem At the House App will act on your referral on priority',
                'referral' => $referral,
                'success' => true
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to submit referral',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function allReferrals()
    {
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
        $referrals = DB::table('referrals')
            ->where('status', "pending")
            ->get();

        return response()->json(['success' => true, 'pendindreferrals' => $referrals]);
    }

    public function allReferralsApp(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['message' => 'User not authenticated', 'success' => false], 401);
            }

            if ($request->input('latitude') && $request->input('longitude')) {
                $clientLatitude = $request->input('latitude');
                $clientLongitude = $request->input('longitude');
            } else {
                return response()->json(['success' => false, 'error' => 'Latitude and Longitude are required'], 400);
            }

            $referrals = DB::table('referrals')
                ->join('agent_services', 'referrals.agent_service_id', '=', 'agent_services.id')
                ->join('users', 'referrals.referrer_id', '=', 'users.id')
                ->select(
                    'referrals.*',
                    'agent_services.service_name as service_name',
                    'agent_services.short_description as short_description',
                    'users.email as email',
                    'agent_services.phone_number as phone_number',
                    'agent_services.featured_image as featured_image',
                    'agent_services.latitude as latitude',
                    'agent_services.longitude as longitude',
                )
                ->where('referrals.referrer_id', $user->id)
                ->get();

            foreach ($referrals as $referral) {
                if ($referral->featured_image && !str_starts_with($referral->featured_image, 'http')) {
                    $referral->featured_image = url('storage/' . $referral->featured_image);
                }

                $serviceLatitude = $referral->latitude;
                $serviceLongitude = $referral->longitude;
                $distance = ServiceController::getDistance($clientLatitude, $clientLongitude, $serviceLatitude, $serviceLongitude);
                $referral->distance = $distance;
            }

            if ($referrals->isEmpty()) {
                return response()->json(['message' => 'No referrals found', 'success' => false], 404);
            }

            return response()->json(['success' => true, 'services' => $referrals]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred: ' . $e->getMessage(), 'success' => false], 500);
        }
    }

    public function singleReferralsApp(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        $serviceId = $request->input('id');

        if ($request->input('latitude') && $request->input('longitude')) {
            $clientLatitude = $request->input('latitude');
            $clientLongitude = $request->input('longitude');
        } else {
            return response()->json(['success' => false, 'error' => 'Latitude and Longitude are required'], 400);
        }

        $service = AgentService::find($serviceId);

        if (!$service) {
            return response()->json(['error' => 'Service not found'], 404);
        }

        try {
            $referral = DB::table('referrals')
                ->join('agent_services', 'referrals.agent_service_id', '=', 'agent_services.id')
                ->join('users', 'agent_services.user_id', '=', 'users.id')
                ->select(
                    'referrals.*',
                    'agent_services.service_name as service_name',
                    'agent_services.short_description as short_description',
                    'users.email as email',
                    'agent_services.phone_number as phone_number',
                    'agent_services.featured_image as featured_image',
                    'agent_services.latitude as latitude',
                    'agent_services.longitude as longitude',
                )
                ->where('referrals.referrer_id', $user->id)
                ->where('referrals.agent_service_id', $serviceId)
                ->first();

            $serviceLatitude = $referral->latitude;
            $serviceLongitude = $referral->longitude;
            $distance = ServiceController::getDistance($clientLatitude, $clientLongitude, $serviceLatitude, $serviceLongitude);
            $referral->distance = $distance;

            if (!$referral) {
                return response()->json([
                    'success' => false,
                    'message' => 'No referral found for this service',
                ], 404);
            }

            if ($referral->featured_image && !str_starts_with($referral->featured_image, 'http')) {
                $referral->featured_image = url('storage/' . $referral->featured_image);
            }

            if (!$referral) {
                return response()->json([
                    'message' => 'No referral found for this service',
                    'success' => false
                ], 404);
            }

            return response()->json([
                'message' => 'Referral found',
                'referral' => $referral,
                'success' => true
            ], 200);
        } catch (\Exception $e) {
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
