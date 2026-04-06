<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller { // Atau extends Affiliate_Controller jika Anda punya base controller

    public function __construct() {
        parent::__construct();

        // Proteksi: Wajib login untuk mengakses dashboard
        if (!$this->session->userdata('is_affiliate_logged_in')) {
            $this->session->set_flashdata('error', 'Anda harus login untuk mengakses halaman ini.');
            redirect('affiliate/auth/login');
        }

        $this->load->model('Affiliate_model');
        $this->load->helper('date'); // Muat helper untuk fungsi timespan()
    }

    /**
     * Menampilkan halaman utama dashboard afiliasi.
     */
    public function index() {
        // Ambil ID afiliasi dari session yang disimpan saat login
        $affiliate_id = $this->session->userdata('affiliate_id');
        
        // 1. Mengambil data spesifik untuk afiliasi yang login (lebih efisien)
        $data['affiliate'] = $this->Affiliate_model->get_affiliate_by_id($affiliate_id);
        
        // 2. MENGAMBIL DATA RIWAYAT AKTIVITAS (INI YANG HILANG DARI KODE ANDA)
        $data['activities'] = $this->Affiliate_model->get_activities($affiliate_id, 5); // Ambil 5 aktivitas terbaru

        // Pengaman jika data afiliasi karena suatu hal tidak ditemukan
        if (!$data['affiliate']) {
            $this->session->set_flashdata('error', 'Gagal memuat data afiliasi. Silakan login kembali.');
            redirect('affiliate/auth/logout'); // Logout paksa
            return;
        }
        
        // 3. Kirim semua data ke view
        $data['title'] = 'Affiliate Portal | Mine Hive';
        $this->load->view('affiliate/dashboard_view', $data);
    }
public function change_password() {
    // 1. Muat library form validation
    $this->load->library('form_validation');

    // 2. Atur aturan validasi (tidak berubah)
    $this->form_validation->set_rules('old_password', 'Password Lama', 'required|trim');
    $this->form_validation->set_rules('new_password', 'Password Baru', 'required|trim|min_length[8]|differs[old_password]');
    $this->form_validation->set_rules('confirm_password', 'Konfirmasi Password', 'required|trim|matches[new_password]');

    // Kustomisasi pesan error
    $this->form_validation->set_message('min_length', '{field} harus memiliki minimal {param} karakter.');
    $this->form_validation->set_message('matches', '{field} tidak cocok dengan Password Baru.');
    $this->form_validation->set_message('differs', '{field} tidak boleh sama dengan Password Lama.');

    // 3. Jalankan validasi
    if ($this->form_validation->run() === FALSE) {
        // Jika validasi gagal, kembalikan ke dashboard dengan pesan error
        $this->index();
        return;
    }

    // 4. Jika validasi sukses, ambil data
    $affiliate_id = $this->session->userdata('affiliate_id');
    $current_user = $this->Affiliate_model->get_affiliate_by_id($affiliate_id);
    
    $old_password = $this->input->post('old_password');
    $new_password = $this->input->post('new_password');

    // 5. Verifikasi password lama (menggunakan perbandingan teks biasa)
    if ($old_password === $current_user->password) {
        
        // --- Perubahan Utama: Langsung simpan password baru tanpa hash ---
        $this->Affiliate_model->update_password($affiliate_id, $new_password);
        
        $this->session->set_flashdata('success', 'Password Anda telah berhasil diperbarui!');

    } else {
        // Jika password lama salah
        $this->session->set_flashdata('error', 'Password lama yang Anda masukkan salah.');
    }

    // 6. Arahkan kembali ke halaman dashboard
    redirect('affiliate/dashboard');
}
}