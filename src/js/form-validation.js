function validateLettersOnly(input) {
    // Remove any existing error messages
    const errorDiv = input.nextElementSibling;
    if (errorDiv && errorDiv.classList.contains('error-message')) {
        errorDiv.remove();
    }
    
    // Check if input contains only letters and spaces
    if (!/^[a-zA-Z\s]*$/.test(input.value)) {
        // Create error message
        const error = document.createElement('div');
        error.classList.add('error-message');
        error.style.color = 'red';
        error.textContent = 'Please enter letters only';
        
        // Insert error after input
        input.parentNode.insertBefore(error, input.nextSibling);
        
        // Clear invalid input
        input.value = input.value.replace(/[^a-zA-Z\s]/g, '');
        
        return false;
    }
    return true;
}

// Add form submission validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return;

    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        // Get all text inputs that should only contain letters
        const letterInputs = form.querySelectorAll('input[pattern="[A-Za-z\\s]+"]');
        
        letterInputs.forEach(input => {
            if (!validateLettersOnly(input)) {
                isValid = false;
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Please correct the errors in the form before submitting.');
        }
    });
} 