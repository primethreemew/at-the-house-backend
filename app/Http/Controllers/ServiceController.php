<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AgentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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

        if ($user->hasRole('agent')) {
            $services = AgentService::where('user_id', $user->id)->get();
        } elseif ($user->hasRole('admin')) {
            $services = AgentService::all();
        } else {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json(['services' => $services]);
    }

    public function getAgentServicesApp()
    {
        $user = Auth::user();

        if ($user->hasRole('agent')) {
            $services = AgentService::where('user_id', $user->id)->get();
        } elseif ($user->hasRole('admin')) {
            $services = AgentService::all();
        } else {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        return response()->json(['success' => true, 'services' => $services]);
    }

    public function getAgentServiceApp($id)
    {
        $user = Auth::user();

        try { 
            $service = AgentService::findOrFail($id);

            // if ($user->hasRole('agent') && $service->user_id == $user->id) {
            //     // Agent can retrieve their own service
            // } elseif ($user->hasRole('admin') || $user->hasRole('user')) {
            //     // Admin and User can retrieve any service
            // } else {
            //     return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
            // }

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
    public function updateAgentService($id, Request $request)
    {
        $user = Auth::user();

        try {
            $service = AgentService::findOrFail($id);

            if ($user->hasRole('agent') && $service->user_id == $user->id) {
                $service->update($request->all());
            } elseif ($user->hasRole('admin')) {
                $service->update($request->all());
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
