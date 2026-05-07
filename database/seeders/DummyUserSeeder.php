<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DummyUserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name'                => 'John Smith',
                'email'               => 'john.smith@example.com',
                'phone'               => '9876543210',
                'country_code'        => '+1',
                'subscription_status' => 'premium',
                'is_verified'         => 1,
                'verified_at'         => Carbon::now()->subDays(30),
                'is_active'           => 1,
            ],
            [
                'name'                => 'Sarah Johnson',
                'email'               => 'sarah.johnson@example.com',
                'phone'               => '8765432109',
                'country_code'        => '+1',
                'subscription_status' => 'free',
                'is_verified'         => 1,
                'verified_at'         => Carbon::now()->subDays(25),
                'is_active'           => 1,
            ],
            [
                'name'                => 'Michael Brown',
                'email'               => 'michael.brown@example.com',
                'phone'               => '7654321098',
                'country_code'        => '+44',
                'subscription_status' => 'premium',
                'is_verified'         => 0,
                'verified_at'         => null,
                'is_active'           => 1,
            ],
            [
                'name'                => 'Emily Davis',
                'email'               => 'emily.davis@example.com',
                'phone'               => '6543210987',
                'country_code'        => '+91',
                'subscription_status' => 'free',
                'is_verified'         => 0,
                'verified_at'         => null,
                'is_active'           => 1,
            ],
            [
                'name'                => 'David Wilson',
                'email'               => 'david.wilson@example.com',
                'phone'               => '5432109876',
                'country_code'        => '+1',
                'subscription_status' => 'premium',
                'is_verified'         => 1,
                'verified_at'         => Carbon::now()->subDays(15),
                'is_active'           => 0,
            ],
            [
                'name'                => 'Jessica Taylor',
                'email'               => 'jessica.taylor@example.com',
                'phone'               => '4321098765',
                'country_code'        => '+44',
                'subscription_status' => 'free',
                'is_verified'         => 0,
                'verified_at'         => null,
                'is_active'           => 1,
            ],
            [
                'name'                => 'Robert Martinez',
                'email'               => 'robert.martinez@example.com',
                'phone'               => '3210987654',
                'country_code'        => '+1',
                'subscription_status' => 'premium',
                'is_verified'         => 1,
                'verified_at'         => Carbon::now()->subDays(10),
                'is_active'           => 1,
            ],
            [
                'name'                => 'Amanda Garcia',
                'email'               => 'amanda.garcia@example.com',
                'phone'               => '2109876543',
                'country_code'        => '+91',
                'subscription_status' => 'free',
                'is_verified'         => 0,
                'verified_at'         => null,
                'is_active'           => 0,
            ],
            [
                'name'                => 'Chris Anderson',
                'email'               => 'chris.anderson@example.com',
                'phone'               => '1098765432',
                'country_code'        => '+61',
                'subscription_status' => 'premium',
                'is_verified'         => 1,
                'verified_at'         => Carbon::now()->subDays(5),
                'is_active'           => 1,
            ],
            [
                'name'                => 'Laura Thomas',
                'email'               => 'laura.thomas@example.com',
                'phone'               => '9087654321',
                'country_code'        => '+44',
                'subscription_status' => 'free',
                'is_verified'         => 0,
                'verified_at'         => null,
                'is_active'           => 1,
            ],
        ];

        foreach ($users as $i => $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                array_merge($userData, [
                    'password'     => Hash::make('User@12345'),
                    'is_deleted'   => 0,
                    'role'         => null,
                    'created_date' => Carbon::now()->subDays(30 - $i),
                    'updated_date' => Carbon::now(),
                ])
            );
        }
    }
}
