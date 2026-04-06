<?php
/**
 * ===================================================================
 * XENDIT WEBHOOK HANDLER - VERSI FINAL (28 JUNI 2025)
 * ===================================================================
 * - Menerima notifikasi dari berbagai jenis pembayaran Xendit.
 * - Memverifikasi token dengan aman (case-insensitive).
 * - Menjalankan command Pterodactyl dan mengirim notifikasi Discord.
 * - Dilengkapi dengan logging yang detail untuk debugging.
 */

// --- Bagian 0: Setup Logging untuk Debugging ---

// Definisikan nama file log.
$log_file = __DIR__ . '/webhook_debug_log.txt';
// Hapus log lama setiap kali ada request baru agar tidak bingung.
file_put_contents($log_file, "=== MEMULAI SESI DEBUG BARU ===\n\n");

/**
 * Fungsi helper untuk menulis log dengan timestamp.
 */
function write_log($message) {
    global $log_file;
    // @file_put_contents digunakan untuk menekan pesan error jika folder tidak bisa ditulisi.
    @file_put_contents($log_file, date('[Y-m-d H:i:s] ') . $message . "\n", FILE_APPEND);
}

write_log("Webhook diakses.");


// --- Bagian 1: Memuat Framework CodeIgniter ---
try {
    define('BASEPATH', __DIR__ . '/system/');
    define('APPPATH', __DIR__ . '/application/');
    define('VIEWPATH', APPPATH . 'views/');
    define('ENVIRONMENT', 'production');

    require_once BASEPATH . 'core/CodeIgniter.php';
    write_log("SUKSES: Framework CodeIgniter berhasil dimuat.");
} catch (Exception $e) {
    write_log("FATAL ERROR saat memuat CodeIgniter: " . $e->getMessage());
    http_response_code(500);
    exit;
}


// --- Bagian 2: Logika Inti Webhook ---
$CI =& get_instance();
write_log("SUKSES: Instance CodeIgniter didapatkan.");

$CI->load->model('Store_model');
$CI->load->model('Transaction_model');
$CI->load->model('Promo_model');
$CI->load->model('Affiliate_model');
$CI->load->model('Settings_model'); // [TAMBAHAN BARU]
$CI->load->model('Scratch_event_model'); // [TAMBAHAN BARU]
$CI->load->model('Bucks_kaget_model');
$CI->config->load('xendit', TRUE);
write_log("SUKSES: Semua Model dan Config berhasil di-load.");

// 1. Verifikasi Keamanan Token Webhook (Versi Final Case-Insensitive)
$xendit_webhook_token = $CI->config->item('xendit_webhook_token', 'xendit');
$request_headers = getallheaders();
// Ubah semua nama header (keys) menjadi huruf kecil
$request_headers = array_change_key_case($request_headers, CASE_LOWER); 
// Sekarang, pencarian 'x-callback-token' pasti akan berhasil
$x_callback_token = $request_headers['x-callback-token'] ?? '';

write_log("DEBUG: Token dari Config -> [" . $xendit_webhook_token . "]");
write_log("DEBUG: Token dari Header -> [" . $x_callback_token . "]");

if (trim($x_callback_token) !== trim($xendit_webhook_token)) {
    write_log("GAGAL: Callback token tidak valid.");
    http_response_code(403);
    exit;
}
write_log("SUKSES: Callback token berhasil diverifikasi.");


// 2. Ambil data JSON dari Xendit
$request_body = file_get_contents('php://input');
$data = json_decode($request_body, true);
write_log("INFO: Data JSON diterima -> " . $request_body);


// 3. Logika Hybrid: Tangani semua jenis notifikasi pembayaran
$data_to_process = null;

// Cek #1: Apakah ini webhook tipe INVOICE? (Memiliki 'event')
if (isset($data['event']) && $data['event'] == 'invoice.paid') {
    write_log("Tipe webhook terdeteksi: INVOICE");
    if (isset($data['data']['status']) && $data['data']['status'] == 'PAID') {
        $data_to_process = $data['data']; // Data yang akan kita proses ada di dalam objek 'data'
    }
}
// Cek #2: Jika bukan, apakah ini webhook tipe PAYMENT (VA, QRIS, eWallet)? (Langsung ada 'status')
elseif (isset($data['status']) && $data['status'] == 'PAID') {
    write_log("Tipe webhook terdeteksi: PAYMENT (VA/QRIS/eWallet)");
    $data_to_process = $data; // Data yang akan kita proses adalah data itu sendiri
}

