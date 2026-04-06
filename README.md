# MineHive Store

Selamat datang di repositori MineHive Store! Ini adalah platform e-commerce yang didedikasikan untuk server Minecraft MineHive, memungkinkan pemain untuk membeli berbagai item, rank, dan layanan lainnya untuk meningkatkan pengalaman bermain mereka.

## Situs Live

Anda dapat mengunjungi toko kami yang sedang berjalan di:
[store.minehive.id](https://store.minehive.id)

## Fitur Utama (Contoh)

*   Manajemen Produk dan Kategori
*   Sistem Pembayaran Terintegrasi (Midtrans, Xendit, dll.)
*   Manajemen Pengguna dan Otentikasi
*   Sistem Afiliasi
*   Integrasi dengan Discord dan Pterodactyl (berdasarkan file yang terlihat)
*   Sistem Log dan Pelaporan

## Teknologi yang Digunakan

Proyek ini dibangun menggunakan:

*   **PHP**
*   **CodeIgniter** (berdasarkan struktur folder `application/`)
*   **Composer** untuk manajemen dependensi
*   **MySQL/MariaDB** (kemungkinan untuk database)

## Instalasi Lokal

Untuk menjalankan proyek ini secara lokal, ikuti langkah-langkah berikut:

1.  **Clone repositori:**
    ```bash
    git clone [URL_REPOSITORI_ANDA]
    cd MineHive12
    ```
2.  **Instal dependensi Composer:**
    ```bash
    composer install
    ```
3.  **Konfigurasi Database:**
    *   Buat database MySQL/MariaDB baru.
    *   Salin `application/config/database.php.example` (jika ada, atau buat file `database.php`) dan sesuaikan kredensial database Anda.
    *   Jalankan migrasi database (jika ada).
4.  **Konfigurasi Lingkungan:**
    *   Sesuaikan file konfigurasi di `application/config/` seperti `config.php`, `midtrans.php`, `xendit.php` dengan pengaturan lingkungan Anda.
5.  **Siapkan Web Server:**
    *   Konfigurasi Apache atau Nginx untuk mengarahkan ke folder `MineHive12/`.

## Kontribusi

Kami menyambut kontribusi! Jika Anda ingin berkontribusi, silakan fork repositori ini dan buat pull request dengan perubahan Anda.

## Lisensi

Proyek ini dilisensikan di bawah MIT License.
Copyright (c) 2026 Allync.
Lihat file `LICENSE.txt` untuk detail lebih lanjut.
