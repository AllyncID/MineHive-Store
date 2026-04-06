<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Affiliate extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Affiliate_model');
    }

    public function index() {
        if ($this->session->userdata('is_affiliate_logged_in')) {
            redirect('affiliate/dashboard');
        }

        $this->load->view('affiliate/login_view');
    }
}