<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Discount_model extends CI_Model {

    public function get_all_discounts() {
        // Mengambil semua diskon beserta targetnya menggunakan GROUP_CONCAT
        $this->db->select("d.*, GROUP_CONCAT(CONCAT(ds.scope_type, ':', ds.scope_value) SEPARATOR ';') as scopes");
        $this->db->from('discounts as d');
        $this->db->join('discount_scopes as ds', 'd.id = ds.discount_id', 'left');
        $this->db->group_by('d.id');
        $this->db->order_by('d.starts_at', 'DESC');
        return $this->db->get()->result();
    }

    // [FUNGSI BARU]
    public function get_by_id($id) {
        return $this->db->get_where('discounts', ['id' => $id])->row();
    }

    // [FUNGSI BARU]
    public function get_scopes_by_discount_id($id) {
        return $this->db->get_where('discount_scopes', ['discount_id' => $id])->result();
    }

    public function insert_discount($discount_data, $scopes_data) {
        $this->db->trans_start();
        $this->db->insert('discounts', $discount_data);
        $discount_id = $this->db->insert_id();

        foreach ($scopes_data as $scope) {
            $scope['discount_id'] = $discount_id;
            $this->db->insert('discount_scopes', $scope);
        }
        $this->db->trans_complete();
        return $this->db->trans_status();
    }
    
    // [FUNGSI BARU]
    public function update_discount($id, $discount_data, $scopes_data) {
        $this->db->trans_start();
        
        // 1. Update data diskon utama
        $this->db->update('discounts', $discount_data, ['id' => $id]);
        
        // 2. Hapus scope lama
        $this->db->delete('discount_scopes', ['discount_id' => $id]);
        
        // 3. Masukkan scope baru
        if (!empty($scopes_data)) {
            foreach ($scopes_data as $scope) {
                $scope['discount_id'] = $id;
                $this->db->insert('discount_scopes', $scope);
            }
        }
        
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    public function find_active_discount_for_product($product_id, $category_name) {
        // Kita tidak lagi menggunakan $now dari PHP, biarkan MySQL yang bekerja.

        // Prioritas 1: Cari diskon spesifik untuk produk ini
        $this->db->select('d.*');
        $this->db->from('discounts as d');
        $this->db->join('discount_scopes as ds', 'd.id = ds.discount_id');
        $this->db->where('d.is_active', 1);
        $this->db->where('d.starts_at <= NOW()', NULL, FALSE); // Menggunakan NOW() MySQL
        $this->db->where('d.ends_at >= NOW()', NULL, FALSE);   // Menggunakan NOW() MySQL
        $this->db->where('ds.scope_type', 'product');
        $this->db->where('ds.scope_value', $product_id);
        $product_discount = $this->db->get()->row();
        
        if ($product_discount) {
            return $product_discount;
        }

        // Prioritas 2: Jika tidak ada, cari diskon untuk kategori produk ini
        $this->db->select('d.*');
        $this->db->from('discounts as d');
        $this->db->join('discount_scopes as ds', 'd.id = ds.discount_id');
        $this->db->where('d.is_active', 1);
        $this->db->where('d.starts_at <= NOW()', NULL, FALSE);
        $this->db->where('d.ends_at >= NOW()', NULL, FALSE);
        $this->db->where('ds.scope_type', 'category');
        $this->db->where('ds.scope_value', $category_name);
        $category_discount = $this->db->get()->row();

        if ($category_discount) {
            return $category_discount;
        }

        // Prioritas 3: Jika masih tidak ada, cari diskon untuk seluruh toko
        $this->db->select('d.*');
        $this->db->from('discounts as d');
        $this->db->join('discount_scopes as ds', 'd.id = ds.discount_id');
        $this->db->where('d.is_active', 1);
        $this->db->where('d.starts_at <= NOW()', NULL, FALSE);
        $this->db->where('d.ends_at >= NOW()', NULL, FALSE);
        $this->db->where('ds.scope_type', 'store-wide');
        $store_discount = $this->db->get()->row();

        return $store_discount;
    }

    public function delete_discount($id) {
        $this->db->trans_start();
        $this->db->delete('discounts', ['id' => $id]);
        $this->db->delete('discount_scopes', ['discount_id' => $id]);
        $this->db->trans_complete();
        return $this->db->trans_status();
    }
    // Fungsi untuk edit dan delete bisa ditambahkan di sini nanti jika diperlukan
}