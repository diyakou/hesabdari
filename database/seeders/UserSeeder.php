<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'مدیر فروشگاه',
                'email' => 'admin@example.com',
                'password' => 'password',
                'role' => UserRole::Manager,
                'is_active' => true,
            ],
            [
                'name' => 'حسابدار فروشگاه',
                'email' => 'accountant@example.com',
                'password' => 'password',
                'role' => UserRole::Accountant,
                'is_active' => true,
            ],
            [
                'name' => 'فروشنده فروشگاه',
                'email' => 'sales@example.com',
                'password' => 'password',
                'role' => UserRole::Salesperson,
                'is_active' => true,
            ],
            [
                'name' => 'انباردار فروشگاه',
                'email' => 'warehouse@example.com',
                'password' => 'password',
                'role' => UserRole::WarehouseKeeper,
                'is_active' => true,
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }

        if (isset($this->command)) {
            $this->command->info('کاربران آزمایشی سیستم با نقش‌های مختلف با موفقیت ایجاد شدند:');
            $this->command->table(
                ['نام', 'ایمیل', 'نقش', 'رمز عبور'],
                collect($users)->map(fn ($u) => [
                    $u['name'],
                    $u['email'],
                    $u['role']->label(),
                    $u['password'],
                ])->all()
            );
        }
    }
}
