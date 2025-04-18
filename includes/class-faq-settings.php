/**
 * Sanitizza i dati dei colori per evitare errori nel database
 *
 * @param mixed $input Il valore dell'input da sanitizzare
 * @return string Il valore sanitizzato
 */
public function sanitize_color_option( $input ) {
    // Verifica che l'input sia una stringa
    if ( !is_string( $input ) ) {
        return '';
    }
    
    // Rimuovi spazi bianchi
    $input = trim( $input );
    
    // Convalida il formato del colore
    if ( empty( $input ) || $input === 'transparent' ) {
        return $input;
    }
    
    // Se inizia con # verifica che sia un colore esadecimale valido
    if ( $input[0] === '#' ) {
        // Colore esadecimale (#RGB o #RRGGBB)
        $input = preg_replace( '/[^A-Fa-f0-9#]/', '', $input );
        
        // Convalida la lunghezza
        if ( strlen( $input ) !== 4 && strlen( $input ) !== 7 ) {
            return '#ffffff'; // Valore predefinito di fallback
        }
    } else if ( strpos( $input, 'rgb' ) === 0 ) {
        // Formato rgb() o rgba()
        $pattern = '/rgba?\(\s*(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\s*,\s*(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\s*,\s*(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\s*(?:,\s*([01](?:\.[0-9]+)?))?\s*\)/';
        if ( !preg_match( $pattern, $input ) ) {
            return '#ffffff'; // Valore predefinito di fallback
        }
    } else {
        // Non è un colore valido, ritorna il valore predefinito
        return '#ffffff';
    }
    
    return $input;
}

/**
 * Salva le impostazioni del plugin con gestione errori migliorata
 */
public function save_settings() {
    try {
        // Verifica il nonce per sicurezza
        if ( !isset( $_POST['faq_settings_nonce'] ) || !wp_verify_nonce( $_POST['faq_settings_nonce'], 'faq_save_settings' ) ) {
            wp_die( 'Verifica di sicurezza fallita.' );
        }
        
        // Inizializza un array per le opzioni da salvare
        $options_to_save = array();
        
        // Elabora le opzioni di colore con sanitizzazione
        $color_options = array(
            'faq_title_color',
            'faq_question_color',
            'faq_question_hover_color',
            'faq_answer_color',
            'faq_border_color',
            'faq_background_color',
            'faq_icon_color'
        );
        
        foreach ( $color_options as $option ) {
            if ( isset( $_POST[$option] ) ) {
                $options_to_save[$option] = $this->sanitize_color_option( $_POST[$option] );
            }
        }
        
        // Elabora altre opzioni con sanitizzazione appropriata
        if ( isset( $_POST['faq_title_size'] ) ) {
            $options_to_save['faq_title_size'] = absint( $_POST['faq_title_size'] );
        }
        
        if ( isset( $_POST['faq_question_size'] ) ) {
            $options_to_save['faq_question_size'] = absint( $_POST['faq_question_size'] );
        }
        
        if ( isset( $_POST['faq_answer_size'] ) ) {
            $options_to_save['faq_answer_size'] = absint( $_POST['faq_answer_size'] );
        }
        
        // Opzioni booleane
        $boolean_options = array(
            'faq_allow_multiple_open',
            'faq_show_icon',
            'faq_show_search'
        );
        
        foreach ( $boolean_options as $option ) {
            $options_to_save[$option] = isset( $_POST[$option] ) ? '1' : '0';
        }
        
        // Aggiorna ogni opzione individualmente
        foreach ( $options_to_save as $option => $value ) {
            update_option( $option, $value );
        }
        
        // Aggiungi un messaggio di successo
        add_settings_error(
            'faq_settings',
            'settings_updated',
            __( 'Impostazioni salvate con successo.', 'custom-faq-manager' ),
            'updated'
        );
        
    } catch ( Exception $e ) {
        // Gestione degli errori
        error_log( 'Errore nel salvataggio delle impostazioni FAQ: ' . $e->getMessage() );
        
        add_settings_error(
            'faq_settings',
            'settings_error',
            __( 'Si è verificato un errore durante il salvataggio delle impostazioni.', 'custom-faq-manager' ),
            'error'
        );
    }
    
    // Reindirizza alla pagina delle impostazioni con messaggi appropriati
    set_transient( 'settings_errors', get_settings_errors(), 30 );
    
    $redirect_url = add_query_arg( 'settings-updated', 'true', wp_get_referer() );
    wp_safe_redirect( $redirect_url );
    exit;
} 