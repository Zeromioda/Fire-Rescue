<?php

namespace Database\Seeders;

use App\Models\Apparatus;
use App\Models\Backlog;
use App\Models\Equipment;
use App\Models\Incident;
use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Realistic demo data for testing dashboards and AI analysis.
 * Run manually: php artisan db:seed --class=DemoDataSeeder --force
 * Safe to re-run: it stops if demo data already exists.
 */
class DemoDataSeeder extends Seeder
{
    private const TZ = 'Asia/Manila';
    private const EMAIL_DOMAIN = 'bfad178.test';

    public function run(): void
    {
        mt_srand(178); // repeatable "random" choices

        if (User::where('email', 'like', '%@'.self::EMAIL_DOMAIN)->exists()) {
            // Earlier demo data: only add dispatch/tracking records it is missing
            $count = DB::transaction(fn () => $this->responseTracking());
            $this->command?->info("Demo data already present; added response tracking to {$count} incidents.");
            return;
        }

        DB::transaction(function () {
            $dispatcher = $this->dispatcher();
            $firefighters = $this->firefighters();

            $this->incidents($dispatcher);
            $this->equipment();
            $this->apparatuses();
            $this->backlogs($firefighters);
            $this->loginLogs($firefighters->prepend($dispatcher));
            $this->responseTracking();
        });

        $this->command?->info('Demo data seeded.');
    }

    /**
     * Give demo incidents dispatch assignments, response milestones and a timeline,
     * derived from their dispatcher notes and after-action times.
     * Only touches demo incidents (they have dispatcher_notes) that have no tracking yet.
     */
    private function responseTracking(): int
    {
        $units = Apparatus::all()->keyBy('call_sign');
        $crew = User::role('Firefighter')->where('email', 'like', '%@'.self::EMAIL_DOMAIN)->get();
        $roles = ['Team Leader', 'Driver/Operator', 'Nozzleman', 'Nozzleman', 'Rescuer', 'EMS/First Aider'];
        $busy = collect(); // responders already on an active demo incident

        $incidents = Incident::whereNotNull('dispatcher_notes')
            ->whereNull('dispatched_at')
            ->whereDoesntHave('updates')
            ->orderBy('created_at')
            ->get();

        foreach ($incidents as $incident) {
            $reported = $incident->created_at->copy();
            $logger = $incident->user_id;
            $log = fn (Carbon $at, ?string $stage, ?string $note, ?int $by) => DB::table('incident_updates')->insert([
                'incident_id' => $incident->id, 'user_id' => $by, 'stage' => $stage, 'note' => $note,
                'created_at' => $at, 'updated_at' => $at,
            ]);

            $log($reported, 'Reported', 'Emergency call logged.', $logger);

            if ($incident->status === 'Pending') {
                continue;
            }

            $active = $incident->status !== 'Resolved';
            $dispatched = $reported->copy()->addMinutes(mt_rand(1, 3));
            $arrived = $this->reportTime($incident, 'Time of Arrival', $reported) ?? $dispatched->copy()->addMinutes(mt_rand(4, 10));
            $controlled = $incident->status === 'Dispatched' ? null
                : ($this->reportTime($incident, 'Time of Fire Subdue', $reported) ?? $arrived->copy()->addMinutes(mt_rand(15, 60)));
            $resolved = $incident->status === 'Resolved' ? $controlled->copy()->addMinutes(mt_rand(15, 45)) : null;

            // Recent incidents: never record a milestone in the future
            foreach (['arrived', 'controlled', 'resolved'] as $var) {
                if ($$var && $$var->isFuture()) {
                    $$var = now()->subMinutes(1);
                }
            }

            // Units named in the dispatcher notes, e.g. "Dispatched Engine 1, Tanker 1."
            $assigned = $units->filter(fn ($u, $sign) => str_contains((string) $incident->dispatcher_notes, $sign));
            foreach ($assigned as $unit) {
                DB::table('incident_apparatus')->insert([
                    'incident_id' => $incident->id, 'apparatus_id' => $unit->id,
                    'dispatched_at' => $dispatched, 'released_at' => $resolved,
                    'created_at' => $dispatched, 'updated_at' => $resolved ?? $dispatched,
                ]);
            }

            $pool = $active ? $crew->whereNotIn('id', $busy) : $crew;
            $team = $pool->shuffle()->take(min($pool->count(), mt_rand(3, 6)))->values();
            foreach ($team as $i => $person) {
                DB::table('incident_personnel')->insert([
                    'incident_id' => $incident->id, 'user_id' => $person->id, 'role' => $roles[$i] ?? 'Responder',
                    'dispatched_at' => $dispatched, 'released_at' => $resolved,
                    'created_at' => $dispatched, 'updated_at' => $resolved ?? $dispatched,
                ]);
                if ($active) {
                    $busy->push($person->id);
                }
            }

            $leader = $team->first()?->id;
            $sent = collect([$assigned->keys()->implode(', ') ?: null, $team->count().' responders'])->filter()->implode(' with ');
            $log($dispatched, 'Dispatched', "Dispatched {$sent}.", $logger);
            $log($arrived, 'On Scene', 'First unit arrived on scene; size-up in progress.', $leader);
            if ($controlled) {
                $log($controlled, 'Under Control', 'Fire declared under control.', $leader);
            }
            if ($resolved) {
                $log($resolved, 'Resolved', 'Fire out; overhaul complete and area turned over to the barangay.', $leader);
            }

            DB::table('incidents')->where('id', $incident->id)->update([
                'dispatched_at' => $dispatched, 'arrived_at' => $arrived,
                'controlled_at' => $controlled, 'resolved_at' => $resolved,
            ]);
        }

        // Match live availability to the active assignments
        $deployedUnits = DB::table('incident_apparatus')->whereNull('released_at')->pluck('apparatus_id');
        Apparatus::whereIn('id', $deployedUnits)->update(['status' => 'dispatched']);
        Apparatus::whereNotIn('id', $deployedUnits)->where('status', 'dispatched')->update(['status' => 'available']);

        $deployedCrew = DB::table('incident_personnel')->whereNull('released_at')->pluck('user_id');
        User::whereIn('id', $deployedCrew)->update(['is_available' => false]);

        return $incidents->count();
    }

