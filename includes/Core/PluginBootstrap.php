<?php
if (!defined('ABSPATH')) exit;

class ChileHalal_Plugin_Bootstrap {
    
    public function init() {
        $this->registerModels();
        $this->registerApi();
        $this->registerAdmin();
    }

    private function registerModels() {
        new ChileHalal_Product_Model();
        new ChileHalal_App_User_Model();
        new ChileHalal_Audit_Log_Model();
        new ChileHalal_Business_Model();
        new ChileHalal_Coupon_Model();
    }

    private function registerApi() {
        new ChileHalal_Api_Router();
    }

    private function registerAdmin() {
        if (is_admin()) {
            new ChileHalal_Admin_Menu();
        }
    }
}