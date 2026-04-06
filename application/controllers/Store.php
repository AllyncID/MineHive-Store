<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Store extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Store_model');
        $this->load->model('Discount_model');
        $this->load->model('Promo_popup_model'); // Load our new model
        $this->load->library('session');

        if (!$this->session->userdata('is_logged_in')) {
            $this->session->set_flashdata('error', 'Anda harus login untuk mengakses toko.');
            redirect(base_url());
        }
        
    }

    private function _format_realm_name($realm_name) {
        $realm_key = strtolower((string) $realm_name);
        $realm_names = [
            'survival' => 'Survival',
            'skyblock' => 'Skyblock',
            'acidisland' => 'AcidIsland',
            'oneblock' => 'OneBlock',
        ];

        return $realm_names[$realm_key] ?? ucwords(str_replace(['_', '-'], ' ', (string) $realm_name));
    }

    private function _format_category_name($category) {
        $category_key = strtolower(trim((string) $category));
        $category_names = [
            'ranks' => 'Rank',
            'rank' => 'Rank',
            'rank_upgrades' => 'Upgrade Rank',
            'currency' => 'Currency',
            'bundles' => 'Bundle',
            'battlepass' => 'Battlepass'
        ];

        return $category_names[$category_key] ?? ucwords(str_replace(['_', '-'], ' ', $category_key));
    }

    private function _build_store_meta_description($realm_name, $category) {
        $realm_label = $this->_format_realm_name($realm_name);
        $category_key = strtolower(trim((string) $category));
        $category_label = $this->_format_category_name($category);

        if ($category_key === 'rank_upgrades') {
            return 'Lihat opsi upgrade rank untuk realm ' . $realm_label . ' di MineHive dan lanjutkan progress rank kamu dengan harga yang sesuai.';
        }

        if ($category_key === 'ranks' || $category_key === 'rank') {
            return 'Lihat daftar rank, benefit, dan item spesial untuk realm ' . $realm_label . ' di store resmi MineHive.';
        }

        return 'Belanja produk ' . $category_label . ' untuk realm ' . $realm_label . ' di store resmi MineHive.';
    }

    private function _prioritize_bucks_kaget_products(array $products) {
        if (empty($products)) {
            return $products;
        }

        foreach ($products as $index => &$product) {
            $product['__sort_index'] = $index;
            $product['__bucks_kaget_priority'] = (strtolower(trim((string) ($product['product_type'] ?? ''))) === 'bucks_kaget') ? 0 : 1;
        }
        unset($product);

        usort($products, static function ($left, $right) {
            $priorityCompare = ($left['__bucks_kaget_priority'] ?? 1) <=> ($right['__bucks_kaget_priority'] ?? 1);
            if ($priorityCompare !== 0) {
                return $priorityCompare;
            }

            return ($left['__sort_index'] ?? 0) <=> ($right['__sort_index'] ?? 0);
        });

        foreach ($products as &$product) {
            unset($product['__sort_index'], $product['__bucks_kaget_priority']);
        }
        unset($product);

        return $products;
    }

    public function show($realm_name, $category = 'ranks') {
        // Currency sekarang global (tidak per-realm lagi)
        if ($category === 'currency') {
            return $this->currency();
        }

        $uuid = $this->session->userdata('is_logged_in') ? $this->session->userdata('uuid') : null;
        $username = $this->session->userdata('is_logged_in') ? $this->session->userdata('username') : null;
        
        if ($username && !$this->Store_model->has_player_joined_realm($username, $realm_name)) {
            $error_message = 'Anda belum pernah bergabung ke realm ' . $this->_format_realm_name($realm_name) . '.';
            $this->session->set_flashdata('error', $error_message);
            redirect(base_url(''));
            return;
        }

        $this->config->load('ranks', TRUE);
        $this->load->model('Product_model');

        $all_hierarchies = $this->config->item('rank_hierarchies', 'ranks');
        $realm_name_lc = strtolower($realm_name);
        $rank_hierarchy = $all_hierarchies[$realm_name_lc] ?? []; 
        $current_rank_name = $username ? $this->Store_model->get_player_rank($username, $realm_name, $rank_hierarchy) : null;
        $current_rank_product = null;
        if ($current_rank_name) {
            $current_rank_product = $this->Product_model->get_product_by_luckperms_group($current_rank_name, $realm_name);
        }
        
        $db_query_category = ($category == 'rank' || $category == 'rank_upgrades') ? 'ranks' : $category;
        $products_from_db = $this->Store_model->get_products_by_realm($realm_name, $db_query_category);
        
        $processed_products = [];
        foreach ($products_from_db as $product) {
            $product = $this->Store_model->enrich_product_description_for_display($product);
            $product['product_type'] = strtolower(trim((string) ($product['product_type'] ?? '')));
            $product['category'] = strtolower(trim((string) ($product['category'] ?? '')));
            $final_price = $product['price'];
            $original_price = null;
            $is_owned = false;
            $is_upgrade_calculation = false;

            if ($product['category'] == 'ranks' && $current_rank_name && !empty($rank_hierarchy)) {
                $player_rank_weight = $rank_hierarchy[$current_rank_name] ?? 0;
                $target_rank_weight = $rank_hierarchy[$product['luckperms_group']] ?? -1;

                if ($target_rank_weight !== -1 && $target_rank_weight <= $player_rank_weight) {
                    $is_owned = true;
                }
            }

            if ($category == 'rank_upgrades' && !$is_owned && $product['category'] == 'ranks' && $current_rank_product) {
                $player_rank_weight = $rank_hierarchy[$current_rank_name] ?? 0;
                $target_rank_weight = $rank_hierarchy[$product['luckperms_group']] ?? 0;

                if ($player_rank_weight > 0 && $target_rank_weight > $player_rank_weight) {
                    $is_upgrade_calculation = true;
                    $upgrade_cost = $product['price'] - $current_rank_product->price;
                    $final_price = max(0, $upgrade_cost);
                    $original_price = $product['price'];
                }
            }

            $active_discount = $this->Discount_model->find_active_discount_for_product($product['id'], $product['category']);
            if ($active_discount) {
                if (!$is_upgrade_calculation) {
                    $original_price = $product['price'];
                }
                $discount_amount = $final_price * ($active_discount->discount_percentage / 100);
                $final_price -= $discount_amount;
                $product['discount_percentage'] = $active_discount->discount_percentage;
            }

            $product['final_price'] = $final_price;
            $product['original_price'] = $original_price;
            $product['is_owned'] = $is_owned;

            if ($category == 'rank_upgrades' && stripos($product['name'], '30 Days') !== false) {
                continue;
            }

            $processed_products[] = $product;
        }
        
        // BLOK INI DIHAPUS KARENA BOX SALE HANYA ADA DI HOME
        // $this->load->model('Settings_model');
        // $settings = $this->Settings_model->get_all_settings();
        // $data['announcement_bar'] = [ ... ];
        // =================================================
        
        // Get promo popup data
        $data['promo_popup'] = $this->Promo_popup_model->get_promo_data();

        $data['products'] = $processed_products;
        $data['current_rank'] = $current_rank_name;
        $data['rank_hierarchy'] = $rank_hierarchy;
        $data['title'] = $this->_format_realm_name($realm_name) . " | Mine Hive";
        $data['meta_description'] = $this->_build_store_meta_description($realm_name, $category);
        $data['realm_name'] = $this->_format_realm_name($realm_name);
        $data['category'] = $category;
        
        $this->load->view('templates/header', $data);
        $this->load->view('store/products_view', $data);
        $this->load->view('templates/footer');
    }

    public function currency() {
        $products_from_db = $this->Store_model->get_products_by_category('currency');

        $processed_products = [];
        foreach ($products_from_db as $product) {
            $product = $this->Store_model->enrich_product_description_for_display($product);
            $product['product_type'] = strtolower(trim((string) ($product['product_type'] ?? '')));
            $product['category'] = strtolower(trim((string) ($product['category'] ?? '')));
            $final_price = $product['price'];
            $original_price = null;

            $active_discount = $this->Discount_model->find_active_discount_for_product($product['id'], $product['category']);
            if ($active_discount) {
                $original_price = $product['price'];
                $discount_amount = $final_price * ($active_discount->discount_percentage / 100);
                $final_price -= $discount_amount;
                $product['discount_percentage'] = $active_discount->discount_percentage;
            }

            $product['final_price'] = $final_price;
            $product['original_price'] = $original_price;
            $product['is_owned'] = false;

            $processed_products[] = $product;
        }

        $processed_products = $this->_prioritize_bucks_kaget_products($processed_products);

        $data['promo_popup'] = $this->Promo_popup_model->get_promo_data();

        $data['products'] = $processed_products;
        $data['current_rank'] = null;
        $data['rank_hierarchy'] = [];
        $data['title'] = "Currency | Mine Hive";
        $data['meta_description'] = 'Belanja Bucks, Bucks Kaget, dan produk currency resmi MineHive untuk kebutuhan item, hadiah, dan top up server.';
        $data['realm_name'] = 'Global';
        $data['category'] = 'currency';

        $this->load->view('templates/header', $data);
        $this->load->view('store/products_view', $data);
        $this->load->view('templates/footer');
    }
}
