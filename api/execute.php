<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/SqlExecutor.php';

$config = require __DIR__ . '/../config.php';

// Verificació de seguretat localhost
if (($config['security']['localhost_only'] ?? true) && !in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Accés denegat: només permès des de localhost per seguretat ASIX.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$code = trim($input['code'] ?? '');
$exerciseCode = trim($input['exercise_code'] ?? '');
$stepNum = (int)($input['step_num'] ?? 1);
$dbName = trim($input['db_name'] ?? ($config['sql_server']['default_db'] ?? 'HelpdeskTIC'));

if (empty($code)) {
    echo json_encode(['success' => false, 'error' => 'El codi SQL no pot estar buit.']);
    exit;
}

$executor = new \SqlTutor\SqlExecutor($config);
$result = $executor->execute($code, $dbName);

$db = \SqlTutor\Database::getConnection();
$stepPassed = false;
$nextStep = null;
$xpEarned = 0;

if ($result['success'] && !empty($exerciseCode)) {
    // Comprovar si l'exercici existeix
    $stmt = $db->prepare("SELECT * FROM lab_exercises WHERE exercise_code = ?");
    $stmt->execute([$exerciseCode]);
    $exercise = $stmt->fetch();

    if ($exercise) {
        $steps = json_decode($exercise['steps_json'] ?? '[]', true);
        $totalSteps = count($steps);
        $currentStepConfig = null;

        foreach ($steps as $s) {
            if ($s['step_num'] === $stepNum) {
                $currentStepConfig = $s;
                break;
            }
        }

        if ($currentStepConfig) {
            $regex = $currentStepConfig['validation_regex'] ?? '';
            // Validar que el codi contingui la sintaxi demanada al pas
            if (empty($regex) || @preg_match($regex, $code)) {
                $stepPassed = true;
                $xpEarned = 25; // 25 XP per pas superat

                // Guardar / actualitzar progrés
                $progStmt = $db->prepare("
                    INSERT INTO user_progress (item_type, item_id, completed, score, last_code, updated_at)
                    VALUES ('lab', ?, ?, ?, ?, datetime('now', 'localtime'))
                    ON CONFLICT(item_type, item_id) DO UPDATE SET
                        score = score + excluded.score,
                        last_code = excluded.last_code,
                        updated_at = excluded.updated_at
                ");
                $isCompleted = ($stepNum >= $totalSteps) ? 1 : 0;
                $progStmt->execute([$exerciseCode, $isCompleted, $xpEarned, $code]);

                // Sumar XP al perfil
                $db->exec("UPDATE user_profile SET xp = xp + {$xpEarned} WHERE id = 1");

                if ($stepNum < $totalSteps) {
                    $nextStep = $stepNum + 1;
                }
            }
        }
    }
} elseif (!$result['success'] && !empty($exerciseCode)) {
    // Registrar error per al repàs espaiat
    $logStmt = $db->prepare("
        INSERT INTO mistakes_log (topic_slug, exercise_code, concept, code_attempt, error_message, review_due, created_at)
        VALUES ('tema02-sp', ?, 'Sintaxi/Execució SQL', ?, ?, date('now', '+2 days'), datetime('now', 'localtime'))
    ");
    $logStmt->execute([$exerciseCode, $code, $result['error'] ?? '']);
}

echo json_encode([
    'success' => $result['success'],
    'output' => $result['output'],
    'error' => $result['error'],
    'explained_error' => $result['explained_error'],
    'step_passed' => $stepPassed,
    'next_step' => $nextStep,
    'xp_earned' => $xpEarned,
    'execution_time_ms' => $result['execution_time_ms']
]);