// 4. Jika ada data yang valid untuk diproses, jalankan semua logika inti
if ($data_to_process) {
    
    $external_id_parts = explode('-', $data_to_process['external_id']);
    $transaction_id = $external_id_parts[2] ?? null; 

    if ($transaction_id) {
        $transaction = $CI->Transaction_model->get_transaction_by_id($transaction_id);

        if ($transaction && $transaction['status'] == 'pending') {
            
            write_log("INFO: Memproses transaksi ID " . $transaction_id . '...');
            
            // [PERBAIKAN] Ambil UUID pembeli DULU
            $pembeli_uuid = $transaction['player_uuid']; 
            
            // [PERBAIKAN] Cek status pembelian pertama SEBELUM update status
            write_log("BONUS: Memeriksa bonus pembelian pertama (sebelum update status)...");
            $transaction_count = $CI->Transaction_model->get_completed_transaction_count($pembeli_uuid);
            $is_first_time_buyer = ($transaction_count == 0); // 0 = ini yg pertama
            write_log("BONUS: Status pembeli pertama: " . ($is_first_time_buyer ? 'YA' : 'TIDAK') . " (Count: $transaction_count)");


            $CI->db->trans_start();

            // A. Update status transaksi
            // [PERBAIKAN] Panggil update_transaction_status dari Transaction_model
            // Kita belum tahu jumlah komisi, jadi kirim null
            $CI->Transaction_model->update_transaction_status($transaction_id, 'completed', null);
            
            // B. Ambil data dari DB
            $cart = json_decode($transaction['cart_data'], true);
            $penerima_username = $transaction['gift_recipient_username'] ?? $transaction['player_username'];
            $pembeli_username = $transaction['player_username'];
            // [MODIFIKASI] Ambil UUID pembeli untuk cek bonus (Sudah dipindah ke atas)
            // $pembeli_uuid = $transaction['player_uuid']; 

            // C. Kirim Perintah ke Pterodactyl
            // C. Kirim Perintah ke Pterodactyl
            write_log("PROSES: Memulai eksekusi command Pterodactyl...");
            $all_commands_success = true;
            $proxy_server_id = $CI->config->item('pterodactyl_server_id_proxy');
            $has_currency_purchase = false;
            $has_bucks_kaget_purchase = false;
            $clean_grand_total = (int) str_replace([".", ","], "", ($data_to_process['amount'] ?? 0));
            foreach ($cart['items'] as $item) {
                $product = $CI->Store_model->get_product_by_id($item['id']);
                if ($product) {
                    $is_bucks_kaget = $CI->Store_model->is_bucks_kaget_product($product);
                    if ($is_bucks_kaget) {
                        $has_bucks_kaget_purchase = true;
                        write_log(" > INFO: Item Bucks Kaget terdeteksi. Pembuatan link akan diproses setelah pembayaran tervalidasi.");
                        continue;
                    }

                    // Deteksi currency: command produk currency selalu dieksekusi di Proxy (global)
                    $product_type = strtolower(trim($product['product_type'] ?? ''));
                    $product_category = strtolower(trim($product['category'] ?? ''));
                    $is_currency = ($product_type === 'currency') || ($product_category === 'currency');
                    if ($is_currency) {
                        $has_currency_purchase = true;
                    }

                    $server_id = null;
                    $target_label = null;

                    if ($is_currency) {
                        $server_id = $proxy_server_id;
                        $target_label = 'Proxy';

                        if (empty($server_id)) {
                            write_log(" > ERROR: pterodactyl_server_id_proxy belum di-set di config. Loop dihentikan.");
                            $all_commands_success = false;
                            break;
                        }
                    } else {
                        // [FIX] Prioritaskan realm dari item keranjang jika ada, jika tidak, baru pakai default dari produk
                        $realm_to_use = isset($item['realm']) ? trim($item['realm']) : trim($product['realm']);
                        $target_label = $realm_to_use !== '' ? $realm_to_use : 'UNKNOWN';
                        $config_key_name = 'pterodactyl_server_id_' . strtolower($realm_to_use);
                        $server_id = $CI->config->item($config_key_name);

                        if (empty($server_id)) {
                            write_log(" > PERINGATAN: Server ID untuk realm '" . $target_label . "' tidak ditemukan di config.");
                            continue;
                        }
                    }

                        // [MODIFIKASI: HANDLER QUANTITY STACKING]
                        $qty = isset($item['quantity']) ? (int)$item['quantity'] : 1;
                        $command_template = $product['command'];

                        // Cek apakah command support {quantity} (contoh: Battlepass)
                        if (strpos($command_template, '{quantity}') !== false) {
                            // Kirim 1 kali dengan jumlah quantity
                            $command = str_replace(
                                ['{username}', '{grand_total}', '{quantity}'], 
                                [$penerima_username, $clean_grand_total, $qty], 
                                $command_template
                            );
                            
                            write_log(" > [BULK] Mengirim command: '" . $command . "' ke server: " . $target_label . " (ID: " . $server_id . ")");
                            
                            if (!send_pterodactyl_command($CI, $command, $server_id)) {
                                $all_commands_success = false; break;
                            }
                        } else {
                            // Jika command tidak support {quantity}, loop sebanyak quantity
                            for ($i = 0; $i < $qty; $i++) {
                                $command = str_replace(
                                    ['{username}', '{grand_total}', '{quantity}'], 
                                    [$penerima_username, $clean_grand_total, 1], // quantity 1 per loop
                                    $command_template
                                );
                                
                                write_log(" > [LOOP " . ($i+1) . "/$qty] Mengirim command: '" . $command . "' ke server: " . $target_label . " (ID: " . $server_id . ")");

                                if (!send_pterodactyl_command($CI, $command, $server_id)) {
                                    $all_commands_success = false; break 2; // Break loop luar juga
                                }
                            }
                        }
                }
            }

            // [BARU] Khusus pembelian currency: eksekusi `donations add <grand_total>` di semua realm donation
            if ($all_commands_success && $has_currency_purchase) {
                // Catatan: donations add ini bersifat NON-BLOCKING (tidak boleh menggagalkan proses utama),
                // supaya notifikasi Discord + proses lain tetap jalan walaupun salah satu realm sedang down.
                $donations_success = true;
                $donation_command = 'donations add ' . (int) $clean_grand_total;
                foreach (['survival', 'skyblock', 'oneblock'] as $donation_realm) {
                    $donation_server_id = $CI->config->item('pterodactyl_server_id_' . $donation_realm);
                    if (empty($donation_server_id)) {
                        write_log(" > WARNING: Server ID untuk " . $donation_realm . " tidak ditemukan. Donations dilewati (non-blocking).");
                        $donations_success = false;
                        continue;
                    }

                    write_log(" > Mengirim command donations (" . $donation_realm . "): '" . $donation_command . "' (ID: " . $donation_server_id . ")");
                    if (!send_pterodactyl_command($CI, $donation_command, $donation_server_id)) {
                        write_log(" > WARNING: Gagal eksekusi donations di " . $donation_realm . ". Dilanjutkan (non-blocking).");
                        $donations_success = false;
                        continue;
                    }
                }
                write_log("PROSES: Donations command selesai. Status sukses: " . ($donations_success ? 'Ya' : 'Tidak'));
            }

            if ($all_commands_success && $has_bucks_kaget_purchase) {
                write_log("PROSES: Memulai pembuatan campaign Bucks Kaget...");
                $bucks_kaget_result = create_bucks_kaget_campaign_for_transaction($CI, $transaction_id, $transaction, $cart);

                if (!$bucks_kaget_result['success']) {
                    write_log("ERROR: Gagal membuat campaign Bucks Kaget -> " . ($bucks_kaget_result['message'] ?? 'Unknown error'));
                    $all_commands_success = false;
                } else {
                    $cart = $bucks_kaget_result['cart'];
                    write_log("PROSES: Campaign Bucks Kaget berhasil dibuat. URL -> " . ($bucks_kaget_result['result']['url'] ?? '-'));
                }
            }
            write_log("PROSES: Eksekusi Pterodactyl selesai. Status sukses: " . ($all_commands_success ? 'Ya' : 'Tidak'));
            
            // D. Proses lain jika Pterodactyl sukses
            if($all_commands_success) {
                
                // [MODIFIKASI] Cek Bonus Pembelian Pertama
                // [PERBAIKAN] Variabel $is_first_time_buyer sudah di-set di luar blok ini
                write_log("BONUS: Mengecek variabel \$is_first_time_buyer...");
                
                if ($is_first_time_buyer) {
                    // Ini adalah pembelian pertama!
                    write_log("BONUS: Terdeteksi Pembeli Pertama! UUID: " . $pembeli_uuid);
                    $CI->load->model('First_bonus_model'); // Load model
                    $bonus_commands = $CI->First_bonus_model->get_active_commands(); // Ambil command
                    write_log("BONUS: Ditemukan " . count($bonus_commands) . " command bonus aktif.");
                    
                    foreach ($bonus_commands as $bonus) {
                        // Asumsi bonus command HANYA berlaku untuk realm 'survival'
                        // (Sesuai logika dari controllers/Payment.php)
                        $server_id_bonus = $CI->config->item('pterodactyl_server_id_survival');
                        if (!empty($server_id_bonus)) {
                            $command_to_send = str_replace('{username}', $penerima_username, $bonus->reward_command);
                            write_log("BONUS: Mengirim Command Bonus: " . $command_to_send);
                            // Panggil fungsi lokal (tanpa underscore)
                            send_pterodactyl_command($CI, $command_to_send, $server_id_bonus);
                            // Kita tidak menghentikan proses jika bonus gagal
                        } else {
                            write_log("BONUS GAGAL: 'pterodactyl_server_id_survival' tidak di-set di config.");
                        }
                    }
                } else {
                    write_log("BONUS: Bukan pembeli pertama (berdasarkan pengecekan di awal).");
                }
                // [AKHIR MODIFIKASI BONUS]


                // [FITUR BARU] PROSES HADIAH GOSOK BERHADIAH
                write_log("[Scratch Event] Memulai proses cek hadiah gosok...");
                // Model Scratch_event_model dan Settings_model sudah di-load di atas
                
                $scratch_settings = $CI->Settings_model->get_all_settings();
                if (!empty($scratch_settings['scratch_event_enabled']) && $scratch_settings['scratch_event_enabled'] == '1') {
                    
                    write_log("[Scratch Event] Fitur AKIF. Cek tier untuk Grand Total: " . $transaction['grand_total']);
                    // Gunakan grand_total dari data transaksi di DB
                    $tier = $CI->Scratch_event_model->get_applicable_tier($transaction['grand_total']);

                    if ($tier) {
                        write_log("[Scratch Event] Customer masuk Tier: " . $tier->title);
                        $reward = $CI->Scratch_event_model->get_random_reward_for_tier($tier->id);

                        if ($reward) {
                            write_log("[Scratch Event] Customer memenangkan: " . $reward->display_name);

                            // 1. Eksekusi jika command
                            if ($reward->reward_type == 'command') {
                                // Model Store_model sudah di-load di atas
                                
                                // Ambil item pertama dari keranjang
                                $first_item_id = array_key_first($cart['items']);
                                $first_item = $cart['items'][$first_item_id];
                                // get_product_by_id mengembalikan array di file webhook-mu
                                $product = $CI->Store_model->get_product_by_id($first_item['id']); 

                                if ($product) {
                                    // $product sudah array, tidak perlu cast
                                    $realm = trim((string) ($first_item['realm'] ?? $product['realm'] ?? ''));
                                    $config_key_name = 'pterodactyl_server_id_' . strtolower($realm);
                                    $server_id = $CI->config->item($config_key_name);
                                    
                                    if ($server_id) {
                                        $command = str_replace('{username}', $penerima_username, $reward->reward_value);
                                        // Panggil fungsi send_pterodactyl_command() lokal
                                        send_pterodactyl_command($CI, $command, $server_id);
                                        write_log("[Scratch Event] EKSEKUSI COMMAND: " . $command . " di server " . $realm);
                                    } else {
                                        write_log("[Scratch Event] GAGAL EKSEKUSI: Server ID Pterodactyl untuk realm " . $realm . " tidak ditemukan.");
                                    }
                                } else {
                                     write_log("[Scratch Event] GAGAL EKSEKUSI: Tidak bisa menemukan data produk untuk item pertama di keranjang.");
                                }
                            }

                            // 2. Catat kemenangan (untuk SEMUA tipe hadiah)
                            $CI->Scratch_event_model->log_won_reward([
                                'transaction_id' => $transaction_id,
                                'player_uuid'    => $pembeli_uuid, // Gunakan UUID pembeli
                                'reward_bank_id' => $reward->id
                            ]);
                            write_log("[Scratch Event] Kemenangan dicatat di db untuk UUID: " . $pembeli_uuid);

                        } else {
                            write_log("[Scratch Event] Tier ditemukan, tapi GAGAL mengambil hadiah (Pool Hadiah mungkin kosong).");
                        }
                    } else {
                        write_log("[Scratch Event] Belanja customer (Rp " . $transaction['grand_total'] . ") tidak memenuhi syarat tier manapun.");
                    }
                } else {
                    write_log("[Scratch Event] Fitur NON-AKTIF. Proses dilewati.");
                }
                // [AKHIR FITUR BARU] GOSOK BERHADIAH


                write_log("PROSES: Memulai proses Afiliasi, Promo, dan Discord...");
                
                $commission_amount_to_log = 0; // Inisialisasi komisi

                // Cek apakah ada diskon yang dipakai dari data keranjang
                // [PERBAIKAN] Logika ini disesuaikan dengan struktur cart baru
                
                // 1. Cek Diskon Afiliasi
                if (isset($cart['applied_referral']) && $cart['referral_discount'] > 0) {
                    $referral_info = $cart['applied_referral'];
                    $affiliate_id = $referral_info['affiliate_id'];
                    
                    // Ambil data afiliasi LENGKAP untuk dapat rate komisi TERBARU
                    $affiliate_data = $CI->Affiliate_model->get_affiliate_by_id($affiliate_id);
                    
                    if ($affiliate_data) {
                        // Hitung komisi berdasarkan badge afiliasi saat ini
                        $commission_rate = $affiliate_data->commission_rate; // misal: 0.03
                        // [PERBAIKAN] Basis komisi adalah grand_total SEBELUM diskon referral
                        // (Subtotal - Diskon Keranjang - Diskon Promo)
                        $base_for_commission = $cart['subtotal'] - ($cart['cart_discount'] ?? 0) - ($cart['promo_discount'] ?? 0);
                        $commission_amount = $base_for_commission * $commission_rate;
                        $commission_amount_to_log = $commission_amount; // Simpan untuk log transaksi

                        write_log("AFILIASI: Menghitung komisi. Basis: {$base_for_commission}, Rate: {$commission_rate}, Komisi: {$commission_amount}");

                        if ($commission_amount > 0) {
                            // Panggil fungsi log_affiliate_sale yang sudah canggih
                            $CI->Affiliate_model->log_affiliate_sale($affiliate_id, $cart['grand_total']);
                            
                            // Catat aktivitas (untuk riwayat)
                            $CI->Affiliate_model->log_activity([
                                'affiliate_id' => $affiliate_id,
                                'type'         => 'commission',
                                'amount'       => $commission_amount,
                                'description'  => 'Komisi ' . ($commission_rate * 100) . '% dari ' . $pembeli_username . ' (Trx: #' . $transaction_id . ')'
                            ]);
                            write_log("AFILIASI: log_affiliate_sale & log_activity dipanggil untuk ID: {$affiliate_id}");
                        }
                        // Catat pemakaian kode
                        $CI->Affiliate_model->increment_code_usage($referral_info['code']);
                        write_log("AFILIASI: Mencatat pemakaian untuk kode: {$referral_info['code']}");
                    }
                } 
                // 2. Cek Diskon Promo (jika tidak ada diskon afiliasi)
                elseif (isset($cart['applied_promo']) && $cart['promo_discount'] > 0) {
                    $promo_info = $cart['applied_promo'];
                    $CI->Promo_model->increment_usage($promo_info['code']);
                    write_log("PROMO: Mencatat pemakaian untuk kode promo: {$promo_info['code']}");
                }

                // [PERBAIKAN] Update status transaksi LAGI untuk mencatat jumlah komisi
                $CI->Transaction_model->update_transaction_status($transaction_id, 'completed', $commission_amount_to_log);
                
                // Ambil ID Invoice dari data Xendit
                $invoice_id = $data_to_process['external_id'] ?? 'N/A';

                // Kirim ID tersebut ke fungsi Discord
                // [PERBAIKAN] Kirim $is_first_time_buyer ke Discord
                $discord_ok = send_to_discord_webhook($CI, $cart, $pembeli_username, ($transaction['is_gift'] ? $penerima_username : null), $invoice_id, $is_first_time_buyer);
                write_log("PROSES: Notifikasi Discord " . ($discord_ok ? 'berhasil dikirim.' : 'GAGAL dikirim.'));
                
                // =======================================================
                // === LOGIKA BONUS TOP UP (DARI FILE LAMA)            ===
                // =======================================================
                write_log("BONUS TOPUP: Memeriksa apakah transaksi layak mendapatkan bonus top up...");
                
                // Muat model bonus
                $CI->load->model('Bonus_model');
                
                // Ambil total belanja dari transaksi
                $grand_total = (float) $transaction['grand_total'];

                // Cari tingkatan bonus yang sesuai dengan total belanja
                $bonus_tier = $CI->Bonus_model->get_tier_for_amount($grand_total);

                if ($bonus_tier) {
                    write_log("BONUS TOPUP: Ditemukan bonus tier! Deskripsi: " . $bonus_tier->reward_description);

                    // --- Logika untuk menentukan realm target ---
                    $realm_totals = [];
                    // Loop item di keranjang untuk menghitung total belanja per realm
                    foreach ($cart['items'] as $item) {
                        $product_info = $CI->Store_model->get_product_by_id($item['id']);
                        if ($product_info) {
                            // Pastikan $product_info adalah array
                            $product_info_arr = (array) $product_info;
                            $realm_name = strtolower((string) ($item['realm'] ?? $product_info_arr['realm'] ?? ''));
                            if ($realm_name === '') {
                                continue;
                            }
                            if (!isset($realm_totals[$realm_name])) {
                                $realm_totals[$realm_name] = 0;
                            }
                            $realm_totals[$realm_name] += $item['price'];
                        }
                    }
                    
                    // Cari realm dengan total belanja tertinggi
                    if (!empty($realm_totals)) {
                        arsort($realm_totals); // Urutkan dari tertinggi ke terendah
                        $target_realm = key($realm_totals); // Ambil nama realm pertama (tertinggi)

                        write_log("BONUS TOPUP: Realm target untuk hadiah adalah: " . $target_realm);

                        // Ambil server ID untuk realm target
                        $server_id = $CI->config->item('pterodactyl_server_id_' . $target_realm);
                        if ($server_id) {
                            // Siapkan dan kirim command hadiah
                            $clean_grand_total = (int) str_replace([".", ","], "", $data_to_process['amount']);
                            $bonus_command = str_replace(['{username}', '{grand_total}'], [$penerima_username, $clean_grand_total], $bonus_tier->reward_command);
                            write_log("BONUS TOPUP: Mengirim command hadiah: '" . $bonus_command . "'");
                            send_pterodactyl_command($CI, $bonus_command, $server_id);
                        } else {
                            write_log("BONUS TOPUP GAGAL: Server ID untuk realm target '" . $target_realm . "' tidak ditemukan.");
                        }
                    } else {
                        write_log("BONUS TOPUP GAGAL: Tidak bisa menentukan realm target.");
                    }
                } else {
                    write_log("BONUS TOPUP: Tidak ada bonus yang sesuai untuk total belanja ini.");
                }
                // =======================================================
                // === AKHIR DARI LOGIKA BONUS TOP UP ===
                // =======================================================

            } else {
                write_log("ERROR: Eksekusi Pterodactyl GAGAL untuk transaksi ID " . $transaction_id);
                $CI->Transaction_model->update_transaction_status($transaction_id, 'pending', null);
            }
            
            $CI->db->trans_complete();
            write_log("INFO: Transaksi database selesai.");

        } else {
            write_log("INFO: Transaksi diterima, tapi diabaikan. Status di DB: " . ($transaction['status'] ?? 'TIDAK DITEMUKAN'));
        }
    } else {
        write_log("ERROR: Gagal mengekstrak transaction_id dari external_id: " . ($data_to_process['external_id'] ?? 'NULL'));
    }
} else {
    write_log("INFO: Webhook diterima tapi diabaikan. Tidak ditemukan status PAID yang valid atau event 'invoice.paid' yang sesuai.");
}

