<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Promo_model extends CI_Model {

    public function get_all_with_usage() {
        // [PERUBAHAN] Pastikan p.* mengambil kolom baru
        $this->db->select('p.*, COUNT(t.id) as usage_count');
        $this->db->from('promo_codes as p');
        // Kita asumsikan ada tabel 'transactions' dengan kolom 'promo_code_used'
        $this->db->join('transactions as t', 'p.code = t.promo_code_used', 'left');
        $this->db->group_by('p.id');
        $query = $this->db->get();
        return $query->result();
    }
    
    public function get_by_id($id) {
        return $this->db->get_where('promo_codes', ['id' => $id])->row();
    }

    public function insert($data) {
        return $this->db->insert('promo_codes', $data);
    }

    public function update($id, $data) {
        return $this->db->update('promo_codes', $data, ['id' => $id]);
    }

    public function delete($id) {
        return $this->db->delete('promo_codes', ['id' => $id]);
    }

    public function get_by_code($code) {
        // [PERUBAHAN] Fungsi ini HANYA mengambil data.
        // Controller yang akan mengecek 'is_active'.
        return $this->db->get_where('promo_codes', ['code' => $code])->row();
    }

    public function increment_usage($code) {
        $this->db->where('code', $code);
        $this->db->set('usage_count', 'usage_count+1', FALSE); // FALSE agar tidak di-escape
        return $this->db->update('promo_codes');
    }
    
    // GANTI FUNGSI LAMA DENGAN INI
    public function get_all_codes_for_redeem() {
        // 'description' dihapus dari select
        // [PERUBAHAN] Hanya ambil kode yang aktif
        return $this->db->select('id, code')->where('is_active', 1)->order_by('code', 'ASC')->get('promo_codes')->result();
    }
}
