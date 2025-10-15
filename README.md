# Laravel Multitenant Single Database REST API
## Deskripsi

Proyek ini adalah contoh implementasi Laravel multitenant dengan menggunakan single database PostgreSQL. Dengan menggunakan teknologi REST API, proyek ini memungkinkan pengguna untuk membuat aplikasi dengan multiple tenant yang berbeda menggunakan single domain dan admin.

## Stack

* PHP 8.2+
* Laravel 12.x
* PostgreSQL 16.x+

## Fitur

* Single domain dan admin untuk semua tenant
* Multi subdomain untuk setiap tenant
* REST API sebagai interface
* Penggunaan PostgreSQL sebagai database

## Instalasi

1. Clone repository ini menggunakan perintah berikut:
```bash
composer create-project --prefer-dist laravel/laravel multitenant-api
```
2. Instal semua dependensi yang dibutuhkan dengan perintah berikut:
```bash
composer install
```
3. Copy file `.env.example` ke file `.env` dan ubah nilai yang diperlukan.
```markdown README/.env
{{ ubah nilai db dan auth sesuai dengan kebutuhan }}
```
4. Jalankan perintah berikut untuk migrasi database:
```bash
php artisan migrate
```
5. Jalankan perintah berikut untuk membuat tabel tenant:
```bash
php artisan db:seed
```

## Konfigurasi Valet
```bash
valet park
valet link
```
## Perintah Lainnya
```bash
php artisan migrate
php artisan db:seed
```
## Akses Login Super Admin (Landlord)
```bash
email : superadmin@mail.com
password : password
```
