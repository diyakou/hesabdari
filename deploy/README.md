# دیپلوی روی `pnp.securecodehub.ir`

این نصب‌کننده برای **Ubuntu 22.04 و 24.04 LTS** آماده شده و Nginx، PHP 8.3، PostgreSQL،
صف Laravel، زمان‌بند systemd و HTTPS رایگان Let's Encrypt را پیکربندی می‌کند.

در Ubuntu 22.04، به‌دلیل اینکه نسخه رسمی PHP آن 8.1 است، مخزن `ondrej/php`
به‌صورت خودکار اضافه می‌شود تا PHP 8.3 موردنیاز Laravel 13 نصب شود.

## پیش‌نیاز

رکورد `A` دامنه `pnp.securecodehub.ir` باید به IP عمومی سرور اشاره کند و پورت‌های
`80` و `443` در فایروال باز باشند. اجرای اولیه باید با کاربر دارای دسترسی `sudo`
انجام شود.

## نصب از نسخه‌ای که روی سرور کپی شده است

از ریشه پروژه اجرا کنید:

```bash
sudo bash deploy/deploy.sh --email admin@securecodehub.ir
```

## نصب مستقیم از Git

```bash
curl -fsSLo /tmp/pnp-deploy.sh https://example.com/path/to/deploy.sh
sudo bash /tmp/pnp-deploy.sh \
  --email admin@securecodehub.ir \
  --repo https://github.com/OWNER/REPOSITORY.git \
  --branch main
```

برای مخزن خصوصی، بهتر است کلید deploy روی سرور تنظیم شود و آدرس SSH مخزن به
`--repo` داده شود. رمز PostgreSQL به‌صورت خودکار ساخته می‌شود و فقط در فایل
`/var/www/pnp.securecodehub.ir/.env` با دسترسی محدود ذخیره خواهد شد.

اگر DNS هنوز آماده نیست، نصب HTTP را انجام دهید و پس از اصلاح DNS همان دستور را
بدون `--no-ssl` دوباره اجرا کنید:

```bash
sudo bash deploy/deploy.sh --no-ssl
```

بعد از نصب، نخستین مدیر را به‌صورت تعاملی ایجاد کنید:

```bash
sudo -u pnp php /var/www/pnp.securecodehub.ir/artisan make:admin
```

اجرای دوباره اسکریپت، برنامه را به‌روزرسانی می‌کند؛ پیش از migration از دیتابیس
در پوشه `backups/` نسخه پشتیبان می‌گیرد. برای جلوگیری از اجرای seedها از گزینه
`--no-seed` استفاده کنید.
