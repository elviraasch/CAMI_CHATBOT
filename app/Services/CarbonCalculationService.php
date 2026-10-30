<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class CarbonCalculationService
{
    private const FUEL_FACTORS = [
        'solar_cn53' => 72.85, 'solar_cn51' => 72.93, 'solar_cn48' => 73.28,
        'diesel' => 74.52, 'ron98' => 68.91, 'ron92' => 69.04,
        'ron90' => 69.29, 'ron88' => 69.67, 'lpg' => 65.41,
        'avtur' => 72.36, 'kerosene' => 72.43, 'minyakbakar' => 77.90,
        'coal' => 99.718, 'naturalgas' => 57.600
    ];

    private const GRID_FACTORS = [
        'jawa_bali' => 0.80, // Disamakan dengan calculator.js (0.80)
        'household' => 0.80, // Disamakan dengan calculator.js (0.80)
    ];

    private const TRANSIT_FACTORS = [
        'flight_dom' => 0.1330, 'flight_int_short' => 0.1530,
        'flight_int_long' => 0.1950, 'train' => 0.0370, 'bus' => 0.0890,
    ];

    private const FLIGHT_FACTORS = [
        'economy' => 1.0, 'business' => 1.5, 'first' => 2.0,
    ];

    private const TRAIN_FACTORS = [
        'ekonomi' => 0.01219, 'bisnis' => 0.01308, 'eksekutif' => 0.01750,
        'panoramic' => 0.02469, 'luxury' => 0.02804, 'priority' => 0.03044,
        'compartment' => 0.04390,
    ];

    private const FOOD_FACTORS = [
        'beef' => 209.9, 'poultry' => 20.1, 'fish' => 26.5, 'veg' => 1.0, 
        'rice' => 8.8, 'egg' => 8.4, 'fruits' => 2.9, 'milk' => 4.8,
    ];

    private const VEHICLE_FACTORS = [
        'car_petrol' => [
            'ron98' => 68.91, 'ron92' => 69.04, 'ron90' => 69.29,
            'ron88' => 69.67, 
        ],
        'car_diesel' => [
            'ron98' => 68.91, 'ron92' => 69.04, 'ron90' => 69.29,
            'ron88' => 69.67, 'diesel' => 74.52,
        ],
        'motorcycle' => [
            'ron98' => 68.91, 'ron92' => 69.04, 'ron90' => 69.29,
            'ron88' => 69.67, 
        ],
    ];

    private const WATER_FACTOR = 0.149; // Disamakan dengan calculator.js

    private const WASTE_FACTOR = 0.52;
    
    private const LIMBAH_FACTOR = 0.272;  

    private const HOTEL_FACTOR = 49.37;

    private const AIRPORT_PLACE_ALIASES = [
        'jakarta' => 'CGK', 'soekarno hatta' => 'CGK',
        'halim' => 'HLP', 'halim perdanakusuma' => 'HLP',
        'bali' => 'DPS', 'denpasar' => 'DPS', 'ngurah rai' => 'DPS',
        'surabaya' => 'SUB', 'juanda' => 'SUB',
        'medan' => 'KNO', 'kualanamu' => 'KNO', 'kuala namu' => 'KNO',
        'makassar' => 'UPG', 'hasanuddin' => 'UPG', 'sultan hasanuddin' => 'UPG',
        'yogyakarta' => 'YIA', 'jogja' => 'YIA', 'kulon progo' => 'YIA',
        'adisutjipto' => 'JOG', 'bandung' => 'BDO', 'husein sastranegara' => 'BDO',
        'semarang' => 'SRG', 'ahmad yani' => 'SRG',
        'solo' => 'SOC', 'surakarta' => 'SOC', 'adi soemarmo' => 'SOC',
        'palembang' => 'PLM', 'sultan mahmud badaruddin' => 'PLM',
        'balikpapan' => 'BPN', 'sepinggan' => 'BPN',
        'pontianak' => 'PNK', 'supadio' => 'PNK',
        'banjarmasin' => 'BDJ', 'syamsudin noor' => 'BDJ',
        'manado' => 'MDC', 'sam ratulangi' => 'MDC',
        'lombok' => 'LOP', 'mataram' => 'LOP', 'zainuddin abdul madjid' => 'LOP',
        'batam' => 'BTH', 'hang nadim' => 'BTH',
        'pekanbaru' => 'PKU', 'sultan syarif kasim' => 'PKU',
        'padang' => 'PDG', 'minangkabau' => 'PDG',
        'aceh' => 'BTJ', 'banda aceh' => 'BTJ', 'sultan iskandar muda' => 'BTJ',
        'malang' => 'MLG', 'abdul rachman saleh' => 'MLG',
    ];

    public function calculate(User $user, array $data): array
    {
        return $user->isCompany()
            ? $this->calculateCompany($data)
            : $this->calculatePersonal($data);
    }

    private function calculatePersonal(array $data): array
    {
        $energyFuel = $this->choice($data, 'energy_fuel');
        $energyFactor = $energyFuel === 'none' ? 0.0 : $this->factor(self::FUEL_FACTORS, $energyFuel);
        $energy = $energyFuel === 'none'
            ? 0.0
            : $this->number($data, 'energy_qty') * 12
                * $energyFactor;

        $vehicleType = $this->choice($data, 'vehicle_type');
        $vehicleFuel = $this->choice($data, 'vehicle_fuel');
        $vehicleFactor = $vehicleType === 'none'
            ? 0.0
            : $this->nestedFactor(self::VEHICLE_FACTORS, $vehicleType, $vehicleFuel);
        $vehicle = $vehicleType === 'none'
            ? 0.0
            : $this->number($data, 'vehicle_km') * 12
                * $vehicleFactor;

        $electricityFactor = self::GRID_FACTORS['household'];
        $electricity = $this->number($data, 'electricity_kwh')
            * 12 * $electricityFactor;

        $transitMode = $this->choice($data, 'transit_mode');
        $transitFactor = $transitMode === 'none'
            ? 0.0
            : $this->factor(self::TRANSIT_FACTORS, $transitMode);
        $transit = $transitMode === 'none'
            ? 0.0
            : $this->number($data, 'transit_km')
                * $transitFactor;

        $foodType = $this->choice($data, 'food_type');
        $foodFactor = $foodType === 'none' ? 0.0 : $this->factor(self::FOOD_FACTORS, $foodType);
        $food = $foodType === 'none'
            ? 0.0
            : $this->number($data, 'food_kg') * 12
                * $foodFactor;

        $transitLabels = [
            'flight_dom' => 'Pesawat domestik',
            'flight_int_short' => 'Pesawat internasional jarak pendek',
            'flight_int_long' => 'Pesawat internasional jarak jauh',
            'train' => 'Kereta',
            'bus' => 'Bus',
        ];
        $foodLabels = [
            'beef' => 'Daging sapi',
            'poultry' => 'Unggas',
            'fish' => 'Ikan',
            'veg' => 'Sayuran',
        ];

        $details = [
            'energy_rt' => $energy,
            'vehicle' => $vehicle,
            'electricity' => $electricity,
            'transit' => $transit,
            'food' => $food,
            'water' => $this->number($data, 'water_m3') * 12 * self::WATER_FACTOR,
            'waste' => $this->number($data, 'waste_kg') * 12 * self::WASTE_FACTOR,
        ];

        return $this->result('personal', $details, [
            'scope1' => [
                [
                    'label' => 'Energi rumah tangga',
                    'source' => 'Energi rumah tangga',
                    'activity' => $energyFuel,
                    'input_type' => $energyFuel,
                    'quantity' => $this->number($data, 'energy_qty'),
                    'unit' => 'unit/bulan',
                    'emission_factor' => $energyFactor,
                    'value_kg' => $energy,
                ],
                [
                    'label' => 'Kendaraan pribadi',
                    'source' => 'Kendaraan pribadi',
                    'activity' => $vehicleType,
                    'input_type' => $vehicleType,
                    'transport_mode' => $vehicleType,
                    'transport_class' => $vehicleFuel,
                    'quantity' => $this->number($data, 'vehicle_km'),
                    'unit' => 'km/bulan',
                    'emission_factor' => $vehicleFactor,
                    'value_kg' => $vehicle,
                ],
            ],
            'scope2' => [
                [
                    'label' => 'Konsumsi listrik',
                    'source' => 'Konsumsi listrik',
                    'activity' => 'Listrik rumah tangga',
                    'input_type' => 'electricity',
                    'quantity' => $this->number($data, 'electricity_kwh'),
                    'unit' => 'kWh/bulan',
                    'emission_factor' => $electricityFactor,
                    'value_kg' => $electricity,
                ],
            ],
            'scope3' => [
                [
                    'label' => 'Transportasi umum',
                    'source' => 'Transportasi umum',
                    'activity' => $transitLabels[$transitMode] ?? 'Transportasi umum',
                    'input_type' => $transitMode,
                    'transport_mode' => $transitMode,
                    'distance_km' => $this->number($data, 'transit_km'),
                    'quantity' => $this->number($data, 'transit_km'),
                    'unit' => 'penumpang-km',
                    'emission_factor' => $transitFactor,
                    'value_kg' => $transit,
                ],
                [
                    'label' => 'Konsumsi makanan',
                    'source' => 'Konsumsi makanan',
                    'activity' => $foodLabels[$foodType] ?? 'Konsumsi makanan',
                    'input_type' => $foodType,
                    'quantity' => $this->number($data, 'food_kg'),
                    'unit' => 'kg/bulan',
                    'emission_factor' => $foodFactor,
                    'value_kg' => $food,
                ],
                [
                    'label' => 'Penggunaan air',
                    'source' => 'Penggunaan air',
                    'activity' => 'Air',
                    'input_type' => 'water',
                    'quantity' => $this->number($data, 'water_m3'),
                    'unit' => 'm3/bulan',
                    'emission_factor' => self::WATER_FACTOR,
                    'value_kg' => $details['water'],
                ],
                [
                    'label' => 'Pengelolaan limbah',
                    'source' => 'Pengelolaan limbah',
                    'activity' => 'Sampah',
                    'input_type' => 'waste',
                    'quantity' => $this->number($data, 'waste_kg'),
                    'unit' => 'kg/bulan',
                    'emission_factor' => self::WASTE_FACTOR,
                    'value_kg' => $details['waste'],
                ],
            ],
        ]);
    }

    private function calculateCompany(array $data): array
    {
        $stationaryFuel = $this->choice($data, 'stationary_fuel');
        $stationary = $stationaryFuel === 'none'
            ? 0.0
            : $this->number($data, 'stationary_qty')
                * $this->factor(self::FUEL_FACTORS, $stationaryFuel);

        $mobileFuel = $this->choice($data, 'mobile_fuel');
        $mobileFuelEmission = $mobileFuel === 'none'
            ? 0.0
            : $this->number($data, 'mobile_fuel_qty')
                * $this->factor(self::FUEL_FACTORS, $mobileFuel);

        $distanceFuel = $this->choice($data, 'mobile_distance_fuel');
        $distanceFactor = in_array(
            $distanceFuel,
            ['solar_cn53', 'solar_cn51', 'solar_cn48', 'diesel'],
            true
        ) ? 0.270 : (in_array(
            $distanceFuel,
            ['ron98', 'ron92', 'ron90', 'ron88'],
            true
        ) ? 0.210 : 0.0);
        $mobileDistanceEmission = $this->number($data, 'mobile_km') * $distanceFactor;
        $mobile = $mobileFuelEmission + $mobileDistanceEmission;

        $grid = $this->choice($data, 'electricity_grid');
        $electricity = $grid === 'none'
            ? 0.0
            : $this->number($data, 'electricity_kwh')
                * $this->factor(self::GRID_FACTORS, $grid);

        $flightClass = $this->choice($data, 'flight_class');
        $flightRoute = $flightClass === 'none'
            ? $this->emptyFlightRoute()
            : $this->flightRoute($data);
        $flightDistance = $flightRoute['distance_km'];
        $flight = $flightClass === 'none'
            ? 0.0
            : $this->number($data, 'flight_pax')
                * $flightDistance
                * $this->factor(self::FLIGHT_FACTORS, $flightClass);

        $hotel = $this->number($data, 'hotel_nights')
            * $this->number($data, 'hotel_rooms')
            * self::HOTEL_FACTOR;

        $trainClass = $this->choice($data, 'train_class');
        $train = $trainClass === 'none'
            ? 0.0
            : $this->number($data, 'train_km')
                * $this->factor(self::TRAIN_FACTORS, $trainClass);

        $details = [
            'stat' => $stationary,
            'mobile' => $mobile,
            'elec' => $electricity,
            'flight' => $flight,
            'flight_km' => $flightDistance,
            'hotel' => $hotel,
            'train' => $train,
        ];

        return $this->result('company', $details, [
            'scope1' => [
                [
                    'label' => 'Pembakaran stasioner',
                    'source' => 'Pembakaran stasioner',
                    'activity' => $stationaryFuel,
                    'input_type' => $stationaryFuel,
                    'quantity' => $this->number($data, 'stationary_qty'),
                    'unit' => 'unit',
                    'emission_factor' => $stationaryFuel === 'none' ? 0 : $this->factor(self::FUEL_FACTORS, $stationaryFuel),
                    'value_kg' => $stationary,
                ],
                [
                    'label' => 'Kendaraan operasional - BBM',
                    'source' => 'Kendaraan operasional',
                    'activity' => $mobileFuel,
                    'input_type' => $mobileFuel,
                    'quantity' => $this->number($data, 'mobile_fuel_qty'),
                    'unit' => 'unit',
                    'emission_factor' => $mobileFuel === 'none' ? 0 : $this->factor(self::FUEL_FACTORS, $mobileFuel),
                    'value_kg' => $mobileFuelEmission,
                ],
                [
                    'label' => 'Kendaraan operasional - jarak',
                    'source' => 'Kendaraan operasional',
                    'activity' => $distanceFuel,
                    'input_type' => $distanceFuel,
                    'transport_mode' => 'operational_vehicle',
                    'distance_km' => $this->number($data, 'mobile_km'),
                    'quantity' => $this->number($data, 'mobile_km'),
                    'unit' => 'km',
                    'emission_factor' => $distanceFactor,
                    'value_kg' => $mobileDistanceEmission,
                ],
            ],
            'scope2' => [
                [
                    'label' => 'Konsumsi listrik',
                    'source' => 'Konsumsi listrik',
                    'activity' => $grid,
                    'input_type' => $grid,
                    'quantity' => $this->number($data, 'electricity_kwh'),
                    'unit' => 'kWh',
                    'emission_factor' => $grid === 'none' ? 0 : $this->factor(self::GRID_FACTORS, $grid),
                    'value_kg' => $electricity,
                ],
            ],
            'scope3' => [
                [
                    'label' => $this->flightLabel($flightRoute),
                    'source' => 'Penerbangan dinas',
                    'activity' => $this->flightLabel($flightRoute),
                    'input_type' => $flightClass,
                    'value_kg' => $flight,
                    'origin' => $flightRoute['origin'],
                    'origin_name' => $flightRoute['origin_name'],
                    'destination' => $flightRoute['destination'],
                    'destination_name' => $flightRoute['destination_name'],
                    'distance_km' => $flightDistance,
                    'quantity' => $flightDistance * $this->number($data, 'flight_pax'),
                    'unit' => 'penumpang-km',
                    'emission_factor' => $flightClass === 'none' ? 0 : $this->factor(self::FLIGHT_FACTORS, $flightClass),
                    'pax' => $this->number($data, 'flight_pax'),
                    'passengers' => $this->number($data, 'flight_pax'),
                    'cabin_class' => $flightClass,
                    'transport_mode' => 'plane',
                    'transport_class' => $flightClass,
                ],
                [
                    'label' => 'Akomodasi hotel',
                    'source' => 'Akomodasi hotel',
                    'activity' => 'Kamar hotel',
                    'input_type' => 'hotel',
                    'quantity' => $this->number($data, 'hotel_nights') * $this->number($data, 'hotel_rooms'),
                    'unit' => 'kamar-malam',
                    'emission_factor' => self::HOTEL_FACTOR,
                    'value_kg' => $hotel,
                ],
                [
                    'label' => 'Perjalanan kereta',
                    'source' => 'Perjalanan kereta',
                    'activity' => $trainClass,
                    'input_type' => $trainClass,
                    'transport_mode' => 'train',
                    'transport_class' => $trainClass,
                    'distance_km' => $this->number($data, 'train_km'),
                    'quantity' => $this->number($data, 'train_km'),
                    'unit' => 'penumpang-km',
                    'emission_factor' => $trainClass === 'none' ? 0 : $this->factor(self::TRAIN_FACTORS, $trainClass),
                    'value_kg' => $train,
                ],
            ],
        ]);
    }

    private function result(string $mode, array $details, array $scopeDetails): array
    {
        $scope1 = collect($scopeDetails['scope1'])->sum('value_kg');
        $scope2 = collect($scopeDetails['scope2'])->sum('value_kg');
        $scope3 = collect($scopeDetails['scope3'])->sum('value_kg');

        return [
            'mode' => $mode,
            'details' => $details,
            'scope_details' => $scopeDetails,
            'scope1_kg' => $scope1,
            'scope2_kg' => $scope2,
            'scope3_kg' => $scope3,
            'total_kg' => $scope1 + $scope2 + $scope3,
        ];
    }

    private function number(array $data, string $key): float
    {
        $value = $data[$key] ?? 0;
        if (! is_numeric($value) || (float) $value < 0) {
            throw new InvalidArgumentException("Nilai {$key} harus berupa angka nol atau positif.");
        }

        return (float) $value;
    }

    private function choice(array $data, string $key): string
    {
        return (string) ($data[$key] ?? 'none');
    }

    private function factor(array $factors, string $key): float
    {
        if (! array_key_exists($key, $factors)) {
            throw new InvalidArgumentException("Pilihan faktor emisi {$key} tidak tersedia.");
        }

        return $factors[$key];
    }

    private function nestedFactor(array $factors, string $first, string $second): float
    {
        if (! isset($factors[$first]) || ! array_key_exists($second, $factors[$first])) {
            throw new InvalidArgumentException('Kombinasi kendaraan dan bahan bakar tidak tersedia.');
        }

        return $factors[$first][$second];
    }

    private function flightRoute(array $data): array
    {
        $originInput = $this->optionalText($data, 'flight_origin');
        $destinationInput = $this->optionalText($data, 'flight_destination');

        // Tetap menerima payload model Rasa lama selama proses pergantian model.
        if ($originInput === null && $destinationInput === null) {
            return array_merge($this->emptyFlightRoute(), [
                'distance_km' => $this->number($data, 'flight_km'),
            ]);
        }

        if ($originInput === null || $destinationInput === null) {
            throw new InvalidArgumentException('Bandara asal dan tujuan penerbangan wajib diisi.');
        }

        if (! Schema::hasTable('airports_data')) {
            throw new InvalidArgumentException('Data bandara belum tersedia pada database CAMAR.');
        }

        $origin = $this->resolveAirportCode($originInput);
        $destination = $this->resolveAirportCode($destinationInput);

        $airports = DB::table('airports_data')
            ->select('iata_code', 'name', 'latitude_deg', 'longitude_deg')
            ->whereIn('iata_code', [$origin, $destination])
            ->get()
            ->keyBy(fn (object $airport): string => strtoupper((string) $airport->iata_code));

        foreach ([$origin, $destination] as $code) {
            if (! $airports->has($code)) {
                throw new InvalidArgumentException("Bandara {$code} tidak ditemukan pada data bandara CAMAR.");
            }
        }

        $from = $airports->get($origin);
        $to = $airports->get($destination);

        $distance = $origin === $destination
            ? 0.0
            : round($this->haversineKm(
                (float) $from->latitude_deg,
                (float) $from->longitude_deg,
                (float) $to->latitude_deg,
                (float) $to->longitude_deg,
            ), 2);

        return [
            'origin' => $origin,
            'origin_name' => (string) $from->name,
            'destination' => $destination,
            'destination_name' => (string) $to->name,
            'distance_km' => $distance,
        ];
    }

    private function resolveAirportCode(string $input): string
    {
        $value = trim($input);
        if (preg_match('/^([A-Z]{3})(?:\s*-\s*.+)?$/i', $value, $matches)) {
            return strtoupper($matches[1]);
        }

        $normalised = $this->normaliseAirportSearch($value);
        if (isset(self::AIRPORT_PLACE_ALIASES[$normalised])) {
            return self::AIRPORT_PLACE_ALIASES[$normalised];
        }

        $terms = array_values(array_filter(
            explode(' ', $normalised),
            fn (string $term): bool => strlen($term) >= 2
        ));
        if ($terms === []) {
            throw new InvalidArgumentException("Nama bandara atau daerah '{$input}' belum dikenali.");
        }

        $query = DB::table('airports_data')
            ->select('iata_code', 'name')
            ->whereNotNull('iata_code')
            ->where('iata_code', '<>', '');
        foreach (array_slice($terms, 0, 5) as $term) {
            $query->where('name', 'like', "%{$term}%");
        }

        $airport = $query->limit(10)->get()
            ->sortBy(function (object $candidate) use ($normalised): string {
                $candidateName = $this->normaliseAirportSearch((string) $candidate->name);
                $exactRank = $candidateName === $normalised ? 0 : 1;

                return sprintf('%d-%05d', $exactRank, strlen($candidateName));
            })
            ->first();

        if (! $airport) {
            throw new InvalidArgumentException(
                "Bandara atau daerah '{$input}' tidak ditemukan. Coba gunakan kode IATA atau nama bandara yang lebih lengkap."
            );
        }

        return strtoupper((string) $airport->iata_code);
    }

    private function normaliseAirportSearch(string $value): string
    {
        $normalised = strtolower(trim($value));
        $normalised = preg_replace('/[^\pL\pN]+/u', ' ', $normalised) ?? $normalised;
        $terms = preg_split('/\s+/', $normalised) ?: [];
        $ignored = ['bandara', 'airport', 'internasional', 'international'];

        return implode(' ', array_values(array_filter(
            $terms,
            fn (string $term): bool => $term !== '' && ! in_array($term, $ignored, true)
        )));
    }

    private function optionalText(array $data, string $key): ?string
    {
        $value = trim((string) ($data[$key] ?? ''));

        return $value === '' ? null : $value;
    }

    private function emptyFlightRoute(): array
    {
        return [
            'origin' => null,
            'origin_name' => null,
            'destination' => null,
            'destination_name' => null,
            'distance_km' => 0.0,
        ];
    }

    private function haversineKm(float $fromLat, float $fromLon, float $toLat, float $toLon): float
    {
        $lat1 = deg2rad($fromLat);
        $lat2 = deg2rad($toLat);
        $deltaLat = deg2rad($toLat - $fromLat);
        $deltaLon = deg2rad($toLon - $fromLon);
        $a = sin($deltaLat / 2) ** 2
            + cos($lat1) * cos($lat2) * sin($deltaLon / 2) ** 2;

        return 6371.0088 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function flightLabel(array $route): string
    {
        if ($route['origin'] === null || $route['destination'] === null) {
            return 'Perjalanan pesawat';
        }

        $formattedDistance = number_format($route['distance_km'], 2, ',', '.');

        return "Penerbangan dinas {$route['origin']} ke {$route['destination']} ({$formattedDistance} km)";
    }
}
