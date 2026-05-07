<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        Admin::updateOrCreate(
            ['email' => 'admin@truepath.com'],
            [
                'name'         => 'Admin',
                'password'     => Hash::make('7#WpL4@zK!91'),
                'role'         => 'Admin',
                'is_active'    => 1,
                'is_deleted'   => 0,
                'created_date' => Carbon::now(),
                'updated_date' => Carbon::now(),
            ]
        );
    }
}