http_response_code(200);
write_log("SELESAI: Proses webhook selesai. Mengirim respons 200 OK.");

function get_bucks_kaget_cart_context($CI, $cart) {
    $context = [
        'contains' => false,
        'product' => null,
        'config' => null,
        'total_bucks' => 0,
        'item_quantity' => 0
    ];

    $existing_form = is_array($cart['bucks_kaget_form'] ?? null) ? $cart['bucks_kaget_form'] : [];

    foreach (($cart['items'] ?? []) as $item) {
        $product_id = (int) ($item['id'] ?? 0);
        if ($product_id <= 0) {
            continue;
        }

        $product = $CI->Store_model->get_product_by_id($product_id);
        if (!$product || !$CI->Store_model->is_bucks_kaget_product($product)) {
            continue;
        }

        $config = $CI->Store_model->get_bucks_kaget_config($product);
        if (!$config) {
            continue;
        }

        $context['contains'] = true;
        if ($context['product'] === null) {
            $context['product'] = $product;
            $context['config'] = $config;
        }

        $quantity = max(1, (int) ($item['quantity'] ?? 1));
        $context['item_quantity'] += $quantity;
        $context['total_bucks'] = (int) ($existing_form['total_bucks'] ?? ($item['bucks_kaget_total_bucks'] ?? $context['total_bucks']));
        break;
    }

    if ($context['contains'] && $context['total_bucks'] <= 0 && !empty($context['config'])) {
        $context['total_bucks'] = (int) ($context['config']['default_total_bucks'] ?? 0);
    }

    return $context;
}

