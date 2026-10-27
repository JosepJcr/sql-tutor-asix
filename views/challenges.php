<?php
/**
 * Vista de Reptes i Repàs Espaiat
 */
?>

<div style="max-width: 900px; margin: 0 auto;">
    <div class="card" style="background: linear-gradient(135deg, #1e293b, #0f172a); border-color: #a855f7;">
        <h1 style="font-size: 1.4rem; color: #f8fafc; margin-bottom: 6px;">
            ⚡ Reptes Ràpids & Repàs Espaiat
        </h1>
        <p style="color: #94a3b8; font-size: 0.9rem;">
            Posa a prova la teva capacitat de depuració de codi T-SQL. Identifica errors típics de sintaxi, abast de variables i transaccions per guanyar punts extra d'experiència (+35 XP per encert).
        </p>
    </div>

    <!-- Alert de Repàs Espaiat -->
    <?php if (!empty($pendingReviews)): ?>
        <div class="card" style="border-left: 4px solid #eab308; background: rgba(234, 179, 8, 0.05);">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 1.5rem;">🧠</span>
                <div>
                    <h3 style="font-size: 1rem; color: #fde047; margin-bottom: 2px;">
                        Repàs espaiat recomanat per a avui
                    </h3>
                    <p style="font-size: 0.85rem; color: #cbd5e1;">
                        Tens conceptes que vas fallar fa uns dies pendents de repassar (com <em>Ordre de validacions</em> o <em>Paràmetres OUTPUT</em>). Resoldre els reptes d'avui t'ajudarà a consolidar-los!
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Llista de Reptes -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <?php foreach ($challenges as $ch): ?>
            <div class="card challenge-card" data-challenge-id="<?= htmlspecialchars($ch['id']) ?>">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <div>
                        <span style="font-size: 0.75rem; background: #581c87; color: #d8b4fe; padding: 2px 8px; border-radius: 4px; font-weight: 700;">
                            <?= htmlspecialchars($ch['badge']) ?>
                        </span>
                        <h2 style="font-size: 1.1rem; color: #f8fafc; margin-top: 6px;">
                            <?= htmlspecialchars($ch['title']) ?>
                        </h2>
                    </div>
                    <span style="font-size: 0.85rem; color: #eab308; font-weight: 600;">
                        +35 XP
                    </span>
                </div>

                <p style="font-size: 0.88rem; color: #cbd5e1; margin-bottom: 14px;">
                    <?= htmlspecialchars($ch['description']) ?>
                </p>

                <!-- Fragment de codi del repte -->
                <pre style="background: #0b1120; border: 1px solid var(--border-color); border-radius: 8px; padding: 12px 16px; font-family: var(--font-mono); font-size: 0.85rem; color: #38bdf8; overflow-x: auto; margin-bottom: 15px;"><code><?= htmlspecialchars($ch['code']) ?></code></pre>

                <!-- Opcions -->
                <div class="challenge-options" style="display: flex; flex-direction: column; gap: 8px;">
                    <?php foreach ($ch['options'] as $idx => $opt): ?>
                        <div class="quiz-option challenge-option" data-index="<?= $idx ?>">
                            <?= htmlspecialchars($opt) ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="challenge-feedback" style="display: none; margin-top: 14px; padding: 12px; border-radius: 6px; font-size: 0.88rem; background: #0f172a; border-left: 3px solid #38bdf8;"></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
