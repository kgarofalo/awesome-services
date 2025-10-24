(function($) {
    'use strict';
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.dibraco-image-field').forEach(container => {
            const dataName = container.getAttribute('data-name');
            
            const mediaButton = container.querySelector(`[data-name="${dataName}-media-button"]`);
            const clearButton = container.querySelector(`[data-name="${dataName}-clear-image-button"]`);
            const input = document.getElementById(dataName);
            const preview = document.getElementById(`${dataName}-preview`);
            
            mediaButton.addEventListener('click', (e) => {
                e.preventDefault();
                
                console.log('Opening media library for:', dataName);
                
                const currentInput = input;
                const currentPreview = preview;
                
                wp.media.editor.send.attachment = function(props, attachment) {
                    console.log('GOT IMAGE!', attachment);
                    
                    currentInput.value = attachment.url;
                    currentPreview.src = attachment.url;
                    currentPreview.setAttribute('image_id', attachment.id);
                };
                wp.media.editor.open();
            });
            
            clearButton.addEventListener('click', () => {
                input.value = '';
                preview.src = '';
            });
        });
    });
    
})(jQuery);
jQuery(document).ready(function($) {
    if (typeof tinymce !== 'undefined') {
        document.querySelectorAll('.dibraco-wysiwyg').forEach((container, index) => {
            const textarea = container.querySelector('textarea');
            
            setTimeout(() => {
                tinymce.init({
                    target: textarea,
                    plugins: 'lists link fullscreen image',
                    branding: false,
                    toolbar: 'undo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | link image | telephonelink serviceareas',
                    height: 220,
                    forced_root_block: false,
                    menubar: false,
                    setup: function(editor) {
                        if (editor.ui && editor.ui.registry) {
                            editor.ui.registry.addButton('telephonelink', {
                                tooltip: 'Telephone Link Shortcode',
                                icon: 'phone',
                                onAction: function() { 
                                    editor.execCommand('mceInsertContent', false, '[telephonelink]');
                                }
                            });
                            editor.ui.registry.addButton('serviceareas', {
                                tooltip: 'Insert Service Areas Shortcode',
                                icon: 'piglet',
                                onAction: function() {
                                    editor.execCommand('mceInsertContent', false, '[service_areas]');
                                }
                            });
                        }
                    }
                });
            }, index * 50);
        });
    }
});