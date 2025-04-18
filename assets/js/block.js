/**
 * Custom FAQ Manager - Gutenberg Block
 */
(function(blocks, editor, components, i18n, element) {
    var el = element.createElement;
    var __ = i18n.__;
    var InspectorControls = editor.InspectorControls;
    var PanelBody = components.PanelBody;
    var SelectControl = components.SelectControl;
    var RangeControl = components.RangeControl;
    var CheckboxControl = components.CheckboxControl;
    var ServerSideRender = wp.serverSideRender;
    var useState = wp.element.useState;
    var useEffect = wp.element.useEffect;
    
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
            var setAttributes = props.setAttributes;
            var [availableFaqs, setAvailableFaqs] = useState([]);
            var [isLoading, setIsLoading] = useState(false);
            
            // Carica le FAQ disponibili all'avvio
            useEffect(function() {
                setIsLoading(true);
                
                wp.apiFetch({
                    path: '/wp/v2/custom_faq?per_page=100'
                }).then(function(faqs) {
                    var faqOptions = faqs.map(function(faq) {
                        return {
                            id: faq.id,
                            title: faq.title.rendered
                        };
                    });
                    setAvailableFaqs(faqOptions);
                    setIsLoading(false);
                }).catch(function(error) {
                    console.error('Errore nel caricamento delle FAQ:', error);
                    setIsLoading(false);
                });
            }, []);
            
            // Gestisce la selezione/deselezione di una FAQ
            function toggleFaq(faqId) {
                var selectedFaqs = attributes.selectedFaqs.slice();
                
                if (selectedFaqs.includes(faqId)) {
                    // Rimuovi FAQ dalla selezione
                    selectedFaqs = selectedFaqs.filter(function(id) {
                        return id !== faqId;
                    });
                } else {
                    // Aggiungi FAQ alla selezione
                    selectedFaqs.push(faqId);
                }
                
                setAttributes({ selectedFaqs: selectedFaqs });
            }
            
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
                ),
                el(
                    PanelBody,
                    {
                        title: __("Seleziona FAQ specifiche", "custom-faq-manager"),
                        initialOpen: false
                    },
                    el(
                        "div",
                        { className: "custom-faq-checkbox-list" },
                        isLoading ? 
                            el("p", {}, __("Caricamento FAQ...", "custom-faq-manager")) :
                            availableFaqs.length === 0 ?
                                el("p", {}, __("Nessuna FAQ disponibile.", "custom-faq-manager")) :
                                availableFaqs.map(function(faq) {
                                    return el(
                                        CheckboxControl,
                                        {
                                            key: faq.id,
                                            label: faq.title,
                                            checked: attributes.selectedFaqs.includes(faq.id),
                                            onChange: function() {
                                                toggleFaq(faq.id);
                                            }
                                        }
                                    );
                                }),
                        el(
                            "p",
                            { className: "faq-selection-help" },
                            __("Seleziona le FAQ specifiche da mostrare. Se nessuna FAQ è selezionata, verranno mostrate tutte (secondo le impostazioni di limite e ordinamento).", "custom-faq-manager")
                        )
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
                        __("Nota: l'anteprima potrebbe apparire diversa nel frontend.", "custom-faq-manager")
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
);