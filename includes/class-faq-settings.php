<?php

/**
 * FAQ Settings
 */
class FAQ_Settings {

    /**
     * Save FAQ Settings
     */
    public function save_faq_settings() {
        // Verifica nonce per la sicurezza
        if (!isset($_POST['custom_faq_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['custom_faq_nonce'])), 'custom_faq_settings')) {
            wp_die(esc_html__('Security check failed', 'FAQ-Manager-Easy-WordPress'));
        }
        
        // Verifica autorizzazioni
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page', 'FAQ-Manager-Easy-WordPress'));
        }
        
        // Inizializza l'array delle impostazioni
        $settings = array();
        
        // Sanitizza e salva i colori
        $settings['question_bg_color'] = $this->sanitize_custom_color(isset($_POST['question_bg_color']) ? sanitize_text_field(wp_unslash($_POST['question_bg_color'])) : '#f9f9f9');
        $settings['question_text_color'] = $this->sanitize_custom_color(isset($_POST['question_text_color']) ? sanitize_text_field(wp_unslash($_POST['question_text_color'])) : '#333333');
        $settings['answer_bg_color'] = $this->sanitize_custom_color(isset($_POST['answer_bg_color']) ? sanitize_text_field(wp_unslash($_POST['answer_bg_color'])) : '#ffffff');
        $settings['answer_text_color'] = $this->sanitize_custom_color(isset($_POST['answer_text_color']) ? sanitize_text_field(wp_unslash($_POST['answer_text_color'])) : '#333333');
        $settings['border_color'] = $this->sanitize_custom_color(isset($_POST['border_color']) ? sanitize_text_field(wp_unslash($_POST['border_color'])) : '#dddddd');
        $settings['active_question_bg_color'] = $this->sanitize_custom_color(isset($_POST['active_question_bg_color']) ? sanitize_text_field(wp_unslash($_POST['active_question_bg_color'])) : '#e9e9e9');
        $settings['hover_bg_color'] = $this->sanitize_custom_color(isset($_POST['hover_bg_color']) ? sanitize_text_field(wp_unslash($_POST['hover_bg_color'])) : '#f0f0f0');
        
        // Sanitizza l'altezza massima delle risposte
        $settings['answer_max_height'] = isset($_POST['answer_max_height']) ? 
                                        absint(wp_unslash($_POST['answer_max_height'])) : 90;
        
        // Aggiorna le opzioni nel database
        update_option('ceafm_settings', $settings);
        
        // Reindirizza alla pagina delle impostazioni con un messaggio di conferma
        wp_safe_redirect(add_query_arg('updated', 'true', wp_get_referer()));
        exit;
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
     * Display FAQ Settings Page
     */
    public function display_faq_settings() {
        // Verifica il nonce per le operazioni GET
        $is_nonce_verified = isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_key($_GET['_wpnonce']), 'faq_settings_nonce');
        
        // Recupera le impostazioni salvate
        $settings = get_option('ceafm_settings', array());
        
        // Valori di default
        $defaults = array(
            'question_bg_color' => '#f9f9f9',
            'question_text_color' => '#333333',
            'answer_bg_color' => '#ffffff',
            'answer_text_color' => '#333333',
            'border_color' => '#dddddd',
            'active_question_bg_color' => '#e9e9e9',
            'hover_bg_color' => '#f0f0f0',
            'answer_max_height' => 90
        );
        
        // Unisce le impostazioni salvate con i valori di default
        $settings = wp_parse_args($settings, $defaults);
        
        // Visualizza il messaggio di aggiornamento
        if (isset($_GET['updated']) && $_GET['updated'] === 'true' && $is_nonce_verified) {
            echo '<div class="notice notice-success is-dismissible"><p>';
            echo esc_html__('Settings saved successfully.', 'FAQ-Manager-Easy-WordPress');
            echo '</p></div>';
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('FAQ Manager Settings', 'FAQ-Manager-Easy-WordPress'); ?></h1>
            
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="save_faq_settings">
                <?php wp_nonce_field('custom_faq_settings', 'custom_faq_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php echo esc_html__('Question Background Color', 'FAQ-Manager-Easy-WordPress'); ?></th>
                        <td>
                            <input type="text" name="question_bg_color" value="<?php echo esc_attr($settings['question_bg_color']); ?>" class="color-picker" data-default-color="<?php echo esc_attr($defaults['question_bg_color']); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Question Text Color', 'FAQ-Manager-Easy-WordPress'); ?></th>
                        <td>
                            <input type="text" name="question_text_color" value="<?php echo esc_attr($settings['question_text_color']); ?>" class="color-picker" data-default-color="<?php echo esc_attr($defaults['question_text_color']); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Answer Background Color', 'FAQ-Manager-Easy-WordPress'); ?></th>
                        <td>
                            <input type="text" name="answer_bg_color" value="<?php echo esc_attr($settings['answer_bg_color']); ?>" class="color-picker" data-default-color="<?php echo esc_attr($defaults['answer_bg_color']); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Answer Text Color', 'FAQ-Manager-Easy-WordPress'); ?></th>
                        <td>
                            <input type="text" name="answer_text_color" value="<?php echo esc_attr($settings['answer_text_color']); ?>" class="color-picker" data-default-color="<?php echo esc_attr($defaults['answer_text_color']); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Border Color', 'FAQ-Manager-Easy-WordPress'); ?></th>
                        <td>
                            <input type="text" name="border_color" value="<?php echo esc_attr($settings['border_color']); ?>" class="color-picker" data-default-color="<?php echo esc_attr($defaults['border_color']); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Active Question Background Color', 'FAQ-Manager-Easy-WordPress'); ?></th>
                        <td>
                            <input type="text" name="active_question_bg_color" value="<?php echo esc_attr($settings['active_question_bg_color']); ?>" class="color-picker" data-default-color="<?php echo esc_attr($defaults['active_question_bg_color']); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Hover Background Color', 'FAQ-Manager-Easy-WordPress'); ?></th>
                        <td>
                            <input type="text" name="hover_bg_color" value="<?php echo esc_attr($settings['hover_bg_color']); ?>" class="color-picker" data-default-color="<?php echo esc_attr($defaults['hover_bg_color']); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Max Height for Answers (vh)', 'FAQ-Manager-Easy-WordPress'); ?></th>
                        <td>
                            <input type="number" name="answer_max_height" value="<?php echo esc_attr($settings['answer_max_height']); ?>" min="10" max="100" step="1" />
                            <p class="description"><?php echo esc_html__('Maximum height for answers as percentage of viewport height. Set to 100 for no limit.', 'FAQ-Manager-Easy-WordPress'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo esc_attr__('Save Changes', 'FAQ-Manager-Easy-WordPress'); ?>">
                </p>
            </form>

            <div class="faq-settings-preview">
                <h2><?php echo esc_html__('Preview', 'FAQ-Manager-Easy-WordPress'); ?></h2>
                <div class="faq-preview-container" style="max-width: 600px;">
                    <div class="faq-item" style="border: 1px solid <?php echo esc_attr($settings['border_color']); ?>; margin-bottom: 10px;">
                        <div class="faq-question" style="background-color: <?php echo esc_attr($settings['question_bg_color']); ?>; color: <?php echo esc_attr($settings['question_text_color']); ?>; padding: 10px; cursor: pointer;">
                            <?php echo esc_html__('Sample FAQ Question', 'FAQ-Manager-Easy-WordPress'); ?>
                        </div>
                        <div class="faq-answer" style="background-color: <?php echo esc_attr($settings['answer_bg_color']); ?>; color: <?php echo esc_attr($settings['answer_text_color']); ?>; padding: 10px; border-top: 1px solid <?php echo esc_attr($settings['border_color']); ?>;">
                            <?php echo esc_html__('This is a sample answer. It demonstrates how your FAQ answers will appear with the current color settings.', 'FAQ-Manager-Easy-WordPress'); ?>
                        </div>
                    </div>
                    <div class="faq-item" style="border: 1px solid <?php echo esc_attr($settings['border_color']); ?>;">
                        <div class="faq-question" style="background-color: <?php echo esc_attr($settings['active_question_bg_color']); ?>; color: <?php echo esc_attr($settings['question_text_color']); ?>; padding: 10px; cursor: pointer;">
                            <?php echo esc_html__('Active FAQ Question (when open)', 'FAQ-Manager-Easy-WordPress'); ?>
                        </div>
                        <div class="faq-answer" style="background-color: <?php echo esc_attr($settings['answer_bg_color']); ?>; color: <?php echo esc_attr($settings['answer_text_color']); ?>; padding: 10px; border-top: 1px solid <?php echo esc_attr($settings['border_color']); ?>;">
                            <?php echo esc_html__('This shows how an active (open) FAQ item will appear.', 'FAQ-Manager-Easy-WordPress'); ?>
                        </div>
                    </div>
                </div>
                <p class="description"><?php echo esc_html__('Note: This preview is static. The actual FAQ items will be interactive.', 'FAQ-Manager-Easy-WordPress'); ?></p>
            </div>

            <div class="faq-settings-help">
                <h2><?php echo esc_html__('Shortcode Usage', 'FAQ-Manager-Easy-WordPress'); ?></h2>
                <p><?php echo esc_html__('Use the following shortcode to display your FAQs on any page or post:', 'FAQ-Manager-Easy-WordPress'); ?></p>
                <code>[custom_faq]</code>
                
                <h3><?php echo esc_html__('Shortcode Parameters', 'FAQ-Manager-Easy-WordPress'); ?></h3>
                <ul>
                    <li><code>category</code> - <?php echo esc_html__('Display FAQs from a specific category (use category slug)', 'FAQ-Manager-Easy-WordPress'); ?></li>
                    <li><code>limit</code> - <?php echo esc_html__('Limit the number of FAQs displayed', 'FAQ-Manager-Easy-WordPress'); ?></li>
                    <li><code>orderby</code> - <?php echo esc_html__('Order FAQs by: "title", "date", "menu_order"', 'FAQ-Manager-Easy-WordPress'); ?></li>
                    <li><code>order</code> - <?php echo esc_html__('Sort order: "ASC" or "DESC"', 'FAQ-Manager-Easy-WordPress'); ?></li>
                </ul>
                
                <h3><?php echo esc_html__('Examples', 'FAQ-Manager-Easy-WordPress'); ?></h3>
                <p><code>[custom_faq category="general" limit="5" orderby="title" order="ASC"]</code></p>
            </div>
        </div>
        <?php
    }
} 