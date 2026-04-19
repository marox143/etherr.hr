jQuery(document).ready(function($) {
    // Variants management
    var variantIndex = 0;
    
    // Find existing variants on page load
    function initVariants() {
        var existingVariants = $('.qr-variant-row');
        if (existingVariants.length > 0) {
            variantIndex = Math.max.apply(Math, existingVariants.map(function() {
                return parseInt($(this).data('index')) || 0;
            }).get()) + 1;
        }
    }
    
    // Add variant
    $('#qr-add-variant').on('click', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'qr_digital_pricelist_add_variant',
                index: variantIndex,
                nonce: qrDigitalPricelistAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#qr-variants-container').append(response.data.html);
                    variantIndex++;
                }
            }
        });
    });
    
    // Remove variant
    $(document).on('click', '.qr-remove-variant', function(e) {
        e.preventDefault();
        $(this).closest('.qr-variant-row').remove();
    });
    
    // Initialize on page load
    initVariants();
    
    // Reorder variants with drag and drop (optional enhancement)
    if ($.ui && $.ui.sortable) {
        $('#qr-variants-container').sortable({
            items: '.qr-variant-row',
            handle: '.qr-variant-field:first-child',
            placeholder: 'qr-variant-placeholder',
            update: function(event, ui) {
                // Update sort order values
                $('#qr-variants-container .qr-variant-row').each(function(index) {
                    $(this).find('input[name$="[sort_order]"]').val(index);
                    $(this).data('index', index);
                    
                    // Update name attributes to match new index
                    $(this).find('input, select').each(function() {
                        var name = $(this).attr('name');
                        if (name && name.includes('qr_variants[')) {
                            var newName = name.replace(/qr_variants\[\d+\]/, 'qr_variants[' + index + ']');
                            $(this).attr('name', newName);
                        }
                    });
                });
            }
        });
    }
    
    // Settings page enhancements
    $('.qr-digital-pricelist-settings input[type="text"]').on('input', function() {
        var $this = $(this);
        var maxLength = parseInt($this.attr('maxlength')) || 0;
        var value = $this.val();
        
        if (maxLength > 0 && value.length > maxLength) {
            $this.val(value.substring(0, maxLength));
        }
    });
    
    // Units page enhancements
    $('.qr-digital-pricelist-units input[type="text"]').on('input', function() {
        var $this = $(this);
        var id = $this.attr('id');
        
        if (id === 'unit_slug') {
            // Auto-format slug
            var value = $this.val().toLowerCase().replace(/[^a-z0-9_-]/g, '');
            $this.val(value);
        }
    });
    
    // Category and item list enhancements
    $(document).on('change', '.wp-list-table .check-column input[type="checkbox"]', function() {
        var $table = $(this).closest('.wp-list-table');
        var $checked = $table.find('.check-column input[type="checkbox"]:checked');
        var $bulkActions = $table.find('#bulk-action-selector-top');
        
        if ($checked.length > 0) {
            $bulkActions.prop('disabled', false);
        } else {
            $bulkActions.prop('disabled', true);
        }
    });
    
    // Quick edit enhancements
    if (typeof inlineEditPost !== 'undefined') {
        var originalInlineEditPost = inlineEditPost.edit;
        
        inlineEditPost.edit = function(id) {
            originalInlineEditPost.apply(this, arguments);
            
            var $row = $('#edit-' + id);
            var postId = id.replace('post-', '');
            
            // Add QR pricelist fields to quick edit if applicable
            if ($row.find('.qr-menu-item-quick-edit').length === 0) {
                var $enabledField = $('<label class="qr-menu-item-quick-edit">' + 
                    '<span class="title">Enabled</span>' +
                    '<input type="checkbox" name="qr_enabled_quick" value="1" />' +
                    '</label>');
                
                var $sortOrderField = $('<label class="qr-menu-item-quick-edit">' + 
                    '<span class="title">Sort Order</span>' +
                    '<input type="number" name="qr_sort_order_quick" value="0" step="1" min="0" />' +
                    '</label>');
                
                $row.find('.inline-edit-col-right').append($enabledField).append($sortOrderField);
                
                // Load current values
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'qr_digital_pricelist_get_item_meta',
                        post_id: postId,
                        nonce: qrDigitalPricelistAdmin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $row.find('input[name="qr_enabled_quick"]').prop('checked', response.data.enabled);
                            $row.find('input[name="qr_sort_order_quick"]').val(response.data.sort_order);
                        }
                    }
                });
            }
        };
    }
    
    // Dashboard page enhancements
    $('.qr-digital-pricelist-dashboard .quick-action').on('click', function(e) {
        var $this = $(this);
        var href = $this.attr('href');
        
        // Add loading state
        $this.addClass('loading');
        
        // Remove loading state after a short delay
        setTimeout(function() {
            $this.removeClass('loading');
        }, 1000);
    });
    
    // Copy shortcode to clipboard
    $('.qr-digital-pricelist-shortcode-copy').on('click', function(e) {
        e.preventDefault();
        
        var $this = $(this);
        var shortcode = $this.data('shortcode');
        
        if (navigator.clipboard) {
            navigator.clipboard.writeText(shortcode).then(function() {
                $this.text('Copied!');
                setTimeout(function() {
                    $this.text('Copy');
                }, 2000);
            });
        }
    });
    
    // Initialize tooltips
    if ($.fn.tooltip) {
        $('.qr-digital-pricelist-tooltip').tooltip({
            position: { my: 'left+15 center', at: 'right center' }
        });
    }
    
    // Form validation
    $('.qr-digital-pricelist-form').on('submit', function(e) {
        var $form = $(this);
        var isValid = true;
        
        $form.find('input[required], select[required]').each(function() {
            var $field = $(this);
            var value = $field.val().trim();
            
            if (value === '') {
                $field.addClass('error');
                isValid = false;
            } else {
                $field.removeClass('error');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            
            // Show error message
            if (!$form.find('.qr-digital-pricelist-form-error').length) {
                $form.prepend('<div class="qr-digital-pricelist-form-error notice notice-error"><p>Please fill in all required fields.</p></div>');
            }
        }
    });
    
    // Remove error styling on input
    $('.qr-digital-pricelist-form input, .qr-digital-pricelist-form select').on('input change', function() {
        $(this).removeClass('error');
    });
});
