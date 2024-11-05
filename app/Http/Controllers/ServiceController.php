<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AgentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ServiceController extends Controller
{
    /**
     * Create a new agent service.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function agentServiceCreate(Request $request)
    {
        $request->validate([
            'service_type'=> 'required',
            'service_name' => 'required',
            'short_description' => 'required',
            'message_number' => 'required',
            'phone_number' => 'required',
            'category_id' => 'required',
            'hours' => 'required', // Add this line for hours validation
            'featured_image' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'banner_image' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = auth()->user();

        if ($user->hasAnyRole(['admin', 'agent'])) {
            if ($user->hasRole('admin')) {
                $userId = $request->input('user_id') ?? $user->id;
                $service = AgentService::create([
                    'service_type' => $request->input('service_type'),
                    'service_name' => $request->input('service_name'),
                    'short_description' => $request->input('short_description'),
                    'message_number' => $request->input('message_number'),
                    'phone_number' => $request->input('phone_number'),
                    'category_id' => $request->input('category_id'),
                    'user_id' => $userId,
                    'hours' => $request->input('hours'), // Add this line for hours
                ]);
            } elseif ($user->hasRole('agent')) {
                $service = AgentService::create([
                    'service_type' => $request->input('service_type'),
                    'service_name' => $request->input('service_name'),
                    'short_description' => $request->input('short_description'),
                    'message_number' => $request->input('message_number'),
                    'phone_number' => $request->input('phone_number'),
                    'category_id' => $request->input('category_id'),
                    'user_id' => $user->id,
                    'hours' => $request->input('hours'), // Add this line for hours
                ]);
            }

            if ($request->hasFile('featured_image')) {
                $featuredImage = $request->file('featured_image')->store('images');
                $service->update(['featured_image' => $featuredImage]);
            }

            if ($request->hasFile('banner_image')) {
                $bannerImage = $request->file('banner_image')->store('images');
                $service->update(['banner_image' => $bannerImage]);
            }

            return response()->json(['message' => 'Service created successfully', 'service' => $service], 201);
        } else {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
    }

    /**
     * Get all services for an agent or all services for an admin.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllAgentServices()
    {
        
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($user->hasRole('agent')) {
            $services = AgentService::where('user_id', $user->id)->get();

            foreach ($services as $service) {
                if ($service->featured_image) {
                    $service->featured_image = url('storage/' . $service->featured_image);
                }

                if ($service->banner_image) {
                    $service->banner_image = url('storage/' . $service->banner_image);
                }
            }
            return response()->json(['success' => true, 'services' => $services]);
        }elseif ($user->hasRole('admin')) {
            $allowedCategoryTypes = ['popular', 'most_demanding'];
            $result = [];

            try {
                foreach ($allowedCategoryTypes as $categoryType) {
                // $services = DB::select("SELECT * FROM services WHERE category_type = ?", [$categoryType]);
                $services = DB::table('agent_services')
                    ->join('services', 'agent_services.category_id', '=', 'services.id')
                    ->where('agent_services.service_type', [$categoryType])
                    ->select('agent_services.*', 'services.category_name', 'services.category_type')
                    ->get();
                    $result[$categoryType] = $services;
                }

                foreach ($services as $service) {
                    if ($service->featured_image) {
                        $service->featured_image = url('storage/' . $service->featured_image);
                    }

                    if ($service->banner_image) {
                        $service->banner_image = url('storage/' . $service->banner_image);
                    }
                }

                // Return all services grouped by category type
                return response()->json(['success' => true, 'services' => $result]);

            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => 'An error occurred while retrieving services', 'error' => $e->getMessage()], 500);
            }
        }else{
            return response()->json(['error' => 'Unauthorized'], 403);
        }
    }

    public function getAgentServicesApp()
    {
        // $user = Auth::user();
        

        // if ($user->hasRole('agent')) {
        //     $services = AgentService::where('user_id', $user->id)->get();
        // } elseif ($user->hasRole('admin')) {
        //     $services = AgentService::all();
        // } else {
        //     return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        // }

        // return response()->json(['success' => true, 'services' => $services]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($user->hasRole('agent')) {
            $services = AgentService::where('user_id', $user->id)->get();

            foreach ($services as $service) {
                if ($service->featured_image) {
                    $service->featured_image = url('storage/' . $service->featured_image);
                }

                if ($service->banner_image) {
                    $service->banner_image = url('storage/' . $service->banner_image);
                }
            }
            return response()->json(['success' => true, 'services' => $services]);
        }elseif ($user->hasRole('admin')) {
            $allowedCategoryTypes = ['popular', 'most_demanding'];
            $result = [];

            try {
                foreach ($allowedCategoryTypes as $categoryType) {
                
                $services = DB::table('agent_services')
                    ->join('services', 'agent_services.category_id', '=', 'services.id')
                    ->where('agent_services.service_type', [$categoryType])
                    ->select('agent_services.*', 'services.category_name', 'services.category_type')
                    ->get();
                    $result[$categoryType] = $services;
                }

                foreach ($services as $service) {
                    if ($service->featured_image) {
                        $service->featured_image = url('storage/' . $service->featured_image);
                    }

                    if ($service->banner_image) {
                        $service->banner_image = url('storage/' . $service->banner_image);
                    }
                }

                // Return all services grouped by category type
                return response()->json(['success' => true, 'services' => $result]);

            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => 'An error occurred while retrieving services', 'error' => $e->getMessage()], 500);
            }
        }else{
            return response()->json(['error' => 'Unauthorized'], 403);
        }
    }

    public function getAllAgentsServices()
    {

        $allowedCategoryTypes = ['popular', 'most_demanding'];
        $result = [];

        try {
            foreach ($allowedCategoryTypes as $categoryType) {
               // $services = DB::select("SELECT * FROM services WHERE category_type = ?", [$categoryType]);
               $services = DB::table('agent_services')
                ->join('services', 'agent_services.category_id', '=', 'services.id')
                ->where('agent_services.service_type', [$categoryType])
                ->select('agent_services.*', 'services.category_name', 'services.category_type')
                ->get();
                $result[$categoryType] = $services;
            }

            // Return all services grouped by category type
            return response()->json(['success' => true, 'services' => $result]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred while retrieving services', 'error' => $e->getMessage()], 500);
        }
    }

    public function getAgentServiceApp($id)
    {
        $user = Auth::user();

        try { 
            $service = AgentService::findOrFail($id);

            // // Check user roles if needed, e.g., agents can only access their own services
            // if ($user->hasRole('agent') && $service->user_id != $user->id) {
            //     return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
            // }

            // Append URL to the featured image if it exists
            if ($service->featured_image) {
                $service->featured_image = url('storage/' . $service->featured_image);
            }

            return response()->json(['success' => true, 'service' => $service]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'error' => 'Service not found'], 404);
        }
    }


    /**
     * Get a specific agent service by ID.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAgentService($id)
    {
        $user = Auth::user();

        try { 
            $service = AgentService::findOrFail($id);

            if ($user->hasRole('agent') && $service->user_id == $user->id) {
                // Agent can retrieve their own service
            } elseif ($user->hasRole('admin') || $user->hasRole('user')) {
                // Admin and User can retrieve any service
            } else {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            return response()->json(['service' => $service]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Service not found'], 404);
        }
    }

    /**
     * Update a specific agent service by ID.
     *
     * @param  int  $id
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    // public function updateAgentService($id, Request $request)
    // {
    //     $user = Auth::user();

    //     try {
    //         $service = AgentService::findOrFail($id);

    //         $imagePath = $request->hasFile('featuredImage') ? $request->file('featuredImage')->store('featured_image', 'public') : null;

    //         if ($user->hasRole('agent') && $service->user_id == $user->id) {
    //             $service->update($request->all());
    //         } elseif ($user->hasRole('admin')) {
    //             $service->update($request->all());
    //         } else {
    //             return response()->json(['error' => 'Unauthorized'], 403);
    //         }

    //         return response()->json(['message' => 'Service updated successfully', 'service' => $service]);
    //     } catch (ModelNotFoundException $e) {
    //         return response()->json(['error' => 'Service not found'], 404);
    //     }
    // }

    public function updateAgentService($id, Request $request)
    {
        $user = Auth::user();

        try {
            $service = AgentService::findOrFail($id);

            // Check if a base64 image string is provided
            if ($request->has('featuredImage')) {
                $imageData = $request->input('featuredImage');
                $imageData = str_replace('data:image/png;base64,', '', $imageData);
                $imageData = str_replace(' ', '+', $imageData);
                $imageName = uniqid() . '.png'; // or any other desired format
                Storage::disk('public')->put('featured_image/' . $imageName, base64_decode($imageData));
                $service->featured_image = 'featured_image/' . $imageName;
            }

            if ($user->hasRole('agent') && $service->user_id == $user->id) {
                $service->update($request->except('featuredImage')); // Exclude the image from mass assignment
            } elseif ($user->hasRole('admin')) {
                $service->update($request->except('featuredImage')); // Exclude the image from mass assignment
            } else {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            return response()->json(['message' => 'Service updated successfully', 'service' => $service]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Service not found'], 404);
        }
    }


    /**
     * Delete a specific agent service by ID.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteAgentService($id)
    {
        $user = Auth::user();

        try {
            $service = AgentService::findOrFail($id);

            if ($user->hasRole('agent') && $service->user_id == $user->id) {
                $service->delete();
            } elseif ($user->hasRole('admin')) {
                $service->delete();
            } else {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            return response()->json(['message' => 'Service deleted successfully']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Service not found'], 404);
        }
    }
}
