<?php
/**
 * Vista de Dashboard / Mapa de Temes
 */
?>

<div style="max-width: 1000px; margin: 0 auto;">
    <!-- Targeta de Benvinguda i Progrés de Nivell -->
    <div class="card" style="background: linear-gradient(135deg, #1e293b, #0f172a); border-color: #0284c7;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <div>
                <h1 style="font-size: 1.5rem; color: #f8fafc;">Benvingut/da al Laboratori SQL ASIX</h1>
                <p style="color: #94a3b8; font-size: 0.9rem;">
                    Practica T-SQL, funcions escalars i stored procedures directament basats en els exercicis de classe de David Porti.
                </p>
            </div>
            <a href="index.php?page=lab&ex=LAB_SP_01" class="btn btn-primary" style="padding: 10px 20px;">
                🚀 Continuar Laboratori
            </a>
        </div>

        <div style="margin-top: 10px;">
            <div style="display: flex; justify-content: space-between; font-size: 0.8rem; margin-bottom: 5px; color: #94a3b8;">
                <span>Progrés cap al següent nivell</span>
                <span><?= ($userProfile['xp'] ?? 0) % 100 ?> / 100 XP</span>
            </div>
            <div style="height: 8px; background: #334155; border-radius: 4px; overflow: hidden;">
                <div style="width: <?= ($userProfile['xp'] ?? 0) % 100 ?>%; height: 100%; background: #38bdf8; transition: width 0.3s;"></div>
            </div>
        </div>
    </div>

    <!-- Mapa / Arbre de Temes -->
    <h2 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 15px; color: #f1f5f9;">
        🗺️ Arbre de Competències
    </h2>

    <div class="roadmap-grid">
        <?php foreach ($topics as $t): ?>
            <div class="topic-card" style="<?= ($t['is_unlocked']) ? '' : 'opacity: 0.6;' ?>">
                <div>
                    <div class="topic-header">
                        <span class="topic-icon"><?= htmlspecialchars($t['icon'] ?? '📘') ?></span>
                        <div>
                            <h3 style="font-size: 1.05rem; font-weight: 600; color: #f8fafc;">
                                <?= htmlspecialchars($t['title']) ?>
                            </h3>
                            <span style="font-size: 0.75rem; color: <?= ($t['is_unlocked']) ? '#4ade80' : '#94a3b8' ?>;">
                                <?= ($t['is_unlocked']) ? '● Desbloquejat' : '🔒 Bloquejat' ?>
                            </span>
                        </div>
                    </div>

                    <p style="font-size: 0.85rem; color: #94a3b8; margin-bottom: 20px;">
                        <?= htmlspecialchars($t['description']) ?>
                    </p>
                </div>

                <div style="display: flex; gap: 10px;">
                    <?php if ($t['is_unlocked']): ?>
                        <a href="index.php?page=learn&topic=<?= urlencode($t['slug']) ?>" class="btn btn-secondary" style="flex: 1; font-size: 0.8rem;">
                            📖 Teoria
                        </a>
                        <a href="index.php?page=lab&topic=<?= urlencode($t['slug']) ?>" class="btn btn-primary" style="flex: 1; font-size: 0.8rem;">
                            🧪 Pràctica
                        </a>
                    <?php else: ?>
                        <button class="btn btn-secondary" style="width: 100%; cursor: not-allowed;" disabled>
                            Pendent al temari
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Panell de seguretat ASIX explicat -->
    <div class="card" style="margin-top: 30px; border-left: 4px solid #38bdf8;">
        <h3 style="font-size: 1.05rem; color: #38bdf8; margin-bottom: 8px;">
            🔒 Per què és segura l'execució en aquesta Sandbox? (Nota per a estudiants ASIX)
        </h3>
        <p style="font-size: 0.85rem; color: #cbd5e1; line-height: 1.6;">
            Aquesta aplicació executa codi SQL real contra la teva instància local mitjançant la utilitat <code>sqlcmd</code> llançada des d'un procés aïllat amb <code>proc_open</code>. 
            Com a administradors de sistemes, és fonamental implementar <strong>timeout de seguretat (5 segons)</strong> per evitar que consultes pesants o bucles infinits col·lapsin la CPU, 
            bloqueig d'ordres destructives (com <code>xp_cmdshell</code>) i restricció estricta d'accés exclusivament a <code>localhost</code>.
        </p>
    </div>
</div>
