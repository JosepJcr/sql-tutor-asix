<?php
declare(strict_types=1);

namespace SqlTutor;

class SqlExecutor
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function execute(string $sqlCode, string $database = 'HelpdeskTIC'): array
    {
        // 1. Validació de paraules clau bloquejades per seguretat
        $blocked = $this->checkBlockedKeywords($sqlCode);
        if ($blocked !== null) {
            return [
                'success' => false,
                'output' => '',
                'error' => "Ordre bloquejada per seguretat del laboratori: '{$blocked}'.",
                'explained_error' => "Com a mesura de seguretat en aquest entorn ASIX, no està permès executar ordres de configuració o crides al sistema operatiu com xp_cmdshell o SHUTDOWN.",
                'execution_time_ms' => 0
            ];
        }

        // 2. Comprovar ruta de sqlcmd
        $sqlcmd = $this->config['sql_server']['sqlcmd_path'] ?? 'sqlcmd';
        $timeout = (int)($this->config['sql_server']['timeout_seconds'] ?? 5);

        // 3. Crear fitxer temporal al sandbox
        $sandboxDir = __DIR__ . '/../data/sandbox';
        if (!is_dir($sandboxDir)) {
            mkdir($sandboxDir, 0777, true);
        }

        $tempFile = $sandboxDir . '/exec_' . uniqid('', true) . '.sql';
        
        // Assegurar codificació UTF-8 neta
        file_put_contents($tempFile, "\xEF\xBB\xBF" . $sqlCode);

        // 4. Construir comanda sqlcmd
        // -S: Servidor
        // -E: Connexió de confiança de Windows
        // -C: TrustServerCertificate (imprescindible a SQL Server modern)
        // -d: Base de dades inicial
        // -i: Fitxer d'entrada
        // -y 0: No truncar tipus text/varchar
        $cmd = sprintf(
            '"%s" -S localhost -E -C -d %s -i "%s" -y 0',
            $sqlcmd,
            escapeshellarg($database),
            $tempFile
        );

        $descriptors = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w']  // stderr
        ];

        $startTime = microtime(true);
        $process = @proc_open($cmd, $descriptors, $pipes);

        if (!is_resource($process)) {
            @unlink($tempFile);
            return [
                'success' => false,
                'output' => '',
                'error' => "No s'ha pogut iniciar el procés sqlcmd.",
                'explained_error' => "Comprova que el servei SQL Server estigui iniciat a Windows i que sqlcmd estigui a la ruta especificada.",
                'execution_time_ms' => 0
            ];
        }

        fclose($pipes[0]);

        // Maneig de timeout de seguretat (5 segons per defecte)
        $timeoutMicroseconds = $timeout * 1000000;
        $stdout = '';
        $stderr = '';
        $hasTimedOut = false;

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $startWait = microtime(true);
        while (true) {
            $stdout .= stream_get_contents($pipes[1]);
            $stderr .= stream_get_contents($pipes[2]);

            $status = proc_get_status($process);
            if (!$status['running']) {
                break;
            }

            if ((microtime(true) - $startWait) > $timeout) {
                // Matar procés si s'excedeix el temps (p. ex. bucle WHILE infinit)
                proc_terminate($process, 9);
                $hasTimedOut = true;
                break;
            }

            usleep(25000); // 25ms
        }

        $stdout .= stream_get_contents($pipes[1]);
        $stderr .= stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        @unlink($tempFile);

        $execTimeMs = round((microtime(true) - $startTime) * 1000, 2);

        if ($hasTimedOut) {
            return [
                'success' => false,
                'output' => $stdout,
                'error' => "Temps d'execució superat (Timeout de {$timeout}s).",
                'explained_error' => "El codi ha trigat massa a executar-se. Probablement hi ha un bucle `WHILE` sense condició d'increment, un bloqueig de transacció o una consulta molt pesant.",
                'execution_time_ms' => $execTimeMs
            ];
        }

        // Comprovar errors a la sortida de SQL Server (en anglès o castellà)
        $combinedOutput = $stdout . "\n" . $stderr;
        $isError = false;
        $errorMessage = '';

        if (preg_match('/(Msg|Mensaje)\s+\d+,\s+(Level|Nivel)\s+\d+,\s+(State|Estado)\s+\d+/i', $combinedOutput) ||
            preg_match('/(Msg|Mensaje)\s+\d+,/i', $combinedOutput)) {
            $isError = true;
            $errorMessage = $this->extractSqlError($combinedOutput);
        }

        $explainedError = $isError ? $this->translateSqlError($errorMessage) : null;

        return [
            'success' => !$isError,
            'output' => trim($stdout),
            'error' => $errorMessage,
            'explained_error' => $explainedError,
            'execution_time_ms' => $execTimeMs
        ];
    }

    private function checkBlockedKeywords(string $sql): ?string
    {
        $blockedList = $this->config['security']['blocked_sql_keywords'] ?? [];
        foreach ($blockedList as $keyword) {
            if (stripos($sql, $keyword) !== false) {
                return $keyword;
            }
        }
        return null;
    }

    private function extractSqlError(string $output): string
    {
        $lines = explode("\n", $output);
        $errorLines = [];
        foreach ($lines as $line) {
            if (preg_match('/(Msg|Mensaje|Error|Level|Nivel)\s+/i', $line) ||
                stripos($line, 'Debe declarar') !== false ||
                stripos($line, 'Sintaxis incorrecta') !== false ||
                stripos($line, 'No se puede') !== false) {
                $errorLines[] = trim($line);
            }
        }
        return !empty($errorLines) ? implode("\n", array_slice($errorLines, 0, 4)) : trim($output);
    }

    public function translateSqlError(string $error): string
    {
        if (stripos($error, 'Must declare the scalar variable') !== false || stripos($error, 'Debe declarar la variable escalar') !== false) {
            preg_match('/[@\']([^\'\s]+)[\'\.]?/', $error, $m);
            $varName = $m[1] ?? 'variable';
            return "T-SQL no reconeix la variable `{$varName}`. Assegura't d'haver fet `DECLARE {$varName} <tipus>` a l'inici del lot i recorda que després d'un `GO` les variables anteriors deixen d'existir.";
        }

        if (stripos($error, 'Invalid object name') !== false || stripos($error, 'El nombre de objeto') !== false) {
            return "El nom de la taula, vista o funció no és vàlid. Comprova que la base de dades sigui correcta (`USE HelpdeskTIC;`) i que hagis indicat l'esquema `dbo.` si es tracta d'una funció.";
        }

        if (stripos($error, 'Cannot find either column') !== false || stripos($error, 'Invalid column name') !== false || stripos($error, 'Nombre de columna no válido') !== false) {
            return "Una de les columnes indicades no existeix a la taula. Revisa l'ortografia exacta de la columna a l'esquema de HelpdeskTIC.";
        }

        if (stripos($error, 'Incorrect syntax near') !== false || stripos($error, 'Sintaxis incorrecta cerca de') !== false) {
            return "Error de sintaxi T-SQL. Revisa si falta alguna coma, parèntesi, o si has posat un punt i coma `;` indegut (per exemple `END; ELSE` que és un error molt típic).";
        }

        if (stripos($error, 'PRIMARY KEY') !== false || stripos($error, 'UNIQUE KEY') !== false || stripos($error, 'restricción PRIMARY KEY') !== false || stripos($error, 'restricción UNIQUE KEY') !== false) {
            return "Violació de clau única o primària: s'està intentant afegir un valor que ja existeix (com un codi d'inventari o usuari repetit).";
        }

        if (stripos($error, 'FOREIGN KEY') !== false || stripos($error, 'restricción FOREIGN KEY') !== false) {
            return "Error d'integritat referencial (Clau Forana): estàs intentant referenciar un registre que no existeix, o donar de baixa un empleat que encara té equips o incidències associades!";
        }

        if (stripos($error, 'Cannot insert the value NULL') !== false || stripos($error, 'No se puede insertar el valor NULL') !== false) {
            return "S'ha intentat desar un valor buit (NULL) en una columna que és obligatòria (NOT NULL).";
        }

        return "SQL Server ha trobat una fallada d'execució. Revisa la línia indicada i les validacions prèvies.";
    }
}
