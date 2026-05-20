# BahriCanli ExceptionMailer

Laravel 8 ve üzeri uygulamalarda raporlanan exception kayıtlarını e-posta olarak gönderen küçük bir paket.

## Kurulum

```bash
composer require bahricanli/exception-mailer
```

## Gereksinimler

- PHP 7.3 veya üzeri
- Laravel 8 veya üzeri

Laravel package discovery provider'ı otomatik yükler. Config dosyasını yayınlamak için:

```bash
php artisan vendor:publish --tag=exception-mailer-config
```

View dosyasını özelleştirmek için:

```bash
php artisan vendor:publish --tag=exception-mailer-views
```

## Konfigürasyon

`.env` içine en azından alıcı adreslerini ekleyin:

```dotenv
EXCEPTION_MAILER_TO=dev@example.com,ops@example.com
EXCEPTION_MAILER_ENVIRONMENTS=production
```

Yaygın ayarlar:

```dotenv
EXCEPTION_MAILER_ENABLED=true
EXCEPTION_MAILER_CC=
EXCEPTION_MAILER_BCC=
EXCEPTION_MAILER_SUBJECT="[{{ app }}] {{ exception }}"
EXCEPTION_MAILER_QUEUE=false
```

## Test Maili

```bash
php artisan exception-mailer:test --force
```

`--force`, local/test ortamında environment filtresini bypass ederek mail ayarlarını hızlıca doğrulamak içindir.

## Davranış

- Varsayılan olarak sadece `production` ortamında çalışır.
- 404, auth, validation, token mismatch ve model not found exception'larını göndermez.
- Request inputlarını mail'e ekler, hassas alanları maskeler.
- Mail gönderimi başarısız olursa uygulamayı düşürmez; hatayı loglar.

## Namespace

```php
BahriCanli\ExceptionMailer
```
