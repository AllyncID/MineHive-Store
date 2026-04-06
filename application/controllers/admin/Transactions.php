<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Transactions extends Admin_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Transaction_model');
    }

    public function index() {
        // Logika filter, sama seperti di Dashboard.php
        $range = $this->input->get('range', TRUE) ?: 'all'; // Default ke 'all'
        $days = null;
        $filter_title = 'Semua Waktu';

        switch ($range) {
            case 'today': $days = 1; $filter_title = 'Hari Ini'; break;
            case '7days': $days = 7; $filter_title = '7 Hari Terakhir'; break;
            case '30days': $days = 30; $filter_title = '30 Hari Terakhir'; break;
        }
        
        // Panggil model dengan parameter filter
        $data['transactions'] = $this->Transaction_model->get_all_transactions($days);
        $data['title'] = 'Riwayat Transaksi';
        $data['filter_title'] = $filter_title;
        $data['current_range'] = $range;
        
        $this->load->view('admin/templates/admin_header', $data);
        $this->load->view('admin/transactions/index', $data);
        $this->load->view('admin/templates/admin_footer');
    }
}