<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\BellTiming;

class BellTimingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Winter Schedule (November to February)
        $winterSchedule = [
            ['name' => 'Morning Assembly', 'time' => '08:00', 'type' => 'start', 'order' => 1],
            ['name' => 'Period 1 Start', 'time' => '08:15', 'type' => 'start', 'order' => 2],
            ['name' => 'Period 1 End', 'time' => '09:00', 'type' => 'end', 'order' => 3],
            ['name' => 'Period 2 Start', 'time' => '09:00', 'type' => 'start', 'order' => 4],
            ['name' => 'Period 2 End', 'time' => '09:45', 'type' => 'end', 'order' => 5],
            ['name' => 'Recess Start', 'time' => '09:45', 'type' => 'break', 'order' => 6],
            ['name' => 'Recess End', 'time' => '10:15', 'type' => 'break', 'order' => 7],
            ['name' => 'Period 3 Start', 'time' => '10:15', 'type' => 'start', 'order' => 8],
            ['name' => 'Period 3 End', 'time' => '11:00', 'type' => 'end', 'order' => 9],
            ['name' => 'Period 4 Start', 'time' => '11:00', 'type' => 'start', 'order' => 10],
            ['name' => 'Period 4 End', 'time' => '11:45', 'type' => 'end', 'order' => 11],
            ['name' => 'Lunch Break Start', 'time' => '11:45', 'type' => 'break', 'order' => 12],
            ['name' => 'Lunch Break End', 'time' => '12:30', 'type' => 'break', 'order' => 13],
            ['name' => 'Period 5 Start', 'time' => '12:30', 'type' => 'start', 'order' => 14],
            ['name' => 'Period 5 End', 'time' => '13:15', 'type' => 'end', 'order' => 15],
            ['name' => 'Period 6 Start', 'time' => '13:15', 'type' => 'start', 'order' => 16],
            ['name' => 'School End', 'time' => '14:00', 'type' => 'end', 'order' => 17],
        ];

        // Summer Schedule (March to October)
        $summerSchedule = [
            ['name' => 'Morning Assembly', 'time' => '07:30', 'type' => 'start', 'order' => 1],
            ['name' => 'Period 1 Start', 'time' => '07:45', 'type' => 'start', 'order' => 2],
            ['name' => 'Period 1 End', 'time' => '08:30', 'type' => 'end', 'order' => 3],
            ['name' => 'Period 2 Start', 'time' => '08:30', 'type' => 'start', 'order' => 4],
            ['name' => 'Period 2 End', 'time' => '09:15', 'type' => 'end', 'order' => 5],
            ['name' => 'Recess Start', 'time' => '09:15', 'type' => 'break', 'order' => 6],
            ['name' => 'Recess End', 'time' => '09:45', 'type' => 'break', 'order' => 7],
            ['name' => 'Period 3 Start', 'time' => '09:45', 'type' => 'start', 'order' => 8],
            ['name' => 'Period 3 End', 'time' => '10:30', 'type' => 'end', 'order' => 9],
            ['name' => 'Period 4 Start', 'time' => '10:30', 'type' => 'start', 'order' => 10],
            ['name' => 'Period 4 End', 'time' => '11:15', 'type' => 'end', 'order' => 11],
            ['name' => 'Lunch Break Start', 'time' => '11:15', 'type' => 'break', 'order' => 12],
            ['name' => 'Lunch Break End', 'time' => '12:00', 'type' => 'break', 'order' => 13],
            ['name' => 'Period 5 Start', 'time' => '12:00', 'type' => 'start', 'order' => 14],
            ['name' => 'Period 5 End', 'time' => '12:45', 'type' => 'end', 'order' => 15],
            ['name' => 'Period 6 Start', 'time' => '12:45', 'type' => 'start', 'order' => 16],
            ['name' => 'School End', 'time' => '13:30', 'type' => 'end', 'order' => 17],
        ];

        // Insert winter schedule
        foreach ($winterSchedule as $bell) {
            BellTiming::create([
                'name' => $bell['name'],
                'time' => $bell['time'],
                'season' => 'winter',
                'type' => $bell['type'],
                'order' => $bell['order'],
                'is_active' => true,
                'description' => 'Default winter schedule'
            ]);
        }

        // Insert summer schedule
        foreach ($summerSchedule as $bell) {
            BellTiming::create([
                'name' => $bell['name'],
                'time' => $bell['time'],
                'season' => 'summer',
                'type' => $bell['type'],
                'order' => $bell['order'],
                'is_active' => true,
                'description' => 'Default summer schedule'
            ]);
        }
    }
}