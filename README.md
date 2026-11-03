# Sistem Pasien Klinik Hewan

Aplikasi Sistem Pasien Klinik Hewan dibuat untuk memenuhi tugas UTS Praktikum Pemrograman Web.

## Teknologi
- Laravel
- PHP
- MySQL
- PDO
- Blade

## Fitur
- Menampilkan data pasien hewan
- Menambahkan data pasien hewan
- Mengubah data pasien hewan
- Menghapus data pasien hewan
- Pencarian berdasarkan kode pasien, nama hewan, dan nomor rekam
- Filter berdasarkan jenis hewan

## Database
Database: `klinik_hewan_2401020148`

Tabel:
- `jenis_hewan`
- `pasien_hewan`

File SQL tersedia di `database/klinik_hewan.sql`.

## Cara Menjalankan

1. Clone repository GitHub.
2. Masuk ke folder proyek.
3. Jalankan `composer install`.
4. Salin `.env.example` menjadi `.env`.
5. Sesuaikan konfigurasi database pada `.env`.
6. Import `database/klinik_hewan.sql` ke MySQL.
7. Jalankan `php artisan key:generate`.
8. Jalankan `php artisan config:clear`.
9. Jalankan `php artisan serve`.
10. Buka `http://127.0.0.1:8000/pasien-hewan`.