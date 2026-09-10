<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\Region;
use App\Models\Street;
use App\Models\Ward;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use SplFileObject;

class AdministrativeHierarchySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = database_path('seeders/administrative-hierarchy.csv');

        if (! is_file($path)) {
            throw new RuntimeException("Administrative hierarchy file not found: {$path}");
        }

        $file = new SplFileObject($path);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);
        $headers = $file->fgetcsv();
        $regions = [];
        $districts = [];
        $wards = [];
        $streets = [];

        foreach ($file as $row) {
            if (! is_array($row) || count($row) !== count($headers)) {
                continue;
            }

            $record = array_combine($headers, $row);
            $regionCode = $this->clean($record['region_code'] ?? null);
            $districtCode = $this->clean($record['district_code'] ?? null);
            $wardCode = $this->clean($record['ward_code'] ?? null);
            $streetCode = $this->clean($record['village_mtaa_code'] ?? null);

            if ($regionCode !== null) {
                $regions[$regionCode] = ['code' => $regionCode, 'name' => $this->clean($record['region_name'])];
            }
            if ($districtCode !== null && $regionCode !== null) {
                $districts[$districtCode] = ['code' => $districtCode, 'name' => $this->clean($record['district_name']), 'region_code' => $regionCode];
            }
            if ($wardCode !== null && $districtCode !== null) {
                $wards[$wardCode] = ['code' => $wardCode, 'name' => $this->clean($record['ward_name']), 'district_code' => $districtCode];
            }
            if ($streetCode !== null && $wardCode !== null) {
                $streets[$streetCode] = ['code' => $streetCode, 'name' => $this->clean($record['village_mtaa_name']), 'ward_code' => $wardCode];
            }
        }

        DB::transaction(function () use ($regions, $districts, $wards, $streets): void {
            Region::query()->upsert(array_values($regions), ['code'], ['name']);
            $regionIds = Region::query()->whereIn('code', array_keys($regions))->pluck('id', 'code');
            $districtRows = collect($districts)->map(fn (array $district): array => ['code' => $district['code'], 'name' => $district['name'], 'region_id' => $regionIds[$district['region_code']]])->values()->all();
            District::query()->upsert($districtRows, ['code'], ['name', 'region_id']);
            $districtIds = District::query()->whereIn('code', array_keys($districts))->pluck('id', 'code');
            $wardRows = collect($wards)->map(fn (array $ward): array => ['code' => $ward['code'], 'name' => $ward['name'], 'district_id' => $districtIds[$ward['district_code']]])->values()->all();

            foreach (array_chunk($wardRows, 1000) as $chunk) {
                Ward::query()->upsert($chunk, ['code'], ['name', 'district_id']);
            }
            $wardIds = Ward::query()->whereIn('code', array_keys($wards))->pluck('id', 'code');
            $streetRows = collect($streets)->map(fn (array $street): array => ['code' => $street['code'], 'name' => $street['name'], 'ward_id' => $wardIds[$street['ward_code']]])->values()->all();

            foreach (array_chunk($streetRows, 1000) as $chunk) {
                Street::query()->upsert($chunk, ['code'], ['name', 'ward_id']);
            }
        });

        $this->command?->info(sprintf('Administrative hierarchy imported: %d regions, %d districts, %d wards and %d streets.', count($regions), count($districts), count($wards), count($streets)));
    }

    private function clean(mixed $value): ?string
    {
        $cleaned = trim(mb_convert_encoding((string) $value, 'UTF-8', 'UTF-8, Windows-1252, ISO-8859-1'));

        return $cleaned === '' || strtoupper($cleaned) === 'NULL' ? null : $cleaned;
    }
}
