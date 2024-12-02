<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
class MapController extends Controller
{
    public function getZipCodeFromAddress(Request $request)
    {
        $address = $request->input('address');
        if (!$address) {
            return response()->json(['error' => 'Address is required'], 422);
        }

        $apiKey = env('GOOGLE_MAP_API_KEY');
        $address = urlencode($address);
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&key={$apiKey}";

        $response = Http::get($url);
        $data = $response->json();

        if ($data['status'] !== 'OK') {
            return response()->json(['error' => 'Unable to find address'], 422);
        }

        $postalCode = null;
        foreach ($data['results'][0]['address_components'] as $component) {
            if (in_array('postal_code', $component['types'])) {
                $postalCode = $component['long_name'];
                break;
            }
        }

        if (!$postalCode) {
            return response()->json(['error' => 'No postal code found for this address'], 422);
        }

        return response()->json(['postal_code' => $postalCode]);
    }

    public function getCoordinatesFromZipCode(Request $request)
    {
        $zipCode = $request->input('zipcode');
        if (!$zipCode) {
            return response()->json(['error' => 'Zip code is required'], 422);
        }

        $apiKey = env('GOOGLE_MAP_API_KEY');
        $address = urlencode($zipCode);
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&key={$apiKey}";

        $response = Http::get($url);
        $data = $response->json();

        if ($data['status'] !== 'OK') {
            return response()->json(['error' => 'Unable to find coordinates for this zip code'], 422);
        }

        $location = $data['results'][0]['geometry']['location'];
        $latitude = $location['lat'];
        $longitude = $location['lng'];

        return response()->json([
            'latitude' => $latitude,
            'longitude' => $longitude
        ]);
    }
}