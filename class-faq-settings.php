<?php

/**
 * FAQ Settings
 */
class FAQ_Settings {

    /**
     * Save FAQ Settings
     */
    public function save_faq_settings() {
        // Verifica nonce
        if (!isset($_POST['custom_faq_nonce']) || !wp_verify_nonce(sanitize_key(wp_unslash($_POST['custom_faq_nonce'])), 'custom_faq_settings')) {
            wp_die(esc_html__('Verifica di sicurezza fallita. Riprova.', 'custom-faq-manager'));
        }
        
        // Verifica i permessi
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Non hai i permessi necessari per modificare queste impostazioni.', 'custom-faq-manager'));
        }
        
        // Inizializza array delle impostazioni
        $settings = array();
        
        // Sanitizza e salva i colori
        $color_fields = array(
            'question_bg_color',
            'question_text_color',
            'answer_bg_color',
            'answer_text_color',
            'border_color',
            'active_question_bg_color',
            'hover_bg_color'
        );
        
        foreach ($color_fields as $field) {
            if (isset($_POST[$field])) {
                $settings[$field] = sanitize_hex_color(wp_unslash($_POST[$field]));
            }
        }
        
        // Sanitizza e salva l'altezza massima
        if (isset($_POST['answer_max_height'])) {
            $settings['answer_max_height'] = intval(wp_unslash($_POST['answer_max_height']));
        }
        
        // Salva le impostazioni
        update_option('custom_faq_settings', $settings);
        
        // Reindirizza e mostra messaggio di conferma
        wp_safe_redirect(add_query_arg('updated', 'true', admin_url('admin.php?page=custom-faq-settings')));
        exit;
    }
} 