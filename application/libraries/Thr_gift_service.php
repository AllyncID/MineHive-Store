<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Thr_gift_service {

    protected $CI;

    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->model('Store_model');
        $this->CI->load->model('Settings_model');
    }

    public function parse_allocations_json($raw_json) {
        $decoded = json_decode((string) $raw_json, true);
        if (!is_array($decoded)) {
            return ['error' => 'Format data THR tidak valid.'];
        }

        $merged_allocations = [];
        $allocation_count = 0;

        foreach ($decoded as $row) {
            if (!is_array($row)) {
                continue;
            }

            $username = trim((string) ($row['username'] ?? ''));
            $quantity = (int) ($row['quantity'] ?? 0);

            if ($username === '' || $quantity < 1) {
                continue;
            }

            $username = str_replace(["\r", "\n"], '', $username);
            if (strlen($username) > 32) {
                return ['error' => 'Username penerima THR terlalu panjang (maks 32 karakter).'];
            }

            if (!isset($merged_allocations[$username])) {
                $merged_allocations[$username] = 0;
                $allocation_count++;
            }

            $merged_allocations[$username] += $quantity;

            if ($allocation_count > 50) {
                return ['error' => 'Maksimal 50 penerima untuk sekali THR.'];
            }
        }

        if (empty($merged_allocations)) {
            return ['error' => 'Daftar penerima THR kosong atau tidak valid.'];
        }

        $normalized = [];
        $total_quantity = 0;

        foreach ($merged_allocations as $username => $quantity) {
            $normalized[] = [
                'username' => $username,
                'quantity' => (int) $quantity
            ];
            $total_quantity += (int) $quantity;
        }

        return [
            'allocations' => $normalized,
            'total_quantity' => $total_quantity
        ];
    }

    public function is_thr_gift_cart($cart) {
        return is_array($cart)
            && !empty($cart['thr_gift'])
            && !empty($cart['thr_gift']['enabled'])
            && !empty($cart['thr_gift']['allocations']);
    }

    public function create_thr_cart_from_product($product, array $allocations) {
        $product_array = is_object($product) ? (array) $product : (array) $product;
        $total_quantity = 0;

        foreach ($allocations as $allocation) {
            $total_quantity += (int) ($allocation['quantity'] ?? 0);
        }

        $unit_price = (float) ($product_array['price'] ?? 0);
        $item_name = (string) ($product_array['name'] ?? 'Unknown Product');
        $realm = (string) ($product_array['realm'] ?? '');

        $cart_item = [
            'id' => (int) ($product_array['id'] ?? 0),
            'name' => $item_name,
            'unit_price' => $unit_price,
            'price' => $unit_price * $total_quantity,
            'quantity' => $total_quantity,
            'realm' => $realm,
            'is_upgrade' => false,
            'is_flash_sale' => false,
            'original_price' => null
        ];

        return [
            'items' => [
                $cart_item['id'] => $cart_item
            ],
            'subtotal' => $cart_item['price'],
            'cart_discount' => 0,
            'applied_cart_discount_tier' => null,
            'promo_discount' => 0,
            'referral_discount' => 0,
            'applied_promo' => null,
            'applied_referral' => null,
            'grand_total' => $cart_item['price'],
            'thr_gift' => [
                'enabled' => 1,
                'item_id' => $cart_item['id'],
                'item_name' => $item_name,
                'total_quantity' => $total_quantity,
                'total_players' => count($allocations),
                'allocations' => array_values($allocations)
            ]
        ];
    }

    public function execute_thr_gift($cart, $sender_username, $grand_total, array $options = []) {
        $settings = $this->CI->Settings_model->get_all_settings();
        $logger = isset($options['logger']) && is_callable($options['logger'])
            ? $options['logger']
            : function ($message) {
            };

        if (!$this->is_thr_gift_cart($cart)) {
            $logger('Cart bukan THR gift. Tidak ada command yang dijalankan.');
            return [
                'success' => false,
                'message' => 'Cart bukan THR gift.',
                'logs' => [],
                'warnings' => []
            ];
        }

        $thr_data = $cart['thr_gift'];
        $thr_item = $this->resolve_thr_item($cart, $thr_data);

        if (!$thr_item) {
            $logger('Item THR tidak ditemukan di keranjang.');
            return [
                'success' => false,
                'message' => 'Item THR tidak ditemukan di keranjang.',
                'logs' => [],
                'warnings' => []
            ];
        }

        $product = $this->CI->Store_model->get_product_by_id($thr_item['id']);
        if (!$product) {
            $logger('Produk THR tidak ditemukan di database.');
            return [
                'success' => false,
                'message' => 'Produk THR tidak ditemukan.',
                'logs' => [],
                'warnings' => []
            ];
        }

        $realm_to_use = trim((string) ($thr_item['realm'] ?? $product['realm'] ?? ''));
        $server_id = $this->CI->config->item('pterodactyl_server_id_' . strtolower($realm_to_use));
        if (empty($server_id)) {
            $logger("Server ID untuk realm '{$realm_to_use}' tidak ditemukan.");
            return [
                'success' => false,
                'message' => "Server ID untuk realm '{$realm_to_use}' tidak ditemukan.",
                'logs' => [],
                'warnings' => []
            ];
        }

        $clean_item_name = str_replace(["\r", "\n"], ' ', (string) ($thr_data['item_name'] ?? $thr_item['name'] ?? $product['name']));
        $grand_total_value = (int) round((float) $grand_total);
        $broadcast_enabled = ($settings['thr_gift_broadcast_enabled'] ?? $settings['thr_broadcast_enabled'] ?? '1') === '1';
        $broadcast_template = trim((string) ($settings['thr_gift_broadcast_command'] ?? $settings['thr_broadcast_command'] ?? ''));

        $logs = [];
        $warnings = [];

        foreach ($thr_data['allocations'] as $allocation) {
            $recipient = trim((string) ($allocation['username'] ?? ''));
            $quantity = (int) ($allocation['quantity'] ?? 0);

            if ($recipient === '' || $quantity < 1) {
                continue;
            }

            $context = [
                '{username}' => $recipient,
                '{recipient}' => $recipient,
                '{sender}' => $sender_username,
                '{item_name}' => $clean_item_name,
                '{quantity}' => $quantity,
                '{total_quantity}' => (int) ($thr_data['total_quantity'] ?? $quantity),
                '{total_players}' => (int) ($thr_data['total_players'] ?? 1),
                '{grand_total}' => $grand_total_value
            ];

            $command_result = $this->dispatch_product_command($product, $server_id, $context, $logger);
            $logs = array_merge($logs, $command_result['logs']);

            if (!$command_result['success']) {
                return [
                    'success' => false,
                    'message' => 'Eksekusi command THR gagal untuk ' . $recipient . '.',
                    'logs' => $logs,
                    'warnings' => $warnings
                ];
            }

            if ($broadcast_enabled && $broadcast_template !== '') {
                $broadcast_command = strtr($broadcast_template, $context);
                $logger("Broadcast THR ke {$recipient}: {$broadcast_command}");
                if ($this->send_pterodactyl_command($broadcast_command, $server_id)) {
                    $logs[] = "[Broadcast] {$broadcast_command}";
                } else {
                    $warnings[] = "Broadcast THR gagal untuk {$recipient}.";
                    $logger("Broadcast THR gagal untuk {$recipient}.");
                }
            }
        }

        return [
            'success' => true,
            'message' => 'THR gift berhasil dieksekusi.',
            'logs' => $logs,
            'warnings' => $warnings
        ];
    }

    protected function resolve_thr_item(array $cart, array $thr_data) {
        $item_id = (int) ($thr_data['item_id'] ?? 0);

        foreach ($cart['items'] ?? [] as $item) {
            if ((int) ($item['id'] ?? 0) === $item_id) {
                return $item;
            }
        }

        if (!empty($cart['items'])) {
            return reset($cart['items']);
        }

        return null;
    }

    protected function dispatch_product_command(array $product, $server_id, array $context, callable $logger) {
        $command_template = (string) ($product['command'] ?? '');
        $logs = [];

        if ($command_template === '') {
            $logger('Command produk kosong, eksekusi dibatalkan.');
            return [
                'success' => false,
                'logs' => $logs
            ];
        }

        $allocation_qty = (int) ($context['{quantity}'] ?? 1);

        if (strpos($command_template, '{quantity}') !== false) {
            $command = strtr($command_template, $context);
            $logger("Kirim command bulk: {$command}");

            if (!$this->send_pterodactyl_command($command, $server_id)) {
                return [
                    'success' => false,
                    'logs' => $logs
                ];
            }

            $logs[] = $command;
            return [
                'success' => true,
                'logs' => $logs
            ];
        }

        for ($i = 0; $i < $allocation_qty; $i++) {
            $command_context = $context;
            $command_context['{quantity}'] = 1;
            $command = strtr($command_template, $command_context);
            $logger("Kirim command loop " . ($i + 1) . "/{$allocation_qty}: {$command}");

            if (!$this->send_pterodactyl_command($command, $server_id)) {
                return [
                    'success' => false,
                    'logs' => $logs
                ];
            }

            $logs[] = $command;
        }

        return [
            'success' => true,
            'logs' => $logs
        ];
    }

    protected function send_pterodactyl_command($command, $server_id) {
        $panel_url = $this->CI->config->item('pterodactyl_panel_url');
        $api_key = $this->CI->config->item('pterodactyl_api_key');

        if (empty($panel_url) || empty($api_key) || empty($server_id)) {
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

        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $http_code === 204;
    }
}
