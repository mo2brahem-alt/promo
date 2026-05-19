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

        $requestedBranches = [
            ['code' => 'MAIN', 'name' => 'الفرع الرئيسي', 'city' => 'القاهرة', 'address' => 'الفرع الرئيسي', 'email' => 'seller.main@promo.test', 'seller' => 'بائع الفرع الرئيسي', 'phone' => '01020000001'],
            ['code' => 'BR140T', 'name' => 'فرع 140ط', 'city' => 'القاهرة', 'address' => 'فرع 140ط', 'email' => 'seller.140t@promo.test', 'seller' => 'بائع فرع 140ط', 'phone' => '01020000002'],
            ['code' => 'ALEX', 'name' => 'فرع الاسكندرية', 'city' => 'الإسكندرية', 'address' => 'فرع الاسكندرية', 'email' => 'seller.alexandria@promo.test', 'seller' => 'بائع فرع الاسكندرية', 'phone' => '01020000003'],
            ['code' => 'HANOVIL', 'name' => 'فرع الهنوفيل', 'city' => 'الإسكندرية', 'address' => 'فرع الهنوفيل', 'email' => 'seller.hanovil@promo.test', 'seller' => 'بائع فرع الهنوفيل', 'phone' => '01020000004'],
            ['code' => 'TANTA', 'name' => 'فرع طنطا', 'city' => 'الغربية', 'address' => 'فرع طنطا', 'email' => 'seller.tanta@promo.test', 'seller' => 'بائع فرع طنطا', 'phone' => '01020000005'],
            ['code' => 'MANSOURA', 'name' => 'فرع المنصورة', 'city' => 'الدقهلية', 'address' => 'فرع المنصورة', 'email' => 'seller.mansoura@promo.test', 'seller' => 'بائع فرع المنصورة', 'phone' => '01020000006'],
            ['code' => 'DAMIETTA', 'name' => 'فرع دمياط', 'city' => 'دمياط', 'address' => 'فرع دمياط', 'email' => 'seller.damietta@promo.test', 'seller' => 'بائع فرع دمياط', 'phone' => '01020000007'],
            ['code' => 'ZAGAZIG', 'name' => 'فرع الزقازيق', 'city' => 'الشرقية', 'address' => 'فرع الزقازيق', 'email' => 'seller.zagazig@promo.test', 'seller' => 'بائع فرع الزقازيق', 'phone' => '01020000008'],
            ['code' => 'BENISUEF', 'name' => 'فرع بني سويف', 'city' => 'بني سويف', 'address' => 'فرع بني سويف', 'email' => 'seller.benisuef@promo.test', 'seller' => 'بائع فرع بني سويف', 'phone' => '01020000009'],
            ['code' => 'FAISAL', 'name' => 'فرع فيصل', 'city' => 'الجيزة', 'address' => 'فرع فيصل', 'email' => 'seller.faisal@promo.test', 'seller' => 'بائع فرع فيصل', 'phone' => '01020000010'],
            ['code' => 'MOHANDESIN', 'name' => 'فرع المهندسين', 'city' => 'الجيزة', 'address' => 'فرع المهندسين', 'email' => 'seller.mohandesin@promo.test', 'seller' => 'بائع فرع المهندسين', 'phone' => '01020000011'],
            ['code' => 'SELIM', 'name' => 'فرع سليم', 'city' => 'القاهرة', 'address' => 'فرع سليم', 'email' => 'seller.selim@promo.test', 'seller' => 'بائع فرع سليم', 'phone' => '01020000012'],
            ['code' => 'NOZHA', 'name' => 'فرع النزهة', 'city' => 'القاهرة', 'address' => 'فرع النزهة', 'email' => 'seller.nozha@promo.test', 'seller' => 'بائع فرع النزهة', 'phone' => '01020000013'],
            ['code' => 'MOKATTAM', 'name' => 'فرع المقطم', 'city' => 'القاهرة', 'address' => 'فرع المقطم', 'email' => 'seller.mokattam@promo.test', 'seller' => 'بائع فرع المقطم', 'phone' => '01020000014'],
            ['code' => 'ISMAILIA', 'name' => 'فرع الاسماعيلية', 'city' => 'الإسماعيلية', 'address' => 'فرع الاسماعيلية', 'email' => 'seller.ismailia@promo.test', 'seller' => 'بائع فرع الاسماعيلية', 'phone' => '01020000015'],
            ['code' => 'SHOUBRA', 'name' => 'فرع شبرا مصر', 'city' => 'القاهرة', 'address' => 'فرع شبرا مصر', 'email' => 'seller.shoubra@promo.test', 'seller' => 'بائع فرع شبرا مصر', 'phone' => '01020000016'],
            ['code' => 'OCTOBER', 'name' => 'فرع اكتوبر', 'city' => 'الجيزة', 'address' => 'فرع اكتوبر', 'email' => 'seller.october@promo.test', 'seller' => 'بائع فرع اكتوبر', 'phone' => '01020000017'],
        ];

        foreach ($requestedBranches as $branchData) {
            $branch = Branch::updateOrCreate(
                ['code' => $branchData['code']],
                [
                    'name' => $branchData['name'],
                    'city' => $branchData['city'],
                    'address' => $branchData['address'],
                    'is_active' => true,
                ],
            );

            $branchSeller = User::updateOrCreate(
                ['email' => $branchData['email']],
                [
                    'name' => $branchData['seller'],
                    'phone' => $branchData['phone'],
                    'password' => 'password',
                    'role' => User::ROLE_SELLER,
                    'is_active' => true,
                ],
            );

            $branchSeller->branches()->sync([$branch->id]);
        }

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
