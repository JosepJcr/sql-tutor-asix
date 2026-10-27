<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/OfflineTutor.php';
require_once __DIR__ . '/../core/AITutor.php';

$config = require __DIR__ . '/../config.php';
$db = \SqlTutor\Database::getConnection();

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? 'ask'; // 'ask' o 'hint'
$exerciseCode = trim($input['exercise_code'] ?? '');
$stepNum = (int)($input['step_num'] ?? 1);
$userCode = trim($input['code'] ?? '');
$sqlOutput = trim($input['output'] ?? '');
$sqlError = trim($input['error'] ?? '');
$userMessage = trim($input['message'] ?? '');
$hintLevel = (int)($input['hint_level'] ?? 1);

// Obtenir exercici
$stmt = $db->prepare("SELECT * FROM lab_exercises WHERE exercise_code = ?");
$stmt->execute([$exerciseCode]);
$exercise = $stmt->fetch() ?: [
    'title' => 'Exercici lliure',
    'description' => 'Pràctica lliure de T-SQL',
    'steps_json' => '[]'
];

if ($action === 'hint') {
    $hintData = \SqlTutor\OfflineTutor::getTieredHint($exercise, $stepNum, $hintLevel);
    $cost = $hintData['xp_cost'];

    // Restar XP si l'usuari en té prou (com a mínim queda a 0)
    $profile = $db->query("SELECT xp FROM user_profile WHERE id = 1")->fetch();
    $currentXp = (int)($profile['xp'] ?? 0);
    $newXp = max(0, $currentXp - $cost);
    $db->exec("UPDATE user_profile SET xp = {$newXp} WHERE id = 1");

    echo json_encode([
        'success' => true,
        'hint_level' => $hintLevel,
        'hint_text' => $hintData['text'],
        'xp_cost' => $cost,
        'new_xp' => $newXp
    ]);
    exit;
}

// Acció 'ask' (Pregunta o petició de feedback)
$tutor = new \SqlTutor\AITutor($config, $db);
$reply = $tutor->ask($exercise, $stepNum, $userCode, $sqlOutput, $sqlError, $userMessage);

echo json_encode([
    'success' => true,
    'reply' => $reply
]);
