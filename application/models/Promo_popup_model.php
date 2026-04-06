<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Promo_popup_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
    }

    public function get_promo_data()
    {
        // Fetch the single row of configuration
        // Assuming table 'promo_popup_5tier' exists
        $query = $this->db->get('promo_popup_5tier', 1);
        return $query->row_array();
    }

    public function update_promo_data($data)
    {
        // Update the single row
        // We use update without a specific WHERE because there's generally only one row for global config
        // If you have multiple rows, you might need to specify an ID, e.g., where('id', 1)
        return $this->db->update('promo_popup_5tier', $data);
    }
}