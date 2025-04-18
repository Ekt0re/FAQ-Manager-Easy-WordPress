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
 * Text Domain: custom-faq-manager
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Custom_FAQ_Manager {
    
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
        load_plugin_textdomain('custom-faq-manager', false, dirname(plugin_basename(__FILE__)) . '/languages');
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
            'name'               => _x('FAQs', 'post type general name', 'custom-faq-manager'),
            'singular_name'      => _x('FAQ', 'post type singular name', 'custom-faq-manager'),
            'menu_name'          => _x('FAQs', 'admin menu', 'custom-faq-manager'),
            'name_admin_bar'     => _x('FAQ', 'add new on admin bar', 'custom-faq-manager'),
            'add_new'            => _x('Add New', 'faq', 'custom-faq-manager'),
            'add_new_item'       => __('Add New FAQ', 'custom-faq-manager'),
            'new_item'           => __('New FAQ', 'custom-faq-manager'),
            'edit_item'          => __('Edit FAQ', 'custom-faq-manager'),
            'view_item'          => __('View FAQ', 'custom-faq-manager'),
            'all_items'          => __('All FAQs', 'custom-faq-manager'),
            'search_items'       => __('Search FAQs', 'custom-faq-manager'),
            'not_found'          => __('No FAQs found.', 'custom-faq-manager'),
            'not_found_in_trash' => __('No FAQs found in trash.', 'custom-faq-manager')
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
            __('FAQ Management', 'custom-faq-manager'),
            __('FAQ Manager', 'custom-faq-manager'),
            'manage_options',
            'custom-faq-manager',
            array($this, 'admin_page_display'),
            'dashicons-format-status',
            30
        );
    }
    
    /**
     * Register admin scripts and styles
     */
    public function register_admin_scripts($hook) {
        if ('toplevel_page_custom-faq-manager' !== $hook) {
            return;
        }
        
        wp_enqueue_style('custom-faq-admin-css', plugin_dir_url(__FILE__) . 'assets/css/admin.css', array(), '1.0.0');
        wp_enqueue_script('custom-faq-admin-js', plugin_dir_url(__FILE__) . 'assets/js/admin.js', array('jquery'), '1.0.0', true);
        
        wp_localize_script('custom-faq-admin-js', 'custom_faq_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('custom-faq-nonce')
        ));
    }
    
    /**
     * Register frontend scripts and styles
     */
    public function register_frontend_scripts() {
        wp_register_style('custom-faq-manager-frontend', plugins_url('assets/css/frontend.css', __FILE__), array(), '1.2');
        wp_register_script('custom-faq-manager-frontend', plugins_url('assets/js/frontend.js', __FILE__), array('jquery'), '1.2', true);
        wp_register_script('custom-faq-fix-answers', plugins_url('assets/js/fix-faq-answers.js', __FILE__), array(), '1.2', true);
        
        // Carica automaticamente gli asset se è presente un blocco o uno shortcode FAQ
        global $post;
        if (is_singular() && is_a($post, 'WP_Post')) {
            // Controlla blocchi e shortcode
            if (has_block('custom-faq-manager/faq-block', $post) || 
                has_shortcode($post->post_content, 'custom_faq') ||
                strpos($post->post_content, 'wp-block-astra-child-faq-block') !== false) {
                
                wp_enqueue_style('custom-faq-manager-frontend');
                wp_enqueue_script('custom-faq-manager-frontend');
                wp_enqueue_script('custom-faq-fix-answers');
                
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
        $settings = get_option('custom_faq_settings', array(
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
        $settings_json = json_encode($js_settings);
        
        // Aggiungi lo script al footer
        add_action('wp_footer', function() use ($settings_json) {
            echo "<script>\n";
            echo "window.customFaqSettings = " . wp_json_encode(json_decode($settings_json)) . ";\n";
            echo "</script>";
        });
    }
    
    /**
     * Admin page display
     */
    public function admin_page_display() {
        // Carica le impostazioni grafiche
        $settings = get_option('custom_faq_settings', array(
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
            <h1><?php esc_html_e('FAQ Management', 'custom-faq-manager'); ?></h1>
            
            <div class="language-selector" style="margin-bottom: 20px; text-align: right;">
                <form id="language-selector-form">
                    <label for="language-selector"><?php esc_html_e('Language:', 'custom-faq-manager'); ?></label>
                    <select id="language-selector" name="language">
                        <?php 
                        foreach ($this->get_supported_languages() as $code => $name) {
                            $selected = ($code === $this->get_current_language()) ? 'selected' : '';
                            echo '<option value="' . esc_attr($code) . '" ' . esc_attr($selected) . '>' . esc_html($name) . '</option>';
                        }
                        ?>
                    </select>
                </form>
            </div>
            
            <div class="faq-container">
                <div class="faq-list-container">
                    <h2><?php esc_html_e('FAQ Items List', 'custom-faq-manager'); ?></h2>
                    
                    <div class="faq-toolbar">
                        <div class="faq-search">
                            <input type="text" id="faq-search" placeholder="<?php esc_attr_e('Search', 'custom-faq-manager'); ?>">
                            <button id="search-btn" class="button"><span class="dashicons dashicons-search"></span></button>
                        </div>
                        <button id="new-faq-btn" class="button button-primary"><?php esc_html_e('New', 'custom-faq-manager'); ?></button>
                    </div>
                    
                    <div class="faq-list">
                        <table class="widefat">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Name', 'custom-faq-manager'); ?></th>
                                    <th><?php esc_html_e('Actions', 'custom-faq-manager'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="faq-list-body">
                                <?php
                                $faqs = get_posts(array(
                                    'post_type'      => 'custom_faq',
                                    'posts_per_page' => -1,
                                    'orderby'        => 'title',
                                    'order'          => 'ASC'
                                ));
                                
                                if ($faqs) {
                                    foreach ($faqs as $faq) {
                                        ?>
                                        <tr data-id="<?php echo esc_attr($faq->ID); ?>">
                                            <td class="faq-title"><?php echo esc_html($faq->post_title); ?></td>
                                            <td class="faq-actions">
                                                <button class="edit-faq button" data-id="<?php echo esc_attr($faq->ID); ?>"><?php esc_html_e('Edit', 'custom-faq-manager'); ?></button>
                                                <button class="delete-faq button" data-id="<?php echo esc_attr($faq->ID); ?>"><?php esc_html_e('Delete', 'custom-faq-manager'); ?></button>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                } else {
                                    ?>
                                    <tr class="no-items">
                                        <td colspan="2"><?php esc_html_e('No FAQs found.', 'custom-faq-manager'); ?></td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div id="faq-editor" class="postbox">
                    <h2 id="editor-title" class="hndle"><?php esc_html_e('Add New FAQ', 'custom-faq-manager'); ?></h2>
                    <div class="inside">
                        <form id="faq-form">
                            <input type="hidden" id="faq-id" value="">
                            
                            <div class="form-field">
                                <label for="faq-title"><?php esc_html_e('Question', 'custom-faq-manager'); ?></label>
                                <input type="text" id="faq-title" name="title" class="widefat">
                            </div>
                            
                            <div class="form-field">
                                <label for="faq-content"><?php esc_html_e('Answer', 'custom-faq-manager'); ?></label>
                                <?php 
                                wp_editor('', 'faq-content', array(
                                    'textarea_name' => 'content',
                                    'media_buttons' => true,
                                    'textarea_rows' => 8
                                )); 
                                ?>
                            </div>
                            
                            <div class="form-field">
                                <label for="faq-css"><?php esc_html_e('Custom CSS', 'custom-faq-manager'); ?></label>
                                <textarea id="faq-css" name="css" class="widefat" rows="5" placeholder="<?php esc_attr_e('Add custom CSS for this FAQ item (optional)', 'custom-faq-manager'); ?>"></textarea>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="button button-primary"><?php esc_html_e('Save FAQ', 'custom-faq-manager'); ?></button>
                                <button id="cancel-edit" class="button"><?php esc_html_e('Cancel', 'custom-faq-manager'); ?></button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="faq-settings-container">
                    <h2><?php esc_html_e('Visual Customization', 'custom-faq-manager'); ?></h2>
                    <form id="faq-settings-form">
                        <div class="settings-actions" style="margin-bottom: 15px;">
                            <button type="button" id="import-theme-colors" class="button"><?php esc_html_e('Import from Theme', 'custom-faq-manager'); ?></button>
                            <span class="settings-info" style="margin-left: 10px; font-style: italic;"><?php esc_html_e('Import colors from active theme', 'custom-faq-manager'); ?></span>
                        </div>
                        
                        <div class="settings-grid">
                            <div class="form-field">
                                <label for="question_bg_color"><?php esc_html_e('Question background color', 'custom-faq-manager'); ?></label>
                                <input type="color" id="question_bg_color" name="question_bg_color" value="<?php echo esc_attr($settings['question_bg_color']); ?>">
                            </div>
                            
                            <div class="form-field">
                                <label for="question_text_color"><?php esc_html_e('Question text color', 'custom-faq-manager'); ?></label>
                                <input type="color" id="question_text_color" name="question_text_color" value="<?php echo esc_attr($settings['question_text_color']); ?>">
                            </div>
                            
                            <div class="form-field">
                                <label for="answer_bg_color"><?php esc_html_e('Answer background color', 'custom-faq-manager'); ?></label>
                                <input type="color" id="answer_bg_color" name="answer_bg_color" value="<?php echo esc_attr($settings['answer_bg_color']); ?>">
                            </div>
                            
                            <div class="form-field">
                                <label for="answer_text_color"><?php esc_html_e('Answer text color', 'custom-faq-manager'); ?></label>
                                <input type="color" id="answer_text_color" name="answer_text_color" value="<?php echo esc_attr($settings['answer_text_color']); ?>">
                            </div>
                            
                            <div class="form-field">
                                <label for="border_color"><?php esc_html_e('Border color', 'custom-faq-manager'); ?></label>
                                <input type="color" id="border_color" name="border_color" value="<?php echo esc_attr($settings['border_color']); ?>">
                            </div>
                            
                            <div class="form-field">
                                <label for="active_question_bg_color"><?php esc_html_e('Active question background color', 'custom-faq-manager'); ?></label>
                                <input type="color" id="active_question_bg_color" name="active_question_bg_color" value="<?php echo esc_attr($settings['active_question_bg_color']); ?>">
                            </div>
                            
                            <div class="form-field">
                                <label for="hover_bg_color"><?php esc_html_e('Hover background color', 'custom-faq-manager'); ?></label>
                                <input type="color" id="hover_bg_color" name="hover_bg_color" value="<?php echo esc_attr($settings['hover_bg_color']); ?>">
                            </div>
                            
                            <div class="form-field">
                                <label for="answer_max_height"><?php esc_html_e('Maximum answer height (px)', 'custom-faq-manager'); ?></label>
                                <input type="number" id="answer_max_height" name="answer_max_height" min="0" max="500" value="<?php echo esc_attr($settings['answer_max_height']); ?>">
                            </div>
                        </div>
                        
                        <div class="preview-container" style="margin-top: 20px;">
                            <h3><?php esc_html_e('Preview', 'custom-faq-manager'); ?></h3>
                            <div id="faq-preview-item">
                                <div id="faq-preview-question"><?php esc_html_e('Sample Question', 'custom-faq-manager'); ?></div>
                                <div id="faq-preview-answer">
                                    <p><?php esc_html_e('This is a sample answer. The appearance of FAQs on your site will follow the style settings you choose above.', 'custom-faq-manager'); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div id="settings-status" style="margin-top: 15px; padding: 10px; display: none;"></div>
                        
                        <div class="settings-actions" style="margin-top: 15px;">
                            <button type="submit" class="button button-primary"><?php esc_html_e('Save Settings', 'custom-faq-manager'); ?></button>
                        </div>
                    </form>
                </div>
                
                <div class="shortcode-info">
                    <h2><?php esc_html_e('Shortcode Usage', 'custom-faq-manager'); ?></h2>
                    <p><?php esc_html_e('Use the following shortcode to display FAQs on your posts or pages:', 'custom-faq-manager'); ?></p>
                    <code>[custom_faq]</code>
                    
                    <h3><?php esc_html_e('Shortcode Parameters', 'custom-faq-manager'); ?></h3>
                    <ul>
                        <li><code>limit</code> - <?php esc_html_e('Number of FAQs to display (default: all)', 'custom-faq-manager'); ?></li>
                        <li><code>orderby</code> - <?php esc_html_e('Sort by field (default: title)', 'custom-faq-manager'); ?></li>
                        <li><code>order</code> - <?php esc_html_e('Sort order (default: ASC)', 'custom-faq-manager'); ?></li>
                        <li><code>selectedFaqs</code> - <?php esc_html_e('Comma-separated list of FAQ IDs to display', 'custom-faq-manager'); ?></li>
                    </ul>
                    
                    <h4><?php esc_html_e('Example', 'custom-faq-manager'); ?></h4>
                    <code>[custom_faq limit="5" orderby="date" order="DESC"]</code>
                </div>
                
                <div class="faq-author-info">
                    <h3><?php esc_html_e('Plugin Information', 'custom-faq-manager'); ?></h3>
                    <p>
                        <strong>Custom Easy FAQ Manager</strong> v1.3<br>
                        <?php esc_html_e('Developed by', 'custom-faq-manager'); ?> <a href="https://www.instagram.com/ettore_sartori/" target="_blank">Ettore Sartori</a><br>
                        <a href="https://github.com/Ekt0re/FAQ-Manager-Easy-WordPress" target="_blank"><?php esc_html_e('Visit GitHub repository', 'custom-faq-manager'); ?></a><br>
                        <?php esc_html_e('License', 'custom-faq-manager'); ?>: <a href="https://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GPLv3</a>
                    </p>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Register AJAX handlers
     */
    private function register_ajax_handlers() {
        add_action('wp_ajax_search_faqs', array($this, 'search_faqs'));
        add_action('wp_ajax_get_faq', array($this, 'get_faq'));
        add_action('wp_ajax_save_faq', array($this, 'save_faq'));
        add_action('wp_ajax_delete_faq', array($this, 'delete_faq'));
        add_action('wp_ajax_save_faq_settings', array($this, 'save_faq_settings'));
        add_action('wp_ajax_get_theme_colors', array($this, 'get_theme_colors'));
    }
    
    /**
     * Search FAQs - AJAX handler
     */
    public function search_faqs() {
        // Verifica nonce
        check_ajax_referer('custom-faq-nonce', 'nonce');
        
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
                'message' => esc_html__('Nessuna FAQ trovata.', 'custom-faq-manager')
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
     * Get FAQ - AJAX handler
     */
    public function get_faq() {
        // Verifica il nonce
        check_ajax_referer('custom-faq-nonce', 'nonce');
        
        // Controlla se l'ID è presente e valido
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if ($id <= 0) {
            wp_send_json_error(array('message' => __('ID FAQ non valido.', 'custom-faq-manager')));
            return;
        }
        
        // Recupera i dati della FAQ
        $faq = get_post($id);
        
        // Controlla se la FAQ esiste e ha il tipo corretto
        if (!$faq || $faq->post_type !== 'custom_faq') {
            wp_send_json_error(array('message' => __('FAQ non trovata.', 'custom-faq-manager')));
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
     * Save FAQ - AJAX handler
     */
    public function save_faq() {
        // Verifica il nonce
        check_ajax_referer('custom-faq-nonce', 'nonce');
        
        // Recupera e sanitizza i dati inviati
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
        $content = isset($_POST['content']) ? wp_kses_post(wp_unslash($_POST['content'])) : '';
        $css = isset($_POST['css']) ? sanitize_textarea_field(wp_unslash($_POST['css'])) : '';
        
        // Verifica se il titolo è presente
        if (empty($title)) {
            wp_send_json_error(array('message' => __('Il titolo è obbligatorio.', 'custom-faq-manager')));
            return;
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
                wp_send_json_error(array('message' => __('Impossibile aggiornare: FAQ non trovata.', 'custom-faq-manager')));
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
                ? __('FAQ aggiornata con successo.', 'custom-faq-manager') 
                : __('Nuova FAQ creata con successo.', 'custom-faq-manager')
        );
        
        // Invia risposta di successo
        wp_send_json_success($response);
        exit;
    }
    
    /**
     * Delete FAQ - AJAX handler
     */
    public function delete_faq() {
        check_ajax_referer('custom-faq-nonce', 'nonce');
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if ($id <= 0) {
            wp_send_json_error(array('message' => __('ID FAQ non valido.', 'custom-faq-manager')));
            return;
        }
        
        $result = wp_delete_post($id, true);
        
        if (!$result) {
            wp_send_json_error(array('message' => __('Impossibile eliminare la FAQ.', 'custom-faq-manager')));
            return;
        }
        
        wp_send_json_success(array('message' => __('FAQ eliminata con successo.', 'custom-faq-manager')));
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
            return '<p>' . esc_html__('Nessuna FAQ disponibile.', 'custom-faq-manager') . '</p>';
        }
        
        ob_start();
        
        echo '<div class="custom-faq-container">';
        
        foreach ($faqs as $faq) {
            $custom_css = get_post_meta($faq->ID, '_faq_custom_css', true);
            $css_id = 'faq-' . $faq->ID;
            
            if (!empty($custom_css)) {
                echo '<style type="text/css">#' . esc_attr($css_id) . ' { ' . esc_html($custom_css) . ' }</style>';
            }
            
            echo '<div class="faq-item" id="' . esc_attr($css_id) . '">';
            echo '<div class="faq-question">' . esc_html($faq->post_title) . '</div>';
            echo '<div class="faq-answer">' . wp_kses_post(apply_filters('the_content', $faq->post_content)) . '</div>';
            echo '</div>';
        }
        
        echo '</div>';
        
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
            'custom-faq-block-editor',
            plugin_dir_url(__FILE__) . 'assets/js/block.js',
            array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor', 'wp-i18n', 'wp-api-fetch'),
            filemtime(plugin_dir_path(__FILE__) . 'assets/js/block.js'),
            true  // Carica nel footer
        );

        // Register style for the block editor
        wp_register_style(
            'custom-faq-block-editor-style',
            plugin_dir_url(__FILE__) . 'assets/css/block-editor.css',
            array('wp-edit-blocks'),
            filemtime(plugin_dir_path(__FILE__) . 'assets/css/block-editor.css')
        );

        // Register the block
        register_block_type('custom-faq-manager/faq-block', array(
            'editor_script' => 'custom-faq-block-editor',
            'editor_style' => 'custom-faq-block-editor-style',
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
     * Save FAQ Settings - AJAX handler
     */
    public function save_faq_settings() {
        // Verifica nonce
        check_ajax_referer('custom-faq-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => esc_html__('Non hai i permessi necessari.', 'custom-faq-manager')));
            return;
        }
        
        // Assicurati che i dati necessari siano presenti
        $required_fields = array(
            'question_bg_color',
            'question_text_color',
            'answer_bg_color',
            'answer_text_color',
            'border_color',
            'active_question_bg_color',
            'hover_bg_color',
            'answer_max_height'
        );
        
        $settings = array();
        $has_errors = false;
        
        // Verifica e salva ogni campo
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field])) {
                $has_errors = true;
                $settings[$field] = '';
            } else {
                // Sanitizza i campi colore
                if ($field === 'answer_max_height') {
                    $settings[$field] = intval(wp_unslash($_POST[$field]));
                } else {
                    $color = $this->sanitize_custom_color(sanitize_text_field(wp_unslash($_POST[$field])));
                    // Se il colore non è valido, usa un valore predefinito
                    $settings[$field] = !empty($color) ? $color : '#ffffff';
                }
            }
        }
        
        // Salva le impostazioni anche se ci sono errori (con valori predefiniti)
        update_option('custom_faq_settings', $settings);
        
        if ($has_errors) {
            wp_send_json_error(array('message' => esc_html__('Alcuni campi non sono validi. Sono stati usati valori predefiniti.', 'custom-faq-manager')));
        } else {
            wp_send_json_success(array('message' => esc_html__('Impostazioni salvate con successo.', 'custom-faq-manager')));
        }
    }
    
    /**
     * Sanifica un colore esadecimale in modo sicuro
     */
    private function sanitize_custom_color($color) {
        if ('' === $color) {
            return '';
        }
        
        // Rimuovi eventuali spazi
        $color = trim($color);
        
        // Verifica se è un valore esadecimale valido
        if (preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', $color)) {
            return $color;
        }
        
        return '';
    }

    /**
     * Recupera i colori dal tema attivo
     */
    public function get_theme_colors() {
        check_ajax_referer('custom-faq-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Non hai i permessi necessari.', 'custom-faq-manager')));
            return;
        }
        
        // Ottieni i colori dal tema attivo
        // In WordPress, questo può variare a seconda del tema
        $theme_colors = array();
        
        // Prova a ottenere i colori da Astra Theme
        if (function_exists('astra_get_option')) {
            $theme_colors = array(
                'question_bg_color' => astra_get_option('site-background-color', '#f8f8f8'),
                'question_text_color' => astra_get_option('text-color', '#333333'),
                'answer_bg_color' => '#ffffff',
                'answer_text_color' => astra_get_option('text-color', '#333333'),
                'border_color' => astra_get_option('border-color', '#e2e2e2'),
                'active_question_bg_color' => astra_get_option('header-bg-color', '#eaeaea'),
                'hover_bg_color' => '#f0f0f0'
            );
        } 
        // Altrimenti prova con Twenty Twenty-Three
        else {
            // Ottieni i colori dal tema
            $theme_colors = array(
                'question_bg_color' => '#f8f8f8',
                'question_text_color' => '#333333',
                'answer_bg_color' => '#ffffff',
                'answer_text_color' => '#333333',
                'border_color' => '#e2e2e2',
                'active_question_bg_color' => '#eaeaea',
                'hover_bg_color' => '#f0f0f0'
            );
        }
        
        wp_send_json_success(array('colors' => $theme_colors));
    }
}

// Initialize the plugin
$custom_faq_manager = new Custom_FAQ_Manager();

// Create plugin directory structure on activation
register_activation_hook(__FILE__, 'custom_faq_manager_activate');

/**
 * Funzione da eseguire durante l'attivazione del plugin
 */
function custom_faq_manager_activate() {
    global $wp_filesystem;
    
    // Inizializza il file system di WordPress
    if (!function_exists('WP_Filesystem')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    
    // Richiedi le credenziali se necessario
    $creds = request_filesystem_credentials('');
    
    if (false === $creds || !WP_Filesystem($creds)) {
        // Se non possiamo accedere al filesystem, continuiamo comunque
        return;
    }
    
    // Crea le directory necessarie
    $dir = plugin_dir_path(__FILE__);
    
    // Directory assets
    $assets_dir = $dir . 'assets';
    if (!$wp_filesystem->is_dir($assets_dir)) {
        $wp_filesystem->mkdir($assets_dir, FS_CHMOD_DIR);
    }
    
    // Directory CSS
    $css_dir = $dir . 'assets/css';
    if (!$wp_filesystem->is_dir($css_dir)) {
        $wp_filesystem->mkdir($css_dir, FS_CHMOD_DIR);
    }
    
    // Directory JS
    $js_dir = $dir . 'assets/js';
    if (!$wp_filesystem->is_dir($js_dir)) {
        $wp_filesystem->mkdir($js_dir, FS_CHMOD_DIR);
    }
    
    // Directory templates
    $templates_dir = $dir . 'templates';
    if (!$wp_filesystem->is_dir($templates_dir)) {
        $wp_filesystem->mkdir($templates_dir, FS_CHMOD_DIR);
    }
    
    // Crea i file CSS e JS
    create_css_files();
    create_js_files();
    
    // Flush rewrite rules dopo la registrazione del custom post type
    flush_rewrite_rules();
}

/**
 * Create CSS files for the plugin
 */
function create_css_files() {
    global $wp_filesystem;
    
    // Inizializza il file system di WordPress se non è già stato fatto
    if (!function_exists('WP_Filesystem')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    
    // Richiedi le credenziali se necessario
    if (!WP_Filesystem()) {
        return;
    }
    
    $dir = plugin_dir_path(__FILE__);
    
    // Admin CSS
    $admin_css = $dir . 'assets/css/admin.css';
    if (!$wp_filesystem->exists($admin_css)) {
        $css_content = '/**
 * Custom FAQ Manager - Admin Styles
 */

.faq-container {
    display: flex;
    gap: 30px;
    margin-top: 20px;
}

.faq-list-container {
    flex: 1;
}

.faq-editor {
    flex: 1;
    background: #fff;
    padding: 20px;
    border-radius: 5px;
    border: 1px solid #ddd;
}

.faq-toolbar {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
}

.faq-search {
    display: flex;
    gap: 5px;
}

.faq-list table {
    border-collapse: collapse;
    width: 100%;
}

.faq-title {
    width: 70%;
}

.faq-actions {
    text-align: right;
}

.form-field {
    margin-bottom: 20px;
}

.form-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-field input[type="text"],
.form-field textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.form-actions {
    margin-top: 20px;
    display: flex;
    gap: 10px;
}

.faq-shortcode-info {
    margin-top: 30px;
    background: #fff;
    padding: 20px;
    border-radius: 5px;
    border: 1px solid #ddd;
}

.faq-shortcode-info code {
    background: #f5f5f5;
    padding: 3px 5px;
    border-radius: 3px;
}

/* Responsive adjustments */
@media screen and (max-width: 782px) {
    .faq-container {
        flex-direction: column;
    }
    
    .faq-editor,
    .faq-list-container {
        width: 100%;
    }
}';
        $wp_filesystem->put_contents($admin_css, $css_content, FS_CHMOD_FILE);
    }
    
    // Frontend CSS
    $frontend_css = $dir . 'assets/css/frontend.css';
    if (!$wp_filesystem->exists($frontend_css)) {
        $css_content = '/**
 * Custom FAQ Manager - Frontend Styles
 */

.custom-faq-container {
    margin: 30px 0;
    max-width: 100%;
    font-family: inherit;
}

.faq-item {
    margin-bottom: 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    overflow: hidden;
    background-color: #fff;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    transition: box-shadow 0.3s ease;
}

.faq-item:hover {
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.faq-question {
    background-color: #f9f9f9;
    padding: 15px 20px;
    cursor: pointer;
    font-weight: 600;
    position: relative;
    transition: background-color 0.3s ease;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.faq-question:hover {
    background-color: #f0f0f0;
}

.faq-question:after {
    content: "+";
    font-size: 22px;
    line-height: 1;
    color: #777;
    transition: transform 0.3s ease;
}

.faq-question.active {
    background-color: #e9e9e9;
    border-bottom: 1px solid #ddd;
}


.faq-question.active:after {
    content: "−";
    transform: rotate(0deg);
}

.faq-answer {
    display: none;
    padding: 15px 20px;
    color: #333;
    line-height: 1.6;
    background-color: white;
}

.faq-answer p:first-child {
    margin-top: 0;
}

.faq-answer p:last-child {
    margin-bottom: 0;
}

.faq-answer.active {
    display: block;
}

.custom-faq-empty {
    text-align: center;
    padding: 20px;
    background-color: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 5px;
    color: #666;
}';
        $wp_filesystem->put_contents($frontend_css, $css_content, FS_CHMOD_FILE);
    }
    
    // Block Editor CSS
    $block_css = $dir . 'assets/css/block-editor.css';
    if (!$wp_filesystem->exists($block_css)) {
        $css_content = '/**
 * Custom FAQ Manager - Block Editor Styles
 */

.faq-block-inspector-panel {
    margin-bottom: 20px;
}

.faq-block-inspector-panel .components-base-control {
    margin-bottom: 15px;
}

.faq-block-placeholder {
    padding: 20px;
    background-color: #f9f9f9;
    border: 1px dashed #aaa;
    border-radius: 5px;
    text-align: center;
}

.faq-block-placeholder svg {
    width: 30px;
    height: 30px;
    margin-bottom: 10px;
    color: #555;
}

.faq-block-placeholder h3 {
    margin: 0 0 10px;
    color: #333;
}

.faq-block-placeholder p {
    margin: 0;
    color: #666;
}

.faq-block-preview {
    border: 1px solid #ddd;
    border-radius: 5px;
    overflow: hidden;
    margin-bottom: 10px;
}

.faq-block-preview-item {
    margin-bottom: 0;
    border-bottom: 1px solid #ddd;
}

.faq-block-preview-item:last-child {
    border-bottom: none;
}

.faq-block-preview-question {
    background-color: #f9f9f9;
    padding: 12px 15px;
    font-weight: 600;
    color: #333;
}

.faq-block-preview-answer {
    padding: 12px 15px;
    background-color: #fff;
    color: #333;
}

.faq-block-select-options {
    margin-top: 10px;
}

.faq-block-select-list {
    margin-top: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    max-height: 200px;
    overflow-y: auto;
}

.faq-block-select-item {
    padding: 8px 12px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
}

.faq-block-select-item:last-child {
    border-bottom: none;
}

.faq-block-select-item:hover {
    background-color: #f5f5f5;
}';
        $wp_filesystem->put_contents($block_css, $css_content, FS_CHMOD_FILE);
    }
}

/**
 * Create JS files for the plugin
 */
function create_js_files() {
    global $wp_filesystem;
    
    // Inizializza il file system di WordPress se non è già stato fatto
    if (!function_exists('WP_Filesystem')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    
    // Richiedi le credenziali se necessario
    if (!WP_Filesystem()) {
        return;
    }
    
    $dir = plugin_dir_path(__FILE__);
    
    // Assicurati che la directory JS esista
    $js_dir = $dir . 'assets/js/';
    if (!$wp_filesystem->is_dir($js_dir)) {
        $wp_filesystem->mkdir($js_dir, FS_CHMOD_DIR);
    }
    
    // Creazione del file fix-faq-answers.js
    $fix_answers_js = $dir . 'assets/js/fix-faq-answers.js';
    if (!$wp_filesystem->exists($fix_answers_js)) {
        $js_content = '/**
 * Script per correggere gli elementi FAQ con altezza fissa
 * Controlla elementi .faq-answer con stile inline e rimuove il max-height fisso
 */
document.addEventListener(\'DOMContentLoaded\', function() {
    // Trova tutte le risposte FAQ con max-height inline
    const faqAnswers = document.querySelectorAll(\'.faq-answer[style*="max-height"]\');
    
    if (faqAnswers.length) {
        faqAnswers.forEach(function(answer) {
            // Rimuovi lo stile inline di max-height
            answer.style.maxHeight = \'\';
            
            // Se non ha già la classe \'active\', aggiungi temporaneamente la classe e poi rimuovila
            // per permettere all\'animazione di ripristinarsi correttamente
            if (answer.classList.contains(\'active\')) {
                // Già attivo, rimuovi e riaggiungi la classe per applicare le nuove regole CSS
                answer.classList.remove(\'active\');
                // Forza un reflow
                answer.offsetHeight;
                // Riaggiungi la classe active
                answer.classList.add(\'active\');
            }
        });
    }
    
    // Correggi anche il caso specifico menzionato
    const specificAnswer = document.querySelector(\'.faq-answer.active[style*="max-height: 90px"]\');
    if (specificAnswer) {
        specificAnswer.style.maxHeight = \'\';
        // Riapplica l\'animazione
        specificAnswer.classList.remove(\'active\');
        specificAnswer.offsetHeight; // Forza un reflow
        specificAnswer.classList.add(\'active\');
    }
});';
        $wp_filesystem->put_contents($fix_answers_js, $js_content, FS_CHMOD_FILE);
    }
    
    // Admin JS
    $admin_js = $dir . 'assets/js/admin.js';
    if (!$wp_filesystem->exists($admin_js)) {
        $js_content = '/**
 * Custom FAQ Manager - Admin Scripts
 */
jQuery(document).ready(function($) {
    // Codice JavaScript admin...
});';
        $wp_filesystem->put_contents($admin_js, $js_content, FS_CHMOD_FILE);
    }
    
    // Frontend JS
    $frontend_js = $dir . 'assets/js/frontend.js';
    if (!$wp_filesystem->exists($frontend_js)) {
        $js_content = '/**
 * Custom FAQ Manager - Frontend Scripts
 */
document.addEventListener("DOMContentLoaded", function() {
    // Codice JavaScript frontend...
});';
        $wp_filesystem->put_contents($frontend_js, $js_content, FS_CHMOD_FILE);
    }
    
    // Block JS
    $block_js = $dir . 'assets/js/block.js';
    if (!$wp_filesystem->exists($block_js)) {
        $js_content = '/**
 * Custom FAQ Manager - Gutenberg Block
 */
(function(blocks, editor, components, i18n, element) {
    // Codice JavaScript del blocco Gutenberg...
})();';
        $wp_filesystem->put_contents($block_js, $js_content, FS_CHMOD_FILE);
    }
} 