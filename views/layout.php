<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'SQL Tutor ASIX') ?> | Educem ASIX2</title>
    
    <!-- CodeMirror 5 CDN per a ressaltat T-SQL -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/material-darker.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/hint/show-hint.min.css">
    
    <!-- CSS Propi -->
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
    <!-- Barra Lateral de Navegació -->
    <aside class="sidebar">
        <div class="brand">
            <span>💾 SQL Tutor</span>
            <span class="tag">ASIX 2</span>
        </div>

        <ul class="nav-links">
            <li>
                <a href="index.php?page=dashboard" class="<?= ($currentPage === 'dashboard') ? 'active' : '' ?>">
                    <span>🗺️</span> Mapa de Temes
                </a>
            </li>
            <li>
                <a href="index.php?page=learn" class="<?= ($currentPage === 'learn') ? 'active' : '' ?>">
                    <span>📖</span> Aprendre (Lliçons)
                </a>
            </li>
            <li>
                <a href="index.php?page=lab" class="<?= ($currentPage === 'lab') ? 'active' : '' ?>">
                    <span>🧪</span> Laboratori & Tutor
                </a>
            </li>
            <li>
                <a href="index.php?page=challenges" class="<?= ($currentPage === 'challenges') ? 'active' : '' ?>">
                    <span>⚡</span> Reptes & Repàs
                </a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <button id="btn-sync-syllabus" class="btn btn-secondary" style="width: 100%; margin-bottom: 10px; font-size: 0.8rem;">
                🔄 Actualitzar temari
            </button>
            <p style="font-size: 0.72rem; color: #64748b; text-align: center;">
                SGBD: SQL Server 2025 (Local)<br>
                Mòdul M0377 · Educem
            </p>
        </div>
    </aside>

    <!-- Contingut Principal -->
    <div class="main-wrapper">
        <!-- Barra Superior d'Estadístiques i Gamificació -->
        <header class="topbar">
            <h2 style="font-size: 1.15rem; font-weight: 600; color: #f1f5f9;">
                <?= htmlspecialchars($viewHeader ?? 'Panell d\'aprenentatge') ?>
            </h2>

            <div class="topbar-stats">
                <div class="stat-pill xp" title="Punts d'experiència acumulats">
                    <span>⭐</span> <span id="stat-xp-val"><?= (int)($userProfile['xp'] ?? 0) ?></span> XP
                </div>
                <div class="stat-pill level" title="Nivell d'administrador/a">
                    <span>🛡️</span> Nivell <?= (int)floor(($userProfile['xp'] ?? 0) / 100) + 1 ?>
                </div>
                <div class="stat-pill streak" title="Dies consecutius practicant">
                    <span>🔥</span> <?= (int)($userProfile['streak_days'] ?? 1) ?> dies
                </div>
            </div>
        </header>

        <!-- Contenidor de la Vista -->
        <main class="content-container">
            <?= $content ?? '' ?>
        </main>
    </div>

    <!-- Scripts de CodeMirror i App -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/sql/sql.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/hint/show-hint.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/hint/sql-hint.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
