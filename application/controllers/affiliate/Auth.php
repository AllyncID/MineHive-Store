<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
        $this->load->model('Affiliate_model');
    }

    public function login() {
        if ($this->session->userdata('is_affiliate_logged_in')) {
            redirect('affiliate/dashboard');
        }
        $this->load->view('affiliate/login_view');
    }

    public function process_login() {
        $username = $this->input->post('username');
        $password = $this->input->post('password');

        $affiliate = $this->Affiliate_model->validate_login($username, $password);

        if ($affiliate) {
            $session_data = [
                'affiliate_id'    => $affiliate->id,
                'affiliate_username' => $affiliate->minecraft_username,
                'is_affiliate_logged_in' => TRUE
            ];
            $this->session->set_userdata($session_data);
            redirect('affiliate/dashboard');
        } else {
            $this->session->set_flashdata('error', 'Username atau password salah.');
            redirect('affiliate/auth/login');
        }
    }

    public function logout() {
        $this->session->sess_destroy();
        redirect('affiliate/auth/login');
    }
}