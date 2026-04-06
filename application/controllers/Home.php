<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends CI_Controller {

    private function _format_realm_name($realm_name) {
        $realm_key = strtolower((string) $realm_name);
        $realm_names = [
            'survival' => 'Survival',
            'skyblock' => 'Skyblock',
            'acidisland' => 'AcidIsland',
            'oneblock' => 'OneBlock',
        ];

        return $realm_names[$realm_key] ?? ucwords(str_replace(['_', '-'], ' ', (string) $realm_name));
    }

    public function index() {

        // Muat model Settings
        $this->load->model('Settings_model');
        // [TAMBAHAN] Muat model Promo Popup
        $this->load->model('Promo_popup_model'); 

        $settings = $this->Settings_model->get_all_settings();

        // === AMBIL SEMUA DATA FLASH SALE YANG AKTIF ===
        $this->load->model('Flash_sale_model');
        $data['flash_sales'] = $this->Flash_sale_model->get_active_flash_sales();
        
        // === [BARU] AMBIL FEATURED PRODUCTS (HOT DEALS) ===
        $this->load->model('Featured_model');
        $data['featured_products'] = $this->Featured_model->get_active_featured(12); 
        
        $data['featured_design'] = $settings['featured_design_style'] ?? 'marquee'; 
        // ==================================================

        // Siapkan data untuk dikirim ke view header
        $data['announcement_bar'] = [
            'enabled'   => $settings['announcement_bar_enabled'] ?? false,
            'text'      => $settings['announcement_bar_text'] ?? '',
            'link'      => $settings['announcement_bar_link'] ?? '',
            'timer_end' => $settings['announcement_timer_end'] ?? '',
            'bg_color_1' => $settings['announcement_bg_color_1'] ?? null,
            'bg_color_2' => $settings['announcement_bg_color_2'] ?? null
        ];

        // [TAMBAHAN] Ambil data Promo Popup agar muncul di Footer
        $data['promo_popup'] = $this->Promo_popup_model->get_promo_data();

        $data['title'] = 'Welcome | MineHive';
        $data['meta_description'] = 'Store resmi MineHive untuk rank, Bucks, promo, dan item spesial di Survival, Skyblock, AcidIsland, dan OneBlock.';

        // --- Mengambil Data Supporters ---
        $this->load->model('Store_model');
        $this->load->model('Transaction_model');

        $data['top_donator'] = $this->Store_model->get_top_donators(1);
        $data['recent_supporters'] = $this->Transaction_model->get_recent_transactions(10);

        // Memuat view dengan semua data yang sudah kita kumpulkan
        $this->load->view('templates/header', $data);
        $this->load->view('home_view', $data);
        // Data $promo_popup sekarang akan terbaca di sini
        $this->load->view('templates/footer', $data); 
    }

    public function get_recent_purchases() {
        header('Content-Type: application/json');
        $this->load->model('Transaction_model');
        $transactions = $this->Transaction_model->get_recent_transactions(5);
        $purchases = [];
        foreach ($transactions as $trx) {
            $first_item = trim(explode(',', (string) $trx->purchased_items)[0] ?? '');
            $realm_name = (string) ($trx->realm ?? '');
            $cart_data = json_decode($trx->cart_data ?? '', true);

            if (!empty($cart_data['items']) && is_array($cart_data['items'])) {
                $first_cart_item = reset($cart_data['items']);
                if (!empty($first_cart_item['realm'])) {
                    $realm_name = (string) $first_cart_item['realm'];
                } elseif (!empty($first_cart_item['name']) && preg_match('/\((survival|skyblock|acidisland|oneblock)\)\s*$/i', $first_cart_item['name'], $matches)) {
                    $realm_name = $matches[1];
                }
            }

            $purchases[] = [
                'username' => $trx->player_username,
                'realm'    => $this->_format_realm_name($realm_name),
                'items'    => $first_item
            ];
        }
        echo json_encode($purchases);
    }
}
