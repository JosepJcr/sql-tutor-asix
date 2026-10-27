<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../core/Database.php';

$db = \SqlTutor\Database::getConnection();

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? $_GET['action'] ?? 'get';

if ($action === 'get') {
    $profile = $db->query("SELECT * FROM user_profile WHERE id = 1")->fetch() ?: [
        'xp' => 0, 'level' => 1, 'streak_days' => 1, 'badges_json' => '[]'
    ];
    $completedCount = (int)$db->query("SELECT COUNT(*) FROM user_progress WHERE completed = 1")->fetchColumn();
    $attemptsCount = (int)$db->query("SELECT COUNT(*) FROM user_progress")->fetchColumn();

    $level = (int)floor(($profile['xp'] ?? 0) / 100) + 1;
    $progressInLevel = ($profile['xp'] ?? 0) % 100;

    echo json_encode([
        'success' => true,
        'profile' => [
            'username' => $profile['username'] ?? 'Estudiant ASIX',
            'xp' => (int)$profile['xp'],
            'level' => $level,
            'progress_in_level' => $progressInLevel,
            'streak_days' => (int)$profile['streak_days'],
            'badges' => json_decode($profile['badges_json'] ?? '[]', true),
            'completed_exercises' => $completedCount,
            'total_attempts' => $attemptsCount
        ]
    ]);
    exit;
}

if ($action === 'quiz_submit') {
    $lessonId = (int)($input['lesson_id'] ?? 0);
    $selectedOption = (int)($input['selected_option'] ?? -1);

    $stmt = $db->prepare("SELECT quiz_json FROM lessons WHERE id = ?");
    $stmt->execute([$lessonId]);
    $lesson = $stmt->fetch();

    if (!$lesson || empty($lesson['quiz_json'])) {
        echo json_encode(['success' => false, 'error' => 'Lliçó no trobada']);
        exit;
    }

    $quiz = json_decode($lesson['quiz_json'], true);
    $isCorrect = ($selectedOption === ($quiz['correct'] ?? 0));
    $xpEarned = 0;

    if ($isCorrect) {
        $xpEarned = 15;
        $db->exec("UPDATE user_profile SET xp = xp + {$xpEarned} WHERE id = 1");
        
        $db->prepare("
            INSERT INTO user_progress (item_type, item_id, completed, score, updated_at)
            VALUES ('quiz', ?, 1, 15, datetime('now', 'localtime'))
            ON CONFLICT(item_type, item_id) DO UPDATE SET score = score + 15, updated_at = excluded.updated_at
        ")->execute([(string)$lessonId]);
    }

    echo json_encode([
        'success' => true,
        'is_correct' => $isCorrect,
        'explanation' => $quiz['explanation'] ?? '',
        'xp_earned' => $xpEarned
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Acció invàlida']);