function build_bucks_kaget_public_url($CI, $token) {
    $base_url = rtrim((string) $CI->config->item('base_url'), '/');
    if ($base_url === '') {
        $base_url = 'https://store.minehive.id';
    }

    return $base_url . '/' . ltrim((string) $token, '/');
}

function create_bucks_kaget_campaign_for_transaction($CI, $transaction_id, $transaction, $cart) {
    $context = get_bucks_kaget_cart_context($CI, $cart);
    if (!$context['contains'] || empty($context['config'])) {
        return [
            'success' => true,
            'cart' => $cart,
            'result' => null
        ];
    }

    $config = $context['config'];
    $form = is_array($cart['bucks_kaget_form'] ?? null) ? $cart['bucks_kaget_form'] : [];
    $buyer_username = trim((string) ($transaction['player_username'] ?? 'MineHive Player'));

    $name = trim((string) ($form['name'] ?? ''));
    if ($name === '') {
        $name = 'Bucks Kaget dari ' . $buyer_username;
    }

    $total_recipients = (int) ($form['total_recipients'] ?? $config['default_recipients']);
    $total_recipients = min($config['max_recipients'], max($config['min_recipients'], $total_recipients));

    if ($context['total_bucks'] < $total_recipients) {
        return [
            'success' => false,
            'message' => 'Total Bucks lebih kecil dari jumlah penerima.'
        ];
    }

    $expiry_hours = (int) ($form['expiry_hours'] ?? $config['default_expiry_hours']);
    $expiry_hours = min($config['max_expiry_hours'], max($config['min_expiry_hours'], $expiry_hours));

    $expires_at = trim((string) ($form['expires_at'] ?? ''));
    if ($expires_at === '') {
        $expires_at = date('Y-m-d H:i:s', time() + ($expiry_hours * 3600));
    }

    $allocations = $CI->Bucks_kaget_model->generate_random_allocations($context['total_bucks'], $total_recipients);
    if (empty($allocations)) {
        return [
            'success' => false,
            'message' => 'Gagal membagi total Bucks ke jumlah penerima yang diminta.'
        ];
    }

    $campaign_id = $CI->Bucks_kaget_model->create_campaign([
        'name' => $name,
        'token' => $CI->Bucks_kaget_model->generate_unique_token(),
        'total_bucks' => (int) $context['total_bucks'],
        'total_recipients' => $total_recipients,
        'expires_at' => $expires_at,
        'is_active' => 1
    ], $allocations);

    if (!$campaign_id) {
        return [
            'success' => false,
            'message' => 'Database campaign Bucks Kaget gagal dibuat.'
        ];
    }

    $campaign = $CI->Bucks_kaget_model->get_campaign_by_id($campaign_id);
    if (!$campaign || empty($campaign->token)) {
        return [
            'success' => false,
            'message' => 'Campaign dibuat, tapi token publik tidak ditemukan.'
        ];
    }

    $result = [
        'campaign_id' => (int) $campaign_id,
        'token' => (string) $campaign->token,
        'url' => build_bucks_kaget_public_url($CI, $campaign->token),
        'name' => $name,
        'total_bucks' => (int) $context['total_bucks'],
        'total_recipients' => $total_recipients,
        'expiry_hours' => $expiry_hours,
        'expires_at' => $expires_at
    ];

    $cart['bucks_kaget_form'] = [
        'name' => $name,
        'total_bucks' => (int) $context['total_bucks'],
        'total_recipients' => $total_recipients,
        'expiry_hours' => $expiry_hours,
        'expires_at' => $expires_at
    ];
    $cart['bucks_kaget_result'] = $result;

    $CI->Transaction_model->update_cart_data($transaction_id, $cart);

    return [
        'success' => true,
        'cart' => $cart,
        'result' => $result
    ];
}


