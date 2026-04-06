<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Products extends Admin_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Product_model');
        $this->load->model('Store_model');
        $this->load->library('Product_execution_service');
    }

    private function _normalize_product_type($raw_value) {
        $product_type = strtolower(trim((string) $raw_value));
        $allowed_types = ['rank', 'currency', 'bucks_kaget', 'bundles', 'battlepass'];

        return in_array($product_type, $allowed_types, true) ? $product_type : 'rank';
    }

    private function _normalize_command_lines($raw_value) {
        $raw_text = trim((string) $raw_value);
        if ($raw_text === '') {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', $raw_text);
        $commands = [];

        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line !== '') {
                $commands[] = $line;
            }
        }

        return $commands;
    }

    private function _build_bundle_payload_from_post() {
        $bundle_mode = trim((string) $this->input->post('bundle_mode'));
        $bundle_ids = $this->input->post('bundle_product_ids') ?? [];

        if (!is_array($bundle_ids)) {
            $bundle_ids = [];
        }

        $bundle_ids = array_values(array_filter(array_map('intval', $bundle_ids), function($id) {
            return $id > 0;
        }));

        if ($bundle_mode === 'product_list' && !empty($bundle_ids)) {
            return [
                'description' => json_encode($bundle_ids),
                'command' => '',
                'luckperms_group' => NULL
            ];
        }

        $normalized_commands = $this->_normalize_command_lines($this->input->post('bundle_commands'));

        $bundle_config = [
            'bundle_mode' => 'custom_commands',
            'display_description' => (string) $this->input->post('description_bundle_custom'),
            'commands' => $normalized_commands
        ];

        return [
            'description' => json_encode($bundle_config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'command' => implode("\n", $normalized_commands),
            'luckperms_group' => NULL
        ];
    }

    private function _build_bucks_kaget_payload_from_post() {
        $default_total_bucks = max(1, (int) $this->input->post('bucks_kaget_default_total_bucks'));
        $min_total_bucks = max(1, (int) $this->input->post('bucks_kaget_min_total_bucks'));
        $max_total_bucks = max($default_total_bucks, (int) $this->input->post('bucks_kaget_max_total_bucks'));
        $default_recipients = max(1, (int) $this->input->post('bucks_kaget_default_recipients'));
        $min_recipients = max(1, (int) $this->input->post('bucks_kaget_min_recipients'));
        $max_recipients = max($default_recipients, (int) $this->input->post('bucks_kaget_max_recipients'));

        $default_expiry_hours = max(1, (int) $this->input->post('bucks_kaget_default_expiry_hours'));
        $min_expiry_hours = max(1, (int) $this->input->post('bucks_kaget_min_expiry_hours'));
        $max_expiry_hours = max($default_expiry_hours, (int) $this->input->post('bucks_kaget_max_expiry_hours'));

        $price_tiers = [];
        $raw_price_tiers = trim((string) $this->input->post('bucks_kaget_price_tiers'));
        if ($raw_price_tiers !== '') {
            $decoded_price_tiers = json_decode($raw_price_tiers, true);
            if (is_array($decoded_price_tiers)) {
                foreach ($decoded_price_tiers as $tier) {
                    if (!is_array($tier)) {
                        continue;
                    }

                    $min_qty = max(1, (int) ($tier['min_qty'] ?? 0));
                    $price_per_buck = max(1, (int) ($tier['price_per_buck'] ?? ($tier['price_per_level'] ?? 0)));
                    $price_tiers[$min_qty] = [
                        'min_qty' => $min_qty,
                        'price_per_buck' => $price_per_buck
                    ];
                }
            }
        }

        if (empty($price_tiers)) {
            $price_tiers = [
                1 => ['min_qty' => 1, 'price_per_buck' => 7000],
                10 => ['min_qty' => 10, 'price_per_buck' => 6850],
                25 => ['min_qty' => 25, 'price_per_buck' => 6700],
                50 => ['min_qty' => 50, 'price_per_buck' => 6550],
                100 => ['min_qty' => 100, 'price_per_buck' => 6400],
                200 => ['min_qty' => 200, 'price_per_buck' => 6250],
            ];
        }

        ksort($price_tiers);
        $price_tiers = array_values($price_tiers);

        $payload = [
            'display_description' => trim((string) $this->input->post('bucks_kaget_description')),
            'default_total_bucks' => min($max_total_bucks, max($min_total_bucks, $default_total_bucks)),
            'min_total_bucks' => min($max_total_bucks, $min_total_bucks),
            'max_total_bucks' => $max_total_bucks,
            'default_recipients' => min($max_recipients, max($min_recipients, $default_recipients)),
            'min_recipients' => min($max_recipients, $min_recipients),
            'max_recipients' => $max_recipients,
            'default_expiry_hours' => min($max_expiry_hours, max($min_expiry_hours, $default_expiry_hours)),
            'min_expiry_hours' => min($max_expiry_hours, $min_expiry_hours),
            'max_expiry_hours' => $max_expiry_hours,
            'price_tiers' => $price_tiers
        ];

        return [
            'description' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'command' => '',
            'luckperms_group' => null,
            'category' => 'currency',
            'realm' => 'global'
        ];
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
        $webhook_url = trim((string) $this->config->item('discord_webhook_url_support'));
        return $webhook_url !== '' ? [$webhook_url] : [];
    }

    private function _get_public_support_discord_username() {
        $username = trim((string) $this->config->item('discord_webhook_support_name'));
        return $username !== '' ? $username : 'Patreon';
    }

    private function _get_public_support_discord_avatar_url() {
        $avatar_url = trim((string) $this->config->item('discord_webhook_support_avatar_url'));
        return $avatar_url !== '' ? $avatar_url : 'https://i.imgur.com/BUS06N2.png';
    }

    private function _build_public_support_purchase_summary($product_name, $quantity = 1) {
        $clean_product_name = str_replace(' (Upgrade)', '', $this->_sanitize_discord_text($product_name));
        if ($clean_product_name === '') {
            $clean_product_name = 'Test Purchase';
        }

        return '(' . max(1, (int) $quantity) . 'x) ' . $clean_product_name;
    }

    private function _post_to_discord_webhook($webhook_url, array $payload) {
        $json_data = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json_data === false) {
            log_message('error', 'Admin Test Purchase Discord: Gagal encode payload JSON.');
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
            log_message('error', 'Admin Test Purchase Discord: Gagal kirim notifikasi. HTTP ' . $http_code . ' | Error: ' . $curl_error . ' | Response: ' . (string) $response);
            return false;
        }

        return true;
    }

    private function _send_test_purchase_discord_webhook($target_username, $product_name, $quantity = 1) {
        $webhook_urls = $this->_get_store_discord_webhook_urls();
        if (empty($webhook_urls)) {
            return;
        }

        $target_username = $this->_sanitize_discord_text($target_username);
        if ($target_username === '') {
            $target_username = 'Unknown Player';
        }

        $purchase_summary = $this->_build_public_support_purchase_summary($product_name, $quantity);
        $store_url = rtrim((string) base_url(), '/');

        $payload = [
            'username' => $this->_get_public_support_discord_username(),
            'avatar_url' => $this->_get_public_support_discord_avatar_url(),
            'allowed_mentions' => ['parse' => []],
            'embeds' => [[
                'author' => [
                    'name' => $target_username,
                    'icon_url' => $this->_get_minecraft_head_url($target_username, 64)
                ],
                'title' => 'Thank you for the support!',
                'description' => 'Thank you **' . $target_username . '** for buying ' . $purchase_summary . '! Your support is the only reason we can do amazing updates! <:love_orange:1481312433653153905>',
                'color' => 15548997,
                'thumbnail' => [
                    'url' => $this->_get_minecraft_head_url($target_username, 128)
                ],
                'footer' => [
                    'text' => 'Visit the store at ' . $store_url . ' | Test Purchase'
                ],
                'timestamp' => date('c')
            ]]
        ];

        foreach ($webhook_urls as $webhook_url) {
            $this->_post_to_discord_webhook($webhook_url, $payload);
        }
    }

    public function index() {
        $realm_filter = $this->input->get('realm', TRUE) ?: 'all';
        $data['products'] = $this->Product_model->get_all_products($realm_filter);
        $data['title'] = 'Manage Products';
        $data['current_filter'] = $realm_filter;
        $data['test_defaults'] = $this->session->userdata('admin_product_test_defaults') ?? [
            'product_id' => '',
            'target_username' => '',
            'quantity' => 1
        ];
        $data['selected_test_product'] = null;

        $selected_test_product_id = (int) ($data['test_defaults']['product_id'] ?? 0);
        if ($selected_test_product_id > 0) {
            foreach ($data['products'] as $product) {
                if ((int) $product->id === $selected_test_product_id) {
                    $data['selected_test_product'] = $product;
                    break;
                }
            }

            if ($data['selected_test_product'] === null) {
                $data['selected_test_product'] = $this->Product_model->get_by_id($selected_test_product_id);
            }
        }

        $data['test_purchase_result'] = $this->session->flashdata('test_purchase_result');
        
        $this->load->view('admin/templates/admin_header', $data);
        $this->load->view('admin/products/index', $data);
        $this->load->view('admin/templates/admin_footer');
    }

    public function test_purchase() {
        if (!$this->input->post()) {
            redirect('admin/products');
        }

        $product_id = (int) $this->input->post('product_id');
        $target_username = trim(str_replace(["\r", "\n"], '', (string) $this->input->post('target_username')));
        $quantity = max(1, min(99, (int) $this->input->post('quantity')));
        $return_realm = $this->input->post('return_realm', TRUE) ?: 'all';
        $allowed_realms = ['all', 'survival', 'skyblock', 'acidisland', 'oneblock'];

        if (!in_array($return_realm, $allowed_realms, true)) {
            $return_realm = 'all';
        }

        $this->session->set_userdata('admin_product_test_defaults', [
            'product_id' => $product_id,
            'target_username' => $target_username,
            'quantity' => $quantity
        ]);

        if ($product_id <= 0) {
            $this->session->set_flashdata('error', 'Pilih produk yang ingin dites terlebih dahulu.');
            redirect('admin/products?realm=' . $return_realm);
        }

        if ($target_username === '') {
            $this->session->set_flashdata('error', 'Nickname target wajib diisi.');
            redirect('admin/products?realm=' . $return_realm);
        }

        if (strlen($target_username) > 32) {
            $this->session->set_flashdata('error', 'Nickname target terlalu panjang. Maksimal 32 karakter.');
            redirect('admin/products?realm=' . $return_realm);
        }

        $product = $this->Store_model->get_product_by_id($product_id);
        if (!$product) {
            $this->session->set_flashdata('error', 'Produk yang dipilih tidak ditemukan.');
            redirect('admin/products?realm=' . $return_realm);
        }

        if ($this->Store_model->is_bucks_kaget_product($product)) {
            $this->session->set_flashdata('error', 'Produk Bucks Kaget tidak memakai test purchase live. Produk ini baru membuat link claim setelah checkout + webhook pembayaran berhasil.');
            redirect('admin/products?realm=' . $return_realm);
        }

        $execution_result = $this->product_execution_service->execute_product_purchase(
            $product,
            $target_username,
            $quantity,
            [
                'grand_total' => ((float) ($product['price'] ?? 0)) * $quantity,
                'include_donation_counter' => false
            ]
        );

        $execution_result['product_id'] = $product_id;
        $execution_result['product_name'] = $product['name'] ?? ('Product #' . $product_id);
        $execution_result['target_username'] = $target_username;
        $execution_result['quantity'] = $quantity;
        $execution_result['grand_total'] = $execution_result['grand_total'] ?? (((float) ($product['price'] ?? 0)) * $quantity);
        $execution_result['warnings'] = $execution_result['warnings'] ?? [];
        $execution_result['executed_commands'] = $execution_result['executed_commands'] ?? [];

        if ($execution_result['success']) {
            $this->_send_test_purchase_discord_webhook($target_username, $execution_result['product_name'], $quantity);
            $this->session->set_flashdata('success', 'Test purchase berhasil dijalankan untuk "' . $execution_result['product_name'] . '".');
        } else {
            $this->session->set_flashdata('error', $execution_result['message'] ?? 'Test purchase gagal dijalankan.');
        }

        $this->session->set_flashdata('test_purchase_result', $execution_result);
        redirect('admin/products?realm=' . $return_realm);
    }

    public function add() {
        if ($this->input->post()) {
            
            // --- [PERBAIKAN LOGIKA] ---
            // Ambil data spesifik berdasarkan tipe produk
            $product_type = $this->_normalize_product_type($this->input->post('product_type'));
            
            $data = [
                'name' => $this->input->post('name'),
                'price' => $this->input->post('price'),
                'original_price' => $this->input->post('original_price'),
                'realm' => $this->input->post('realm'),
                'product_type' => $product_type,
                'category' => $this->input->post('category'), // [TAMBAHAN] Ambil kategori
                'command' => $this->input->post('command'),
                'image_url' => $this->input->post('image_url'),
                'is_active' => $this->input->post('is_active')
            ];

            // Atur 'description' dan 'luckperms_group' berdasarkan tipe
            if ($product_type == 'rank') {
                $data['description'] = $this->input->post('description_perks');
                $data['luckperms_group'] = $this->input->post('luckperms_group');
            } elseif ($product_type == 'currency') {
                $data['description'] = $this->input->post('description_simple');
                $data['luckperms_group'] = NULL;
            } elseif ($product_type == 'bucks_kaget') {
                $bucks_kaget_payload = $this->_build_bucks_kaget_payload_from_post();
                $data['description'] = $bucks_kaget_payload['description'];
                $data['command'] = $bucks_kaget_payload['command'];
                $data['luckperms_group'] = $bucks_kaget_payload['luckperms_group'];
                $data['category'] = $bucks_kaget_payload['category'];
                $data['realm'] = $bucks_kaget_payload['realm'];
            } elseif ($product_type == 'bundles') {
                $bundle_payload = $this->_build_bundle_payload_from_post();
                $data['description'] = $bundle_payload['description'];
                $data['command'] = $bundle_payload['command'];
                $data['luckperms_group'] = $bundle_payload['luckperms_group'];
            } elseif ($product_type == 'battlepass') {
                // [BARU] Ambil data JSON dari textarea battlepass
                $data['description'] = $this->input->post('description_battlepass');
                $data['luckperms_group'] = NULL;
            } else {
                $data['description'] = $this->input->post('description_simple'); // Fallback
                $data['luckperms_group'] = NULL;
            }
            // --- [AKHIR PERBAIKAN LOGIKA] ---
            
            $this->Product_model->insert($data);
            
            $this->session->set_flashdata('success', 'Produk berhasil ditambahkan.');
            redirect('admin/products');
        }
        
        $data['title'] = 'Tambah Produk Baru';
        // --- [PERBAIKAN DI SINI] ---
        // Kita perlu mengambil semua produk agar bisa ditampilkan di dropdown "Isi Bundle"
        $data['all_products'] = $this->Product_model->get_all_products();
        // --- [AKHIR PERBAIKAN] ---
        
        $this->load->view('admin/templates/admin_header', $data);
        $this->load->view('admin/products/form', $data);
        $this->load->view('admin/templates/admin_footer');
    }

    public function edit($id) {
        if ($this->input->post()) {

            // --- [PERBAIKAN LOGIKA] ---
            // Ambil data spesifik berdasarkan tipe produk
            $product_type = $this->_normalize_product_type($this->input->post('product_type'));

            $data = [
                'name' => $this->input->post('name'),
                'price' => $this->input->post('price'),
                'original_price' => $this->input->post('original_price'),
                'realm' => $this->input->post('realm'),
                'product_type' => $product_type,
                'category' => $this->input->post('category'), // [TAMBAHAN] Ambil kategori
                'command' => $this->input->post('command'),
                'image_url' => $this->input->post('image_url'),
                'is_active' => $this->input->post('is_active')
            ];

            // Atur 'description' dan 'luckperms_group' berdasarkan tipe
            if ($product_type == 'rank') {
                $data['description'] = $this->input->post('description_perks');
                $data['luckperms_group'] = $this->input->post('luckperms_group');
            } elseif ($product_type == 'currency') {
                $data['description'] = $this->input->post('description_simple');
                $data['luckperms_group'] = NULL;
            } elseif ($product_type == 'bucks_kaget') {
                $bucks_kaget_payload = $this->_build_bucks_kaget_payload_from_post();
                $data['description'] = $bucks_kaget_payload['description'];
                $data['command'] = $bucks_kaget_payload['command'];
                $data['luckperms_group'] = $bucks_kaget_payload['luckperms_group'];
                $data['category'] = $bucks_kaget_payload['category'];
                $data['realm'] = $bucks_kaget_payload['realm'];
            } elseif ($product_type == 'bundles') {
                $bundle_payload = $this->_build_bundle_payload_from_post();
                $data['description'] = $bundle_payload['description'];
                $data['command'] = $bundle_payload['command'];
                $data['luckperms_group'] = $bundle_payload['luckperms_group'];
            } elseif ($product_type == 'battlepass') {
                // [BARU] Ambil data JSON dari textarea battlepass
                $data['description'] = $this->input->post('description_battlepass');
                $data['luckperms_group'] = NULL;
            } else {
                $data['description'] = $this->input->post('description_simple'); // Fallback
                $data['luckperms_group'] = NULL;
            }
            // --- [AKHIR PERBAIKAN LOGIKA] ---
            
            $this->Product_model->update($id, $data);
            $this->session->set_flashdata('success', 'Produk berhasil diperbarui.');
            redirect('admin/products');
        }

        $data['product'] = $this->Product_model->get_by_id($id);
        $data['title'] = 'Edit Produk';
        
        // --- [PERBAIKAN DI SINI] ---
        // Kita perlu mengambil semua produk agar bisa ditampilkan di dropdown "Isi Bundle"
        $data['all_products'] = $this->Product_model->get_all_products();
        // --- [AKHIR PERBAIKAN] ---
        
        $this->load->view('admin/templates/admin_header', $data);
        $this->load->view('admin/products/form', $data);
        $this->load->view('admin/templates/admin_footer');
    }

    public function delete($id) {
        $this->Product_model->delete($id);
        $this->session->set_flashdata('success', 'Produk berhasil dihapus.');
        redirect('admin/products');
    }

    public function duplicate($id) {
        // Panggil fungsi duplicate di model
        $new_product_id = $this->Product_model->duplicate($id);

        if ($new_product_id) {
            // Jika berhasil, beri pesan sukses
            $this->session->set_flashdata('success', 'Produk berhasil diduplikasi. Anda bisa mengedit salinannya sekarang.');
        } else {
            // Jika gagal, beri pesan error
            $this->session->set_flashdata('error', 'Gagal menduplikasi produk.');
        }

        // Arahkan kembali ke halaman daftar produk
        redirect('admin/products');
    }
}
