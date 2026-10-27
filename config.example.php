<?php
/**
 * Fitxer de configuració de SQL Tutor ASIX
 * No compartir credencials privades en el control de versions.
 */

return [
    // Ruta absoluta de la carpeta del temari de classe (només lectura)
    'syllabus_path' => 'C:\\Users\\josep\\OneDrive\\Escritorio\\educem\\ASIX2\\BBDD',

    // Execució SQL Server Local (T-SQL)
    'sql_server' => [
        'host' => 'localhost',
        'sqlcmd_path' => 'C:\\Program Files\\Microsoft SQL Server\\Client SDK\\ODBC\\180\\Tools\\Binn\\SQLCMD.EXE',
        'default_db' => 'HelpdeskTIC',
        'timeout_seconds' => 5, // Timeout màxim d'execució sandbox per proc_open
        'max_memory_mb' => 128,
    ],

    // IA Tutor Socràtic
    'ai' => [
        // Proveïdor: 'claude', 'gemini' o 'offline'
        'provider' => 'offline', 
        'api_key' => '', // Clau de Claude (Anthropic) o Gemini
        'model' => 'claude-3-5-sonnet-20241022', // O gemini-1.5-flash / gemini-2.0-flash
        'max_tokens' => 800,
        'temperature' => 0.3,
        // Límits per controlar la despesa
        'daily_request_limit' => 50,
    ],

    // Seguretat
    'security' => [
        // Només permetre connexions locals per seguretat ASIX
        'localhost_only' => true,
        // Paraules clau prohibides en el sandbox SQL per evitar problemes al sistema
        'blocked_sql_keywords' => [
            'xp_cmdshell',
            'sp_configure',
            'SHUTDOWN',
            'ALTER DATABASE master',
            'DROP DATABASE master',
            'EXEC master..',
        ],
    ],
];
