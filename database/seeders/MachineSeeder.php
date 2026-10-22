<?php

namespace Database\Seeders;

use App\Domain\DepletionCurve;
use App\Enums\AssignmentStatus;
use App\Enums\HardwareState;
use App\Enums\Tier;
use App\Models\VendingMachine;
use Illuminate\Database\Seeder;

/**
 * The facilities. Coordinates are the public entrances of well-known hospitals and transit stations, to about 100 m,
 * so every pin lands on the facility and no paid geocoding service is needed.
 */
class MachineSeeder extends Seeder
{
    /** [name, type, city, lat, lng] */
    private const COMMERCIAL = [
        ['Massachusetts General Hospital', 'Hospital', 'Boston, MA', 42.3631, -71.0686],
        ['South Station', 'Transit station', 'Boston, MA', 42.3519, -71.0552],
        ['NYU Langone Health', 'Hospital', 'New York, NY', 40.7421, -73.9739],
        ['Penn Station', 'Transit station', 'New York, NY', 40.7506, -73.9935],
        ['Thomas Jefferson University Hospital', 'Hospital', 'Philadelphia, PA', 39.9496, -75.1577],
        ['30th Street Station', 'Transit station', 'Philadelphia, PA', 39.9556, -75.1822],
        ['Union Station', 'Transit station', 'Washington, DC', 38.8973, -77.0063],
        ['Johns Hopkins Hospital', 'Hospital', 'Baltimore, MD', 39.2965, -76.5927],
        ['Grady Memorial Hospital', 'Hospital', 'Atlanta, GA', 33.7518, -84.3827],
        ['Five Points Station', 'Transit station', 'Atlanta, GA', 33.7539, -84.3915],
        ['Cleveland Clinic Main Campus', 'Hospital', 'Cleveland, OH', 41.5026, -81.6213],
        ['Northwestern Memorial Hospital', 'Hospital', 'Chicago, IL', 41.8957, -87.6217],
        ['Jackson Memorial Hospital', 'Hospital', 'Miami, FL', 25.7905, -80.2120],
        ['Baylor University Medical Center', 'Hospital', 'Dallas, TX', 32.7903, -96.7809],
        ['Dallas Union Station', 'Transit station', 'Dallas, TX', 32.7761, -96.8075],
        ['Hennepin County Medical Center', 'Hospital', 'Minneapolis, MN', 44.9725, -93.2619],
        ['Henry Ford Hospital', 'Hospital', 'Detroit, MI', 42.3671, -83.0846],
        ['Vanderbilt University Medical Center', 'Hospital', 'Nashville, TN', 36.1420, -86.8010],
    ];

    private const ENTERPRISE = [
        ['Grand Central Terminal', 'Transit hub', 'New York, NY', 40.7527, -73.9772],
        ['Logan International Airport, Terminal B', 'Airport', 'Boston, MA', 42.3656, -71.0096],
        ['Chicago Union Station', 'Transit hub', 'Chicago, IL', 41.8789, -87.6403],
        ['Hartsfield-Jackson Atlanta Airport, Domestic Terminal', 'Airport', 'Atlanta, GA', 33.6407, -84.4277],
        ['Houston Methodist Hospital', 'Hospital campus', 'Houston, TX', 29.7106, -95.3989],
        ['Los Angeles Union Station', 'Transit hub', 'Los Angeles, CA', 34.0562, -118.2365],
        ['Ronald Reagan UCLA Medical Center', 'Hospital campus', 'Los Angeles, CA', 34.0663, -118.4467],
        ['Harborview Medical Center', 'Hospital campus', 'Seattle, WA', 47.6039, -122.3250],
        ['Denver Union Station', 'Transit hub', 'Denver, CO', 39.7531, -105.0000],
        ['Salesforce Transit Center', 'Transit hub', 'San Francisco, CA', 37.7897, -122.3966],
        ['Phoenix Sky Harbor International Airport', 'Airport', 'Phoenix, AZ', 33.4342, -112.0117],
        ['Minneapolis-Saint Paul International Airport', 'Airport', 'Minneapolis, MN', 44.8819, -93.2217],
        ['Miami International Airport', 'Airport', 'Miami, FL', 25.7933, -80.2906],
        ['Nashville International Airport', 'Airport', 'Nashville, TN', 36.1267, -86.6819],
    ];

    /**
     * Adds every facility in the lists that is not in the database yet, so running it again (or the update migration
     * that calls it) only adds new places and never touches existing machines, members or ledgers.
     */
    public function run(): void
    {
        $have = VendingMachine::query()->pluck('facility_name')->flip();
        $tz = (string) config('drinkwell.timezone');
        $now = now($tz);
        $minute = $now->hour * 60 + $now->minute;
        // New machines carry on from how many of each size already exist.
        $n = [];
        foreach ([Tier::Commercial, Tier::Enterprise] as $t) {
            $n[$t->value] = VendingMachine::query()->where('tier', $t)->count();
        }

        foreach ([[Tier::Commercial, self::COMMERCIAL, 'C'], [Tier::Enterprise, self::ENTERPRISE, 'E']] as [$tier, $list, $letter]) {
            foreach ($list as [$facility, $type, $city, $lat, $lng]) {
                if ($have->has($facility)) {
                    continue;
                }
                $n[$tier->value]++;
                $cap = $tier->capacity();
                $stock = $cap - DepletionCurve::soldBy($cap, $minute);

                VendingMachine::create([
                    'serial_no' => VendingMachine::serialFor($tier, $n[$tier->value], $facility),
                    'tier' => $tier,
                    'capacity' => $cap,
                    'facility_name' => $facility,
                    'facility_type' => $type,
                    'city' => $city,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'stock' => $stock,
                    'hardware_state' => $stock === 0 ? HardwareState::SoldOut : HardwareState::Online,
                    'assignment_status' => AssignmentStatus::Open,
                    'operating_date' => $now->toDateString(),
                    'day_minute' => $minute,
                    'clock_synced_at' => now(),
                ]);
            }
        }
    }
}
