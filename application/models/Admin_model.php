<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function get_by_username($username) {
        return $this->db->get_where('admins', ['username' => $username])->row();
    }

    // --- FUNGSI BARU UNTUK DASHBOARD ---

    public function count_total_products() {
        return $this->db->count_all('products');
    }

    // --- UBAH FUNGSI DI BAWAH INI ---

    public function count_total_transactions($days = null) {
        if ($days) {
            // Jika ada parameter hari, filter berdasarkan rentang waktu
            $this->db->where('created_at >=', 'CURDATE() - INTERVAL ' . (int)$days . ' DAY', FALSE);
        }
        return $this->db->count_all_results('transactions');
    }

    public function calculate_total_revenue($days = null) {
        $this->db->select_sum('grand_total');
        if ($days) {
            // Jika ada parameter hari, filter berdasarkan rentang waktu
            $this->db->where('created_at >=', 'CURDATE() - INTERVAL ' . (int)$days . ' DAY', FALSE);
        }
        $this->db->where('status', 'completed');
        $query = $this->db->get('transactions');
        $result = $query->row();
        return ($result->grand_total) ? $result->grand_total : 0;
    }

    // Fungsi untuk grafik sudah cukup fleksibel, tidak perlu diubah
    public function get_daily_sales_for_chart($days = 7) {
        $this->db->select("DATE(created_at) as sale_date, SUM(grand_total) as daily_total");
        $this->db->from('transactions');
        $this->db->where('created_at >=', 'CURDATE() - INTERVAL ' . ($days - 1) . ' DAY', FALSE);
        $this->db->where('status', 'completed');
        $this->db->group_by('DATE(created_at)');
        $this->db->order_by('sale_date', 'ASC');
        
        $query = $this->db->get();
        return $query->result_array();
    }
}