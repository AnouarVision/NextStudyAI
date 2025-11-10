let currentIndex = 0;
let answers = Array(questions.length).fill(null);

const questionArea = document.getElementById('questionArea');
const hiddenAnswersDiv = document.getElementById('hiddenAnswers');
const progressBar = document.getElementById('progressBar');
const progressText = document.getElementById('progressText');
const prevBtn = document.getElementById('prevBtn');
const nextBtn = document.getElementById('nextBtn');
const submitBtn = document.getElementById('submitBtn');

// compare short answer / completion responses
function isAnswerCloseJS(userAns, correctAnswer, keywords = []) {
    let userAnsNorm = userAns.trim().toLowerCase();
    let correctAnsNorm = (correctAnswer ?? '').trim().toLowerCase();
    const articles = /\b(il|lo|la|i|gli|le|di|a|da|in|su)\b/g;
    userAnsNorm = userAnsNorm.replace(articles,'');
    correctAnsNorm = correctAnsNorm.replace(articles,'');

    if (userAnsNorm.includes(correctAnsNorm)) return true;

    const distance = levenshteinJS(userAnsNorm, correctAnsNorm);
    const length = Math.max(userAnsNorm.length, correctAnsNorm.length, 1);
    const similarity = 1 - (distance / length);

    let keywordMatches = 0;
    keywords.forEach(kw => { if (userAnsNorm.includes(kw.toLowerCase())) keywordMatches++; });
    const keywordScore = keywords.length ? keywordMatches / keywords.length : 0;

    return (similarity + keywordScore) / 2 >= 0.6;
}

// compute Levenshtein distance
function levenshteinJS(a,b){
    const matrix = [];
    if(a.length===0) return b.length;
    if(b.length===0) return a.length;
    for(let i=0;i<=b.length;i++){ matrix[i] = [i]; }
    for(let j=0;j<=a.length;j++){ matrix[0][j] = j; }
    for(let i=1;i<=b.length;i++){
        for(let j=1;j<=a.length;j++){
            if(b.charAt(i-1)===a.charAt(j-1)) matrix[i][j]=matrix[i-1][j-1];
            else matrix[i][j]=Math.min(matrix[i-1][j-1]+1, matrix[i][j-1]+1, matrix[i-1][j]+1);
        }
    }
    return matrix[b.length][a.length];
}

function renderQuestion() {
    const q = questions[currentIndex];
    let html = '';

    switch(q.type) {
        case 'completamento':
            // render completion question with draggable options
            const text = q.question.replace(/_+/g, '<span class="drop-zone blank"></span>');
            html += `<p class="completion-text"><b>${text}</b></p>
                     <div class="drag-options">
                         ${(q.options ?? []).map(opt => `<div class="draggable">${opt}</div>`).join('')}
                     </div>`;
            break;

        case 'risposta_breve':
            // render short answer input
            html += `<div class="question-block">
                        <p><b>${q.question}</b></p>
                        <input type="text" id="textAnswer" placeholder="Write your answer">
                        <button type="button" id="finishBtn" class="finish-btn">Check Answer</button>
                     </div>`;
            break;

        case 'vero_falso':
            // render true/false question
            html += `<div class="question-block">
                    <p><b>${q.question}</b></p>
                    <div class="options-container">
                        ${["vero","falso"].map(opt => `
                            <div class="option">
                                <input type="radio" name="option" value="${opt}" id="opt_${opt}">
                                <label for="opt_${opt}">${opt}</label>
                            </div>`).join('')}
                    </div></div>`;
            break;

        case 'multipla':
            // render multiple choice question
            html += `<div class="question-block">
                        <p><b>${q.question}</b></p>
                        <div class="options-container">
                            ${(q.options ?? []).map(opt => `
                                <div class="option">
                                    <input type="radio" name="option" value="${opt}" id="opt_${opt}">
                                    <label for="opt_${opt}">${opt}</label>
                                </div>`).join('')}
                        </div></div>`;
            break;

        case 'collegamento':
            // render matching question
            html += `<div class="question-block">
                        <p><b>${q.question}</b></p>
                        <div class="collegamento-container">
                            ${(q.left ?? []).map((opt, i) => `<div class="col-item" data-index="${i}">${opt}</div>`).join('')}
                        </div>
                        <div class="col-targets">
                            ${(q.right ?? []).map((t, i) => `<div class="col-target" data-index="${i}">${t}</div>`).join('')}
                        </div></div>`;
            break;

        default:
            html += `<h1>Question type not supported</h1>`;
    }

    html += `<div id="feedbackArea" class="inline-feedback" style="display:none;"></div>`;
    questionArea.innerHTML = html;

    attachEvents();
    updateNav();
    updateProgress();
}

// show inline feedback
function showFeedback(isCorrect, feedbackText) {
    const fb = document.getElementById('feedbackArea');
    fb.className = 'inline-feedback ' + (isCorrect ? 'correct' : 'wrong');
    fb.innerHTML = isCorrect ? `<b>Correct answer!</b><br>${feedbackText}` : `<b>Wrong answer!</b><br>${feedbackText}`;
    fb.style.display = 'block';
}

