document.addEventListener('DOMContentLoaded', function() {
    const passwordFirst = document.querySelector('.password-first');
    const passwordSecond = document.querySelector('.password-second');
    const resultText = document.querySelector('.result-text');
    const form = document.querySelector('form');

    if (form) {
        form.addEventListener('submit', function(e) {
            if (passwordFirst.value !== passwordSecond.value) {
                e.preventDefault();
                resultText.textContent = 'Passwords do not match!';
                resultText.style.color = 'red';
            }
        });
    }

    if (passwordSecond) {
        passwordSecond.addEventListener('input', function() {
            if (passwordFirst.value === passwordSecond.value) {
                resultText.textContent = 'Passwords match!';
                resultText.style.color = 'green';
            } else {
                resultText.textContent = 'Passwords do not match!';
                resultText.style.color = 'red';
            }
        });
    }
});