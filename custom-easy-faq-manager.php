<?php
/**
 * Plugin Name: Custom Easy FAQ Manager
 * Plugin URI: https://github.com/Ekt0re/FAQ-Manager-Easy-WordPress
 * Description: Crea e gestisci facilmente le tue FAQ personalizzate su WordPress con una GUI semplice ed intuitiva!
 * Version: 1.1
 * Author: Ettore Sartori
 * Author URI: https://www.instagram.com/ettore_sartori/
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: custom-faq-manager
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CUSTOM_FAQ_MANAGER_VERSION', '1.1');
define('CUSTOM_FAQ_MANAGER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CUSTOM_FAQ_MANAGER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once CUSTOM_FAQ_MANAGER_PLUGIN_DIR . 'includes/class-faq-settings.php';

class Custom_FAQ_Manager {
    
    /**
     * Debug AJAX errors - Attivabile via wp-config.php
     * Utile per debug di errori AJAX 400 (Bad Request)
     */
    private function debug_ajax_errors() {
        // Disabilitato temporaneamente per evitare errori 500
        return;
        
        /*
        // Controlla se è stata definita la costante di debug AJAX
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_AJAX_ERRORS') && WP_DEBUG_AJAX_ERRORS) {
            // Registra gli hook per aggiungere informazioni di debug alle risposte AJAX
            add_filter('wp_doing_ajax', function($is_doing_ajax) {
                // Log delle richieste AJAX
                if ($is_doing_ajax) {
                    error_log('DEBUG AJAX REQUEST: ' . print_r($_REQUEST, true));
                }
                return $is_doing_ajax;
            });
            
            // Aggiungi handler per gestire gli errori 400 Bad Request
            add_action('wp_ajax_nopriv_get_faq', function() {
                error_log('AJAX get_faq chiamato ma non registrato - utente non loggato');
            }, 1);
            
            // Debug dell'hook wp_ajax_ per verificare se l'azione viene registrata correttamente
            add_action('admin_init', function() {
                if (isset($_REQUEST['debug_ajax_hooks']) && current_user_can('manage_options')) {
                    global $wp_filter;
                    if (isset($wp_filter['wp_ajax_get_faq'])) {
                        echo '<pre>Debug hook wp_ajax_get_faq: ';
                        print_r($wp_filter['wp_ajax_get_faq']);
                        echo '</pre>';
                        exit;
                    } else {
                        echo 'Hook wp_ajax_get_faq non registrato!';
                        exit;
                    }
                }
            });
        }
        */
    }

    /**
     * Constructor
     */
    public function __construct() {
        // Register custom post type
        add_action('init', array($this, 'register_faq_post_type'));
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'register_admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'register_frontend_scripts'));
        
        // Add shortcode
        add_shortcode('custom_faq', array($this, 'faq_shortcode'));
        
        // Register AJAX handlers
        $this->register_ajax_handlers();
        
        // Add Gutenberg block
        add_action('init', array($this, 'register_faq_block'));
        
        // Debug AJAX errors
        $this->debug_ajax_errors();
        
        // Registra l'hook di attivazione per i file
        register_activation_hook(__FILE__, 'custom_faq_manager_activate');
        
        // Aggiungi supporto per le traduzioni
        add_action('plugins_loaded', array($this, 'load_textdomain'));
    }
    
    /**
     * Carica il textdomain per le traduzioni
     */
    public function load_textdomain() {
        load_plugin_textdomain('custom-faq-manager', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
    
    // ... resto del codice originale 