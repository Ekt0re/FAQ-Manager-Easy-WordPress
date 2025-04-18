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
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

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
            'add_new'            => _x('Aggiungi Nuova', 'faq', 'custom-faq-manager'),
            'add_new_item'       => __('Aggiungi Nuova FAQ', 'custom-faq-manager'),
            'new_item'           => __('Nuova FAQ', 'custom-faq-manager'),
            'edit_item'          => __('Modifica FAQ', 'custom-faq-manager'),
            'view_item'          => __('Visualizza FAQ', 'custom-faq-manager'),
            'all_items'          => __('Tutte le FAQ', 'custom-faq-manager'),
            'search_items'       => __('Cerca FAQ', 'custom-faq-manager'),
            'not_found'          => __('Nessuna FAQ trovata.', 'custom-faq-manager'),
            'not_found_in_trash' => __('Nessuna FAQ trovata nel cestino.', 'custom-faq-manager')
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
            __('Gestione FAQ', 'custom-faq-manager'),
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
        wp_register_style('custom-faq-manager-frontend', plugins_url('assets/css/frontend.css', __FILE__));
        wp_register_script('custom-faq-manager-frontend', plugins_url('assets/js/frontend.js', __FILE__), array('jquery'), null, true);
        wp_register_script('custom-faq-fix-answers', plugins_url('assets/js/fix-faq-answers.js', __FILE__), array(), null, true);
        
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
            echo "<script>
                window.customFaqSettings = {$settings_json};
            </script>";
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
            <h1><?php _e('Gestione FAQ', 'custom-faq-manager'); ?></h1>
            
            <div class="faq-container">
                <div class="faq-list-container">
                    <h2><?php _e('Lista Elementi FAQ', 'custom-faq-manager'); ?></h2>
                    
                    <div class="faq-toolbar">
                        <div class="faq-search">
                            <input type="text" id="faq-search" placeholder="<?php _e('Cerca', 'custom-faq-manager'); ?>">
                            <button id="search-btn" class="button"><span class="dashicons dashicons-search"></span></button>
                        </div>
                        <button id="new-faq-btn" class="button button-primary"><?php _e('Nuovo', 'custom-faq-manager'); ?></button>
                    </div>
                    
                    <div class="faq-list">
                        <table class="widefat">
                            <thead>
                                <tr>
                                    <th><?php _e('Nome', 'custom-faq-manager'); ?></th>
                                    <th><?php _e('Azioni', 'custom-faq-manager'); ?></th>
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
                                                <button class="edit-faq button" data-id="<?php echo esc_attr($faq->ID); ?>"><?php _e('Modifica', 'custom-faq-manager'); ?></button>
                                                <button class="delete-faq button" data-id="<?php echo esc_attr($faq->ID); ?>"><?php _e('Elimina', 'custom-faq-manager'); ?></button>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                } else {
                                    ?>
                                    <tr class="no-items">
                                        <td colspan="2"><?php _e('Nessuna FAQ trovata.', 'custom-faq-manager'); ?></td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="faq-editor" id="faq-editor">
                    <h2 id="editor-title"><?php _e('Aggiungi Nuova FAQ', 'custom-faq-manager'); ?></h2>
                    
                    <form id="faq-form">
                        <input type="hidden" id="faq-id" value="">
                        
                        <div class="form-field">
                            <label for="faq-title"><?php _e('Domanda', 'custom-faq-manager'); ?></label>
                            <input type="text" id="faq-title" name="faq-title" required>
                        </div>
                        
                        <div class="form-field">
                            <label for="faq-content"><?php _e('Risposta', 'custom-faq-manager'); ?></label>
                            <?php
                            wp_editor('', 'faq-content', array(
                                'media_buttons' => true,
                                'textarea_rows' => 10,
                                'teeny'         => false
                            ));
                            ?>
                        </div>
                        
                        <div class="form-field">
                            <label><?php _e('Stile Custom', 'custom-faq-manager'); ?></label>
                            <textarea id="faq-css" name="faq-css" placeholder="<?php _e('CSS personalizzato per questa FAQ', 'custom-faq-manager'); ?>"></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="button button-primary"><?php _e('Salva FAQ', 'custom-faq-manager'); ?></button>
                            <button type="button" id="cancel-edit" class="button"><?php _e('Annulla', 'custom-faq-manager'); ?></button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="faq-settings-container">
                <h2><?php _e('Personalizzazione Grafica', 'custom-faq-manager'); ?></h2>
                <form id="faq-settings-form">
                    <div class="settings-actions" style="margin-bottom: 15px;">
                        <button type="button" id="import-theme-colors" class="button"><?php _e('Importa dal Tema', 'custom-faq-manager'); ?></button>
                        <span class="settings-info" style="margin-left: 10px; font-style: italic;"><?php _e('Importa i colori dal tema attivo', 'custom-faq-manager'); ?></span>
                    </div>
                    
                    <div class="settings-grid">
                        <div class="form-field">
                            <label for="question_bg_color"><?php _e('Colore sfondo domanda', 'custom-faq-manager'); ?></label>
                            <input type="color" id="question_bg_color" name="question_bg_color" value="<?php echo esc_attr($settings['question_bg_color']); ?>">
                        </div>
                        
                        <div class="form-field">
                            <label for="question_text_color"><?php _e('Colore testo domanda', 'custom-faq-manager'); ?></label>
                            <input type="color" id="question_text_color" name="question_text_color" value="<?php echo esc_attr($settings['question_text_color']); ?>">
                        </div>
                        
                        <div class="form-field">
                            <label for="answer_bg_color"><?php _e('Colore sfondo risposta', 'custom-faq-manager'); ?></label>
                            <input type="color" id="answer_bg_color" name="answer_bg_color" value="<?php echo esc_attr($settings['answer_bg_color']); ?>">
                        </div>
                        
                        <div class="form-field">
                            <label for="answer_text_color"><?php _e('Colore testo risposta', 'custom-faq-manager'); ?></label>
                            <input type="color" id="answer_text_color" name="answer_text_color" value="<?php echo esc_attr($settings['answer_text_color']); ?>">
                        </div>
                        
                        <div class="form-field">
                            <label for="border_color"><?php _e('Colore bordo', 'custom-faq-manager'); ?></label>
                            <input type="color" id="border_color" name="border_color" value="<?php echo esc_attr($settings['border_color']); ?>">
                        </div>
                        
                        <div class="form-field">
                            <label for="active_question_bg_color"><?php _e('Colore sfondo domanda attiva', 'custom-faq-manager'); ?></label>
                            <input type="color" id="active_question_bg_color" name="active_question_bg_color" value="<?php echo esc_attr($settings['active_question_bg_color']); ?>">
                        </div>
                        
                        <div class="form-field">
                            <label for="hover_bg_color"><?php _e('Colore sfondo hover', 'custom-faq-manager'); ?></label>
                            <input type="color" id="hover_bg_color" name="hover_bg_color" value="<?php echo esc_attr($settings['hover_bg_color']); ?>">
                        </div>
                        
                        <div class="form-field">
                            <label for="answer_max_height"><?php _e('Altezza massima risposta chiusa (px)', 'custom-faq-manager'); ?></label>
                            <input type="number" id="answer_max_height" name="answer_max_height" min="0" max="500" value="<?php echo esc_attr($settings['answer_max_height']); ?>">
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="button button-primary"><?php _e('Salva Impostazioni', 'custom-faq-manager'); ?></button>
                        <div id="settings-status" style="display: none; margin-left: 10px; padding: 5px 10px; background-color: #dff0d8; color: #3c763d; border-radius: 3px;">
                            <?php _e('Modifiche salvate con successo!', 'custom-faq-manager'); ?>
                        </div>
                    </div>
                </form>
                
                <div class="settings-preview" style="margin-top: 20px; border: 1px solid #ddd; border-radius: 5px; padding: 15px; background: #f9f9f9;">
                    <h3><?php _e('Anteprima in tempo reale', 'custom-faq-manager'); ?> <span class="settings-info"><?php _e('(clicca sulla domanda per aprire/chiudere)', 'custom-faq-manager'); ?></span></h3>
                    <div id="faq-preview-item" class="faq-item" style="margin-bottom: 0;">
                        <div id="faq-preview-question" class="faq-question">
                            <?php _e('Esempio di domanda', 'custom-faq-manager'); ?>
                        </div>
                        <div id="faq-preview-answer" class="faq-answer">
                            <p><?php _e('Questo è un esempio di risposta. Le modifiche ai colori verranno mostrate in tempo reale qui.', 'custom-faq-manager'); ?></p>
                            <p><?php _e('Puoi verificare anche come appare il testo su più righe e controllare l\'altezza massima impostata.', 'custom-faq-manager'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="faq-shortcode-info">
                <h3><?php _e('Shortcode', 'custom-faq-manager'); ?></h3>
                <p><?php _e('Usa questo shortcode per visualizzare le FAQ sul tuo sito:', 'custom-faq-manager'); ?></p>
                <code>[custom_faq]</code>
                
                <h4><?php _e('Opzioni Shortcode', 'custom-faq-manager'); ?></h4>
                <ul>
                    <li><code>limit</code>: <?php _e('Numero di FAQ da mostrare (default: tutte)', 'custom-faq-manager'); ?></li>
                    <li><code>orderby</code>: <?php _e('Ordinamento (title, date - default: title)', 'custom-faq-manager'); ?></li>
                    <li><code>order</code>: <?php _e('Direzione ordinamento (ASC, DESC - default: ASC)', 'custom-faq-manager'); ?></li>
                    <li><code>selected_faqs</code>: <?php _e('IDs delle FAQ specifiche da mostrare (es. "1,4,7")', 'custom-faq-manager'); ?></li>
                </ul>
                <p><?php _e('Esempio:', 'custom-faq-manager'); ?> <code>[custom_faq limit="5" orderby="date" order="DESC"]</code></p>
                
                <h3><?php _e('Blocco Gutenberg', 'custom-faq-manager'); ?></h3>
                <p><?php _e('Puoi anche aggiungere le FAQ utilizzando il blocco Gutenberg "FAQ". Cercalo nell\'editor di WordPress.', 'custom-faq-manager'); ?></p>
            </div>
            
            <div class="faq-author-info">
                <h3><?php _e('Informazioni sul Plugin', 'custom-faq-manager'); ?></h3>
                <p>
                    <strong>Custom Easy FAQ Manager</strong> v1.1<br>
                    <?php _e('Sviluppato da', 'custom-faq-manager'); ?> <a href="https://www.instagram.com/ettore_sartori/" target="_blank">Ettore Sartori</a><br>
                    <a href="https://github.com/Ekt0re/FAQ-Manager-Easy-WordPress" target="_blank"><?php _e('Visita il repository GitHub', 'custom-faq-manager'); ?></a><br>
                    <?php _e('Licenza', 'custom-faq-manager'); ?>: <a href="https://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GPLv3</a>
                </p>
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
        check_ajax_referer('custom-faq-nonce', 'nonce');
        
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        
        $args = array(
            'post_type'      => 'custom_faq',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC'
        );
        
        if (!empty($search)) {
            $args['s'] = $search;
        }
        
        $faqs = get_posts($args);
        
        ob_start();
        
        if ($faqs) {
            foreach ($faqs as $faq) {
                ?>
                <tr data-id="<?php echo esc_attr($faq->ID); ?>">
                    <td class="faq-title"><?php echo esc_html($faq->post_title); ?></td>
                    <td class="faq-actions">
                        <button class="edit-faq button" data-id="<?php echo esc_attr($faq->ID); ?>"><?php _e('Modifica', 'custom-faq-manager'); ?></button>
                        <button class="delete-faq button" data-id="<?php echo esc_attr($faq->ID); ?>"><?php _e('Elimina', 'custom-faq-manager'); ?></button>
                    </td>
                </tr>
                <?php
            }
        } else {
            ?>
            <tr class="no-items">
                <td colspan="2"><?php _e('Nessuna FAQ trovata.', 'custom-faq-manager'); ?></td>
            </tr>
            <?php
        }
        
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
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
            return '<p>' . __('Nessuna FAQ disponibile.', 'custom-faq-manager') . '</p>';
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
            echo '<div class="faq-answer">' . apply_filters('the_content', $faq->post_content) . '</div>';
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
            filemtime(plugin_dir_path(__FILE__) . 'assets/js/block.js')
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
        // Abilita error reporting per il debug
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('DEBUG: save_faq_settings chiamato');
            error_log('POST data: ' . print_r($_POST, true));
        }
        
        // Verifica nonce
        check_ajax_referer('custom-faq-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Non hai i permessi necessari.', 'custom-faq-manager')));
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
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("ERROR: Campo mancante: $field");
                }
                $settings[$field] = '';
            } else {
                // Sanitizza i campi colore
                if ($field === 'answer_max_height') {
                    $settings[$field] = intval($_POST[$field]);
                } else {
                    $color = $this->sanitize_custom_color($_POST[$field]);
                    // Se il colore non è valido, usa un valore predefinito
                    $settings[$field] = !empty($color) ? $color : '#ffffff';
                }
            }
        }
        
        // Salva le impostazioni anche se ci sono errori (con valori predefiniti)
        update_option('custom_faq_settings', $settings);
        
        if ($has_errors) {
            wp_send_json_error(array('message' => __('Alcuni campi non sono validi. Sono stati usati valori predefiniti.', 'custom-faq-manager')));
        } else {
            wp_send_json_success(array('message' => __('Impostazioni salvate con successo.', 'custom-faq-manager')));
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
    // Crea le directory necessarie
    $dir = plugin_dir_path(__FILE__);
    
    if (!file_exists($dir . 'assets')) {
        mkdir($dir . 'assets', 0755, true);
    }
    
    if (!file_exists($dir . 'assets/css')) {
        mkdir($dir . 'assets/css', 0755, true);
    }
    
    if (!file_exists($dir . 'assets/js')) {
        mkdir($dir . 'assets/js', 0755, true);
    }
    
    if (!file_exists($dir . 'templates')) {
        mkdir($dir . 'templates', 0755, true);
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
    $dir = plugin_dir_path(__FILE__);
    
    // Admin CSS
    $admin_css = $dir . 'assets/css/admin.css';
    if (!file_exists($admin_css)) {
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
        file_put_contents($admin_css, $css_content);
    }
    
    // Frontend CSS
    $frontend_css = $dir . 'assets/css/frontend.css';
    if (!file_exists($frontend_css)) {
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
    padding: 0;
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.5s ease, padding 0.5s ease;
}

.faq-answer.active {
    padding: 20px;
    max-height: 1000px; /* Arbitrary large value */
}

.faq-answer p:first-child {
    margin-top: 0;
}



.faq-answer p:last-child {
    margin-bottom: 0;
}

/* Responsive adjustments */
@media screen and (max-width: 768px) {
    .faq-question {
        padding: 12px 15px;
        font-size: 0.95rem;
    }
    
    .faq-answer.active {
        padding: 15px;
    }
}';
        file_put_contents($frontend_css, $css_content);
    }
    
    // Block editor CSS
    $block_editor_css = $dir . 'assets/css/block-editor.css';
    if (!file_exists($block_editor_css)) {
        $css_content = '/**
 * Custom FAQ Manager - Block Editor Styles
 */

.wp-block-custom-faq-manager-faq-block {
    padding: 1em;
    background: #f8f8f8;
    border: 1px dashed #ccc;
    border-radius: 5px;
}

.faq-block-title {
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
    font-size: 1.3em;
    font-weight: bold;
    color: #23282d;
}

.faq-block-description {
    margin-bottom: 15px;
    font-size: 0.9em;
    color: #666;
}

.faq-block-options {
    background: #ffffff;
    padding: 15px;
    border: 1px solid #e0e0e0;
    border-radius: 5px;
    margin-bottom: 15px;
}

.faq-block-option {
    display: flex;
    margin-bottom: 10px;
    align-items: center;
}

.faq-block-option label {
    flex: 0 0 120px;
    font-weight: 500;
}

.faq-block-option .components-select-control__input,
.faq-block-option .components-range-control__number {
    width: 100%;
}

.faq-preview-notice {
    font-style: italic;
    color: #666;
    font-size: 0.9em;
    margin-top: 10px;
}';
        file_put_contents($block_editor_css, $css_content);
    }
}

