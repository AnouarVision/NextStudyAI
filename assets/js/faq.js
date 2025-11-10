document.querySelectorAll('.faq-question').forEach(question => {
    question.addEventListener('click', () => {
        const answer = question.nextElementSibling;
        const isOpen = answer.classList.contains('show');

        document.querySelectorAll('.faq-answer').forEach(a => a.classList.remove('show'));
        document.querySelectorAll('.faq-question i.arrow').forEach(i => i.classList.remove('open'));

        if (!isOpen) {
            answer.classList.add('show');
            question.querySelector('i.arrow').classList.add('open');
        }
    });
});
