document.addEventListener('DOMContentLoaded', function () {
    // Copy to clipboard functionality
    var copyButtons = document.querySelectorAll('.copy-btn');
    copyButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            var mailTag = this.closest('td').querySelector('.spcf_class');
            mailTag.select();
            document.execCommand('copy');

            var tooltip = this.closest('.mcf_tooltip').querySelector('.mcf_tooltiptext');
            tooltip.textContent = 'Copied!';

            setTimeout(function () {
                tooltip.textContent = 'Copy to clipboard';
            }, 2000);
        });
    });


    // Function to toggle enabled/disabled state of Start Count and No. of Digits fields
    function toggleFields(formId, enableFields) {
        var formSection = document.querySelector('div[id="cfc_setting_' + formId + '"]');
        if (formSection) {
            var startCountInput = formSection.querySelector('input[name="mcf_cf7_settings[count_' + formId + ']"]');
            var digitsInput = formSection.querySelector('input[name="mcf_cf7_settings[digits_' + formId + ']"]');

            if (startCountInput && digitsInput) {
                startCountInput.disabled = !enableFields;
                digitsInput.disabled = !enableFields;

                // Optionally, change the appearance of disabled fields
                startCountInput.style.opacity = enableFields ? '1' : '0.5';
                digitsInput.style.opacity = enableFields ? '1' : '0.5';
            }
        }
    }

    // Find all Display type radio button groups
    var displayTypeGroups = document.querySelectorAll('input[name^="mcf_cf7_settings[type_"]');

    // Add event listeners to each radio button
    displayTypeGroups.forEach(function (radio) {
        radio.addEventListener('change', function () {
            var formId = this.name.match(/\[type_(\d+)\]/)[1]; // Extract full form ID
            var enableFields = this.value === '1'; // '1' corresponds to Serial Number
            toggleFields(formId, enableFields);
        });
    });

    // Initial setup on page load
    displayTypeGroups.forEach(function (radio) {
        if (radio.checked) {
            var formId = radio.name.match(/\[type_(\d+)\]/)[1]; // Extract full form ID
            var enableFields = radio.value === '1';
            toggleFields(formId, enableFields);
        }
    });

    // Force re-check after a short delay (in case of any race conditions)
    setTimeout(function () {
        displayTypeGroups.forEach(function (radio) {
            if (radio.checked) {
                var formId = radio.name.match(/\[type_(\d+)\]/)[1]; // Extract full form ID
                var enableFields = radio.value === '1';
                toggleFields(formId, enableFields);
            }
        });
    }, 500);


});