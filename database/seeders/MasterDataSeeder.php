<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cluster;
use App\Models\JobTitle;
use App\Models\Unit;
use App\Models\SubUnit;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        // Clusters
        $clusters = [
            'GROUND HANDLING',
            'OFFICE',
        ];

        foreach ($clusters as $cluster) {
            Cluster::firstOrCreate(['name' => $cluster]);
        }

        // Job Titles
        $jobTitles = [
            'PASSENGER HANDLING',
            'BAGGAGE HANDLING',
            'RAMP HANDLING',
            'CARGO HANDLING',
            'AIRCRAFT SERVICE',
            'SUPPORTING UNIT',
            'OFFICE / ADMINISTRATION',
        ];

        foreach ($jobTitles as $title) {
            JobTitle::firstOrCreate(['name' => $title]);
        }

        // Units
        $units = [
            'FLIGHT OPERATION',
            'RAMP HANDLING',
            'BAGGAGE HANDLING',
            'HEAD OFFICE',
            'PASSENGER HANDLING',
            'SUPPORTING / MANAGEMENT',
        ];

        foreach ($units as $unit) {
            Unit::firstOrCreate(['name' => $unit]);
        }

        // Sub Units
        $subUnits = [
            'PORTER APRON',
            'PORTER CARGO',
            'PORTER MAKE-UP',
            'AIRCRAFT INTERIOR CLEANING',
            'DISPATCHER',
            'CONTROLLER',
            'DRIVER',
            'AVSEC',
            'RAMP',
            'PASASI',
            'QUALITY CONTROL',
            'HEALTH, SAFETY, AND ENVIRONMENT',
            'HEAD OF AIRPORT SERVICES',
            'HEAD STATION',
        ];

        foreach ($subUnits as $subUnit) {
            SubUnit::firstOrCreate(['name' => $subUnit]);
        }
    }
}
