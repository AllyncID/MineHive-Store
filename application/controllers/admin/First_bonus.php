<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controller Admin untuk Mengelola Bonus Pembelian Pertama
 * Dibuat untuk fitur bonus pemain baru.
 */
class First_bonus extends Admin_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('First_bonus_model');
        $this->load->library('form_validation');
    }

    /**
     * Menampilkan daftar semua command bonus
     */
    public function index() {
        $data['title'] = 'Manage Bonus Pembelian Pertama';
        $data['bonuses'] = $this->First_bonus_model->get_all();
        
        $this->load->view('admin/templates/admin_header', $data);
        $this->load->view('admin/first_bonus/index', $data);
        $this->load->view('admin/templates/admin_footer');
    }

    /**
     * Form untuk menambah command bonus baru
     */
    public function add() {
        $this->form_validation->set_rules('description', 'Deskripsi', 'required|trim');
        $this->form_validation->set_rules('reward_command', 'Command Hadiah', 'required|trim');

        if ($this->form_validation->run() === FALSE) {
            $data['title'] = 'Tambah Command Bonus Baru';
            $this->load->view('admin/templates/admin_header', $data);
            $this->load->view('admin/first_bonus/form', $data);
            $this->load->view('admin/templates/admin_footer');
        } else {
            $data_to_save = [
                'description'    => $this->input->post('description'),
                'reward_command' => $this->input->post('reward_command'),
                'is_active'      => $this->input->post('is_active') ? 1 : 0
            ];
            $this->First_bonus_model->insert($data_to_save);
            $this->session->set_flashdata('success', 'Command bonus baru berhasil ditambahkan.');
            redirect('admin/first_bonus');
        }
    }

    /**
     * Form untuk mengedit command bonus
     */
    public function edit($id) {
        $data['bonus'] = $this->First_bonus_model->get_by_id($id);
        if (!$data['bonus']) {
            show_404();
        }

        $this->form_validation->set_rules('description', 'Deskripsi', 'required|trim');
        $this->form_validation->set_rules('reward_command', 'Command Hadiah', 'required|trim');

        if ($this->form_validation->run() === FALSE) {
            $data['title'] = 'Edit Command Bonus';
            $this->load->view('admin/templates/admin_header', $data);
            $this->load->view('admin/first_bonus/form', $data);
            $this->load->view('admin/templates/admin_footer');
        } else {
            $data_to_update = [
                'description'    => $this->input->post('description'),
                'reward_command' => $this->input->post('reward_command'),
                'is_active'      => $this->input->post('is_active') ? 1 : 0
            ];
            $this->First_bonus_model->update($id, $data_to_update);
            $this->session->set_flashdata('success', 'Command bonus berhasil diperbarui.');
            redirect('admin/first_bonus');
        }
    }

    /**
     * Menghapus command bonus
     */
    public function delete($id) {
        $this->First_bonus_model->delete($id);
        $this->session->set_flashdata('success', 'Command bonus berhasil dihapus.');
        redirect('admin/first_bonus');
    }
}
