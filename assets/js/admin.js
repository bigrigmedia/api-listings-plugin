jQuery(document).ready(function($){
    // Initialize color picker
    $('.api-plugin-color-picker').wpColorPicker();
    
    // Initialize range sliders
    $('.api-plugin-range-slider').on('input', function() {
        var slider = $(this);
        var value = slider.val();
        var rangeDisplay = slider.siblings('.range-display').find('.range-value');
        rangeDisplay.text(value);
    });
    
    // Handle image upload
    $(document).on('click', '.api-plugin-upload-image', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var fieldName = button.data('field');
        var hiddenField = $('#' + fieldName);
        var previewContainer = button.siblings('.image-preview');
        
        // Create the media frame
        var frame = wp.media({
            title: 'Select Image',
            button: {
                text: 'Use this image'
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });
        
        // When an image is selected in the media frame
        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            
            // Set the attachment ID in the hidden field
            hiddenField.val(attachment.id);
            
            // Display the image preview
            var imageUrl = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
            previewContainer.html('<img src="' + imageUrl + '" style="max-width: 200px; max-height: 200px; display: block;" />');
            
            // Show remove button if it doesn't exist
            if (!button.siblings('.api-plugin-remove-image').length) {
                button.after(' <button type="button" class="button api-plugin-remove-image" data-field="' + fieldName + '">Remove Image</button>');
            }
        });
        
        // Open the media frame
        frame.open();
    });
    
    // Handle image removal
    $(document).on('click', '.api-plugin-remove-image', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var fieldName = button.data('field');
        var hiddenField = $('#' + fieldName);
        var previewContainer = button.siblings('.image-preview');
        
        // Clear the hidden field
        hiddenField.val('');
        
        // Clear the preview
        previewContainer.empty();
        
        // Remove the remove button
        button.remove();
    });
});