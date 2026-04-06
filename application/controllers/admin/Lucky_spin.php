<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Lucky_spin extends Admin_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Lucky_spin_model');
        $this->load->model('Store_model');
        $this->load->library('form_validation');
    }

    public function index() {
        $data['title'] = 'Lucky Spin';
        $data['campaigns'] = $this->Lucky_spin_model->get_all_campaigns();

        $this->load->view('admin/templates/admin_header', $data);
        $this->load->view('admin/lucky_spin/index', $data);
        $this->load->view('admin/templates/admin_footer');
    }

    public function add() {
        $this->set_form_rules();
        $data['title'] = 'Buat Lucky Spin Baru';
        $data['products'] = $this->get_reward_products();

        if ($this->form_validation->run() === FALSE) {
            $this->load->view('admin/templates/admin_header', $data);
            $this->load->view('admin/lucky_spin/form', $data);
            $this->load->view('admin/templates/admin_footer');
            return;
        }

        $reward_parse = $this->parse_reward_rows();
        if (!empty($reward_parse['errors'])) {
            $data['manual_error'] = implode(' ', $reward_parse['errors']);
            $this->load->view('admin/templates/admin_header', $data);
            $this->load->view('admin/lucky_spin/form', $data);
            $this->load->view('admin/templates/admin_footer');
            return;
        }

        $expires_at = $this->normalize_expires_at($this->input->post('expires_at', TRUE));
        $campaign_id = $this->Lucky_spin_model->create_campaign([
            'name' => $this->input->post('name', TRUE),
            'token' => $this->Lucky_spin_model->generate_unique_token(),
            'max_players' => (int) $this->input->post('max_players'),
            'max_spins_per_player' => (int) $this->input->post('max_spins_per_player'),
            'expires_at' => $expires_at,
            'is_active' => $this->input->post('is_active') ? 1 : 0
        ], $reward_parse['rewards']);

        if (!$campaign_id) {
            $this->session->set_flashdata('error', 'Campaign Lucky Spin gagal dibuat.');
            redirect('admin/lucky_spin/add');
            return;
        }

        $this->session->set_flashdata('success', 'Lucky Spin berhasil dibuat. Link spin siap dibagikan.');
        redirect('admin/lucky_spin/view/' . $campaign_id);
    }

    public function view($id) {
        $data['campaign'] = $this->Lucky_spin_model->get_campaign_by_id($id);
        if (!$data['campaign']) {
            show_404();
        }

        $data['title'] = 'Detail Lucky Spin';
        $data['rewards'] = $this->Lucky_spin_model->get_rewards_for_campaign((int) $id);
        $data['entries'] = $this->Lucky_spin_model->get_entries_for_campaign((int) $id);

        $this->load->view('admin/templates/admin_header', $data);
        $this->load->view('admin/lucky_spin/view', $data);
        $this->load->view('admin/templates/admin_footer');
    }

    public function toggle($id) {
        $campaign = $this->Lucky_spin_model->get_campaign_by_id($id);
        if (!$campaign) {
            show_404();
        }

        $this->Lucky_spin_model->toggle_campaign_status($id);
        $this->session->set_flashdata('success', ((int) $campaign->is_active === 1 ? 'Campaign Lucky Spin ditutup.' : 'Campaign Lucky Spin diaktifkan kembali.'));
        redirect('admin/lucky_spin/view/' . $id);
    }

    public function delete($id) {
        $campaign = $this->Lucky_spin_model->get_campaign_by_id($id);
        if (!$campaign) {
            show_404();
        }

        $this->Lucky_spin_model->delete_campaign($id);
        $this->session->set_flashdata('success', 'Campaign Lucky Spin berhasil dihapus.');
        redirect('admin/lucky_spin');
    }

    private function set_form_rules() {
        $this->form_validation->set_rules('name', 'Nama Campaign', 'required|trim|max_length[120]');
        $this->form_validation->set_rules('max_players', 'Jumlah Orang', 'required|integer|greater_than[0]');
        $this->form_validation->set_rules('max_spins_per_player', 'Batas Spin per Player', 'required|integer|greater_than[0]');
        $this->form_validation->set_rules('expires_at', 'Waktu Expired', 'trim|callback__valid_optional_datetime');
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

    private function get_reward_products() {
        return $this->db
            ->select('id, name, category, realm, product_type')
            ->from('products')
            ->where('is_active', 1)
            ->order_by('name', 'ASC')
            ->get()
            ->result_array();
    }

    private function parse_reward_rows() {
        $reward_types = (array) $this->input->post('reward_type');
        $labels = (array) $this->input->post('reward_label');
        $bucks_amounts = (array) $this->input->post('bucks_amount');
        $product_ids = (array) $this->input->post('product_id');
        $weights = (array) $this->input->post('reward_weight');
        $stocks = (array) $this->input->post('reward_stock');

        $rewards = [];
        $errors = [];

        foreach ($reward_types as $index => $reward_type_raw) {
            $reward_type = strtolower(trim((string) $reward_type_raw));
            $label = trim((string) ($labels[$index] ?? ''));
            $bucks_amount = (int) ($bucks_amounts[$index] ?? 0);
            $product_id = (int) ($product_ids[$index] ?? 0);
            $weight = max(1, (int) ($weights[$index] ?? 1));
            $stock_raw = trim((string) ($stocks[$index] ?? ''));
            $stock = $stock_raw === '' ? null : (int) $stock_raw;

            if (!in_array($reward_type, ['bucks', 'product', 'zonk'], true)) {
                continue;
            }

            $reward = [
                'reward_type' => $reward_type,
                'weight' => $weight,
                'stock' => $stock !== null ? max(1, $stock) : null,
                'sort_order' => $index
            ];

            if ($reward_type === 'bucks') {
                if ($bucks_amount < 1) {
                    $errors[] = 'Nominal Bucks pada hadiah Lucky Spin harus lebih dari 0.';
                    continue;
                }

                $reward['bucks_amount'] = $bucks_amount;
                $reward['product_id'] = null;
                $reward['label'] = $label !== '' ? $label : number_format($bucks_amount, 0, ',', '.') . ' Bucks';
            } elseif ($reward_type === 'product') {
                if ($product_id < 1) {
                    $errors[] = 'Pilih produk yang valid untuk hadiah bertipe product.';
                    continue;
                }

                $product = $this->Store_model->get_product_by_id($product_id);
                if (!$product) {
                    $errors[] = 'Produk hadiah Lucky Spin tidak ditemukan.';
                    continue;
                }

                $reward['bucks_amount'] = null;
                $reward['product_id'] = $product_id;
                $reward['label'] = $label !== '' ? $label : (string) $product['name'];
            } else {
                $reward['bucks_amount'] = null;
                $reward['product_id'] = null;
                $reward['label'] = $label !== '' ? $label : 'Zonk';
            }

            $rewards[] = $reward;
        }

        if (empty($rewards)) {
            $errors[] = 'Tambahkan minimal satu hadiah Lucky Spin.';
        }

        return [
            'rewards' => $rewards,
            'errors' => $errors
        ];
    }
}