/**
 * Fungsi untuk mengirim command ke Pterodactyl.
 */
function send_pterodactyl_command($CI, $command, $server_id) {
    write_log("  [Ptero] Memulai fungsi send_pterodactyl_command.");

    $panel_url = $CI->config->item('pterodactyl_panel_url');
    $api_key = $CI->config->item('pterodactyl_api_key');

    if (empty($panel_url) || empty($api_key)) {
        write_log("  [Ptero] GAGAL: URL Panel Pterodactyl atau API Key kosong di file config.");
        return false;
    }

    $api_url = rtrim($panel_url, '/') . '/api/client/servers/' . $server_id . '/command';
    $post_data = json_encode(['command' => $command]);
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json',
        'Accept: Application/vnd.pterodactyl.v1+json'
    ]);

    $response_body = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        write_log("  [Ptero] GAGAL: Terjadi error cURL (masalah jaringan/koneksi): " . $curl_error);
        return false;
    }

    write_log("  [Ptero] INFO: Pterodactyl merespons dengan HTTP Code: " . $http_code);
    if ($http_code !== 204) {
         write_log("  [Ptero] INFO: Response body dari Pterodactyl: " . $response_body);
    }
    return ($http_code === 204);
}

/**
 * [PERBAIKAN] Helper function untuk format baris harga di Discord.
 * Disalin dari controllers/Payment.php
 */
