<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/ChallengesService.php';

$db = \SqlTutor\Database::getConnection();

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? $_GET['action'] ?? 'list';

if ($action === 'list') {
    $challenges = \SqlTutor\ChallengesService::getChallenges();
    $pendingReviews = \SqlTutor\ChallengesService::getPendingReviews($db);

    echo json_encode([
        'success' => true,
        'challenges' => $challenges,
        'pending_spaced_reviews' => $pendingReviews
    ]);
    exit;
}

if ($action === 'verify') {
    $challenges = \SqlTutor\ChallengesService::getChallenges();
    $chId = $input['challenge_id'] ?? '';
    $selectedOption = (int)($input['selected_option'] ?? -1);

    $found = null;
    foreach ($challenges as $c) {
        if ($c['id'] === $chId) {
            $found = $c;
            break;
        }
    }

    if (!$found) {
        echo json_encode(['success' => false, 'error' => 'Repte no trobat']);
        exit;
    }

    $isCorrect = ($selectedOption === $found['correct']);
    $xpEarned = 0;

    if ($isCorrect) {
        $xpEarned = 35;
        $db->exec("UPDATE user_profile SET xp = xp + {$xpEarned} WHERE id = 1");
    }

    echo json_encode([
        'success' => true,
        'is_correct' => $isCorrect,
        'explanation' => $found['explanation'],
        'xp_earned' => $xpEarned
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Acció invàlida']);
