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
        
        // Initialize theme selection
        initThemeSelection();
        
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
     * Initialize theme selection functionality
     */
    function initThemeSelection() {
        // Load theme presets from the hidden field
        var themesData = JSON.parse($('#ajl_wpsps_theme_presets').val() || '{}');
        
        // Handle theme selection
        $('.ajl-wpsps-theme-card').on('click', function(e) {
            var $card = $(this);
            var themeId = $card.data('theme-id');
            
            // Skip if clicking on the already selected theme or on the button
            if ($card.hasClass('selected') || $(e.target).hasClass('ajl-wpsps-theme-select')) {
                return;
            }
            
            // Update UI
            $('.ajl-wpsps-theme-card').removeClass('selected');
            $card.addClass('selected');
            
            // Check the radio button
            $card.find('input[type="radio"]').prop('checked', true);
            
            // Update button text
            $('.ajl-wpsps-theme-select').text(ajlWpspsData.selectText);
            $card.find('.ajl-wpsps-theme-select').text(ajlWpspsData.selectedText);
            
            // Apply theme settings if theme exists in our data
            if (themesData[themeId]) {
                applyThemeSettings(themeId, themesData[themeId]);
            }
        });
        
        // Handle the Select/Selected button click
        $('.ajl-wpsps-theme-select').on('click', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $card = $button.closest('.ajl-wpsps-theme-card');
            
            // Skip if this theme is already selected
            if ($card.hasClass('selected')) {
                return;
            }
            
            // Trigger the card click to select the theme
            $card.trigger('click');
        });
    }
    
    /**
     * Apply theme settings to form fields
     */
    function applyThemeSettings(themeId, theme) {
        // Get complete theme settings
        var themeSettings = getFullThemeSettings(themeId, theme);
        
        // Apply color settings with a brief delay to ensure color pickers have initialized
        setTimeout(function() {
            // Form container background
            updateColorPicker('#ajl_wpsps_form_container_background_color', themeSettings.form_container_background_color);
            
            // Input background
            updateColorPicker('#ajl_wpsps_background_color', themeSettings.background_color);
            
            // Text color
            updateColorPicker('#ajl_wpsps_text_color', themeSettings.text_color);
            
            // Label text color (if set)
            if (themeSettings.label_text_color) {
                updateColorPicker('#ajl_wpsps_label_text_color', themeSettings.label_text_color);
            }
            
            // Input text color (if set)
            if (themeSettings.input_text_color) {
                updateColorPicker('#ajl_wpsps_input_text_color', themeSettings.input_text_color);
            }
            
            // Primary color
            updateColorPicker('#ajl_wpsps_primary_color', themeSettings.primary_color);
            
            // Button background
            updateColorPicker('#ajl_wpsps_button_background_color', themeSettings.button_background_color);
            
            // Button text color
            updateColorPicker('#ajl_wpsps_button_text_color', themeSettings.button_text_color);
            
            // Button hover background
            updateColorPicker('#ajl_wpsps_button_hover_background_color', themeSettings.button_hover_background_color);
            
            // Update preview elements
            $('.ajl-wpsps-button-preview').css('background-color', themeSettings.button_background_color);
            $('.ajl-wpsps-button-preview').css('color', themeSettings.button_text_color);
            $('.ajl-wpsps-button-hover').css('background-color', themeSettings.button_hover_background_color);
            
            // Update numeric values
            $('#ajl_wpsps_border_radius').val(themeSettings.border_radius).trigger('change');
            $('#ajl_wpsps_label_font_size').val(themeSettings.label_font_size);
            $('#ajl_wpsps_input_font_size').val(themeSettings.input_font_size);
            
            // Update selects
            $('#ajl_wpsps_label_font_weight').val(themeSettings.label_font_weight);
            
            // Update border radius preview
            $('.ajl-wpsps-radius-box').css('border-radius', themeSettings.border_radius + 'px');
            
            // Show a notification
            showThemeAppliedNotice(theme.name);
            
        }, 200);
    }
    
    /**
     * Get full settings for a theme with default values for all fields
     */
    function getFullThemeSettings(themeId, theme) {
        // Default settings for all themes
        var defaults = {
            form_container_background_color: '#ffffff',
            background_color: '#ffffff',
            text_color: '#32325d',
            label_text_color: '', // Will inherit from text_color if empty
            input_text_color: '', // Will inherit from text_color if empty
            primary_color: '#0f8569',
            button_background_color: '#0f8569',
            button_text_color: '#ffffff',
            button_hover_background_color: '#0e7c62',
            border_radius: 3, // Default should be 3px
            label_font_size: 14,
            input_font_size: 16,
            label_font_weight: 'normal'
        };
        
        // Theme-specific mappings based on the theme's color palette
        var themeSettings = {
            // Map the basic theme colors to specific form elements
            form_container_background_color: theme.colors.background,
            background_color: theme.colors.background,
            text_color: theme.colors.text,
            primary_color: theme.colors.primary,
            button_background_color: theme.colors.primary,
            button_text_color: '#ffffff', // Usually white for buttons
            button_hover_background_color: theme.colors.secondary
        };
        
        // Set specific colors for specific themes
        if (themeId === 'midnight' || themeId === 'monochrome') {
            // For dark themes, ensure input text is visible on dark backgrounds
            if (theme.colors.background.toLowerCase() === '#34495e' || 
                theme.colors.background.toLowerCase() === '#2c3e50') {
                themeSettings.input_text_color = '#ffffff'; // White text for dark backgrounds
            }
        }
        
        // Add theme-specific layout values
        switch (themeId) {
            case 'midnight':
            case 'monochrome':
                themeSettings.border_radius = 0; // Sharp corners
                break;
            case 'sunset':
            case 'coral':
                themeSettings.border_radius = 5; // More rounded
                break;
            case 'ocean':
            case 'lavender':
                themeSettings.border_radius = 4; // Slightly rounded
                break;
            case 'minimal':
                themeSettings.border_radius = 2; // Subtle roundness
                break;
            case 'default':
                themeSettings.border_radius = 3; // Default roundness
                break;
            default:
                themeSettings.border_radius = 3; // Default roundness
        }
        
        // Add theme-specific typography values
        switch (themeId) {
            case 'midnight':
            case 'monochrome':
                themeSettings.label_font_weight = 'bold';
                break;
            case 'minimal':
                themeSettings.label_font_weight = '300'; // Light weight
                break;
            case 'forest':
            case 'ocean':
                themeSettings.label_font_weight = '500'; // Medium weight
                break;
            default:
                themeSettings.label_font_weight = 'normal';
        }
        
        // Merge with defaults
        return $.extend({}, defaults, themeSettings);
    }
    
    /**
     * Update a color picker input with a new value
     */
    function updateColorPicker(selector, color) {
        var $input = $(selector);
        
        // Set the input value
        $input.val(color);
        
        // Update the color picker UI
        if ($input.hasClass('ajl-color-picker') && $input.wpColorPicker) {
            try {
                // Force a refresh of the picker by triggering change first
                $input.trigger('change');
                $input.wpColorPicker('color', color);
                
                // Additionally update any preview elements
                if ($input.attr('id') === 'ajl_wpsps_button_background_color') {
                    $('.ajl-wpsps-button-preview').css('background-color', color);
                } else if ($input.attr('id') === 'ajl_wpsps_button_text_color') {
                    $('.ajl-wpsps-button-preview').css('color', color);
                } else if ($input.attr('id') === 'ajl_wpsps_button_hover_background_color') {
                    $('.ajl-wpsps-button-hover').css('background-color', color);
                } else if ($input.attr('id') === 'ajl_wpsps_form_container_background_color') {
                    // Preview container background - intentionally left empty for now
                }
            } catch (e) {
                console.warn('Failed to update color picker:', e);
                // Fallback approach if WP Color Picker fails
                $input.val(color).trigger('change');
            }
        }
    }
    
    /**
     * Show a notice when a theme has been applied
     */
    function showThemeAppliedNotice(themeName) {
        // Create notice element
        var $notice = $('<div class="notice notice-success is-dismissible theme-applied-notice">' +
                           '<p>' + ajlWpspsData.themeAppliedMessage.replace('%s', themeName) + '</p>' +
                           '<button type="button" class="notice-dismiss"></button>' +
                        '</div>');
        
        // Remove any existing notices
        $('.theme-applied-notice').remove();
        
        // Add to the page
        $('.ajl-wpsps-tabs-container').prepend($notice);
        
        // Set up dismiss button
        $notice.find('.notice-dismiss').on('click', function() {
            $notice.fadeOut(300, function() { $notice.remove(); });
        });
        
        // Auto-remove after 3 seconds
        setTimeout(function() {
            $notice.fadeOut(300, function() { $notice.remove(); });
        }, 3000);
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
        var $input = $('#ajl_wpsps_border_radius');
        
        // Initialize preview on page load with whatever value is in the input
        var initialRadius = $input.val() || '0';
        $('.ajl-wpsps-radius-box').css('border-radius', initialRadius + 'px');
        
        // Update border radius preview when the input changes
        $input.on('input change', function() {
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
                        $input.val('3');
                    }
                }
            });
            
            return true;
        });
    }

})(jQuery); 