/**
 * Create JS files for the plugin
 */
function create_js_files() {
    $dir = plugin_dir_path(__FILE__);
    
    // Crea la cartella se non esiste
    if (!file_exists($dir . 'assets/js/')) {
        mkdir($dir . 'assets/js/', 0755, true);
    }
    
    // Creazione del file fix-faq-answers.js
    $fix_answers_js = $dir . 'assets/js/fix-faq-answers.js';
    if (!file_exists($fix_answers_js)) {
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
        file_put_contents($fix_answers_js, $js_content);
    }
    
    // Admin JS
    $admin_js = $dir . 'assets/js/admin.js';
    if (!file_exists($admin_js)) {
        $js_content = '/**
 * Custom FAQ Manager - Admin Scripts
 */
jQuery(document).ready(function($) {
    // Edit FAQ
    $(document).on("click", ".edit-faq", function() {
        var id = $(this).data("id");
        
        // Show loading indicator
        $(this).text("Loading...").attr("disabled", true);
        
        // Get FAQ data
        $.post(custom_faq_ajax.ajax_url, {
            action: "get_faq",
            id: id,
            nonce: custom_faq_ajax.nonce
        }, function(response) {
            if (response.success) {
                var data = response.data;
                
                // Populate form
                $("#faq-id").val(data.id);
                $("#faq-title").val(data.title);
                
                // Set content in editor
                if (typeof tinyMCE !== "undefined" && tinyMCE.get("faq-content")) {
                    tinyMCE.get("faq-content").setContent(data.content);
                } else {
                    $("#faq-content").val(data.content);
                }
                
                $("#faq-css").val(data.css);
                
                // Update editor title
                $("#editor-title").text("Modifica FAQ");
                
                // Scroll to editor
                $("html, body").animate({
                    scrollTop: $("#faq-editor").offset().top - 50
                }, 500);
            } else {
                alert(response.data.message);
            }
            
            // Reset button
            $(".edit-faq[data-id=\"" + id + "\"]").text("Modifica").attr("disabled", false);
        }).fail(function() {
            alert("Si è verificato un errore. Riprova.");
            $(".edit-faq[data-id=\"" + id + "\"]").text("Modifica").attr("disabled", false);
        });
    });
    
    // New FAQ button
    $("#new-faq-btn").click(function() {
        // Reset form
        $("#faq-id").val("");
        $("#faq-title").val("");
        
        // Clear editor
        if (typeof tinyMCE !== "undefined" && tinyMCE.get("faq-content")) {
            tinyMCE.get("faq-content").setContent("");
        } else {
            $("#faq-content").val("");
        }
        
        $("#faq-css").val("");
        
        // Update editor title
        $("#editor-title").text("Aggiungi Nuova FAQ");
        
        // Scroll to editor
        $("html, body").animate({
            scrollTop: $("#faq-editor").offset().top - 50
        }, 500);
    });
    
    // Cancel edit
    $("#cancel-edit").click(function(e) {
        e.preventDefault();
        
        // Reset form
        $("#faq-id").val("");
        $("#faq-title").val("");
        
        // Clear editor
        if (typeof tinyMCE !== "undefined" && tinyMCE.get("faq-content")) {
            tinyMCE.get("faq-content").setContent("");
        } else {
            $("#faq-content").val("");
        }
        
        $("#faq-css").val("");
        
        // Update editor title
        $("#editor-title").text("Aggiungi Nuova FAQ");
        
        // Scroll to list
        $("html, body").animate({
            scrollTop: $(".faq-list-container").offset().top - 50
        }, 500);
    });
    
    // Search FAQs
    $("#search-btn").click(function() {
        var search = $("#faq-search").val();
        
        $.post(custom_faq_ajax.ajax_url, {
            action: "search_faqs",
            search: search,
            nonce: custom_faq_ajax.nonce
        }, function(response) {
            if (response.success) {
                $("#faq-list-body").html(response.data.html);
            }
        });
    });
    
    // Search on Enter key
    $("#faq-search").keypress(function(e) {
        if (e.which === 13) {
            $("#search-btn").click();
            e.preventDefault();
        }
    });
    
    // Save FAQ
    $("#faq-form").submit(function(e) {
        e.preventDefault();
        
        var id = $("#faq-id").val();
        var title = $("#faq-title").val();
        var content = "";
        
        // Get content from editor
        if (typeof tinyMCE !== "undefined" && tinyMCE.get("faq-content")) {
            content = tinyMCE.get("faq-content").getContent();
        } else {
            content = $("#faq-content").val();
        }
        
        var css = $("#faq-css").val();
        
        // Validate
        if (!title.trim()) {
            alert("La domanda è obbligatoria.");
            return;
        }
        
        // Show loading
        var $submitBtn = $(this).find("button[type=\"submit\"]");
        var originalText = $submitBtn.text();
        $submitBtn.text("Salvataggio...").attr("disabled", true);
        
        $.post(custom_faq_ajax.ajax_url, {
            action: "save_faq",
            id: id,
            title: title,
            content: content,
            css: css,
            nonce: custom_faq_ajax.nonce
        }, function(response) {
            if (response.success) {
                // Refresh FAQ list
                $("#search-btn").click();
                
                // Show success message
                alert(response.data.message);
                
                // Reset form
                $("#faq-id").val("");
                $("#faq-title").val("");
                
                // Clear editor
                if (typeof tinyMCE !== "undefined" && tinyMCE.get("faq-content")) {
                    tinyMCE.get("faq-content").setContent("");
                } else {
                    $("#faq-content").val("");
                }
                
                $("#faq-css").val("");
                
                // Update editor title
                $("#editor-title").text("Aggiungi Nuova FAQ");
                
                // Scroll to list
                $("html, body").animate({
                    scrollTop: $(".faq-list-container").offset().top - 50
                }, 500);
            } else {
                alert(response.data.message);
            }
            
            // Reset button
            $submitBtn.text(originalText).attr("disabled", false);
        }).fail(function() {
            alert("Si è verificato un errore. Riprova.");
            $submitBtn.text(originalText).attr("disabled", false);
        });
    });
    
    // Delete FAQ
    $(document).on("click", ".delete-faq", function() {
        if (!confirm("Sei sicuro di voler eliminare questa FAQ?")) {
            return;
        }
        
        var id = $(this).data("id");
        var $row = $(this).closest("tr");
        
        // Show loading
        $(this).text("Eliminazione...").attr("disabled", true);
        
        $.post(custom_faq_ajax.ajax_url, {
            action: "delete_faq",
            id: id,
            nonce: custom_faq_ajax.nonce
        }, function(response) {
            if (response.success) {
                // Remove row
                $row.fadeOut(300, function() {
                    $(this).remove();
                    
                    // Check if list is empty
                    if ($("#faq-list-body tr").length === 0) {
                        $("#faq-list-body").html("<tr class=\"no-items\"><td colspan=\"2\">Nessuna FAQ trovata.</td></tr>");
                    }
                });
                
                // Show success message
                alert(response.data.message);
            } else {
                alert(response.data.message);
                $(".delete-faq[data-id=\"" + id + "\"]").text("Elimina").attr("disabled", false);
            }
        }).fail(function() {
            alert("Si è verificato un errore. Riprova.");
            $(".delete-faq[data-id=\"" + id + "\"]").text("Elimina").attr("disabled", false);
        });
    });
});';
        file_put_contents($admin_js, $js_content);
    }
    
    // Frontend JS
    $frontend_js = $dir . 'assets/js/frontend.js';
    if (!file_exists($frontend_js)) {
        $js_content = '/**
 * Custom FAQ Manager - Frontend Scripts
 */
jQuery(document).ready(function($) {
    // Toggle FAQ items
    $(".faq-question").on("click", function() {
        var $this = $(this);
        var $answer = $this.next(".faq-answer");
        var $faqItem = $this.closest(".faq-item");
        
        // Toggle active state
        $this.toggleClass("active");
        
        // Toggle answer visibility with animation
        if ($this.hasClass("active")) {
            $answer.addClass("active");
            
            // Optional: Close other open FAQs (accordion behavior)
            // Uncomment these lines to enable accordion behavior
            /*
            $faqItem.siblings(".faq-item").find(".faq-question.active").removeClass("active");
            $faqItem.siblings(".faq-item").find(".faq-answer.active").removeClass("active");
            */
        } else {
            $answer.removeClass("active");
        }
    });
    
    // Check for URL hash to open specific FAQ
    function checkHash() {
        var hash = window.location.hash;
        if (hash) {
            var $targetFaq = $(hash);
            if ($targetFaq.length && $targetFaq.hasClass("faq-item")) {
                // Open the FAQ
                var $question = $targetFaq.find(".faq-question");
                var $answer = $targetFaq.find(".faq-answer");
                
                if (!$question.hasClass("active")) {
                    $question.addClass("active");
                    $answer.addClass("active");
                }
                
                // Scroll to the FAQ with slight delay for smooth animation
                setTimeout(function() {
                    $("html, body").animate({
                        scrollTop: $targetFaq.offset().top - 100
                    }, 500);
                }, 100);
            }
        }
    }
    
    // Run on page load
    checkHash();
    
    // Run when hash changes (user clicks a link to an FAQ)
    $(window).on("hashchange", checkHash);
});';
        file_put_contents($frontend_js, $js_content);
    }
    
    // Block JS
    $block_js = $dir . 'assets/js/block.js';
    if (!file_exists($block_js)) {
        $js_content = '/**
 * Custom FAQ Manager - Gutenberg Block
 */
(function(blocks, editor, components, i18n, element) {
    var el = element.createElement;
    var __ = i18n.__;
    var InspectorControls = editor.InspectorControls;
    var PanelBody = components.PanelBody;
    var SelectControl = components.SelectControl;
    var RangeControl = components.RangeControl;
    var ServerSideRender = wp.serverSideRender;
    
    // Register the block
    blocks.registerBlockType("custom-faq-manager/faq-block", {
        title: __("FAQ", "custom-faq-manager"),
        icon: "format-status",
        category: "widgets",
        keywords: [
            __("faq", "custom-faq-manager"),
            __("domande", "custom-faq-manager"),
            __("frequent", "custom-faq-manager")
        ],
        
        // Block attributes
        attributes: {
            limit: {
                type: "number",
                default: -1
            },
            orderby: {
                type: "string",
                default: "title"
            },
            order: {
                type: "string",
                default: "ASC"
            },
            selectedFaqs: {
                type: "array",
                default: []
            }
        },
        
        // Editor component
        edit: function(props) {
            var attributes = props.attributes;
            
            // Inspector controls for block options
            var inspectorControls = el(
                InspectorControls,
                { key: "inspector" },
                el(
                    PanelBody,
                    {
                        title: __("Impostazioni FAQ", "custom-faq-manager"),
                        initialOpen: true
                    },
                    el(
                        RangeControl,
                        {
                            label: __("Numero di FAQ da mostrare", "custom-faq-manager"),
                            value: attributes.limit,
                            onChange: function(value) {
                                props.setAttributes({ limit: value === undefined ? -1 : value });
                            },
                            min: -1,
                            max: 50,
                            help: __("-1 per mostrare tutte le FAQ", "custom-faq-manager")
                        }
                    ),
                    el(
                        SelectControl,
                        {
                            label: __("Ordinamento", "custom-faq-manager"),
                            value: attributes.orderby,
                            options: [
                                { label: __("Titolo", "custom-faq-manager"), value: "title" },
                                { label: __("Data", "custom-faq-manager"), value: "date" }
                            ],
                            onChange: function(value) {
                                props.setAttributes({ orderby: value });
                            }
                        }
                    ),
                    el(
                        SelectControl,
                        {
                            label: __("Direzione ordinamento", "custom-faq-manager"),
                            value: attributes.order,
                            options: [
                                { label: __("Ascendente (A-Z)", "custom-faq-manager"), value: "ASC" },
                                { label: __("Discendente (Z-A)", "custom-faq-manager"), value: "DESC" }
                            ],
                            onChange: function(value) {
                                props.setAttributes({ order: value });
                            }
                        }
                    )
                )
            );
            
            // Block content in editor
            return [
                inspectorControls,
                el(
                    "div",
                    { className: props.className },
                    el(
                        "div",
                        { className: "faq-block-title" },
                        __("Blocco FAQ", "custom-faq-manager")
                    ),
                    el(
                        "div",
                        { className: "faq-block-description" },
                        __("Questo blocco visualizzerà le tue FAQ. Usa i controlli nella barra laterale per personalizzare la visualizzazione.", "custom-faq-manager")
                    ),
                    el(
                        ServerSideRender,
                        {
                            block: "custom-faq-manager/faq-block",
                            attributes: attributes
                        }
                    ),
                    el(
                        "div",
                        { className: "faq-preview-notice" },
                        __("Nota: l\'anteprima potrebbe apparire diversa nel frontend.", "custom-faq-manager")
                    )
                )
            ];
        },
        
        // Save function returns null because this is a dynamic block
        save: function() {
            return null;
        }
    });
})(
    window.wp.blocks,
    window.wp.blockEditor,
    window.wp.components,
    window.wp.i18n,
    window.wp.element
);';
        file_put_contents($block_js, $js_content);
    }
} 