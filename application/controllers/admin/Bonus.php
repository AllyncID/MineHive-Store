<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bonus extends Admin_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('Bonus_model');
        $this->load->library('form_validation'); // Muat library validasi
    }

    public function index() {
        $data['title'] = 'Manage Top Up Bonus';
        $data['tiers'] = $this->Bonus_model->get_all();
        $this->load->view('admin/templates/admin_header', $data);
        $this->load->view('admin/bonus/index', $data);
        $this->load->view('admin/templates/admin_footer');
    }
    
    public function add() {
        // Aturan validasi form
        $this->form_validation->set_rules('min_amount', 'Total Minimum', 'required|numeric');
        $this->form_validation->set_rules('reward_description', 'Deskripsi Hadiah', 'required|trim');
        $this->form_validation->set_rules('reward_command', 'Command Hadiah', 'required|trim');

        if ($this->form_validation->run() === FALSE) {
            // Jika validasi gagal, tampilkan form lagi
            $data['title'] = 'Tambah Tier Bonus Baru';
            $this->load->view('admin/templates/admin_header', $data);
            $this->load->view('admin/bonus/form', $data);
            $this->load->view('admin/templates/admin_footer');
        } else {
            // Jika validasi sukses, simpan data
            $data_to_save = [
                'min_amount' => $this->input->post('min_amount'),
                'max_amount' => $this->input->post('max_amount') ?: NULL, // Simpan NULL jika kosong
                'reward_description' => $this->input->post('reward_description'),
                'reward_command' => $this->input->post('reward_command'),
                'is_active' => $this->input->post('is_active')
            ];
            $this->Bonus_model->insert($data_to_save);
            $this->session->set_flashdata('success', 'Tier bonus baru berhasil ditambahkan.');
            redirect('admin/bonus');
        }
    }
    
    public function edit($id) {
        $data['tier'] = $this->Bonus_model->get_by_id($id);
        if (!$data['tier']) { show_404(); }

        $this->form_validation->set_rules('min_amount', 'Total Minimum', 'required|numeric');
        $this->form_validation->set_rules('reward_description', 'Deskripsi Hadiah', 'required|trim');
        $this->form_validation->set_rules('reward_command', 'Command Hadiah', 'required|trim');
        
        if ($this->form_validation->run() === FALSE) {
            $data['title'] = 'Edit Tier Bonus';
            $this->load->view('admin/templates/admin_header', $data);
            $this->load->view('admin/bonus/form', $data);
            $this->load->view('admin/templates/admin_footer');
        } else {
            $data_to_update = [
                'min_amount' => $this->input->post('min_amount'),
                'max_amount' => $this->input->post('max_amount') ?: NULL,
                'reward_description' => $this->input->post('reward_description'),
                'reward_command' => $this->input->post('reward_command'),
                'is_active' => $this->input->post('is_active')
            ];
            $this->Bonus_model->update($id, $data_to_update);
            $this->session->set_flashdata('success', 'Tier bonus berhasil diperbarui.');
            redirect('admin/bonus');
        }
    }

    public function delete($id) {
        $this->Bonus_model->delete($id);
        $this->session->set_flashdata('success', 'Tier bonus berhasil dihapus.');
        redirect('admin/bonus');
    }
}