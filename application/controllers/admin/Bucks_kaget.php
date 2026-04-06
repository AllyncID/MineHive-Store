<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bucks_kaget extends Admin_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Bucks_kaget_model');
        $this->load->library('form_validation');
    }

    public function index() {
        $data['title'] = 'Bucks Kaget';
        $data['campaigns'] = $this->Bucks_kaget_model->get_all_campaigns();

        $this->load->view('admin/templates/admin_header', $data);
        $this->load->view('admin/bucks_kaget/index', $data);
        $this->load->view('admin/templates/admin_footer');
    }

    public function add() {
        $this->set_form_rules();

        if ($this->form_validation->run() === FALSE) {
            $data['title'] = 'Buat Bucks Kaget Baru';

            $this->load->view('admin/templates/admin_header', $data);
            $this->load->view('admin/bucks_kaget/form', $data);
            $this->load->view('admin/templates/admin_footer');
            return;
        }

        $total_bucks = (int) $this->input->post('total_bucks');
        $total_recipients = (int) $this->input->post('total_recipients');
        $allocations = $this->Bucks_kaget_model->generate_random_allocations($total_bucks, $total_recipients);

        if (empty($allocations)) {
            $this->session->set_flashdata('error', 'Gagal membagi bucks secara random. Pastikan total bucks lebih besar atau sama dengan total penerima.');
            redirect('admin/bucks_kaget/add');
            return;
        }

        $expires_at = $this->normalize_expires_at($this->input->post('expires_at', TRUE));
        $campaign_id = $this->Bucks_kaget_model->create_campaign([
            'name' => $this->input->post('name', TRUE),
            'token' => $this->Bucks_kaget_model->generate_unique_token(),
            'total_bucks' => $total_bucks,
            'total_recipients' => $total_recipients,
            'expires_at' => $expires_at,
            'is_active' => $this->input->post('is_active') ? 1 : 0
        ], $allocations);

        if (!$campaign_id) {
            $this->session->set_flashdata('error', 'Campaign Bucks Kaget gagal dibuat.');
            redirect('admin/bucks_kaget/add');
            return;
        }

        $this->session->set_flashdata('success', 'Bucks Kaget berhasil dibuat. Link random sudah siap dibagikan.');
        redirect('admin/bucks_kaget/view/' . $campaign_id);
    }

    public function view($id) {
        $data['campaign'] = $this->Bucks_kaget_model->get_campaign_by_id($id);
        if (!$data['campaign']) {
            show_404();
        }

        $data['title'] = 'Detail Bucks Kaget';
        $data['claims'] = $this->Bucks_kaget_model->get_claims_for_campaign($id);

        $this->load->view('admin/templates/admin_header', $data);
        $this->load->view('admin/bucks_kaget/view', $data);
        $this->load->view('admin/templates/admin_footer');
    }

    public function toggle($id) {
        $campaign = $this->Bucks_kaget_model->get_campaign_by_id($id);
        if (!$campaign) {
            show_404();
        }

        $this->Bucks_kaget_model->toggle_campaign_status($id);
        $this->session->set_flashdata('success', ((int) $campaign->is_active === 1 ? 'Campaign Bucks Kaget ditutup.' : 'Campaign Bucks Kaget diaktifkan kembali.'));
        redirect('admin/bucks_kaget/view/' . $id);
    }

    public function delete($id) {
        $campaign = $this->Bucks_kaget_model->get_campaign_by_id($id);
        if (!$campaign) {
            show_404();
        }

        $this->Bucks_kaget_model->delete_campaign($id);
        $this->session->set_flashdata('success', 'Campaign Bucks Kaget berhasil dihapus.');
        redirect('admin/bucks_kaget');
    }

    private function set_form_rules() {
        $this->form_validation->set_rules('name', 'Nama Campaign', 'required|trim|max_length[120]');
        $this->form_validation->set_rules('total_bucks', 'Total Bucks', 'required|integer|greater_than[0]');
        $this->form_validation->set_rules('total_recipients', 'Total Penerima', 'required|integer|greater_than[0]|callback__validate_total_recipients');
        $this->form_validation->set_rules('expires_at', 'Waktu Expired', 'trim|callback__valid_optional_datetime');
    }

    public function _validate_total_recipients($value) {
        $total_bucks = (int) $this->input->post('total_bucks');
        $total_recipients = (int) $value;

        if ($total_recipients < 1) {
            return true;
        }

        if ($total_bucks < $total_recipients) {
            $this->form_validation->set_message('_validate_total_recipients', 'Total Bucks minimal harus sama dengan total penerima agar semua orang dapat bagian.');
            return false;
        }

        return true;
    }

    public function _valid_optional_datetime($value) {
        $value = trim((string) $value);
        if ($value === '') {
            return true;
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            $this->form_validation->set_message('_valid_optional_datetime', 'Format waktu expired tidak valid.');
            return false;
        }

        if ($timestamp <= time()) {
            $this->form_validation->set_message('_valid_optional_datetime', 'Waktu expired harus lebih besar dari waktu sekarang.');
            return false;
        }

        return true;
    }

    private function normalize_expires_at($value) {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);
        return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
    }
}
