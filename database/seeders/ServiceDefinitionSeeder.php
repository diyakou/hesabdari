<?php

namespace Database\Seeders;

use App\Models\ServiceDefinition;
use App\Models\ServiceFormVersion;
use Illuminate\Database\Seeder;

class ServiceDefinitionSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'name' => 'انتقال اطلاعات بین دو گوشی',
                'code' => 'data_transfer',
                'description' => 'انتقال کامل اطلاعات، تصاویر، مخاطبین و برنامه‌ها بین گوشی مبدأ و مقصد',
                'default_fee_rials' => 2500000,
                'schema' => [
                    [
                        'name' => 'source_device',
                        'label' => 'مدل گوشی مبدأ',
                        'type' => 'text',
                        'required' => true,
                    ],
                    ['name' => 'source_capacity', 'label' => 'ظرفیت گوشی مبدأ', 'type' => 'text', 'required' => false],
                    ['name' => 'source_imei', 'label' => 'IMEI / سریال گوشی مبدأ', 'type' => 'text', 'required' => false],
                    [
                        'name' => 'target_device',
                        'label' => 'مدل گوشی مقصد',
                        'type' => 'text',
                        'required' => true,
                    ],
                    ['name' => 'target_capacity', 'label' => 'ظرفیت گوشی مقصد', 'type' => 'text', 'required' => false],
                    ['name' => 'target_imei', 'label' => 'IMEI / سریال گوشی مقصد', 'type' => 'text', 'required' => false],
                    [
                        'name' => 'data_types',
                        'label' => 'موارد قابل انتقال',
                        'type' => 'checklist',
                        'required' => false,
                        'options' => ['مخاطبین', 'پیام‌ها', 'عکس‌ها و ویدئوها', 'برنامه‌ها', 'تنظیمات'],
                    ],
                    [
                        'name' => 'estimated_gb',
                        'label' => 'حجم تقریبی (گیگابایت)',
                        'type' => 'number',
                        'required' => false,
                    ],
                    ['name' => 'appearance', 'label' => 'وضعیت ظاهری هنگام پذیرش', 'type' => 'checklist', 'required' => false, 'options' => ['خط و خش', 'شکستگی', 'مشکل تاچ', 'خاموش تحویل شد']],
                    ['name' => 'estimated_duration', 'label' => 'زمان تقریبی تحویل', 'type' => 'select', 'required' => false, 'options' => ['۱ تا ۲ ساعت', '۲ تا ۴ ساعت', 'تا پایان روز', 'روز کاری بعد']],
                ],
            ],
            [
                'name' => 'تعمیرات سخت‌افزاری',
                'code' => 'hardware_repair',
                'description' => 'پذیرش دستگاه، ثبت ایراد و وضعیت ظاهری و ارجاع به تعمیرکار',
                'default_fee_rials' => 0,
                'schema' => [
                    ['name' => 'device_model', 'label' => 'مدل دستگاه', 'type' => 'text', 'required' => true],
                    ['name' => 'imei', 'label' => 'IMEI / شماره سریال', 'type' => 'text', 'required' => false],
                    ['name' => 'reported_issue', 'label' => 'ایراد اعلام‌شده مشتری', 'type' => 'textarea', 'required' => true],
                    ['name' => 'appearance', 'label' => 'وضعیت ظاهری هنگام پذیرش', 'type' => 'checklist', 'required' => false, 'options' => ['خط و خش', 'شکستگی', 'مشکل تاچ', 'خمیدگی', 'آب‌خوردگی', 'خاموش تحویل شد']],
                    ['name' => 'estimated_duration', 'label' => 'زمان تقریبی تحویل', 'type' => 'select', 'required' => false, 'options' => ['همان روز', '۱ تا ۲ روز کاری', '۳ تا ۵ روز کاری', 'پس از تأمین قطعه']],
                ],
            ],
            [
                'name' => 'ساخت Apple ID اختصاصی',
                'code' => 'apple_id',
                'description' => 'ساخت اپل آیدی بدون ذخیره‌سازی رمز عبور شخصی مشتری',
                'default_fee_rials' => 3000000,
                'schema' => [
                    [
                        'name' => 'email',
                        'label' => 'ایمیل شخصی مشتری',
                        'type' => 'text',
                        'required' => false,
                    ],
                    [
                        'name' => 'delivery_confirmed',
                        'label' => 'تأیید تحویل و ثبت به مشتری',
                        'type' => 'boolean',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'راه‌اندازی اولیه دستگاه',
                'code' => 'initial_setup',
                'description' => 'تنظیمات اولیه سیستم‌عامل، اکانت و زبان',
                'default_fee_rials' => 1500000,
                'schema' => [
                    [
                        'name' => 'device_model',
                        'label' => 'مدل گوشی',
                        'type' => 'text',
                        'required' => true,
                    ],
                    [
                        'name' => 'actions',
                        'label' => 'اقدامات انجام‌شده',
                        'type' => 'textarea',
                        'required' => false,
                    ],
                ],
            ],
            [
                'name' => 'نصب گلس و برچسب محافظ',
                'code' => 'glass_installation',
                'description' => 'اجرت نصب محافظ صفحه نمایش (بدون احتساب قیمت گلس مصرفی)',
                'default_fee_rials' => 500000,
                'schema' => [
                    [
                        'name' => 'phone_model',
                        'label' => 'مدل گوشی',
                        'type' => 'text',
                        'required' => true,
                    ],
                    [
                        'name' => 'glass_type',
                        'label' => 'نوع گلس مصرفی',
                        'type' => 'text',
                        'required' => false,
                    ],
                ],
            ],
        ];

        $repairFields = [
            ['name' => 'device_model', 'label' => 'برند و مدل دستگاه', 'type' => 'text', 'required' => true],
            ['name' => 'imei', 'label' => 'IMEI / شماره سریال', 'type' => 'text', 'required' => false],
            ['name' => 'reported_issue', 'label' => 'شرح ایراد اعلام‌شده', 'type' => 'textarea', 'required' => true],
            ['name' => 'appearance', 'label' => 'وضعیت ظاهری هنگام پذیرش', 'type' => 'checklist', 'required' => false, 'options' => ['خط و خش', 'شکستگی', 'مشکل تاچ', 'خمیدگی', 'آب‌خوردگی', 'خاموش تحویل شد']],
            ['name' => 'included_items', 'label' => 'اقلام همراه دستگاه', 'type' => 'checklist', 'required' => false, 'options' => ['سیم‌کارت', 'قاب', 'کابل', 'شارژر', 'کارت حافظه']],
            ['name' => 'estimated_duration', 'label' => 'زمان تقریبی تحویل', 'type' => 'select', 'required' => false, 'options' => ['همان روز', '۱ تا ۲ روز کاری', '۳ تا ۵ روز کاری', 'پس از تأمین قطعه']],
        ];

        $services = array_merge($services, [
            ['name' => 'تعویض ال‌سی‌دی و تاچ', 'code' => 'display_repair', 'category' => 'hardware', 'description' => 'تعویض نمایشگر، تاچ یا شیشه دستگاه', 'default_fee_rials' => 0, 'schema' => $repairFields],
            ['name' => 'تعویض باتری', 'code' => 'battery_replacement', 'category' => 'hardware', 'description' => 'تست سلامت و تعویض باتری دستگاه', 'default_fee_rials' => 0, 'schema' => array_merge($repairFields, [['name' => 'battery_health', 'label' => 'سلامت فعلی باتری', 'type' => 'number', 'required' => false]])],
            ['name' => 'تعمیر سوکت شارژ', 'code' => 'charging_port_repair', 'category' => 'hardware', 'description' => 'عیب‌یابی شارژ، تعویض سوکت یا فلت شارژ', 'default_fee_rials' => 0, 'schema' => $repairFields],
            ['name' => 'تعمیر برد و آب‌خوردگی', 'code' => 'board_repair', 'category' => 'hardware', 'description' => 'عیب‌یابی برد، خاموشی و آسیب ناشی از رطوبت', 'default_fee_rials' => 0, 'schema' => $repairFields],
            ['name' => 'تعمیر دوربین، اسپیکر و میکروفن', 'code' => 'component_repair', 'category' => 'hardware', 'description' => 'تعمیر یا تعویض قطعات صوتی و دوربین', 'default_fee_rials' => 0, 'schema' => $repairFields],
            ['name' => 'تعویض قاب، شاسی و درب پشت', 'code' => 'body_repair', 'category' => 'hardware', 'description' => 'تعمیر بدنه، شاسی، دکمه‌ها و درب پشت', 'default_fee_rials' => 0, 'schema' => $repairFields],
            ['name' => 'فلش و نصب سیستم‌عامل', 'code' => 'os_install', 'category' => 'software', 'description' => 'نصب یا بازیابی Android و iOS و رفع خطای بوت', 'default_fee_rials' => 2000000, 'schema' => $repairFields],
            ['name' => 'بازیابی و پشتیبان‌گیری اطلاعات', 'code' => 'data_recovery', 'category' => 'software', 'description' => 'بکاپ، بازیابی فایل‌ها و اطلاعات قابل دسترس', 'default_fee_rials' => 0, 'schema' => $repairFields],
            ['name' => 'رفع کندی، ویروس و تبلیغات', 'code' => 'software_cleanup', 'category' => 'software', 'description' => 'بهینه‌سازی، حذف بدافزار و رفع تبلیغات مزاحم', 'default_fee_rials' => 1500000, 'schema' => $repairFields],
            ['name' => 'نصب برنامه و تنظیم حساب‌ها', 'code' => 'app_setup', 'category' => 'software', 'description' => 'نصب برنامه، تنظیم ایمیل و حساب‌های کاربری بدون نگهداری رمز', 'default_fee_rials' => 1000000, 'schema' => $repairFields],
            ['name' => 'خدمات رجیستری و همتا', 'code' => 'registry_service', 'category' => 'software', 'description' => 'بررسی رجیستری، انتقال مالکیت و فعال‌سازی قانونی', 'default_fee_rials' => 1000000, 'schema' => $repairFields],
        ]);

        foreach ($services as $serviceData) {
            $category = $serviceData['category'] ?? ($serviceData['code'] === 'hardware_repair' ? 'hardware' : 'software');
            $service = ServiceDefinition::updateOrCreate(
                ['code' => $serviceData['code']],
                [
                    'name' => $serviceData['name'],
                    'category' => $category,
                    'description' => $serviceData['description'],
                    'default_fee_rials' => $serviceData['default_fee_rials'],
                    'is_active' => true,
                ]
            );

            ServiceFormVersion::updateOrCreate(
                [
                    'service_definition_id' => $service->id,
                    'version' => 1,
                ],
                [
                    'fields_schema' => $serviceData['schema'],
                ]
            );
        }
    }
}
