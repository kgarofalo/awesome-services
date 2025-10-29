(function($) {
    'use strict';

    const imageFields = document.querySelectorAll('.dibraco-image-field');
    const wysiwygs = document.querySelectorAll('.dibraco-wysiwyg');
    const repeaters = document.querySelectorAll('.dibraco-repeater-wrapper');
    
    if (imageFields.length === 0 && wysiwygs.length === 0) {
        return;
    }
    
   function initializeImageField(imageFieldDiv) {
    const mediaButton = imageFieldDiv.querySelector('.media-button');
    const clearButton = imageFieldDiv.querySelector('.clear_image_button');
    const imagePreview = imageFieldDiv.querySelector('.image-preview');
    const imagePreviewSrc = imagePreview.src;
    const hiddenInput = imageFieldDiv.querySelector('.dibraco_image_id_input');
    
    const currentImageUrl = hiddenInput.value;
    const currentImageId = imagePreview.getAttribute('image-id');
    
mediaButton.addEventListener('click', function() {
    const currentImageId = imagePreview.getAttribute('image-id');
    
    let mediaFrame = wp.media({
        title: 'Select or Upload Image',
        button: {
            text: 'Use this image'
        },
        multiple: false,
        library: {
            type: 'image',
        },
        frame: 'post',     
        state: 'insert'  
    });
    
    mediaFrame.on('open', function() {
        let modalImage = currentImageId;  // Capture it here
        if (modalImage) {
            const selection = mediaFrame.state().get('selection');
            const att = wp.media.attachment(modalImage);
            att.fetch();
            selection.add(att ? [att] : []);
        }
    });
    
    mediaFrame.on('insert', function() {  // Changed from 'select' to 'insert'
    const selectedAttachment = mediaFrame.state().get('selection').first().toJSON();
    hiddenInput.value = selectedAttachment.url;
    imagePreview.setAttribute('src', selectedAttachment.url);
    imagePreview.setAttribute('image-id', selectedAttachment.id);
        mediaFrame.detach();

});
    
    mediaFrame.open();
});
   
   clearButton.addEventListener('click', function() {
    imagePreview.src = '';
    hiddenInput.value = '';
    imagePreview.setAttribute('image-id', '');
    });
   
}
     function initializeRepeater(repeater) {
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1) {
                    if (node.classList && node.classList.contains('dibraco-image-field')) {
                        initializeImageField(node);
                    }
                    if (node.classList && node.classList.contains('dibraco-wysiwyg')) {
                        initializeWysiwyg(node);
                    }
                    const newImageFields = node.querySelectorAll('.dibraco-image-field');
                    const newWysiwigs = node.querySelectorAll('.dibraco-wysiwyg');
                    
                    newImageFields.forEach(initializeImageField);
                    newWysiwigs.forEach(initializeWysiwyg);
                }
            });
        });
    });
    
    observer.observe(repeater, {
        childList: true,
        subtree: true
    });
}
 
    
    if (imageFields.length > 0) {
        imageFields.forEach(initializeImageField);
    }
    
   
 
        function initializeWysiwyg(wysiwygDiv) {
        const textarea = wysiwygDiv.querySelector('textarea');
        const toolbar = wysiwygDiv.querySelector('.wysiwyg-toolbar');
        const visualTab = toolbar.querySelector('.visual-tab');
        const textTab = toolbar.querySelector('.text-tab');
        const shortcodeButtons = toolbar.querySelectorAll('.insert-shortcode');
        
        tinymce.init({
            selector: '#' + textarea.id, 
            forced_root_block: false,
            menubar: false,
            branding: false,
             content_css: false,      
            skin: false,            
            plugins: 'lists wordpress',
            toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | link wp_add_media'
        });
   
        visualTab.addEventListener('click', function() {
            const editor = tinymce.get(textarea.id);
            const iframe = document.getElementById(textarea.id + '_ifr');
            
            editor.setContent(textarea.value);
            textarea.style.display = 'none';
            iframe.style.display = 'block';
            
            visualTab.classList.add('active');
            textTab.classList.remove('active');
        });
        
        // Handle Text tab
        textTab.addEventListener('click', function() {
            const editor = tinymce.get(textarea.id);
            const iframe = document.getElementById(textarea.id + '_ifr');
            
            textarea.value = editor.getContent();
            iframe.style.display = 'none';
            textarea.style.display = 'block';
            
            textTab.classList.add('active');
            visualTab.classList.remove('active');
        });
        
        // Handle shortcode buttons
        shortcodeButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                const shortcode = this.getAttribute('data-shortcode');
                const iframe = document.getElementById(textarea.id + '_ifr');
                
                if (iframe.style.display === 'none') {
                    // Text mode - insert into textarea
                    const cursorPos = textarea.selectionStart;
                    const textBefore = textarea.value.substring(0, cursorPos);
                    const textAfter = textarea.value.substring(cursorPos);
                    textarea.value = textBefore + shortcode + textAfter;
                    textarea.selectionStart = textarea.selectionEnd = cursorPos + shortcode.length;
                    textarea.focus();
                } else {
                    // Visual mode - insert into editor
                    const editor = tinymce.get(textarea.id);
                    editor.execCommand('mceInsertContent', false, shortcode);
                }
            });
        });
        }
 
     if (wysiwygs.length > 0) {
        wysiwygs.forEach(initializeWysiwyg);
    }
   

    if (repeaters.length > 0) {
      repeaters.forEach(initializeRepeater);
        
    }
    
})(jQuery); 
/*
(function($) {
     'use strict';
    if (document.querySelectorAll('.dibraco-image-field').length === 0) {
        return;
    }
    
    function initializeImageField(container) {
        const dataName = container.getAttribute('data-name');
        const mediaButton = container.querySelector(`[data-name="${dataName}-media-button"]`);
        const clearButton = container.querySelector(`[data-name="${dataName}-clear-image-button"]`);
        const hiddenInput = document.getElementById(dataName);
        const preview = document.getElementById(`${dataName}-preview`);
        const imageID = preview.getAttribute('image_id');
        
    mediaButton.addEventListener('click', function() {
    
    wp.media.editor.open();
    
    wp.media.editor.send.attachment = function(props, attachment) {
        // This code runs LATER when they click "Select"
        hiddenInput.value = attachment.url;
        preview.setAttribute('src', attachment.url);
        preview.setAttribute('image_id', attachment.id);
    };
    
    
    if (imageID !== '' && wp.media.frame) {
        wp.media.frame.on('open', function() {
            const attachment = wp.media.attachment(imageID);
            attachment.fetch().done(function() {
                wp.media.frame.state().get('selection').add(attachment);
            });
        });
    }
   });
        
        clearButton.addEventListener('click', () => {
            console.log('Clear button clicked for:', dataName);
            hiddenInput.value = '';
            preview.src = '';
            preview.setAttribute('image_id', '');
            console.log('Field cleared');
        });
 
    }
    document.addEventListener('DOMContentLoaded', () => {
        console.log('DOM Content Loaded');
        
        document.querySelectorAll('.dibraco-image-field').forEach(container => {
            initializeImageField(container);
        });
        
        if (document.querySelectorAll('.dibraco-repeater-wrapper').length > 0) {
            console.log('Setting up mutation observer for repeaters');
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    mutation.addedNodes.forEach((node) => {
                        if (node.classList && node.classList.contains('dibraco-image-field')) {
                            console.log('New image field detected in repeater');
                            initializeImageField(node);
                        }
                    });
                });
            });
            
            document.querySelectorAll('.dibraco-repeater-wrapper').forEach(wrapper => {
                observer.observe(wrapper, {
                    childList: true,
                    subtree: true
                });
            });
        }
    });
})(jQuery);
*/
        