    /** Read a clock time like "6:25 AM" for a field in the incident's AI summary, on the day it was reported. */
    private function reportTime(Incident $incident, string $field, Carbon $reported): ?Carbon
    {
        if (! preg_match('/'.preg_quote($field, '/').':\D*?(\d{1,2}:\d{2} [AP]M)/', (string) $incident->ai_summary, $m)) {
            return null;
        }

        $local = $reported->copy()->timezone(self::TZ);
        $time = Carbon::parse($local->format('Y-m-d').' '.$m[1], self::TZ);

        if ($time->lt($local)) {
            $time->addDay(); // crossed midnight
        }

        return $time->utc();
    }

    private function dispatcher(): User
    {
        $admin = User::role('Admin')->orderBy('id')->first();

        if ($admin) {
            return $admin;
        }

        $admin = User::forceCreate([
            'name' => 'SFO2 Ramon Dela Cruz',
            'email' => 'dispatch@'.self::EMAIL_DOMAIN,
            'password' => Str::random(40),
            'rank' => 'Admin',
            'badge_number' => 'BFP-178-001',
            'email_verified_at' => now(),
        ]);
        $admin->assignRole(Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']));

        return $admin;
    }

    private function firefighters()
    {
        $role = Role::firstOrCreate(['name' => 'Firefighter', 'guard_name' => 'web']);

        $names = [
            'FO1 Mark Anthony Reyes', 'FO1 Jerome Bautista', 'FO2 Kristine Mae Santos', 'FO1 Paolo Villanueva',
            'FO2 Joshua Mendoza', 'FO3 Rodel Castillo', 'FO1 Angelica Ramos', 'FO2 Christian Garcia',
            'FO1 Jayson Aquino', 'FO3 Marvin Torres', 'FO1 Ronaldo Navarro', 'SFO1 Edgardo Flores',
            'FO2 Mary Grace Domingo', 'FO1 Kenneth Salazar', 'FO2 Arnel Pascual', 'FO1 Rhea Lopez',
            'FO3 Dennis Manalo', 'FO1 John Carlo Rivera', 'FO2 Mylene Gonzales', 'SFO1 Alfredo Cruz',
        ];

        return collect($names)->map(function ($name, $i) use ($role) {
            $plain = Str::of($name)->after(' ')->lower()->replace(' ', '.')->ascii();

            $user = User::forceCreate([
                'name' => $name,
                'email' => $plain.'@'.self::EMAIL_DOMAIN,
                // Demo accounts are not meant to be logged into
                'password' => Str::random(40),
                'rank' => 'Firefighter',
                'badge_number' => sprintf('BFP-178-%03d', 101 + $i),
                'is_available' => $i % 5 !== 3,
                'email_verified_at' => now(),
                'created_at' => now()->subDays(200 - $i * 3),
                'updated_at' => now()->subDays(mt_rand(0, 20)),
            ]);
            $user->assignRole($role);

            return $user;
        });
    }

    private function incidents(User $dispatcher): void
    {
        $locations = [
            ['Zabarte Road, Brgy. 178 Camarin, Caloocan City', 14.7566, 121.0452],
            ['Area D, Brgy. 178 Camarin, Caloocan City', 14.7601, 121.0498],
            ['Kiko, Brgy. 178 Camarin, Caloocan City', 14.7632, 121.0531],
            ['Camarin Road cor. Susano Road, Caloocan City', 14.7548, 121.0415],
            ['Phase 2, Almar Subdivision, Camarin, Caloocan City', 14.7580, 121.0560],
            ['Maria Clara St., Area B, Camarin, Caloocan City', 14.7615, 121.0470],
            ['Villa Vicenta Subdivision, Camarin, Caloocan City', 14.7655, 121.0489],
            ['Camarin Public Market, Zabarte Road, Caloocan City', 14.7572, 121.0438],
            ['Brgy. 178 Covered Court, Area C, Camarin, Caloocan City', 14.7593, 121.0512],
            ['Tala Creek embankment, Camarin, Caloocan City', 14.7669, 121.0547],
            ['Deparo Road boundary, Camarin, Caloocan City', 14.7520, 121.0400],
            ['Area A interior, Brgy. 178 Camarin, Caloocan City', 14.7609, 121.0443],
        ];

        // [category, severity, title, description, cause, casualties, crew/overview, minutes to subdue]
        $templates = [
            ['Structural Fire', 'High', 'Residential fire – two-storey house', 'Caller reports thick smoke from the ground-floor kitchen of a two-storey house; occupants have evacuated.', 'LPG hose leak near the kitchen stove ignited', 'One resident treated on scene for first-degree burns on the left arm; no fatalities', 'Engine 1 and Engine 2 with 10 personnel responded; fire was confined to the kitchen and part of the dining area', 38],
            ['Electrical Fire', 'Medium', 'Electrical fire at sari-sari store', 'Store owner reports sparks and smoke from the back of the store near the chest freezer.', 'Overloaded extension cord powering a chest freezer and display chiller', 'No casualties', 'Engine 1 with 6 personnel responded; power was cut and the fire was extinguished with dry chemical extinguishers', 18],
            ['Structural Fire', 'Critical', 'Row-house fire spreading to adjacent units', 'Multiple callers report a fire in a row of light-material houses spreading quickly to neighbouring units.', 'Illegal "jumper" electrical connection overheated inside a unit', 'Two residents treated for smoke inhalation; one firefighter sustained a minor hand laceration', 'Engine 1, Engine 2, Tanker 1 and two volunteer brigades with 24 personnel responded; four units were totally burned and six families were displaced', 142],
            ['Grass Fire', 'Low', 'Grass fire along creek embankment', 'Barangay tanod reports a spreading grass fire along the creek embankment near houses.', 'Open burning of household rubbish spread to dry grass', 'No casualties', 'Fire Motorcycle 1 and Engine 2 with 5 personnel responded; approximately 300 square meters of grassland burned', 27],
            ['Vehicle Fire', 'Medium', 'Tricycle engine fire', 'Driver reports his tricycle caught fire while parked at the terminal.', 'Fuel line leak dripping onto the hot engine block', 'No casualties', 'Fire Motorcycle 2 and Engine 1 with 5 personnel responded; the sidecar and engine were damaged', 12],
            ['Rubbish Fire', 'Low', 'Rubbish fire in vacant lot', 'Resident reports heavy smoke from a pile of burning garbage in a vacant lot.', 'Intentional open burning of garbage', 'No casualties', 'Fire Motorcycle 1 with 3 personnel responded; the pile was doused and the barangay was advised on the open-burning ordinance', 15],
            ['Electrical Fire', 'Low', 'Electric post fire at service drop', 'Caller reports sparks and a small fire on an electric post in front of a residence.', 'Loose service-drop connection arcing at the electric post', 'No casualties', 'Engine 2 with 4 personnel responded and secured the area until the electric utility crew de-energized the line', 35],
            ['Structural Fire', 'High', 'Kitchen fire – unattended cooking', 'Neighbour reports smoke and flames from a house with no one answering the door.', 'Unattended cooking oil on a lit stove', 'One occupant suffered minor burns while trying to put out the fire', 'Engine 1 with 8 personnel responded; the kitchen was partially damaged', 22],
            ['Structural Fire', 'Medium', 'Bedroom fire caused by lighted candle', 'Resident reports a small fire in a bedroom during a power interruption.', 'Lighted candle left unattended during a power interruption ignited a curtain', 'A 7-year-old child was checked for smoke inhalation; no injuries', 'Engine 2 with 6 personnel responded; the fire was limited to one bedroom', 19],
            ['Vehicle Fire', 'High', 'Delivery truck fire along main road', 'Motorists report a delivery truck engulfed in flames on the roadside; traffic is building up.', 'Electrical short circuit in the wiring harness behind the dashboard', 'No casualties; the driver escaped unhurt', 'Engine 1 and Tanker 1 with 9 personnel responded; one lane was closed for 40 minutes', 31],
            ['Commercial Fire', 'High', 'Fire at motorcycle repair shop', 'Shop owner reports a fire among stored oil and parts at the back of the shop.', 'Welding sparks ignited oil-soaked rags', 'One mechanic treated for minor burns on the forearm', 'Engine 1, Engine 2 and Rescue 1 with 12 personnel responded; the shop interior was heavily damaged', 47],
            ['Rescue', 'Medium', 'Rescue – residents trapped by street flooding', 'Barangay requests assistance for families trapped by waist-deep floodwater during heavy rain.', 'Not applicable – water rescue operation', 'No casualties; 14 residents including 3 senior citizens were evacuated', 'Rescue 1 with 8 personnel and a rubber boat responded; evacuees were brought to the covered court', 95],
            ['Structural Fire', 'Low', 'Smoke report – burnt food, no fire', 'Neighbour reports smoke coming from a closed apartment window.', 'Burnt food left on the stove; no fire', 'No casualties', 'Engine 2 with 4 personnel responded; the unit was ventilated and the occupant was advised', 9],
            ['Electrical Fire', 'Medium', 'Electric fan motor overheated', 'Resident reports a burning smell and smoke from an electric fan in the living room.', 'Overheated motor of an electric fan left running for long hours', 'No casualties', 'Engine 1 with 5 personnel responded; the fan and a sofa were damaged', 14],
            ['Commercial Fire', 'Critical', 'Warehouse fire – plastic household goods', 'Security guard reports a large fire inside a warehouse storing plastic household products.', 'Not specified', 'No casualties', 'Engine 1, Engine 2, Tanker 1, Rescue 1 and neighbouring stations with 35 personnel responded; the fire reached third alarm and the warehouse sustained major damage', 214],
            ['Rescue', 'Medium', 'Rescue – motorist trapped after collision', 'Caller reports a car collided with a concrete post and the driver is trapped inside.', 'Not applicable – vehicular extrication', 'Driver sustained a leg injury and was transported to the hospital by Ambulance 1', 'Rescue 1 and Ambulance 1 with 7 personnel responded; the driver was extricated using hydraulic rescue tools', 34],
            ['Electrical Fire', 'Medium', 'Computer unit fire at internet café', 'Café attendant reports smoke from one of the computer units.', 'Short circuit in a computer power supply unit', 'No casualties', 'Engine 2 with 5 personnel responded; two computer units were damaged', 16],
            ['Structural Fire', 'High', 'Boarding house room fire', 'Boarders report a fire in a second-floor room of a boarding house.', 'Unextinguished cigarette butt on a foam mattress', 'One boarder treated for smoke inhalation', 'Engine 1 and Engine 2 with 11 personnel responded; one room was gutted and two rooms had smoke damage', 41],
            ['Gas Leak', 'Medium', 'LPG leak – no ignition', 'Resident reports a strong smell of gas from a neighbour\'s kitchen.', 'Not applicable – leaking LPG regulator contained without ignition', 'No casualties', 'Engine 2 with 4 personnel responded; the tank valve was closed, the house was ventilated and the regulator was replaced', 20],
            ['Grass Fire', 'Medium', 'Grass fire near residential area', 'Residents report a wide grass fire moving toward houses due to strong wind.', 'Discarded cigarette in dry grass during the dry season', 'No casualties', 'Engine 2 and Fire Motorcycle 1 with 7 personnel responded; about 1,200 square meters burned before containment', 52],
        ];

        // Hours of day weighted toward late afternoon and evening peaks
        $hours = [6, 8, 10, 11, 12, 13, 15, 16, 17, 17, 18, 18, 19, 19, 20, 20, 21, 22, 23, 2];

        $crews = ['Engine 1', 'Engine 2', 'Tanker 1', 'Rescue 1', 'Fire Motorcycle 1'];

        // 25 closed incidents over ~6 months, then 5 in the last 3 days
        $resolved = collect(range(0, 24))->map(fn ($n) => [
            'template' => $templates[$n % count($templates)],
            'at' => now(self::TZ)->subDays(6 + $n * 7 + mt_rand(0, 5))->setTime($hours[mt_rand(0, count($hours) - 1)], mt_rand(0, 59)),
            'status' => 'Resolved',
        ]);

        // Dry-season grass fires fall in April/May
        $resolved = $resolved->map(function ($row) {
            if ($row['template'][0] === 'Grass Fire') {
                $row['at'] = $row['at']->copy()->setDate((int) now(self::TZ)->format('Y'), mt_rand(4, 5), mt_rand(1, 28));
            }
            return $row;
        });

        $active = collect([
            [0, 2, 'Pending'],
            [1, 6, 'Dispatched'],
            [7, 21, 'Under Control'],
            [16, 41, 'Dispatched'],
            [13, 66, 'Under Control'],
        ])->map(fn ($a) => [
            'template' => $templates[$a[0]],
            'at' => now(self::TZ)->subHours($a[1])->subMinutes(mt_rand(0, 50)),
            'status' => $a[2],
        ]);

        foreach ($resolved->concat($active)->sortBy('at')->values() as $n => $row) {
            [$category, $severity, $title, $description, $cause, $casualties, $overview, $subdueMinutes] = $row['template'];
            [$address, $lat, $lng] = $locations[($n * 5) % count($locations)];

            $reportedAt = $row['at'];
            $arrivedAt = $reportedAt->copy()->addMinutes(mt_rand(5, 13));
            $subduedAt = $arrivedAt->copy()->addMinutes($subdueMinutes + mt_rand(-3, 6));
            $crew = collect($crews)->shuffle()->take(mt_rand(1, 3))->implode(', ');

            $attributes = [
                'user_id' => $dispatcher->id,
                'title' => $title,
                'category' => $category,
                'severity' => $severity,
                'location_address' => $address,
                'latitude' => $lat + mt_rand(-15, 15) / 10000,
                'longitude' => $lng + mt_rand(-15, 15) / 10000,
                'description' => $description,
                'status' => $row['status'],
                'dispatcher_notes' => $row['status'] === 'Pending' ? 'Awaiting unit confirmation.' : "Dispatched {$crew}.",
                'created_at' => $reportedAt->copy()->utc(),
                'updated_at' => $reportedAt->copy()->addMinutes(mt_rand(3, 30))->utc(),
            ];

            if ($row['status'] === 'Resolved') {
                $isRescue = str_starts_with($cause, 'Not applicable');
                $arrival = $arrivedAt->format('g:i A');
                $subdued = $subduedAt->format('g:i A');

                $attributes['after_action_report'] = "Received the call at {$reportedAt->format('g:i A')} on {$reportedAt->format('F j, Y')}. "
                    ."Team arrived on scene at {$arrival}. {$overview}. "
                    .($isRescue ? "Operation was completed at {$subdued}. " : "Fire was declared under control and subsequently fire out at {$subdued}. ")
                    ."Cause: {$cause}. Casualties: {$casualties}. Area was turned over to the barangay after overhaul and safety inspection.";

                $attributes['ai_summary'] =
                    "• Cause of Fire: {$cause}\n".
                    "• Time of Arrival: {$arrival}\n".
                    "• Time of Fire Subdue: ".($isRescue ? "Not applicable ({$subdued} operation completed)" : $subdued)."\n".
                    "• Casualties: {$casualties}\n".
                    "• Operational Overview: {$overview}.";

                $attributes['updated_at'] = $subduedAt->copy()->addMinutes(mt_rand(40, 180))->utc();
            }

            Incident::forceCreate($attributes);
        }
    }

    private function equipment(): void
    {
        // [name, category, quantity, status]
        $items = [
            ['Self-Contained Breathing Apparatus (SCBA) Set', 'SCBA', 12, 'Available'],
            ['SCBA Spare Air Cylinder 6.8L', 'SCBA', 18, 'Available'],
            ['Structural Turnout Gear Set (Coat & Pants)', 'PPE', 24, 'Assigned'],
            ['Fire Helmet with Face Shield', 'PPE', 24, 'Assigned'],
            ['Firefighting Gloves', 'PPE', 30, 'Available'],
            ['Fire Hose 1.5" x 50ft (Attack Line)', 'Hose', 20, 'Available'],
            ['Fire Hose 2.5" x 50ft (Supply Line)', 'Hose', 14, 'Available'],
            ['Fire Hose 1.5" x 50ft (Damaged Jacket)', 'Hose', 3, 'In Maintenance'],
            ['Combination Fog/Straight Stream Nozzle', 'Nozzle', 8, 'Available'],
            ['Portable Fire Extinguisher ABC 10 lbs', 'Extinguisher', 15, 'Available'],
            ['Hydraulic Rescue Tool Set (Spreader & Cutter)', 'Rescue Tool', 1, 'Available'],
            ['Thermal Imaging Camera', 'Detection', 2, 'Assigned'],
            ['Halligan Bar', 'Forcible Entry', 4, 'Available'],
            ['Flathead Axe', 'Forcible Entry', 4, 'Available'],
            ['24ft Aluminium Extension Ladder', 'Ladder', 2, 'Available'],
            ['Portable Generator 5kVA', 'Power', 1, 'In Maintenance'],
            ['Rope Rescue Kit (Static Rope 60m)', 'Rescue Tool', 2, 'Available'],
            ['Trauma First Aid Kit', 'Medical Kit', 6, 'Available'],
            ['Automated External Defibrillator (AED)', 'Medical Kit', 1, 'Available'],
            ['Handheld VHF Radio', 'Communication', 10, 'Assigned'],
            ['AFFF Foam Concentrate (20L pail)', 'Foam', 6, 'Available'],
            ['Life Vest', 'Water Rescue', 12, 'Available'],
            ['Spine Board with Head Immobilizer', 'Medical Kit', 2, 'Available'],
            ['Old Canvas Hose 1.5" (Condemned)', 'Hose', 5, 'Decommissioned'],
        ];

        foreach ($items as $i => [$name, $category, $qty, $status]) {
            Equipment::forceCreate([
                'asset_tag' => sprintf('EQ-178-%03d', $i + 1),
                'name' => $name,
                'category' => $category,
                'quantity' => $qty,
                'status' => $status,
                'created_at' => now()->subDays(180 - $i * 5),
                'updated_at' => now()->subDays(mt_rand(0, 30)),
            ]);
        }
    }

    private function apparatuses(): void
    {
        // [call sign, plate, type, status, water liters, fuel %]
        $units = [
            ['Engine 1', 'SJA 1781', 'Pumper Truck', 'dispatched', 4000, 72],
            ['Engine 2', 'SJA 1782', 'Pumper Truck', 'dispatched', 4000, 64],
            ['Tanker 1', 'SJB 1783', 'Water Tanker', 'available', 10000, 88],
            ['Rescue 1', 'SJC 1784', 'Rescue Truck', 'available', 0, 91],
            ['Ambulance 1', 'SJD 1785', 'Ambulance', 'available', 0, 79],
            ['Ladder 1', 'SJE 1786', 'Aerial Ladder Truck', 'maintenance', 1500, 45],
            ['Command 1', 'SJF 1787', 'Command Vehicle', 'available', 0, 83],
            ['Fire Motorcycle 1', 'MC 17881', 'Fire Motorcycle', 'available', 60, 95],
            ['Fire Motorcycle 2', 'MC 17882', 'Fire Motorcycle', 'maintenance', 60, 30],
            ['Water Tender 2', 'SJG 1789', 'Water Tender', 'available', 6000, 67],
        ];

        foreach ($units as [$callSign, $plate, $type, $status, $water, $fuel]) {
            if (Apparatus::where('plate_number', $plate)->exists()) {
                continue;
            }

            Apparatus::forceCreate([
                'call_sign' => $callSign,
                'plate_number' => $plate,
                'type' => $type,
                'status' => $status,
                'water_capacity_liters' => $water,
                'fuel_level_percent' => $fuel,
                'created_at' => now()->subDays(mt_rand(150, 400)),
                'updated_at' => now()->subHours(mt_rand(1, 240)),
            ]);
        }
    }

    private function backlogs($firefighters): void
    {
        // [title, category, priority, status, description]
        $tasks = [
            ['Engine 1 brake inspection overdue', 'Apparatus Repair', 'High', 'In Progress', 'Brake pads worn per last inspection; schedule replacement with the motor pool.'],
            ['Ladder 1 hydraulic leak on aerial turntable', 'Apparatus Repair', 'Critical', 'In Progress', 'Hydraulic fluid leak found during weekly check; unit placed out of service.'],
            ['Fire Motorcycle 2 battery replacement', 'Apparatus Repair', 'Medium', 'Pending', 'Unit fails to start without jump; battery is over 2 years old.'],
            ['Hydrostatic testing of SCBA cylinders', 'Equipment Maintenance', 'High', 'Pending', '6 cylinders due for 5-year hydrostatic test this quarter.'],
            ['Replace damaged 1.5" hose jackets', 'Equipment Maintenance', 'Medium', 'In Progress', 'Three attack lines have jacket abrasion after the row-house fire.'],
            ['Portable generator carburetor cleaning', 'Equipment Maintenance', 'Low', 'Deferred', 'Generator surging under load; awaiting parts.'],
            ['Monthly fire extinguisher inspection – Camarin Public Market', 'General Task', 'Medium', 'Resolved', 'Inspected 42 extinguishers; 5 were recharged.'],
            ['Fire safety seminar – Area D homeowners', 'General Task', 'Medium', 'Resolved', 'Conducted LPG safety and electrical overloading seminar for 60 residents.'],
            ['Oplan Ligtas Pamayanan barangay drill', 'General Task', 'High', 'Pending', 'Coordinate community fire drill with barangay officials.'],
            ['Hydrant flow test – Zabarte Road', 'General Task', 'Medium', 'In Progress', 'Two hydrants reported low pressure; coordinate with the water utility.'],
            ['Follow-up investigation – warehouse fire cause', 'Emergency Incident', 'High', 'In Progress', 'Cause still undetermined; awaiting arson investigator report.'],
            ['Unverified smoke report – Kiko area', 'Emergency Incident', 'Low', 'Resolved', 'Patrol found backyard cooking with firewood; no fire.'],
            ['Inspect illegal "jumper" connections – Area A', 'Emergency Incident', 'Critical', 'Pending', 'Coordinate with the electric utility after the row-house fire.'],
            ['Grass-cutting along Tala Creek before dry season', 'General Task', 'Medium', 'Deferred', 'Request barangay clearing crew to reduce grass fire fuel load.'],
            ['Thermal imaging camera screen replacement', 'Equipment Maintenance', 'Medium', 'Pending', 'Screen has dead pixels; request supplier warranty service.'],
            ['Refill AFFF foam concentrate stock', 'Equipment Maintenance', 'Low', 'Resolved', 'Received 4 new 20L pails.'],
            ['Tanker 1 water pump pressure test', 'Apparatus Repair', 'Medium', 'Resolved', 'Pump passed at rated pressure.'],
            ['Update personnel contact roster', 'General Task', 'Low', 'Resolved', 'Roster updated with new radio call signs.'],
            ['Fire safety inspection – boarding houses in Area B', 'General Task', 'High', 'In Progress', '7 of 12 boarding houses inspected; 3 notices issued for missing extinguishers.'],
            ['Ambulance 1 oxygen tank refill', 'Equipment Maintenance', 'High', 'Resolved', 'Two oxygen tanks refilled and checked.'],
        ];

        foreach ($tasks as $i => [$title, $category, $priority, $status, $description]) {
            $created = now()->subDays(60 - $i * 3)->subHours(mt_rand(0, 12));

            Backlog::forceCreate([
                'ticket_number' => sprintf('BL-178-%03d', $i + 1),
                'title' => $title,
                'category' => $category,
                'priority' => $priority,
                'status' => $status,
                'description' => $description,
                'assigned_to_user_id' => $firefighters[$i % $firefighters->count()]->id,
                'created_at' => $created,
                'updated_at' => $status === 'Pending' ? $created : $created->copy()->addDays(mt_rand(1, 5)),
            ]);
        }
    }

    private function loginLogs($users): void
    {
        $devices = [
            ['Desktop Terminal', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0 Safari/537.36', 'BFAD Station 178 Console'],
            ['Mobile Handheld', 'Mozilla/5.0 (Linux; Android 14; SM-A155F) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0 Mobile Safari/537.36', 'Field Response – Mobile'],
            ['Field Tablet', 'Mozilla/5.0 (Linux; Android 13; SM-X205) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0 Safari/537.36', 'Apparatus Mounted Tablet'],
        ];

        // Shift changes at 08:00 and 20:00 drive most logins
        $shiftHours = [7, 7, 8, 8, 8, 19, 19, 20, 20, 13, 22, 2];

        for ($i = 0; $i < 40; $i++) {
            $user = $users[$i % $users->count()];
            [$device, $agent, $location] = $devices[$i % 5 === 0 ? 1 : ($i % 7 === 0 ? 2 : 0)];

            $at = now(self::TZ)->subDays(intdiv($i, 3))
                ->setTime($shiftHours[mt_rand(0, count($shiftHours) - 1)], mt_rand(0, 59), mt_rand(0, 59));

            if ($at->isFuture()) {
                $at = now(self::TZ)->subMinutes(mt_rand(5, 90));
            }

            LoginLog::forceCreate([
                'user_id' => $user->id,
                'ip_address' => $device === 'Desktop Terminal' ? '192.168.10.'.mt_rand(10, 40) : '112.198.'.mt_rand(60, 120).'.'.mt_rand(2, 250),
                'user_agent' => $agent,
                'device_type' => $device,
                'location' => $location,
                'login_at' => $at->copy()->utc(),
                'created_at' => $at->copy()->utc(),
                'updated_at' => $at->copy()->utc(),
            ]);
        }
    }
}
