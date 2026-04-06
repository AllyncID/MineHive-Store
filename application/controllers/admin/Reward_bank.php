<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reward_bank extends Admin_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Reward_bank_model');
        $this->load->library('form_validation');
    }

    public function index() {
        $data['title'] = 'Bank Hadiah (Scratch & Win)';
        $data['rewards'] = $this->Reward_bank_model->get_all();
        $this->load->view('admin/templates/admin_header', $data);
        $this->load->view('admin/reward_bank/index', $data);
        $this->load->view('admin/templates/admin_footer');
    }

    public function add() {
        $this->form_validation->set_rules('display_name', 'Nama Hadiah (Tampilan)', 'required|trim');
        $this->form_validation->set_rules('reward_type', 'Tipe Hadiah', 'required');
        $this->form_validation->set_rules('reward_value', 'Isi Hadiah (Value)', 'required|trim');

        if ($this->form_validation->run() === FALSE) {
            $data['title'] = 'Tambah Hadiah Baru';
            $this->load->view('admin/templates/admin_header', $data);
            $this->load->view('admin/reward_bank/form', $data);
            $this->load->view('admin/templates/admin_footer');
        } else {
            $data_to_save = [
                'display_name'   => $this->input->post('display_name'),
                'reward_type'    => $this->input->post('reward_type'),
                'reward_value'   => $this->input->post('reward_value'),
                'is_active'      => $this->input->post('is_active') ? 1 : 0
            ];
            $this->Reward_bank_model->insert($data_to_save);
            $this->session->set_flashdata('success', 'Hadiah baru berhasil ditambahkan ke Bank.');
            redirect('admin/reward_bank');
        }
    }

    public function edit($id) {
        $data['reward'] = $this->Reward_bank_model->get_by_id($id);
        if (!$data['reward']) { show_404(); }

        $this->form_validation->set_rules('display_name', 'Nama Hadiah (Tampilan)', 'required|trim');
        $this->form_validation->set_rules('reward_type', 'Tipe Hadiah', 'required');
        $this->form_validation->set_rules('reward_value', 'Isi Hadiah (Value)', 'required|trim');

        if ($this->form_validation->run() === FALSE) {
            $data['title'] = 'Edit Hadiah';
            $this->load->view('admin/templates/admin_header', $data);
            $this->load->view('admin/reward_bank/form', $data);
            $this->load->view('admin/templates/admin_footer');
        } else {
            $data_to_update = [
                'display_name'   => $this->input->post('display_name'),
                'reward_type'    => $this->input->post('reward_type'),
                'reward_value'   => $this->input->post('reward_value'),
                'is_active'      => $this->input->post('is_active') ? 1 : 0
            ];
            $this->Reward_bank_model->update($id, $data_to_update);
            $this->session->set_flashdata('success', 'Hadiah berhasil diperbarui.');
            redirect('admin/reward_bank');
        }
    }

    public function delete($id) {
        $this->Reward_bank_model->delete($id);
        $this->session->set_flashdata('success', 'Hadiah berhasil dihapus dari Bank.');
        redirect('admin/reward_bank');
    }
}