<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cart extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->model('Store_model');
        $this->load->model('Promo_model');
        $this->load->model('Discount_model');
        $this->load->model('Product_model');
        $this->load->model('Cart_discount_model'); 
        $this->load->model('Settings_model');      
        $this->load->model('Affiliate_model');
        $this->load->model('Transaction_model');
        $this->load->model('Featured_model'); 
        $this->load->model('User_model');
        $this->config->load('ranks', TRUE);
        $this->load->helper('url');
        $this->load->library('user_agent');
    }

    // =======================================================================
    // PRIVATE HELPER FUNCTIONS
    // =======================================================================

    private function _get_cart_from_session() {
        return $this->session->userdata('cart') ?? [
            'items' => [],
            'subtotal' => 0,
            'cart_discount' => 0,
            'applied_cart_discount_tier' => null,
            'promo_discount' => 0,
            'referral_discount' => 0, 
            'applied_promo' => null,
            'applied_referral' => null,
            'grand_total' => 0
        ];
    }

    private function _is_valid_item_list($data) {
        if (!is_array($data)) return false;
        if (empty($data)) return false;
        if (array_keys($data) !== range(0, count($data) - 1)) return false;
        foreach ($data as $item_id) {
            if (!is_scalar($item_id) || !ctype_digit((string) $item_id)) {
                return false;
            }
        }
        return true;
    }

    private function _format_realm_name($realm) {
        $realm_key = strtolower((string) $realm);
        $realm_names = [
            'survival' => 'Survival',
            'skyblock' => 'Skyblock',
            'acidisland' => 'AcidIsland',
            'oneblock' => 'OneBlock',
            'global' => 'Global',
        ];

        return $realm_names[$realm_key] ?? ucwords(str_replace(['_', '-'], ' ', (string) $realm));
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

        $price_per_buck_final = $price_per_buck_raw * $discount_ratio;
        $final_total_price = (int) round($total_bucks * $price_per_buck_final);
        $original_total_price = (int) round($total_bucks * $price_per_buck_raw);

        return [
            'config' => $config,
            'total_bucks' => $total_bucks,
            'tier_min_qty' => $tier_min_qty,
            'price_per_buck_raw' => $price_per_buck_raw,
            'price_per_buck_final' => $price_per_buck_final,
            'final_total_price' => $final_total_price,
            'original_total_price' => $original_total_price,
            'total_savings' => max(0, $original_total_price - $final_total_price)
        ];
    }

    private function _normalize_bucks_kaget_form_input($product, array $submitted_form = [], array $existing_form = [], $strict = false) {
        $config = $this->Store_model->get_bucks_kaget_config($product);
        if (!$config) {
            return [
                'valid' => false,
                'message' => 'Konfigurasi Bucks Kaget tidak ditemukan.',
                'form' => null,
                'config' => null
            ];
        }

        $default_name = $this->_get_bucks_kaget_default_name();
        $name = trim((string) ($submitted_form['name'] ?? ($existing_form['name'] ?? '')));
        if ($name === '') {
            $name = $default_name;
        }

        if (strlen($name) > 120) {
            if ($strict) {
                return [
                    'valid' => false,
                    'message' => 'Nama Bucks Kaget maksimal 120 karakter.',
                    'form' => null,
                    'config' => $config
                ];
            }

            $name = substr($name, 0, 120);
        }

        $raw_total_bucks = array_key_exists('total_bucks', $submitted_form)
            ? (int) $submitted_form['total_bucks']
            : (int) ($existing_form['total_bucks'] ?? $config['default_total_bucks']);
        if ($strict && ($raw_total_bucks < $config['min_total_bucks'] || $raw_total_bucks > $config['max_total_bucks'])) {
            return [
                'valid' => false,
                'message' => 'Total Bucks harus di antara ' . $config['min_total_bucks'] . ' dan ' . $config['max_total_bucks'] . '.',
                'form' => null,
                'config' => $config
            ];
        }
        $total_bucks = min($config['max_total_bucks'], max($config['min_total_bucks'], $raw_total_bucks));

        $raw_total_recipients = array_key_exists('total_recipients', $submitted_form)
            ? (int) $submitted_form['total_recipients']
            : (int) ($existing_form['total_recipients'] ?? $config['default_recipients']);
        if ($strict && ($raw_total_recipients < $config['min_recipients'] || $raw_total_recipients > $config['max_recipients'])) {
            return [
                'valid' => false,
                'message' => 'Total penerima harus di antara ' . $config['min_recipients'] . ' dan ' . $config['max_recipients'] . '.',
                'form' => null,
                'config' => $config
            ];
        }
        $total_recipients = min($config['max_recipients'], max($config['min_recipients'], $raw_total_recipients));

        $raw_expiry_hours = array_key_exists('expiry_hours', $submitted_form)
            ? (int) $submitted_form['expiry_hours']
            : (int) ($existing_form['expiry_hours'] ?? $config['default_expiry_hours']);
        if ($strict && ($raw_expiry_hours < $config['min_expiry_hours'] || $raw_expiry_hours > $config['max_expiry_hours'])) {
            return [
                'valid' => false,
                'message' => 'Masa aktif link harus di antara ' . $config['min_expiry_hours'] . ' dan ' . $config['max_expiry_hours'] . ' jam.',
                'form' => null,
                'config' => $config
            ];
        }
        $expiry_hours = min($config['max_expiry_hours'], max($config['min_expiry_hours'], $raw_expiry_hours));

        if ($strict && $total_bucks < $total_recipients) {
            return [
                'valid' => false,
                'message' => 'Total Bucks minimal harus sama dengan jumlah penerima.',
                'form' => null,
                'config' => $config
            ];
        }

        if ($total_bucks < $total_recipients) {
            $total_recipients = $total_bucks;
        }

        return [
            'valid' => true,
            'message' => null,
            'config' => $config,
            'form' => [
                'name' => $name,
                'total_bucks' => $total_bucks,
                'total_recipients' => $total_recipients,
                'expiry_hours' => $expiry_hours,
                'expires_at' => date('Y-m-d H:i:s', time() + ($expiry_hours * 3600))
            ]
        ];
    }

    private function _apply_bucks_kaget_form_to_cart(&$cart, $product, array $form) {
        if (is_object($product)) {
            $product = (array) $product;
        }

        $pricing = $this->_resolve_bucks_kaget_pricing($product, $form['total_bucks'] ?? 0);
        if (!$pricing) {
            return null;
        }

        foreach (($cart['items'] ?? []) as $cart_key => $cart_item) {
            if (!empty($cart_item['is_bucks_kaget']) && (int) $cart_key !== (int) $product['id']) {
                unset($cart['items'][$cart_key]);
            }
        }

        $cart['items'][$product['id']] = [
            'id' => (int) $product['id'],
            'name' => (string) ($product['name'] ?? 'Bucks Kaget'),
            'unit_price' => $pricing['final_total_price'],
            'price' => $pricing['final_total_price'],
            'quantity' => 1,
            'is_upgrade' => false,
            'is_bucks_kaget' => true,
            'original_price' => $pricing['total_savings'] > 0 ? $pricing['original_total_price'] : null,
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

    private function _get_bucks_kaget_cart_context(array $cart) {
        $context = [
            'contains' => false,
            'product' => null,
            'config' => null,
            'total_bucks' => 0,
            'item_quantity' => 0,
            'display_description' => '',
            'default_name' => '',
            'form' => null,
            'pricing' => null
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
            $context['display_description'] = $config['display_description'] !== ''
                ? $config['display_description']
                : 'Atur total bucks, jumlah penerima, dan masa aktif link claim sebelum checkout.';
            $context['item_quantity'] = max(1, (int) ($item['quantity'] ?? 1));
            break;
        }

        if (!$context['contains'] || empty($context['config'])) {
            return $context;
        }

        $existing_form = is_array($cart['bucks_kaget_form'] ?? null) ? $cart['bucks_kaget_form'] : [];
        $normalized = $this->_normalize_bucks_kaget_form_input(
            $context['product'],
            [],
            [
                'name' => $existing_form['name'] ?? '',
                'total_bucks' => $existing_form['total_bucks'] ?? ($cart['items'][$context['product']['id']]['bucks_kaget_total_bucks'] ?? $context['config']['default_total_bucks']),
                'total_recipients' => $existing_form['total_recipients'] ?? ($cart['items'][$context['product']['id']]['bucks_kaget_total_recipients'] ?? $context['config']['default_recipients']),
                'expiry_hours' => $existing_form['expiry_hours'] ?? $context['config']['default_expiry_hours']
            ],
            false
        );

        $context['default_name'] = $this->_get_bucks_kaget_default_name();
        $context['form'] = $normalized['form'];
        $context['pricing'] = $this->_resolve_bucks_kaget_pricing($context['product'], $context['form']['total_bucks'] ?? 0);
        $context['total_bucks'] = (int) ($context['pricing']['total_bucks'] ?? 0);

        return $context;
    }

    private function _get_bundle_original_total(array $item_ids) {
        $total = 0;
        foreach ($item_ids as $item_id) {
            if (!is_scalar($item_id)) continue;
            $product = $this->Store_model->get_product_by_id($item_id);
            if ($product) {
                if ($product['product_type'] === 'bundles') {
                    $nested_ids = json_decode($product['description'], true);
                    if ($this->_is_valid_item_list($nested_ids)) {
                        $total += $this->_get_bundle_original_total($nested_ids);
                    } else {
                        $total += (float)$product['price'];
                    }
                } else {
                    $total += (float)$product['price'];
                }
            }
        }
        return $total;
    }

    /**
     * [MODIFIKASI] Helper inti dengan dukungan QUANTITY STACKING
     * Menambahkan parameter $qty_to_add (Default 1)
     */
    private function _add_item_to_cart($product_id, $source_category = 'ranks', &$cart, $parent_bundle_name = null, $bundle_ratio = 1.0, $parent_bundle_id = null, $is_flash_sale = false, $qty_to_add = 1) {
        $product_to_add = $this->Store_model->get_product_by_id($product_id);
        if (!$product_to_add) return ['status' => 'error', 'message' => 'Produk tidak ditemukan.'];
        $is_bucks_kaget_product = $this->Store_model->is_bucks_kaget_product($product_to_add);

        // Cek jika ini adalah bundle isi (Recursive add)
        // [NOTE] Untuk Bundle, kita akan loop recursive sesuai quantity
        if ($product_to_add['product_type'] === 'bundles') {
            $product_ids_in_bundle = json_decode($product_to_add['description'], true);
            if ($this->_is_valid_item_list($product_ids_in_bundle)) {
                $item_added_count = 0;
                foreach ($product_ids_in_bundle as $item_id_in_bundle) {
                    // Panggil recursive dengan quantity yang sama
                    $result = $this->_add_item_to_cart($item_id_in_bundle, 'ranks', $cart, $parent_bundle_name, $bundle_ratio, $parent_bundle_id, $is_flash_sale, $qty_to_add);
                    if ($result['status'] == 'success') $item_added_count++;
                }
                if ($item_added_count > 0) return ['status' => 'success', 'message' => 'Isi dari sub-bundle "' . $product_to_add['name'] . '" ditambahkan.'];
                else return ['status' => 'info', 'message' => 'Item dari sub-bundle "' . $product_to_add['name'] . '" sudah ada di keranjang.'];
            }
        }

        if ($is_bucks_kaget_product) {
            foreach (($cart['items'] ?? []) as $existing_cart_key => $existing_item) {
                if ((int) ($existing_item['id'] ?? 0) === (int) $product_id) {
                    continue;
                }

                if (!empty($existing_item['is_bucks_kaget'])) {
                    return ['status' => 'info', 'message' => 'Hanya satu jenis produk Bucks Kaget yang bisa dibeli dalam satu checkout.'];
                }
            }
        }

        // --- CEK APAKAH ITEM BISA DI-STACK ---
        // Item yang TIDAK boleh stack: Ranks dan Upgrades (karena logikanya 1 per user)
        $is_stackable = !($source_category == 'ranks' || $source_category == 'rank_upgrades' || $product_to_add['category'] == 'ranks' || $product_to_add['category'] == 'rank_upgrades');

        // Cek Keberadaan Item di Cart
        if (isset($cart['items'][$product_id])) {
            if ($is_stackable) {
                // [LOGIKA STACKING] Jika stackable, tambahkan quantity
                $existing_item = &$cart['items'][$product_id];
                
                // Pastikan key quantity ada
                if (!isset($existing_item['quantity'])) $existing_item['quantity'] = 1;
                if (!isset($existing_item['unit_price'])) $existing_item['unit_price'] = $existing_item['price']; // Fallback

                // Update Quantity & Harga Total
                $existing_item['quantity'] += $qty_to_add;
                $existing_item['price'] = $existing_item['unit_price'] * $existing_item['quantity'];
                
                // Update Harga Asli (Coret) jika ada
                if (isset($existing_item['original_price']) && $existing_item['original_price'] > 0) {
                    // Kita asumsikan rasio original price konstan, hitung ulang dari base
                    $base_original = $existing_item['original_price'] / ($existing_item['quantity'] - $qty_to_add);
                    $existing_item['original_price'] = $base_original * $existing_item['quantity'];
                }

                return ['status' => 'success', 'message' => "Jumlah item '{$product_to_add['name']}' diperbarui menjadi {$existing_item['quantity']}x."];
            } else {
                // [LOGIKA RANK] Tidak boleh duplikat
                return ['status' => 'info', 'message' => 'Produk "' . $product_to_add['name'] . '" sudah ada di keranjang.'];
            }
        }

        // --- JIKA ITEM BELUM ADA (BARU) ---
        $item_name = $product_to_add['name'];
        $final_item_price = (float) $product_to_add['price'];
        $original_price_for_display = null;
        $is_upgrade = false;

        // Modifikasi Nama
        if ($product_to_add['category'] === 'currency' && !$is_bucks_kaget_product) { 
            $item_name .= ' (' . $this->_format_realm_name($product_to_add['realm']) . ')';
        }
        if ($is_flash_sale) {
            if (strpos($parent_bundle_id, 'feat_') === 0) {
                $item_name .= ' (Hot Deal)';
            } else {
                $item_name .= ' (Flash Sale)';
            }
        }

        // Logika Upgrade (Hanya jika bukan Flash Sale dan bukan bagian bundle rasio)
        if ($source_category == 'rank_upgrades' && $product_to_add['category'] == 'ranks' && $bundle_ratio == 1.0 && !$is_flash_sale) {
            $is_upgrade = true;
            $item_name = $product_to_add['name'] . ' (Upgrade)';
            $username = $this->session->userdata('username');
            $realm = $product_to_add['realm'];
            $all_hierarchies = $this->config->item('rank_hierarchies', 'ranks');
            $hierarchy = $all_hierarchies[strtolower($realm)] ?? [];
            $current_rank_name = $this->Store_model->get_player_rank($username, $realm, $hierarchy);
            $player_rank_weight = $hierarchy[$current_rank_name] ?? 0;
            $target_rank_weight = $hierarchy[$product_to_add['luckperms_group']] ?? 0;
            
            if ($target_rank_weight > $player_rank_weight) {
                $current_rank_product = $this->Product_model->get_product_by_luckperms_group($current_rank_name, $realm);
                if ($current_rank_product) {
                    $upgrade_cost = $product_to_add['price'] - $current_rank_product->price;
                    $final_item_price = max(0, $upgrade_cost);
                    $original_price_for_display = $product_to_add['price'];
                }
            } else {
                 return ['status' => 'error', 'message' => 'Anda tidak bisa meng-upgrade ke rank yang setara atau lebih rendah.'];
            }
        }

        // Cek Diskon Global (Toko) jika bukan Flash Sale
        if ($is_flash_sale == false) {
            $active_discount = $this->Discount_model->find_active_discount_for_product($product_to_add['id'], $product_to_add['category']);
            if ($active_discount) {
                if (is_null($original_price_for_display)) {
                    $original_price_for_display = $final_item_price;
                }
                $discount_amount = $final_item_price * ($active_discount->discount_percentage / 100);
                $final_item_price -= $discount_amount;
            }
        }

        // Terapkan Rasio Bundle (jika ada)
        if (is_null($original_price_for_display)) {
            $original_price_for_display = $final_item_price;
        }
        $final_item_price = $final_item_price * $bundle_ratio;

        if ($parent_bundle_name) {
            $item_name .= ' [' . $parent_bundle_name . ']';
        }

        // Simpan Data Item Baru
        $cart_item = [
            'id'             => $product_to_add['id'],
            'name'           => $item_name,
            
            // [MODIFIKASI] Simpan harga satuan & total berdasarkan quantity
            'unit_price'     => $final_item_price, 
            'price'          => $final_item_price * $qty_to_add, 
            'quantity'       => $qty_to_add,
            
            'is_upgrade'     => $is_upgrade,
            'is_bucks_kaget' => $is_bucks_kaget_product,
            // [MODIFIKASI] Original price dikalikan quantity
            'original_price' => ($bundle_ratio < 1.0 || $is_flash_sale || !is_null($original_price_for_display)) ? ($original_price_for_display * $qty_to_add) : null,
            'is_flash_sale'  => $is_flash_sale, 
            'bundle_group_id' => $parent_bundle_id
        ];
        
        // Simpan ke array menggunakan ID Produk sebagai Key
        $cart['items'][$product_id] = $cart_item;

        return ['status' => 'success', 'message' => 'Produk "' . $item_name . '" berhasil ditambahkan ke keranjang!'];
    }

    private function _update_cart_totals(&$cart) {
        $subtotal = 0;
        $subtotal_excluding_flash_sale = 0;
        $cart_discount_amount = 0;

        foreach ($cart['items'] as $item) {
            $subtotal += $item['price'];
            if (empty($item['is_flash_sale'])) {
                $subtotal_excluding_flash_sale += $item['price'];
            }
        }
        $cart['subtotal'] = $subtotal;

        // Reset nilai diskon
        $cart['cart_discount'] = 0;
        $cart['applied_cart_discount_tier'] = null;
        $cart['promo_discount'] = 0;
        $cart['referral_discount'] = 0;

        // 1. Hitung Diskon Keranjang (Tier)
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

        // 2. Hitung basis untuk diskon Promo & Referral
        $base_for_promo = max(0, $subtotal - $cart_discount_amount);
        $base_for_referral = max(0, $subtotal - $cart_discount_amount);

        // 3. Terapkan Promo Code
        $promo_discount_amount = 0;
        if (isset($cart['applied_promo'])) {
            $applied_promo = $cart['applied_promo'];
            if ($base_for_promo > 0) {
                if ($applied_promo['discount_type'] == 'percentage') {
                    $promo_discount_amount = $base_for_promo * $applied_promo['value'];
                } else { 
                    $promo_discount_amount = min($applied_promo['value'], $base_for_promo);
                }
            }
        }
        $cart['promo_discount'] = $promo_discount_amount;

        // 4. Terapkan Referral Code
        $referral_discount_amount = 0;
        if (isset($cart['applied_referral']) && $base_for_referral > 0) {
            $applied_referral = $cart['applied_referral'];
            $referral_discount_amount = $base_for_referral * $applied_referral['value'];
        }
        $cart['referral_discount'] = $referral_discount_amount;

        // 5. Final Grand Total
        $cart['grand_total'] = max(0, $subtotal - $cart_discount_amount - $promo_discount_amount - $referral_discount_amount);
    }

    private function _get_cart_upsell_message(array $cart) {
        $subtotal_for_upsell = 0;
        foreach (($cart['items'] ?? []) as $item) {
            if (empty($item['is_flash_sale'])) {
                $subtotal_for_upsell += (float) ($item['price'] ?? 0);
            }
        }

        if ($subtotal_for_upsell <= 0 || !$this->Cart_discount_model->is_enabled()) {
            return null;
        }

        $next_tier = $this->Cart_discount_model->get_next_tier($subtotal_for_upsell);
        if (!$next_tier) {
            return null;
        }

        $amount_needed = max(0, (float) $next_tier->min_amount - $subtotal_for_upsell);
        $percentage = (int) $next_tier->discount_percentage;

        return 'Tambah belanjaan senilai <strong>Rp ' . number_format($amount_needed, 0, ',', '.') . '</strong> lagi untuk mendapatkan diskon extra <strong>' . $percentage . '%</strong>!';
    }

    private function _build_cart_summary_payload(array $cart) {
        $payload = [
            'subtotal' => 'Rp ' . number_format($cart['subtotal'], 0, ',', '.'),
            'cart_discount' => '- Rp ' . number_format($cart['cart_discount'], 0, ',', '.'),
            'cart_discount_percentage' => (isset($cart['applied_cart_discount_tier']) ? (int) $cart['applied_cart_discount_tier']['percentage'] : 0),
            'promo_discount' => '- Rp ' . number_format($cart['promo_discount'], 0, ',', '.'),
            'referral_discount' => '- Rp ' . number_format($cart['referral_discount'], 0, ',', '.'),
            'grand_total' => 'Rp ' . number_format($cart['grand_total'], 0, ',', '.'),
            'applied_promo_data' => $cart['applied_promo'] ?? null,
            'applied_referral_data' => $cart['applied_referral'] ?? null,
            'upsell_message' => $this->_get_cart_upsell_message($cart)
        ];

        foreach (($cart['items'] ?? []) as $item) {
            if (empty($item['is_bucks_kaget'])) {
                continue;
            }

            $payload['bucks_kaget_item'] = [
                'id' => (int) ($item['id'] ?? 0),
                'price' => 'Rp ' . number_format((int) ($item['price'] ?? 0), 0, ',', '.'),
                'original_price' => !empty($item['original_price'])
                    ? 'Rp ' . number_format((int) ($item['original_price'] ?? 0), 0, ',', '.')
                    : null
            ];
            break;
        }

        return $payload;
    }

    private function _build_bucks_kaget_ui_payload(array $cart) {
        $context = $this->_get_bucks_kaget_cart_context($cart);
        if (!$context['contains'] || empty($context['form']) || empty($context['pricing'])) {
            return null;
        }

        $tier_min_qty = (int) ($context['pricing']['tier_min_qty'] ?? 1);
        $tier_label = $tier_min_qty > 1
            ? 'Tier diskon aktif mulai ' . number_format($tier_min_qty, 0, ',', '.') . ' Bucks.'
            : 'Harga dasar masih berlaku untuk jumlah ini.';

        return [
            'name' => (string) ($context['form']['name'] ?? $context['default_name']),
            'total_bucks' => (int) ($context['form']['total_bucks'] ?? 0),
            'total_recipients' => (int) ($context['form']['total_recipients'] ?? 0),
            'expiry_hours' => (int) ($context['form']['expiry_hours'] ?? 0),
            'price_per_buck' => 'Rp ' . number_format((int) round($context['pricing']['price_per_buck_final'] ?? 0), 0, ',', '.'),
            'total_price' => 'Rp ' . number_format((int) ($context['pricing']['final_total_price'] ?? 0), 0, ',', '.'),
            'original_total_price' => 'Rp ' . number_format((int) ($context['pricing']['original_total_price'] ?? 0), 0, ',', '.'),
            'show_original_total_price' => !empty($context['pricing']['total_savings']),
            'tier_label' => $tier_label
        ];
    }

    // =======================================================================
    // PUBLIC FUNCTIONS
    // =======================================================================

    public function index() {
        $cart = $this->_get_cart_from_session();
        $this->_update_cart_totals($cart);
        $bucks_kaget_context = $this->_get_bucks_kaget_cart_context($cart);
        if (!$bucks_kaget_context['contains'] && isset($cart['bucks_kaget_form'])) {
            unset($cart['bucks_kaget_form']);
        }
        $this->session->set_userdata('cart', $cart);

        $subtotal = $cart['subtotal'];
        $data['upsell_message'] = null; 
        $data['first_time_buyer_message'] = null;

        // Cek Pembeli Pertama
        $uuid = $this->session->userdata('uuid');
        $transaction_count = $this->Transaction_model->get_completed_transaction_count($uuid);
        $data['is_first_time_buyer'] = ($transaction_count == 0);

        if ($data['is_first_time_buyer'] && $subtotal > 0) {
            $data['first_time_buyer_message'] = "Ini pembelian pertamamu! Selesaikan checkout untuk mendapatkan <strong>Bonus Spesial</strong> eksklusif!";
        } 
        
        // Cek Upsell Diskon Keranjang
        $data['upsell_message'] = $this->_get_cart_upsell_message($cart);

        $data['cart'] = $cart;
        $data['bucks_kaget_context'] = $bucks_kaget_context;
        
        // Cross Sell Logic
        $data['cross_sell_products'] = [];
        if (!empty($data['cart']['items'])) {
            $raw_cross_sell_products = $this->Store_model->get_cross_sell_products($data['cart']['items']);
            foreach ($raw_cross_sell_products as &$product) { 
                $product = $this->Store_model->enrich_product_description_for_display($product);
                $active_discount = $this->Discount_model->find_active_discount_for_product($product['id'], $product['realm'] ?? $product['category']); 
                if ($active_discount) {
                    $discount_amount = $product['price'] * ($active_discount->discount_percentage / 100);
                    $product['final_price'] = $product['price'] - $discount_amount;
                    $product['original_price'] = $product['price'];
                } else {
                    $product['final_price'] = $product['price'];
                    $product['original_price'] = null;
                }
            }
            unset($product); 
            $data['cross_sell_products'] = $raw_cross_sell_products;
        }

        $data['title'] = 'Cart | MineHive';
        $data['meta_description'] = 'Periksa keranjang belanja, promo, diskon, dan total checkout kamu sebelum lanjut pembayaran di MineHive.';
        $this->load->view('templates/header', $data);
        $this->load->view('cart_view', $data);
        $this->load->view('templates/footer');
    }

    public function add($product_id = NULL, $source_category = 'ranks') {
        if (!$product_id) { 
            if ($this->input->is_ajax_request()) {
                echo json_encode(['status' => 'error', 'message' => 'Produk tidak valid.']); return;
            }
            redirect(base_url()); return; 
        }

        if (!$this->session->userdata('is_logged_in')) {
            if ($this->input->is_ajax_request()) {
                echo json_encode(['status' => 'redirect', 'url' => base_url(), 'message' => 'Silakan login terlebih dahulu.']); return;
            }
            $this->session->set_flashdata('error', 'Anda harus login untuk menambahkan item.');
            redirect($this->agent->referrer() ?? 'cart');
            return;
        }

        $cart = $this->_get_cart_from_session();
        $product_to_add = $this->Store_model->get_product_by_id($product_id);
        
        if (!$product_to_add) {
             if ($this->input->is_ajax_request()) {
                echo json_encode(['status' => 'error', 'message' => 'Produk tidak ditemukan.']); return;
             }
             $this->session->set_flashdata('error', 'Produk tidak valid.');
             redirect(base_url()); return;
        }

        $quantity = 1;
        if ($this->input->post('quantity')) {
            $quantity = (int)$this->input->post('quantity');
        }
        
        // PENTING: Batasi quantity untuk Rank Upgrades dan Battlepass (karena sistem khususnya)
        if ($source_category == 'rank_upgrades' || $product_to_add['category'] == 'rank_upgrades') {
            $quantity = 1;
        }
        if ($quantity < 1) $quantity = 1;

        // [MODIFIKASI] Panggil _add_item_to_cart SEKALI SAJA dengan quantity yang diinginkan
        // Helper sekarang akan menangani stacking (penjumlahan qty) secara otomatis
        // Loop 'for' dihapus karena sudah tidak efisien
        $result = $this->_add_item_to_cart($product_id, $source_category, $cart, null, 1.0, null, false, $quantity);
        
        $success_message = $result['message'];
        $status = $result['status'];

        $this->_update_cart_totals($cart);
        $this->session->set_userdata('cart', $cart);

        if ($this->input->is_ajax_request()) {
            $total_items = count($cart['items']); // Ini menghitung total item unik (row)
            $grand_total = 'Rp ' . number_format($cart['grand_total'], 0, ',', '.');
            
            if ($status == 'success') {
                echo json_encode([
                    'status' => 'success', 
                    // Pesan dinamis sesuai quantity
                    'message' => ($quantity > 1) ? $quantity . 'x ' . $product_to_add['name'] . ' berhasil ditambahkan!' : $success_message,
                    'cart_count' => $total_items,
                    'cart_total' => $grand_total
                ]);
            } else {
                echo json_encode([
                    'status' => $status, 
                    'message' => $success_message
                ]);
            }
        } else {
            if ($status == 'success') $this->session->set_flashdata('success', $quantity . ' item berhasil ditambahkan.');
            elseif ($status == 'info') $this->session->set_flashdata('info', $success_message);
            else $this->session->set_flashdata('error', $success_message);
            
            redirect('cart');
        }
    }

    public function add_battlepass() {
        if (!$this->session->userdata('is_logged_in')) {
            echo json_encode(['status' => 'error', 'message' => 'Anda harus login untuk menambahkan item.']);
            return;
        }
        header('Content-Type: application/json');
        
        $product_id = $this->input->post('product_id');
        $quantity = (int)$this->input->post('quantity');
        
        if (!$product_id || $quantity < 1) {
            echo json_encode(['status' => 'error', 'message' => 'Data produk tidak valid.']);
            return;
        }
        
        $product = $this->Store_model->get_product_by_id($product_id);
        if (!$product || $product['product_type'] !== 'battlepass') {
            echo json_encode(['status' => 'error', 'message' => 'Produk battlepass tidak ditemukan.']);
            return;
        }

        $cart = $this->_get_cart_from_session();
        
        $raw_db_price = (float)$product['price'];
        $final_price_single = $raw_db_price;
        $active_discount = $this->Discount_model->find_active_discount_for_product($product['id'], $product['realm']);
        
        if ($active_discount) {
            $discount_amount = $raw_db_price * ($active_discount->discount_percentage / 100);
            $final_price_single = $raw_db_price - $discount_amount;
        }
        $discount_ratio = ($raw_db_price > 0) ? ($final_price_single / $raw_db_price) : 1.0;

        $price_per_level_raw = (float)$product['price']; 
        try {
            $config = json_decode($product['description'], true);
            if (is_array($config) && !empty($config)) {
                usort($config, function($a, $b) {
                    return $a['min_qty'] - $b['min_qty'];
                });
                foreach (array_reverse($config) as $tier) {
                    if ($quantity >= $tier['min_qty']) {
                        $price_per_level_raw = (float)$tier['price_per_level'];
                        break; 
                    }
                }
            }
        } catch (Exception $e) {
            log_message('error', 'Config JSON Battlepass error: ' . $product_id);
        }

        $price_per_level_final = $price_per_level_raw * $discount_ratio;
        $final_total_price = $quantity * $price_per_level_final;
        $original_total_price = $quantity * $price_per_level_raw;
        
        $item_name = $quantity . 'x ' . $product['name'];
        $cart_item = [
            'id'             => $product['id'],
            'name'           => $item_name,
            'price'          => $final_total_price, 
            'quantity'       => $quantity, 
            'is_upgrade'     => false,
            'original_price' => ($discount_ratio < 1.0) ? $original_total_price : null,
            'is_flash_sale'  => false,
            'bundle_group_id' => null,
            'is_battlepass'  => true 
        ];
        
        $cart['items'][$product_id] = $cart_item;
        $this->_update_cart_totals($cart);
        $this->session->set_userdata('cart', $cart);
        
        echo json_encode(['status' => 'success', 'message' => $item_name . ' berhasil ditambahkan ke keranjang.']);
    }

    public function add_bucks_kaget() {
        header('Content-Type: application/json');

        if (!$this->session->userdata('is_logged_in')) {
            echo json_encode(['status' => 'redirect', 'url' => base_url(), 'message' => 'Silakan login terlebih dahulu.']);
            return;
        }

        $product_id = (int) $this->input->post('product_id');
        $product = $this->Store_model->get_product_by_id($product_id);
        if (!$product || !$this->Store_model->is_bucks_kaget_product($product)) {
            echo json_encode(['status' => 'error', 'message' => 'Produk Bucks Kaget tidak ditemukan.']);
            return;
        }

        $cart = $this->_get_cart_from_session();
        $existing_form = is_array($cart['bucks_kaget_form'] ?? null) ? $cart['bucks_kaget_form'] : [];
        $normalized = $this->_normalize_bucks_kaget_form_input(
            $product,
            [
                'total_bucks' => $this->input->post('total_bucks'),
                'total_recipients' => $this->input->post('total_recipients')
            ],
            $existing_form,
            true
        );

        if (!$normalized['valid']) {
            echo json_encode(['status' => 'error', 'message' => $normalized['message']]);
            return;
        }

        $applied = $this->_apply_bucks_kaget_form_to_cart($cart, $product, $normalized['form']);
        if (!$applied) {
            echo json_encode(['status' => 'error', 'message' => 'Gagal memproses Bucks Kaget.']);
            return;
        }

        $this->_update_cart_totals($cart);
        $this->session->set_userdata('cart', $cart);

        echo json_encode([
            'status' => 'success',
            'message' => 'Bucks Kaget berhasil ditambahkan ke keranjang.',
            'cart_count' => count($cart['items']),
            'cart_total' => 'Rp ' . number_format($cart['grand_total'], 0, ',', '.'),
            'redirect_url' => base_url('cart')
        ]);
    }

    public function update_bucks_kaget() {
        header('Content-Type: application/json');

        $cart = $this->_get_cart_from_session();
        $context = $this->_get_bucks_kaget_cart_context($cart);
        if (!$context['contains'] || empty($context['product'])) {
            echo json_encode(['status' => 'error', 'message' => 'Produk Bucks Kaget tidak ada di keranjang.']);
            return;
        }

        $normalized = $this->_normalize_bucks_kaget_form_input(
            $context['product'],
            [
                'name' => $this->input->post('name'),
                'total_bucks' => $this->input->post('total_bucks'),
                'total_recipients' => $this->input->post('total_recipients'),
                'expiry_hours' => $this->input->post('expiry_hours')
            ],
            is_array($context['form']) ? $context['form'] : [],
            false
        );

        if (!$normalized['valid']) {
            echo json_encode(['status' => 'error', 'message' => $normalized['message']]);
            return;
        }

        $applied = $this->_apply_bucks_kaget_form_to_cart($cart, $context['product'], $normalized['form']);
        if (!$applied) {
            echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui Bucks Kaget.']);
            return;
        }

        $this->_update_cart_totals($cart);
        $this->session->set_userdata('cart', $cart);

        echo json_encode([
            'status' => 'success',
            'message' => 'Bucks Kaget berhasil diperbarui.',
            'cart' => $this->_build_cart_summary_payload($cart),
            'bucks_kaget' => $this->_build_bucks_kaget_ui_payload($cart)
        ]);
    }

    public function add_flash_sale($flash_sale_id) {
        if (!$flash_sale_id) { redirect(base_url()); return; }
        if (!$this->session->userdata('is_logged_in')) {
            $this->session->set_flashdata('error', 'Anda harus login untuk membeli item flash sale.');
            redirect($this->agent->referrer() ?? 'cart');
            return;
        }

        $this->load->model('Flash_sale_model');
        $flash_sale = $this->Flash_sale_model->get_active_flash_sale_by_id($flash_sale_id);
        
        if (!$flash_sale || $flash_sale->stock_sold >= $flash_sale->stock_limit) {
            $this->session->set_flashdata('error', 'Maaf, penawaran flash sale sudah tidak valid atau stok habis.');
            redirect(base_url());
            return;
        }

        $cart = $this->_get_cart_from_session(); 
        
        $group_id_to_check = 'fs_' . $flash_sale_id;
        foreach ($cart['items'] as $item) {
            if (($item['bundle_group_id'] ?? null) == $group_id_to_check) {
                $this->session->set_flashdata('info', 'Produk flash sale ini sudah ada di keranjang Anda.');
                redirect('cart');
                return;
            }
        }
         if ($flash_sale->product_type !== 'bundles' && isset($cart['items'][$flash_sale->product_id])) {
             $this->session->set_flashdata('info', 'Produk flash sale ini sudah ada di keranjang Anda.');
            redirect('cart');
            return;
        }

        $bundle_data_fs = json_decode($flash_sale->description, true);
        if ($flash_sale->product_type === 'bundles' && $this->_is_valid_item_list($bundle_data_fs)) {
            $product_ids_in_bundle = $bundle_data_fs;
            $master_bundle_price = $flash_sale->original_price - ($flash_sale->original_price * $flash_sale->discount_percentage / 100);
            $parent_bundle_name = $flash_sale->name;
            $parent_bundle_id = 'fs_' . $flash_sale_id; 
            
            $original_total_price = $this->_get_bundle_original_total($product_ids_in_bundle);
            $bundle_ratio = 1.0;
            if ($original_total_price > 0 && $master_bundle_price < $original_total_price) {
                $bundle_ratio = $master_bundle_price / $original_total_price;
            }

            $items_added_count = 0;
            foreach ($product_ids_in_bundle as $item_id_in_bundle) {
                if (is_scalar($item_id_in_bundle)) {
                    $result = $this->_add_item_to_cart($item_id_in_bundle, 'ranks', $cart, $parent_bundle_name, $bundle_ratio, $parent_bundle_id, true);
                    if ($result['status'] == 'success') {
                        $items_added_count++;
                    }
                }
            }
            if ($items_added_count > 0) {
                 $this->session->set_flashdata('success', 'Paket Flash Sale "' . $parent_bundle_name . '" berhasil ditambahkan!');
            } else {
                 $this->session->set_flashdata('info', 'Semua item dari paket Flash Sale "' . $parent_bundle_name . '" sudah ada di keranjang.');
            }
        } else {
            $flash_price = $flash_sale->original_price - ($flash_sale->original_price * $flash_sale->discount_percentage / 100);
            $product_name_in_cart = $flash_sale->name;
            if ($flash_sale->category === 'currency') {
                $product_name_in_cart .= ' (' . $this->_format_realm_name($flash_sale->realm) . ')';
            }
            $product_name_in_cart .= ' (Flash Sale)'; 
            
            $cart_item = [
                'id'             => $flash_sale->product_id,
                'name'           => $product_name_in_cart,
                'price'          => $flash_price,
                'is_upgrade'     => false,
                'original_price' => $flash_sale->original_price,
                'is_flash_sale'  => true,
                'flash_sale_id'  => $flash_sale_id, 
                'bundle_group_id' => 'fs_' . $flash_sale_id 
            ];
            $cart['items'][$flash_sale->product_id] = $cart_item;
            $this->session->set_flashdata('success', 'Produk flash sale berhasil ditambahkan ke keranjang!');
        }
        
        $this->_update_cart_totals($cart);
        $this->session->set_userdata('cart', $cart);
        redirect('cart');
    }

    public function add_featured($featured_id) {
        if (!$featured_id) { 
            if ($this->input->is_ajax_request()) {
                echo json_encode(['status' => 'error', 'message' => 'Penawaran tidak valid.']); return;
            }
            redirect(base_url()); return; 
        }

        if (!$this->session->userdata('is_logged_in')) {
            if ($this->input->is_ajax_request()) {
                echo json_encode(['status' => 'redirect', 'url' => base_url(), 'message' => 'Silakan login terlebih dahulu.']); return;
            }
            $this->session->set_flashdata('error', 'Anda harus login untuk membeli item.');
            redirect($this->agent->referrer() ?? 'cart');
            return;
        }

        $quantity = $this->input->post('quantity') ? (int)$this->input->post('quantity') : 1;
        if ($quantity < 1) $quantity = 1;

        // [MODIFIKASI] Ambil dan validasi realm
        $realm = $this->input->post('realm');
        if (!$realm || !in_array($realm, ['survival', 'skyblock', 'oneblock'], true)) {
            if ($this->input->is_ajax_request()) {
                echo json_encode(['status' => 'error', 'message' => 'Realm tidak valid.']); return;
            }
            $this->session->set_flashdata('error', 'Silakan pilih realm yang valid.');
            redirect(base_url());
            return;
        }

        $featured_item = $this->Featured_model->get_by_id($featured_id);

        if (!$featured_item) {
            if ($this->input->is_ajax_request()) {
                echo json_encode(['status' => 'error', 'message' => 'Penawaran tidak ditemukan.']); return;
            }
            $this->session->set_flashdata('error', 'Penawaran ini tidak ditemukan.');
            redirect(base_url());
            return;
        }

        $product = $this->Store_model->get_product_by_id($featured_item->product_id);
        if (!$product) {
            if ($this->input->is_ajax_request()) {
                echo json_encode(['status' => 'error', 'message' => 'Produk asli tidak ditemukan.']); return;
            }
            $this->session->set_flashdata('error', 'Produk asli tidak ditemukan.');
            redirect(base_url());
            return;
        }

        $cart = $this->_get_cart_from_session(); 

        $original_price = (float) $product['price'];
        $discount_percent = $featured_item->discount_percentage;
        $final_unit_price = $original_price - ($original_price * $discount_percent / 100);

        $product_name_in_cart = $product['name'];
        // [MODIFIKASI] Hapus penambahan realm dari data produk, ganti dengan realm pilihan user
        // if ($product['category'] === 'currency') {
        //     $product_name_in_cart .= ' (' . ucfirst($product['realm']) . ')';
        // }
        $product_name_in_cart .= ' (Hot Deal)'; 
        $product_name_in_cart .= ' (' . $this->_format_realm_name($realm) . ')'; // Tambahkan realm pilihan user

        // [MODIFIKASI] Jadikan cart key unik per realm
        $cart_key = 'feat_' . $featured_id . '_' . $realm;

        $current_qty = 0;
        if (isset($cart['items'][$cart_key])) {
            $current_qty = $cart['items'][$cart_key]['quantity'];
        }
        
        $new_total_qty = $current_qty + $quantity;
        $total_price_stack = $final_unit_price * $new_total_qty;
        $total_original_price_stack = ($discount_percent > 0) ? ($original_price * $new_total_qty) : null;

        $cart_item = [
            'id'             => $product['id'],
            'name'           => $product_name_in_cart,
            'price'          => $total_price_stack, 
            'unit_price'     => $final_unit_price,  
            'quantity'       => $new_total_qty,
            'realm'          => $realm, // [MODIFIKASI] Simpan realm
            'is_upgrade'     => false,
            'original_price' => $total_original_price_stack,
            'is_flash_sale'  => true, 
            'flash_sale_id'  => 'feat_' . $featured_id, 
            'bundle_group_id' => 'feat_' . $featured_id . '_' . $realm // [MODIFIKASI] Jadikan group ID unik juga
        ];

        $cart['items'][$cart_key] = $cart_item;
        
        $this->_update_cart_totals($cart);
        $this->session->set_userdata('cart', $cart);

        if ($this->input->is_ajax_request()) {
            $total_items = count($cart['items']); 
            $grand_total = 'Rp ' . number_format($cart['grand_total'], 0, ',', '.');
            
            echo json_encode([
                'status' => 'success', 
                'message' => $quantity . 'x ' . $product_name_in_cart . ' berhasil ditambahkan!', 
                'cart_count' => $total_items,
                'cart_total' => $grand_total
            ]);
        } else {
            $this->session->set_flashdata('success', $quantity . 'x Produk Hot Deal berhasil ditambahkan!');
            redirect('cart');
        }
    }

    public function remove($key) {
        $cart = $this->_get_cart_from_session(); 
        
        if (isset($cart['items'][$key])) {
            $bundle_group_to_remove = $cart['items'][$key]['bundle_group_id'] ?? null;
            
            if ($bundle_group_to_remove !== null) {
                // Hapus satu grup bundle sekaligus
                $new_cart_items = [];
                foreach ($cart['items'] as $id => $item) {
                    if (($item['bundle_group_id'] ?? null) != $bundle_group_to_remove) {
                        $new_cart_items[$id] = $item;
                    }
                }
                $cart['items'] = $new_cart_items; 
                $this->session->set_flashdata('info', 'Item bundle dihapus. Semua item dari paket yang sama juga ikut dihapus.');
            } else {
                // Hapus item tunggal
                unset($cart['items'][$key]);
                $this->session->set_flashdata('info', 'Produk telah dihapus dari keranjang.');
            }

            if (empty($cart['items'])) {
                $cart['applied_promo'] = null;
                $cart['applied_referral'] = null;
                unset($cart['bucks_kaget_form'], $cart['bucks_kaget_result']);
            } elseif (!$this->_get_bucks_kaget_cart_context($cart)['contains']) {
                unset($cart['bucks_kaget_form'], $cart['bucks_kaget_result']);
            }

            $this->_update_cart_totals($cart); 
            $this->session->set_userdata('cart', $cart);
        }
        redirect('cart');
    }

    public function search_usernames() {
        header('Content-Type: application/json');

        $keyword = trim((string) $this->input->get('q', TRUE));
        if ($keyword === '' || strlen($keyword) < 2) {
            echo json_encode([
                'status' => 'success',
                'suggestions' => []
            ]);
            return;
        }

        echo json_encode([
            'status' => 'success',
            'suggestions' => $this->User_model->search_usernames($keyword, 7)
        ]);
        return;
    }

    // apply_promo remains the same...
    public function apply_promo() {
        header('Content-Type: application/json');
        
        $cart = $this->_get_cart_from_session();
        if (empty($cart['items'])) {
            echo json_encode(['status' => 'error', 'message' => 'Keranjang Anda kosong.']);
            return;
        }

        $input_code = strtoupper($this->input->post('promo_code'));
        $response = [];

        $promo_data = $this->Promo_model->get_by_code($input_code);
        $affiliate_code_data = $this->Affiliate_model->get_code_by_string($input_code);

        if ($promo_data) {
            $usage_count = $promo_data->usage_count ?? 0;
            if ($promo_data->expires_at && strtotime($promo_data->expires_at) < time()) {
                $response = ['status' => 'error', 'message' => 'Kode promo tersebut sudah kedaluwarsa.'];
            } elseif ($promo_data->usage_limit !== null && $usage_count >= $promo_data->usage_limit) {
                $response = ['status' => 'error', 'message' => 'Batas pemakaian kode promo ini sudah habis.'];
            } else {
                $allow_stacking = !empty($promo_data->allow_stacking);
                if (isset($cart['applied_referral']) && !$allow_stacking) {
                    $response = ['status' => 'error', 'message' => 'Kode promo ini tidak bisa digabung dengan kode referral yang sudah ada.'];
                } else {
                    $cart['applied_promo'] = [
                        'type' => 'promo',
                        'code' => $promo_data->code,
                        'discount_type' => $promo_data->type,
                        'value' => $promo_data->value,
                        'allow_stacking' => $allow_stacking
                    ];
                    
                    $this->_update_cart_totals($cart);
                    
                    if ($cart['promo_discount'] <= 0) {
                        $cart['applied_promo'] = null;
                        $this->_update_cart_totals($cart); 
                        $response = ['status' => 'error', 'message' => 'Kode promo berlaku, tapi tidak dapat digunakan untuk produk Flash Sale/Hot Deals.'];
                    } else {
                        $response = ['status' => 'success', 'message' => 'Kode promo berhasil digunakan!'];
                    }
                }
            }
        } elseif ($affiliate_code_data) {
            $promo_is_stackable = !empty($cart['applied_promo']['allow_stacking']);
            if (isset($cart['applied_promo']) && !$promo_is_stackable) {
                $response = ['status' => 'error', 'message' => 'Kode referral ini tidak bisa digabung dengan kode promo yang sudah ada.'];
            } else {
                $cart['applied_referral'] = [
                    'type' => 'affiliate',
                    'code' => $affiliate_code_data->code,
                    'discount_type' => 'percentage', 
                    'value' => $affiliate_code_data->customer_discount_percentage / 100,
                    'affiliate_id' => $affiliate_code_data->affiliate_id
                ];
                $response = ['status' => 'success', 'message' => 'Kode referral berhasil digunakan!'];
            }
        } else {
            $response = ['status' => 'error', 'message' => 'Kode yang Anda masukkan tidak valid.'];
        }

        if (!$promo_data) {
             $this->_update_cart_totals($cart);
        }
        
        $this->session->set_userdata('cart', $cart);

        $response['cart'] = $this->_build_cart_summary_payload($cart);

        echo json_encode($response);
        return;
    }
}
