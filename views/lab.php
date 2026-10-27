<?php
/**
 * Vista de Laboratori amb Tutor Socràtic i CodeMirror
 */
$steps = json_decode($currentLab['steps_json'] ?? '[]', true);
$totalSteps = count($steps);
$firstStep = $steps[0] ?? ['step_num' => 1, 'instruction' => 'Llegeix les instruccions.'];
?>

<div class="lab-layout" data-exercise-code="<?= htmlspecialchars($currentLab['exercise_code']) ?>">
    <!-- Panell Esquerre: Editor i Consola -->
    <div class="editor-pane">
        <!-- Capçalera de l'Exercici -->
        <div class="editor-header">
            <div class="step-indicator">
                <span class="step-badge" id="current-step-badge">Pas 1 de <?= $totalSteps ?></span>
                <span style="font-size: 0.9rem; font-weight: 600; color: #f8fafc;">
                    <?= htmlspecialchars($currentLab['title']) ?>
                </span>
            </div>
            <div style="display: flex; gap: 8px;">
                <select id="exercise-selector" onchange="location.href='index.php?page=lab&ex=' + this.value" 
                        style="background: #1e293b; color: #f8fafc; border: 1px solid var(--border-color); padding: 4px 8px; border-radius: 6px; font-size: 0.8rem;">
                    <?php foreach ($allLabs as $l): ?>
                        <option value="<?= htmlspecialchars($l['exercise_code']) ?>" <?= ($l['exercise_code'] === $currentLab['exercise_code']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($l['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Descripció de l'Objectiu del Pas Actual -->
        <div style="padding: 14px 18px; background: #0f172a; border-bottom: 1px solid var(--border-color);">
            <div style="font-size: 0.85rem; color: #38bdf8; font-weight: 600; margin-bottom: 4px;">
                🎯 OBJECTIU DEL PAS ACTUAL:
            </div>
            <div id="step-instruction" style="font-size: 0.88rem; color: #e2e8f0; line-height: 1.5;">
                <?= htmlspecialchars($firstStep['instruction']) ?>
            </div>
        </div>

        <!-- Editor CodeMirror -->
        <div class="editor-container">
            <textarea id="code-editor"><?= htmlspecialchars($currentLab['initial_code']) ?></textarea>
        </div>

        <!-- Barra d'Accions de l'Editor -->
        <div style="padding: 10px 16px; background: #0f172a; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
            <div style="font-size: 0.75rem; color: #64748b;">
                Base de dades: <strong style="color: #94a3b8;"><?= htmlspecialchars($currentLab['database_name']) ?></strong>
            </div>
            <div style="display: flex; gap: 10px;">
                <button id="btn-next-step" class="btn btn-success" style="display: none;">
                    Següent Pas ➡
                </button>
                <button id="btn-execute" class="btn btn-primary" onclick="executeCode()">
                    ▶ Executar (Ctrl+Enter)
                </button>
            </div>
        </div>

        <!-- Panell de Sortida de SQL Server -->
        <div class="output-pane">
            <div class="output-header">
                <span>TERMINAL DE SORTIDA (SQL Server 2025)</span>
                <span id="exec-status" style="font-size: 0.75rem;">A punt</span>
            </div>
            <div class="output-body" id="output-body">Prem "Executar" per provar el teu codi T-SQL a la base de dades local.</div>
        </div>
    </div>

    <!-- Panell Dret: Tutor Socràtic amb IA -->
    <div class="tutor-pane">
        <div class="tutor-header">
            <span style="font-size: 1.2rem;">🧑‍🏫</span>
            <div>
                <h3 style="font-size: 0.95rem; font-weight: 600; color: #f8fafc;">Tutor Socràtic ASIX</h3>
                <span style="font-size: 0.72rem; color: #4ade80;">● En línia · Mode Guia</span>
            </div>
        </div>

        <div class="tutor-messages" id="tutor-messages">
            <div class="message-bubble tutor">
                Hola! Soc el teu tutor de BBDD. Estic preparat per revisar el teu codi de <strong><?= htmlspecialchars($currentLab['title']) ?></strong>.
                <br><br>
                Escriu el codi a l'editor seguint les instruccions del pas actual. Si t'encallis o surt algun error estrany a SQL Server, pregunta'm o demana una pista!
            </div>
        </div>

        <div class="tutor-actions">
            <!-- Pistes escalonades -->
            <div style="font-size: 0.72rem; color: #94a3b8; margin-bottom: 2px;">Pistes escalonades:</div>
            <div class="hints-row">
                <button class="hint-btn" onclick="requestHint(1)" title="Orientació bàsica">
                    Nivell 1 (-5 XP)
                </button>
                <button class="hint-btn" onclick="requestHint(2)" title="Concepte o funció clau">
                    Nivell 2 (-15 XP)
                </button>
                <button class="hint-btn" onclick="requestHint(3)" title="Fragment de codi de suport">
                    Nivell 3 (-30 XP)
                </button>
            </div>

            <!-- Formulari de xat -->
            <form id="tutor-form" class="tutor-input-row" style="margin-top: 6px;">
                <input type="text" id="tutor-input" placeholder="Pregunta un dubte al tutor..." autocomplete="off">
                <button type="submit" class="btn btn-primary" style="padding: 6px 12px; font-size: 0.8rem;">
                    Enviar
                </button>
            </form>
        </div>
    </div>
</div>
