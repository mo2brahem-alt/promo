<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\PromoCode;
use App\Models\PromoCodeRedemption;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin',
                'phone' => '01010000000',
                'password' => 'password',
                'role' => User::ROLE_SUPER_ADMIN,
                'is_active' => true,
            ],
        );

        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'phone' => '01010000001',
                'password' => 'password',
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
            ],
        );

        User::updateOrCreate(
            ['email' => 'admin@promo.test'],
            [
                'name' => 'Promo Admin',
                'phone' => '01010000005',
                'password' => 'password',
                'role' => User::ROLE_SUPER_ADMIN,
                'is_active' => true,
            ],
        );

        $manager = User::updateOrCreate(
            ['email' => 'manager@example.com'],
            [
                'name' => 'Promo Manager',
                'phone' => '01010000002',
                'password' => 'password',
                'role' => User::ROLE_PROMO_MANAGER,
                'is_active' => true,
            ],
        );

        $seller = User::updateOrCreate(
            ['email' => 'seller@example.com'],
            [
                'name' => 'Seller User',
                'phone' => '01010000003',
                'password' => 'password',
                'role' => User::ROLE_SELLER,
                'is_active' => true,
            ],
        );

        $reports = User::updateOrCreate(
            ['email' => 'reports@example.com'],
            [
                'name' => 'Reports Manager',
                'phone' => '01010000004',
                'password' => 'password',
                'role' => User::ROLE_REPORTS_MANAGER,
                'is_active' => true,
            ],
        );

        $mainBranch = Branch::updateOrCreate(
            ['code' => 'MAIN'],
            ['name' => 'فرع رئيسي', 'city' => 'القاهرة', 'address' => 'المقر الرئيسي', 'is_active' => true],
        );

        $testBranch = Branch::updateOrCreate(
            ['code' => 'TEST'],
            ['name' => 'فرع تجريبي', 'city' => 'الجيزة', 'address' => 'منطقة تجريبية', 'is_active' => true],
        );

        $seller->branches()->sync([$testBranch->id]);

        $customers = collect([
            ['name' => 'عميل تجريبي 1', 'phone' => '01000000001', 'email' => 'customer1@example.com', 'city' => 'القاهرة'],
            ['name' => 'عميل تجريبي 2', 'phone' => '01000000002', 'email' => 'customer2@example.com', 'city' => 'الجيزة'],
            ['name' => 'عميل تجريبي 3', 'phone' => '01000000003', 'email' => 'customer3@example.com', 'city' => 'الإسكندرية'],
            ['name' => 'عميل تجريبي 4', 'phone' => '01000000004', 'email' => 'customer4@example.com', 'city' => 'القاهرة'],
            ['name' => 'عميل تجريبي 5', 'phone' => '01000000005', 'email' => 'customer5@example.com', 'city' => 'المنصورة'],
        ])->map(fn (array $data) => Customer::updateOrCreate(
            ['phone' => $data['phone']],
            $data + ['notes' => 'بيانات اختبار', 'is_active' => true, 'created_by' => $manager->id],
        ));

        $activePromo = PromoCode::updateOrCreate(
            ['code' => 'WELCOME10'],
            [
                'title' => 'كود ترحيبي نشط',
                'description' => 'كود تجريبي مربوط بعملاء وفرع تجريبي.',
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

        $expiredPromo = PromoCode::updateOrCreate(
            ['code' => 'EXPIRED15'],
            [
                'title' => 'كود منتهي',
                'description' => 'كود منتهي لاختبار رسائل الصلاحية.',
                'discount_type' => PromoCode::DISCOUNT_PERCENTAGE,
                'discount_value' => 15,
                'max_invoice_amount' => 3000,
                'max_discount_amount' => 250,
                'starts_at' => now()->subMonth(),
                'expires_at' => now()->subDay(),
                'max_total_uses' => 20,
                'max_uses_per_customer' => 1,
                'is_active' => true,
                'created_by' => $manager->id,
            ],
        );

        $inactivePromo = PromoCode::updateOrCreate(
            ['code' => 'INACTIVE20'],
            [
                'title' => 'كود غير نشط',
                'description' => 'كود غير نشط لاختبار الحالة.',
                'discount_type' => PromoCode::DISCOUNT_PERCENTAGE,
                'discount_value' => 20,
                'max_invoice_amount' => 4000,
                'max_discount_amount' => 500,
                'starts_at' => now()->subDay(),
                'expires_at' => now()->addMonth(),
                'max_total_uses' => 50,
                'max_uses_per_customer' => 1,
                'is_active' => false,
                'created_by' => $manager->id,
            ],
        );

        $activePromo->customers()->sync($customers->pluck('id')->all());
        $activePromo->branches()->sync([$testBranch->id]);
        $expiredPromo->customers()->sync([$customers->first()->id]);
        $expiredPromo->branches()->sync([$testBranch->id]);
        $inactivePromo->customers()->sync([$customers->last()->id]);
        $inactivePromo->branches()->sync([$testBranch->id]);

        PromoCodeRedemption::updateOrCreate(
            [
                'promo_code_id' => $activePromo->id,
                'customer_id' => $customers->first()->id,
                'invoice_number' => 'POS-1001',
            ],
            [
                'branch_id' => $testBranch->id,
                'seller_id' => $seller->id,
                'invoice_amount' => 1200,
                'discount_percentage' => 10,
                'discount_amount' => 120,
                'redeemed_at' => now(),
            ],
        );
    }
}
