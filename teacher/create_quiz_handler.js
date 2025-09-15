// School/teacher/create_quiz_handler.js

document.addEventListener('DOMContentLoaded', () => {
    const questionsContainer = document.getElementById('questions-container');
    const addQuestionBtn = document.getElementById('add-question-btn');
    const quizForm = document.getElementById('quiz-form');
    let questionCounter = 0;

    const createQuestionTemplate = () => {
        questionCounter++;
        const questionHtml = `
            <div class="question p-6 bg-gray-50 rounded-lg shadow-inner draggable" draggable="true" data-id="${questionCounter}">
                <div class="flex items-center mb-4">
                    <span class="handle mr-3 text-gray-500 cursor-grab">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </span>
                    <label for="question-${questionCounter}" class="block text-sm font-bold text-gray-700 flex-grow">Question ${questionCounter}</label>
                    <button type="button" class="remove-question-btn text-red-500 hover:text-red-700 text-sm">Remove</button>
                </div>
                <input type="text" name="questions[${questionCounter}][question_text]" id="question-${questionCounter}" class="block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm mb-4" placeholder="Enter question text" required>
                <input type="hidden" name="questions[${questionCounter}][id]" value="${questionCounter}">
                
                <div class="options-container space-y-2">
                    <p class="text-sm font-medium text-gray-700 mb-2">Options:</p>
                    <div class="option-item flex items-center space-x-2 option-draggable" draggable="true">
                        <span class="handle text-gray-500 cursor-grab">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </span>
                        <input type="radio" name="questions[${questionCounter}][correct_answer]" value="0" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300" required>
                        <input type="text" name="questions[${questionCounter}][options][0]" class="flex-grow py-2 px-3 border border-gray-300 rounded-md shadow-sm" placeholder="Option 1" required>
                    </div>
                    <div class="option-item flex items-center space-x-2 option-draggable" draggable="true">
                        <span class="handle text-gray-500 cursor-grab">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </span>
                        <input type="radio" name="questions[${questionCounter}][correct_answer]" value="1" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300" required>
                        <input type="text" name="questions[${questionCounter}][options][1]" class="flex-grow py-2 px-3 border border-gray-300 rounded-md shadow-sm" placeholder="Option 2" required>
                    </div>
                    <div class="option-item flex items-center space-x-2 option-draggable" draggable="true">
                        <span class="handle text-gray-500 cursor-grab">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </span>
                        <input type="radio" name="questions[${questionCounter}][correct_answer]" value="2" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300" required>
                        <input type="text" name="questions[${questionCounter}][options][2]" class="flex-grow py-2 px-3 border border-gray-300 rounded-md shadow-sm" placeholder="Option 3" required>
                    </div>
                    <div class="option-item flex items-center space-x-2 option-draggable" draggable="true">
                        <span class="handle text-gray-500 cursor-grab">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </span>
                        <input type="radio" name="questions[${questionCounter}][correct_answer]" value="3" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300" required>
                        <input type="text" name="questions[${questionCounter}][options][3]" class="flex-grow py-2 px-3 border border-gray-300 rounded-md shadow-sm" placeholder="Option 4" required>
                    </div>
                </div>
            </div>
        `;
        questionsContainer.insertAdjacentHTML('beforeend', questionHtml);
    };

    // Initial question
    createQuestionTemplate();

    addQuestionBtn.addEventListener('click', createQuestionTemplate);

    // Remove question functionality
    questionsContainer.addEventListener('click', (e) => {
        if (e.target.classList.contains('remove-question-btn')) {
            const questionElement = e.target.closest('.question');
            if (questionElement) {
                questionElement.remove();
                renumberQuestions();
            }
        }
    });

    // Drag and drop for questions
    questionsContainer.addEventListener('dragstart', (e) => {
        if (e.target.classList.contains('draggable')) {
            e.target.classList.add('dragging');
            e.dataTransfer.setData('text/plain', e.target.dataset.id);
            e.dataTransfer.effectAllowed = 'move';
        }
    });

    questionsContainer.addEventListener('dragover', (e) => {
        e.preventDefault();
        const draggingElement = questionsContainer.querySelector('.dragging');
        const afterElement = getDragAfterElement(questionsContainer, e.clientY);
        if (afterElement == null) {
            if (draggingElement) questionsContainer.appendChild(draggingElement);
        } else {
            if (draggingElement) questionsContainer.insertBefore(draggingElement, afterElement);
        }
    });

    questionsContainer.addEventListener('dragend', (e) => {
        const draggingElement = questionsContainer.querySelector('.dragging');
        if (draggingElement) {
            draggingElement.classList.remove('dragging');
            renumberQuestions();
        }
    });

    function getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.draggable:not(.dragging)')];
        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    function renumberQuestions() {
        const questions = questionsContainer.querySelectorAll('.question');
        questions.forEach((question, index) => {
            const newId = index + 1;
            question.dataset.id = newId;
            question.querySelector('.block-text-sm').textContent = `Question ${newId}`;
            
            // Renumber input names for PHP processing
            const questionText = question.querySelector('input[type="text"]');
            questionText.name = `questions[${newId}][question_text]`;
            questionText.id = `question-${newId}`;

            const optionInputs = question.querySelectorAll('.option-item input');
            optionInputs.forEach((input, optionIndex) => {
                if(input.type === 'radio') {
                    input.name = `questions[${newId}][correct_answer]`;
                    input.value = optionIndex;
                } else if(input.type === 'text') {
                    input.name = `questions[${newId}][options][${optionIndex}]`;
                }
            });
        });
    }
});
