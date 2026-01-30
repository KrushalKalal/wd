<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Employee;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class HierarchySeeder extends Seeder
{
    public function run()
    {
        // 1️⃣ Roles
        $roles = [
            'Master Admin',
            'Country Head',
            'Zonal Head',
            'State Head',
            'City Head',
            'On/Off Trade Head',
            'Sales Employee'
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        echo "Roles created ✅\n";

        // 2️⃣ Master Admin
        $masterAdminUser = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Master Admin',
                'password' => Hash::make('123456'),
            ]
        );
        $masterAdminUser->assignRole('Master Admin');

        $masterAdmin = Employee::firstOrCreate(
            ['user_id' => $masterAdminUser->id],
            [
                'name' => 'Master Admin',
                'country' => 'India',
                'company_id' => null,
                'branch_id' => null,
                'store_id' => null,
                'dept_id' => null,
                'reporting_to' => null,
            ]
        );

        echo "Master Admin created ✅\n";

        // 3️⃣ Country Head
        $countryUser = User::firstOrCreate(
            ['email' => 'countryhead@gmail.com'],
            [
                'name' => 'Country Head',
                'password' => Hash::make('123456'),
            ]
        );
        $countryUser->assignRole('Country Head');

        $countryHead = Employee::firstOrCreate(
            ['user_id' => $countryUser->id],
            [
                'name' => 'Country Head',
                'reporting_to' => $masterAdmin->id,
                'country' => 'India',
            ]
        );

        // 4️⃣ Zonal Head
        $zonalUser = User::firstOrCreate(
            ['email' => 'zonalhead@gmail.com'],
            [
                'name' => 'Zonal Head',
                'password' => Hash::make('123456'),
            ]
        );
        $zonalUser->assignRole('Zonal Head');

        $zonalHead = Employee::firstOrCreate(
            ['user_id' => $zonalUser->id],
            [
                'name' => 'Zonal Head',
                'reporting_to' => $countryHead->id,
                'country' => 'India',
            ]
        );

        // 5️⃣ State Head
        $stateUser = User::firstOrCreate(
            ['email' => 'statehead@gmail.com'],
            [
                'name' => 'State Head',
                'password' => Hash::make('123456'),
            ]
        );
        $stateUser->assignRole('State Head');

        $stateHead = Employee::firstOrCreate(
            ['user_id' => $stateUser->id],
            [
                'name' => 'State Head',
                'reporting_to' => $zonalHead->id,
                'country' => 'India',
            ]
        );

        // 6️⃣ City Head
        $cityUser = User::firstOrCreate(
            ['email' => 'cityhead@gmail.com'],
            [
                'name' => 'City Head',
                'password' => Hash::make('123456'),
            ]
        );
        $cityUser->assignRole('City Head');

        $cityHead = Employee::firstOrCreate(
            ['user_id' => $cityUser->id],
            [
                'name' => 'City Head',
                'reporting_to' => $stateHead->id,
                'country' => 'India',
            ]
        );

        // 7️⃣ On/Off Trade Head
        $tradeUser = User::firstOrCreate(
            ['email' => 'tradehead@gmail.com'],
            [
                'name' => 'On/Off Trade Head',
                'password' => Hash::make('123456'),
            ]
        );
        $tradeUser->assignRole('On/Off Trade Head');

        $tradeHead = Employee::firstOrCreate(
            ['user_id' => $tradeUser->id],
            [
                'name' => 'On/Off Trade Head',
                'reporting_to' => $cityHead->id,
                'country' => 'India',
            ]
        );

        // 8️⃣ Sales Employee
        $salesUser = User::firstOrCreate(
            ['email' => 'sales1@gmail.com'],
            [
                'name' => 'Sales Employee 1',
                'password' => Hash::make('123456'),
            ]
        );
        $salesUser->assignRole('Sales Employee');

        Employee::firstOrCreate(
            ['user_id' => $salesUser->id],
            [
                'name' => 'Sales Employee 1',
                'reporting_to' => $tradeHead->id,
                'country' => 'India',
            ]
        );

        echo "Hierarchy seeded successfully ✅\n";
    }
}
