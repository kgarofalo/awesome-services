document.addEventListener('DOMContentLoaded', function () {
    const addButton = document.getElementById('add-field-button');
    if (!container || !addButton) return;
    const fieldOptions = {
        text: 'Text',
        textarea: 'Text Area',
        wysiwyg: 'Wysiwig'
    };
    let optionsHTML = '';
    for (const key in fieldOptions) {
        if (fieldOptions.hasOwnProperty(key)) {
            optionsHTML += `<option value="${key}">${fieldOptions[key]}</option>`;
        }
    }
    function fixInitialState() {
        const allRows = Array.from(container.querySelectorAll('.field-row'));
        allRows.forEach((currentRow) => {
            const isCurrentRowPairEnd = currentRow.querySelector('input[name*="[pair_end]"]');
            if (isCurrentRowPairEnd)return;
            const nextRow = currentRow.nextElementSibling;
            if (!nextRow) return;

            const nextRowStartsPair = nextRow.querySelector('.pair-toggle-cell input[name*="[pair]"]:checked');
            if (nextRowStartsPair) {
                const currentPairCell = currentRow.querySelector('.pair-toggle-cell');
                if (currentPairCell && currentPairCell.querySelector('input[name*="[pair]"]')) {
                    currentPairCell.innerHTML = '';
                }
            }
        });
    }
function handlePairChecked(checkbox) {
  const currentRow = checkbox.closest('.field-row');
  const prevRow = currentRow.previousElementSibling;
  const nextRow = currentRow.nextElementSibling;
  if (nextRow) {
        const nextFieldName = nextRow.querySelector('.field-name-input').getAttribute('name');
        const nextPairCell = nextRow.querySelector('.pair-toggle-cell');
        const nextActionsCell = nextPairCell.nextElementSibling;
        nextPairCell.innerHTML = `Paired<input type="hidden" name="fields[${nextFieldName}][pair_end]" value="1">`;
        nextActionsCell.innerHTML = '';
    }
 if (prevRow) {
        const prevPairCell = prevRow.querySelector('.pair-toggle-cell');
        const isPrevRowPairEnd = prevPairCell.querySelector('input[name*="[pair_end]"]');
        if (!isPrevRowPairEnd) {
            if (prevPairCell.querySelector('input[name*="[pair]"]')) {
                prevPairCell.innerHTML = '';
            }
        }
    }
}
        
function handlePairUnchecked(checkbox) {
  const currentRow = checkbox.closest('.field-row');
  const prevRow = currentRow.previousElementSibling;
  const nextRow = currentRow.nextElementSibling;
    if (nextRow) {
        const nextFieldName = nextRow.querySelector('.field-name-input').getAttribute('name');
        const nextPairCell = nextRow.querySelector('.pair-toggle-cell');
        const nextActionsCell = nextPairCell.nextElementSibling;
        nextPairCell.innerHTML = `<input type="checkbox" name="fields[${nextFieldName}][pair]" value="1">`;
        nextActionsCell.innerHTML = `<a href="#" class="button button-danger remove-field-button">Remove</a>`;
    }
     if (prevRow) {
        const prevPairCell = prevRow.querySelector('.pair-toggle-cell');
        const isPrevRowPairEnd = prevPairCell.querySelector('input[name*="[pair_end]"]');
        if (!isPrevRowPairEnd) {
             const hasPairCheckbox = prevPairCell.querySelector('input[name*="[pair]"]');
             if (!hasPairCheckbox) {
                const prevFieldName = prevRow.querySelector('.field-name-input').getAttribute('name');
                prevPairCell.innerHTML = `<input type="checkbox" name="fields[${prevFieldName}][pair]" value="1">`;
             }
        }
    }
}

    container.addEventListener('change', function(e) {
        if (e.target.matches('.pair-toggle-cell input[name*="[pair]"]')) {
            const checkbox = e.target;
            if (checkbox.checked) {
                handlePairChecked(checkbox);
            } else {
                handlePairUnchecked(checkbox);
            }
        }
    });


     container.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-field-button')) {
            e.preventDefault();
            const rowToRemove = e.target.closest('.field-row');
            const prevRow = rowToRemove.previousElementSibling;
            const nextRow = rowToRemove.nextElementSibling;

            const pairCheckbox = rowToRemove.querySelector('input[name*="[pair]"]');
            const wasChecked = pairCheckbox && pairCheckbox.checked;
            const hadNoCheckbox = !pairCheckbox;

            rowToRemove.remove();
            if (wasChecked) {
                if (prevRow) {
                    const prevPairCell = prevRow.querySelector('.pair-toggle-cell');
                    if (!prevPairCell.querySelector('input[name*="[pair_end]"]')) {
                        const prevFieldName = prevRow.querySelector('.field-name-input').getAttribute('name');
                        prevPairCell.innerHTML = `<input type="checkbox" name="fields[${prevFieldName}][pair]" value="1">`;
                    }
                }
                if (nextRow) {
                    const nextFieldName = nextRow.querySelector('.field-name-input').getAttribute('name');
                    const nextPairCell = nextRow.querySelector('.pair-toggle-cell');
                    const nextActionsCell = nextPairCell.nextElementSibling;
                    nextPairCell.innerHTML = `<input type="checkbox" name="fields[${nextFieldName}][pair]" value="1">`;
                    nextActionsCell.innerHTML = `<a href="#" class="button button-danger remove-field-button">Remove</a>`;
                }
            } else if (hadNoCheckbox) {
                const prevPairCell = prevRow.querySelector('.pair-toggle-cell');
                 if (!prevPairCell.querySelector('input[name*="[pair_end]"]')) {
                    const newNextRow = prevRow.nextElementSibling;
                    let newNextRowStartsPair = false;
                    if (newNextRow) {
                        const check = newNextRow.querySelector('input[name*="[pair]"]:checked');
                        if (check) newNextRowStartsPair = true;
                    }

                    if (!newNextRowStartsPair) {
                         const prevFieldName = prevRow.querySelector('.field-name-input').getAttribute('name');
                         prevPairCell.innerHTML = `<input type="checkbox" name="fields[${prevFieldName}][pair]" value="1">`;
                    }
                 }
            }
        }
    });
    addButton.addEventListener('click', function(e) {
        e.preventDefault();
        const fieldName = 'new_' + Math.random().toString(36).substring(2, 9);
        const lastRow = container.querySelector('.field-row:last-of-type');
        let lastRowStartsPair = false;
        if (lastRow) {
            const lastPairCheckbox = lastRow.querySelector('.pair-toggle-cell input[name*="[pair]"]:checked');
            if (lastPairCheckbox) {
                lastRowStartsPair = true;
            }
        }
        const newRow = document.createElement('tr');
        newRow.className = 'field-row';
        let pairCellHtml, actionsCellHtml;
        if (lastRowStartsPair) {
            pairCellHtml = `<td class="pair-toggle-cell">Paired<input type="hidden" name="fields[${fieldName}][pair_end]" value="1"></td>`;
            actionsCellHtml = '<td></td>';
        } else {
            pairCellHtml = `<td class="pair-toggle-cell"><input type="checkbox" name="fields[${fieldName}][pair]" value="1"></td>`;
            actionsCellHtml = '<td><a href="#" class="button button-danger remove-field-button">Remove</a></td>';
        }
        newRow.innerHTML = `
            <td><input type="text" name="${fieldName}" placeholder="New Field Name eg (Section 4 Title)" class="widefat field-name-input" value=""></td>
            <td><select name="fields[${fieldName}][type]" class="widefat">${optionsHTML}</select></td>
            ${pairCellHtml}
            ${actionsCellHtml}
        `;
        container.appendChild(newRow);
        if (lastRowStartsPair && lastRow) {
            const lastRowActionsCell = lastRow.querySelector('.pair-toggle-cell').nextElementSibling;
            if (lastRowActionsCell) {
                lastRowActionsCell.innerHTML = '';
            }
        }
    });
    fixInitialState();
});