<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bonus_model extends CI_Model {
    private $table = 'topup_bonus_tiers';

    public function get_all() {
        return $this->db->order_by('min_amount', 'ASC')->get($this->table)->result();
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

    public function get_tier_for_amount($amount) {
        $this->db->where('is_active', 1);
        $this->db->where('min_amount <=', $amount);
        // Cek max_amount atau jika max_amount adalah NULL (untuk tier teratas)
        $this->db->where('(max_amount >= ' . $this->db->escape($amount) . ' OR max_amount IS NULL)');
        $this->db->order_by('min_amount', 'DESC'); // Ambil tier tertinggi yang memenuhi syarat
        $this->db->limit(1);
        return $this->db->get($this->table)->row();
    }
}