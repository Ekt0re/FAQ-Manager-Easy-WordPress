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
    var ServerSideRender = wp.serverSideRender;
    
    // Register the block
    blocks.registerBlockType('custom-faq-manager/faq-block', {
        title: __('FAQ', 'custom-faq-manager'),
        icon: 'format-status',
        category: 'widgets',
        keywords: [
            __('faq', 'custom-faq-manager'),
            __('domande', 'custom-faq-manager'),
            __('frequent', 'custom-faq-manager')
        ],
        
        // Block attributes
        attributes: {
            limit: {
                type: 'number',
                default: -1
            },
            orderby: {
                type: 'string',
                default: 'title'
            },
            order: {
                type: 'string',
                default: 'ASC'
            }
        },
        
        // Editor component
        edit: function(props) {
            var attributes = props.attributes;
            
            // Inspector controls for block options
            var inspectorControls = el(
                InspectorControls,
                { key: 'inspector' },
                el(
                    PanelBody,
                    {
                        title: __('Impostazioni FAQ', 'custom-faq-manager'),
                        initialOpen: true
                    },
                    el(
                        RangeControl,
                        {
                            label: __('Numero di FAQ da mostrare', 'custom-faq-manager'),
                            value: attributes.limit,
                            onChange: function(value) {
                                props.setAttributes({ limit: value === undefined ? -1 : value });
                            },
                            min: -1,
                            max: 50,
                            help: __('-1 per mostrare tutte le FAQ', 'custom-faq-manager')
                        }
                    ),
                    el(
                        SelectControl,
                        {
                            label: __('Ordinamento', 'custom-faq-manager'),
                            value: attributes.orderby,
                            options: [
                                { label: __('Titolo', 'custom-faq-manager'), value: 'title' },
                                { label: __('Data', 'custom-faq-manager'), value: 'date' }
                            ],
                            onChange: function(value) {
                                props.setAttributes({ orderby: value });
                            }
                        }
                    ),
                    el(
                        SelectControl,
                        {
                            label: __('Direzione ordinamento', 'custom-faq-manager'),
                            value: attributes.order,
                            options: [
                                { label: __('Ascendente (A-Z)', 'custom-faq-manager'), value: 'ASC' },
                                { label: __('Discendente (Z-A)', 'custom-faq-manager'), value: 'DESC' }
                            ],
                            onChange: function(value) {
                                props.setAttributes({ order: value });
                            }
                        }
                    )
                )
            );
            
            // Block content in editor
            return [
                inspectorControls,
                el(
                    'div',
                    { className: props.className },
                    el(
                        'div',
                        { className: 'faq-block-title' },
                        __('Blocco FAQ', 'custom-faq-manager')
                    ),
                    el(
                        'div',
                        { className: 'faq-block-description' },
                        __('Questo blocco visualizzerà le tue FAQ. Usa i controlli nella barra laterale per personalizzare la visualizzazione.', 'custom-faq-manager')
                    ),
                    el(
                        ServerSideRender,
                        {
                            block: 'custom-faq-manager/faq-block',
                            attributes: attributes
                        }
                    ),
                    el(
                        'div',
                        { className: 'faq-preview-notice' },
                        __('Nota: l\'anteprima potrebbe apparire diversa nel frontend.', 'custom-faq-manager')
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