<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cron extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Hanya izinkan script ini dijalankan melalui Command Line (CLI), bukan browser
        if (!$this->input->is_cli_request()) {
            show_error('Akses ditolak. Script ini hanya bisa dijalankan via Cron Job.', 403);
            exit();
        }
        
        // [BARU] Load model yang dibutuhkan untuk flash sale
        $this->load->model('Settings_model');
        $this->load->model('Product_model');
        $this->load->model('Flash_sale_model');
    }

    /**
     * [BARU] Fungsi Cron Job untuk Generate Flash Sale Harian
     * Jalankan ini sekali sehari (misal: jam 00:01).
     * Perintah Cron: /usr/bin/php /path/to/your/project/index.php cron generate_daily_flash_sale
     */
    public function generate_daily_flash_sale() {
        echo "Memulai proses Random Flash Sale...\n";
        
        // [BARU] Langkah 0: Bersihkan sale lama yang sudah kedaluwarsa
        echo "Membersihkan flash sale lama...\n";
        $deleted_count = $this->Flash_sale_model->delete_expired_sales();
        echo "$deleted_count flash sale kedaluwarsa telah dihapus.\n";
        // [AKHIR BARU]
        
        // 1. Ambil semua pengaturan
        $settings = $this->Settings_model->get_all_settings();
        
        // 2. Cek apakah fitur diaktifkan
        if (empty($settings['random_fs_enabled']) || $settings['random_fs_enabled'] == '0') {
            echo "Fitur Random Flash Sale Non-Aktif. Proses dihentikan.\n";
            return;
        }

        // 3. Cek apakah sudah ada flash sale (manual) yang sedang aktif
        $active_sales = $this->Flash_sale_model->get_active_flash_sales(1); // Cukup cek 1 saja
        if (count($active_sales) > 0) {
            echo "Ditemukan flash sale (manual) yang sedang aktif. Proses auto-generation dilewati.\n";
            return;
        }

        // 4. Ambil daftar produk yang boleh di-sale
        $excluded_ids = json_decode($settings['random_fs_excluded_products'] ?? '[]', true);
        $eligible_products = $this->Product_model->get_eligible_products_for_random_sale($excluded_ids);

        // [MODIFIKASI] Ambil jumlah sale yang diinginkan dari settings
        $sale_count = (int)($settings['random_fs_count'] ?? 3);

        if (empty($eligible_products)) {
            echo "Tidak ada produk yang memenuhi syarat untuk flash sale. Proses dihentikan.\n";
            return;
        }
        
        // [MODIFIKASI] Cek apakah jumlah produk cukup
        if (count($eligible_products) < $sale_count) {
            echo "Produk yang memenuhi syarat (" . count($eligible_products) . ") lebih sedikit dari jumlah yang diminta ($sale_count). Proses dihentikan.\n";
            return;
        }
        
        echo "Ditemukan " . count($eligible_products) . " produk yang memenuhi syarat. Memilih $sale_count produk...\n";

        // [MODIFIKASI] Pilih X produk secara acak (bukan cuma 1)
        shuffle($eligible_products); // Acak array produk
        $selected_products = array_slice($eligible_products, 0, $sale_count); // Ambil $sale_count produk pertama

        // 6. Siapkan data flash sale
        $min_discount = (int)($settings['random_fs_min_discount'] ?? 10);
        $max_discount = (int)($settings['random_fs_max_discount'] ?? 30);
        $min_duration = (int)($settings['random_fs_min_duration_hours'] ?? 3);
        $max_duration = (int)($settings['random_fs_max_duration_hours'] ?? 6);
        
        // Pastikan min tidak lebih besar dari max
        if ($min_discount > $max_discount) $min_discount = $max_discount;
        if ($min_duration > $max_duration) $min_duration = $max_duration;
        
        $sales_data_to_insert = [];
        $sales_data_for_discord = [];

        // [MODIFIKASI] Loop sebanyak produk yang dipilih
        foreach ($selected_products as $random_product) {
            $random_discount = rand($min_discount, $max_discount);
            $random_duration_hours = rand($min_duration, $max_duration);
            $end_date_time = date('Y-m-d H:i:s', strtotime("+$random_duration_hours hours"));

            // 7. Siapkan data untuk di-insert
            $data_to_save = [
                'product_id'          => $random_product->id,
                'discount_percentage' => $random_discount,
                'start_date'          => date('Y-m-d H:i:s'), // Mulai sekarang
                'end_date'            => $end_date_time,
                'stock_limit'         => 1000, // Stok default (bisa kamu tambahkan ke settings juga)
                'stock_sold'          => 0,
                'is_active'           => 1
            ];
            
            $sales_data_to_insert[] = $data_to_save;
            
            // Siapkan data untuk Discord
            $sales_data_for_discord[] = [
                'product' => $random_product,
                'discount' => $random_discount,
                'end_date' => $end_date_time
            ];

            echo "Produk dipilih: " . $random_product->name . " ($random_discount%)\n";
        }

        // 8. Simpan ke database menggunakan insert_batch
        if ($this->Flash_sale_model->insert_batch($sales_data_to_insert)) {
            echo "============================================\n";
            echo "SUKSES! $sale_count Random Flash Sale telah dibuat.\n";
            echo "============================================\n";

            // [MODIFIKASI] Kirim notifikasi Discord (1x untuk semua sale)
            $webhook_url = $settings['random_fs_webhook_url'] ?? null;
            if (!empty($webhook_url)) {
                echo "Mengirim notifikasi ke Discord...\n";
                $this->_send_flash_sale_webhook(
                    $webhook_url,
                    $sales_data_for_discord, // Kirim array berisi data sale
                    $sale_count
                );
            }
        } else {
            echo "GAGAL menyimpan flash sale ke database.\n";
        }
    }

    /**
     * [MODIFIKASI] Fungsi private untuk kirim notifikasi flash sale ke Discord
     * Sekarang menerima array $sales_data
     */
    private function _send_flash_sale_webhook($webhook_url, $sales_data, $sale_count) {
        
        $embed_fields = []; // Kita akan kumpulkan semua produk di sini
        
        $product_count = 0;
        foreach ($sales_data as $sale) {
            $product_count++;
            $product = $sale['product'];
            $discount_percentage = $sale['discount'];
            $end_date = $sale['end_date'];
            
            // Hitung harga baru
            $original_price = (float)$product->price;
            $new_price = $original_price - ($original_price * (int)$discount_percentage / 100);
            
            // Konversi waktu berakhir ke Unix timestamp (ini kuncinya)
            $end_timestamp = strtotime($end_date);
            $discord_relative_time = "<t:" . $end_timestamp . ":R>"; // Countdown
            
            // [OPSI 1: LEBIH RAPI - Judul Produk jadi Judul Field]
            $embed_fields[] = [
                'name' => 'Produk: ' . $product->name, // Judul field adalah nama produk
                'value' => "Diskon: **" . $discount_percentage . "% OFF**\n" .
                           "Harga: **Rp " . number_format($new_price, 0, ',', '.') . "** ~~Rp " . number_format($original_price, 0, ',', '.') . "~~\n" .
                           "Berakhir: " . $discord_relative_time,
                'inline' => false // Pastikan false agar tidak ke samping
            ];

            // Tambahkan pemisah JIKA ini bukan produk terakhir
            if ($product_count < $sale_count) {
                $embed_fields[] = [
                    'name' => "\u{200b}", // Spasi kosong
                    'value' => '━━━━━━━━━━━━━━', // Garis pemisah
                    'inline' => false
                ];
            }
        }

        /*
        // [OPSI 2: PAKE GAMBAR THUMBNAIL - HAPUS TANDA // JIKA MAU PAKAI INI]
        // Gantikan seluruh loop 'foreach' di atas dengan kode ini
        // Pastikan produkmu punya image_url yang bagus
        
        foreach ($sales_data as $sale) {
            $product = $sale['product'];
            $discount_percentage = $sale['discount'];
            $end_date = $sale['end_date'];
            
            $original_price = (float)$product->price;
            $new_price = $original_price - ($original_price * (int)$discount_percentage / 100);
            
            $end_timestamp = strtotime($end_date);
            $discord_relative_time = "<t:" . $end_timestamp . ":R>";

            // Judul produk
            $embed_fields[] = [
                'name' => 'Produk: ' . $product->name,
                'value' => "Diskon: **" . $discount_percentage . "% OFF**",
                'inline' => true
            ];
            
            // Harga
            $embed_fields[] = [
                'name' => 'Harga Sale',
                'value' => "**Rp " . number_format($new_price, 0, ',', '.') . "**\n~~Rp " . number_format($original_price, 0, ',', '.') . "~~",
                'inline' => true
            ];

            // Waktu
            $embed_fields[] = [
                'name' => 'Berakhir',
                'value' => $discord_relative_time,
                'inline' => true
            ];

            // Thumbnail (GAMBAR) - Pastikan $product->image_url ada isinya
            if (!empty($product->image_url)) {
                 $embed_fields[] = [
                    'name' => 'Gambar',
                    'value' => "[Lihat Gambar](" . $product->image_url . ")", // Link kosong
                    'inline' => true
                 ];
            }

             // Pemisah
             $embed_fields[] = [
                'name' => "\u{200b}", // Spasi kosong
                'value' => '━━━━━━━━━━━━━━', // Garis pemisah
                'inline' => false
            ];
        }
        // Hapus pemisah terakhir
        if (count($embed_fields) > 0) {
            array_pop($embed_fields);
        }
        */


        $embed = [
            'title' => "⚡ $sale_count FLASH SALE BARU TELAH DIMULAI! ⚡",
            'description' => "Jangan sampai ketinggalan, stok terbatas!",
            'color' => 15844367, // Warna emas/oranye
            'fields' => $embed_fields, // Masukkan semua field produk
            // 'thumbnail' dihapus karena ada banyak produk
            'footer' => [
                'text' => 'MineHive Store | Auto Flash Sale'
            ],
            'timestamp' => date('c') // Waktu pesan dikirim
        ];

        // Kirim cURL
        $json_data = json_encode(['embeds' => [$embed]]);
        
        $ch = curl_init($webhook_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-type: application/json']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code >= 200 && $http_code < 300) {
            echo "Notifikasi Discord berhasil terkirim.\n";
        } else {
            echo "GAGAL mengirim notifikasi Discord. Status: $http_code | Response: $response\n";
        }
    }


    /**
     * Fungsi untuk membersihkan file log lama.
     * @param int $retention_days Berapa hari log ingin disimpan.
     */
    public function cleanup_logs($retention_days = 2) {
        echo "Memulai proses pembersihan log...\n";

        // Tentukan path ke folder logs
        $log_path = APPPATH . 'logs/';

        // Tentukan batas waktu (semua file SEBELUM tanggal ini akan dihapus)
        $threshold = strtotime('-' . (int)$retention_days . ' days');

        $files_deleted = 0;

        // Loop semua file di dalam folder logs
        foreach (scandir($log_path) as $filename) {
            // Hanya proses file log-YYYY-MM-DD.php
            if (preg_match('/^log-(\d{4}-\d{2}-\d{2})\.php$/', $filename, $matches)) {
                $file_path = $log_path . $filename;
                $file_date_str = $matches[1];
                $file_timestamp = strtotime($file_date_str);

                // Jika waktu file lebih lama dari batas waktu, hapus file tersebut
                if ($file_timestamp < $threshold) {
                    if (unlink($file_path)) {
                        echo "Menghapus: " . $filename . "\n";
                        $files_deleted++;
                    } else {
                        echo "GAGAL menghapus: " . $filename . "\n";
                    }
                }
            }
        }

        echo "Proses selesai. Total file log lama yang dihapus: " . $files_deleted . "\n";
    }
}