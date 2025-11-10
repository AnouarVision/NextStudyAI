document.addEventListener('DOMContentLoaded', () => {
    const fileInput = document.getElementById('file-input');
    const chooseFileBtn = document.getElementById('chooseFileBtn');
    const timeLimitCheckbox = document.getElementById('timeLimitCheckbox');
    const timeLimitInput = document.getElementById('timeLimitInput');
    const quizTypeBtns = document.querySelectorAll('.quiz-type-btn');
    const quizTypeHidden = document.getElementById('quizType');
    const quizConfigForm = document.getElementById('quizConfigForm');

    //Open file picker
    chooseFileBtn?.addEventListener('click', () => {
        fileInput.click();
    });

    // When the user selects a file → submit the form
    fileInput?.addEventListener('change', () => {
        if (fileInput.files.length > 0) {
            document.getElementById('uploadForm').submit();
        }
    });

    // Continue → show the quiz section
    document.getElementById('continueBtn')?.addEventListener('click', () => {
        const configSection = document.getElementById('configSection');
        chooseFileBtn.classList.add('hidden');
        configSection.classList.remove('hidden');
        configSection.scrollIntoView({ behavior: 'smooth' });
    });

    // Quiz Customization Section
    timeLimitCheckbox?.addEventListener('change', () => {
        if (timeLimitCheckbox.checked) {
            timeLimitInput.disabled = false;
            timeLimitInput.focus();
        } else {
            timeLimitInput.disabled = true;
            timeLimitInput.value = 30;
        }
    });

    // Quiz type selection
    quizTypeBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            quizTypeBtns.forEach(b => b.classList.remove('active'));

            btn.classList.add('active');

            quizTypeHidden.value = btn.dataset.type;

            // Small feedback animation for the button
            btn.animate([{ transform: 'scale(1)' }, { transform: 'scale(1.1)' }, { transform: 'scale(1)' }], {
                duration: 200,
                easing: 'ease-out'
            });
        });
    });

});
