<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Product_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function get_all_products($realm = NULL) {
        if ($realm && $realm != 'all') {
            $this->db->where('realm', $realm);
        }
        // sisanya sama
        $query = $this->db->get('products');
        return $query->result();
    }

    /**
     * [BARU] Mengambil produk yang valid untuk random flash sale
     *
     * @param array $excluded_ids Array berisi ID produk yang tidak boleh dipilih
     * @return array Daftar produk yang eligible
     */
    public function get_eligible_products_for_random_sale($excluded_ids = []) {
        // [MODIFIKASI] Tambahkan image_url
        $this->db->select('id, name, price, image_url'); // Hanya ambil data yang perlu
        $this->db->from('products');
        $this->db->where('is_active', 1); // Hanya produk yang aktif
        
        // Filter produk yang harganya tidak 0 (opsional tapi bagus)
        $this->db->where('price >', 0); 
        
        if (!empty($excluded_ids)) {
            $this->db->where_not_in('id', $excluded_ids);
        }
        
        // Ambil semua produk yang memenuhi syarat
        return $this->db->get()->result();
    }

    public function get_by_id($id) {
        return $this->db->get_where('products', ['id' => $id])->row();
    }

    public function insert($data) {
        return $this->db->insert('products', $data);
    }

    public function update($id, $data) {
        return $this->db->update('products', $data, ['id' => $id]);
    }

    public function delete($id) {
        return $this->db->delete('products', ['id' => $id]);
    }

    public function duplicate($id) {
        // 1. Ambil data produk asli
        $original_product = $this->get_by_id($id);

        if ($original_product) {
            // 2. Ubah object menjadi array untuk persiapan insert
            $new_data = (array) $original_product;

            // 3. Hapus ID lama agar database membuat ID baru (auto-increment)
            unset($new_data['id']);

            // 4. Ubah nama produk untuk menandakan ini adalah salinan
            $new_data['name'] = $original_product->name . ' (Duplikat)';
            
            // 5. (Rekomendasi) Set produk baru sebagai tidak aktif (draft)
            // Ini memberi admin kesempatan untuk mereview sebelum ditampilkan di toko.
            $new_data['is_active'] = 0;

            // 6. Masukkan data baru sebagai produk baru dan kembalikan statusnya
            if ($this->db->insert('products', $new_data)) {
                return $this->db->insert_id();
            }
        }

        return false; // Gagal jika produk asli tidak ditemukan
    }

    public function get_product_by_luckperms_group($group_name, $realm) {
        if (empty($group_name) || empty($realm)) {
            return null;
        }
        $this->db->where('luckperms_group', $group_name);
        $this->db->where('realm', $realm);
        return $this->db->get('products')->row();
    }
}