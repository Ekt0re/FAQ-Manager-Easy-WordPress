/**
 * Custom Easy FAQ Manager - Language Switcher
 * Gestisce il cambio lingua nell'interfaccia amministrativa
 */
jQuery(document).ready(function($) {
    // Gestisce il cambio di lingua quando si seleziona un'opzione dal menu a tendina
    $('#language-selector').on('change', function() {
        const selectedLanguage = $(this).val();
        
        // Salva la lingua selezionata in un cookie
        document.cookie = "custom_faq_language=" + selectedLanguage + "; path=/; max-age=31536000"; // 1 anno
        
        // Ricarica la pagina per applicare la nuova lingua
        location.reload();
    });
    
    // Mostra la lingua attualmente selezionata
    const currentLanguage = getCookie('custom_faq_language');
    if (currentLanguage) {
        $('#language-selector').val(currentLanguage);
    }
    
    // Funzione per ottenere il valore di un cookie
    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    }
}); 