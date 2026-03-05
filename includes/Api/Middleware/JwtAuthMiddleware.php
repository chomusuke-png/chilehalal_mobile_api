<?php
if (!defined('ABSPATH')) exit;

class ChileHalal_Jwt_Auth_Middleware {
    public function checkAuth($request) {
        $auth_result = ChileHalal_API_Middleware::validate_request($request);
        
        if (is_wp_error($auth_result)) {
            return $auth_result;
        }
        
        $request->set_param('auth_user', $auth_result);
        return true;
    }
}