<?php
/*
Plugin Name: WP_Scraper
 * Plugin URI:        https://www.extendons.com/
 * Description:       Scrap each and everything from any wordpress site
 * Author:            Extendons
 * Version:           1.0.3
 * Developed By:      Extendons Team
 * Author URI:        http://extendons.com/
 * Support:           http://support.extendons.com/
 * Text Domain:       extfsp
 * Developer:         Osama Jawad
 */
class Extendons_wp_scrapper {

    public function __construct() {
 
      $this->module_constants();
        if ( is_admin() ) {
            require_once( EXT_FSP_PLUGIN_DIR.'admin/wp-scrapper-admin.php' );
        }

            add_action( 'init',array( $this, 'enqueue_classes' ) );
    }
    public function enqueue_classes(){
        if ( function_exists( 'load_plugin_textdomain' ) )
          load_plugin_textdomain( 'wps_text', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
        wp_enqueue_script('jquery');

    } 


    public function module_constants() {

      if ( !defined( 'EXT_FSP_URL' ) )
          define( 'EXT_FSP_URL', plugin_dir_url( __FILE__ ) );

      if ( !defined( 'EXT_FSP_BASENAME' ) )
          define( 'EXT_FSP_BASENAME', plugin_basename( __FILE__ ) );

      if ( ! defined( 'EXT_FSP_PLUGIN_DIR' ) )
          define( 'EXT_FSP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
    }

    }

    $extdpr = new Extendons_wp_scrapper();
 
?>