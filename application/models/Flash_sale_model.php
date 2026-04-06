<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Flash_sale_model extends CI_Model {
    private $table = 'flash_sales';

    public function get_all() {
        $this->db->select('fs.*, p.name as product_name');
        $this->db->from($this->table . ' as fs');
        $this->db->join('products as p', 'fs.product_id = p.id', 'left');
        $this->db->order_by('fs.start_date', 'DESC');
        return $this->db->get()->result();
    }

    public function get_by_id($id) {
        return $this->db->get_where($this->table, ['id' => $id])->row();
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
    
    public function insert_batch($data) {
        if (empty($data)) {
            return false;
        }
        return $this->db->insert_batch($this->table, $data);
    }
    
    public function delete_expired_sales() {
        // Langkah 1: Ambil DULU semua ID sale yang sudah kedaluwarsa
        $this->db->select('id');
        $this->db->where('end_date <', 'DATE_SUB(NOW(), INTERVAL 1 HOUR)', FALSE);
        $query = $this->db->get($this->table);
        $expired_sales = $query->result();

        // Langkah 2: Jika tidak ada apa-apa, selesaikan
        if (empty($expired_sales)) {
            return 0; // Tidak ada yang dihapus
        }

        // Langkah 3: Kumpulkan semua ID ke dalam satu array
        $expired_ids = [];
        foreach ($expired_sales as $sale) {
            $expired_ids[] = $sale->id;
        }

        // Langkah 4: Hapus semua sale berdasarkan ID-nya (lolos safe mode)
        $this->db->where_in('id', $expired_ids);
        $this->db->delete($this->table);
        
        // Mengembalikan jumlah baris yang terhapus
        return $this->db->affected_rows();
    }

    // Fungsi untuk mengambil flash sale yang sedang aktif
    public function get_active_flash_sales($limit = 3) {
        $now = date('Y-m-d H:i:s');
        $this->db->select('fs.*, p.name, p.image_url, p.price as original_price, p.realm, p.category, p.product_type, p.description');
        $this->db->from('flash_sales as fs');
        $this->db->join('products as p', 'fs.product_id = p.id', 'left');
        $this->db->where('fs.is_active', 1);
        $this->db->where('fs.start_date <=', $now);
        $this->db->where('fs.end_date >=', $now);
        $this->db->where('fs.stock_sold < fs.stock_limit');
        $this->db->order_by('fs.end_date', 'ASC'); // Prioritaskan yang akan segera berakhir
        $this->db->limit($limit);
        return $this->db->get()->result(); // Mengembalikan array of objects
    }
    
    // Fungsi untuk mengambil detail satu flash sale (untuk Cart)
    public function get_active_flash_sale_by_id($id) {
        $now = date('Y-m-d H:i:s');
        // [PERBAIKAN] Ambil semua data produk yang relevan
        $this->db->select('fs.*, p.name, p.price as original_price, p.realm, p.category, p.product_type, p.description, p.image_url');
        $this->db->from($this->table . ' as fs');
        $this->db->join('products as p', 'fs.product_id = p.id', 'left');
        $this->db->where('fs.id', $id);
        $this->db->where('fs.is_active', 1);
        $this->db->where('fs.start_date <=', $now);
        $this->db->where('fs.end_date >=', $now);
        $this->db->where('fs.stock_sold < fs.stock_limit');
        $this->db->limit(1);
        return $this->db->get()->row();
    }
}