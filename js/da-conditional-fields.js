(function () {
  function allowedList(node) {
    return node.getAttribute('data-controlling-values').split('|'); // Split values by pipe (|)
  }

  function currentValue(ctrl) {
    if (ctrl.matches('select')) return ctrl.value; // Return the value from <select>
    if (ctrl.matches('fieldset')) {
      var checked = ctrl.querySelector('input[type="radio"]:checked, input[type="checkbox"]:checked');
      return checked.value; 
    }
    return ''; 
  }

  function evaluateAndToggleDependents(controllerId, value) {
    var deps = document.querySelectorAll('[data-controlling-field="' + controllerId + '"]');
    deps.forEach(function (node) {
      var allowedValues = allowedList(node); 
      var show = allowedValues.indexOf(value) !== -1; // Show if current value matches allowed values
      if (show) {
        node.classList.add('visible');
        node.classList.remove('hidden');
        node.setAttribute('aria-hidden', 'false');
      } else {
        node.classList.add('hidden');
        node.classList.remove('visible');
        node.setAttribute('aria-hidden', 'true');
      }
    });
  }

  // Function to initialize and evaluate the dependent field visibility
  function init() {
    document.querySelectorAll('[data-controlling-field]').forEach(function (dep) {
      var controllerId = dep.getAttribute('data-controlling-field');
      var controller = document.getElementById(controllerId);
        evaluateAndToggleDependents(controllerId, currentValue(controller));
      controller.addEventListener('change', function () {
        evaluateAndToggleDependents(controllerId, currentValue(controller));
      });
    });
  }

  init();
})();

/*



function setupDependentFieldLogic(dependentFieldContainer) {
    const controllingFieldName = dependentFieldContainer.getAttribute('data-controlling-field');
    const controllingValues = dependentFieldContainer.dataset.controllingValues.split('|');
    const controllingField = document.querySelectorAll(`[name="${controllingFieldName}"]`);
  
    controllingField.forEach(field => {
        field.addEventListener('change', toggleVisibility); 
    });

    function getFieldValue() {
        const elementType = controllingField[0].type;

        if (elementType === 'checkbox') {
            return Array.from(controllingField).some(el => el.checked) ? "1" : "0";
        } else if (elementType === 'radio') {
            const checkedElement = Array.from(controllingField).find(el => el.checked);
            const contValue = checkedElement.value;
            
            if (checkedElement.classList.contains('toggle-input')) {
                const controllingContainer = checkedElement.closest('.the-toggle');
                if (controllingContainer.getAttribute('data-controlling-field')) {
                    return [contValue, controllingContainer];
                }
                return contValue;
            }
            return contValue;
        } else {
            const controllingContainer = controllingField[0].closest('.dibraco-select');
            const contValue = controllingField[0].value;
            if (controllingContainer && controllingContainer.getAttribute('data-controlling-field')) {
                return [contValue, controllingContainer];
            }
            return contValue;
        }
    }

    function toggleVisibility() {
        const fieldValue = getFieldValue();

        if (Array.isArray(fieldValue) && fieldValue.length === 2) {
            const [contValue, parentContainer] = fieldValue;
            observeParentVisibility(parentContainer);

            if (parentContainer.classList.contains('hidden')) {
                dependentFieldContainer.classList.add('hidden');
                dependentFieldContainer.classList.remove('visible');
            } else {
                if (controllingValues.includes(contValue)) {
                    dependentFieldContainer.classList.remove('hidden');
                    dependentFieldContainer.classList.add('visible');
                } else {
                    dependentFieldContainer.classList.add('hidden');
                    dependentFieldContainer.classList.remove('visible');
                }
            }
        } else {
            const isVisible = controllingValues.includes(fieldValue);
            if (isVisible) {
                dependentFieldContainer.classList.remove('hidden');
                dependentFieldContainer.classList.add('visible');
            } else {
                dependentFieldContainer.classList.remove('visible');
                dependentFieldContainer.classList.add('hidden');
            }
        }
    }

    function observeParentVisibility(parentContainer) {
        const observer = new MutationObserver(() => {
            if (parentContainer.classList.contains('hidden')) {
                dependentFieldContainer.classList.add('hidden');
                dependentFieldContainer.classList.remove('visible');
            } else {
                let fieldValue = getFieldValue();
                let contValue;

                if (Array.isArray(fieldValue)) {
                    [contValue, parentContainer] = fieldValue;
                    fieldValue = contValue;
                }

                if (controllingValues.includes(fieldValue)) {
                    dependentFieldContainer.classList.remove('hidden');
                    dependentFieldContainer.classList.add('visible');
                } else {
                    dependentFieldContainer.classList.add('hidden');
                    dependentFieldContainer.classList.remove('visible');
                }
            }
        });

        observer.observe(parentContainer, {
            attributes: true, 
            attributeFilter: ['class'],
        });
    }

    toggleVisibility();

    const observer = new MutationObserver(function(mutationsList) {
        mutationsList.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    if (node.matches('[data-controlling-field]')) {
                        setupDependentFieldLogic(node);
                    }
                    node.querySelectorAll && node.querySelectorAll('[data-controlling-field]').forEach(function(child) {
                        setupDependentFieldLogic(child);
                    });
                }
            });
        });
    });

    observer.observe(document.body, { childList: true, subtree: true });
}

const isAdminPage = document.body.classList.contains('wp-admin'); 
const dynamicScope = document.getElementById('poststuff') ||      
                     document.getElementById('wpbody-content') || 
                     document.getElementById('wpcontent') ||      
                     document.querySelector('.wrap');

if (isAdminPage && dynamicScope) {
    document.querySelectorAll('[data-controlling-field]').forEach(dependentField => {
        setupDependentFieldLogic(dependentField);  
    });
}
*/