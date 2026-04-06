<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Settings_model extends CI_Model {

    public function get_all_settings() {
        $settings_array = [];
        $query = $this->db->get('site_settings');
        foreach ($query->result() as $row) {
            $settings_array[$row->setting_name] = $row->setting_value;
        }
        return $settings_array;
    }

    public function update_batch($data) {
        foreach ($data as $name => $value) {
            // Gunakan 'REPLACE' atau 'INSERT...ON DUPLICATE KEY UPDATE'
            // REPLACE lebih sederhana untuk kasus ini
            $this->db->replace('site_settings', [
                'setting_name' => $name,
                'setting_value' => $value
            ]);
        }
        return true;
    }
}