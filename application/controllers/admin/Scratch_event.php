<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Scratch_event extends Admin_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Scratch_event_model');
        $this->load->model('Reward_bank_model');
        $this->load->model('Settings_model');
        $this->load->library('form_validation');
    }

    public function index() {
        $data['title'] = 'Pengaturan Event Gosok Berhadiah';
        $data['settings'] = $this->Settings_model->get_all_settings();
        $data['tiers'] = $this->Scratch_event_model->get_all_tiers_with_rewards();
        
        $this->load->view('admin/templates/admin_header', $data);
        $this->load->view('admin/scratch_event/index', $data);
        $this->load->view('admin/templates/admin_footer');
    }

    public function update_settings() {
        if ($this->input->post()) {
            $settings_data = [
                'scratch_event_enabled' => $this->input->post('scratch_event_enabled') ? '1' : '0',
                'scratch_event_title'   => $this->input->post('scratch_event_title')
            ];
            $this->Settings_model->update_batch($settings_data);
            $this->session->set_flashdata('success', 'Pengaturan event berhasil disimpan.');
        }
        redirect('admin/scratch_event');
    }

    public function add_tier() {
        $this->form_validation->set_rules('title', 'Judul Tier', 'required|trim');
        $this->form_validation->set_rules('min_amount', 'Minimal Belanja', 'required|numeric');

        if ($this->form_validation->run() === FALSE) {
            $data['title'] = 'Tambah Tier Baru';
            $data['all_rewards'] = $this->Reward_bank_model->get_all_active();
            $this->load->view('admin/templates/admin_header', $data);
            $this->load->view('admin/scratch_event/form_tier', $data);
            $this->load->view('admin/templates/admin_footer');
        } else {
            $tier_data = [
                'title'      => $this->input->post('title'),
                'min_amount' => $this->input->post('min_amount'),
                'max_amount' => $this->input->post('max_amount') ?: NULL,
                'is_active'  => $this->input->post('is_active') ? 1 : 0
            ];
            $reward_ids = $this->input->post('reward_ids') ?? [];

            $this->Scratch_event_model->add_tier($tier_data, $reward_ids);
            $this->session->set_flashdata('success', 'Tier baru berhasil ditambahkan.');
            redirect('admin/scratch_event');
        }
    }

    public function edit_tier($id) {
        $data['tier'] = $this->Scratch_event_model->get_tier_by_id($id);
        if (!$data['tier']) { show_404(); }

        $data['all_rewards'] = $this->Reward_bank_model->get_all_active();
        $data['assigned_reward_ids'] = $this->Scratch_event_model->get_reward_ids_for_tier($id);

        $this->form_validation->set_rules('title', 'Judul Tier', 'required|trim');
        $this->form_validation->set_rules('min_amount', 'Minimal Belanja', 'required|numeric');

        if ($this->form_validation->run() === FALSE) {
            $data['title'] = 'Edit Tier: ' . $data['tier']->title;
            $this->load->view('admin/templates/admin_header', $data);
            $this->load->view('admin/scratch_event/form_tier', $data);
            $this->load->view('admin/templates/admin_footer');
        } else {
            $tier_data = [
                'title'      => $this->input->post('title'),
                'min_amount' => $this->input->post('min_amount'),
                'max_amount' => $this->input->post('max_amount') ?: NULL,
                'is_active'  => $this->input->post('is_active') ? 1 : 0
            ];
            $reward_ids = $this->input->post('reward_ids') ?? [];

            $this->Scratch_event_model->update_tier($id, $tier_data, $reward_ids);
            $this->session->set_flashdata('success', 'Tier berhasil diperbarui.');
            redirect('admin/scratch_event');
        }
    }

    public function delete_tier($id) {
        $this->Scratch_event_model->delete_tier($id);
        $this->session->set_flashdata('success', 'Tier berhasil dihapus.');
        redirect('admin/scratch_event');
    }
}