<?php

namespace Database\Seeders;

use App\Models\FinancialAccount;
use App\Models\LedgerAccount;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['code' => '101', 'name' => 'صندوق و بانک', 'type' => 'asset', 'description' => 'موجودی نقد، بانک‌ها و کارت‌خوان'],
            ['code' => '102', 'name' => 'حساب‌های دریافتنی تجاری', 'type' => 'asset', 'description' => 'بدهی مشتریان و طرف‌های حساب'],
            ['code' => '103', 'name' => 'موجودی کالا', 'type' => 'asset', 'description' => 'ارزش دفتری کالاهای موجود در انبار'],
            ['code' => '201', 'name' => 'حساب‌های پرداختنی تجاری', 'type' => 'liability', 'description' => 'بستانکاری تأمین‌کنندگان'],
            ['code' => '301', 'name' => 'تراز افتتاحیه و سرمایه', 'type' => 'equity', 'description' => 'سرمایه اولیه و مانده افتتاحیه'],
            ['code' => '401', 'name' => 'فروش کالا', 'type' => 'revenue', 'description' => 'درآمد حاصل از فروش کالا و گوشی'],
            ['code' => '402', 'name' => 'درآمد خدمات', 'type' => 'revenue', 'description' => 'درآمد حاصل از ارائه خدمات نرم‌افزاری و راه‌اندازی'],
            ['code' => '403', 'name' => 'برگشت از فروش و تخفیفات', 'type' => 'revenue', 'description' => 'کاهنده درآمد ناشی از مرجوعی کالا'],
            ['code' => '501', 'name' => 'بهای تمام‌شده کالای فروش‌رفته', 'type' => 'expense', 'description' => 'ارزش خرید کالاهای فروخته‌شده (COGS)'],
            ['code' => '502', 'name' => 'اختلاف قیمت مرجوعی خرید', 'type' => 'expense', 'description' => 'تفاوت ارزش خروج کالا با مبلغ بستانکاری تأمین‌کننده در مرجوعی خرید'],
            ['code' => '601', 'name' => 'هزینه‌های جاری و اداری', 'type' => 'expense', 'description' => 'اجاره، حقوق، قبوض و هزینه‌های روزمره'],
            ['code' => '602', 'name' => 'تعدیل موجودی انبار', 'type' => 'expense', 'description' => 'کسری و اضافات ناشی از انبارگردانی یا تعدیل مستند'],
        ];

        foreach ($accounts as $data) {
            LedgerAccount::firstOrCreate(
                ['code' => $data['code']],
                $data
            );
        }

        // Create default cash and bank accounts linked to 101
        $cashAndBankLedger = LedgerAccount::where('code', '101')->first();
        if ($cashAndBankLedger) {
            FinancialAccount::firstOrCreate(
                ['name' => 'صندوق اصلی فروشگاه'],
                [
                    'type' => 'cash',
                    'ledger_account_id' => $cashAndBankLedger->id,
                    'is_active' => true,
                ]
            );

            FinancialAccount::firstOrCreate(
                ['name' => 'حساب بانک و کارت‌خوان'],
                [
                    'type' => 'pos',
                    'ledger_account_id' => $cashAndBankLedger->id,
                    'is_active' => true,
                ]
            );
        }
    }
}
