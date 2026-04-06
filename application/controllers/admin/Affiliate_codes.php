<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Affiliate_codes extends Admin_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('Affiliate_model');
    }

    public function index() {
        $data['codes'] = $this->Affiliate_model->get_all_affiliate_codes();
        $data['title'] = 'Manajemen Kode Afiliasi';
        $this->load->view('admin/templates/admin_header', $data);
        $this->load->view('admin/affiliate_codes/index', $data);
        $this->load->view('admin/templates/admin_footer');
    }

    public function add() {
        if ($this->input->post()) {
            $data = $this->input->post();
            $this->Affiliate_model->insert_affiliate_code($data);
            $this->session->set_flashdata('success', 'Kode afiliasi baru berhasil dibuat.');
            redirect('admin/affiliate_codes');
        }
        $data['affiliates'] = $this->Affiliate_model->get_all_affiliates();
        $data['title'] = 'Buat Kode Afiliasi Baru';
        $this->load->view('admin/templates/admin_header', $data);
        $this->load->view('admin/affiliate_codes/form', $data);
        $this->load->view('admin/templates/admin_footer');
    }
}