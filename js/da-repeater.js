function getFragmentsAndQuerySelector(button) {
    var fragmentOne = button.attr('data-name');
    var fragmentTwo = fragmentOne.replace(/-/g, '_');
    var parentFragment = button.attr('data-parent-name') || null;
      var querySelector = '[data-name="' + fragmentOne + '"]' +  
        (parentFragment ? '[data-parent-name="' + parentFragment + '"]' : '');
    var fieldName = button.attr('data-name'); 
    var rowCountInput = 'input[name="' + fieldName + '_row_count"]';
    var regexOne = new RegExp("(" + fragmentOne.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ")\\[(\\d+)\\](?=\\[)");
    var regexTwo = new RegExp("(" + fragmentTwo.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ")\\[(\\d+)\\](?=\\[)");

    return { fragmentOne, fragmentTwo, parentFragment, querySelector, regexOne, regexTwo, rowCountInput };
}

   
jQuery(document).ready(function($) {    

    const repeatersOnPage = $('.dibraco-repeater-wrapper');

        $(document).on('click', '.dibraco-repeater-wrapper .add-row-button', function() {
        var button = $(this);
        var { fragmentOne, fragmentTwo, parentFragment, querySelector, rowCountInput, regexOne, regexTwo } = getFragmentsAndQuerySelector(button);
        var rowCountInputElement = $('.repeater-input[data-name="' + fragmentOne + '"]');
        var rowCount = parseInt(rowCountInputElement.val(), 10);
        rowCountInputElement.val(rowCount + 1); 
        var repeaterRows = button.closest('.dibraco-repeater-wrapper').find('.repeater-rows' + querySelector).first();
        var lastRepeaterRow = repeaterRows.find('.repeater-row' + querySelector).last();
        var lastRowIndex = parseInt(lastRepeaterRow.attr('data-row-index'), 10) || 0;   
        var newIndex = lastRowIndex + 1;
        var clonedRow = lastRepeaterRow.clone(true);
    clonedRow.attr('data-row-index', newIndex);
        clonedRow.find('input, select, textarea').each(function() {
          if (this.type !== 'checkbox' && this.type !== 'radio') {

        $(this).val(''); 
        }
        });
    clonedRow.find('label').each(function() {
    var label = $(this);
    var labelHtml = label.html();
    var indexRegex = new RegExp('\\b' + lastRowIndex + '\\b', 'g');
    var newHtml = labelHtml.replace(indexRegex, newIndex);
    label.html(newHtml); // Set full HTML back
});
clonedRow.find('img').each(function() { 
    $(this).attr('src', ''); 
    $(this).attr('image_id','');
});
        clonedRow.find('.remove-row-button[data-name="' + fragmentOne + '"]')
            .attr('data-row-index', newIndex)
            .removeClass('hidden');
        clonedRow.find('*').each(function() {
            var element = $(this);
            $.each(this.attributes, function() {
                var attributeName = this.name;
                var attributeValue = this.value;
                if (attributeValue.includes(fragmentOne) || attributeValue.includes(fragmentTwo)) {
                    var updatedValue = attributeValue
                        .replace(regexOne, function(match, prefix, number) {
                            return prefix + "[" + newIndex + "]"; 
                        })
                        .replace(regexTwo, function(match, prefix, number) {
                            return prefix + "[" + newIndex + "]"; 
                        });

                    if (updatedValue !== attributeValue) {
                        element.attr(attributeName, updatedValue);
                    }
                }
            });
        });

        repeaterRows.append(clonedRow);
    });

$(document).on('click', '.dibraco-repeater-wrapper .remove-row-button', function () {
    var button = $(this);
    var { fragmentOne, fragmentTwo, parentFragment, querySelector, regexOne, regexTwo, rowCountInput } = getFragmentsAndQuerySelector(button);
    var rowCountInputElement = $('.repeater-input[data-name="' + fragmentOne + '"]');
   rowCountInputElement.val(parseInt(rowCountInputElement.val(), 10) - 1);
    var rowIndexToRemove = parseInt(button.attr('data-row-index'), 10);  // Get the row index to remove
    var rowSelector = '.repeater-row' + querySelector; 
    var repeaterRowsContainerSelector = '.repeater-rows' + querySelector;
    var $rowToRemove = $(rowSelector +  '[data-row-index="' + rowIndexToRemove + '"]');
      $rowToRemove.remove();  // Remove the row

    var repeaterRowsContainer = $(repeaterRowsContainerSelector);
    repeaterRowsContainer.find(rowSelector).each(function ()
        {
        var row = $(this);
        var currentIndex = parseInt(row.attr('data-row-index'), 10);
        if (currentIndex > rowIndexToRemove) {
            var newIndex = currentIndex - 1;
            row.attr('data-row-index', newIndex);
            row.find('.remove-row-button').attr('data-row-index', newIndex);
            row.find('*').each(function () {
                var element = $(this);
            $.each(this.attributes, function() {
                var attributeName = this.name;
                var attributeValue = this.value;
                  if (attributeValue.includes(fragmentOne) || attributeValue.includes(fragmentTwo)) {
                     var updatedValue = attributeValue
                        .replace(regexOne, function(match, prefix, number) {
                            return prefix + "[" + newIndex + "]"; 
                        })
                        .replace(regexTwo, function(match, prefix, number) {
                            return prefix + "[" + newIndex + "]"; // Increment after fragmentTwo
                        });
                    if (updatedValue !== attributeValue) {
                        element.attr(attributeName, updatedValue);
                        }
                    }
                });
            });
        }
        });
});
});