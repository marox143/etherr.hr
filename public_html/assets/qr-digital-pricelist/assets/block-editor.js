(function(blocks, element, components, editor) {
    var el = element.createElement;
    var RichText = editor.RichText;
    var InspectorControls = editor.InspectorControls;
    var PanelBody = components.PanelBody;
    var TextControl = components.TextControl;
    var ToggleControl = components.ToggleControl;
    var SelectControl = components.SelectControl;
    
    // Get categories for dropdown
    var categoriesOptions = [
        { label: 'All Categories', value: '' }
    ];
    
    // This would normally be fetched via API, but for simplicity we'll use static options
    // In a real implementation, you'd use wp.apiRequest or similar
    
    blocks.registerBlockType('qr-digital-pricelist/pricelist', {
        title: 'QR Digital Pricelist',
        icon: 'list-view',
        category: 'widgets',
        attributes: {
            category: {
                type: 'string',
                default: ''
            },
            showDisabled: {
                type: 'boolean',
                default: false
            }
        },
        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            
            // Inspector controls
            function getInspectorControls() {
                return el(
                    InspectorControls,
                    null,
                    el(
                        PanelBody,
                        { title: 'Pricelist Settings', initialOpen: true },
                        el(TextControl, {
                            label: 'Category Slug',
                            help: 'Leave empty to show all categories',
                            value: attributes.category,
                            onChange: function(value) {
                                setAttributes({ category: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: 'Show Disabled Items',
                            help: 'Include disabled categories, items, and variants',
                            checked: attributes.showDisabled,
                            onChange: function(value) {
                                setAttributes({ showDisabled: value });
                            }
                        })
                    )
                );
            }
            
            // Preview content
            function getPreviewContent() {
                return el(
                    'div',
                    { className: 'qr-digital-pricelist-block-preview' },
                    el(
                        'div',
                        { className: 'qr-digital-pricelist-preview-header' },
                        el(
                            'h3',
                            { className: 'qr-digital-pricelist-preview-title' },
                            'QR Digital Pricelist'
                        )
                    ),
                    el(
                        'div',
                        { className: 'qr-digital-pricelist-preview-content' },
                        el(
                            'div',
                            { className: 'qr-digital-pricelist-preview-category' },
                            el(
                                'h4',
                                { className: 'qr-digital-pricelist-preview-category-title' },
                                attributes.category ? 'Category: ' + attributes.category : 'All Categories'
                            ),
                            el(
                                'div',
                                { className: 'qr-digital-pricelist-preview-items' },
                                el(
                                    'div',
                                    { className: 'qr-digital-pricelist-preview-item' },
                                    el(
                                        'h5',
                                        { className: 'qr-digital-pricelist-preview-item-title' },
                                        'Sample Item 1'
                                    ),
                                    el(
                                        'div',
                                        { className: 'qr-digital-pricelist-preview-variants' },
                                        el(
                                            'span',
                                            { className: 'qr-digital-pricelist-preview-variant' },
                                            '0.5l - €5.00'
                                        ),
                                        el(
                                            'span',
                                            { className: 'qr-digital-pricelist-preview-variant' },
                                            '1.0l - €8.00'
                                        )
                                    )
                                ),
                                el(
                                    'div',
                                    { className: 'qr-digital-pricelist-preview-item' },
                                    el(
                                        'h5',
                                        { className: 'qr-digital-pricelist-preview-item-title' },
                                        'Sample Item 2'
                                    ),
                                    el(
                                        'div',
                                        { className: 'qr-digital-pricelist-preview-variants' },
                                        el(
                                            'span',
                                            { className: 'qr-digital-pricelist-preview-variant' },
                                            'shot - €3.50'
                                        )
                                    )
                                )
                            )
                        ),
                        attributes.showDisabled && el(
                            'div',
                            { className: 'qr-digital-pricelist-preview-note' },
                            'Note: Disabled items will be shown'
                        )
                    )
                );
            }
            
            return [
                getInspectorControls(),
                el(
                    'div',
                    { className: props.className },
                    getPreviewContent()
                )
            ];
        },
        save: function(props) {
            // This block is rendered server-side, so save returns null
            return null;
        }
    });
}(
    window.wp.blocks,
    window.wp.element,
    window.wp.components,
    window.wp.editor
));
