/**
 * Fix FAQ Answers Script
 * 
 * Risolve problemi di animazione nelle FAQ gestendo sia altezza che opacità
 * Supporta sia jQuery che JavaScript vanilla
 */
(function() {
    'use strict';
    
    // Gestione globale degli errori
    try {
        // Aspetta che il DOM sia completamente caricato
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initScript);
        } else {
            initScript();
        }
    } catch (error) {
        console.error('Errore durante l\'inizializzazione dello script FAQ:', error);
    }
    
    /**
     * Inizializza lo script in base alla disponibilità di jQuery
     */
    function initScript() {
        // Applica le variabili CSS dalle impostazioni
        applyCssVariables();
        
        if (typeof jQuery !== 'undefined') {
            initWithjQuery(jQuery);
        } else {
            initVanilla();
        }
    }
    
    /**
     * Applica le variabili CSS dalle impostazioni
     */
    function applyCssVariables() {
        try {
            // Verifica se le impostazioni personalizzate sono disponibili
            if (typeof window.customFaqSettings !== 'undefined') {
                const settings = window.customFaqSettings;
                
                // Crea un elemento stile
                const styleElement = document.createElement('style');
                
                // Definisci le variabili CSS root
                styleElement.textContent = `
                    :root {
                        --faq-question-bg-color: ${settings.questionBgColor || '#f9f9f9'};
                        --faq-question-text-color: ${settings.questionTextColor || '#333333'};
                        --faq-answer-bg-color: ${settings.answerBgColor || '#ffffff'};
                        --faq-answer-text-color: ${settings.answerTextColor || '#333333'};
                        --faq-border-color: ${settings.borderColor || '#dddddd'};
                        --faq-active-question-bg-color: ${settings.activeQuestionBgColor || '#e9e9e9'};
                        --faq-hover-bg-color: ${settings.hoverBgColor || '#f0f0f0'};
                        --faq-answer-max-height: ${settings.answerMaxHeight || 1000}px;
                        --faq-bg-color: ${settings.answerBgColor || '#ffffff'};
                    }
                    
                    /* Animazioni aggiuntive per le FAQ */
                    @keyframes faqPulse {
                        0% { transform: scale(1); }
                        50% { transform: scale(1.05); }
                        100% { transform: scale(1); }
                    }
                    
                    @keyframes faqIconSpin {
                        from { transform: rotate(0deg); }
                        to { transform: rotate(180deg); }
                    }
                    
                    .faq-question:active:after {
                        animation: faqPulse 0.3s ease;
                    }
                `;
                
                // Aggiungi lo stile al documento
                document.head.appendChild(styleElement);
            }
        } catch (error) {
            console.error('Errore nell\'applicazione delle variabili CSS:', error);
        }
    }
    
    /**
     * Funzione per attivare/disattivare una FAQ
     * @param {Element} item - Elemento FAQ
     * @param {Boolean} isActive - Stato da impostare (true = attivo, false = disattivo)
     */
    function toggleFaqState(item, isActive) {
        try {
            const question = item.querySelector('.faq-question');
            const answer = item.querySelector('.faq-answer');
            
            if (!question || !answer) return;
            
            if (isActive) {
                // Attiva la FAQ
                item.classList.add('active');
                question.classList.add('active');
                answer.classList.add('active');
                
                // Imposta l'altezza della risposta
                const realHeight = answer.scrollHeight;
                answer.style.maxHeight = realHeight + 'px';
                answer.style.visibility = 'visible';
            } else {
                // Disattiva la FAQ
                item.classList.remove('active');
                question.classList.remove('active');
                answer.classList.remove('active');
                
                // Resetta l'altezza della risposta
                answer.style.maxHeight = '0';
                answer.style.visibility = 'hidden';
            }
        } catch (error) {
            console.error('Errore nel toggle della FAQ:', error);
        }
    }
    
    /**
     * Inizializzazione con jQuery
     * @param {Object} $ - jQuery
     */
    function initWithjQuery($) {
        try {
            $(document).ready(function() {
                // Trova tutti i contenitori FAQ
                $('.custom-faq-container').each(function() {
                    // Trova tutte le domande e le risposte all'interno di questo contenitore
                    var $questions = $(this).find('.faq-question');
                    var $answers = $(this).find('.faq-answer');
                    var $items = $(this).find('.faq-item');
                    
                    // Imposta la altezza massima iniziale per tutte le risposte
                    $answers.each(function() {
                        var $answer = $(this);
                        var $item = $answer.closest('.faq-item');
                        var $question = $item.find('.faq-question');
                        
                        // Se la risposta è attiva
                        if ($item.hasClass('active') || $question.hasClass('active')) {
                            // Memorizza l'altezza reale
                            var realHeight = $answer.outerHeight();
                            
                            // Imposta altezza e opacità
                            $answer.css({
                                'max-height': realHeight + 'px',
                                'visibility': 'visible',
                                'display': 'block'
                            });
                            
                            // Assicurati che tutte le classi siano coerenti
                            $item.addClass('active');
                            $question.addClass('active');
                            $answer.addClass('active');
                        } else {
                            // Imposta altezza e opacità per elementi chiusi
                            $answer.css({
                                'max-height': '0',
                                'visibility': 'hidden',
                                'display': 'block'
                            });
                            
                            // Rimuovi tutte le classi active
                            $item.removeClass('active');
                            $question.removeClass('active');
                            $answer.removeClass('active');
                        }
                    });
                    
                    // Gestisci il click sulle domande
                    $questions.on('click', function() {
                        var $question = $(this);
                        var $item = $question.closest('.faq-item');
                        
                        // Aggiungi classe temporanea per l'animazione del click
                        $question.addClass('clicked');
                        setTimeout(function() {
                            $question.removeClass('clicked');
                        }, 300);
                        
                        // Attiva/disattiva la FAQ
                        if ($item.hasClass('active')) {
                            toggleFaqState($item[0], false);
                        } else {
                            toggleFaqState($item[0], true);
                        }
                    });
                    
                    // Aggiungi listener per il ridimensionamento della finestra
                    $(window).on('resize', function() {
                        // Aggiorna l'altezza per tutte le risposte aperte
                        $items.filter('.active').each(function() {
                            var $item = $(this);
                            var $answer = $item.find('.faq-answer');
                            
                            // Resetta temporaneamente max-height per ottenere l'altezza reale
                            $answer.css('max-height', 'none');
                            var realHeight = $answer.outerHeight();
                            
                            // Ripristina max-height con il nuovo valore
                            $answer.css('max-height', realHeight + 'px');
                        });
                    });
                });
            });
        } catch (error) {
            console.error('Errore nell\'inizializzazione jQuery delle FAQ:', error);
        }
    }
    
    /**
     * Inizializzazione con JavaScript vanilla
     */
    function initVanilla() {
        try {
            // Trova tutti i contenitori FAQ
            var faqContainers = document.querySelectorAll('.custom-faq-container');
            
            faqContainers.forEach(function(container) {
                // Trova tutte le domande e le risposte all'interno di questo contenitore
                var questions = container.querySelectorAll('.faq-question');
                var answers = container.querySelectorAll('.faq-answer');
                var items = container.querySelectorAll('.faq-item');
                
                // Imposta lo stato iniziale per tutte le FAQ
                items.forEach(function(item) {
                    var isActive = item.classList.contains('active');
                    toggleFaqState(item, isActive);
                });
                
                // Aggiungi gestori di eventi per le domande
                questions.forEach(function(question) {
                    question.addEventListener('click', function() {
                        var item = question.closest('.faq-item');
                        
                        // Aggiungi una classe temporanea per animare l'icona al click
                        question.classList.add('clicked');
                        setTimeout(function() {
                            question.classList.remove('clicked');
                        }, 300);
                        
                        // Attiva/disattiva la FAQ
                        toggleFaqState(item, !item.classList.contains('active'));
                    });
                });
                
                // Aggiungi listener per il ridimensionamento della finestra
                window.addEventListener('resize', function() {
                    // Aggiorna l'altezza per tutte le risposte aperte
                    var activeItems = container.querySelectorAll('.faq-item.active');
                    activeItems.forEach(function(item) {
                        var answer = item.querySelector('.faq-answer');
                        if (answer) {
                            // Resetta temporaneamente max-height per ottenere l'altezza reale
                            answer.style.maxHeight = 'none';
                            var realHeight = answer.scrollHeight;
                            
                            // Ripristina max-height con il nuovo valore
                            answer.style.maxHeight = realHeight + 'px';
                        }
                    });
                });
            });
        } catch (error) {
            console.error('Errore nell\'inizializzazione vanilla delle FAQ:', error);
        }
    }
})(); 