<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Featured_model extends CI_Model {
    
    private $table = 'featured_products';

    // Ambil semua featured product dengan detail produknya
    public function get_all() {
        $this->db->select('fp.*, p.name as product_name, p.price, p.realm');
        $this->db->from($this->table . ' as fp');
        $this->db->join('products as p', 'fp.product_id = p.id');
        $this->db->order_by('fp.sort_order', 'ASC');
        $this->db->order_by('fp.id', 'DESC');
        return $this->db->get()->result();
    }

    // Ambil featured products untuk Homepage (Default Limit 12)
    public function get_active_featured($limit = 12) {
        $this->db->select('fp.*, p.name, p.image_url, p.price as original_price, p.realm, p.category, p.product_type, p.description');
        $this->db->from($this->table . ' as fp');
        $this->db->join('products as p', 'fp.product_id = p.id');
        // Pastikan produk aslinya juga aktif
        $this->db->where('p.is_active', 1);
        $this->db->order_by('fp.sort_order', 'ASC');
        $this->db->limit($limit);
        return $this->db->get()->result();
    }

    public function get_by_id($id) {
        return $this->db->get_where($this->table, ['id' => $id])->row();
    }

    // Hitung jumlah item saat ini (untuk validasi max 12)
    public function count_all() {
        return $this->db->count_all($this->table);
    }

    public function insert($data) {
        return $this->db->insert($this->table, $data);
    }

    public function update($id, $data) {
        return $this->db->update($this->table, $data, ['id' => $id]);
    }

    public function delete($id) {
        return $this->db->delete($this->table, ['id' => $id]);
    }
}