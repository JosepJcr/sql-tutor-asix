/**
 * SQL Tutor ASIX - Aplicació Principal Frontend
 */

let activeEditor = null;
let currentStepNumber = 1;
let currentExerciseCode = '';

document.addEventListener('DOMContentLoaded', () => {
    initCodeMirrors();
    initLessonQuizzes();
    initSyncButton();
    initLabController();
    initChallengeController();
});

/**
 * Inicialitza els editors CodeMirror a la pàgina
 */
function initCodeMirrors() {
    const editorEl = document.getElementById('code-editor');
    if (editorEl) {
        activeEditor = CodeMirror.fromTextArea(editorEl, {
            mode: 'text/x-mssql',
            theme: 'material-darker',
            lineNumbers: true,
            indentWithTabs: false,
            indentUnit: 4,
            matchBrackets: true,
            autoCloseBrackets: true,
            extraKeys: {
                "Ctrl-Enter": () => executeCode(),
                "Cmd-Enter": () => executeCode(),
                "Ctrl-Space": "autocomplete"
            }
        });
    }

    // Inicialitzar editors simples "prova-ho" a les lliçons
    document.querySelectorAll('.try-editor').forEach((textarea) => {
        const cm = CodeMirror.fromTextArea(textarea, {
            mode: 'text/x-mssql',
            theme: 'material-darker',
            lineNumbers: true,
            readOnly: false
        });
        textarea._cmInstance = cm;
    });
}

/**
 * Execució de codi SQL al Sandbox
 */
async function executeCode() {
    if (!activeEditor) return;

    const btn = document.getElementById('btn-execute');
    const outputBody = document.getElementById('output-body');
    const code = activeEditor.getValue();

    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '⏳ Executant...';
    }

    outputBody.innerHTML = '<span style="color:#94a3b8;">Enviant consulta a SQL Server via sandbox...</span>';

    try {
        const res = await fetch('api/execute.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                code: code,
                exercise_code: currentExerciseCode,
                step_num: currentStepNumber,
                db_name: 'HelpdeskTIC'
            })
        });

        const data = await res.json();

        if (data.success) {
            outputBody.innerHTML = `<span style="color:#4ade80;">✔ Consulta executada correctament (${data.execution_time_ms} ms)</span>\n\n` +
                                   (data.output ? escapeHtml(data.output) : '(La consulta no ha retornat cap fila)');
            
            if (data.step_passed) {
                handleStepPassed(data);
            }
        } else {
            let errorHtml = `<span class="output-error">✖ Error de SQL Server:</span>\n${escapeHtml(data.error)}\n`;
            if (data.explained_error) {
                errorHtml += `<div class="output-explanation">💡 <strong>Explicació clara:</strong> ${escapeHtml(data.explained_error)}</div>`;
            }
            outputBody.innerHTML = errorHtml;

            // Notificar al tutor si està obert
            appendTutorMessage(`He detectat que l'execució ha donat un error. Vol que t'ajudi a entendre què ha fallat?`, 'tutor');
        }
    } catch (err) {
        outputBody.innerHTML = `<span class="output-error">Error de comunicació amb el servidor local: ${err.message}</span>`;
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '▶ Executar (Ctrl+Enter)';
        }
    }
}

/**
 * Gestió de superació d'un pas al laboratori
 */
function handleStepPassed(data) {
    appendTutorMessage(`🎉 **Molt bona feina!** Has completat amb èxit el pas ${currentStepNumber}. Has guanyat +${data.xp_earned} XP!`, 'tutor');
    updateXpHeader(data.xp_earned);

    if (data.next_step) {
        const nextBtn = document.getElementById('btn-next-step');
        if (nextBtn) {
            nextBtn.style.display = 'inline-flex';
            nextBtn.onclick = () => loadStep(data.next_step);
        }
    } else {
        appendTutorMessage(`🏆 **Felicitats!** Has completat tots els passos d'aquest exercici del temari!`, 'tutor');
    }
}

/**
 * Canvi de pas al laboratori
 */
function loadStep(stepNum) {
    currentStepNumber = stepNum;
    const badge = document.getElementById('current-step-badge');
    if (badge) badge.textContent = `Pas ${stepNum}`;

    const nextBtn = document.getElementById('btn-next-step');
    if (nextBtn) nextBtn.style.display = 'none';

    // Notificar al tutor
    appendTutorMessage(`Has desbloquejat el **Pas ${stepNum}**. Llegeix el nou objectiu i quan ho tinguis llest, prem Executar!`, 'tutor');
}

/**
 * Xat Socràtic amb el Tutor
 */
async function askTutor(message) {
    if (!message) return;

    appendTutorMessage(message, 'user');
    const input = document.getElementById('tutor-input');
    if (input) input.value = '';

    const code = activeEditor ? activeEditor.getValue() : '';
    const outputEl = document.getElementById('output-body');
    const outputText = outputEl ? outputEl.innerText : '';

    appendTutorMessage('🤔 _En David està pensant..._', 'tutor', 'thinking-msg');

    try {
        const res = await fetch('api/tutor.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'ask',
                exercise_code: currentExerciseCode,
                step_num: currentStepNumber,
                code: code,
                output: outputText,
                message: message
            })
        });

        const data = await res.json();
        const thinking = document.getElementById('thinking-msg');
        if (thinking) thinking.remove();

        if (data.success) {
            appendTutorMessage(data.reply, 'tutor');
        } else {
            appendTutorMessage("Em sap greu, hi ha hagut una petita incidència en processar la resposta. Torna a intentar-ho.", 'tutor');
        }
    } catch (e) {
        const thinking = document.getElementById('thinking-msg');
        if (thinking) thinking.remove();
        appendTutorMessage("No s'ha pogut connectar amb el tutor.", 'tutor');
    }
}

