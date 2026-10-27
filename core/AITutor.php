<?php
declare(strict_types=1);

namespace SqlTutor;

use PDO;

class AITutor
{
    private array $config;
    private ?PDO $db;

    public function __construct(array $config, ?PDO $db = null)
    {
        $this->config = $config;
        $this->db = $db;
    }

    public function ask(
        array $exercise,
        int $stepNum,
        string $userCode,
        ?string $sqlOutput,
        ?string $sqlError,
        string $userMessage = '',
        int $attempts = 1
    ): string {
        $provider = $this->config['ai']['provider'] ?? 'offline';
        $apiKey = trim($this->config['ai']['api_key'] ?? '');

        if ($provider === 'offline' || empty($apiKey)) {
            return OfflineTutor::getFeedback($exercise, $stepNum, $userCode, $sqlError, $userMessage);
        }

        // Si tenim clau, cridem l'API corresponent
        try {
            if ($provider === 'claude') {
                return $this->callClaude($exercise, $stepNum, $userCode, $sqlOutput, $sqlError, $userMessage, $attempts);
            } elseif ($provider === 'gemini') {
                return $this->callGemini($exercise, $stepNum, $userCode, $sqlOutput, $sqlError, $userMessage, $attempts);
            }
        } catch (\Throwable $e) {
            // En cas d'error de xarxa o quota, fallback suau
            $fallback = OfflineTutor::getFeedback($exercise, $stepNum, $userCode, $sqlError, $userMessage);
            return "[Mode offline per incidència de connexió]\n\n" . $fallback;
        }

        return OfflineTutor::getFeedback($exercise, $stepNum, $userCode, $sqlError, $userMessage);
    }

    private function buildSystemPrompt(array $exercise, int $stepNum, int $attempts): string
    {
        return "Ets en David, un tutor virtual i professor expert d'Administració de Bases de Dades (ASIX2) a Catalunya. " .
               "El teu alumne està aprenent T-SQL (Microsoft SQL Server), funcions UDF i Stored Procedures amb el temari oficial d'Educem. " .
               "\n\nDIRECTRIUS PEDAGÒGIQUES OBLIGATÒRIES:\n" .
               "1. Respon SEMPRE en CATALÀ, amb un to proper, engrescador i professional orientat a futurs administradors de sistemes (ASIX).\n" .
               "2. Fes servir el MÈTODE SOCRÀTIC: no donis la solució completa ni el codi sencer resolt de primeres. Fes-li preguntes clau, assenyala la línia o concepte que falla, i guia'l perquè ell mateix dedueixi l'error.\n" .
               "3. Només si l'alumne porta més de 3 intents fallits i demana explícitament veure la solució, pots proporcionar-li un fragment de codi explicat pas a pas.\n" .
               "4. Recorda bones pràctiques d'ASIX: 'SET NOCOUNT ON', prefix 'dbo.' en UDF escalars, prefix 'usp_' en procediments, ús de transaccions amb TRY/CATCH quan hi ha modificacions i control de NULL.\n" .
               "5. Respostes breus i clares (màxim 3 o 4 paràgrafs curts).";
    }

    private function callClaude(array $exercise, int $stepNum, string $userCode, ?string $sqlOutput, ?string $sqlError, string $userMessage, int $attempts): string
    {
        $apiKey = $this->config['ai']['api_key'];
        $model = $this->config['ai']['model'] ?? 'claude-3-5-sonnet-20241022';

        $systemPrompt = $this->buildSystemPrompt($exercise, $stepNum, $attempts);

        $promptContent = "Exercici actual: {$exercise['title']}\n" .
                         "Pas número: {$stepNum}\n" .
                         "Descripció: {$exercise['description']}\n\n" .
                         "Codi actual escrit per l'alumne:\n```sql\n{$userCode}\n```\n\n" .
                         "Sortida de SQL Server:\n" . ($sqlOutput ?: '(sense sortida)') . "\n\n" .
                         "Error de SQL Server:\n" . ($sqlError ?: '(sense error)') . "\n\n" .
                         "Pregunta o dubte de l'alumne: " . ($userMessage ?: "M'he quedat encallat en aquest pas, em pots guiar?");

        $url = 'https://api.anthropic.com/v1/messages';
        $data = [
            'model' => $model,
            'max_tokens' => (int)($this->config['ai']['max_tokens'] ?? 800),
            'system' => $systemPrompt,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $promptContent
                ]
            ]
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01'
            ],
            CURLOPT_TIMEOUT => 15
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return OfflineTutor::getFeedback($exercise, $stepNum, $userCode, $sqlError, $userMessage);
        }

        $resData = json_decode($response, true);
        return $resData['content'][0]['text'] ?? OfflineTutor::getFeedback($exercise, $stepNum, $userCode, $sqlError, $userMessage);
    }

    private function callGemini(array $exercise, int $stepNum, string $userCode, ?string $sqlOutput, ?string $sqlError, string $userMessage, int $attempts): string
    {
        $apiKey = $this->config['ai']['api_key'];
        $model = $this->config['ai']['model'] ?? 'gemini-1.5-flash';

        $systemPrompt = $this->buildSystemPrompt($exercise, $stepNum, $attempts);

        $promptContent = "{$systemPrompt}\n\n" .
                         "Exercici actual: {$exercise['title']}\n" .
                         "Pas número: {$stepNum}\n" .
                         "Codi de l'alumne:\n{$userCode}\n\n" .
                         "Error SQL:\n{$sqlError}\n\n" .
                         "Dubte de l'alumne: {$userMessage}";

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $promptContent]
                    ]
                ]
            ]
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 15
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return OfflineTutor::getFeedback($exercise, $stepNum, $userCode, $sqlError, $userMessage);
        }

        $resData = json_decode($response, true);
        return $resData['candidates'][0]['content']['parts'][0]['text'] ?? OfflineTutor::getFeedback($exercise, $stepNum, $userCode, $sqlError, $userMessage);
    }
}