function attachEvents() {
    const q = questions[currentIndex];

    // multiple choice / true-false options
    if (q.type === 'multipla' || q.type === 'vero_falso') {
        const options = questionArea.querySelectorAll('.option');
        let answered = false;
        options.forEach(opt => {
            opt.addEventListener('click', () => {
                if (answered) return;
                options.forEach(o => o.classList.remove('selected'));
                opt.classList.add('selected');
                const input = opt.querySelector('input');
                input.checked = true;
                answers[currentIndex] = input.value;
                const correct = q.answer && input.value.trim().toLowerCase() === q.answer.trim().toLowerCase();
                showFeedback(correct, q.feedback || "No feedback available.");
                answered = true;
                updateNav();
            });
        });
    }

    // matching (drag & drop)
    if (q.type === 'collegamento') {
        const items = questionArea.querySelectorAll('.col-item');
        const targets = questionArea.querySelectorAll('.col-target');
        items.forEach((item, idx) => {
            item.draggable = true;
            item.ondragstart = e => e.dataTransfer.setData('text/plain', idx);
        });
        targets.forEach((target, i) => {
            target.ondragover = e => e.preventDefault();
            target.ondrop = e => {
                e.preventDefault();
                if(target.dataset.filled) return;
                const itemIdx = e.dataTransfer.getData('text/plain');
                const draggedItem = items[itemIdx];
                target.textContent = draggedItem.textContent;
                target.dataset.filled = "true";
                answers[currentIndex] = answers[currentIndex] || [];
                answers[currentIndex][i] = draggedItem.textContent;
                const allFilled = [...targets].every(t => t.dataset.filled === "true");
                if(allFilled){
                    const correct = JSON.stringify(answers[currentIndex]) === JSON.stringify(q.pairs ?? []);
                    showFeedback(correct, q.feedback || "No feedback available.");
                    nextBtn.disabled = false;
                }
            };
        });
    }

    // short answer input
    if (q.type === 'risposta_breve') {
        const textInput = questionArea.querySelector('#textAnswer');
        const finishBtn = questionArea.querySelector('#finishBtn');
        if (textInput && finishBtn) {
            finishBtn.addEventListener('click', () => {
                const userVal = textInput.value.trim();
                if (!userVal) return alert("Please write your answer first!");
                answers[currentIndex] = { user: userVal };
                const correct = isAnswerCloseJS(userVal, q.answer ?? '', q.keywords ?? []);
                showFeedback(correct, q.feedback || "No feedback available.");
                nextBtn.disabled = false;
            });
            textInput.addEventListener('input', () => {
                document.getElementById('feedbackArea').style.display = 'none';
                nextBtn.disabled = true;
            });
        }
    }

    // completion (drag & drop)
    if (q.type === 'completamento') {
        const draggablesComp = questionArea.querySelectorAll('.draggable');
        const blanks = questionArea.querySelectorAll('.blank');
        draggablesComp.forEach(d => {
            d.draggable = true;
            d.ondragstart = e => e.dataTransfer.setData('text/plain', e.target.textContent);
        });
        blanks.forEach((blank, i) => {
            blank.ondragover = e => e.preventDefault();
            blank.ondrop = e => {
                e.preventDefault();
                if(blank.classList.contains('locked')) return;
                const val = e.dataTransfer.getData('text/plain');
                blank.textContent = val;
                blank.classList.add('filled', 'locked');
                draggablesComp.forEach(d => { if(d.textContent === val) d.style.display = 'none'; });
                answers[currentIndex] = answers[currentIndex] || [];
                answers[currentIndex][i] = val;
                const filled = [...blanks].every(b => b.textContent.trim() !== '');
                if(filled) {
                    const userAnswer = answers[currentIndex].join(' ').trim();
                    const correctAnswer = Array.isArray(q.answer) ? q.answer.join(' ').trim() : q.answer.trim();
                    const correct = isAnswerCloseJS(userAnswer, correctAnswer, q.keywords ?? []);
                    showFeedback(correct, q.feedback || "No feedback available.");
                    nextBtn.disabled = false;
                }
            };
        });
    }
}

// update navigation buttons
function updateNav() {
    prevBtn.disabled = currentIndex === 0;
    nextBtn.disabled = currentIndex === questions.length - 1 || answers[currentIndex] === null;
    submitBtn.style.display = currentIndex === questions.length - 1 ? 'inline-block' : 'none';
}

// update progress bar
function updateProgress() {
    const perc = ((currentIndex+1)/questions.length)*100;
    progressBar.style.width = perc + '%';
    progressText.textContent = `Question ${currentIndex+1} of ${questions.length}`;
}

prevBtn.onclick = () => { if(currentIndex>0){ currentIndex--; renderQuestion(); } };
nextBtn.onclick = () => { if(currentIndex<questions.length-1 && answers[currentIndex]!==null){ currentIndex++; renderQuestion(); } };

// submit quiz form
document.getElementById('quizForm').addEventListener('submit', e => {
    hiddenAnswersDiv.innerHTML = '';
    answers.forEach((a,i) => {
        if(a !== null){
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'q'+i;
            input.value = Array.isArray(a) ? JSON.stringify(a) : (a.user ?? a);
            hiddenAnswersDiv.appendChild(input);
        }
    });
});


// Timer
if (isTimed) {
    let timeLeft = timeLimit * 60;
    const timerEl = document.getElementById('timeRemaining');
    const timerInterval = setInterval(() => {
        timeLeft--;
        let min = Math.floor(timeLeft/60);
        let sec = timeLeft%60;
        timerEl.textContent = `${min}:${sec<10?'0':''}${sec}`;
        if(timeLeft<=0){
            clearInterval(timerInterval);
            alert('Time is up!');
            document.getElementById('quizForm').submit();
        }
    },1000);
}

renderQuestion();