/**
 * Pistes escalonades
 */
async function requestHint(level) {
    try {
        const res = await fetch('api/tutor.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'hint',
                exercise_code: currentExerciseCode,
                step_num: currentStepNumber,
                hint_level: level
            })
        });

        const data = await res.json();
        if (data.success) {
            appendTutorMessage(`🔑 **Pista Nivell ${level} (-${data.xp_cost} XP):**\n\n${data.hint_text}`, 'tutor');
            const xpVal = document.getElementById('stat-xp-val');
            if (xpVal) xpVal.textContent = data.new_xp;
        }
    } catch (e) {
        alert("Error demanant la pista.");
    }
}

function appendTutorMessage(text, sender, id = null) {
    const container = document.getElementById('tutor-messages');
    if (!container) return;

    const div = document.createElement('div');
    div.className = `message-bubble ${sender}`;
    if (id) div.id = id;

    // Conversió bàsica de markdown per a negretes i salts
    let formatted = escapeHtml(text)
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/`(.*?)`/g, '<code style="background:#1e293b;padding:2px 4px;border-radius:4px;">$1</code>')
        .replace(/\n/g, '<br>');

    div.innerHTML = formatted;
    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
}

/**
 * Inicialitza el controlador del laboratori
 */
function initLabController() {
    const labWrapper = document.querySelector('[data-exercise-code]');
    if (labWrapper) {
        currentExerciseCode = labWrapper.getAttribute('data-exercise-code');
    }

    const tutorForm = document.getElementById('tutor-form');
    if (tutorForm) {
        tutorForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const input = document.getElementById('tutor-input');
            if (input && input.value.trim()) {
                askTutor(input.value.trim());
            }
        });
    }
}

/**
 * Inicialitza els micro-tests de les lliçons
 */
function initLessonQuizzes() {
    document.querySelectorAll('.quiz-option').forEach(option => {
        option.addEventListener('click', async function() {
            const quizBox = this.closest('.quiz-box');
            if (quizBox.getAttribute('data-answered') === 'true') return;

            const lessonId = quizBox.getAttribute('data-lesson-id');
            const optIndex = parseInt(this.getAttribute('data-index'), 10);

            try {
                const res = await fetch('api/progress.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'quiz_submit',
                        lesson_id: lessonId,
                        selected_option: optIndex
                    })
                });

                const data = await res.json();
                quizBox.setAttribute('data-answered', 'true');

                if (data.is_correct) {
                    this.classList.add('correct');
                    updateXpHeader(data.xp_earned);
                } else {
                    this.classList.add('wrong');
                }

                const feedback = quizBox.querySelector('.quiz-feedback');
                if (feedback) {
                    feedback.style.display = 'block';
                    feedback.innerHTML = `<strong>${data.is_correct ? '✔ Correcte!' : '✖ No és correcte.'}</strong> ${data.explanation}`;
                }
            } catch (e) {
                console.error(e);
            }
        });
    });
}

/**
 * Inicialitza el mode de reptes
 */
function initChallengeController() {
    document.querySelectorAll('.challenge-option').forEach(opt => {
        opt.addEventListener('click', async function() {
            const card = this.closest('.challenge-card');
            if (card.getAttribute('data-answered') === 'true') return;

            const chId = card.getAttribute('data-challenge-id');
            const optIdx = parseInt(this.getAttribute('data-index'), 10);

            const res = await fetch('api/challenges.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'verify',
                    challenge_id: chId,
                    selected_option: optIdx
                })
            });

            const data = await res.json();
            card.setAttribute('data-answered', 'true');

            if (data.is_correct) {
                this.classList.add('correct');
                updateXpHeader(data.xp_earned);
            } else {
                this.classList.add('wrong');
            }

            const fb = card.querySelector('.challenge-feedback');
            if (fb) {
                fb.style.display = 'block';
                fb.innerHTML = `<strong>${data.is_correct ? 'Molt ben trobat!' : 'Vaja!'}</strong> ${data.explanation}`;
            }
        });
    });
}

/**
 * Botó de sincronització del temari
 */
function initSyncButton() {
    const btn = document.getElementById('btn-sync-syllabus');
    if (btn) {
        btn.addEventListener('click', async () => {
            btn.disabled = true;
            btn.innerHTML = '🔄 Sincronitzant...';

            try {
                const res = await fetch('api/indexer.php?action=sync');
                const data = await res.json();

                if (data.success) {
                    let msg = `Sincronització completada amb èxit!\n- Fitxers escanejats: ${data.scanned}\n- Nous fitxers: ${data.new}\n- Modificats: ${data.modified}\n- Sense canvis: ${data.unchanged}`;
                    if (data.new_topics && data.new_topics.length > 0) {
                        msg += `\n\n🎉 S'han detectat nous temes:\n- ` + data.new_topics.join('\n- ');
                    }
                    alert(msg);
                    location.reload();
                } else {
                    alert('Error sincronitzant: ' + data.message);
                }
            } catch (e) {
                alert('Incidència connectant amb l\'indexador: ' + e.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '🔄 Actualitzar temari';
            }
        });
    }
}

function updateXpHeader(added) {
    const xpVal = document.getElementById('stat-xp-val');
    if (xpVal) {
        const current = parseInt(xpVal.textContent, 10) || 0;
        xpVal.textContent = current + added;
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>"']/g, (m) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    })[m]);
}
