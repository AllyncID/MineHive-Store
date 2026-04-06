<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Store_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database(); // Memastikan koneksi database dimuat
    }

    private function _supports_player_database($realm_name) {
        // Hanya enforce cek join/rank jika grup database realm memang dikonfigurasi.
        $realm_group = strtolower((string) $realm_name);

        if ($realm_group === '') {
            return false;
        }

        $active_group = null;
        $query_builder = null;
        $db = [];

        include APPPATH . 'config/database.php';

        if (empty($db[$realm_group]) || !is_array($db[$realm_group])) {
            return false;
        }

        $realm_config = $db[$realm_group];

        return !empty($realm_config['hostname'])
            && !empty($realm_config['username'])
            && array_key_exists('password', $realm_config)
            && !empty($realm_config['database']);
    }

    private function _is_legacy_bundle_item_list($data) {
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

    private function _build_legacy_bundle_display_lines(array $item_ids, array &$visited_ids = []) {
        $lines = [];

        foreach ($item_ids as $item_id) {
            $item_id = (int) $item_id;
            if ($item_id <= 0) {
                continue;
            }

            $child_product = $this->get_product_by_id($item_id);
            if (!$child_product) {
                continue;
            }

            $child_description = json_decode($child_product['description'] ?? '', true);
            $is_nested_bundle = strtolower((string) ($child_product['product_type'] ?? '')) === 'bundles'
                && $this->_is_legacy_bundle_item_list($child_description);

            if ($is_nested_bundle) {
                if (isset($visited_ids[$item_id])) {
                    continue;
                }

                $visited_ids[$item_id] = true;
                $lines[] = $child_product['name'];
                $nested_lines = $this->_build_legacy_bundle_display_lines($child_description, $visited_ids);
                foreach ($nested_lines as $nested_line) {
                    $lines[] = 'Includes: ' . $nested_line;
                }
                unset($visited_ids[$item_id]);
                continue;
            }

            $lines[] = $child_product['name'];
        }

        return $lines;
    }

    public function enrich_product_description_for_display(array $product) {
        $product_type = strtolower((string) ($product['product_type'] ?? ''));
        if ($product_type !== 'bundles') {
            return $product;
        }

        $description_data = json_decode($product['description'] ?? '', true);
        if (!$this->_is_legacy_bundle_item_list($description_data)) {
            return $product;
        }

        $visited_ids = [];
        $display_lines = $this->_build_legacy_bundle_display_lines($description_data, $visited_ids);
        if (empty($display_lines)) {
            return $product;
        }

        $product['description'] = json_encode(
            ['Includes' => $display_lines],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        return $product;
    }

    /**
     * Mengambil daftar produk dari DATABASE berdasarkan nama realm.
     * @param string $realm_name Nama realm.
     * @return array Daftar produk.
     */
    public function get_products_by_realm($realm_name, $category) { // <-- Nama parameter kedua lebih cocok 'category'
        $this->db->where('realm', $realm_name);
        
        // --- PERUBAHAN DI SINI ---
        // Kita sekarang memfilter berdasarkan kolom 'category' yang baru.
        $this->db->where('category', $category); 
        
        $this->db->where('is_active', 1);
        $query = $this->db->get('products');
        return $query->result_array();
    }

    /**
     * Mengambil daftar produk dari DATABASE berdasarkan kategori (tanpa filter realm).
     * Dipakai untuk kategori yang sifatnya global seperti currency.
     * @param string $category Nama kategori.
     * @return array Daftar produk.
     */
    public function get_products_by_category($category) {
        $this->db->where('category', $category);
        $this->db->where('is_active', 1);
        $query = $this->db->get('products');
        return $query->result_array();
    }

    /**
     * Fungsi untuk mendapatkan satu produk dari DATABASE berdasarkan ID-nya.
     * @param string $product_id ID produk.
     * @return array|null Satu baris data produk atau null.
     */
    public function get_product_by_id($product_id) {
        $query = $this->db->get_where('products', ['id' => $product_id]);
        return $query->row_array(); // Mengembalikan SATU baris sebagai array asosiatif
    }

    public function is_bucks_kaget_product($product) {
        if (is_object($product)) {
            $product = (array) $product;
        }

        if (!is_array($product)) {
            return false;
        }

        return strtolower(trim((string) ($product['product_type'] ?? ''))) === 'bucks_kaget';
    }

    public function get_bucks_kaget_config($product) {
        if (!$this->is_bucks_kaget_product($product)) {
            return null;
        }

        if (is_object($product)) {
            $product = (array) $product;
        }

        $decoded = json_decode((string) ($product['description'] ?? ''), true);
        $config = is_array($decoded) ? $decoded : [];

        $default_total_bucks = max(1, (int) ($config['default_total_bucks'] ?? ($config['total_bucks'] ?? 10)));
        $min_total_bucks = max(1, (int) ($config['min_total_bucks'] ?? 1));
        $max_total_bucks = max($default_total_bucks, (int) ($config['max_total_bucks'] ?? 500));

        $default_recipients = max(1, (int) ($config['default_recipients'] ?? 10));
        $min_recipients = max(1, (int) ($config['min_recipients'] ?? 1));
        $max_recipients = max($default_recipients, (int) ($config['max_recipients'] ?? 100));

        $default_expiry_hours = max(1, (int) ($config['default_expiry_hours'] ?? 24));
        $min_expiry_hours = max(1, (int) ($config['min_expiry_hours'] ?? 1));
        $max_expiry_hours = max($default_expiry_hours, (int) ($config['max_expiry_hours'] ?? 168));

        $default_price_tiers = [
            ['min_qty' => 1, 'price_per_buck' => 7000],
            ['min_qty' => 10, 'price_per_buck' => 6850],
            ['min_qty' => 25, 'price_per_buck' => 6700],
            ['min_qty' => 50, 'price_per_buck' => 6550],
            ['min_qty' => 100, 'price_per_buck' => 6400],
            ['min_qty' => 200, 'price_per_buck' => 6250],
        ];

        $normalized_price_tiers = [];
        $raw_price_tiers = is_array($config['price_tiers'] ?? null) ? $config['price_tiers'] : [];
        foreach ($raw_price_tiers as $tier) {
            if (!is_array($tier)) {
                continue;
            }

            $min_qty = max(1, (int) ($tier['min_qty'] ?? 0));
            $price_per_buck = max(1, (int) ($tier['price_per_buck'] ?? ($tier['price_per_level'] ?? 0)));
            $normalized_price_tiers[$min_qty] = [
                'min_qty' => $min_qty,
                'price_per_buck' => $price_per_buck
            ];
        }

        if (empty($normalized_price_tiers)) {
            foreach ($default_price_tiers as $tier) {
                $normalized_price_tiers[$tier['min_qty']] = $tier;
            }
        }

        ksort($normalized_price_tiers);
        $normalized_price_tiers = array_values($normalized_price_tiers);

        return [
            'display_description' => trim((string) ($config['display_description'] ?? '')),
            'base_price_per_buck' => max(1, (int) round((float) ($product['price'] ?? 7000))),
            'default_total_bucks' => min($max_total_bucks, max($min_total_bucks, $default_total_bucks)),
            'min_total_bucks' => min($max_total_bucks, $min_total_bucks),
            'max_total_bucks' => $max_total_bucks,
            'default_recipients' => min($max_recipients, max($min_recipients, $default_recipients)),
            'min_recipients' => min($max_recipients, $min_recipients),
            'max_recipients' => $max_recipients,
            'default_expiry_hours' => min($max_expiry_hours, max($min_expiry_hours, $default_expiry_hours)),
            'min_expiry_hours' => min($max_expiry_hours, $min_expiry_hours),
            'max_expiry_hours' => $max_expiry_hours,
            'price_tiers' => $normalized_price_tiers
        ];
    }

public function get_player_rank($username, $realm_name, $rank_hierarchy) {
    if (empty($username) || empty($realm_name) || empty($rank_hierarchy)) {
        return null;
    }

    if (!$this->_supports_player_database($realm_name)) {
        return null;
    }

    $db_group = strtolower($realm_name);
    try {
        $realm_db = $this->load->database($db_group, TRUE);
        if (!$realm_db || !$realm_db->conn_id) { return null; }
    } catch (Exception $e) {
        log_message('error', 'get_player_rank: ' . $e->getMessage());
        return null;
    }

    // Langkah 1: Dapatkan UUID pemain dari tabel players berdasarkan username huruf kecil
    $player_query = $realm_db->select('uuid')->from('luckperms_players')->where('username', strtolower($username))->limit(1)->get();
    if ($player_query->num_rows() === 0) {
        return null; // Pemain tidak ditemukan di tabel players
    }
    $uuid = $player_query->row()->uuid;

    // Langkah 2: Dapatkan semua grup permanen yang dimiliki pemain dari tabel permissions
    $permissions_query = $realm_db->select('permission')
                                  ->from('luckperms_user_permissions')
                                  ->where('uuid', $uuid)
                                  ->where('permission LIKE', 'group.%') // Cari permission yang merupakan grup
                                  ->where('expiry', 0) // <-- Kunci: Hanya ambil yang permanen (tidak ada waktu kedaluwarsa)
                                  ->get();

    if ($permissions_query->num_rows() === 0) {
        return 'default'; // Jika tidak punya rank, anggap 'default'
    }

    $highest_rank = 'default';
    $highest_weight = $rank_hierarchy['default'] ?? 0;

    // Langkah 3: Loop semua grup dan cari yang bobotnya paling tinggi
    foreach ($permissions_query->result() as $row) {
        $rank_name = str_replace('group.', '', $row->permission);
        
        // Cek apakah rank ini ada di hierarki kita
        if (isset($rank_hierarchy[$rank_name])) {
            $current_weight = $rank_hierarchy[$rank_name];
            // Jika bobot rank saat ini lebih tinggi dari yang sudah disimpan, perbarui
            if ($current_weight > $highest_weight) {
                $highest_weight = $current_weight;
                $highest_rank = $rank_name;
            }
        }
    }

    return $highest_rank;
}
    
public function has_player_joined_realm($username, $realm_name)
{
    if (!$this->_supports_player_database($realm_name)) {
        return true;
    }

    // Konversi nama realm menjadi nama grup database
    $db_group = strtolower($realm_name);

    try {
        $realm_db = $this->load->database($db_group, TRUE);
        if (!$realm_db || !$realm_db->conn_id) {
            log_message('error', 'Gagal terhubung ke database realm: ' . $db_group);
            return false;
        }
    } catch (Exception $e) {
        log_message('error', 'Error saat memuat database realm: ' . $e->getMessage());
        return false;
    }
    
    // Jalankan query dengan mengubah username dari session menjadi huruf kecil
    $query = $realm_db->select('uuid')
                      ->from('luckperms_players')
                      ->where('username', strtolower($username)) // <-- Kunci perubahannya di sini
                      ->limit(1)
                      ->get();

    return $query->num_rows() === 1;
}
// public function has_player_joined_realm($uuid, $realm_db_group) {
//         // Selalu anggap pemain sudah pernah join untuk sementara
//         return true; 
//     }

    /**
     * Mengambil daftar top donatur berdasarkan total pembelian.
     * @param int $limit Jumlah donatur yang ingin ditampilkan.
     * @return array Daftar top donatur.
     */
    public function get_top_donators($limit = 5) {
        $this->db->select('player_username, SUM(grand_total) as total_spent');
        $this->db->from('transactions');
        $this->db->where('status', 'completed');
        $this->db->group_by('player_username');
        $this->db->order_by('total_spent', 'DESC');
        $this->db->limit($limit);
        
        $query = $this->db->get();
        return $query->result_array();
    }
    
// GANTI FUNGSI LAMA ANDA DENGAN VERSI BARU INI

public function get_cross_sell_products($cart_items = [], $limit = 6) {
    if (empty($cart_items)) {
        return [];
    }

    $exclude_ids = array_column($cart_items, 'id');
    
    // === PERBAIKAN DI SINI ===
    // Menggunakan fungsi distinct() bawaan CodeIgniter
    $realms_in_cart = $this->db
                           ->distinct()
                           ->select('realm')
                           ->where_in('id', $exclude_ids)
                           ->get('products')
                           ->result_array();

    if (empty($realms_in_cart)) {
        return [];
    }
    
    $realm_names = array_column($realms_in_cart, 'realm');

    // Query utama untuk mencari produk rekomendasi
    $this->db->select('*');
    $this->db->from('products');
    $this->db->where_in('category', ['bundles', 'currency']);
    $this->db->where_in('realm', $realm_names);
    $this->db->where_not_in('id', $exclude_ids);
    $this->db->where('is_active', 1);
    $this->db->order_by('RAND()');
    $this->db->limit($limit);

    $query = $this->db->get();
    return $query->result_array();
}
}
