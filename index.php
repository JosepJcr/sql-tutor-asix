<?php
declare(strict_types=1);

/**
 * SQL Tutor ASIX - Controlador Frontal (Router)
 */

require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Migrations.php';
require_once __DIR__ . '/core/Indexer.php';
require_once __DIR__ . '/core/ChallengesService.php';

$configFile = __DIR__ . '/config.php';
if (!file_exists($configFile)) {
    die("Error: No s'ha trobat el fitxer de configuració 'config.php'. Si us plau, copia 'config.example.php' a 'config.php'.");
}
$config = require $configFile;

// Inicialització de la base de dades SQLite local
$db = \SqlTutor\Database::getConnection();
\SqlTutor\Migrations::run($db);

// Sincronitzar automàticament el temari si és el primer cop
$filesCount = (int)$db->query("SELECT COUNT(*) FROM syllabus_files")->fetchColumn();
if ($filesCount === 0 && !empty($config['syllabus_path'])) {
    $indexer = new \SqlTutor\Indexer($db, $config['syllabus_path']);
    $indexer->sync();
}

// Dades de perfil de l'usuari
$userProfile = $db->query("SELECT * FROM user_profile WHERE id = 1")->fetch() ?: [
    'xp' => 0, 'level' => 1, 'streak_days' => 1, 'badges_json' => '[]'
];

// Router senzill de pàgines
$currentPage = $_GET['page'] ?? 'dashboard';
$viewHeader = 'Panell d\'aprenentatge';
$pageTitle = 'Mapa de Temes';

ob_start();

switch ($currentPage) {
    case 'learn':
        $viewHeader = 'Aprendre: Fonaments i Teoria ASIX';
        $pageTitle = 'Lliçons Teòriques';
        
        $allLessons = $db->query("SELECT id, topic_slug, title, order_num FROM lessons ORDER BY order_num ASC")->fetchAll();
        $lessonId = (int)($_GET['id'] ?? ($allLessons[0]['id'] ?? 1));
        
        $stmt = $db->prepare("SELECT * FROM lessons WHERE id = ?");
        $stmt->execute([$lessonId]);
        $currentLesson = $stmt->fetch() ?: $allLessons[0] ?? null;

        require __DIR__ . '/views/learn.php';
        break;

    case 'lab':
        $viewHeader = 'Laboratori T-SQL & Tutor Socràtic';
        $pageTitle = 'Laboratori de Pràctiques';

        $allLabs = $db->query("SELECT exercise_code, title, difficulty, database_name FROM lab_exercises ORDER BY id ASC")->fetchAll();
        $exCode = $_GET['ex'] ?? ($allLabs[0]['exercise_code'] ?? 'LAB_UDF_01');

        $stmt = $db->prepare("SELECT * FROM lab_exercises WHERE exercise_code = ?");
        $stmt->execute([$exCode]);
        $currentLab = $stmt->fetch() ?: $allLabs[0] ?? null;

        require __DIR__ . '/views/lab.php';
        break;

    case 'challenges':
        $viewHeader = 'Reptes Ràpids & Repàs Espaiat';
        $pageTitle = 'Reptes T-SQL';

        $challenges = \SqlTutor\ChallengesService::getChallenges();
        $pendingReviews = \SqlTutor\ChallengesService::getPendingReviews($db);

        require __DIR__ . '/views/challenges.php';
        break;

    case 'dashboard':
    default:
        $currentPage = 'dashboard';
        $viewHeader = 'Mapa de Temes & Progrés ASIX';
        $pageTitle = 'Mapa de Temes';

        $topics = $db->query("SELECT * FROM topics ORDER BY order_num ASC")->fetchAll();
        require __DIR__ . '/views/dashboard.php';
        break;
}

$content = ob_get_clean();
require __DIR__ . '/views/layout.php';
