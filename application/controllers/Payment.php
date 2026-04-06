<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payment extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->model('Store_model');
        $this->load->model('Transaction_model');
        $this->load->model('Promo_model');
        $this->load->model('Affiliate_model'); // (Saya tambahkan ini di construct)
        $this->load->model('Discount_model');
        $this->load->model('Cart_discount_model');
        $this->load->model('Settings_model');
        $this->config->load('xendit', TRUE); // Muat konfigurasi Xendit kita

        
        // Wajib login untuk mengakses controller ini
        if (!$this->session->userdata('is_logged_in')) {
            $this->session->set_flashdata('error', 'Anda harus login untuk melanjutkan.');
            redirect(base_url());
        }
    }

    /**
     * Fungsi private untuk mengirim command via Pterodactyl Client API.
     */
    private function _send_pterodactyl_command($command, $server_id) {
        $panel_url = $this->config->item('pterodactyl_panel_url');
        $api_key = $this->config->item('pterodactyl_api_key');

        if (empty($panel_url) || empty($api_key) || empty($server_id)) {
            log_message('error', 'Pterodactyl Gagal: Konfigurasi (URL/Key/ServerID) tidak lengkap.');
            return false;
        }

        $api_url = rtrim($panel_url, '/') . '/api/client/servers/' . $server_id . '/command';
        $post_data = json_encode(['command' => $command]);
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json',
            'Accept: Application/vnd.pterodactyl.v1+json'
        ]);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        // --- [LOG TAMBAHAN DI SINI] ---
        // Kita log *setiap* panggilan Pterodactyl untuk melihat HTTP code-nya
        log_message('error', '--- [LOG DEBUG PTERODACTYL] --- Server: ' . $server_id . ' | Command: ' . $command . ' | HTTP Code: ' . $http_code);
        // --- [AKHIR LOG TAMBAHAN] ---


        if ($http_code !== 204) {
            log_message('error', '================ PTERODACTYL API CALL FAILED (CODE BUKAN 204) ================');
            log_message('error', 'Server ID Target: ' . $server_id);
            log_message('error', 'Perintah: ' . $command);
            log_message('error', 'HTTP Status Code Diterima: ' . $http_code);
            log_message('error', 'Pesan Error cURL (jika ada): ' . $curl_error);
            log_message('error', 'Respons Penuh dari API: ' . $response);
            log_message('error', '===========================================================');
            return false;
        }
        return true;
    }

    private function _split_command_lines($raw_command) {
        $raw_command = trim((string) $raw_command);
        if ($raw_command === '') {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', $raw_command);
        $commands = [];

        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line !== '') {
                $commands[] = $line;
            }
        }

        return $commands;
    }

    private function _extract_commands_for_product($product) {
        $commands = [];
        $has_custom_bundle_commands = false;
        $product_type = strtolower(trim((string) ($product['product_type'] ?? '')));
        $description = json_decode($product['description'] ?? '', true);

        if (
            $product_type === 'bundles' &&
            is_array($description) &&
            (($description['bundle_mode'] ?? '') === 'custom_commands' || isset($description['commands'])) &&
            !empty($description['commands']) &&
            is_array($description['commands'])
        ) {
            foreach ($description['commands'] as $command_line) {
                $command_line = trim((string) $command_line);
                if ($command_line !== '') {
                    $commands[] = $command_line;
                }
            }
            $has_custom_bundle_commands = !empty($commands);
        }

        if (!$has_custom_bundle_commands) {
            foreach ($this->_split_command_lines($product['command'] ?? '') as $command_line) {
                $commands[] = $command_line;
            }
        }

        return $commands;
    }

    private function _get_bucks_kaget_default_name() {
        $buyer_username = trim((string) $this->session->userdata('username'));
        if ($buyer_username === '') {
            $buyer_username = 'MineHive Player';
        }

        return 'Bucks Kaget dari ' . $buyer_username;
    }

    private function _resolve_bucks_kaget_pricing($product, $total_bucks) {
        if (is_object($product)) {
            $product = (array) $product;
        }

        $config = $this->Store_model->get_bucks_kaget_config($product);
        if (!$config) {
            return null;
        }

        $total_bucks = min($config['max_total_bucks'], max($config['min_total_bucks'], (int) $total_bucks));
        $raw_base_price_per_buck = max(1, (float) ($product['price'] ?? $config['base_price_per_buck'] ?? 7000));
        $final_base_price_per_buck = $raw_base_price_per_buck;

        $active_discount = $this->Discount_model->find_active_discount_for_product($product['id'], $product['category'] ?? '');
        if ($active_discount) {
            $discount_amount = $raw_base_price_per_buck * ($active_discount->discount_percentage / 100);
            $final_base_price_per_buck = max(0, $raw_base_price_per_buck - $discount_amount);
        }

        $discount_ratio = $raw_base_price_per_buck > 0 ? ($final_base_price_per_buck / $raw_base_price_per_buck) : 1.0;
        if ($discount_ratio > 1) {
            $discount_ratio = 1.0;
        }

        $tier_min_qty = 1;
        $price_per_buck_raw = $raw_base_price_per_buck;
        foreach (array_reverse($config['price_tiers']) as $tier) {
            if ($total_bucks >= (int) ($tier['min_qty'] ?? 1)) {
                $tier_min_qty = (int) ($tier['min_qty'] ?? 1);
                $price_per_buck_raw = max(1, (float) ($tier['price_per_buck'] ?? $raw_base_price_per_buck));
                break;
            }
        }

        return [
            'config' => $config,
            'total_bucks' => $total_bucks,
            'tier_min_qty' => $tier_min_qty,
            'price_per_buck_raw' => $price_per_buck_raw,
            'price_per_buck_final' => $price_per_buck_raw * $discount_ratio,
            'final_total_price' => (int) round($total_bucks * ($price_per_buck_raw * $discount_ratio)),
            'original_total_price' => (int) round($total_bucks * $price_per_buck_raw)
        ];
    }

    private function _apply_bucks_kaget_form_to_cart(array &$cart, $product, array $form) {
        if (is_object($product)) {
            $product = (array) $product;
        }

        $pricing = $this->_resolve_bucks_kaget_pricing($product, $form['total_bucks'] ?? 0);
        if (!$pricing) {
            return null;
        }

        $cart['items'][$product['id']] = [
            'id' => (int) $product['id'],
            'name' => (string) ($product['name'] ?? 'Bucks Kaget'),
            'unit_price' => $pricing['final_total_price'],
            'price' => $pricing['final_total_price'],
            'quantity' => 1,
            'is_upgrade' => false,
            'is_bucks_kaget' => true,
            'original_price' => $pricing['original_total_price'] > $pricing['final_total_price'] ? $pricing['original_total_price'] : null,
            'is_flash_sale' => false,
            'bundle_group_id' => null,
            'bucks_kaget_total_bucks' => $pricing['total_bucks'],
            'bucks_kaget_total_recipients' => (int) ($form['total_recipients'] ?? 1),
            'bucks_kaget_price_per_buck' => (int) round($pricing['price_per_buck_final']),
            'bucks_kaget_original_price_per_buck' => (int) round($pricing['price_per_buck_raw']),
            'bucks_kaget_tier_min_qty' => (int) $pricing['tier_min_qty']
        ];

        $cart['bucks_kaget_form'] = [
            'name' => (string) ($form['name'] ?? $this->_get_bucks_kaget_default_name()),
            'total_bucks' => (int) $pricing['total_bucks'],
            'total_recipients' => (int) ($form['total_recipients'] ?? 1),
            'expiry_hours' => (int) ($form['expiry_hours'] ?? $pricing['config']['default_expiry_hours']),
            'expires_at' => (string) ($form['expires_at'] ?? date('Y-m-d H:i:s', time() + ((int) ($form['expiry_hours'] ?? $pricing['config']['default_expiry_hours']) * 3600))),
            'product_id' => (int) ($product['id'] ?? 0),
            'product_name' => (string) ($product['name'] ?? 'Bucks Kaget')
        ];

        unset($cart['bucks_kaget_result']);

        return [
            'form' => $cart['bucks_kaget_form'],
            'pricing' => $pricing
        ];
    }

    private function _update_cart_totals(array &$cart) {
        $subtotal = 0;
        $cart_discount_amount = 0;

        foreach (($cart['items'] ?? []) as $item) {
            $subtotal += (float) ($item['price'] ?? 0);
        }

        $cart['subtotal'] = $subtotal;
        $cart['cart_discount'] = 0;
        $cart['applied_cart_discount_tier'] = null;
        $cart['promo_discount'] = 0;
        $cart['referral_discount'] = 0;

        $settings = $this->Settings_model->get_all_settings();
        $is_cart_discount_enabled = $settings['cart_discount_enabled'] ?? false;
        if ($is_cart_discount_enabled && $subtotal > 0) {
            $applicable_tier = $this->Cart_discount_model->get_applicable_tier($subtotal);
            if ($applicable_tier) {
                $cart_discount_amount = $subtotal * ($applicable_tier->discount_percentage / 100);
                $cart['cart_discount'] = $cart_discount_amount;
                $cart['applied_cart_discount_tier'] = [
                    'min_amount' => $applicable_tier->min_amount,
                    'percentage' => $applicable_tier->discount_percentage
                ];
            }
        }

        $base_for_promo = max(0, $subtotal - $cart_discount_amount);
        $base_for_referral = max(0, $subtotal - $cart_discount_amount);

        if (isset($cart['applied_promo'])) {
            $applied_promo = $cart['applied_promo'];
            if ($base_for_promo > 0) {
                if (($applied_promo['discount_type'] ?? '') == 'percentage') {
                    $cart['promo_discount'] = $base_for_promo * (float) ($applied_promo['value'] ?? 0);
                } else {
                    $cart['promo_discount'] = min((float) ($applied_promo['value'] ?? 0), $base_for_promo);
                }
            }
        }

        if (isset($cart['applied_referral']) && $base_for_referral > 0) {
            $applied_referral = $cart['applied_referral'];
            $cart['referral_discount'] = $base_for_referral * (float) ($applied_referral['value'] ?? 0);
        }

        $cart['grand_total'] = max(0, $subtotal - $cart_discount_amount - $cart['promo_discount'] - $cart['referral_discount']);
    }

    private function _get_bucks_kaget_cart_context(array $cart) {
        $context = [
            'contains' => false,
            'product' => null,
            'config' => null,
            'total_bucks' => 0,
            'item_quantity' => 0,
            'form' => null
        ];

        foreach (($cart['items'] ?? []) as $item) {
            $product_id = (int) ($item['id'] ?? 0);
            if ($product_id <= 0) {
                continue;
            }

            $product = $this->Store_model->get_product_by_id($product_id);
            if (!$product || !$this->Store_model->is_bucks_kaget_product($product)) {
                continue;
            }

            $config = $this->Store_model->get_bucks_kaget_config($product);
            if (!$config) {
                continue;
            }

            $context['contains'] = true;
            $context['product'] = $product;
            $context['config'] = $config;
            $context['item_quantity'] = max(1, (int) ($item['quantity'] ?? 1));
            break;
        }

        if (!$context['contains'] || empty($context['config']) || empty($context['product'])) {
            return $context;
        }

        $existing_form = is_array($cart['bucks_kaget_form'] ?? null) ? $cart['bucks_kaget_form'] : [];
        $context['form'] = [
            'name' => trim((string) ($existing_form['name'] ?? '')) ?: $this->_get_bucks_kaget_default_name(),
            'total_bucks' => max(
                $context['config']['min_total_bucks'],
                min(
                    $context['config']['max_total_bucks'],
                    (int) ($existing_form['total_bucks'] ?? ($cart['items'][$context['product']['id']]['bucks_kaget_total_bucks'] ?? $context['config']['default_total_bucks']))
                )
            ),
            'total_recipients' => max(
                $context['config']['min_recipients'],
                min(
                    $context['config']['max_recipients'],
                    (int) ($existing_form['total_recipients'] ?? ($cart['items'][$context['product']['id']]['bucks_kaget_total_recipients'] ?? $context['config']['default_recipients']))
                )
            ),
            'expiry_hours' => max(
                $context['config']['min_expiry_hours'],
                min(
                    $context['config']['max_expiry_hours'],
                    (int) ($existing_form['expiry_hours'] ?? $context['config']['default_expiry_hours'])
                )
            )
        ];
        $context['total_bucks'] = (int) $context['form']['total_bucks'];

        return $context;
    }

    private function _normalize_bucks_kaget_checkout_form(array $cart) {
        $context = $this->_get_bucks_kaget_cart_context($cart);
        if (!$context['contains'] || empty($context['config']) || empty($context['product'])) {
            return [
                'valid' => true,
                'context' => $context,
                'form' => null,
                'message' => null,
                'cart' => $cart
            ];
        }

        $config = $context['config'];
        $existing_form = is_array($context['form']) ? $context['form'] : [];
        $name = trim((string) $this->input->post('bucks_kaget_name'));
        if ($name === '') {
            $name = trim((string) ($existing_form['name'] ?? $this->_get_bucks_kaget_default_name()));
        }

        if (strlen($name) > 120) {
            return [
                'valid' => false,
                'context' => $context,
                'form' => $existing_form,
                'message' => 'Nama Bucks Kaget maksimal 120 karakter.',
                'cart' => $cart
            ];
        }

        $total_bucks = (int) $this->input->post('bucks_kaget_total_bucks');
        if ($total_bucks <= 0) {
            $total_bucks = (int) ($existing_form['total_bucks'] ?? $config['default_total_bucks']);
        }

        if ($total_bucks < $config['min_total_bucks'] || $total_bucks > $config['max_total_bucks']) {
            return [
                'valid' => false,
                'context' => $context,
                'form' => $existing_form,
                'message' => 'Total Bucks harus di antara ' . $config['min_total_bucks'] . ' dan ' . $config['max_total_bucks'] . '.',
                'cart' => $cart
            ];
        }

        $total_recipients = (int) $this->input->post('bucks_kaget_total_recipients');
        if ($total_recipients <= 0) {
            $total_recipients = (int) ($existing_form['total_recipients'] ?? $config['default_recipients']);
        }

        $expiry_hours = (int) $this->input->post('bucks_kaget_expiry_hours');
        if ($expiry_hours <= 0) {
            $expiry_hours = (int) ($existing_form['expiry_hours'] ?? $config['default_expiry_hours']);
        }

        if ($total_recipients < $config['min_recipients'] || $total_recipients > $config['max_recipients']) {
            return [
                'valid' => false,
                'context' => $context,
                'form' => $existing_form,
                'message' => 'Total penerima Bucks Kaget harus di antara ' . $config['min_recipients'] . ' dan ' . $config['max_recipients'] . '.',
                'cart' => $cart
            ];
        }

        if ($expiry_hours < $config['min_expiry_hours'] || $expiry_hours > $config['max_expiry_hours']) {
            return [
                'valid' => false,
                'context' => $context,
                'form' => $existing_form,
                'message' => 'Masa aktif link Bucks Kaget harus di antara ' . $config['min_expiry_hours'] . ' dan ' . $config['max_expiry_hours'] . ' jam.',
                'cart' => $cart
            ];
        }

        if ($total_bucks < $total_recipients) {
            return [
                'valid' => false,
                'context' => $context,
                'form' => $existing_form,
                'message' => 'Total Bucks Kaget minimal harus sama dengan jumlah penerima.',
                'cart' => $cart
            ];
        }

        $form = [
            'name' => $name,
            'total_bucks' => $total_bucks,
            'total_recipients' => $total_recipients,
            'expiry_hours' => $expiry_hours,
            'expires_at' => date('Y-m-d H:i:s', time() + ($expiry_hours * 3600)),
            'product_id' => (int) ($context['product']['id'] ?? 0),
            'product_name' => (string) ($context['product']['name'] ?? 'Bucks Kaget')
        ];

        $this->_apply_bucks_kaget_form_to_cart($cart, $context['product'], $form);
        $this->_update_cart_totals($cart);

        return [
            'valid' => true,
            'context' => $this->_get_bucks_kaget_cart_context($cart),
            'form' => $cart['bucks_kaget_form'],
            'message' => null,
            'cart' => $cart
        ];
    }

    private function _get_transaction_for_current_user($transaction_id) {
        $transaction_id = (int) $transaction_id;
        if ($transaction_id <= 0) {
            return null;
        }

        $transaction = $this->Transaction_model->get_transaction_by_id($transaction_id);
        if (!$transaction) {
            return null;
        }

        $session_uuid = trim((string) $this->session->userdata('uuid'));
        if ($session_uuid === '' || trim((string) ($transaction['player_uuid'] ?? '')) !== $session_uuid) {
            return null;
        }

        return $transaction;
    }

    private function _build_transaction_status_payload($transaction) {
        $transaction_id = (int) ($transaction['id'] ?? 0);
        $status = strtolower(trim((string) ($transaction['status'] ?? 'pending')));
        $payment_method = strtolower(trim((string) ($transaction['payment_method'] ?? '')));
        $created_at = strtotime((string) ($transaction['created_at'] ?? ''));
        $cart = json_decode((string) ($transaction['cart_data'] ?? ''), true);
        $cart = is_array($cart) ? $cart : [];

        $bucks_kaget_result = is_array($cart['bucks_kaget_result'] ?? null) ? $cart['bucks_kaget_result'] : null;
        $bucks_kaget_form = is_array($cart['bucks_kaget_form'] ?? null) ? $cart['bucks_kaget_form'] : null;
        $has_bucks_kaget = $bucks_kaget_result !== null || $bucks_kaget_form !== null;
        $checkout_meta = is_array($cart['checkout_meta'] ?? null) ? $cart['checkout_meta'] : [];
        $invoice_url = trim((string) ($checkout_meta['invoice_url'] ?? ''));
        $can_pay = (
            $status === 'pending' &&
            $payment_method === 'xendit' &&
            $invoice_url !== '' &&
            $created_at > 0 &&
            $created_at >= (time() - 86400)
        );

        $message = 'Pembayaran kamu sedang kami proses.';
        if ($status === 'completed') {
            $message = $has_bucks_kaget && !empty($bucks_kaget_result['url'])
                ? 'Pembayaran berhasil. Link Bucks Kaget kamu sudah siap dibagikan.'
                : 'Pembayaran berhasil diproses dan item sudah dikirim.';
        } elseif ($status === 'failed') {
            $message = 'Pembayaran gagal atau belum bisa diproses.';
        }

        return [
            'transaction_id' => $transaction_id,
            'status' => $status,
            'message' => $message,
            'has_bucks_kaget' => $has_bucks_kaget,
            'invoice_url' => $invoice_url,
            'can_pay' => $can_pay,
            'bucks_kaget' => [
                'ready' => !empty($bucks_kaget_result['url']),
                'name' => (string) ($bucks_kaget_result['name'] ?? ($bucks_kaget_form['name'] ?? 'Bucks Kaget')),
                'url' => (string) ($bucks_kaget_result['url'] ?? ''),
                'total_bucks' => (int) ($bucks_kaget_result['total_bucks'] ?? ($bucks_kaget_form['total_bucks'] ?? 0)),
                'total_recipients' => (int) ($bucks_kaget_result['total_recipients'] ?? ($bucks_kaget_form['total_recipients'] ?? 0)),
                'expires_at' => (string) ($bucks_kaget_result['expires_at'] ?? ($bucks_kaget_form['expires_at'] ?? ''))
            ]
        ];
    }

    private function _persist_checkout_meta($transaction_id, array $cart, array $invoice_result) {
        $cart['checkout_meta'] = [
            'invoice_id' => (string) ($invoice_result['id'] ?? ''),
            'invoice_url' => (string) ($invoice_result['invoice_url'] ?? ''),
            'external_id' => (string) ($invoice_result['external_id'] ?? ''),
            'created' => date('c')
        ];

        $this->Transaction_model->update_cart_data($transaction_id, $cart);
    }

    public function checkout()
    {
        // Bagian 1: Validasi Awal & Logika Gifting
        if (!$this->session->userdata('is_logged_in')) {
            redirect(base_url());
            return;
        }

        $cart = $this->session->userdata('cart');
        if (empty($cart) || empty($cart['items'])) {
            redirect('cart');
            return;
        }

        $bucks_kaget_checkout = $this->_normalize_bucks_kaget_checkout_form($cart);
        $cart = $bucks_kaget_checkout['cart'];
        $contains_bucks_kaget = !empty($bucks_kaget_checkout['context']['contains']);

        $is_gifting = $this->input->post('is_gift') == '1';
        $contains_upgrade = false;
        foreach ($cart['items'] as $item) {
            if (!empty($item['is_upgrade'])) {
                $contains_upgrade = true;
                break;
            }
        }
        if ($contains_upgrade && $is_gifting) {
            $this->session->set_flashdata('error', 'Rank Upgrades tidak bisa dikirim sebagai hadiah.');
            redirect('cart');
            return;
        }

        if ($contains_bucks_kaget && $is_gifting) {
            $this->session->set_flashdata('error', 'Bucks Kaget tidak bisa dikirim sebagai hadiah langsung.');
            redirect('cart');
            return;
        }

        $penerima_username = $this->session->userdata('username');
        if ($is_gifting) {
            $gifting_username = trim($this->input->post('gifting_username'));
            if (!empty($gifting_username)) {
                $penerima_username = $gifting_username;
            } else {
                $this->session->set_flashdata('error', 'Username penerima hadiah tidak boleh kosong.');
                redirect('cart');
                return;
            }
        }

        if ($contains_bucks_kaget) {
            $this->session->set_userdata('cart', $cart);

            if (!$bucks_kaget_checkout['valid']) {
                $this->session->set_flashdata('error', $bucks_kaget_checkout['message']);
                redirect('cart');
                return;
            }
        } elseif (isset($cart['bucks_kaget_form'])) {
            unset($cart['bucks_kaget_form']);
            $this->session->set_userdata('cart', $cart);
        }

        // Bagian 2: PERBAIKAN - Catat Transaksi & Rincian Item ke DB
        
        // [PERBAIKAN] Dapatkan ID Afiliasi dari $cart['applied_referral']
        $affiliate_id = null;
        $commission_rate = null;
        if (isset($cart['applied_referral'])) { // <--- PERUBAHAN DI SINI
            $affiliate_id = $cart['applied_referral']['affiliate_id']; // <--- PERUBAHAN DI SINI
            
            // --- INI DIA PERBAIKANNYA ---
            // 1. Ambil data lengkap afiliasi berdasarkan ID
            $affiliate_data = $this->Affiliate_model->get_affiliate_by_id($affiliate_id);

            // 2. Cek jika data ada, lalu ambil 'commission_rate' (ini adalah desimal, cth: 0.03)
            if ($affiliate_data) {
                // Ambil rate komisi berdasarkan badge afiliasi
                $badge_info = $this->Affiliate_model->get_badge_info($affiliate_data->total_sales, $affiliate_data->total_transactions);
                $commission_rate = $badge_info['commission_rate'];
            }
            // -----------------------------
        }
        
        // Siapkan data transaksi utama
        $transaction_data = [
            'player_uuid' => $this->session->userdata('uuid'),
            'player_username' => $this->session->userdata('username'),
            'subtotal' => $cart['subtotal'],
            // [PERBAIKAN] Jumlahkan kedua diskon untuk kolom 'discount'
            'discount' => ($cart['promo_discount'] ?? 0) + ($cart['referral_discount'] ?? 0), // <--- PERUBAHAN DI SINI
            'cart_discount' => $cart['cart_discount'], // Simpan diskon keranjang
            'grand_total' => $cart['grand_total'],
            'status' => 'pending',
            'payment_method' => 'xendit',
            'cart_data' => json_encode($cart),
            'is_gift' => $is_gifting ? 1 : 0,
            'gift_recipient_username' => $is_gifting ? $penerima_username : null,
            // [PERBAIKAN] Ambil kode promo dari $cart['applied_promo']
            'promo_code_used' => $cart['applied_promo']['code'] ?? null, // <--- PERUBAHAN DI SINI
            'affiliate_id' => $affiliate_id, // Simpan ID afiliasi
            'affiliate_commission_rate' => $commission_rate // Simpan rate komisi saat itu
            // 'affiliate_commission_amount' akan di-isi oleh webhook
        ];

        // Mulai transaksi database untuk menjamin semua data tersimpan atau tidak sama sekali
        $this->db->trans_start();

        // 1. Catat transaksi utama dan dapatkan ID-nya
        $transaction_id = $this->Transaction_model->log_transaction($transaction_data);

        // 2. Loop dan catat setiap item yang dibeli ke tabel `transaction_items`
        if ($transaction_id) {
            foreach ($cart['items'] as $item) {
                $this->Transaction_model->log_transaction_item([
                    'transaction_id' => $transaction_id,
                    'product_id'     => $item['id'],
                    'product_name'   => $item['name'],
                    'price'          => $item['price']
                ]);
            }
        }

        // Selesaikan transaksi database
        $this->db->trans_complete();

        // Jika terjadi error saat menyimpan ke database, batalkan proses
        if ($this->db->trans_status() === FALSE) {
            log_message('error', 'Gagal menyimpan transaksi atau item transaksi ke database.');
            $this->session->set_flashdata('error', 'Terjadi kesalahan pada database. Silakan coba lagi.');
            redirect('cart');
            return;
        }

        // Bagian 3: Buat Invoice Xendit (Tidak Berubah)
        
        $api_key = $this->config->item('xendit_api_key', 'xendit');
        
        $params = [
            'external_id' => 'MH-INV-' . $transaction_id,
            'amount' => (int) $cart['grand_total'],
            'payer_email' => $this->session->userdata('email') ?? 'guest@minehive.id',
            'description' => 'Pembelian item di MineHive Store #' . $transaction_id,
            'success_redirect_url' => base_url('transaction?trx=' . $transaction_id),
            'failure_redirect_url' => base_url('transaction?trx=' . $transaction_id . '&payment=failed'),
        ];

        $this->session->set_userdata('last_checkout_transaction_id', $transaction_id);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.xendit.co/v2/invoices");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Basic " . base64_encode($api_key . ":"),
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if ($http_code == 200 && isset($result['invoice_url'])) {
            $this->_persist_checkout_meta($transaction_id, $cart, $result);
            $this->session->unset_userdata('cart');
            redirect((string) $result['invoice_url']);
        } else {
            log_message('error', 'Xendit cURL Gagal: ' . $response);
            $this->session->set_flashdata('error', 'Gagal terhubung ke gerbang pembayaran. Silakan coba beberapa saat lagi.');
            redirect('cart');
        }
    }

    /**
     * Menerima notifikasi dari Xendit dan menjalankan semua proses otomatis.
     */
    public function xendit_webhook()
    {
        // 1. Verifikasi Keamanan (SANGAT PENTING) - Tidak berubah
        $xendit_webhook_token = $this->config->item('xendit_webhook_token', 'xendit');
        $request_headers = getallheaders();
        $x_callback_token = $request_headers['x-callback-token'] ?? '';

        if ($x_callback_token !== $xendit_webhook_token) {
            http_response_code(403); // Forbidden
            log_message('error', 'Xendit Webhook: Invalid callback token.');
            return;
        }

        // 2. Ambil data JSON dari Xendit - Tidak berubah
        $request_body = file_get_contents('php://input');
        // Ganti nama variabel ke $payload agar lebih jelas
        $payload = json_decode($request_body, true);
        
        // Log mentah untuk debugging jika diperlukan
        log_message('debug', 'Xendit Webhook Payload Received: ' . $request_body);

        // ======================================================================
        // MODIFIKASI UTAMA: MENANGANI STRUKTUR DATA WEBHOOK INVOICE
        // ======================================================================

        // (LOG 1: Webhook diterima)
        log_message('error', '--- [LOG DEBUG AFILIASI] --- Webhook Xendit Diterima. Event: ' . ($payload['event'] ?? 'UNKNOWN'));

        // 3. Hanya proses jika event-nya adalah 'invoice.paid'
        if (isset($payload['event']) && $payload['event'] == 'invoice.paid') {
            
            log_message('info', "Xendit Webhook: Event 'invoice.paid' terdeteksi.");

            // Ambil data invoice dari dalam objek 'data'
            $invoice_data = $payload['data'];

            // Pastikan status di dalam data invoice adalah PAID
            if (isset($invoice_data['status']) && $invoice_data['status'] == 'PAID') {
                
                // Ekstrak ID transaksi kita dari external_id di dalam data invoice
                $external_id_parts = explode('-', $invoice_data['external_id']);
                $transaction_id = $external_id_parts[2] ?? null;

                // (LOG 2: Transaksi ID ditemukan)
                log_message('error', '--- [LOG DEBUG AFILIASI] --- Invoice PAID. Mencari Transaksi ID: ' . $transaction_id);

                if ($transaction_id) {
                    $transaction = $this->Transaction_model->get_transaction_by_id($transaction_id);

                    // Hanya proses jika transaksi ada dan statusnya masih 'pending'
                    if ($transaction && $transaction['status'] == 'pending') {
                        
                        log_message('info', 'Xendit Webhook: Memproses transaksi ID ' . $transaction_id . ' dengan status pending.');
                        
                        // Ambil data keranjang DAN pembeli dari transaksi
                        $cart = json_decode($transaction['cart_data'], true);
                        $penerima_username = $transaction['gift_recipient_username'] ?? $transaction['player_username'];
                        $pembeli_username = $transaction['player_username'];
                        $pembeli_uuid = $transaction['player_uuid']; // [MODIFIKASI] Ambil UUID pembeli
                        $grand_total = $transaction['grand_total']; // Ambil grand total dari DB
                        $affiliate_id = $transaction['affiliate_id']; // Ambil ID afiliasi dari DB
                        // $commission_rate = $transaction['affiliate_commission_rate']; // Kita tidak perlu ini lagi

                        
                        // (LOG 3: Data Afiliasi ditemukan)
                        log_message('error', '--- [LOG DEBUG AFILIASI] --- Transaksi ' . $transaction_id . ' ditemukan. Affiliate ID: ' . ($affiliate_id ?? 'TIDAK ADA'));
                        
                        $this->db->trans_start();

                        // ==========================================================
                        // === PEROMBAKAN LOGIKA (SESUAI PERMINTAAN ANDA) ===
                        // ==========================================================
                        
                        // A. Kirim Perintah ke Pterodactyl (Dijalankan Dulu)
                        $all_commands_success = true;
                        $proxy_server_id = $this->config->item('pterodactyl_server_id_proxy');
                        $has_currency_purchase = false;
                        $has_non_currency_purchase = false;
                        
                        // (LOG 4: Memulai Loop Pterodactyl)
                        log_message('error', '--- [LOG DEBUG AFILIASI] --- Memulai loop Pterodactyl...');

                        foreach ($cart['items'] as $item) {
                            $product = $this->Store_model->get_product_by_id($item['id']);
                            if ($product) {
                                $product_type = strtolower(trim($product['product_type'] ?? ''));
                                $product_category = strtolower(trim($product['category'] ?? ''));
                                $is_currency = ($product_type === 'currency') || ($product_category === 'currency');
                                if ($is_currency) {
                                    $has_currency_purchase = true;
                                } else {
                                    $has_non_currency_purchase = true;
                                }

                                $commands_to_run = $this->_extract_commands_for_product($product);
                                if (!empty($commands_to_run)) {
                                    $target_server_id = null;
                                    $target_server_name = null;

                                    if ($is_currency) {
                                        // Khusus currency: command produk dieksekusi di server Proxy
                                        $target_server_id = $proxy_server_id;
                                        $target_server_name = 'Proxy';

                                        if (empty($target_server_id)) {
                                            $all_commands_success = false;
                                            log_message('error', '--- [LOG DEBUG AFILIASI] --- Pterodactyl GAGAL: pterodactyl_server_id_proxy belum di-set. Loop dihentikan.');
                                            break;
                                        }
                                    } else {
                                        $realm = trim((string) ($item['realm'] ?? $product['realm'] ?? ''));
                                        $config_key_name = 'pterodactyl_server_id_' . strtolower($realm);
                                        $target_server_id = $this->config->item($config_key_name);
                                        $target_server_name = $realm !== '' ? ucfirst(strtolower($realm)) : 'UnknownRealm';

                                        if (empty($target_server_id)) {
                                            $all_commands_success = false;
                                            log_message('error', '--- [LOG DEBUG AFILIASI] --- Pterodactyl GAGAL: Server ID untuk realm "' . ($realm !== '' ? $realm : 'UNKNOWN') . '" tidak ditemukan. Loop dihentikan.');
                                            break;
                                        }
                                    }

                                    $qty = isset($item['quantity']) ? max(1, (int) $item['quantity']) : 1;

                                    $command_failed = false;
                                    foreach ($commands_to_run as $raw_command) {
                                        if (strpos($raw_command, '{quantity}') !== false) {
                                            $command = str_replace(
                                                ['{username}', '{grand_total}', '{quantity}'],
                                                [$penerima_username, (int) $grand_total, $qty],
                                                $raw_command
                                            );

                                            log_message('error', '--- [LOG DEBUG AFILIASI] --- Mengirim command Ptero (' . $target_server_name . '): ' . $command);

                                            if (!$this->_send_pterodactyl_command($command, $target_server_id)) {
                                                $command_failed = true;
                                                break;
                                            }
                                        } else {
                                            for ($i = 0; $i < $qty; $i++) {
                                                $command = str_replace(
                                                    ['{username}', '{grand_total}', '{quantity}'],
                                                    [$penerima_username, (int) $grand_total, 1],
                                                    $raw_command
                                                );

                                                log_message('error', '--- [LOG DEBUG AFILIASI] --- Mengirim command Ptero (' . $target_server_name . '): ' . $command);

                                                if (!$this->_send_pterodactyl_command($command, $target_server_id)) {
                                                    $command_failed = true;
                                                    break;
                                                }
                                            }

                                            if ($command_failed) {
                                                break;
                                            }
                                        }
                                    }

                                    if ($command_failed) {
                                        $all_commands_success = false;
                                        log_message('error', '--- [LOG DEBUG AFILIASI] --- Pterodactyl GAGAL di item: ' . $item['name'] . '. Loop dihentikan.');
                                        break;
                                    }

                                    log_message('error', '--- [LOG DEBUG AFILIASI] --- Pterodactyl SUKSES untuk item: ' . $item['name']);
                                }
                            } else {
                                $has_non_currency_purchase = true;
                            }
                        }

                        // Khusus pembelian currency: eksekusi `donations add <grand_total>` ke semua realm yang pakai donation counter
                        if ($all_commands_success && !empty($has_currency_purchase)) {
                            $donation_command = 'donations add ' . (int) $grand_total;
                            foreach (['survival', 'skyblock', 'oneblock'] as $donation_realm) {
                                $donation_server_id = $this->config->item('pterodactyl_server_id_' . $donation_realm);
                                if (empty($donation_server_id)) {
                                    log_message('error', '--- [LOG DEBUG AFILIASI] --- Donations dilewati: Server ID untuk ' . $donation_realm . ' belum di-set.');
                                    continue;
                                }

                                log_message('error', '--- [LOG DEBUG AFILIASI] --- Mengirim command donations (' . $donation_realm . '): ' . $donation_command);
                                if (!$this->_send_pterodactyl_command($donation_command, $donation_server_id)) {
                                    log_message('error', '--- [LOG DEBUG AFILIASI] --- Donations gagal di ' . $donation_realm . ', tapi proses utama tetap dilanjutkan.');
                                }
                            }
                        }

                        // (LOG 7: Status Final Pterodactyl)
                        log_message('error', '--- [LOG DEBUG AFILIASI] --- Loop Pterodactyl selesai. Final status $all_commands_success: ' . ($all_commands_success ? 'TRUE' : 'FALSE'));


                        // B. Proses sisa (Log Aktivitas, Increment Usage, Discord)
                        // HANYA JIKA PTERODACTYL SUKSES
                        if($all_commands_success) {
                            
                            // (LOG 8: Masuk Blok Sukses)
                            log_message('error', '--- [LOG DEBUG AFILIASI] --- (Blok SUKSES) Pterodactyl berhasil. Melanjutkan proses afiliasi.');

                            // [MODIFIKASI] Cek Bonus Pembelian Pertama
                            $transaction_count = $this->Transaction_model->get_completed_transaction_count($pembeli_uuid);
                            
                            // (PERMINTAAN 1) Cek apakah ini pembeli pertama
                            $is_first_time_buyer = ($transaction_count == 0);

                            if ($is_first_time_buyer) {
                                // Ini adalah pembelian pertama!
                                log_message('info', 'First-Time Buyer Detected for UUID: ' . $pembeli_uuid);
                                $this->load->model('First_bonus_model');
                                $bonus_commands = $this->First_bonus_model->get_active_commands();
                                
                                foreach ($bonus_commands as $bonus) {
                                    // Asumsi bonus command HANYA berlaku untuk realm 'survival'
                                    // TODO: Anda mungkin perlu membuat ini lebih fleksibel nanti
                                    $server_id_bonus = $this->config->item('pterodactyl_server_id_survival');
                                    if (!empty($server_id_bonus)) {
                                        $command_to_send = str_replace('{username}', $penerima_username, $bonus->reward_command);
                                        log_message('info', 'Sending First-Time Bonus Command: ' . $command_to_send);
                                        $this->_send_pterodactyl_command($command_to_send, $server_id_bonus);
                                        // Kita tidak menghentikan proses jika bonus gagal
                                    }
                                }
                            }
                            // [AKHIR MODIFIKASI BONUS FIRST TIME]


                            // [FITUR BARU] PROSES HADIAH GOSOK BERHADIAH
                            log_message('error', '[Scratch Event] Memulai proses cek hadiah gosok...');
                            $this->load->model('Scratch_event_model');
                            $this->load->model('Settings_model'); // (Sudah di-load di construct, tapi pastikan saja)

                            $scratch_settings = $this->Settings_model->get_all_settings();
                            if (!empty($scratch_settings['scratch_event_enabled']) && $scratch_settings['scratch_event_enabled'] == '1') {
                                
                                log_message('error', '[Scratch Event] Fitur AKIF. Cek tier untuk Grand Total: ' . $grand_total);
                                $tier = $this->Scratch_event_model->get_applicable_tier($grand_total);

                                if ($tier) {
                                    log_message('error', '[Scratch Event] Customer masuk Tier: ' . $tier->title);
                                    $reward = $this->Scratch_event_model->get_random_reward_for_tier($tier->id);

                                    if ($reward) {
                                        log_message('error', '[Scratch Event] Customer memenangkan: ' . $reward->display_name);

                                        // 1. Eksekusi jika command
                                        if ($reward->reward_type == 'command') {
                                            $this->load->model('Store_model'); // (Sudah di-load di construct, tapi pastikan saja)
                                            
                                            // Ambil item pertama dari keranjang
                                            $first_item_id = array_key_first($cart['items']);
                                            $first_item = $cart['items'][$first_item_id];
                                            $product = $this->Store_model->get_product_by_id($first_item['id']);

                                            if ($product) {
                                                $realm = trim((string) ($first_item['realm'] ?? $product['realm'] ?? ''));
                                                $config_key_name = 'pterodactyl_server_id_' . strtolower($realm);
                                                $server_id = $this->config->item($config_key_name);
                                                
                                                if ($server_id) {
                                                    $command = str_replace('{username}', $penerima_username, $reward->reward_value);
                                                    $this->_send_pterodactyl_command($command, $server_id);
                                                    log_message('error', '[Scratch Event] EKSEKUSI COMMAND: ' . $command . ' di server ' . $realm);
                                                } else {
                                                    log_message('error', '[Scratch Event] GAGAL EKSEKUSI: Server ID Pterodactyl untuk realm ' . $realm . ' tidak ditemukan.');
                                                }
                                            }
                                        }

                                        // 2. Catat kemenangan (untuk SEMUA tipe hadiah)
                                        $this->Scratch_event_model->log_won_reward([
                                            'transaction_id' => $transaction_id,
                                            'player_uuid'    => $pembeli_uuid, // Gunakan UUID pembeli
                                            'reward_bank_id' => $reward->id
                                        ]);
                                        log_message('error', '[Scratch Event] Kemenangan dicatat di db untuk UUID: ' . $pembeli_uuid);

                                    } else {
                                        log_message('error', '[Scratch Event] Tier ditemukan, tapi GAGAL mengambil hadiah (Pool Hadiah mungkin kosong).');
                                    }
                                } else {
                                    log_message('error', '[Scratch Event] Belanja customer tidak memenuhi syarat tier manapun.');
                                }
                            } else {
                                log_message('error', '[Scratch Event] Fitur NON-AKTIF. Proses dilewati.');
                            }
                            // [AKHIR FITUR BARU] GOSOK BERHADIAH


                            // 1. Panggil fungsi log_affiliate_sale. 
                            // Ini adalah "JANTUNG" logika afiliasi.
                            $commission_data = null;
                            if ($affiliate_id) {
                                // (LOG 9: Memanggil log_affiliate_sale)
                                log_message('error', '--- [LOG DEBUG AFILIASI] --- (Blok SUKSES) Memanggil Affiliate_model->log_affiliate_sale() untuk ID: ' . $affiliate_id . ' | Amount: ' . $grand_total);
                                
                                $commission_data = $this->Affiliate_model->log_affiliate_sale($affiliate_id, $grand_total);
                                
                                // (LOG 10: Hasil dari log_affiliate_sale)
                                log_message('error', '--- [LOG DEBUG AFILIASI] --- (Blok SUKSES) Hasil log_affiliate_sale: ' . json_encode($commission_data));
                            }

                            // 2. Update status transaksi menjadi 'completed'
                            // dan simpan jumlah komisi yang DIKEMBALIKAN oleh model.
                            $commission_amount_to_log = ($commission_data && isset($commission_data['commission_amount'])) ? $commission_data['commission_amount'] : 0;
                            
                            $this->Transaction_model->update_transaction_status(
                                $transaction_id, 
                                'completed', 
                                $commission_amount_to_log // Simpan jumlah komisi
                            );


                            // --- (LOGIKA AFILIASI DISESUAIKAN) ---
                            if ($affiliate_id && $commission_data) {
                                
                                // (LOG 11: Masuk ke blok log aktivitas)
                                log_message('error', '--- [LOG DEBUG AFILIASI] --- (Blok SUKSES) Masuk blok log_activity.');

                                // 3. Catat aktivitas (untuk riwayat)
                                // (Kita ambil data dari $commission_data yang dikembalikan)
                                $this->Affiliate_model->log_activity([
                                    'affiliate_id' => $affiliate_id,
                                    'type'         => 'commission',
                                    'amount'       => $commission_data['commission_amount'],
                                    'description'  => 'Komisi ' . ($commission_data['commission_rate'] * 100) . '% dari ' . $pembeli_username . ' (Trx: #' . $transaction_id . ')'
                                ]);
                                
                                // 4. Update pemakaian KODE
                                // [PERBAIKAN] Ambil kode referral dari $cart['applied_referral']
                                $this->Affiliate_model->increment_code_usage($cart['applied_referral']['code']); // <--- PERUBAHAN DI SINI
                            
                            } else if (isset($cart['applied_promo'])) { // <--- PERUBAHAN DI SINI
                                // Logika promo non-afiliasi
                                // [PERBAIKAN] Ambil kode promo dari $cart['applied_promo']
                                $this->Promo_model->increment_usage($cart['applied_promo']['code']); // <--- PERUBAHAN DI SINI
                            }
                            
                            // 5. Kirim notif ke Discord (Terakhir)
                            // (PERMINTAAN 1) Kirim status $is_first_time_buyer ke Discord
                            $this->_send_to_discord_webhook(
                                $cart, 
                                $pembeli_username, 
                                ($transaction['is_gift'] ? $penerima_username : null),
                                $is_first_time_buyer,
                                $transaction_id,
                                $has_currency_purchase,
                                $has_non_currency_purchase
                            );
                        
                        } else {
                            // JIKA PTERODACTYL GAGAL
                            // (LOG 12: Blok Gagal)
                            log_message('error', '--- [LOG DEBUG AFILIASI] --- (Blok GAGAL) $all_commands_success bernilai FALSE. Proses afiliasi dan Discord DIBATALKAN.');
                            // Kita tidak meng-update status jadi 'completed'
                            // Biarkan 'pending' agar bisa di-cek manual.
                        }
                        
                        // ==========================================================
                        // === AKHIR PEROMBAKAN LOGIKA                           ===
                        // ==========================================================

                        $this->db->trans_complete();

                    } else {
                        // Transaksi sudah diproses atau tidak ditemukan, ini bukan error, hanya diabaikan.
                        log_message('info', 'Xendit Webhook: Transaksi ID ' . $transaction_id . ' diterima, tapi diabaikan karena status bukan pending atau tidak ditemukan.');
                    }
                } else {
                    log_message('error', 'Xendit Webhook: Gagal mengekstrak Transaction ID dari external_id: ' . $invoice_data['external_id']);
                }
            } else {
                // Event 'invoice.paid' tapi status internalnya bukan PAID
                 log_message('info', 'Xendit Webhook: Event invoice.paid diterima, tapi status internal bukan PAID. Status: ' . ($invoice_data['status'] ?? 'NULL'));
            }
        } else {
            // Webhook diterima tapi bukan event yang kita proses
            log_message('info', 'Xendit Webhook: Event diterima tapi diabaikan. Event: ' . ($payload['event'] ?? 'NULL'));
        }
        
        // Kirim respons OK 200 ke Xendit untuk menandakan notifikasi sudah diterima dengan baik
        http_response_code(200);
    }

    /**
     * Helper function to format a price line for the Discord embed.
     * This pads strings to create aligned columns.
     * @param string $label Label (e.g., "Subtotal cih :")
     * @param string $price The price string (e.g., "Rp 100.000" or "- Rp 10.000")
     * @param string $suffix Optional suffix (e.g., "(BUDI01)")
     * @param int $labelWidth Width to pad the label to (e.g., 20)
     * @param int $priceWidth Width to pad the price to (e.g., 15)
     * @return string Formatted line.
     */
    private function _format_discord_line($label, $price, $suffix = '', $labelWidth = 18, $priceWidth = 16) {
        $line = str_pad($label, $labelWidth, ' ', STR_PAD_RIGHT) . str_pad($price, $priceWidth, ' ', STR_PAD_LEFT);
        if ($suffix !== '') {
            $line .= ' ' . $suffix;
        }

        return $line;
    }

    private function _sanitize_discord_text($text) {
        $text = preg_replace('/[\x00-\x1F\x7F]/u', ' ', (string) $text);
        return trim($text);
    }

    private function _normalize_avatar_username($username) {
        $normalized = ltrim(trim((string) $username), '.');
        return $normalized !== '' ? $normalized : 'Steve';
    }

    private function _get_minecraft_head_url($username, $size = 128) {
        $size = max(16, (int) $size);
        return 'https://crafthead.net/helm/' . rawurlencode($this->_normalize_avatar_username($username)) . '/' . $size;
    }

    private function _get_store_discord_webhook_urls() {
        $webhook_url = trim((string) $this->config->item('discord_webhook_url'));
        return $webhook_url !== '' ? [$webhook_url] : [];
    }

    private function _get_public_support_discord_webhook_url() {
        return trim((string) $this->config->item('discord_webhook_url_support'));
    }

    private function _get_public_support_discord_username() {
        $username = trim((string) $this->config->item('discord_webhook_support_name'));
        return $username !== '' ? $username : 'Patreon';
    }

    private function _get_public_support_discord_avatar_url() {
        $avatar_url = trim((string) $this->config->item('discord_webhook_support_avatar_url'));
        return $avatar_url !== '' ? $avatar_url : 'https://i.imgur.com/BUS06N2.png';
    }

    private function _post_to_discord_webhook($webhook_url, array $payload) {
        $json_data = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json_data === false) {
            log_message('error', 'Discord Webhook: Gagal encode payload JSON.');
            return false;
        }

        $ch = curl_init($webhook_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-type: application/json']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

        $response = curl_exec($ch);
        $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $http_code < 200 || $http_code >= 300) {
            log_message('error', 'Discord Webhook: Gagal kirim notifikasi. HTTP ' . $http_code . ' | Error: ' . $curl_error . ' | Response: ' . (string) $response);
            return false;
        }

        return true;
    }

    private function _build_discord_item_lines($cart) {
        $lines = [];

        foreach (($cart['items'] ?? []) as $item) {
            $item_name = str_replace(' (Upgrade)', '', $this->_sanitize_discord_text($item['name'] ?? 'Item Tidak Dikenal'));
            if ($item_name === '') {
                $item_name = 'Item Tidak Dikenal';
            }

            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            $realm = trim((string) ($item['realm'] ?? ''));

            $line = '- ' . $item_name;
            if ($quantity > 1) {
                $line .= ' x' . $quantity;
            }
            if ($realm !== '' && strtolower($realm) !== 'global') {
                $line .= ' [' . ucfirst(strtolower($realm)) . ']';
            }

            $lines[] = $line;
        }

        if (empty($lines)) {
            $lines[] = '- Tidak ada item yang tercatat';
        }

        if (count($lines) > 10) {
            $remaining_items = count($lines) - 10;
            $lines = array_slice($lines, 0, 10);
            $lines[] = '- +' . $remaining_items . ' item lainnya';
        }

        return implode("\n", $lines);
    }

    private function _build_discord_payment_summary($cart) {
        $label_width = 18;
        $price_width = 16;
        $lines = [];

        $lines[] = $this->_format_discord_line(
            'Subtotal',
            'Rp ' . number_format((float) ($cart['subtotal'] ?? 0), 0, ',', '.'),
            '',
            $label_width,
            $price_width
        );

        if (!empty($cart['cart_discount'])) {
            $lines[] = $this->_format_discord_line(
                'Diskon Keranjang',
                '- Rp ' . number_format((float) $cart['cart_discount'], 0, ',', '.'),
                '',
                $label_width,
                $price_width
            );
        }

        if (!empty($cart['promo_discount'])) {
            $promo_code = $this->_sanitize_discord_text($cart['applied_promo']['code'] ?? '');
            $lines[] = $this->_format_discord_line(
                'Diskon Promo',
                '- Rp ' . number_format((float) $cart['promo_discount'], 0, ',', '.'),
                $promo_code !== '' ? '(' . $promo_code . ')' : '',
                $label_width,
                $price_width
            );
        }

        if (!empty($cart['referral_discount'])) {
            $referral_code = $this->_sanitize_discord_text($cart['applied_referral']['code'] ?? '');
            $lines[] = $this->_format_discord_line(
                'Diskon Referral',
                '- Rp ' . number_format((float) $cart['referral_discount'], 0, ',', '.'),
                $referral_code !== '' ? '(' . $referral_code . ')' : '',
                $label_width,
                $price_width
            );
        }

        $lines[] = str_repeat('-', $label_width + $price_width + 1);
        $lines[] = $this->_format_discord_line(
            'Total',
            'Rp ' . number_format((float) ($cart['grand_total'] ?? 0), 0, ',', '.'),
            '',
            $label_width,
            $price_width
        );

        return "```\n" . implode("\n", $lines) . "\n```";
    }

    private function _build_discord_notification_meta($cart, $pembeli_username, $penerima_username = null, $has_currency_purchase = false, $has_non_currency_purchase = false) {
        $buyer_name = $this->_sanitize_discord_text($pembeli_username);
        $recipient_name = $this->_sanitize_discord_text($penerima_username);
        $grand_total_text = 'Rp ' . number_format((float) ($cart['grand_total'] ?? 0), 0, ',', '.');
        $store_url = rtrim((string) base_url(), '/') . '/';

        $is_gift_purchase = $recipient_name !== '' && strcasecmp($recipient_name, $buyer_name) !== 0;
        $is_upgrade_purchase = false;
        $item_names = [];

        foreach (($cart['items'] ?? []) as $item) {
            $item_name = str_replace(' (Upgrade)', '', $this->_sanitize_discord_text($item['name'] ?? ''));
            if ($item_name !== '') {
                $item_names[] = $item_name;
            }
            if (!empty($item['is_upgrade'])) {
                $is_upgrade_purchase = true;
            }
        }

        $primary_item_name = $item_names[0] ?? 'paket pilihan';
        $is_donation_only = $has_currency_purchase && !$has_non_currency_purchase;
        $has_mixed_purchase = $has_currency_purchase && $has_non_currency_purchase;

        $meta = [
            'title' => 'Thank you for the support!',
            'description' => '**' . $buyer_name . '** baru saja checkout senilai **' . $grand_total_text . '** di Server MineHive.' . "\n" . $store_url,
            'type_label' => 'Belanja',
            'color' => 5793266,
            'thumbnail_username' => $buyer_name,
        ];

        if ($is_gift_purchase) {
            $meta['description'] = '**' . $buyer_name . '** baru saja mengirim hadiah senilai **' . $grand_total_text . '** untuk **' . $recipient_name . '**.' . "\n" . $store_url;
            $meta['type_label'] = 'Gift Purchase';
            $meta['color'] = 15277667;
            $meta['thumbnail_username'] = $recipient_name;
        } elseif ($is_upgrade_purchase) {
            $meta['description'] = '**' . $buyer_name . '** baru saja upgrade rank ke **' . $primary_item_name . '** dengan total **' . $grand_total_text . '**.' . "\n" . $store_url;
            $meta['type_label'] = 'Rank Upgrade';
            $meta['color'] = 15844367;
        } elseif ($is_donation_only) {
            $meta['description'] = '**' . $buyer_name . '** baru saja berdonasi sebesar **' . $grand_total_text . '** untuk Server MineHive.' . "\n" . $store_url;
            $meta['type_label'] = 'Donasi';
            $meta['color'] = 5763719;
        } elseif ($has_mixed_purchase) {
            $meta['description'] = '**' . $buyer_name . '** baru saja support Server MineHive lewat checkout senilai **' . $grand_total_text . '**.' . "\n" . $store_url;
            $meta['type_label'] = 'Belanja + Donasi';
        }

        return $meta;
    }


    // (PERMINTAAN 1) Tambahkan parameter $is_first_time_buyer
    private function _send_to_discord_webhook_legacy($cart, $pembeli_username, $penerima_username = null, $is_first_time_buyer = false) {
        $webhook_url = $this->config->item('discord_webhook_url');
        if (empty($webhook_url)) {
            return;
        }

        // --- Membangun Pesan Embed yang Dinamis ---
        $item_list_raw = [];
        $is_upgrade_purchase = false;
        foreach ($cart['items'] as $item) {
            $item_list_raw[] = str_replace(' (Upgrade)', '', $item['name']);
            if (!empty($item['is_upgrade'])) {
                $is_upgrade_purchase = true;
            }
        }
        $item_string = implode("\n", $item_list_raw);

        // --- [PERUBAHAN FORMAT HARGA DITERAPKAN] ---
        $labelWidth = 20; // Lebar untuk label
        $priceWidth = 15; // Lebar untuk harga
        
        $subtotal_text = $this->_format_discord_line(
            'Subtotal :', 
            'Rp ' . number_format($cart['subtotal'], 0, ',', '.'),
            '', $labelWidth, $priceWidth
        );
        
        $cart_discount_text = $this->_format_discord_line(
            'Diskon Belanja :', 
            '- Rp ' . number_format($cart['cart_discount'], 0, ',', '.'),
            '', $labelWidth, $priceWidth
        );
        
        $promo_discount_text = $this->_format_discord_line(
            'Diskon Promo:', 
            '- Rp ' . number_format($cart['promo_discount'], 0, ',', '.'),
            isset($cart['applied_promo']['code']) ? "(" . $cart['applied_promo']['code'] . ")" : '',
            $labelWidth, $priceWidth
        );
        
        $referral_discount_text = $this->_format_discord_line(
            'Diskon Referral:', 
            '- Rp ' . number_format($cart['referral_discount'], 0, ',', '.'),
            isset($cart['applied_referral']['code']) ? "(" . $cart['applied_referral']['code'] . ")" : '',
            $labelWidth, $priceWidth
        );
        
        $total_text = $this->_format_discord_line(
            'Grand Total   d:', 
            'Rp ' . number_format($cart['grand_total'], 0, ',', '.'),
            '', $labelWidth, $priceWidth
        );
        
        $separator = str_repeat("─", $labelWidth + $priceWidth);
        // --- [AKHIR PERUBAHAN HARGA] ---


        $details_value = "```\n" . $item_string . "\n\n";
        $details_value .= $subtotal_text . "\n";
        if ($cart['cart_discount'] > 0) {
             $details_value .= $cart_discount_text . "\n";
        }
        
        if ($cart['promo_discount'] > 0) {
            $details_value .= $promo_discount_text . "\n";
        }
        
        if ($cart['referral_discount'] > 0) {
            $details_value .= $referral_discount_text . "\n";
        }

        $details_value .= $separator . "\n"; // Gunakan separator baru
        $details_value .= $total_text . "\n```";

        $author_name = html_escape($pembeli_username);
        if ($is_first_time_buyer) {
            $author_name = '⭐ ' . $author_name; // Tambahkan emoji
        }

        $author_icon_url = 'https://minotar.net/avatar/' . html_escape($pembeli_username) . '/128';
        $embed_title = 'Pembelian Baru';
        $embed_description = 'Telah menyelesaikan pembelian dengan total **Rp ' . number_format($cart['grand_total'], 0, ',', '.') . '**';

        if ($penerima_username && $penerima_username !== $pembeli_username) {
            $embed_title = 'Hadiah Baru';
            $embed_description = 'Telah memberikan **' . html_escape(implode(', ', $item_list_raw)) . '** kepada **' . html_escape($penerima_username) . '**';
        } elseif ($is_upgrade_purchase) {
            $embed_title = 'Rank Upgraded!';
            $embed_description = 'Telah upgrade rank menjadi **' . html_escape($item_list_raw[0]) . '**';
        }

        $embed_fields = [];
        $embed_fields[] = [
            'name' => 'Rincian Pembelian',
            'value' => $details_value,
            'inline' => false
        ];

        $embed_data = [
            'content' => '',
            'embeds' => [[
                'author' => [
                    'name' => $author_name,
                    'icon_url' => $author_icon_url
                ],
                'description' => $embed_description,
                'color' => "4886754",
                'fields' => $embed_fields,
                'footer' => [
                    'text' => 'MineHive Store'
                ],
                'timestamp' => date('c')
            ]]
        ];

        $json_data = json_encode($embed_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $ch = curl_init($webhook_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_exec($ch);
        curl_close($ch);
    }

    private function _send_admin_purchase_to_discord_webhook($cart, $pembeli_username, $penerima_username = null, $is_first_time_buyer = false, $transaction_id = null, $has_currency_purchase = false, $has_non_currency_purchase = false) {
        $webhook_urls = $this->_get_store_discord_webhook_urls();
        if (empty($webhook_urls)) {
            return;
        }

        $buyer_name = $this->_sanitize_discord_text($pembeli_username);
        if ($buyer_name === '') {
            $buyer_name = 'Unknown Player';
        }

        $recipient_name = $this->_sanitize_discord_text($penerima_username);
        if ($recipient_name !== '' && strcasecmp($recipient_name, $buyer_name) === 0) {
            $recipient_name = '';
        }

        $notification_meta = $this->_build_discord_notification_meta(
            $cart,
            $buyer_name,
            $recipient_name,
            $has_currency_purchase,
            $has_non_currency_purchase
        );

        $fields = [
            [
                'name' => 'Jenis Transaksi',
                'value' => '`' . $notification_meta['type_label'] . '`',
                'inline' => true
            ],
            [
                'name' => 'Total',
                'value' => '`Rp ' . number_format((float) ($cart['grand_total'] ?? 0), 0, ',', '.') . '`',
                'inline' => true
            ],
        ];

        if (!empty($transaction_id)) {
            $fields[] = [
                'name' => 'ID Transaksi',
                'value' => '`#' . (int) $transaction_id . '`',
                'inline' => true
            ];
        }

        if ($recipient_name !== '') {
            $fields[] = [
                'name' => 'Penerima',
                'value' => '`' . $recipient_name . '`',
                'inline' => true
            ];
        }

        if ($is_first_time_buyer) {
            $fields[] = [
                'name' => 'Status',
                'value' => '`First Purchase`',
                'inline' => true
            ];
        }

        $fields[] = [
            'name' => 'Item yang Dibeli',
            'value' => $this->_build_discord_item_lines($cart),
            'inline' => false
        ];

        $fields[] = [
            'name' => 'Ringkasan Pembayaran',
            'value' => $this->_build_discord_payment_summary($cart),
            'inline' => false
        ];

        $payload = [
            'username' => 'Server MineHive',
            'avatar_url' => base_url('assets/images/favicon.png'),
            'allowed_mentions' => ['parse' => []],
            'embeds' => [[
                'author' => [
                    'name' => $buyer_name . ($is_first_time_buyer ? ' | First Purchase' : ''),
                    'icon_url' => $this->_get_minecraft_head_url($buyer_name, 64)
                ],
                'title' => $notification_meta['title'],
                'description' => $notification_meta['description'],
                'color' => $notification_meta['color'],
                'thumbnail' => [
                    'url' => $this->_get_minecraft_head_url($notification_meta['thumbnail_username'], 128)
                ],
                'fields' => $fields,
                'footer' => [
                    'text' => 'Server MineHive' . (!empty($transaction_id) ? ' | Transaction #' . (int) $transaction_id : '')
                ],
                'timestamp' => date('c')
            ]]
        ];

        foreach ($webhook_urls as $webhook_url) {
            $this->_post_to_discord_webhook($webhook_url, $payload);
        }
    }

    private function _build_public_support_purchase_summary($cart) {
        $items = [];

        foreach (($cart['items'] ?? []) as $item) {
            $item_name = str_replace(' (Upgrade)', '', $this->_sanitize_discord_text($item['name'] ?? ''));
            if ($item_name === '') {
                $item_name = 'Item Tidak Dikenal';
            }

            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            $items[] = '(' . $quantity . 'x) ' . $item_name;
        }

        return !empty($items) ? implode(', ', $items) : 'something awesome';
    }

    private function _send_public_support_to_discord_webhook($cart, $pembeli_username) {
        $webhook_url = $this->_get_public_support_discord_webhook_url();
        if ($webhook_url === '') {
            return;
        }

        $buyer_name = $this->_sanitize_discord_text($pembeli_username);
        if ($buyer_name === '') {
            $buyer_name = 'Unknown Player';
        }

        $purchase_summary = $this->_build_public_support_purchase_summary($cart);
        $store_url = rtrim((string) base_url(), '/');

        $payload = [
            'username' => $this->_get_public_support_discord_username(),
            'avatar_url' => $this->_get_public_support_discord_avatar_url(),
            'allowed_mentions' => ['parse' => []],
            'embeds' => [[
                'author' => [
                    'name' => $buyer_name,
                    'icon_url' => $this->_get_minecraft_head_url($buyer_name, 64)
                ],
                'title' => 'Thank you for the support!',
                'description' => 'Thank you **' . $buyer_name . '** for buying ' . $purchase_summary . '! Your support is the only reason we can do amazing updates! <:love_orange:1481312433653153905>',
                'color' => 15548997,
                'thumbnail' => [
                    'url' => $this->_get_minecraft_head_url($buyer_name, 128)
                ],
                'footer' => [
                    'text' => 'Visit the store at ' . $store_url
                ],
                'timestamp' => date('c')
            ]]
        ];

        $this->_post_to_discord_webhook($webhook_url, $payload);
    }

    private function _send_to_discord_webhook($cart, $pembeli_username, $penerima_username = null, $is_first_time_buyer = false, $transaction_id = null, $has_currency_purchase = false, $has_non_currency_purchase = false) {
        $this->_send_admin_purchase_to_discord_webhook(
            $cart,
            $pembeli_username,
            $penerima_username,
            $is_first_time_buyer,
            $transaction_id,
            $has_currency_purchase,
            $has_non_currency_purchase
        );

        $this->_send_public_support_to_discord_webhook($cart, $pembeli_username);
    }

    /**
     * Menampilkan halaman notifikasi sukses.
     */
    public function success() {
        $transaction_id = (int) $this->input->get('trx');
        if ($transaction_id <= 0) {
            $transaction_id = (int) $this->session->userdata('last_checkout_transaction_id');
        }

        $transaction = $this->_get_transaction_for_current_user($transaction_id);

        $this->session->unset_userdata('cart');
        $data['title'] = 'Payment Status | MineHive';
        $data['meta_description'] = 'Pembayaran MineHive sedang kami cek. Buka status transaksi dan tunggu konfirmasi otomatis setelah invoice selesai diproses.';
        $data['transaction_id'] = $transaction_id;
        $data['status_payload'] = $transaction ? $this->_build_transaction_status_payload($transaction) : null;

        $this->load->view('templates/header', $data);
        $this->load->view('payment/success_view', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Menampilkan halaman notifikasi gagal.
     */
    public function failed() {
        $transaction_id = (int) $this->input->get('trx');
        if ($transaction_id <= 0) {
            $transaction_id = (int) $this->session->userdata('last_checkout_transaction_id');
        }

        $data['title'] = 'Payment Failed | MineHive';
        $data['meta_description'] = 'Pembayaran belum berhasil diproses. Cek kembali detail transaksi MineHive untuk melanjutkan pembayaran atau melihat status terbaru.';
        $data['transaction_id'] = $transaction_id;

        $this->load->view('templates/header', $data);
        $this->load->view('payment/failed_view', $data);
        $this->load->view('templates/footer');
    }

    public function check_status($transaction_id = null) {
        header('Content-Type: application/json');

        $transaction = $this->_get_transaction_for_current_user($transaction_id);
        if (!$transaction) {
            echo json_encode([
                'status' => 'not_found',
                'message' => 'Transaksi tidak ditemukan.'
            ]);
            return;
        }

        echo json_encode($this->_build_transaction_status_payload($transaction), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

}
