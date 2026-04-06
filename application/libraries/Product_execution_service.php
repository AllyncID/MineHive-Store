<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Product_execution_service {

    protected $CI;

    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->model('Store_model');
        $this->CI->load->library('Pterodactyl_service');
    }

    public function execute_product_purchase($product, $username, $quantity = 1, array $options = []) {
        $product = $this->normalize_product($product);
        $username = $this->sanitize_username($username);
        $quantity = max(1, (int) $quantity);
        $dry_run = !empty($options['dry_run']);
        $include_donation_counter = !empty($options['include_donation_counter']);
        $grand_total = array_key_exists('grand_total', $options)
            ? max(0, (int) round((float) $options['grand_total']))
            : max(0, (int) round(((float) ($product['price'] ?? 0)) * $quantity));

        if (empty($product) || empty($product['id'])) {
            return [
                'success' => false,
                'message' => 'Produk tidak valid untuk dieksekusi.',
                'executed_commands' => [],
                'warnings' => []
            ];
        }

        if ($username === '') {
            return [
                'success' => false,
                'message' => 'Nickname target wajib diisi.',
                'executed_commands' => [],
                'warnings' => []
            ];
        }

        $warnings = [];
        $visited_bundle_ids = [];
        $resolved_products = [];

        $this->expand_product_for_execution($product, $quantity, $resolved_products, $visited_bundle_ids, $warnings);

        if (empty($resolved_products)) {
            return [
                'success' => false,
                'message' => 'Produk ini tidak menghasilkan command yang bisa dites.',
                'executed_commands' => [],
                'warnings' => $warnings
            ];
        }

        $executed_commands = [];
        $has_currency_purchase = false;

        foreach ($resolved_products as $resolved_item) {
            $resolved_product = $this->normalize_product($resolved_item['product'] ?? []);
            $resolved_quantity = max(1, (int) ($resolved_item['quantity'] ?? 1));
            $product_name = (string) ($resolved_product['name'] ?? 'Unknown Product');

            $is_currency = $this->is_currency_product($resolved_product);
            if ($is_currency) {
                $has_currency_purchase = true;
            }

            $server_meta = $this->resolve_target_server($resolved_product, $is_currency);
            if (empty($server_meta['server_id'])) {
                $warnings[] = 'Server ID untuk produk "' . $product_name . '" belum dikonfigurasi.';
                return [
                    'success' => false,
                    'message' => 'Server tujuan untuk produk "' . $product_name . '" belum dikonfigurasi.',
                    'executed_commands' => $executed_commands,
                    'warnings' => $warnings
                ];
            }

            $commands_to_run = $this->extract_commands_for_product($resolved_product);
            if (empty($commands_to_run)) {
                $warnings[] = 'Produk "' . $product_name . '" tidak punya command yang bisa dijalankan.';
                continue;
            }

            foreach ($commands_to_run as $raw_command) {
                if (strpos($raw_command, '{quantity}') !== false) {
                    $command = $this->replace_command_placeholders($raw_command, $username, $grand_total, $resolved_quantity);

                    if (!$dry_run && !$this->CI->pterodactyl_service->send_command($command, $server_meta['server_id'])) {
                        return [
                            'success' => false,
                            'message' => 'Gagal mengirim command untuk produk "' . $product_name . '".',
                            'executed_commands' => $executed_commands,
                            'warnings' => $warnings
                        ];
                    }

                    $executed_commands[] = [
                        'product_name' => $product_name,
                        'server_name' => $server_meta['server_name'],
                        'server_id' => $server_meta['server_id'],
                        'command' => $command
                    ];
                    continue;
                }

                for ($i = 0; $i < $resolved_quantity; $i++) {
                    $command = $this->replace_command_placeholders($raw_command, $username, $grand_total, 1);

                    if (!$dry_run && !$this->CI->pterodactyl_service->send_command($command, $server_meta['server_id'])) {
                        return [
                            'success' => false,
                            'message' => 'Gagal mengirim command untuk produk "' . $product_name . '".',
                            'executed_commands' => $executed_commands,
                            'warnings' => $warnings
                        ];
                    }

                    $executed_commands[] = [
                        'product_name' => $product_name,
                        'server_name' => $server_meta['server_name'],
                        'server_id' => $server_meta['server_id'],
                        'command' => $command
                    ];
                }
            }
        }

        if ($include_donation_counter && $has_currency_purchase) {
            $this->execute_donation_counter($grand_total, $dry_run, $warnings, $executed_commands);
        }

        if (empty($executed_commands)) {
            return [
                'success' => false,
                'message' => 'Tidak ada command yang dijalankan untuk produk ini.',
                'executed_commands' => [],
                'warnings' => $warnings
            ];
        }

        return [
            'success' => true,
            'message' => 'Berhasil menjalankan ' . count($executed_commands) . ' command test.',
            'executed_commands' => $executed_commands,
            'warnings' => $warnings,
            'grand_total' => $grand_total,
            'resolved_items' => count($resolved_products)
        ];
    }

    protected function normalize_product($product) {
        if (is_object($product)) {
            return (array) $product;
        }

        return is_array($product) ? $product : [];
    }

    protected function sanitize_username($username) {
        $username = trim((string) $username);
        $username = str_replace(["\r", "\n"], '', $username);
        return $username;
    }

    protected function split_command_lines($raw_command) {
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

    protected function is_legacy_bundle_item_list($data) {
        if (!is_array($data) || empty($data)) {
            return false;
        }

        if (array_keys($data) !== range(0, count($data) - 1)) {
            return false;
        }

        foreach ($data as $item_id) {
            if (!is_scalar($item_id) || !ctype_digit((string) $item_id)) {
                return false;
            }
        }

        return true;
    }

    protected function expand_product_for_execution(array $product, $quantity, array &$resolved_products, array &$visited_bundle_ids, array &$warnings) {
        $product_type = strtolower(trim((string) ($product['product_type'] ?? '')));
        $description = json_decode($product['description'] ?? '', true);

        if ($product_type === 'bundles' && $this->is_legacy_bundle_item_list($description)) {
            $bundle_id = (int) ($product['id'] ?? 0);

            if ($bundle_id > 0 && isset($visited_bundle_ids[$bundle_id])) {
                $warnings[] = 'Loop bundle terdeteksi pada produk #' . $bundle_id . '.';
                return;
            }

            if ($bundle_id > 0) {
                $visited_bundle_ids[$bundle_id] = true;
            }

            foreach ($description as $child_product_id) {
                $child_product = $this->CI->Store_model->get_product_by_id((int) $child_product_id);
                if (!$child_product) {
                    $warnings[] = 'Produk child #' . (int) $child_product_id . ' tidak ditemukan untuk bundle "' . ($product['name'] ?? 'Unknown Bundle') . '".';
                    continue;
                }

                $this->expand_product_for_execution($child_product, $quantity, $resolved_products, $visited_bundle_ids, $warnings);
            }

            if ($bundle_id > 0) {
                unset($visited_bundle_ids[$bundle_id]);
            }

            return;
        }

        $resolved_products[] = [
            'product' => $product,
            'quantity' => max(1, (int) $quantity)
        ];
    }

    protected function extract_commands_for_product(array $product) {
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
            foreach ($this->split_command_lines($product['command'] ?? '') as $command_line) {
                $commands[] = $command_line;
            }
        }

        return $commands;
    }

    protected function is_currency_product(array $product) {
        $product_type = strtolower(trim((string) ($product['product_type'] ?? '')));
        $product_category = strtolower(trim((string) ($product['category'] ?? '')));

        if ($product_type === 'bucks_kaget') {
            return false;
        }

        return $product_type === 'currency' || $product_category === 'currency';
    }

    protected function resolve_target_server(array $product, $is_currency = false) {
        if ($is_currency) {
            return [
                'server_id' => trim((string) $this->CI->config->item('pterodactyl_server_id_proxy')),
                'server_name' => 'Proxy'
            ];
        }

        $realm = strtolower(trim((string) ($product['realm'] ?? '')));

        return [
            'server_id' => trim((string) $this->CI->config->item('pterodactyl_server_id_' . $realm)),
            'server_name' => $this->format_realm_name($realm)
        ];
    }

    protected function replace_command_placeholders($command, $username, $grand_total, $quantity) {
        return str_replace(
            ['{username}', '{grand_total}', '{quantity}'],
            [$username, (int) $grand_total, (int) $quantity],
            (string) $command
        );
    }

    protected function execute_donation_counter($grand_total, $dry_run, array &$warnings, array &$executed_commands) {
        $donation_command = 'donations add ' . (int) $grand_total;

        foreach (['survival', 'skyblock', 'oneblock'] as $donation_realm) {
            $server_id = trim((string) $this->CI->config->item('pterodactyl_server_id_' . $donation_realm));
            if ($server_id === '') {
                $warnings[] = 'Donation counter dilewati untuk realm ' . $this->format_realm_name($donation_realm) . ' karena server ID kosong.';
                continue;
            }

            if (!$dry_run && !$this->CI->pterodactyl_service->send_command($donation_command, $server_id)) {
                $warnings[] = 'Donation counter gagal dikirim ke realm ' . $this->format_realm_name($donation_realm) . '.';
                continue;
            }

            $executed_commands[] = [
                'product_name' => 'Currency Donation Counter',
                'server_name' => $this->format_realm_name($donation_realm),
                'server_id' => $server_id,
                'command' => $donation_command
            ];
        }
    }

    protected function format_realm_name($realm) {
        $realm = strtolower(trim((string) $realm));
        $realm_names = [
            'survival' => 'Survival',
            'skyblock' => 'Skyblock',
            'acidisland' => 'AcidIsland',
            'oneblock' => 'OneBlock'
        ];

        return $realm_names[$realm] ?? ucfirst($realm);
    }
}
