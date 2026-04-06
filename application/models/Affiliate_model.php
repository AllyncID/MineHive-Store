<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Affiliate_model extends CI_Model {

    // --- Konstanta untuk Badge Tiers ---
    // Sekarang memiliki min_sales DAN min_transactions
    const TIERS = [
        'bronze' => [
            'name' => 'Bronze',
            'commission_rate' => 0.03, // 3%
            'min_sales' => 0,
            'min_transactions' => 0
        ],
        'gold' => [
            'name' => 'Gold',
            'commission_rate' => 0.05, // 5%
            'min_sales' => 2000000,
            'min_transactions' => 30 // Target 50 transaksi
        ],
        'platinum' => [
            'name' => 'Platinum',
            'commission_rate' => 0.07, // 7%
            'min_sales' => 5000000,
            'min_transactions' => 60 // Target 150 transaksi
        ]
    ];

    /**
     * Helper function untuk menentukan badge dan progres misi.
     * LOGIKA DIUBAH: Sekarang mengecek sales DAN transactions.
     * @param float $total_sales Total penjualan afiliasi.
     * @param int $total_transactions Total transaksi afiliasi.
     * @return array Informasi badge, komisi, dan misi.
     */
    public function get_badge_info($total_sales, $total_transactions) {
        $current_badge_key = 'bronze'; // Default

        // Cek Platinum (Harus memenuhi KEDUA syarat)
        if ($total_sales >= self::TIERS['platinum']['min_sales'] && $total_transactions >= self::TIERS['platinum']['min_transactions']) {
            $current_badge_key = 'platinum';
        } 
        // Cek Gold (Harus memenuhi KEDUA syarat)
        elseif ($total_sales >= self::TIERS['gold']['min_sales'] && $total_transactions >= self::TIERS['gold']['min_transactions']) {
            $current_badge_key = 'gold';
        }

        $badge_info = self::TIERS[$current_badge_key];
        $next_badge_key = null;
        $next_badge_info = null;

        if ($current_badge_key === 'bronze') {
            $next_badge_key = 'gold';
        } elseif ($current_badge_key === 'gold') {
            $next_badge_key = 'platinum';
        }

        $progress_data = [
            'badge' => $current_badge_key,
            'badge_name' => $badge_info['name'],
            'commission_rate' => $badge_info['commission_rate'],
            'next_badge_name' => null,
            'next_badge_goal_sales' => null,
            'next_badge_goal_transactions' => null,
            'progress_percent_sales' => 100,
            'progress_percent_transactions' => 100
        ];

        if ($next_badge_key) {
            $next_badge_info = self::TIERS[$next_badge_key];
            
            // Hitung progres untuk sales (Rp)
            $progress_sales = max(0, $total_sales);
            $goal_sales = $next_badge_info['min_sales'];
            $progress_percent_sales = ($goal_sales > 0) ? ($progress_sales / $goal_sales) * 100 : 0;
            
            // Hitung progres untuk transactions (Qty)
            $progress_transactions = max(0, $total_transactions);
            $goal_transactions = $next_badge_info['min_transactions'];
            $progress_percent_transactions = ($goal_transactions > 0) ? ($progress_transactions / $goal_transactions) * 100 : 0;

            // Masukkan data progres ke array
            $progress_data['next_badge_name'] = $next_badge_info['name'];
            $progress_data['next_badge_goal_sales'] = $goal_sales;
            $progress_data['next_badge_goal_transactions'] = $goal_transactions;
            // (FIX PERSENTASE) Ubah jadi (int)
            $progress_data['progress_percent_sales'] = min(100, (int)$progress_percent_sales);
            $progress_data['progress_percent_transactions'] = min(100, (int)$progress_percent_transactions);
        }

        return $progress_data;
    }

    /**
     * Mengambil data afiliasi berdasarkan ID dan menambahkan info badge.
     * @param int $id ID afiliasi.
     * @return object|null Data afiliasi dengan info badge.
     */
    public function get_affiliate_by_id($id) {
        $this->db->where('id', $id);
        $affiliate = $this->db->get('affiliates')->row();

        if ($affiliate) {
            // Tambahkan info badge dinamis ke objek affiliate
            // Kirim total_sales dan total_transactions ke helper
            $badge_data = $this->get_badge_info($affiliate->total_sales, $affiliate->total_transactions);
            foreach ($badge_data as $key => $value) {
                $affiliate->{$key} = $value;
            }
        }
        return $affiliate;
    }

    /**
     * Mengambil data afiliasi berdasarkan username Minecraft.
     * @param string $username Username Minecraft.
     * @return object|null Data afiliasi.
     */
    public function get_affiliate_by_username($username) {
        $this->db->where('minecraft_username', $username);
        return $this->db->get('affiliates')->row();
    }


    /**
     * Fungsi baru untuk mencatat penjualan, menghitung komisi, dan mengupdate progres misi.
     * LOGIKA DIUBAH: Sekarang meng-increment total_sales DAN total_transactions.
     * @param int $affiliate_id ID afiliasi.
     * @param float $sale_amount Jumlah total penjualan (grand_total).
     * @return array Berisi ['commission_amount' => ..., 'commission_rate' => ...]
     */
    public function log_affiliate_sale($affiliate_id, $sale_amount)
    {
        // 1. Ambil data afiliasi (SELECT)
        $this->db->where('id', $affiliate_id);
        $affiliate = $this->db->get('affiliates')->row();
    
        if (!$affiliate) {
            log_message('error', '[Affiliate Debug] log_affiliate_sale: Gagal menemukan afiliasi ID ' . $affiliate_id);
            return false;
        }
        
        log_message('error', '[Affiliate Debug] Data Awal Afiliasi ID ' . $affiliate_id . ': ' . json_encode($affiliate));
    
        // 2. Hitung komisi berdasarkan state SAAT INI
        $badge_info = $this->get_badge_info($affiliate->total_sales, $affiliate->total_transactions);
        $commission_rate = $badge_info['commission_rate'];
        $commission_amount = $sale_amount * $commission_rate;
        
        log_message('error', '[Affiliate Debug] Kalkulasi: Sale Amount=' . $sale_amount . ', Rate=' . $commission_rate . ', Commission Amount=' . $commission_amount);
    
        // 3. Hitung total BARU (untuk cek badge)
        $new_total_sales = (float)$affiliate->total_sales + (float)$sale_amount;
        $new_total_transactions = (int)$affiliate->total_transactions + 1;
    
        // 4. Hitung badge BARU
        $new_badge_info = $this->get_badge_info($new_total_sales, $new_total_transactions);
        $new_badge = $new_badge_info['badge'];
        
        log_message('error', '[Affiliate Debug] Data Baru: New Total Sales=' . $new_total_sales . ', New Total Trans=' . $new_total_transactions . ', New Badge=' . $new_badge);
    
        // 5. Lakukan UPDATE
        log_message('error', '[Affiliate Debug] Menjalankan Query 1 (HANYA Angka) via BYPASS MYSQLi...');
        
        // --- [PERBAIKAN FINAL v4: BYPASS CI] ---
        // Kita akan pakai koneksi mysqli murni, persis seperti tes_db.php
        
        // 1. Load config DB
        include(APPPATH . 'config/database.php');
        $db_config = $db['default'];
        
        // 2. Buat koneksi baru
        $conn = new mysqli($db_config['hostname'], $db_config['username'], $db_config['password'], $db_config['database']);
        if ($conn->connect_error) {
             log_message('error', '[Affiliate Debug] GAGAL BYPASS: ' . $conn->connect_error);
             return false; // Gagal bypass
        }
        log_message('error', '[Affiliate Debug] Bypass koneksi SUKSES.');

        // 3. Buat query SQL mentah
        $sql1 = "UPDATE affiliates 
                 SET 
                    wallet_balance = COALESCE(wallet_balance, 0) + " . (float)$commission_amount . ",
                    total_sales = COALESCE(total_sales, 0) + " . (float)$sale_amount . ",
                    total_transactions = COALESCE(total_transactions, 0) + 1
                 WHERE 
                    id = " . (int)$affiliate_id;

        log_message('error', '[Affiliate Debug] Menjalankan Bypass SQL: ' . $sql1);

        // 4. Jalankan query
        if ($conn->query($sql1) !== TRUE) {
             log_message('error', '[Affiliate Debug] GAGAL BYPASS QUERY: ' . $conn->error);
        } else {
             log_message('error', '[Affiliate Debug] SUKSES BYPASS QUERY.');
        }
        
        // 5. Tutup koneksi bypass
        $conn->close();
        // --- [AKHIR PERBAIKAN] ---
        
        log_message('error', '[Affiliate Debug] Selesai Query 1 (Bypass). Menjalankan Query 2 (HANYA Badge) via CI...');

        // Query 2: HANYA update badge (pakai Active Record tidak masalah)
        $this->db->where('id', $affiliate_id);
        $this->db->set('badge', $new_badge);
        $this->db->update('affiliates');
        
        log_message('error', '[Affiliate Debug] Selesai menjalankan Query 2.');
    
        // 6. Return hasil
        return [
            'commission_amount' => $commission_amount,
            'commission_rate' => $badge_info['commission_rate']
        ];
    }

    // --- Fungsi yang Sudah Ada (Modifikasi) ---

    public function get_all_affiliates() {
        // Tambahkan 'badge', 'total_sales', dan 'total_transactions' ke select
        $this->db->select('id, minecraft_username, email, wallet_balance, is_active, badge, total_sales, total_transactions');
        return $this->db->get('affiliates')->result();
    }

    public function insert_affiliate($data) {
        // Saat menambah afiliasi baru, pastikan badge default 'bronze' dan total 0
        $data['badge'] = 'bronze';
        $data['total_sales'] = 0;
        $data['total_transactions'] = 0; // Set default 0
        return $this->db->insert('affiliates', $data);
    }
    
    // --- Fungsi yang Sudah Ada (Tidak Berubah) ---

    
    public function get_all_affiliate_codes() {
        $this->db->select('ac.*, a.minecraft_username');
        $this->db->from('affiliate_codes as ac');
        $this->db->join('affiliates as a', 'ac.affiliate_id = a.id');
        return $this->db->get()->result();
    }
    public function insert_affiliate_code($data) {
        // Kolom 'affiliate_commission_percentage' di 'affiliate_codes' sudah tidak terpakai
        // tapi kita biarkan saja form mengirimnya agar tidak error.
        // Kita bisa mengabaikannya di sini.
        $data_to_insert = [
            'affiliate_id' => $data['affiliate_id'],
            'code' => $data['code'],
            'customer_discount_percentage' => $data['customer_discount_percentage'],
            // Kita tidak memasukkan 'affiliate_commission_percentage'
        ];
        return $this->db->insert('affiliate_codes', $data_to_insert);
    }

    public function validate_login($username, $password) {
        // =======================================================
        // --- SAKLAR SEMENTARA UNTUK DEBUGGING ---
        // Ubah menjadi 'false' untuk mengaktifkan kembali verifikasi hash yang aman (mode produksi)
        // Ubah menjadi 'true' untuk mematikan verifikasi hash (mode debug tidak aman)
        $mode_login_tanpa_hash = true; 
        // =======================================================
    
        $this->db->where('minecraft_username', $username);
        $user = $this->db->get('affiliates')->row();
    
        // Jika user tidak ditemukan, langsung hentikan.
        if (!$user) {
            return false;
        }
    
        // Jika mode debug "tanpa hash" aktif, gunakan perbandingan teks biasa
        if ($mode_login_tanpa_hash) {
            
            if ($password === $user->password) {
                // Login berhasil jika password teks biasa cocok.
                return $user; 
            }
    
        } 
        // Jika mode debug mati, gunakan verifikasi hash yang aman
        else {
    
            if (password_verify($password, $user->password)) {
                // Login berhasil jika hash cocok.
                return $user;
            }
            
        }
    
        // Jika semua pengecekan di atas gagal, kembalikan false.
        return false;
    }

    public function get_code_by_string($code) {
        $this->db->where('code', $code);
        $this->db->where('is_active', 1);
        return $this->db->get('affiliate_codes')->row();
    }

    public function add_to_wallet($affiliate_id, $amount) {
        $this->db->where('id', $affiliate_id);
        $this->db->set('wallet_balance', 'wallet_balance + ' . $this->db->escape($amount), FALSE);
        return $this->db->update('affiliates');
    }

    /**
     * Menambah jumlah pemakaian sebuah kode afiliasi.
     * @param string $code Kode afiliasi yang digunakan.
     */
    public function increment_code_usage($code) {
        $this->db->where('code', $code);
        $this->db->set('usage_count', 'usage_count+1', FALSE);
        return $this->db->update('affiliate_codes');
    }

    public function get_payout_methods($affiliate_id) {
        $this->db->where('affiliate_id', $affiliate_id);
        return $this->db->get('affiliate_payout_methods')->result();
    }

    public function add_payout_method($data) {
        return $this->db->insert('affiliate_payout_methods', $data);
    }

    public function delete_payout_method($method_id, $affiliate_id) {
        // Pengecekan affiliate_id penting agar staf tidak bisa menghapus metode milik orang lain
        $this->db->where('id', $method_id);
        $this->db->where('affiliate_id', $affiliate_id);
        return $this->db->delete('affiliate_payout_methods');
    }
    
    public function deduct_from_wallet($affiliate_id, $amount) {
        $this->db->where('id', $affiliate_id);
        $this->db->set('wallet_balance', 'wallet_balance - ' . $this->db->escape($amount), FALSE);
        return $this->db->update('affiliates');
    }

    public function request_withdrawal($data) {
        return $this->db->insert('affiliate_withdrawals', $data);
    }

    public function delete_affiliate($id) {
        $this->db->trans_start();
        // Hapus dulu semua kode yang dimiliki oleh afiliasi ini
        $this->db->delete('affiliate_codes', ['affiliate_id' => $id]);
        // Kemudian hapus data afiliasi itu sendiri
        $this->db->delete('affiliates', ['id' => $id]);
        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    public function log_activity($data) {
        // Data harus berisi: affiliate_id, type, amount, description
        return $this->db->insert('affiliate_activities', $data);
    }

    /**
     * Mengambil riwayat aktivitas terbaru untuk seorang afiliasi.
     * @param int $affiliate_id ID afiliasi.
     * @param int $limit Jumlah aktivitas yang ingin ditampilkan.
     * @return array Daftar aktivitas.
     */
    public function get_activities($affiliate_id, $limit = null) {
        // Memilih semua kolom, DAN kolom created_at yang sudah diubah jadi Unix Timestamp
        $this->db->select("*, UNIX_TIMESTAMP(created_at) as created_at_unix", FALSE);

        $this->db->where('affiliate_id', $affiliate_id);
        $this->db->order_by('created_at', 'DESC');

        if ($limit) {
            $this->db->limit($limit);
        }

        return $this->db->get('affiliate_activities')->result();
    }

    public function has_withdrawn_today($affiliate_id)
    {
        $this->db->where('affiliate_id', $affiliate_id);
        // Cek data dari 24 jam terakhir dari sekarang
        // Menggunakan INTERVAL 24 HOUR lebih presisi daripada INTERVAL 1 DAY
        $this->db->where('created_at >=', 'DATE_SUB(NOW(), INTERVAL 24 HOUR)', FALSE);

        // Kita cek di tabel permintaan penarikan
        $query = $this->db->get('affiliate_withdrawals');

        // Jika ditemukan ada baris data (lebih dari 0), berarti dia sudah menarik hari ini
        return $query->num_rows() > 0;
    }
    public function update_password($affiliate_id, $new_hashed_password) {
        $this->db->where('id', $affiliate_id);
        return $this->db->update('affiliates', ['password' => $new_hashed_password]);
    }
}

