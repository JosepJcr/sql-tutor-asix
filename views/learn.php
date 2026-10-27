<?php
/**
 * Vista d'Aprendre (Lliçons interactives)
 */
?>

<div style="max-width: 900px; margin: 0 auto;">
    <!-- Selector de lliçons -->
    <div style="display: flex; gap: 10px; margin-bottom: 20px; overflow-x: auto; padding-bottom: 5px;">
        <?php foreach ($allLessons as $l): ?>
            <a href="index.php?page=learn&id=<?= $l['id'] ?>" 
               class="btn <?= ($currentLesson['id'] == $l['id']) ? 'btn-primary' : 'btn-secondary' ?>" 
               style="font-size: 0.8rem; white-space: nowrap;">
                <?= htmlspecialchars($l['title']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Contingut de la lliçó -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 15px; margin-bottom: 20px;">
            <div>
                <span style="font-size: 0.75rem; color: #38bdf8; text-transform: uppercase; font-weight: 700;">
                    Lliçó Teòrica ASIX
                </span>
                <h1 style="font-size: 1.4rem; color: #f8fafc; margin-top: 4px;">
                    <?= htmlspecialchars($currentLesson['title']) ?>
                </h1>
            </div>
            <span style="font-size: 0.75rem; background: #334155; padding: 4px 10px; border-radius: 6px; color: #94a3b8;">
                📄 <?= htmlspecialchars($currentLesson['source_file'] ?? 'Temari Educem') ?>
            </span>
        </div>

        <!-- Text i Explicacions -->
        <div style="color: #cbd5e1; font-size: 0.95rem; line-height: 1.7;">
            <?= $currentLesson['content_html'] ?>
        </div>

        <!-- Bloc "Prova-ho" Executable -->
        <?php if (!empty($currentLesson['try_code'])): ?>
            <div style="margin-top: 30px;">
                <h3 style="font-size: 1.05rem; color: #38bdf8; margin-bottom: 8px;">
                    ⚡ Prova-ho tu mateix/a (Exemple executable)
                </h3>
                <p style="font-size: 0.85rem; color: #94a3b8; margin-bottom: 10px;">
                    Pots modificar els valors o el codi directament a l'editor i veure la sortida real retornada per SQL Server:
                </p>
                <div style="border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden; margin-bottom: 10px;">
                    <textarea class="try-editor"><?= htmlspecialchars($currentLesson['try_code']) ?></textarea>
                </div>
            </div>
        <?php endif; ?>

        <!-- Micro-Pregunta / Test de Retenció -->
        <?php if (!empty($currentLesson['quiz_json'])): 
            $quiz = json_decode($currentLesson['quiz_json'], true);
        ?>
            <div class="quiz-box" data-lesson-id="<?= $currentLesson['id'] ?>">
                <span style="font-size: 0.75rem; color: #38bdf8; font-weight: 700; text-transform: uppercase;">
                    ❓ Comprova el que has après (+15 XP)
                </span>
                <h4 style="font-size: 1rem; color: #f8fafc; margin: 8px 0 12px 0;">
                    <?= htmlspecialchars($quiz['question']) ?>
                </h4>

                <div class="quiz-options">
                    <?php foreach ($quiz['options'] as $idx => $opt): ?>
                        <div class="quiz-option" data-index="<?= $idx ?>">
                            <?= htmlspecialchars($opt) ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="quiz-feedback" style="display: none; margin-top: 12px; padding: 10px; border-radius: 6px; font-size: 0.85rem; background: #1e293b;"></div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Navegació entre lliçons -->
    <div style="display: flex; justify-content: space-between; margin-top: 20px;">
        <a href="index.php?page=dashboard" class="btn btn-secondary">
            ⬅ Tornar al Mapa
        </a>
        <a href="index.php?page=lab" class="btn btn-primary">
            Anar al Laboratori Pràctic ➡
        </a>
    </div>
</div>
