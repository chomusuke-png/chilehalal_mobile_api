<?php

if ( ! defined( 'ABSPATH' ) ) exit;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class ChileHalal_API_Middleware {

    public static function get_jwt_secret() {
        if ( defined( 'CH_JWT_SECRET' ) ) {
            return CH_JWT_SECRET;
        }
        $db_secret = get_option( 'ch_jwt_secret_db' );
        return ! empty( $db_secret ) ? $db_secret : '';
    }

    public static function validate_request( $request ) {
        $auth_header = $request->get_header( 'authorization' );
        
        if ( ! $auth_header ) {
            return new WP_Error( 'no_token', 'Token de autorización no encontrado.', ['status' => 401] );
        }

        $token = str_replace( 'Bearer ', '', $auth_header );

        try {
            $secret = self::get_jwt_secret();
            if ( empty( $secret ) ) {
                throw new Exception('Configuración de seguridad incompleta en el servidor.');
            }

            $decoded = JWT::decode( $token, new Key( $secret, 'HS256' ) );
            
            return $decoded->data;

        } catch ( Exception $e ) {
            error_log( 'ChileHalal API Error JWT: ' . $e->getMessage() );
            
            return new WP_Error( 'invalid_token', 'Token de sesión inválido o expirado.', ['status' => 401] );
        }
    }

    public static function check_permission( $user_id, $required_cap, $context_data = [] ) {
        $role = get_post_meta( $user_id, '_ch_user_role', true );
        
        if ( $role === 'owner' ) return true;

        switch ( $required_cap ) {
            case 'manage_products':
                if ( $role === 'editor' ) return true;
                
                if ( $role === 'partner' ) {
                    $user_brands = get_post_meta( $user_id, '_ch_user_brands', true );
                    $target_brand = $context_data['brand'] ?? '';
                    
                    if ( empty( $user_brands ) || empty( $target_brand ) ) return false;
                    
                    $user_brands_norm = array_map( 'strtolower', array_map( 'trim', (array) $user_brands ) );
                    return in_array( strtolower( trim( $target_brand ) ), $user_brands_norm );
                }
                return false;

            case 'read':
                return true;

            default:
                return false;
        }
    }
}