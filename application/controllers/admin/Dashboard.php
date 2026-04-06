<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends Admin_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Admin_model');
        $this->load->model('Product_model');
        $this->load->model('Transaction_model');
        $this->load->model('Settings_model');
        $this->load->model('Promo_popup_model'); // Load our promo model
        $this->load->model('Bucks_kaget_model');
    }

    public function index() {
        $data['title'] = 'Dashboard';

        $range = $this->input->get('range', TRUE) ?: '7days';
        $days = 7;
        $filter_title = '7 Hari Terakhir';
        
        switch ($range) {
            case 'today': $days = 1; $filter_title = 'Hari Ini'; break;
            case '30days': $days = 30; $filter_title = '30 Hari Terakhir'; break;
            case 'all': $days = null; $filter_title = 'Semua Waktu'; break;
            case '7days': default: $days = 7; $filter_title = '7 Hari Terakhir'; $range = '7days'; break;
        }
        $data['filter_title'] = $filter_title;
        $data['current_range'] = $range;

        $data['total_products'] = $this->Admin_model->count_total_products();
        $data['total_transactions'] = $this->Admin_model->count_total_transactions($days);
        $data['total_revenue'] = $this->Admin_model->calculate_total_revenue($days);
        $data['recent_transactions'] = $this->Transaction_model->get_recent_transactions(5);

        $chart_days = ($days) ? $days : 30;
        $sales_data_db = $this->Admin_model->get_daily_sales_for_chart($chart_days);
        $sales_by_date = [];
        foreach ($sales_data_db as $row) { 
            $sales_by_date[$row['sale_date']] = $row['daily_total']; 
        }
        $chart_labels = [];
        $chart_data = [];
        for ($i = $chart_days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $chart_labels[] = date('d M', strtotime($date));
            $chart_data[] = isset($sales_by_date[$date]) ? $sales_by_date[$date] : 0;
        }
        $data['chart_labels'] = json_encode($chart_labels);
        $data['chart_data'] = json_encode($chart_data);

        // Load settings and promo data
        $data['settings'] = $this->Settings_model->get_all_settings();
        $data['promo_popup'] = $this->Promo_popup_model->get_promo_data();
        $data['active_bucks_kaget_campaigns'] = $this->Bucks_kaget_model->count_active_campaigns();
        $data['bucks_kaget_remaining_slots'] = $this->Bucks_kaget_model->count_total_remaining_slots();

        $this->load->view('admin/templates/admin_header', $data);
        $this->load->view('admin/dashboard_view', $data);
        $this->load->view('admin/templates/admin_footer');
    }

    public function update_settings() {
        $settings_data = [
            'announcement_bar_enabled' => $this->input->post('announcement_bar_enabled'),
            'announcement_bar_text'    => $this->input->post('announcement_bar_text'),
            'announcement_bar_link'    => $this->input->post('announcement_bar_link'),
            'announcement_timer_end'   => $this->input->post('announcement_timer_end'),
            'announcement_bg_color_1'  => $this->input->post('announcement_bg_color_1'),
            'announcement_bg_color_2'  => $this->input->post('announcement_bg_color_2')
        ];

        $this->Settings_model->update_batch($settings_data);

        $this->session->set_flashdata('success', 'Pengaturan situs berhasil diperbarui.');
        redirect('admin/dashboard');
    }

    public function update_promo_popup() {
        if ($this->input->post()) {
            $data = [
                'title' => $this->input->post('title', TRUE),
                'description' => $this->input->post('description', TRUE), 
                'button_text'  => $this->input->post('button_text', TRUE),
                'button_link'  => $this->input->post('button_link', TRUE),
                'is_enabled' => $this->input->post('is_enabled', TRUE)
            ];

            // [MODIFIKASI] Gabungkan Judul dan Deskripsi Tier
            for ($i = 1; $i <= 5; $i++) {
                $tier_title = $this->input->post('promo_tier_' . $i . '_title', TRUE);
                $tier_desc = $this->input->post('promo_tier_' . $i . '_desc', TRUE);
                
                // Simpan dalam format: Judul|Deskripsi
                if (!empty($tier_title) || !empty($tier_desc)) {
                    $data['promo_tier_' . $i] = $tier_title . '|' . $tier_desc;
                } else {
                    $data['promo_tier_' . $i] = '';
                }
            }

            // Logic Upload Gambar
            if (!empty($_FILES['popup_image']['name'])) {
                // Konfigurasi upload
                $config['upload_path']   = './assets/images/uploads/'; // Pastikan folder ini ada
                $config['allowed_types'] = 'gif|jpg|png|jpeg|webp';
                $config['max_size']      = 2048; // 2MB
                $config['file_name']     = 'event_popup_' . time(); // Nama file unik

                $this->load->library('upload', $config);

                // Cek apakah folder uploads ada, jika tidak buat
                if (!is_dir('./assets/images/uploads/')) {
                    mkdir('./assets/images/uploads/', 0777, true);
                }

                if ($this->upload->do_upload('popup_image')) {
                    $upload_data = $this->upload->data();
                    // Simpan path gambar ke database
                    $data['image_url'] = base_url('assets/images/uploads/' . $upload_data['file_name']);
                } else {
                    // Jika gagal upload, set flashdata error tapi tetap simpan data teks
                    $this->session->set_flashdata('error', 'Gagal upload gambar: ' . $this->upload->display_errors());
                }
            } else {
                // Jika user ingin menghapus gambar (opsional, bisa ditambah checkbox 'delete_image' di view)
                if ($this->input->post('delete_image') == '1') {
                    $data['image_url'] = ''; // Kosongkan URL gambar
                }
            }

            $this->Promo_popup_model->update_promo_data($data);
            
            if (!$this->session->flashdata('error')) {
                $this->session->set_flashdata('success', 'Pengaturan Popup Promo Event berhasil diperbarui.');
            }
        }
        redirect('admin/dashboard');
    }
}
