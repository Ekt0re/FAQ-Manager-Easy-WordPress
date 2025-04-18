/**
 * Custom FAQ Manager - Script per il frontend
 * 
 * Gestisce l'interattività delle FAQ nel frontend con migliore gestione degli errori
 */
document.addEventListener('DOMContentLoaded', function() {
    try {
        // Seleziona tutti i contenitori FAQ
        const faqContainers = document.querySelectorAll('.custom-faq-container');

        if (!faqContainers.length) return;
        
        // Funzione per ottenere l'altezza di un elemento nascosto
        function getHeight(element) {
            try {
                // Clona l'elemento
                const clone = element.cloneNode(true);
                // Imposta le proprietà necessarie per misurarlo correttamente
                clone.style.display = 'block';
                clone.style.height = 'auto';
                clone.style.maxHeight = 'none';
                clone.style.opacity = '0';
                clone.style.visibility = 'hidden';
                clone.style.position = 'absolute';
                clone.style.top = '-9999px';
                
                // Aggiungi il clone al DOM
                document.body.appendChild(clone);
                
                // Ottieni l'altezza
                const height = clone.scrollHeight;
                
                // Rimuovi il clone
                document.body.removeChild(clone);
                
                return height;
            } catch (e) {
                console.error('Errore nel calcolo dell\'altezza:', e);
                return 300; // Valore predefinito in caso di errore
            }
        }
        
        // Funzione per scorrere fluido verso un elemento
        function scrollToElement(element, offset = 100) {
            try {
                const rect = element.getBoundingClientRect();
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                const targetTop = rect.top + scrollTop - offset;
                
                window.scrollTo({
                    top: targetTop,
                    behavior: 'smooth'
                });
            } catch (e) {
                console.error('Errore nello scrolling:', e);
            }
        }
        
        // Funzione per applicare stili personalizzati
        function applyCustomStyles(container) {
            try {
                // Ottieni le impostazioni personalizzate
                const settings = window.customFaqSettings || {};
                
                // Verifica che ci siano impostazioni da applicare
                if (Object.keys(settings).length === 0) return;
                
                // Crea un elemento di stile per questo container
                const styleId = 'custom-faq-styles-' + container.id;
                let styleEl = document.getElementById(styleId);
                
                if (!styleEl) {
                    styleEl = document.createElement('style');
                    styleEl.id = styleId;
                    document.head.appendChild(styleEl);
                }
                
                // Applica gli stili
                styleEl.textContent = `
                    #${container.id} .custom-faq-question {
                        background-color: ${settings.questionBgColor || '#f9f9f9'};
                        color: ${settings.questionTextColor || '#333333'};
                    }
                    #${container.id} .custom-faq-question:hover {
                        background-color: ${settings.hoverBgColor || '#f0f0f0'};
                    }
                    #${container.id} .custom-faq-item.open .custom-faq-question {
                        background-color: ${settings.activeQuestionBgColor || '#e9e9e9'};
                    }
                    #${container.id} .custom-faq-answer {
                        background-color: ${settings.answerBgColor || '#ffffff'};
                        color: ${settings.answerTextColor || '#333333'};
                    }
                    #${container.id} .custom-faq-item {
                        border-color: ${settings.borderColor || '#dddddd'};
                    }
                `;
            } catch (e) {
                console.error('Errore nell\'applicazione degli stili:', e);
            }
        }
        
        // Funzione per attivare/disattivare le FAQ
        function toggleFaq(item, forceOpen = false) {
            try {
                const answer = item.querySelector('.custom-faq-answer');
                
                // Verifica che risposta sia valida
                if (!answer) return;
                
                const isOpen = item.classList.contains('open');
                const container = item.closest('.custom-faq-container');
                
                // Verifica che container sia valido
                if (!container) return;
                
                const allowMultipleOpen = container.dataset.allowMultipleOpen === 'true';
                
                // Se stiamo forzando l'apertura e l'item è già aperto, non fare nulla
                if (forceOpen && isOpen) return;
                
                // Se non è consentita l'apertura multipla, chiudi tutte le altre FAQ
                if ((forceOpen || !isOpen) && !allowMultipleOpen) {
                    const otherItems = container.querySelectorAll('.custom-faq-item.open');
                    otherItems.forEach(function(otherItem) {
                        if (otherItem !== item) {
                            const otherAnswer = otherItem.querySelector('.custom-faq-answer');
                            if (otherAnswer) {
                                otherAnswer.style.maxHeight = '0';
                                otherAnswer.style.opacity = '0';
                                otherItem.classList.remove('open');
                            }
                            
                            // Aggiorna l'URL se necessario
                            if (otherItem.dataset.faqId && window.location.hash === '#' + otherItem.dataset.faqId) {
                                history.pushState(null, null, ' ');
                            }
                        }
                    });
                }
                
                // Alterna lo stato della FAQ
                if (forceOpen) {
                    item.classList.add('open');
                } else if (isOpen) {
                    item.classList.remove('open');
                } else {
                    item.classList.add('open');
                }
                
                // Aggiorna lo stile della risposta
                if (item.classList.contains('open')) {
                    const height = getHeight(answer);
                    answer.style.maxHeight = height + 'px';
                    answer.style.opacity = '1';
                    
                    // Aggiorna l'URL con l'ID della FAQ
                    if (item.dataset.faqId && window.history && window.history.pushState) {
                        window.history.pushState(null, null, '#' + item.dataset.faqId);
                    }
                    
                    // Scorri alla FAQ aperta
                    setTimeout(function() {
                        scrollToElement(item);
                    }, 300);
                } else {
                    answer.style.maxHeight = '0';
                    answer.style.opacity = '0';
                    
                    // Rimuovi l'hash dall'URL
                    if (item.dataset.faqId && window.location.hash === '#' + item.dataset.faqId && window.history && window.history.pushState) {
                        window.history.pushState(null, null, window.location.pathname + window.location.search);
                    }
                }
            } catch (e) {
                console.error('Errore nel toggle della FAQ:', e);
            }
        }
        
        // Inizializza tutti i container FAQ
        faqContainers.forEach(function(container, containerIndex) {
            try {
                // Assicura che ogni container abbia un ID univoco
                if (!container.id) {
                    container.id = 'custom-faq-container-' + containerIndex;
                }
                
                // Applica stili personalizzati
                applyCustomStyles(container);
                
                // Seleziona tutte le FAQ all'interno di questo container
                const faqItems = container.querySelectorAll('.custom-faq-item');
                
                // Default FAQ aperta
                const defaultOpenIndex = parseInt(container.dataset.defaultOpen || '-1');
                
                // Inizializza le FAQ
                faqItems.forEach(function(item, index) {
                    try {
                        const answer = item.querySelector('.custom-faq-answer');
                        
                        // Salta se non c'è una risposta
                        if (!answer) return;
                        
                        // Assicura che ogni item abbia un data-faq-id
                        if (!item.dataset.faqId) {
                            item.dataset.faqId = 'faq-' + containerIndex + '-' + index;
                        }
                        
                        // Inizializza la risposta come chiusa
                        answer.style.maxHeight = '0';
                        answer.style.opacity = '0';
                        
                        // Apri la FAQ predefinita
                        if (index === defaultOpenIndex) {
                            toggleFaq(item, true);
                        }
                        
                        // Aggiungi gestione eventi
                        const question = item.querySelector('.custom-faq-question');
                        if (question) {
                            question.addEventListener('click', function(e) {
                                e.preventDefault();
                                toggleFaq(item);
                            });
                        }
                    } catch (itemError) {
                        console.error('Errore nell\'inizializzazione item FAQ:', itemError);
                    }
                });
            } catch (containerError) {
                console.error('Errore nell\'inizializzazione container FAQ:', containerError);
            }
        });
        
        // Controlla l'hash nell'URL
        function checkUrlHash() {
            try {
                const hash = window.location.hash;
                if (hash && hash.length > 1) {
                    const faqId = hash.substring(1);
                    const targetFaq = document.querySelector(`.custom-faq-item[data-faq-id="${faqId}"]`);
                    
                    if (targetFaq) {
                        // Forza l'apertura della FAQ
                        toggleFaq(targetFaq, true);
                    }
                }
            } catch (e) {
                console.error('Errore nel controllo dell\'hash URL:', e);
            }
        }
        
        // Esegui il controllo dell'hash all'avvio
        checkUrlHash();
        
        // Ascolta i cambiamenti dell'hash
        window.addEventListener('hashchange', checkUrlHash);
        
        // Gestisci il ridimensionamento della finestra
        window.addEventListener('resize', function() {
            try {
                const openFaqs = document.querySelectorAll('.custom-faq-item.open');
                openFaqs.forEach(function(item) {
                    const answer = item.querySelector('.custom-faq-answer');
                    if (answer) {
                        const height = getHeight(answer);
                        answer.style.maxHeight = height + 'px';
                    }
                });
            } catch (e) {
                console.error('Errore nel ridimensionamento:', e);
            }
        });
    } catch (e) {
        console.error('Errore generale nel caricamento FAQ:', e);
    }
});