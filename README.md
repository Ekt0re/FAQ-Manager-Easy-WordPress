# Custom Easy FAQ Manager

Plugin WordPress che permette di creare e gestire facilmente le FAQ (Frequently Asked Questions) personalizzate sul tuo sito con una GUI semplice ed intuitiva!

## Funzionalità

- Interfaccia utente semplice e intuitiva
- Effetto accordion personalizzabile
- Blocco Gutenberg dedicato
- Shortcode per inserire le FAQ in qualsiasi pagina
- Possibilità di personalizzare colori e stili
- Supporto per la ricerca nelle FAQ
- Editor visuale integrato
- Compatibilità responsive per tutti i dispositivi

## Installazione

1. Carica i file del plugin nella cartella `/wp-content/plugins/custom-easy-faq-manager/`
2. Attiva il plugin attraverso il menu 'Plugin' in WordPress
3. Configura il plugin e crea le tue FAQ dal menu "FAQ Manager"
4. Usa il blocco Gutenberg "FAQ" o lo shortcode [custom_faq] per visualizzare le FAQ nelle tue pagine

## Utilizzo

### Shortcode Base
```
[custom_faq]
```

### Shortcode con parametri personalizzati
```
[custom_faq id="1,2,3" order="DESC" orderby="date"]
```

### Parametri disponibili
- `id`: IDs specifici delle FAQ da visualizzare (es. "1,2,3")
- `limit`: Numero massimo di FAQ da visualizzare (es. "5")
- `orderby`: Ordinamento (possibili valori: "title", "date", "rand")
- `order`: Direzione dell'ordinamento (possibili valori: "ASC", "DESC")

## Requisiti

- WordPress 5.0 o superiore
- PHP 7.0 o superiore

## Licenza

Questo plugin è distribuito sotto licenza GPLv3.

## Autore

Sviluppato da [Ettore Sartori](https://www.instagram.com/ettore_sartori/)
