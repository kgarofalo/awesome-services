jQuery(document).ready(function ($) {
    function appendAlphaToHex(hex, alpha) {
        hex = hex.replace('#', '');
        if (hex.length === 3) hex = hex.split('').map(x => x + x).join('');
        let alphaHex = Math.round(alpha * 255).toString(16).padStart(2, '0');
        return `#${hex}${alphaHex}`;
    }

    $(document).on('click', '.dibraco-fake-color-btn', function () {
        var $fakeBtn = $(this);
        var dataName = $fakeBtn.data('name');
        var $picker = $fakeBtn.closest('.dibraco-color-picker');
        var $hex8Input = $picker.find('.dibraco-hex8-input[data-name="' + dataName + '"]');
        var $colorInput = $picker.find('.wp-color-input[data-name="' + dataName + '"]');
        var $slider = $picker.find('.dibraco-slider[data-name="' + dataName + '"]');

        $fakeBtn.hide();
        $hex8Input.show();
        $colorInput.show();

        var hex8 = $hex8Input.val();
        var colorForPicker = hex8.length === 9 ? hex8.substring(0, 7) : hex8;
        $colorInput.val(colorForPicker);

        function getAlpha() { return parseFloat($slider.attr('data-alpha-value')); }

        function setAlpha(a) { $slider.attr('data-alpha-value', a); }

        function updateHex8() {
            const color = $colorInput.wpColorPicker('color');
            const alpha = getAlpha();
            const hex8 = appendAlphaToHex(color, alpha);
            $hex8Input.val(hex8);
            $colorInput.closest('.wp-picker-container').find('.wp-color-result').css('background-color', hex8);
            $slider.css('background-color', color); 
        }

        setTimeout(function() {
            $colorInput.wpColorPicker({ change: updateHex8 });
            updateHex8(); 

            var $container = $colorInput.closest('.wp-picker-container');
            $container.find('.wp-color-result').trigger('click');

            setTimeout(function(){
                var $irisInner = $container.find('.iris-picker-inner');
                if ($irisInner.length && $irisInner.find($slider).length === 0) {
                    $irisInner.append($slider);
                    $slider.show();
                }
                $slider.css({
                    'background-image': 'linear-gradient(45deg, #ccc 25%, transparent 25%, transparent 75%, #ccc 75%, #ccc), linear-gradient(45deg, #ccc 25%, transparent 25%, transparent 75%, #ccc 75%, #ccc)',
                    'background-size': '16px 16px',
                    'background-position': '0 0, 8px 8px'
                });
                var alphaForSlider = getAlpha();
                $slider.slider({
                    orientation: "vertical",
                    range: false,
                    min: 0,
                    max: 100,
                    value: alphaForSlider * 100,
                    slide: function (event, ui) {
                        setAlpha(ui.value / 100);
                        updateHex8();
                    }
                });
            }, 80);

        }, 0);
    });

});
