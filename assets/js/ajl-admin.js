/**
 * WP Simple Pay Styles Admin JS
 *
 * Handles tab switching, color picker initialization, and reset functionality.
 */
(function($) {
    'use strict';

    // Run when the DOM is ready
    $(document).ready(function() {
        // Initialize the tabbed interface
        initTabs();
        
        // Initialize color pickers
        initColorPickers();
        
        // Initialize border radius preview
        initBorderRadiusPreview();
        
        // Initialize the reset button
        initResetButton();
        
        // Prevent form validation errors for hidden tabs
        preventHiddenTabValidation();
    });

    /**
     * Initialize the tabbed interface
     */
    function initTabs() {
        $('.ajl-wpsps-tab-button').on('click', function(e) {
            e.preventDefault();
            
            // Get the tab ID
            var tabId = $(this).data('tab');
            
            // Remove active class from all tabs and panels
            $('.ajl-wpsps-tab-button').removeClass('active');
            $('.ajl-wpsps-tab-panel').removeClass('active');
            
            // Add active class to current tab and panel
            $(this).addClass('active');
            $('[data-tab-content="' + tabId + '"]').addClass('active');
        });
    }

    /**
     * Initialize WordPress color pickers with custom options
     */
    function initColorPickers() {
        // Store original values to restore on form submit
        $('.ajl-color-picker').each(function() {
            $(this).data('original-value', $(this).val());
        });
        
        // Configure standard color pickers
        var colorPickerOptions = {
            // Update button previews when color changes
            change: function(event, ui) {
                var color = ui.color.toString();
                var $input = $(event.target);
                
                // Update button previews if this is a button-related color picker
                if ($input.attr('id') === 'ajl_wpsps_button_background_color') {
                    $('.ajl-wpsps-button-preview').css('background-color', color);
                } else if ($input.attr('id') === 'ajl_wpsps_button_text_color') {
                    $('.ajl-wpsps-button-preview').css('color', color);
                } else if ($input.attr('id') === 'ajl_wpsps_button_hover_background_color') {
                    $('.ajl-wpsps-button-hover').css('background-color', color);
                }
            },
            // Ensure the value gets updated when the color is cleared
            clear: function(event) {
                $(event.target).val('');
                $(event.target).trigger('change');
            }
        };
        
        // Initialize alpha-enabled color pickers
        $('[data-alpha-enabled="true"]').wpColorPicker({
            palettes: true,
            alpha: true,
            change: colorPickerOptions.change,
            clear: colorPickerOptions.clear
        });
        
        // Initialize standard color pickers
        $('.ajl-color-picker:not([data-alpha-enabled="true"])').wpColorPicker({
            palettes: true,
            change: colorPickerOptions.change,
            clear: colorPickerOptions.clear
        });
    }
    
    /**
     * Initialize the border radius preview
     */
    function initBorderRadiusPreview() {
        // Update border radius preview when the input changes
        $('#ajl_wpsps_border_radius').on('input change', function() {
            var radius = $(this).val() + 'px';
            $('.ajl-wpsps-radius-box').css('border-radius', radius);
        });
    }
    
    /**
     * Initialize the reset button functionality
     */
    function initResetButton() {
        $('#ajl-wpsps-reset-styles').on('click', function(e) {
            e.preventDefault();
            
            // Show confirmation dialog
            if (confirm(ajlWpspsData.resetConfirmMessage)) {
                // Create a hidden input to signal reset action
                var $resetInput = $('<input>').attr({
                    type: 'hidden',
                    name: 'ajl_wpsps_reset',
                    value: 'true'
                });
                
                // Add it to the form and submit
                $(this).closest('form').append($resetInput);
                $('#publish').click(); // Trigger the main form submission
            }
        });
    }
    
    /**
     * Prevent validation errors for fields in hidden tabs
     */
    function preventHiddenTabValidation() {
        // Handle form submission
        $('form#post').on('submit', function() {
            // Make sure all numeric fields have valid values before submitting
            $('.ajl-wpsps-input-with-unit input[type="number"]').each(function() {
                var $input = $(this);
                if ($input.val() === '' || isNaN(parseInt($input.val()))) {
                    // Set to default value if empty or invalid
                    if ($input.attr('id') === 'ajl_wpsps_label_font_size') {
                        $input.val('14');
                    } else if ($input.attr('id') === 'ajl_wpsps_input_font_size') {
                        $input.val('16');
                    } else if ($input.attr('id') === 'ajl_wpsps_border_radius') {
                        $input.val('4');
                    }
                }
            });
            
            return true;
        });
    }

})(jQuery); 