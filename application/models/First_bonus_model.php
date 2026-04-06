<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Model untuk mengelola tabel 'first_purchase_bonus'
 * Dibuat untuk fitur bonus pemain baru.
 */
class First_bonus_model extends CI_Model {
    
    private $table = 'first_purchase_bonus';

    public function get_all() {
        return $this->db->order_by('id', 'DESC')->get($this->table)->result();
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

    /**
     * Mengambil semua command bonus yang sedang aktif
     */
    public function get_active_commands() {
        $this->db->where('is_active', 1);
        return $this->db->get($this->table)->result();
    }
}
