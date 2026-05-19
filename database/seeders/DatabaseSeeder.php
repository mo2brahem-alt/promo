<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\PromoCode;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@promo.test'],
            ['name' => 'Admin User', 'password' => 'password', 'role' => User::ROLE_ADMIN, 'is_active' => true],
        );

        $manager = User::updateOrCreate(
            ['email' => 'manager@promo.test'],
            ['name' => 'Promo Manager', 'password' => 'password', 'role' => User::ROLE_PROMO_MANAGER, 'is_active' => true],
        );

        $seller = User::updateOrCreate(
            ['email' => 'seller@promo.test'],
            ['name' => 'Seller User', 'password' => 'password', 'role' => User::ROLE_SELLER, 'is_active' => true],
        );

        $reports = User::updateOrCreate(
            ['email' => 'reports@promo.test'],
            ['name' => 'Reports Manager', 'password' => 'password', 'role' => User::ROLE_REPORTS_MANAGER, 'is_active' => true],
        );

        $branchA = Branch::updateOrCreate(
            ['code' => 'BR-CAIRO-01'],
            ['name' => 'فرع القاهرة', 'city' => 'القاهرة', 'address' => 'مدينة نصر', 'is_active' => true],
        );

        $branchB = Branch::updateOrCreate(
            ['code' => 'BR-GIZA-01'],
            ['name' => 'فرع الجيزة', 'city' => 'الجيزة', 'address' => 'الدقي', 'is_active' => true],
        );

        $seller->branches()->syncWithoutDetaching([$branchA->id]);

        $customerA = Customer::updateOrCreate(
            ['phone' => '01000000001'],
            ['name' => 'عميل تجريبي 1', 'email' => 'customer1@example.com', 'city' => 'القاهرة', 'notes' => 'عميل تجريبي', 'is_active' => true, 'created_by' => $manager->id],
        );

        $customerB = Customer::updateOrCreate(
            ['phone' => '01000000002'],
            ['name' => 'عميل تجريبي 2', 'email' => null, 'city' => 'الجيزة', 'notes' => null, 'is_active' => true, 'created_by' => $manager->id],
        );

        $promo = PromoCode::updateOrCreate(
            ['code' => 'WELCOME10'],
            [
                'title' => 'خصم ترحيبي',
                'description' => 'كود تجريبي لاختبار التفعيل من شاشة البائع.',
                'discount_type' => PromoCode::DISCOUNT_PERCENTAGE,
                'discount_value' => 10,
                'max_invoice_amount' => 5000,
                'max_discount_amount' => 300,
                'starts_at' => now()->subDay(),
                'expires_at' => now()->addMonth(),
                'max_total_uses' => 100,
                'max_uses_per_customer' => 1,
                'is_active' => true,
                'created_by' => $manager->id,
            ],
        );

        $promo->customers()->syncWithoutDetaching([$customerA->id, $customerB->id]);
        $promo->branches()->syncWithoutDetaching([$branchA->id]);
    }
}
