/**
 * Custom FAQ Manager - Admin Scripts
 */
jQuery(document).ready(function($) {
    // Edit FAQ
    $(document).on('click', '.edit-faq', function() {
        var id = $(this).data('id');
        
        // Show loading indicator
        $(this).text('Loading...').attr('disabled', true);
        
        // Get FAQ data
        $.post(custom_faq_ajax.ajax_url, {
            action: 'get_faq',
            id: id,
            nonce: custom_faq_ajax.nonce
        }, function(response) {
            if (response.success) {
                var data = response.data;
                
                // Populate form
                $('#faq-id').val(data.id);
                $('#faq-title').val(data.title);
                
                // Set content in editor
                if (typeof tinyMCE !== 'undefined' && tinyMCE.get('faq-content')) {
                    tinyMCE.get('faq-content').setContent(data.content);
                } else {
                    $('#faq-content').val(data.content);
                }
                
                $('#faq-css').val(data.css);
                
                // Update editor title
                $('#editor-title').text('Modifica FAQ');
                
                // Scroll to editor
                $('html, body').animate({
                    scrollTop: $('#faq-editor').offset().top - 50
                }, 500);
            } else {
                alert(response.data.message);
            }
            
            // Reset button
            $('.edit-faq[data-id="' + id + '"]').text('Modifica').attr('disabled', false);
        }).fail(function() {
            alert('Si è verificato un errore. Riprova.');
            $('.edit-faq[data-id="' + id + '"]').text('Modifica').attr('disabled', false);
        });
    });
    
    // New FAQ button
    $('#new-faq-btn').click(function() {
        // Reset form
        $('#faq-id').val('');
        $('#faq-title').val('');
        
        // Clear editor
        if (typeof tinyMCE !== 'undefined' && tinyMCE.get('faq-content')) {
            tinyMCE.get('faq-content').setContent('');
        } else {
            $('#faq-content').val('');
        }
        
        $('#faq-css').val('');
        
        // Update editor title
        $('#editor-title').text('Aggiungi Nuova FAQ');
        
        // Scroll to editor
        $('html, body').animate({
            scrollTop: $('#faq-editor').offset().top - 50
        }, 500);
    });
    
    // Cancel edit
    $('#cancel-edit').click(function(e) {
        e.preventDefault();
        
        // Reset form
        $('#faq-id').val('');
        $('#faq-title').val('');
        
        // Clear editor
        if (typeof tinyMCE !== 'undefined' && tinyMCE.get('faq-content')) {
            tinyMCE.get('faq-content').setContent('');
        } else {
            $('#faq-content').val('');
        }
        
        $('#faq-css').val('');
        
        // Update editor title
        $('#editor-title').text('Aggiungi Nuova FAQ');
        
        // Scroll to list
        $('html, body').animate({
            scrollTop: $('.faq-list-container').offset().top - 50
        }, 500);
    });
    
    // Search FAQs
    $('#search-btn').click(function() {
        var search = $('#faq-search').val();
        
        $.post(custom_faq_ajax.ajax_url, {
            action: 'search_faqs',
            search: search,
            nonce: custom_faq_ajax.nonce
        }, function(response) {
            if (response.success) {
                $('#faq-list-body').html(response.data.html);
            }
        });
    });
    
    // Search on Enter key
    $('#faq-search').keypress(function(e) {
        if (e.which === 13) {
            $('#search-btn').click();
            e.preventDefault();
        }
    });
    
    // Save FAQ
    $('#faq-form').submit(function(e) {
        e.preventDefault();
        
        var id = $('#faq-id').val();
        var title = $('#faq-title').val();
        var content = '';
        
        // Get content from editor
        if (typeof tinyMCE !== 'undefined' && tinyMCE.get('faq-content')) {
            content = tinyMCE.get('faq-content').getContent();
        } else {
            content = $('#faq-content').val();
        }
        
        var css = $('#faq-css').val();
        
        // Validate
        if (!title.trim()) {
            alert('La domanda è obbligatoria.');
            return;
        }
        
        // Show loading
        var $submitBtn = $(this).find('button[type="submit"]');
        var originalText = $submitBtn.text();
        $submitBtn.text('Salvataggio...').attr('disabled', true);
        
        $.post(custom_faq_ajax.ajax_url, {
            action: 'save_faq',
            id: id,
            title: title,
            content: content,
            css: css,
            nonce: custom_faq_ajax.nonce
        }, function(response) {
            if (response.success) {
                // Refresh FAQ list
                $('#search-btn').click();
                
                // Show success message
                alert(response.data.message);
                
                // Reset form
                $('#faq-id').val('');
                $('#faq-title').val('');
                
                // Clear editor
                if (typeof tinyMCE !== 'undefined' && tinyMCE.get('faq-content')) {
                    tinyMCE.get('faq-content').setContent('');
                } else {
                    $('#faq-content').val('');
                }
                
                $('#faq-css').val('');
                
                // Update editor title
                $('#editor-title').text('Aggiungi Nuova FAQ');
                
                // Scroll to list
                $('html, body').animate({
                    scrollTop: $('.faq-list-container').offset().top - 50
                }, 500);
            } else {
                alert(response.data.message);
            }
            
            // Reset button
            $submitBtn.text(originalText).attr('disabled', false);
        }).fail(function() {
            alert('Si è verificato un errore. Riprova.');
            $submitBtn.text(originalText).attr('disabled', false);
        });
    });
    
    // Delete FAQ
    $(document).on('click', '.delete-faq', function() {
        if (!confirm('Sei sicuro di voler eliminare questa FAQ?')) {
            return;
        }
        
        var id = $(this).data('id');
        var $row = $(this).closest('tr');
        
        // Show loading
        $(this).text('Eliminazione...').attr('disabled', true);
        
        $.post(custom_faq_ajax.ajax_url, {
            action: 'delete_faq',
            id: id,
            nonce: custom_faq_ajax.nonce
        }, function(response) {
            if (response.success) {
                // Remove row
                $row.fadeOut(300, function() {
                    $(this).remove();
                    
                    // Check if list is empty
                    if ($('#faq-list-body tr').length === 0) {
                        $('#faq-list-body').html('<tr class="no-items"><td colspan="2">Nessuna FAQ trovata.</td></tr>');
                    }
                });
                
                // Show success message
                alert(response.data.message);
            } else {
                alert(response.data.message);
                $('.delete-faq[data-id="' + id + '"]').text('Elimina').attr('disabled', false);
            }
        }).fail(function() {
            alert('Si è verificato un errore. Riprova.');
            $('.delete-faq[data-id="' + id + '"]').text('Elimina').attr('disabled', false);
        });
    });
}); 