function format_discord_line($label, $price, $suffix = '', $labelWidth = 20, $priceWidth = 15) {
    // Pad label to the right
    $paddedLabel = str_pad($label, $labelWidth, ' ', STR_PAD_RIGHT);
    
    // Pad price to the left
    $paddedPrice = str_pad($price, $priceWidth, ' ', STR_PAD_LEFT);
    
    // Add suffix if it exists
    $line = $paddedLabel . $paddedPrice;
    if (!empty($suffix)) {
        $line .= ' ' . $suffix;
    }
    
    return $line;
}


/**
 * Fungsi untuk mengirim notifikasi ke Discord.
 * [PERBAIKAN TOTAL PADA FUNGSI INI]
 */
function send_admin_to_discord_webhook($CI, $cart, $pembeli_username, $penerima_username = null, $invoice_id = null, $is_first_time_buyer = false) {
    $webhook_url = $CI->config->item('discord_webhook_url');
    if (empty($webhook_url)) {
        write_log("[Discord] SKIP: discord_webhook_url kosong.");
        return false;
    }

    // --- Membangun Pesan Embed yang Dinamis ---
    $item_list_raw = [];
    $is_upgrade_purchase = false;
    foreach ($cart['items'] as $item) {
        $clean_name = str_replace(' (Upgrade)', '', $item['name']);

        // Khusus produk currency: hilangkan label realm di belakang nama (contoh: "50 Bucks (Survival)" -> "50 Bucks")
        $is_currency_item = false;
        if (!empty($item['id']) && isset($CI->Store_model)) {
            $product_for_discord = $CI->Store_model->get_product_by_id($item['id']);
            if ($product_for_discord) {
                $product_type = strtolower(trim($product_for_discord['product_type'] ?? ''));
                $product_category = strtolower(trim($product_for_discord['category'] ?? ''));
                $is_currency_item = $product_type !== 'bucks_kaget' && (($product_type === 'currency') || ($product_category === 'currency'));

                if ($is_currency_item) {
                    $realm_from_product = trim($product_for_discord['realm'] ?? '');
                    if ($realm_from_product !== '') {
                        $clean_name = preg_replace('/\s*\(\s*' . preg_quote($realm_from_product, '/') . '\s*\)\s*$/i', '', $clean_name);
                    }

                    // Fallback: untuk jaga-jaga jika realm di DB tidak sama dengan label di nama
                    $clean_name = preg_replace('/\s*\(\s*(survival|skyblock|acidisland|oneblock)\s*\)\s*$/i', '', $clean_name);
                }
            }
        }
        
        // [MODIFIKASI] Tambahkan info quantity jika lebih dari 1
        // Asumsi struktur $item['quantity'] ada berkat stacking logic di Cart.php
        if (isset($item['quantity']) && $item['quantity'] > 1) {
            $item_list_raw[] = $item['quantity'] . 'x ' . $clean_name;
        } else {
            $item_list_raw[] = $clean_name;
        }

        if (!empty($item['is_upgrade'])) {
            $is_upgrade_purchase = true;
        }
    }
    $item_string = implode("\n", $item_list_raw);

    // --- [PERBAIKAN FORMAT HARGA DITERAPKAN] ---
    $labelWidth = 20; // Lebar untuk label
    $priceWidth = 15; // Lebar untuk harga
    
    $subtotal_text = format_discord_line(
        'Subtotal :', 
        'Rp ' . number_format($cart['subtotal'], 0, ',', '.'),
        '', $labelWidth, $priceWidth
    );
    
    // Inisialisasi teks diskon
    $cart_discount_text = '';
    $promo_discount_text = '';
    $referral_discount_text = '';
    $has_discount = false;

    // Cek Diskon Keranjang
    if (isset($cart['cart_discount']) && $cart['cart_discount'] > 0) {
        $cart_discount_text = format_discord_line(
            'Diskon Belanja :', 
            '- Rp ' . number_format($cart['cart_discount'], 0, ',', '.'),
            '', $labelWidth, $priceWidth
        );
        $has_discount = true;
    }

    // Cek Diskon Promo
    if (isset($cart['promo_discount']) && $cart['promo_discount'] > 0) {
        $promo_discount_text = format_discord_line(
            'Diskon Promo:', 
            '- Rp ' . number_format($cart['promo_discount'], 0, ',', '.'),
            isset($cart['applied_promo']['code']) ? "(" . $cart['applied_promo']['code'] . ")" : '',
            $labelWidth, $priceWidth
        );
        $has_discount = true;
    }
    
    // Cek Diskon Referral
    if (isset($cart['referral_discount']) && $cart['referral_discount'] > 0) {
        $referral_discount_text = format_discord_line(
            'Diskon Referral:', 
            '- Rp ' . number_format($cart['referral_discount'], 0, ',', '.'),
            isset($cart['applied_referral']['code']) ? "(" . $cart['applied_referral']['code'] . ")" : '',
            $labelWidth, $priceWidth
        );
        $has_discount = true;
    }
    
    $total_text = format_discord_line(
        'Grand Total   :', // Disesuaikan paddingnya
        'Rp ' . number_format($cart['grand_total'], 0, ',', '.'),
        '', $labelWidth, $priceWidth
    );
    
    $separator = str_repeat("─", $labelWidth + $priceWidth);
    // --- [AKHIR PERBAIKAN HARGA] ---


    $details_value = "```\n" . $item_string . "\n\n";
    $details_value .= $subtotal_text . "\n";
    
    // Tambahkan diskon jika ada
    if ($cart_discount_text) {
         $details_value .= $cart_discount_text . "\n";
    }
    if ($promo_discount_text) {
        $details_value .= $promo_discount_text . "\n";
    }
    if ($referral_discount_text) {
        $details_value .= $referral_discount_text . "\n";
    }

    // Tambahkan separator HANYA jika ada diskon
    if ($has_discount) {
        $details_value .= $separator . "\n";
    }
    
    $details_value .= $total_text . "\n```";

    // --- [PERBAIKAN] Tambahkan emoji bintang jika ini pembeli pertama
    $author_name = html_escape($pembeli_username);
    if ($is_first_time_buyer) {
        $author_name = '⭐ ' . $author_name; // Tambahkan emoji
    }

    // === LOGIKA BARU UNTUK AVATAR BEDROCK ===
    if (strpos($pembeli_username, '.') === 0) {
        $author_icon_url = 'https://minotar.net/avatar/steve/128';
    } else {
        $author_icon_url = 'https://minotar.net/avatar/' . html_escape($pembeli_username) . '/128';
    }

    $embed_title = 'Pembelian Baru';
    $embed_description = 'Telah menyelesaikan pembelian dengan total **Rp ' . number_format($cart['grand_total'], 0, ',', '.') . '**';

    if ($penerima_username && $penerima_username !== $pembeli_username) {
        $embed_title = 'Hadiah Baru';
        $embed_description = 'Telah memberikan **' . html_escape(implode(', ', $item_list_raw)) . '** kepada **' . html_escape($penerima_username) . '**';
    } elseif ($is_upgrade_purchase) {
        $embed_title = 'Rank Upgraded!';
        $embed_description = 'Telah upgrade rank menjadi **' . html_escape($item_list_raw[0]) . '**';
    }

    // Tambahkan ID Invoice ke Footer
    $footer_text = 'MineHive Store';
    if ($invoice_id) {
        $footer_text .= ' | Invoice: ' . html_escape($invoice_id);
    }

    $embed_data = [
        'embeds' => [[
            'author' => ['name' => $author_name, 'icon_url' => $author_icon_url],
            'description' => $embed_description,
            'color' => "4886754",
            'fields' => [['name' => 'Rincian Pembelian', 'value' => $details_value]],
            'footer' => ['text' => $footer_text], // Gunakan footer text yang baru
            'timestamp' => date('c')
        ]]
    ];

    $json_data = json_encode($embed_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $ch = curl_init($webhook_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response_body = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        write_log("[Discord] GAGAL: Terjadi error cURL: " . $curl_error);
        return false;
    }

    write_log("[Discord] INFO: Discord merespons dengan HTTP Code: " . $http_code);
    if ($http_code < 200 || $http_code >= 300) {
        write_log("[Discord] INFO: Response body dari Discord: " . ($response_body ?? ''));
        return false;
    }

    return true;
}

function build_public_support_purchase_summary($cart) {
    $items = [];

    foreach (($cart['items'] ?? []) as $item) {
        $item_name = preg_replace('/[\x00-\x1F\x7F]/u', ' ', (string) ($item['name'] ?? ''));
        $item_name = trim(str_replace(' (Upgrade)', '', $item_name));
        if ($item_name === '') {
            $item_name = 'Item Tidak Dikenal';
        }

        $quantity = max(1, (int) ($item['quantity'] ?? 1));
        $items[] = '(' . $quantity . 'x) ' . $item_name;
    }

    return !empty($items) ? implode(', ', $items) : 'something awesome';
}

function send_public_support_to_discord_webhook($CI, $cart, $pembeli_username) {
    $webhook_url = trim((string) $CI->config->item('discord_webhook_url_support'));
    if ($webhook_url === '') {
        write_log("[Discord Public] SKIP: discord_webhook_url_support kosong.");
        return true;
    }

    $webhook_username = trim((string) $CI->config->item('discord_webhook_support_name'));
    if ($webhook_username === '') {
        $webhook_username = 'Patreon';
    }

    $webhook_avatar = trim((string) $CI->config->item('discord_webhook_support_avatar_url'));
    if ($webhook_avatar === '') {
        $webhook_avatar = 'https://i.imgur.com/BUS06N2.png';
    }

    $clean_username = trim((string) $pembeli_username);
    if ($clean_username === '') {
        $clean_username = 'Unknown Player';
    }

    $purchase_summary = build_public_support_purchase_summary($cart);
    $store_url = rtrim((string) $CI->config->item('base_url'), '/');
    if ($store_url === '') {
        $store_url = 'https://store.minehive.id';
    }

    if (strpos($clean_username, '.') === 0) {
        $player_avatar_url = 'https://minotar.net/avatar/steve/128';
    } else {
        $player_avatar_url = 'https://minotar.net/avatar/' . rawurlencode($clean_username) . '/128';
    }

    $embed_data = [
        'username' => $webhook_username,
        'avatar_url' => $webhook_avatar,
        'embeds' => [[
            'author' => [
                'name' => $clean_username,
                'icon_url' => $player_avatar_url
            ],
            'title' => 'Thank you for the support!',
            'description' => 'Thank you **' . $clean_username . '** for buying ' . $purchase_summary . '! Your support is the only reason we can do amazing updates! <:love_orange:1481312433653153905>',
            'color' => 15548997,
            'thumbnail' => [
                'url' => $player_avatar_url
            ],
            'footer' => [
                'text' => 'Visit the store at ' . $store_url
            ],
            'timestamp' => date('c')
        ]]
    ];

    $json_data = json_encode($embed_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $ch = curl_init($webhook_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response_body = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        write_log("[Discord Public] GAGAL: Terjadi error cURL: " . $curl_error);
        return false;
    }

    write_log("[Discord Public] INFO: Discord merespons dengan HTTP Code: " . $http_code);
    if ($http_code < 200 || $http_code >= 300) {
        write_log("[Discord Public] INFO: Response body dari Discord: " . ($response_body ?? ''));
        return false;
    }

    return true;
}

function send_to_discord_webhook($CI, $cart, $pembeli_username, $penerima_username = null, $invoice_id = null, $is_first_time_buyer = false) {
    $admin_ok = send_admin_to_discord_webhook($CI, $cart, $pembeli_username, $penerima_username, $invoice_id, $is_first_time_buyer);
    $public_ok = send_public_support_to_discord_webhook($CI, $cart, $pembeli_username);

    return ($admin_ok && $public_ok);
}

?>
