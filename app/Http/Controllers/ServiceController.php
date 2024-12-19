<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AgentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Stevebauman\Location\Facades\Location;

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
            'service_type' => 'required',
            'service_name' => 'required',
            'short_description' => 'required',
            'address' => 'required',
            'city' => 'required',
            'state' => 'required',
            'zipcode' => 'required',
            'email' => 'required',
            'phone_number' => 'required',
            'category_id' => 'required',
            'website' => 'required',
            'hours' => 'required', // Add this line for hours validation
            //'featured_image' => 'required|image|mimes:jpeg,png,jpg|max:2048', 
            // 'banner_image' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = auth()->user();

        if ($user->hasAnyRole(['admin', 'agent'])) {
            if ($user->hasRole('admin')) {
                $userId = $request->input('user_id') ?? $user->id;
                $service = AgentService::create([
                    'service_type' => $request->input('service_type'),
                    'service_name' => $request->input('service_name'),
                    'short_description' => $request->input('short_description'),
                    'address' => $request->input('address'),
                    'city' => $request->input('city'),
                    'state' => $request->input('state'),
                    'zipcode' => $request->input('zipcode'),
                    'latitude' => $request->input('latitude'),
                    'longitude' => $request->input('longitude'),
                    'email' => $request->input('email'),
                    'phone_number' => $request->input('phone_number'),
                    'category_id' => $request->input('category_id'),
                    'website' => $request->input('website'),
                    'user_id' => $userId,
                    'hours' => $request->input('hours'), // Add this line for hours
                ]);
            } elseif ($user->hasRole('agent')) {
                $service = AgentService::create([
                    'service_type' => $request->input('service_type'),
                    'service_name' => $request->input('service_name'),
                    'short_description' => $request->input('short_description'),
                    'address' => $request->input('address'),
                    'city' => $request->input('city'),
                    'state' => $request->input('state'),
                    'zipcode' => $request->input('zipcode'),
                    'latitude' => $request->input('latitude'),
                    'longitude' => $request->input('longitude'),
                    'email' => $request->input('email'),
                    'phone_number' => $request->input('phone_number'),
                    'category_id' => $request->input('category_id'),
                    'website' => $request->input('website'),
                    'user_id' => $user->id,
                    'hours' => $request->input('hours'), // Add this line for hours
                ]);
            }

            if ($request->hasFile('featured_image')) {
                $featuredImage = $request->file('featured_image');
                $filePath = $featuredImage->store('featured_image', 'public'); // Stores in storage/app/public/featured_images

                $service->featured_image = $filePath;
            } else {
                $defaultImagePath = 'featured_image/default_image.png';
                $service->featured_image = $defaultImagePath;
            }

            $service->save();

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
        } elseif ($user->hasRole('admin')) {
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
        } else {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
    }

    public function getAllAgentServicesApp()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $coordinates = $this->getClientCoordinates();
        $clientLatitude = $coordinates->original['latitude'];
        $clientLongitude = $coordinates->original['longitude'];

        try {
            // $allowedCategoryTypes = ['popular', 'most_demanding'];
            // $result = [];

            // foreach ($allowedCategoryTypes as $categoryType) {
            $services = DB::table('agent_services')
                ->join('services', 'agent_services.category_id', '=', 'services.id')
                //->where('agent_services.service_type', $categoryType)
                ->select('agent_services.*', 'services.category_name', 'services.category_type')
                ->get();

            foreach ($services as $service) {
                $service->featured_image = $service->featured_image
                    ? url('storage/' . $service->featured_image)
                    : null;

                $service->formatted_hours = $this->formatHours($service->hours);
                unset($service->hours);

                $serviceLatitude = $service->latitude;
                $serviceLongitude = $service->longitude;
                $distance = $this->getDistance($clientLatitude, $clientLongitude, $serviceLatitude, $serviceLongitude);
                $service->distance = $distance;
            }

            //$result[$categoryType] = $services;
            //}

            return response()->json(['success' => true, 'services' => $services]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving services',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function getAllPopularServices(Request $request)
    {

        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $coordinates = $this->getClientCoordinates();
        $clientLatitude = $coordinates->original['latitude'];
        $clientLongitude = $coordinates->original['longitude'];

        try {
            $allowedCategoryTypes = ['popular'];
            $result = [];

            foreach ($allowedCategoryTypes as $categoryType) {
                $services = DB::table('agent_services')
                    ->join('services', 'agent_services.category_id', '=', 'services.id')
                    ->where('agent_services.service_type', $categoryType)
                    ->select('agent_services.*', 'services.category_name', 'services.category_type')
                    ->get();

                foreach ($services as $service) {
                    $service->featured_image = $service->featured_image
                        ? url('storage/' . $service->featured_image)
                        : null;

                    $service->formatted_hours = $this->formatHours($service->hours);
                    unset($service->hours);

                    $serviceLatitude = $service->latitude;
                    $serviceLongitude = $service->longitude;
                    $distance = $this->getDistance($clientLatitude, $clientLongitude, $serviceLatitude, $serviceLongitude);
                    $service->distance = $distance;
                }

                $result[$categoryType] = $services;
            }

            return response()->json(['success' => true, 'services' => $result]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving services',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Format hours JSON into readable format.
     *
     * @param string|null $hours
     * @return array|null
     */
    private function formatHours($hours)
    {
        if (!$hours) {
            return null;
        }

        $hoursData = json_decode($hours, true);
        $formattedHours = [];

        foreach ($hoursData as $dayHours) {
            $open = $dayHours['open'] ?? null;
            $close = $dayHours['close'] ?? null;

            if (empty($open) && empty($close)) {
                $formattedHours[$dayHours['day']] = [
                    "open" => "Closed",
                    "close" => "Closed"
                ];
            } else {
                $formattedHours[$dayHours['day']] = [
                    'open' => $open ? date("h:i A", strtotime($open)) : 'Closed',
                    'close' => $close ? date("h:i A", strtotime($close)) : 'Closed',
                ];
            }
        }

        return $formattedHours;
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
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        $coordinates = $this->getClientCoordinates();
        $clientLatitude = $coordinates->original['latitude'];
        $clientLongitude = $coordinates->original['longitude'];

        $allowedCategoryTypes = ['popular', 'most_demanding'];
        $result = [];

        try {
            foreach ($allowedCategoryTypes as $categoryType) {
                $services = DB::table('agent_services')
                    ->join('services', 'agent_services.category_id', '=', 'services.id')
                    ->where('agent_services.service_type', $categoryType)
                    ->select('agent_services.*', 'services.category_name', 'services.category_type', 'services.image')
                    ->get();

                foreach ($services as $service) {
                    if ($service->featured_image && !str_starts_with($service->featured_image, 'http')) {
                        $service->featured_image = url('storage/' . $service->featured_image);
                    }
                    if ($service->banner_image && !str_starts_with($service->banner_image, 'http')) {
                        $service->banner_image = url('storage/' . $service->banner_image);
                    }
                    if ($service->image && !str_starts_with($service->image, 'http')) {
                        $service->image = url('storage/' . $service->image);
                    }

                    $serviceLatitude = $service->latitude;
                    $serviceLongitude = $service->longitude;
                    $distance = $this->getDistance($clientLatitude, $clientLongitude, $serviceLatitude, $serviceLongitude);
                    $service->distance = $distance;
                }
                $result[$categoryType] = $services;
            }

            // Return all services grouped by category type
            return response()->json(['success' => true, 'services' => $result]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred while retrieving services', 'error' => $e->getMessage()], 500);
        }
        // }else{
        //     return response()->json(['error' => 'Unauthorized'], 403);
        // }
    }

    public function getAllAgentsServices()
    {

        $allowedCategoryTypes = ['popular', 'all'];
        $result = [];

        try {
            foreach ($allowedCategoryTypes as $categoryType) {
                if ($categoryType == "all") {
                    $services = DB::select("SELECT * FROM agent_services");
                } else {
                    // $services = DB::select("SELECT * FROM services WHERE category_type = ?", [$categoryType]);
                    $services = DB::table('agent_services')
                        ->join('services', 'agent_services.category_id', '=', 'services.id')
                        ->where('agent_services.service_type', [$categoryType])
                        ->select('agent_services.*', 'services.category_name', 'services.category_type')
                        ->get();
                }
                $result[$categoryType] = $services;
            }

            // Return all services grouped by category type
            return response()->json(['success' => true, 'services' => $result]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred while retrieving services', 'error' => $e->getMessage()], 500);
        }
    }

    // public function getAgentServiceApp($id)
    // {
    //     $user = Auth::user();

    //     try { 
    //         $service = AgentService::findOrFail($id);

    //         // // Check user roles if needed, e.g., agents can only access their own services
    //         // if ($user->hasRole('agent') && $service->user_id != $user->id) {
    //         //     return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
    //         // }

    //         // Append URL to the featured image if it exists
    //         if ($service->featured_image) {
    //             $service->featured_image = url('storage/' . $service->featured_image);
    //         }

    //         return response()->json(['success' => true, 'service' => $service]);
    //     } catch (ModelNotFoundException $e) {
    //         return response()->json(['success' => false, 'error' => 'Service not found'], 404);
    //     }
    // }

    public function getAgentServiceApp($id)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'data' => $user, 'error' => 'Unauthorized'], 403);
        }

        $coordinates = $this->getClientCoordinates();
        $clientLatitude = $coordinates->original['latitude'];
        $clientLongitude = $coordinates->original['longitude'];

        try {
            // Use first() instead of get() to fetch a single record
            $service = DB::table('agent_services')
                ->where('id', $id)
                ->first();

            if (!$service) {
                return response()->json(['success' => false, 'error' => 'Service not found'], 404);
            }

            // Append the full URL for the featured_image if it exists
            if ($service->featured_image) {
                $service->featured_image = url('storage/' . $service->featured_image);
            }

            // Optionally process the hours field
            if ($service->hours) {
                $service->formatted_hours = $this->formatHours($service->hours);
                unset($service->hours);
            }

            $serviceLatitude = $service->latitude;
            $serviceLongitude = $service->longitude;
            $distance = $this->getDistance($clientLatitude, $clientLongitude, $serviceLatitude, $serviceLongitude);
            $service->distance = $distance;

            return response()->json(['success' => true, 'result' => $service]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }


    // public function getAgentServiceApp($id)
    // {
    //     $user = Auth::user();

    //     if (!$user) {
    //         return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
    //     }

    //     try { 
    //         // Use a join to get additional details from the services table
    //         $service = DB::table('agent_services')
    //                     ->where('id', $id)
    //                     ->get();

    //         if (!$service) {
    //             return response()->json(['success' => false, 'error' => 'Service not found'], 404);
    //         }

    //         // Append URL to the featured image if it exists
    //         if ($service->featured_image) {
    //             $service->featured_image = url('storage/' . $service->featured_image);
    //         }

    //         $service->formatted_hours = $this->formatHours($service->hours);
    //                     unset($service->hours);

    //         if ($service->image) {
    //             $service->image = url('storage/' . $service->image);
    //         }

    //         return response()->json(['success' => true, 'service' => $service]);
    //     } catch (ModelNotFoundException $e) {
    //         return response()->json(['success' => false, 'error' => 'Service not found'], 404);
    //     }
    // }

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
            if ($service->featured_image) {
                $service->featured_image = url('storage/' . $service->featured_image);
            }
            if ($service->banner_image) {
                $service->banner_image = url('storage/' . $service->banner_image);
            }
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

    public function updateAgentService($id, Request $request)
    {
        $user = Auth::user();

        try {
            $service = AgentService::findOrFail($id);

            // Only update the featured image if a new image is provided
            if ($request->has('featuredImage') && !empty($request->input('featuredImage'))) {
                $featuredImageData = $request->input('featuredImage');

                // Check if it's a base64 string and needs to be updated
                if (strpos($featuredImageData, 'data:image') === 0) {
                    \Log::info('File is present.', ['filename' => $featuredImageData]);
                    $featuredImageData = str_replace('data:image/png;base64,', '', $featuredImageData);
                    $featuredImageData = str_replace(' ', '+', $featuredImageData);
                    $featuredImageName = uniqid() . '_featured.png'; // Ensure unique naming

                    // Save the image
                    Storage::disk('public')->put('featured_image/' . $featuredImageName, base64_decode($featuredImageData));

                    //$filePath = $featuredImageName->store('featured_image', 'public');

                    // Update the service with the new featured image path
                    $service->featured_image = 'featured_image/' . $featuredImageName;
                }
            }

            // Update other fields
            $service->service_type = $request->input('service_type');
            $service->service_name = $request->input('service_name');
            $service->short_description = $request->input('short_description');
            $service->address = $request->input('address');
            $service->city = $request->input('city');
            $service->state = $request->input('state');
            $service->zipcode = $request->input('zipcode');
            $service->latitude = $request->input('latitude');
            $service->longitude = $request->input('longitude');
            $service->website = $request->input('website');
            $service->email = $request->input('email');
            $service->phone_number = $request->input('phone_number');
            $service->category_id = $request->input('category_id');
            $service->hours = $request->input('hours');

            // Save changes
            $service->save();

            return response()->json(['message' => 'Service updated successfully'], 200);
        } catch (\Exception $e) {
            \Log::error('Error updating service:', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error updating service. Please try again later.'], 500);
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

    static public function getClientCoordinates()
    {
        $ip = self::getUserIpAddr();
        Log::info('IP Address:', ['ip' => $ip]);
        $location = Location::get($ip);
        $latitude = $location->latitude;
        $longitude = $location->longitude;
        Log::info('Latitude:', ['latitude' => $latitude]);
        Log::info('Longitude:', ['longitude' => $longitude]);

        return response()->json(['latitude' => $latitude, 'longitude' => $longitude]);
    }

    static public function getUserIpAddr()
    {
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP'])) $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if (isset($_SERVER['HTTP_X_FORWARDED'])) $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if (isset($_SERVER['HTTP_FORWARDED'])) $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if (isset($_SERVER['REMOTE_ADDR'])) $ipaddress = $_SERVER['REMOTE_ADDR'];
        else $ipaddress = request()->ip();

        if (strpos($ipaddress, ':') !== false) {
            $ipaddress = explode(':', $ipaddress)[0];
        }

        return $ipaddress;
    }

    static public function getDistance($latitude1, $longitude1, $latitude2, $longitude2)
    {
        $lat1 = deg2rad($latitude1);
        $lon1 = deg2rad($longitude1);
        $lat2 = deg2rad($latitude2);
        $lon2 = deg2rad($longitude2);

        $dlat = $lat2 - $lat1;
        $dlon = $lon2 - $lon1;
        $a = sin($dlat / 2) * sin($dlat / 2) + cos($lat1) * cos($lat2) * sin($dlon / 2) * sin($dlon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $r = 3959;

        $distance = $r * $c;

        return round($distance, 2);
    }
}
