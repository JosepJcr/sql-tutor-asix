<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Indexer.php';

$config = require __DIR__ . '/../config.php';
$db = \SqlTutor\Database::getConnection();

$action = $_GET['action'] ?? $_POST['action'] ?? 'sync';
$indexer = new \SqlTutor\Indexer($db, $config['syllabus_path']);

if ($action === 'sync') {
    $res = $indexer->sync();
    echo json_encode($res);
    exit;
}

if ($action === 'summary') {
    $summary = $indexer->getIndexedSummary();
    echo json_encode([
        'success' => true,
        'summary' => $summary
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Acció no reconeguda']);
