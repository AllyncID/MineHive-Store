<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Customers extends Admin_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Transaction_model');
        // Helper date untuk format waktu jika diperlukan
        $this->load->helper('date');
    }

    public function index() {
        // Ambil parameter filter dari URL, default ke 'lifetime_desc'
        $filter = $this->input->get('filter', TRUE) ?: 'lifetime_desc';

        // Konfigurasi filter
        $order = 'DESC';
        $period = 'all';
        $filter_title = 'Top Lifetime (Tertinggi)';

        switch ($filter) {
            case 'lifetime_desc':
                $order = 'DESC';
                $period = 'all';
                $filter_title = 'Top Lifetime (Sultan)';
                break;
            case 'lifetime_asc':
                $order = 'ASC';
                $period = 'all';
                $filter_title = 'Lifetime (Terendah)';
                break;
            case 'month_desc':
                $order = 'DESC';
                $period = 'month';
                $filter_title = 'Top Bulan Ini (Sultan)';
                break;
            case 'month_asc':
                $order = 'ASC';
                $period = 'month';
                $filter_title = 'Bulan Ini (Terendah)';
                break;
        }

        // Ambil data dari model
        $data['customers'] = $this->Transaction_model->get_top_spenders($order, $period);
        
        $data['title'] = 'Top Spenders';
        $data['filter_title'] = $filter_title;
        $data['current_filter'] = $filter;

        $this->load->view('admin/templates/admin_header', $data);
        $this->load->view('admin/customers/index', $data);
        $this->load->view('admin/templates/admin_footer');
    }
}