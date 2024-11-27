<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AgentServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userIds = DB::table('users')->pluck('id')->toArray();
        $categoryIds = DB::table('services')->pluck('id')->toArray();
        
        $serviceTypes = ['popular', 'most_demanding', 'normal'];

        // Maryland cities with their approximate lat/long ranges
        $mdCities = [
            'Baltimore' => ['zip' => ['21201', '21202', '21203', '21204', '21205'], 'lat' => [39.2800, 39.3100], 'lng' => [-76.6300, -76.5900]],
            'Annapolis' => ['zip' => ['21401', '21402', '21403', '21404'], 'lat' => [38.9700, 39.0000], 'lng' => [-76.5200, -76.4900]],
            'Frederick' => ['zip' => ['21701', '21702', '21703', '21704'], 'lat' => [39.4100, 39.4400], 'lng' => [-77.4200, -77.3900]],
            'Rockville' => ['zip' => ['20850', '20851', '20852', '20853'], 'lat' => [39.0800, 39.1100], 'lng' => [-77.1600, -77.1300]],
            'Silver Spring' => ['zip' => ['20901', '20902', '20903', '20904'], 'lat' => [39.0000, 39.0300], 'lng' => [-77.0300, -77.0000]]
        ];

        $streets = ['Main Street', 'Oak Avenue', 'Maple Drive', 'Cedar Lane', 'Pine Road', 'Washington Street', 'Park Avenue'];

        for( $i = 0; $i < 10; $i++ ) {
            $city = array_rand($mdCities);
            $cityData = $mdCities[$city];
            
            DB::table('agent_services')->insert([
                'user_id' => $userIds[array_rand($userIds)],
                'category_id' => $categoryIds[array_rand($categoryIds)],
                'service_name' => 'Service ' . $i,
                'short_description' => 'Short description ' . $i,
                'address' => rand(100, 9999) . ' ' . $streets[array_rand($streets)],
                'city' => $city,
                'state' => 'MD',
                'zipcode' => $cityData['zip'][array_rand($cityData['zip'])],
                'message_number' => '410-555-0100',
                'phone_number' => '410-555-0101',
                'hours' => 'Mon-Fri: 9am-5pm, Sat: 10am-3pm, Sun: Closed',
                'service_type' => $serviceTypes[array_rand($serviceTypes)],
                'latitude' => number_format(rand($cityData['lat'][0] * 10000, $cityData['lat'][1] * 10000) / 10000, 4),
                'longitude' => number_format(rand($cityData['lng'][0] * 10000, $cityData['lng'][1] * 10000) / 10000, 4),
            ]);
        }
    }
}