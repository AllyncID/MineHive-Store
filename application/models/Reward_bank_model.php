<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reward_bank_model extends CI_Model {
    
    private $table = 'reward_bank';

    public function get_all() {
        return $this->db->order_by('id', 'DESC')->get($this->table)->result();
    }

    public function get_all_active() {
        $this->db->where('is_active', 1);
        return $this->db->order_by('display_name', 'ASC')->get($this->table)->result();
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
}