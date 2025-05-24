<?php
/**
 * Plugin Name: Custom Easy FAQ Manager
 * Plugin URI: https://github.com/Ekt0re/FAQ-Manager-Easy-WordPress
 * Description: Create and manage your custom FAQs on WordPress with a simple and intuitive GUI! Multilingual support included.
 * Version: 1.3
 * Author: Ettore Sartori
 * Author URI: https://www.instagram.com/ettore_sartori/
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: FAQ-Manager-Easy-WordPress
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Definizioni costanti plugin
define('CEAFM_VERSION', '1.3');
define('CEAFM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CEAFM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CEAFM_PLUGIN_BASENAME', plugin_basename(__FILE__));

class Custom_Easy_FAQ_Manager {
    
    /**
     * Supported languages
     */
    private $supported_languages = array(
        'en_US' => 'English',
        'it_IT' => 'Italiano',
        'es_ES' => 'Español',
        'fr_FR' => 'Français',
        'de_DE' => 'Deutsch',
        'ru_RU' => 'Русский',
        'vi'    => 'Tiếng Việt'
    );
    
    /**
     * Current language
     */
    private $current_language = 'en_US';
    
    /**
     * Debug AJAX errors - Attivabile via wp-config.php
     * Utile per debug di errori AJAX 400 (Bad Request)
     */
    private function debug_ajax_errors() {
        // Disabilitato per il rilascio pubblico
        return;
    }

    /**
     * Constructor
     */
    public function __construct() {
        // Load text domain for translations
        add_action('plugins_loaded', array($this, 'load_plugin_textdomain'));
        
        // Set current language
        $this->set_current_language();
        
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
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain('FAQ-Manager-Easy-WordPress', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Set current language based on user preference or WordPress locale
     */
    private function set_current_language() {
        // Check if user has selected a language manually
        if (isset($_COOKIE['custom_faq_language'])) {
            $selected_language = sanitize_text_field(wp_unslash($_COOKIE['custom_faq_language']));
            if (array_key_exists($selected_language, $this->supported_languages)) {
                $this->current_language = $selected_language;
                return;
            }
        }
        
        // Otherwise use WordPress locale
        $locale = get_locale();
        
        // Check if the locale is supported
        if (array_key_exists($locale, $this->supported_languages)) {
            $this->current_language = $locale;
        } else {
            // Try to match the language part of the locale
            $language_part = substr($locale, 0, 2);
            foreach (array_keys($this->supported_languages) as $supported_locale) {
                if (substr($supported_locale, 0, 2) == $language_part) {
                    $this->current_language = $supported_locale;
                    return;
                }
            }
            
            // Default to English if no match found
            $this->current_language = 'en_US';
        }
    }

    /**
     * Get supported languages
     */
    public function get_supported_languages() {
        return $this->supported_languages;
    }
    
    /**
     * Get current language
     */
    public function get_current_language() {
        return $this->current_language;
    }
    
    /**
     * Register custom post type for FAQs
     */
    public function register_faq_post_type() {
        $labels = array(
            'name'               => _x('FAQs', 'post type general name', 'FAQ-Manager-Easy-WordPress'),
            'singular_name'      => _x('FAQ', 'post type singular name', 'FAQ-Manager-Easy-WordPress'),
            'menu_name'          => _x('FAQs', 'admin menu', 'FAQ-Manager-Easy-WordPress'),
            'name_admin_bar'     => _x('FAQ', 'add new on admin bar', 'FAQ-Manager-Easy-WordPress'),
            'add_new'            => _x('Add New', 'faq', 'FAQ-Manager-Easy-WordPress'),
            'add_new_item'       => __('Add New FAQ', 'FAQ-Manager-Easy-WordPress'),
            'new_item'           => __('New FAQ', 'FAQ-Manager-Easy-WordPress'),
            'edit_item'          => __('Edit FAQ', 'FAQ-Manager-Easy-WordPress'),
            'view_item'          => __('View FAQ', 'FAQ-Manager-Easy-WordPress'),
            'all_items'          => __('All FAQs', 'FAQ-Manager-Easy-WordPress'),
            'search_items'       => __('Search FAQs', 'FAQ-Manager-Easy-WordPress'),
            'not_found'          => __('No FAQs found.', 'FAQ-Manager-Easy-WordPress'),
            'not_found_in_trash' => __('No FAQs found in trash.', 'FAQ-Manager-Easy-WordPress')
        );
        
        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => false, // We'll add a custom menu
            'query_var'          => true,
            'rewrite'            => array('slug' => 'faq'),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array('title', 'editor'),
            'show_in_rest'       => true, // Needed for Gutenberg
        );
        
        register_post_type('custom_faq', $args);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('FAQ Management', 'FAQ-Manager-Easy-WordPress'),
            __('FAQ Manager', 'FAQ-Manager-Easy-WordPress'),
            'manage_options',
            'ceafm-manager',
            array($this, 'admin_page_display'),
            'dashicons-format-status',
            30
        );
    }
    
    /**
     * Register admin scripts and styles
     */
    public function register_admin_scripts($hook) {
        // Solo nelle pagine del nostro plugin
        if ($hook != 'toplevel_page_ceafm-manager') {
            return;
        }

        // Registra e carica gli stili e gli script per TinyMCE
        wp_enqueue_editor();
        
        // Stili e script per il color picker
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        // Stili e script del plugin
        wp_enqueue_style('ceafm-admin-style', CEAFM_PLUGIN_URL . 'assets/css/admin-style.css', array(), CEAFM_VERSION);
        wp_enqueue_script('ceafm-admin-script', CEAFM_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery', 'wp-color-picker'), CEAFM_VERSION, true);
        wp_enqueue_script('ceafm-language-switcher', CEAFM_PLUGIN_URL . 'assets/js/language-switcher.js', array('jquery'), CEAFM_VERSION, true);
        
        // Localize script
        wp_localize_script('ceafm-admin-script', 'ceafmAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ceafm_nonce'),
            'current_language' => $this->current_language,
            'search_placeholder' => __('Search FAQs...', 'FAQ-Manager-Easy-WordPress'),
            'add_new' => __('Add New FAQ', 'FAQ-Manager-Easy-WordPress'),
            'edit' => __('Edit', 'FAQ-Manager-Easy-WordPress'),
            'delete' => __('Delete', 'FAQ-Manager-Easy-WordPress'),
            'delete_confirm' => __('Are you sure you want to delete this FAQ?', 'FAQ-Manager-Easy-WordPress'),
            'question_placeholder' => __('Enter question here...', 'FAQ-Manager-Easy-WordPress'),
            'answer_placeholder' => __('Enter answer here...', 'FAQ-Manager-Easy-WordPress'),
            'save' => __('Save', 'FAQ-Manager-Easy-WordPress'),
            'cancel' => __('Cancel', 'FAQ-Manager-Easy-WordPress'),
            'no_results' => __('No FAQs found. Add your first FAQ using the button above.', 'FAQ-Manager-Easy-WordPress'),
            'saving' => __('Saving...', 'FAQ-Manager-Easy-WordPress'),
            'error' => __('An error occurred. Please try again.', 'FAQ-Manager-Easy-WordPress')
        ));
    }
    
    /**
     * Register frontend scripts and styles
     */
    public function register_frontend_scripts() {
        wp_register_style('ceafm-frontend', CEAFM_PLUGIN_URL . 'assets/css/frontend.css', array(), CEAFM_VERSION);
        wp_register_script('ceafm-frontend', CEAFM_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), CEAFM_VERSION, true);
        wp_register_script('ceafm-fix-answers', CEAFM_PLUGIN_URL . 'assets/js/fix-faq-answers.js', array(), CEAFM_VERSION, true);
        
        // Carica automaticamente gli asset se è presente un blocco o uno shortcode FAQ
        global $post;
        if (is_singular() && is_a($post, 'WP_Post')) {
            // Controlla blocchi e shortcode
            if (has_block('ceafm/faq-block', $post) || 
                has_shortcode($post->post_content, 'custom_faq') ||
                strpos($post->post_content, 'wp-block-astra-child-faq-block') !== false) {
                
                wp_enqueue_style('ceafm-frontend');
                wp_enqueue_script('ceafm-frontend');
                wp_enqueue_script('ceafm-fix-answers');
                
                // Aggiungi le impostazioni grafiche come variabile JavaScript
                $this->add_faq_settings_to_footer();
            }
        }
    }
    
    /**
     * Inietta le impostazioni grafiche come oggetto JavaScript nel footer
     */
    public function add_faq_settings_to_footer() {
        // Ottieni le impostazioni salvate
        $settings = get_option('ceafm_settings', array(
            'question_bg_color' => '#f9f9f9',
            'question_text_color' => '#333333',
            'answer_bg_color' => '#ffffff',
            'answer_text_color' => '#333333',
            'border_color' => '#dddddd',
            'active_question_bg_color' => '#e9e9e9',
            'hover_bg_color' => '#f0f0f0',
            'answer_max_height' => '90'
        ));
        
        // Rinomina le chiavi per JavaScript e converti in formato camelCase
        $js_settings = array(
            'questionBgColor' => $settings['question_bg_color'],
            'questionTextColor' => $settings['question_text_color'],
            'answerBgColor' => $settings['answer_bg_color'],
            'answerTextColor' => $settings['answer_text_color'],
            'borderColor' => $settings['border_color'],
            'activeQuestionBgColor' => $settings['active_question_bg_color'],
            'hoverBgColor' => $settings['hover_bg_color'],
            'answerMaxHeight' => intval($settings['answer_max_height']),
            'allowMultipleOpen' => true // Default: consenti apertura multipla
        );
        
        // Codifica le impostazioni come JSON
        $settings_json = wp_json_encode($js_settings);
        
        // Aggiungi lo script al footer
        wp_localize_script('ceafm-frontend', 'ceafmSettings', $js_settings);
    }
    
    /**
     * Admin page display
     */
    public function admin_page_display() {
        // Carica le impostazioni grafiche
        $settings = get_option('ceafm_settings', array(
            'question_bg_color' => '#f9f9f9',
            'question_text_color' => '#333333',
            'answer_bg_color' => '#ffffff',
            'answer_text_color' => '#333333',
            'border_color' => '#dddddd',
            'active_question_bg_color' => '#e9e9e9',
            'hover_bg_color' => '#f0f0f0',
            'answer_max_height' => '90'
        ));
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('FAQ Management', 'FAQ-Manager-Easy-WordPress'); ?></h1>
            
            <div class="language-selector" style="margin-bottom: 20px; text-align: right;">
                <label for="language-selector"><?php esc_html_e('Language:', 'FAQ-Manager-Easy-WordPress'); ?></label>
                <select id="language-selector">
                    <?php foreach ($this->get_supported_languages() as $code => $name) : 
                        $selected = ($code === $this->get_current_language()) ? 'selected' : '';
                    ?>
                        <option value="<?php echo esc_attr($code); ?>" <?php echo esc_attr($selected); ?>><?php echo esc_html($name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div id="col-container" class="wp-clearfix">
                <div id="col-left" class="faq-manager-col">
                    <div class="col-wrap">
                        <div class="form-wrap">
                            <h2><?php esc_html_e('FAQ List', 'FAQ-Manager-Easy-WordPress'); ?></h2>
                            
                            <div class="search-box">
                                <input type="search" id="faq-search" placeholder="<?php esc_attr_e('Search FAQs...', 'FAQ-Manager-Easy-WordPress'); ?>" class="regular-text">
                                <button id="search-faq" class="button"><?php esc_html_e('Search', 'FAQ-Manager-Easy-WordPress'); ?></button>
                                <button id="add-new-faq" class="button button-primary"><?php esc_html_e('New', 'FAQ-Manager-Easy-WordPress'); ?></button>
                                <button id="show-settings" class="button"><?php esc_html_e('Settings', 'FAQ-Manager-Easy-WordPress'); ?></button>
                            </div>
                            
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Question', 'FAQ-Manager-Easy-WordPress'); ?></th>
                                        <th><?php esc_html_e('Actions', 'FAQ-Manager-Easy-WordPress'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="faq-list">
                                    <?php
                                    $faqs = get_posts(array(
                                        'post_type' => 'custom_faq',
                                        'posts_per_page' => -1,
                                        'orderby' => 'title',
                                        'order' => 'ASC'
                                    ));
                                    
                                    if (!empty($faqs)) :
                                        foreach ($faqs as $faq) :
                                    ?>
                                        <tr>
                                            <td><?php echo esc_html($faq->post_title); ?></td>
                                            <td>
                                                <button class="button edit-faq" data-id="<?php echo esc_attr($faq->ID); ?>"><?php esc_html_e('Edit', 'FAQ-Manager-Easy-WordPress'); ?></button>
                                                <button class="button delete-faq" data-id="<?php echo esc_attr($faq->ID); ?>"><?php esc_html_e('Delete', 'FAQ-Manager-Easy-WordPress'); ?></button>
                                            </td>
                                        </tr>
                                    <?php
                                        endforeach;
                                    else :
                                    ?>
                                        <tr>
                                            <td colspan="2"><?php esc_html_e('No FAQs found.', 'FAQ-Manager-Easy-WordPress'); ?></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            
                            <div id="faq-editor" class="postbox">
                                <h2 id="editor-title" class="hndle"><?php esc_html_e('Add New FAQ', 'FAQ-Manager-Easy-WordPress'); ?></h2>
                                <div class="inside">
                                    <form id="faq-form">
                                        <input type="hidden" id="faq-id" name="id" value="">
                                        
                                        <div class="form-field">
                                            <label for="faq-title"><?php esc_html_e('Question', 'FAQ-Manager-Easy-WordPress'); ?></label>
                                            <input type="text" id="faq-title" name="title" value="" required>
                                        </div>
                                        
                                        <div class="form-field">
                                            <label for="faq-content"><?php esc_html_e('Answer', 'FAQ-Manager-Easy-WordPress'); ?></label>
                                            <textarea id="faq-content" name="content"></textarea>
                                        </div>
                                        
                                        <div class="form-field">
                                            <label for="faq-css"><?php esc_html_e('Custom CSS', 'FAQ-Manager-Easy-WordPress'); ?></label>
                                            <textarea id="faq-css" name="css" placeholder="<?php esc_attr_e('Add custom CSS for this FAQ item (optional)', 'FAQ-Manager-Easy-WordPress'); ?>"></textarea>
                                        </div>
                                        
                                        <div class="form-field">
                                            <button type="submit" class="button button-primary"><?php esc_html_e('Save FAQ', 'FAQ-Manager-Easy-WordPress'); ?></button>
                                            <button id="cancel-edit" class="button"><?php esc_html_e('Cancel', 'FAQ-Manager-Easy-WordPress'); ?></button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="col-right" class="faq-manager-col">
                    <div class="col-wrap">
                        <div id="shortcode-info" class="postbox">
                            <h3><?php esc_html_e('Shortcode', 'FAQ-Manager-Easy-WordPress'); ?></h3>
                            <div class="inside">
                                <p><?php esc_html_e('Use this shortcode to display your FAQs:', 'FAQ-Manager-Easy-WordPress'); ?></p>
                                <code>[custom_faq]</code>
                                
                                <p><?php esc_html_e('With parameters:', 'FAQ-Manager-Easy-WordPress'); ?></p>
                                <code>[custom_faq limit="5" orderby="title" order="ASC"]</code>
                                
                                <p><?php esc_html_e('To display specific FAQs by ID:', 'FAQ-Manager-Easy-WordPress'); ?></p>
                                <code>[custom_faq selectedFaqs="1,2,3"]</code>
                            </div>
                        </div>
                        
                        <div id="settings-modal" class="postbox">
                            <h3><?php esc_html_e('Visual Customization', 'FAQ-Manager-Easy-WordPress'); ?></h3>
                            <div class="inside">
                                <form id="faq-settings-form">
                                    <div class="color-field">
                                        <label for="question-bg-color"><?php esc_html_e('Question Background Color', 'FAQ-Manager-Easy-WordPress'); ?></label>
                                        <input type="color" id="question-bg-color" name="question_bg_color" value="<?php echo esc_attr($settings['question_bg_color']); ?>">
                                    </div>
                                    
                                    <div class="color-field">
                                        <label for="question-text-color"><?php esc_html_e('Question Text Color', 'FAQ-Manager-Easy-WordPress'); ?></label>
                                        <input type="color" id="question-text-color" name="question_text_color" value="<?php echo esc_attr($settings['question_text_color']); ?>">
                                    </div>
                                    
                                    <div class="color-field">
                                        <label for="answer-bg-color"><?php esc_html_e('Answer Background Color', 'FAQ-Manager-Easy-WordPress'); ?></label>
                                        <input type="color" id="answer-bg-color" name="answer_bg_color" value="<?php echo esc_attr($settings['answer_bg_color']); ?>">
                                    </div>
                                    
                                    <div class="color-field">
                                        <label for="answer-text-color"><?php esc_html_e('Answer Text Color', 'FAQ-Manager-Easy-WordPress'); ?></label>
                                        <input type="color" id="answer-text-color" name="answer_text_color" value="<?php echo esc_attr($settings['answer_text_color']); ?>">
                                    </div>
                                    
                                    <div class="color-field">
                                        <label for="border-color"><?php esc_html_e('Border Color', 'FAQ-Manager-Easy-WordPress'); ?></label>
                                        <input type="color" id="border-color" name="border_color" value="<?php echo esc_attr($settings['border_color']); ?>">
                                    </div>
                                    
                                    <div class="color-field">
                                        <label for="active-question-bg-color"><?php esc_html_e('Active Question Background', 'FAQ-Manager-Easy-WordPress'); ?></label>
                                        <input type="color" id="active-question-bg-color" name="active_question_bg_color" value="<?php echo esc_attr($settings['active_question_bg_color']); ?>">
                                    </div>
                                    
                                    <div class="color-field">
                                        <label for="hover-bg-color"><?php esc_html_e('Hover Background Color', 'FAQ-Manager-Easy-WordPress'); ?></label>
                                        <input type="color" id="hover-bg-color" name="hover_bg_color" value="<?php echo esc_attr($settings['hover_bg_color']); ?>">
                                    </div>
                                    
                                    <div class="form-field">
                                        <label for="answer-max-height"><?php esc_html_e('Maximum Height for Answers (vh)', 'FAQ-Manager-Easy-WordPress'); ?></label>
                                        <input type="number" id="answer-max-height" name="answer_max_height" value="<?php echo esc_attr($settings['answer_max_height']); ?>" min="10" max="100">
                                    </div>
                                    
                                    <div class="form-field buttons">
                                        <button type="submit" class="button button-primary"><?php esc_html_e('Save Settings', 'FAQ-Manager-Easy-WordPress'); ?></button>
                                        <button id="restore-defaults" class="button"><?php esc_html_e('Restore Defaults', 'FAQ-Manager-Easy-WordPress'); ?></button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <div id="plugin-info" class="postbox">
                            <h3><?php esc_html_e('Plugin Information', 'FAQ-Manager-Easy-WordPress'); ?></h3>
                            <div class="inside">
                                <?php esc_html_e('Developed by', 'FAQ-Manager-Easy-WordPress'); ?> <a href="https://www.instagram.com/ettore_sartori/" target="_blank">Ettore Sartori</a><br>
                                <a href="https://github.com/Ekt0re/FAQ-Manager-Easy-WordPress" target="_blank"><?php esc_html_e('Visit GitHub repository', 'FAQ-Manager-Easy-WordPress'); ?></a><br>
                                <?php esc_html_e('License', 'FAQ-Manager-Easy-WordPress'); ?>: <a href="https://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GPLv3</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Register AJAX handlers
     */
    private function register_ajax_handlers() {
        add_action('wp_ajax_ceafm_search', array($this, 'search_faqs'));
        add_action('wp_ajax_ceafm_get', array($this, 'get_faq'));
        add_action('wp_ajax_ceafm_save', array($this, 'save_faq'));
        add_action('wp_ajax_ceafm_delete', array($this, 'delete_faq'));
        add_action('wp_ajax_ceafm_get_theme_colors', array($this, 'get_theme_colors'));
    }
    
    /**
     * Search FAQs
     */
    public function search_faqs() {
        // Verifica nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_key($_POST['nonce']), 'ceafm-nonce')) {
            wp_send_json_error(array('message' => esc_html__('Verifica di sicurezza fallita.', 'FAQ-Manager-Easy-WordPress')));
            return;
        }
        
        // Sanitizza l'input di ricerca
        $search = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
        
        $args = array(
            'post_type'      => 'custom_faq',
            'posts_per_page' => -1,
            's'              => $search,
            'orderby'        => 'title',
            'order'          => 'ASC'
        );
        
        $faqs = get_posts($args);
        
        if (empty($faqs)) {
            wp_send_json_success(array(
                'faqs' => array(),
                'message' => esc_html__('Nessuna FAQ trovata.', 'FAQ-Manager-Easy-WordPress')
            ));
            return;
        }
        
        $items = array();
        
        foreach ($faqs as $faq) {
            $items[] = array(
                'id'    => $faq->ID,
                'title' => esc_html($faq->post_title)
            );
        }
        
        wp_send_json_success(array(
            'faqs' => $items
        ));
    }
    
    /**
     * Get FAQ
     */
    public function get_faq() {
        // Verifica il nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_key($_POST['nonce']), 'ceafm-nonce')) {
            wp_send_json_error(array('message' => esc_html__('Verifica di sicurezza fallita.', 'FAQ-Manager-Easy-WordPress')));
            return;
        }
        
        // Controlla se l'ID è presente e valido
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if ($id <= 0) {
            wp_send_json_error(array('message' => __('ID FAQ non valido.', 'FAQ-Manager-Easy-WordPress')));
            return;
        }
        
        // Recupera i dati della FAQ
        $faq = get_post($id);
        
        // Controlla se la FAQ esiste e ha il tipo corretto
        if (!$faq || $faq->post_type !== 'custom_faq') {
            wp_send_json_error(array('message' => __('FAQ non trovata.', 'FAQ-Manager-Easy-WordPress')));
            return;
        }
        
        // Recupera i metadati personalizzati
        $custom_css = get_post_meta($id, '_faq_custom_css', true);
        
        // Costruisci un array di risposta coerente
        $response = array(
            'id'      => $faq->ID,
            'title'   => htmlspecialchars_decode($faq->post_title),
            'content' => $faq->post_content,
            'css'     => $custom_css ? $custom_css : ''
        );
        
        // Invia la risposta di successo
        wp_send_json_success($response);
        exit; // Assicura che nulla venga eseguito dopo l'invio della risposta
    }
    
    /**
     * Save FAQ
     */
    public function save_faq() {
        // Verifica il nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_key($_POST['nonce']), 'ceafm-nonce')) {
            wp_send_json_error(array('message' => esc_html__('Verifica di sicurezza fallita.', 'FAQ-Manager-Easy-WordPress')));
            return;
        }
        
        // Recupera e sanitizza i dati inviati
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
        $content = isset($_POST['content']) ? wp_kses_post(wp_unslash($_POST['content'])) : '';
        $css = isset($_POST['css']) ? sanitize_textarea_field(wp_unslash($_POST['css'])) : '';
        
        // Validate title
        if (empty($title)) {
            wp_send_json_error(array('message' => __('Il titolo è obbligatorio.', 'FAQ-Manager-Easy-WordPress')));
        }
        
        // Prepara i dati per il post
        $post_data = array(
            'post_title'   => $title,
            'post_content' => $content,
            'post_type'    => 'custom_faq',
            'post_status'  => 'publish'
        );
        
        // Aggiorna o crea il post FAQ
        if ($id > 0) {
            // Verifica che il post esista e sia di tipo 'custom_faq'
            $existing_post = get_post($id);
            if (!$existing_post || $existing_post->post_type !== 'custom_faq') {
                wp_send_json_error(array('message' => __('Impossibile aggiornare: FAQ non trovata.', 'FAQ-Manager-Easy-WordPress')));
                return;
            }
            
            $post_data['ID'] = $id;
            $post_id = wp_update_post($post_data);
        } else {
            $post_id = wp_insert_post($post_data);
        }
        
        // Verifica se ci sono stati errori nell'inserimento/aggiornamento
        if (is_wp_error($post_id)) {
            wp_send_json_error(array(
                'message' => $post_id->get_error_message(),
                'details' => $post_id->get_error_data()
            ));
            return;
        }
        
        // Salva i CSS personalizzati come meta del post
        update_post_meta($post_id, '_faq_custom_css', $css);
        
        // Preparazione della risposta di successo
        $response = array(
            'id'      => $post_id,
            'title'   => $title,
            'message' => $id > 0 
                ? __('FAQ aggiornata con successo.', 'FAQ-Manager-Easy-WordPress') 
                : __('Nuova FAQ creata con successo.', 'FAQ-Manager-Easy-WordPress')
        );
        
        // Invia risposta di successo
        wp_send_json_success($response);
        exit;
    }
    
    /**
     * Delete FAQ
     */
    public function delete_faq() {
        // Verifica il nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_key($_POST['nonce']), 'ceafm-nonce')) {
            wp_send_json_error(array('message' => esc_html__('Verifica di sicurezza fallita.', 'FAQ-Manager-Easy-WordPress')));
            return;
        }
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if ($id <= 0) {
            wp_send_json_error(array('message' => __('ID FAQ non valido.', 'FAQ-Manager-Easy-WordPress')));
            return;
        }
        
        $result = wp_delete_post($id, true);
        
        if (!$result) {
            wp_send_json_error(array('message' => __('Impossibile eliminare la FAQ.', 'FAQ-Manager-Easy-WordPress')));
            return;
        }
        
        wp_send_json_success(array('message' => __('FAQ eliminata con successo.', 'FAQ-Manager-Easy-WordPress')));
    }
    
    /**
     * FAQ shortcode
     */
    public function faq_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit'   => -1,
            'orderby' => 'title',
            'order'   => 'ASC',
            'selectedFaqs' => ''
        ), $atts, 'custom_faq');
        
        $args = array(
            'post_type'      => 'custom_faq',
            'posts_per_page' => intval($atts['limit']),
            'orderby'        => $atts['orderby'],
            'order'          => $atts['order']
        );
        
        // Gestisci la selezione di FAQ specifiche
        if (!empty($atts['selectedFaqs'])) {
            $selected_ids = array();
            
            // Se è una stringa (da shortcode) converti in array
            if (is_string($atts['selectedFaqs'])) {
                $selected_ids = array_map('intval', explode(',', $atts['selectedFaqs']));
            } 
            // Se è già un array (da blocco Gutenberg)
            else if (is_array($atts['selectedFaqs'])) {
                $selected_ids = array_map('intval', $atts['selectedFaqs']);
            }
            
            if (!empty($selected_ids)) {
                $args['post__in'] = $selected_ids;
                // Quando specificati gli ID, l'ordinamento predefinito è per post__in
                if ($atts['orderby'] === 'title' || $atts['orderby'] === 'date') {
                    $args['orderby'] = $atts['orderby'];
                }
            }
        }
        
        $faqs = get_posts($args);
        
        if (empty($faqs)) {
            return '<p>' . esc_html__('Nessuna FAQ disponibile.', 'FAQ-Manager-Easy-WordPress') . '</p>';
        }
        
        // Assicuriamoci che lo stile sia caricato
        if (!wp_style_is('ceafm-frontend', 'enqueued')) {
            wp_enqueue_style('ceafm-frontend');
        }
        
        $custom_css = '';
        ob_start();
        
        echo '<div class="custom-faq-container">';
        
        foreach ($faqs as $faq) {
            $faq_custom_css = get_post_meta($faq->ID, '_faq_custom_css', true);
            $css_id = 'faq-' . $faq->ID;
            
            if (!empty($faq_custom_css)) {
                $custom_css .= '#' . esc_attr($css_id) . ' { ' . esc_html($faq_custom_css) . ' }';
            }
            
            echo '<div class="faq-item" id="' . esc_attr($css_id) . '">';
            echo '<div class="faq-question">' . esc_html($faq->post_title) . '</div>';
            echo '<div class="faq-answer">' . wp_kses_post(apply_filters('the_content', $faq->post_content)) . '</div>';
            echo '</div>';
        }
        
        echo '</div>';
        
        // Aggiunge CSS personalizzato se presente
        if (!empty($custom_css)) {
            wp_add_inline_style('ceafm-frontend', $custom_css);
        }
        
        return ob_get_clean();
    }

    /**
     * Register Gutenberg block
     */
    public function register_faq_block() {
        // Check if Gutenberg is available
        if (!function_exists('register_block_type')) {
            return;
        }

        // Register script for the block
        wp_register_script(
            'ceafm-block-editor',
            CEAFM_PLUGIN_URL . 'assets/js/block.js',
            array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor', 'wp-i18n', 'wp-api-fetch'),
            CEAFM_VERSION,
            true  // Carica nel footer
        );

        // Register style for the block editor
        wp_register_style(
            'ceafm-block-editor-style',
            CEAFM_PLUGIN_URL . 'assets/css/block-editor.css',
            array('wp-edit-blocks'),
            CEAFM_VERSION
        );

        // Register the block
        register_block_type('ceafm/faq-block', array(
            'editor_script' => 'ceafm-block-editor',
            'editor_style' => 'ceafm-block-editor-style',
            'render_callback' => array($this, 'render_faq_block'),
            'attributes' => array(
                'limit' => array(
                    'type' => 'number',
                    'default' => -1
                ),
                'orderby' => array(
                    'type' => 'string',
                    'default' => 'title'
                ),
                'order' => array(
                    'type' => 'string',
                    'default' => 'ASC'
                ),
                'selectedFaqs' => array(
                    'type' => 'array',
                    'default' => []
                ),
                'className' => array(
                    'type' => 'string'
                )
            )
        ));
    }

    /**
     * Render callback for the Gutenberg block
     */
    public function render_faq_block($attributes) {
        $attributes = wp_parse_args($attributes, array(
            'limit' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'selectedFaqs' => []
        ));

        // Use the same function as the shortcode for rendering
        return $this->faq_shortcode($attributes);
    }

    /**
     * Recupera i colori dal tema attivo
     */
    public function get_theme_colors() {
        // Verifica nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_key(wp_unslash($_POST['nonce'])), 'ceafm-nonce')) {
            wp_send_json_error(array('message' => esc_html__('Verifica di sicurezza fallita.', 'FAQ-Manager-Easy-WordPress')));
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => esc_html__('Non hai i permessi necessari.', 'FAQ-Manager-Easy-WordPress')));
            return;
        }
        
        // Ottieni i colori dal tema attivo
        // In WordPress, questo può variare a seconda del tema
        $theme_colors = array();
        
        // Prova a ottenere i colori da Astra Theme
        if (function_exists('astra_get_option')) {
            $theme_colors = array(
                'question_bg_color' => sanitize_hex_color(wp_unslash(astra_get_option('site-background-color', '#f8f8f8'))),
                'question_text_color' => sanitize_hex_color(wp_unslash(astra_get_option('text-color', '#333333'))),
                'answer_bg_color' => sanitize_hex_color(wp_unslash('#ffffff')),
                'answer_text_color' => sanitize_hex_color(wp_unslash(astra_get_option('text-color', '#333333'))),
                'border_color' => sanitize_hex_color(wp_unslash(astra_get_option('border-color', '#e2e2e2'))),
                'active_question_bg_color' => sanitize_hex_color(wp_unslash(astra_get_option('header-bg-color', '#eaeaea'))),
                'hover_bg_color' => sanitize_hex_color(wp_unslash('#f0f0f0'))
            );
        } 
        // Altrimenti prova con Twenty Twenty-Three
        else {
            // Ottieni i colori dal tema
            $theme_colors = array(
                'question_bg_color' => sanitize_hex_color('#f8f8f8'),
                'question_text_color' => sanitize_hex_color('#333333'),
                'answer_bg_color' => sanitize_hex_color('#ffffff'),
                'answer_text_color' => sanitize_hex_color('#333333'),
                'border_color' => sanitize_hex_color('#e2e2e2'),
                'active_question_bg_color' => sanitize_hex_color('#eaeaea'),
                'hover_bg_color' => sanitize_hex_color('#f0f0f0')
            );
        }
        
        wp_send_json_success(array('colors' => $theme_colors));
    }
}

// Inizializza il plugin
$ceafm_plugin = new Custom_Easy_FAQ_Manager();

// Registra hook per l'attivazione
register_activation_hook(__FILE__, 'ceafm_activate');

/**
 * Funzione da eseguire durante l'attivazione del plugin
 */
function ceafm_activate() {
    // Crea il CPT
    $ceafm_plugin = new Custom_Easy_FAQ_Manager();
    $ceafm_plugin->register_faq_post_type();
    
    // Impostazioni predefinite
    $default_settings = array(
        'question_bg_color' => '#f9f9f9',
        'question_text_color' => '#333333',
        'answer_bg_color' => '#ffffff',
        'answer_text_color' => '#333333',
        'border_color' => '#dddddd',
        'active_question_bg_color' => '#e9e9e9',
        'hover_bg_color' => '#f0f0f0',
        'answer_max_height' => '90'
    );
    
    // Salva le impostazioni solo se non esistono già
    if (!get_option('ceafm_settings')) {
        update_option('ceafm_settings', $default_settings);
    }
    
    // Flush rewrite rules dopo la registrazione del custom post type
    flush_rewrite_rules();
} 