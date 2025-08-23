document.addEventListener('DOMContentLoaded', function () {
    const container = document.querySelector('body');

    container.addEventListener('click', function (event) {
        const target = event.target;

        if (target && target.classList.contains('image-upload-button')) {
            const field = target.closest('.dibraco-image-field');
            const imagePreview = field.querySelector('.image-preview');
            const hiddenImageInput = field.querySelector('.dibraco_image_id_input');

            const frame = wp.media({
                title: 'Select Image',
                button: { text: 'Use this image' },
                multiple: false // Single image selection
            });

            frame.on('select', function () {
                const attachment = frame.state().get('selection').first(); 
                const imageUrl = attachment.attributes.url;

                imagePreview.src = imageUrl; 
                hiddenImageInput.value = attachment.id; 
                hiddenImageInput.setAttribute('data-url', imageUrl); 
                imagePreview.style.display = 'block'; 
            });

            frame.open(); 
        }

        if (target && target.classList.contains('dibraco_clear_image_button')) {
            const field = target.closest('.dibraco-image-field');
            const imagePreview = field.querySelector('.image-preview');
            const hiddenImageInput = field.querySelector('.dibraco_image_id_input');

            imagePreview.src = ''; 
            hiddenImageInput.value = ''; 
        }
    });
});


