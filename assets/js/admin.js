/**
 * Custom FAQ Manager - Admin Scripts
 */
jQuery(document).ready(function($) {
    // Edit FAQ
    $(document).on("click", ".edit-faq", function(e) {
        e.preventDefault(); // Previene comportamenti indesiderati
        
        // Previene clic multipli
        if ($(this).data('loading') === true) {
            return false;
        }
        
        var id = $(this).data("id");
        
        // Controlla che l'id sia valido
        if (!id || id <= 0) {
            alert("ID FAQ non valido.");
            return;
        }
        
        // Show loading indicator
        var $button = $(this);
        $button.data('loading', true);
        $button.data('original-text', $button.text());
        $button.text("Caricamento...").attr("disabled", true);
        
        // Get FAQ data with error handling
        $.ajax({
            url: custom_faq_ajax.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: "get_faq",
                id: id,
                nonce: custom_faq_ajax.nonce
            },
            success: function(response) {
                console.log("Risposta completa dal server:", response); // Debug
                
                if (response && response.success && response.data) {
                    var data = response.data;
                    
                    // Controlla che i dati siano validi
                    if (!data || typeof data !== 'object' || !data.id) {
                        console.error("Dati FAQ incompleti:", data);
                        alert("I dati della FAQ ricevuti non sono validi. Riprova o contatta l'amministratore.");
                        resetButton($button);
                        return;
                    }
                    
                    try {
                        // Populate form with safeguards
                        $("#faq-id").val(data.id);
                        
                        // Gestione sicura del titolo
                        if (data.title) {
                            $("#faq-title").val(data.title);
                        } else {
                            $("#faq-title").val('');
                        }
                        
                        // Set content in editor - miglioramento gestione editor
                        const content = data.content || '';
                        
                        if (typeof tinyMCE !== "undefined") {
                            const editor = tinyMCE.get("faq-content");
                            
                            if (editor && editor.initialized) {
                                try {
                                    editor.setContent(content);
                                } catch (editorError) {
                                    console.error("Errore nell'inizializzazione dell'editor:", editorError);
                                    $("#faq-content").val(content);
                                }
                            } else {
                                // Fallback al textarea
                                $("#faq-content").val(content);
                                
                                // Tentativo di reinizializzazione sicura dell'editor
                                if (typeof switchEditors !== 'undefined') {
                                    try {
                                        switchEditors.go('faq-content', 'tmce');
                                        
                                        // Riprova dopo un breve ritardo
                                        setTimeout(function() {
                                            const editorRetry = tinyMCE.get("faq-content");
                                            if (editorRetry && editorRetry.initialized) {
                                                editorRetry.setContent(content);
                                            }
                                        }, 500);
                                    } catch (switchError) {
                                        console.error("Errore nel cambio di editor:", switchError);
                                    }
                                }
                            }
                        } else {
                            // Editor non disponibile, usa il textarea
                            $("#faq-content").val(content);
                        }
                        
                        // Imposta CSS con protezione
                        $("#faq-css").val(data.css || '');
                        
                        // Update editor title
                        $("#editor-title").text("Modifica FAQ: " + data.title);
                        
                        // Scroll to editor with a slight delay
                        setTimeout(function() {
                            try {
                                $("html, body").animate({
                                    scrollTop: $("#faq-editor").offset().top - 50
                                }, 500);
                                
                                // Evidenzia il form per attirare l'attenzione
                                $("#faq-editor").css({
                                    "background-color": "#f7fcff",
                                    "border-color": "#007cba"
                                }).delay(500).animate({
                                    "background-color": "#fff",
                                    "border-color": "#ddd"
                                }, 500);
                            } catch (scrollError) {
                                console.error("Errore nello scroll:", scrollError);
                            }
                        }, 100);
                    } catch (e) {
                        console.error("Errore durante l'elaborazione dei dati:", e);
                        alert("Si è verificato un errore durante l'elaborazione dei dati. Si prega di riprovare.");
                    }
                } else {
                    console.error("Errore nella risposta:", response);
                    var errorMsg = response && response.data && response.data.message
                        ? response.data.message
                        : "Si è verificato un errore durante il caricamento della FAQ. Riprova.";
                    alert(errorMsg);
                }
                
                // Reset button
                resetButton($button);
            },
            error: function(xhr, status, error) {
                console.error("Errore AJAX:", error);
                console.error("Stato:", status);
                console.error("Risposta server:", xhr.responseText);
                
                var errorMsg = "Si è verificato un errore di comunicazione con il server.";
                
                // Prova a interpretare la risposta se è in formato JSON
                try {
                    var jsonResponse = JSON.parse(xhr.responseText);
                    if (jsonResponse && jsonResponse.message) {
                        errorMsg += " Dettagli: " + jsonResponse.message;
                    }
                } catch(e) {
                    // La risposta non è in formato JSON valido
                    if (xhr.status) {
                        errorMsg += " Codice di stato: " + xhr.status;
                    }
                }
                
                alert(errorMsg);
                resetButton($button);
            },
            timeout: 30000 // Aumenta il timeout a 30 secondi
        });
    });
    
    // Funzione helper per ripristinare lo stato del pulsante
    function resetButton($button) {
        if ($button.data('original-text')) {
            $button.text($button.data('original-text'));
        } else {
            $button.text("Modifica");
        }
        $button.attr("disabled", false);
        $button.data('loading', false);
    }
    
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
    
    // Funzione per aggiornare l'elenco delle FAQ
    function refreshFaqList() {
        var $listBody = $("#faq-list-body");
        var search = $("#faq-search").val();
        
        // Mostra indicatore di caricamento
        $listBody.html('<tr><td colspan="2" style="text-align:center;padding:15px;"><span class="spinner is-active" style="float:none;"></span> Aggiornamento in corso...</td></tr>');
        
        $.ajax({
            url: custom_faq_ajax.ajax_url,
            type: 'POST',
            data: {
                action: "search_faqs",
                search: search,
                nonce: custom_faq_ajax.nonce
            },
            dataType: 'json',
            cache: false, // Impedisce la memorizzazione nella cache
            success: function(response) {
                if (response.success && response.data) {
                    // Controlla se abbiamo un array di FAQ
                    if (response.data.faqs && response.data.faqs.length > 0) {
                        var html = '';
                        // Ordina le FAQ per titolo alfabeticamente
                        response.data.faqs.sort(function(a, b) {
                            return a.title.localeCompare(b.title);
                        });
                        
                        $.each(response.data.faqs, function(index, faq) {
                            html += '<tr data-id="' + faq.id + '">';
                            html += '<td class="faq-title">' + faq.title + '</td>';
                            html += '<td class="faq-actions">';
                            html += '<button class="edit-faq button" data-id="' + faq.id + '">Modifica</button> ';
                            html += '<button class="delete-faq button" data-id="' + faq.id + '">Elimina</button>';
                            html += '</td>';
                            html += '</tr>';
                        });
                        $listBody.html(html);
                        
                        // Evidenzia l'ultima FAQ modificata o creata
                        var lastEditedId = $("#last-edited-faq-id").val();
                        if (lastEditedId) {
                            var $lastEditedRow = $('tr[data-id="' + lastEditedId + '"]');
                            if ($lastEditedRow.length) {
                                $lastEditedRow.css({
                                    "background-color": "#f7fcff",
                                    "transition": "background-color 1s"
                                });
                                
                                // Ripristina il colore di sfondo originale dopo 2 secondi
                                setTimeout(function() {
                                    $lastEditedRow.css({
                                        "background-color": "",
                                        "transition": "background-color 1s"
                                    });
                                }, 2000);
                                
                                // Rimuovi l'ID dell'ultima FAQ modificata
                                $("#last-edited-faq-id").val("");
                            }
                        }
                    } else {
                        // Nessuna FAQ trovata
                        $listBody.html('<tr class="no-items"><td colspan="2">Nessuna FAQ trovata.</td></tr>');
                    }
                } else {
                    // Gestisci l'errore
                    $listBody.html('<tr><td colspan="2" style="color:red;">Errore durante l\'aggiornamento dell\'elenco. Ricarica la pagina.</td></tr>');
                }
            },
            error: function() {
                $listBody.html('<tr><td colspan="2" style="color:red;">Errore di connessione. Ricarica la pagina.</td></tr>');
            }
        });
    }
    
    // Aggiungi un campo nascosto per tenere traccia dell'ultima FAQ modificata
    $("body").append('<input type="hidden" id="last-edited-faq-id" value="">');

    // Modifica la gestione del salvataggio FAQ per memorizzare l'ID dell'ultima FAQ modificata
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
        
        $.ajax({
            url: custom_faq_ajax.ajax_url,
            type: 'POST',
            data: {
                action: "save_faq",
                id: id,
                title: title,
                content: content,
                css: css,
                nonce: custom_faq_ajax.nonce
            },
            dataType: 'json',
            cache: false,
            success: function(response) {
                if (response.success) {
                    // Mostra messaggio di successo
                    var successMessage = id > 0 
                        ? "FAQ aggiornata con successo."
                        : "Nuova FAQ creata con successo.";
                    
                    // Crea elemento notifica
                    var $notification = $('<div class="notice notice-success is-dismissible"><p>' + successMessage + '</p></div>');
                    
                    // Aggiungi notifica all'inizio della pagina
                    $('.wrap > h1').after($notification);
                    
                    // Rimuovi notifica dopo 4 secondi
                    setTimeout(function() {
                        $notification.slideUp(300, function() {
                            $(this).remove();
                        });
                    }, 4000);
                    
                    // Memorizza l'ID dell'ultima FAQ modificata
                    $("#last-edited-faq-id").val(response.data.id);
                    
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
                    
                    // Aggiorna elenco FAQ con un piccolo ritardo per assicurare che il database sia aggiornato
                    setTimeout(function() {
                        refreshFaqList();
                    }, 300);
                    
                    // Scroll to list per vedere l'elemento aggiornato/aggiunto
                    $("html, body").animate({
                        scrollTop: $(".faq-list-container").offset().top - 50
                    }, 500);
                } else {
                    alert(response.data.message);
                }
                
                // Reset button
                $submitBtn.text(originalText).attr("disabled", false);
            },
            error: function() {
                alert("Si è verificato un errore. Riprova.");
                $submitBtn.text(originalText).attr("disabled", false);
            }
        });
    });
    
    // Delete FAQ con aggiornamento automatico
    $(document).on("click", ".delete-faq", function() {
        if (!confirm("Sei sicuro di voler eliminare questa FAQ?")) {
            return;
        }
        
        var id = $(this).data("id");
        var $row = $(this).closest("tr");
        
        // Show loading
        $(this).text("Eliminazione...").attr("disabled", true);
        
        $.ajax({
            url: custom_faq_ajax.ajax_url,
            type: 'POST',
            data: {
                action: "delete_faq",
                id: id,
                nonce: custom_faq_ajax.nonce
            },
            dataType: 'json',
            cache: false,
            success: function(response) {
                if (response.success) {
                    // Rimuovi la riga con effetto di dissolvenza
                    $row.fadeOut(300, function() {
                        $(this).remove();
                        
                        // Crea messaggio di successo
                        var $notification = $('<div class="notice notice-success is-dismissible"><p>FAQ eliminata con successo.</p></div>');
                        $('.wrap > h1').after($notification);
                        
                        // Rimuovi notifica dopo 3 secondi
                        setTimeout(function() {
                            $notification.slideUp(300, function() {
                                $(this).remove();
                            });
                        }, 3000);
                        
                        // Aggiorna l'elenco dopo l'eliminazione con un piccolo ritardo
                        setTimeout(function() {
                            refreshFaqList();
                        }, 300);
                    });
                } else {
                    alert(response.data.message);
                    $(".delete-faq[data-id=\"" + id + "\"]").text("Elimina").attr("disabled", false);
                }
            },
            error: function() {
                alert("Si è verificato un errore. Riprova.");
                $(".delete-faq[data-id=\"" + id + "\"]").text("Elimina").attr("disabled", false);
            }
        });
    });

    // ----- INIZIO: Nuove funzioni per la personalizzazione grafica -----
    
    // Funzione per aggiornare l'anteprima in tempo reale
    function updatePreview() {
        var questionBgColor = $("#question_bg_color").val();
        var questionTextColor = $("#question_text_color").val();
        var answerBgColor = $("#answer_bg_color").val();
        var answerTextColor = $("#answer_text_color").val();
        var borderColor = $("#border_color").val();
        var activeQuestionBgColor = $("#active_question_bg_color").val();
        var hoverBgColor = $("#hover_bg_color").val();
        var answerMaxHeight = $("#answer_max_height").val() + "px";
        
        // Aggiorna gli stili nell'anteprima
        $("#faq-preview-item").css({
            "border": "1px solid " + borderColor,
            "border-radius": "5px",
            "overflow": "hidden",
            "box-shadow": "0 2px 5px rgba(0, 0, 0, 0.05)"
        });
        
        $("#faq-preview-question").css({
            "background-color": questionBgColor,
            "color": questionTextColor,
            "border-bottom": "1px solid " + borderColor,
            "padding": "15px 20px",
            "font-weight": "600",
            "cursor": "pointer",
            "position": "relative",
            "display": "flex",
            "justify-content": "space-between",
            "align-items": "center"
        });
        
        // Aggiungi un after element per l'icona +/-
        $("#faq-preview-question").attr("data-after-content", "+");
        
        // Se attivo, cambia il colore di sfondo e l'icona
        if ($("#faq-preview-question").hasClass("active")) {
            $("#faq-preview-question").css("background-color", activeQuestionBgColor);
            $("#faq-preview-question").attr("data-after-content", "−");
        }
        
        $("#faq-preview-answer").css({
            "background-color": answerBgColor,
            "color": answerTextColor,
            "padding": $("#faq-preview-answer").hasClass("active") ? "20px" : "0",
            "max-height": $("#faq-preview-answer").hasClass("active") ? answerMaxHeight : "0",
            "overflow": "auto"
        });
        
        // Imposta stili per hover utilizzando CSS inline
        var styleContent = `
            #faq-preview-question:hover {
                background-color: ${hoverBgColor} !important;
            }
            #faq-preview-question::after {
                content: attr(data-after-content);
                font-size: 22px;
                line-height: 1;
                color: #777;
            }
            #faq-preview-answer p {
                margin-top: 0;
                margin-bottom: 10px;
            }
            #faq-preview-answer p:last-child {
                margin-bottom: 0;
            }
        `;
        
        // Aggiorna o crea lo stile hover
        if ($("#faq-preview-style").length) {
            $("#faq-preview-style").html(styleContent);
        } else {
            $("head").append('<style id="faq-preview-style">' + styleContent + '</style>');
        }
    }
    
    // Aggiorna l'anteprima quando un colore o l'altezza massima viene cambiata
    $(".settings-grid input").on("input", updatePreview);
    
    // Aggiorna i colori delle anteprime accanto alle etichette
    $(".settings-grid input[type='color']").each(function() {
        var $input = $(this);
        var id = $input.attr('id');
        var labelSelector = "label[for='" + id + "']";
        
        // Imposta il colore iniziale nell'anteprima
        $(labelSelector).css('--preview-color', $input.val());
        
        // Aggiorna il colore quando cambia l'input
        $input.on('input', function() {
            $(labelSelector).css('--preview-color', $input.val());
        });
    });
    
    // Inizializza l'anteprima al caricamento della pagina
    setTimeout(function() {
        updatePreview();
        
        // Aggiungi la classe "active" all'anteprima della risposta all'avvio
        $("#faq-preview-answer").addClass("active");
        $("#faq-preview-question").addClass("active");
        
        // Dopo un breve ritardo, aggiorna di nuovo per riflettere lo stato attivo
        setTimeout(updatePreview, 100);
    }, 100);
    
    // Gestisci il pulsante "Importa dal tema"
    $("#import-theme-colors").click(function() {
        // Mostra un messaggio di conferma
        if (!confirm("Sei sicuro di voler importare i colori dal tema attivo? Questa azione sovrascriverà le tue impostazioni correnti.")) {
            return;
        }
        
        // Mostra lo stato di caricamento
        var $button = $(this);
        var originalText = $button.text();
        $button.text("Importazione...").attr("disabled", true);
        
        // Effettua la richiesta AJAX per ottenere i colori dal tema
        $.post(custom_faq_ajax.ajax_url, {
            action: "get_theme_colors",
            nonce: custom_faq_ajax.nonce
        }, function(response) {
            // Ripristina il testo del pulsante
            $button.text(originalText).attr("disabled", false);
            
            if (response.success) {
                var themeColors = response.data.colors;
                
                // Aggiorna i campi di input con i colori del tema
                $.each(themeColors, function(key, value) {
                    $("#" + key).val(value);
                });
                
                // Aggiorna l'anteprima
                updatePreview();
                
                // Mostra un messaggio di successo
                alert("Colori del tema importati con successo nell'anteprima. Clicca 'Salva Impostazioni' per renderli effettivi.");
            } else {
                alert("Errore durante l'importazione dei colori: " + response.data.message);
            }
        }).fail(function() {
            $button.text(originalText).attr("disabled", false);
            alert("Si è verificato un errore di connessione. Riprova più tardi.");
        });
    });
    
    // Salva le impostazioni FAQ
    $("#faq-settings-form").submit(function(e) {
        e.preventDefault();
        
        // Raccolta di tutti i campi colore e validazione
        const colorInputs = $(this).find('input[type="color"]');
        let isValid = true;
        let formData = {
            action: "save_faq_settings",
            nonce: custom_faq_ajax.nonce
        };
        
        // Verifica e raccolta dei valori
        colorInputs.each(function() {
            const input = $(this);
            const name = input.attr('name');
            const value = input.val();
            
            // Validazione di base del colore
            if (!value.match(/^#([A-Fa-f0-9]{3}){1,2}$/)) {
                input.css('border', '1px solid red');
                isValid = false;
            } else {
                input.css('border', '');
                formData[name] = value;
            }
        });
        
        // Aggiungi il valore dell'altezza
        formData['answer_max_height'] = $("#answer_max_height").val();
        
        if (!isValid) {
            alert("Alcuni colori non sono in formato valido. Usa il formato esadecimale (#RRGGBB).");
            return;
        }
        
        // Mostra indicatore di caricamento
        const $submitBtn = $(this).find('button[type="submit"]');
        const originalBtnText = $submitBtn.text();
        $submitBtn.text('Salvataggio...').attr('disabled', true);
        
        // Invia la richiesta AJAX
        $.ajax({
            url: custom_faq_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Mostra messaggio di successo
                    $("#settings-status")
                        .html(response.data.message)
                        .css('background-color', '#dff0d8')
                        .show()
                        .delay(3000)
                        .fadeOut();
                    
                    // Aggiorna preview con i nuovi colori
                    updatePreview();
                } else {
                    // Mostra messaggio di errore
                    $("#settings-status")
                        .html(response.data.message)
                        .css('background-color', '#f2dede')
                        .show()
                        .delay(5000)
                        .fadeOut();
                }
                
                // Ripristina stato del pulsante
                $submitBtn.text(originalBtnText).attr('disabled', false);
            },
            error: function(xhr, status, error) {
                console.error("Errore salvataggio:", xhr.responseText);
                
                // Mostra messaggio di errore
                $("#settings-status")
                    .html("Si è verificato un errore durante il salvataggio. Riprova.")
                    .css('background-color', '#f2dede')
                    .show()
                    .delay(5000)
                    .fadeOut();
                
                // Ripristina stato del pulsante
                $submitBtn.text(originalBtnText).attr('disabled', false);
            }
        });
    });
    
    // Funzione per aggiornare l'anteprima
    function updatePreview() {
        // Applica colori all'anteprima
        $("#faq-preview-question").css({
            'background-color': $("#question_bg_color").val(),
            'color': $("#question_text_color").val()
        });
        
        $("#faq-preview-answer").css({
            'background-color': $("#answer_bg_color").val(),
            'color': $("#answer_text_color").val(),
            'max-height': $("#answer_max_height").val() + 'px'
        });
        
        $("#faq-preview-item").css({
            'border-color': $("#border_color").val()
        });
    }
    
    // Aggiorna l'anteprima quando i valori cambiano
    $("#faq-settings-form input").on('change', updatePreview);
    
    // Inizializza l'anteprima
    updatePreview();
    
    // Toggle dell'anteprima FAQ
    $("#faq-preview-question").on('click', function() {
        var $answer = $("#faq-preview-answer");
        $(this).toggleClass('active');
        
        if ($(this).hasClass('active')) {
            $answer.slideDown();
        } else {
            $answer.slideUp();
        }
    });
    
    // ----- FINE: Nuove funzioni per la personalizzazione grafica -----

    // Gestione cambio lingua
    $("#language-selector").on('change', function() {
        var selectedLanguage = $(this).val();
        
        // Salva la preferenza nei cookie
        document.cookie = "custom_faq_language=" + selectedLanguage + "; path=/; max-age=31536000"; // 1 anno
        
        // Ricarica la pagina per applicare la nuova lingua
        location.reload();
    });
});