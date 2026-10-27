<?php
declare(strict_types=1);

namespace SqlTutor;

use PDO;

class Migrations
{
    public static function run(PDO $db): void
    {
        $db->exec("
            CREATE TABLE IF NOT EXISTS user_profile (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT DEFAULT 'Alumne ASIX',
                xp INTEGER DEFAULT 0,
                level INTEGER DEFAULT 1,
                streak_days INTEGER DEFAULT 1,
                last_active_date TEXT,
                badges_json TEXT DEFAULT '[]'
            );

            CREATE TABLE IF NOT EXISTS syllabus_files (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                filename TEXT NOT NULL,
                file_path TEXT NOT NULL UNIQUE,
                file_hash TEXT NOT NULL,
                file_type TEXT NOT NULL,
                topic_slug TEXT NOT NULL,
                file_size INTEGER DEFAULT 0,
                indexed_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS topics (
                slug TEXT PRIMARY KEY,
                title TEXT NOT NULL,
                description TEXT,
                order_num INTEGER DEFAULT 0,
                is_unlocked INTEGER DEFAULT 1,
                icon TEXT DEFAULT '📘'
            );

            CREATE TABLE IF NOT EXISTS lessons (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                topic_slug TEXT NOT NULL,
                title TEXT NOT NULL,
                order_num INTEGER DEFAULT 0,
                content_html TEXT NOT NULL,
                try_code TEXT,
                quiz_json TEXT,
                source_file TEXT,
                FOREIGN KEY (topic_slug) REFERENCES topics(slug)
            );

            CREATE TABLE IF NOT EXISTS lab_exercises (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                topic_slug TEXT NOT NULL,
                exercise_code TEXT NOT NULL UNIQUE,
                title TEXT NOT NULL,
                difficulty TEXT DEFAULT 'Mitjà',
                database_name TEXT DEFAULT 'HelpdeskTIC',
                description TEXT NOT NULL,
                steps_json TEXT NOT NULL,
                initial_code TEXT,
                solution_code TEXT,
                source_file TEXT,
                FOREIGN KEY (topic_slug) REFERENCES topics(slug)
            );

            CREATE TABLE IF NOT EXISTS user_progress (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                item_type TEXT NOT NULL, -- 'lesson', 'lab', 'challenge'
                item_id TEXT NOT NULL,
                completed INTEGER DEFAULT 0,
                score INTEGER DEFAULT 0,
                hints_used INTEGER DEFAULT 0,
                attempts_count INTEGER DEFAULT 0,
                last_code TEXT,
                updated_at TEXT NOT NULL,
                UNIQUE(item_type, item_id)
            );

            CREATE TABLE IF NOT EXISTS mistakes_log (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                topic_slug TEXT,
                exercise_code TEXT,
                concept TEXT NOT NULL,
                code_attempt TEXT,
                error_message TEXT,
                review_due TEXT NOT NULL,
                created_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS ai_cache (
                cache_key TEXT PRIMARY KEY,
                prompt_hash TEXT NOT NULL,
                response_text TEXT NOT NULL,
                created_at TEXT NOT NULL
            );
        ");

        self::seedDefaultProfile($db);
        self::seedTopicsAndLessons($db);
        self::seedLabExercises($db);
    }

    private static function seedDefaultProfile(PDO $db): void
    {
        $stmt = $db->query("SELECT COUNT(*) FROM user_profile");
        if ((int)$stmt->fetchColumn() === 0) {
            $today = date('Y-m-d');
            $db->exec("
                INSERT INTO user_profile (id, username, xp, level, streak_days, last_active_date, badges_json)
                VALUES (1, 'Estudiant ASIX2', 150, 1, 1, '{$today}', '[\"primer_pas\"]')
            ");
        }
    }

    private static function seedTopicsAndLessons(PDO $db): void
    {
        $stmt = $db->query("SELECT COUNT(*) FROM topics");
        if ((int)$stmt->fetchColumn() === 0) {
            // Temes inicials basats en el temari real d'Educem
            $topics = [
                [
                    'slug' => 'tema01-udf',
                    'title' => 'Tema 1: Funcions definides per l\'usuari (UDF)',
                    'description' => 'Apreneu a programar en T-SQL, variables, estructures de control, funcions escalars i funcions de taula (inline i multisentència).',
                    'order_num' => 1,
                    'is_unlocked' => 1,
                    'icon' => '⚙️'
                ],
                [
                    'slug' => 'tema02-sp',
                    'title' => 'Tema 2: Stored Procedures (Procediments emmagatzemats)',
                    'description' => 'Automatització avançada amb SQL Server: paràmetres INPUT/OUTPUT, transaccions segures, codis de retorn, seguretat i bones pràctiques.',
                    'order_num' => 2,
                    'is_unlocked' => 1,
                    'icon' => '🚀'
                ],
                [
                    'slug' => 'tema03-triggers',
                    'title' => 'Tema 3: Triggers & Cursors (Properament)',
                    'description' => 'Control automàtic d\'esdeveniments DML (INSERT/UPDATE/DELETE) i iteració fila a fila en T-SQL.',
                    'order_num' => 3,
                    'is_unlocked' => 0,
                    'icon' => '🔒'
                ]
            ];

            $insertTopic = $db->prepare("INSERT INTO topics (slug, title, description, order_num, is_unlocked, icon) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($topics as $t) {
                $insertTopic->execute([$t['slug'], $t['title'], $t['description'], $t['order_num'], $t['is_unlocked'], $t['icon']]);
            }

            // Lliçons del Tema 1
            $lessons = [
                [
                    'topic_slug' => 'tema01-udf',
                    'title' => '1.1 Fonaments de T-SQL: Variables i Control de Flux',
                    'order_num' => 1,
                    'source_file' => 'BLOC01.Tema01.Funcions (UDF).pdf',
                    'content_html' => '
                        <h3>Què és T-SQL i per què programem a la base de dades?</h3>
                        <p>A l\'administració de sistemes (ASIX), programar directament dins del SGBD (SQL Server) ens permet automatitzar tasques i executar lògica de negoci a tocar de les dades, reduint el trànsit de xarxa.</p>
                        <div class="note-box">
                            <strong>Variables d\'usuari en T-SQL:</strong>
                            <p>Totes les variables d\'usuari comencen pel prefix <code>@</code> i es declaren amb <code>DECLARE</code>. Per assignar un valor usem <code>SET</code> o directament un <code>SELECT @var = columna FROM taula</code>.</p>
                        </div>
                        <p>Compte amb l\'abast: una variable només viu dins del seu lot (<em>batch</em>). Quan es troba un <code>GO</code>, les variables anteriors deixen d\'existir!</p>
                    ',
                    'try_code' => "DECLARE @total INT = 10;\nSET @total = @total + 5;\nSELECT @total AS Resultat;\n\nIF (@total >= 15)\n    PRINT 'Total superior o igual a 15';\nELSE\n    PRINT 'Total inferior a 15';",
                    'quiz_json' => json_encode([
                        'question' => "Què passa amb una variable @x declarada abans d'una instrucció GO?",
                        'options' => [
                            "Es manté activa fins que tanquem la sessió",
                            "Deixa d'existir perquè GO separa els lots d'execució",
                            "Es converteix en variable global de sistema @@x",
                            "Dona un error de compilació immediat"
                        ],
                        'correct' => 1,
                        'explanation' => "GO no és una ordre de T-SQL sinó una instrucció del client (SSMS, sqlcmd) que envia el lot. Les variables locals moren en acabar el lot."
                    ])
                ],
                [
                    'topic_slug' => 'tema01-udf',
                    'title' => '1.2 Què és una Funció d\'Usuari (UDF) Escalar?',
                    'order_num' => 2,
                    'source_file' => 'BLOC01.Tema01.Funcions (UDF).pdf',
                    'content_html' => '
                        <h3>Anatomia d\'una UDF Escalar</h3>
                        <p>Una funció escalar rep zero o més paràmetres i <strong>retorna un únic valor</strong> d\'un tipus de dades determinat (ex: <code>INT</code>, <code>DECIMAL</code>, <code>VARCHAR</code>).</p>
                        <ul>
                            <li><strong>Esquema obligatori:</strong> A SQL Server, per cridar una funció escalar és imprescindible posar l\'esquema: <code>SELECT dbo.nomFuncio(...)</code>.</li>
                            <li><strong>Immutabilitat:</strong> Una UDF <em>mai</em> pot fer modificacions sobre taules (prohibit <code>INSERT</code>, <code>UPDATE</code>, <code>DELETE</code> directes fora de variables de taula).</li>
                            <li><strong>Rendiment:</strong> Les UDF escalars s\'executen un cop per cada fila retornada a la consulta. En taules gegants cal vigilar el seu cost!</li>
                        </ul>
                    ',
                    'try_code' => "CREATE OR ALTER FUNCTION dbo.aplicarIVA\n(\n    @preu DECIMAL(10,2),\n    @iva DECIMAL(5,2)\n)\nRETURNS DECIMAL(10,2)\nAS\nBEGIN\n    RETURN ROUND(@preu * (1 + @iva / 100.0), 2);\nEND;\nGO\n\nSELECT dbo.aplicarIVA(100.00, 21.00) AS PreuFinal;",
                    'quiz_json' => json_encode([
                        'question' => "Per què cal escriure obligatòriament 'dbo.' en cridar una funció escalar a SQL Server?",
                        'options' => [
                            "Perquè sense esquema SQL Server no la distingeix d'una funció integrada de sistema",
                            "És opcional, funciona igual sense dbo.",
                            "Perquè dbo és l'únic usuari amb permís d'execució",
                            "Perquè ho exigeix el protocol TCP de connexió"
                        ],
                        'correct' => 0,
                        'explanation' => "A SQL Server la resolució de noms de funcions escalars exigeix l'especificació de l'esquema de dues parts (dbo.nomFuncio)."
                    ])
                ],
                [
                    'topic_slug' => 'tema02-sp',
                    'title' => '2.1 Introducció als Stored Procedures',
                    'order_num' => 1,
                    'source_file' => 'BLOC01.Tema02.Stored Procedures.pdf',
                    'content_html' => '
                        <h3>Stored Procedures: Potència i Administració</h3>
                        <p>A diferència de les funcions, els <strong>Stored Procedures (SP)</strong> són l\'eina principal de l\'administrador de sistemes per modificar la BD, implementar transaccions complexes i controlar la seguretat.</p>
                        <div class="note-box">
                            <strong>Avantatges clau per a ASIX:</strong>
                            <ol>
                                <li><strong>Seguretat:</strong> Pots donar permís <code>GRANT EXECUTE</code> sobre el SP a usuaris o apps sense que tinguin permís de <code>SELECT/UPDATE</code> directe a les taules.</li>
                                <li><strong>Rendiment:</strong> SQL Server desa el pla d\'execució a la memòria cau (<em>Plan Cache</em>).</li>
                                <li><strong>SET NOCOUNT ON:</strong> Evita que SQL Server enviï missatges inútils de tipus «(N rows affected)» que saturen la xarxa.</li>
                            </ol>
                        </div>
                    ',
                    'try_code' => "CREATE OR ALTER PROCEDURE dbo.usp_Salutacio\n    @Nom NVARCHAR(50)\nAS\nBEGIN\n    SET NOCOUNT ON;\n    PRINT N'Hola, administrador/a ' + @Nom + N'!';\nEND;\nGO\n\nEXEC dbo.usp_Salutacio N'Josep';",
                    'quiz_json' => json_encode([
                        'question' => "Per què és una bona pràctica posar SET NOCOUNT ON al principi de tot procediment emmagatzemat?",
                        'options' => [
                            "Perquè impedeix que les consultes fallin en cas d'error",
                            "Perquè estalvia trànsit de xarxa i evita que els clients confonguin el recompte de files amb un conjunt de resultats",
                            "Perquè augmenta la mida del log de transaccions",
                            "Perquè és obligatori per fer un COMMIT"
                        ],
                        'correct' => 1,
                        'explanation' => "Tal com explica David Porti a les diapositives, SET NOCOUNT ON suprimeix els missatges 'rows affected', reduint el trànsit client-servidor."
                    ])
                ],
                [
                    'topic_slug' => 'tema02-sp',
                    'title' => '2.2 Paràmetres OUTPUT i Transaccions Segures',
                    'order_num' => 2,
                    'source_file' => 'BLOC01.Tema02.Stored Procedures.pdf',
                    'content_html' => '
                        <h3>Paràmetres OUTPUT i Control d\'Errors</h3>
                        <p>Quan un procediment ha de retornar un o més valors a qui l\'ha cridat (en lloc d\'un resultat per pantalla), usem la paraula clau <code>OUTPUT</code> tant a la definició com a la instrucció <code>EXEC</code>.</p>
                        <div class="note-box">
                            <strong>Transaccions amb TRY...CATCH:</strong>
                            <p>Quan modifiquem taules relacionades (per exemple donar de baixa un empleat i alliberar els seus equips), les operacions han de ser atòmiques: o es fan totes, o es desfà tot amb <code>ROLLBACK TRANSACTION</code>.</p>
                        </div>
                    ',
                    'try_code' => "DECLARE @numIncidencies INT;\n-- Exemple de crida amb paràmetre OUTPUT\n-- EXEC dbo.usp_ComptarIncidenciesTecnic 1, @numIncidencies OUTPUT;\n-- SELECT @numIncidencies AS TotalObertes;",
                    'quiz_json' => json_encode([
                        'question' => "Què passa si oblidem posar la paraula clau OUTPUT a la sentència EXEC en cridar un procediment?",
                        'options' => [
                            "Donarà un error de sintaxi immediat",
                            "La variable no rebrà el valor i no donarà cap error, quedant amb el seu valor anterior o NULL",
                            "El servidor s'aturarà per timeout",
                            "La variable s'omplirà amb un 0 automàticament"
                        ],
                        'correct' => 1,
                        'explanation' => "És un dels errors més típics a SQL Server: si no escrius OUTPUT a l'EXEC, la variable no rep el valor de tornada i el SGBD no avisa d'error!"
                    ])
                ]
            ];

            $insertLesson = $db->prepare("INSERT INTO lessons (topic_slug, title, order_num, source_file, content_html, try_code, quiz_json) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($lessons as $l) {
                $insertLesson->execute([$l['topic_slug'], $l['title'], $l['order_num'], $l['source_file'], $l['content_html'], $l['try_code'], $l['quiz_json']]);
            }
        }
    }

    private static function seedLabExercises(PDO $db): void
    {
        $stmt = $db->query("SELECT COUNT(*) FROM lab_exercises");
        if ((int)$stmt->fetchColumn() === 0) {
            $labs = [
                // Laboratori 1: UDF diesLimitPrestec (De la teva classe)
                [
                    'topic_slug' => 'tema01-udf',
                    'exercise_code' => 'LAB_UDF_01',
                    'title' => 'Exercici 1: Funció diesLimitPrestec (UDF Escalar)',
                    'difficulty' => 'Fàcil - Mitjà',
                    'database_name' => 'HelpdeskTIC',
                    'source_file' => 'Exercici 1 - UDF.Funcions (Solució).sql',
                    'description' => "Crea una funció escalar anomenada <code>dbo.diesLimitPrestec(@prestecID INT)</code> que calculi quants dies queden per retornar un préstec (o quants dies de retard porta).\n\n" .
                                     "Tingues en compte:\n" .
                                     "1. Si el préstec no existeix o ja ha estat retornat, ha de retornar NULL.\n" .
                                     "2. El termini són 15 dies inicials més 7 dies addicionals per cada pròrroga.\n" .
                                     "3. Retorna un número positiu si queden dies, o negatiu si porta retard.",
                    'initial_code' => "-- Escriu aquí la teva funció escalar\nCREATE OR ALTER FUNCTION dbo.diesLimitPrestec(@prestecID INT)\nRETURNS INT\nAS\nBEGIN\n    -- Pas 1: Declara les variables necessàries (@dataPrestec, @prorroga, etc.)\n\n    RETURN 0;\nEND;\nGO",
                    'steps_json' => json_encode([
                        [
                            'step_num' => 1,
                            'instruction' => "Declara la funció escalar amb el nom 'dbo.diesLimitPrestec' que rebi un paràmetre enter '@prestecID INT' i retorni un 'INT'. Dins del cos, declara les variables @dataPrestec DATETIME2, @dataRetorn DATETIME2 i @prorroga INT.",
                            'validation_regex' => '/CREATE\s+OR\s+ALTER\s+FUNCTION\s+(dbo\.)?diesLimitPrestec/i',
                            'hints' => [
                                1 => "Revisa la sintaxi bàsica: CREATE OR ALTER FUNCTION dbo.diesLimitPrestec(@prestecID INT) RETURNS INT AS BEGIN ... END",
                                2 => "Recorda afegir 'RETURNS INT' just abans de l'AS, i declarar les variables amb el tipus adequat (DATETIME2 o DATE).",
                                3 => "Plantilla:\nCREATE OR ALTER FUNCTION dbo.diesLimitPrestec(@prestecID INT)\nRETURNS INT\nAS\nBEGIN\n    DECLARE @dataPrestec DATETIME2;\n    DECLARE @dataRetorn DATETIME2;\n    DECLARE @prorroga INT;\n    -- següent pas\n    RETURN 0;\nEND;\nGO"
                            ]
                        ],
                        [
                            'step_num' => 2,
                            'instruction' => "Fes un SELECT per assignar a les variables els valors de la taula Prestec filtrant per 'prestecID = @prestecID'. Comprova si @dataPrestec és NULL o si @dataRetorn NO és NULL per retornar NULL immediatament.",
                            'validation_regex' => '/WHERE\s+prestecID\s*=\s*@prestecID/i',
                            'hints' => [
                                1 => "Utilitza SELECT @dataPrestec = dataPrestec, @dataRetorn = dataRetorn, @prorroga = prorroga FROM Prestec WHERE prestecID = @prestecID;",
                                2 => "Fes servir condicionals IF: IF (@dataPrestec IS NULL OR @dataRetorn IS NOT NULL) RETURN NULL;",
                                3 => "Codi de suport:\nIF @dataPrestec IS NULL RETURN NULL;\nIF @dataRetorn IS NOT NULL RETURN NULL; -- ja està retornat!"
                            ]
                        ],
                        [
                            'step_num' => 3,
                            'instruction' => "Calcula els dies límit (15 + 7 * ISNULL(@prorroga, 0)) i resta la diferència de dies respecte la data actual amb DATEDIFF(DAY, @dataPrestec, GETDATE()). Retorna el resultat.",
                            'validation_regex' => '/(DATEDIFF|DATEADD)/i',
                            'hints' => [
                                1 => "Pots sumar els dies a la data de préstec amb DATEADD(DAY, 15 + 7 * @prorroga, @dataPrestec) i calcular el DATEDIFF amb GETDATE().",
                                2 => "O bé: @diesLimit = 15 + 7 * ISNULL(@prorroga,0); RETURN @diesLimit - DATEDIFF(DAY, @dataPrestec, GETDATE());",
                                3 => "Comprovació final: SELECT dbo.diesLimitPrestec(5) AS DiesRestants;"
                            ]
                        ]
                    ]),
                    'solution_code' => "CREATE OR ALTER FUNCTION dbo.diesLimitPrestec(@prestecID INT)\nRETURNS INT\nAS\nBEGIN\n    DECLARE @dataPrestec DATETIME2;\n    DECLARE @dataRetorn DATETIME2;\n    DECLARE @prorroga SMALLINT;\n    DECLARE @diesLimit INT;\n\n    SELECT \n        @dataPrestec = dataPrestec,\n        @dataRetorn = dataRetorn,\n        @prorroga = prorroga\n    FROM Prestec\n    WHERE prestecID = @prestecID;\n\n    IF @dataPrestec IS NULL\n        RETURN NULL;\n    IF @dataRetorn IS NOT NULL\n        RETURN NULL;\n\n    SET @diesLimit = 15 + 7 * ISNULL(@prorroga, 0);\n    RETURN @diesLimit - DATEDIFF(DAY, @dataPrestec, GETDATE());\nEND;\nGO"
                ],

                // Laboratori 2: Stored Procedure usp_LlistarEmpleatsDepartament (HelpdeskTIC)
                [
                    'topic_slug' => 'tema02-sp',
                    'exercise_code' => 'LAB_SP_01',
                    'title' => 'Exercici 1: Llistar empleats per departament (HelpdeskTIC)',
                    'difficulty' => 'Mitjà',
                    'database_name' => 'HelpdeskTIC',
                    'source_file' => 'Exercici 2 - Stored Proceudres (Enunciat).pdf',
                    'description' => "Crea un procediment anomenat <code>dbo.usp_LlistarEmpleatsDepartament</code> que rebi el nom d'un departament (@Departament NVARCHAR(50)) i mostri els seus empleats:\n" .
                                     "- IdEmpleat, NomComplet (usant CONCAT_WS), Usuari (o '(sense usuari)' si és NULL) i DataAlta.\n" .
                                     "- Si el departament no té cap empleat, no ha de mostrar cap taula buida, sinó el missatge: <code>No hi ha cap empleat al departament X.</code> i finalitzar amb RETURN.",
                    'initial_code' => "USE HelpdeskTIC;\nGO\n\nCREATE OR ALTER PROCEDURE dbo.usp_LlistarEmpleatsDepartament\n    @Departament NVARCHAR(50)\nAS\nBEGIN\n    SET NOCOUNT ON;\n\n    -- Pas 1: Comprova si el departament té algun empleat amb IF NOT EXISTS\n\n    -- Pas 2: Fes el SELECT amb CONCAT_WS i ISNULL\n\nEND;\nGO",
                    'steps_json' => json_encode([
                        [
                            'step_num' => 1,
                            'instruction' => "Afegeix la comprovació prèvia amb 'IF NOT EXISTS': si no hi ha cap empleat amb aquest departament a dbo.Empleats, mostra el missatge amb PRINT N'No hi ha cap empleat al departament ' + @Departament + N'.' i crida RETURN.",
                            'validation_regex' => '/IF\s+NOT\s+EXISTS\s*\(\s*SELECT.*FROM\s+(dbo\.)?Empleats/i',
                            'hints' => [
                                1 => "Fes una consulta ràpida amb IF NOT EXISTS (SELECT 1 FROM dbo.Empleats WHERE Departament = @Departament)",
                                2 => "Dins del bloc IF fes el PRINT del missatge exacte i posa un RETURN per aturar l'execució del procediment.",
                                3 => "Exemple:\nIF NOT EXISTS (SELECT IdEmpleat FROM dbo.Empleats WHERE Departament = @Departament)\nBEGIN\n    PRINT N'No hi ha cap empleat al departament ' + @Departament + N'.';\n    RETURN;\nEND"
                            ]
                        ],
                        [
                            'step_num' => 2,
                            'instruction' => "Fes la consulta SELECT retornant IdEmpleat, el nom complet concatenat amb CONCAT_WS, l'usuari amb ISNULL(@usuari, '(sense usuari)') i la DataAlta, ordenat per Cognom1.",
                            'validation_regex' => '/SELECT.*IdEmpleat.*CONCAT.*DataAlta/i',
                            'hints' => [
                                1 => "Utilitza CONCAT_WS(' ', Nom, Cognom1, Cognom2) AS NomComplet per evitar espais dobles si Cognom2 és NULL.",
                                2 => "Per controlar els usuaris pendents: ISNULL(Usuari, '(sense usuari)') AS Usuari.",
                                3 => "SELECT IdEmpleat, CONCAT_WS(' ', Nom, Cognom1, Cognom2) AS NomComplet, ISNULL(Usuari, '(sense usuari)') AS Usuari, DataAlta FROM dbo.Empleats WHERE Departament = @Departament ORDER BY Cognom1;"
                            ]
                        ],
                        [
                            'step_num' => 3,
                            'instruction' => "Executa el procediment amb EXEC dbo.usp_LlistarEmpleatsDepartament N'Informàtica' i després amb un departament inexistent com N'Màrqueting'.",
                            'validation_regex' => '/EXEC\s+(dbo\.)?usp_LlistarEmpleatsDepartament/i',
                            'hints' => [
                                1 => "Escriu EXEC dbo.usp_LlistarEmpleatsDepartament N'Informàtica'; a sota del procediment.",
                                2 => "Verifica que el resultat mostri els 3 tècnics d'informàtica (Oriol Font, Marta García, Jordi Puig).",
                                3 => "EXEC dbo.usp_LlistarEmpleatsDepartament N'Informàtica';\nEXEC dbo.usp_LlistarEmpleatsDepartament N'Màrqueting';"
                            ]
                        ]
                    ]),
                    'solution_code' => "USE HelpdeskTIC;\nGO\n\nCREATE OR ALTER PROCEDURE dbo.usp_LlistarEmpleatsDepartament\n    @Departament NVARCHAR(50)\nAS\nBEGIN\n    SET NOCOUNT ON;\n\n    IF NOT EXISTS (SELECT IdEmpleat FROM dbo.Empleats WHERE Departament = @Departament)\n    BEGIN\n        PRINT N'No hi ha cap empleat al departament ' + @Departament + N'.';\n        RETURN;\n    END\n\n    SELECT  IdEmpleat,\n            CONCAT_WS(' ', Nom, Cognom1, Cognom2)  AS NomComplet,\n            ISNULL(Usuari, '(sense usuari)')       AS Usuari,\n            DataAlta\n    FROM    dbo.Empleats\n    WHERE   Departament = @Departament\n    ORDER BY Cognom1;\nEND;\nGO\n\nEXEC dbo.usp_LlistarEmpleatsDepartament N'Informàtica';\nEXEC dbo.usp_LlistarEmpleatsDepartament N'Màrqueting';"
                ],

                // Laboratori 3: Procediment amb transaccions usp_TancarIncidencia
                [
                    'topic_slug' => 'tema02-sp',
                    'exercise_code' => 'LAB_SP_06',
                    'title' => 'Exercici 6: Tancar incidència i validacions d\'estat',
                    'difficulty' => 'Mitjà',
                    'database_name' => 'HelpdeskTIC',
                    'source_file' => 'Exercici 2 - Stored Proceudres (Enunciat).pdf',
                    'description' => "Crea el procediment <code>dbo.usp_TancarIncidencia(@idIncidencia INT)</code>.\n" .
                                     "1. Si la incidència no existeix, mostra el missatge 'Missatge: no existeix' i surt.\n" .
                                     "2. Si la incidència ja està tancada (DataTancament IS NOT NULL), mostra 'Missatge: ja està tancada' i no modifiquis res.\n" .
                                     "3. Si està oberta, actualitza DataTancament amb la data/hora actual i mostra: <code>OK: incidència X tancada</code>.",
                    'initial_code' => "USE HelpdeskTIC;\nGO\n\nCREATE OR ALTER PROCEDURE dbo.usp_TancarIncidencia\n    @idIncidencia INT\nAS\nBEGIN\n    SET NOCOUNT ON;\n    -- Completa les validacions i l'UPDATE\nEND;\nGO",
                    'steps_json' => json_encode([
                        [
                            'step_num' => 1,
                            'instruction' => "Comprova primer si la incidència existeix a la taula Incidencies. Si NO existeix, mostra 'Missatge: no existeix' amb PRINT i acaba amb RETURN.",
                            'validation_regex' => '/IF\s+NOT\s+EXISTS.*FROM\s+(dbo\.)?Incidencies/i',
                            'hints' => [
                                1 => "És crucial comprovar primer l'existència abans de comprovar l'estat!",
                                2 => "IF NOT EXISTS (SELECT 1 FROM dbo.Incidencies WHERE IdIncidencia = @idIncidencia)",
                                3 => "IF NOT EXISTS (SELECT IdIncidencia FROM dbo.Incidencies WHERE IdIncidencia = @idIncidencia)\nBEGIN\n    PRINT N'Missatge: no existeix';\n    RETURN;\nEND;"
                            ]
                        ],
                        [
                            'step_num' => 2,
                            'instruction' => "Comprova si la incidència ja té DataTancament assignada (IS NOT NULL). Si ja està tancada, mostra 'Missatge: ja està tancada' i acaba amb RETURN.",
                            'validation_regex' => '/DataTancament\s+IS\s+NOT\s+NULL/i',
                            'hints' => [
                                1 => "IF EXISTS (SELECT 1 FROM dbo.Incidencies WHERE IdIncidencia = @idIncidencia AND DataTancament IS NOT NULL)",
                                2 => "Mostra el missatge i crida RETURN sense fer cap UPDATE per no alterar la data original.",
                                3 => "IF EXISTS (SELECT IdIncidencia FROM dbo.Incidencies WHERE IdIncidencia = @idIncidencia AND DataTancament IS NOT NULL)\nBEGIN\n    PRINT N'Missatge: ja està tancada';\n    RETURN;\nEND;"
                            ]
                        ],
                        [
                            'step_num' => 3,
                            'instruction' => "Fes l'UPDATE posant DataTancament = SYSDATETIME() i mostra amb PRINT: 'OK: incidència ' + CAST(@idIncidencia AS NVARCHAR(10)) + ' tancada'.",
                            'validation_regex' => '/UPDATE\s+(dbo\.)?Incidencies\s+SET\s+DataTancament/i',
                            'hints' => [
                                1 => "Fes UPDATE dbo.Incidencies SET DataTancament = SYSDATETIME() WHERE IdIncidencia = @idIncidencia;",
                                2 => "Usa PRINT amb CAST(@idIncidencia AS NVARCHAR(10))",
                                3 => "UPDATE dbo.Incidencies SET DataTancament = SYSDATETIME() WHERE IdIncidencia = @idIncidencia;\nPRINT N'OK: incidència ' + CAST(@idIncidencia AS NVARCHAR(10)) + N' tancada';"
                            ]
                        ]
                    ]),
                    'solution_code' => "USE HelpdeskTIC;\nGO\n\nCREATE OR ALTER PROCEDURE dbo.usp_TancarIncidencia\n    @idIncidencia INT\nAS\nBEGIN\n    SET NOCOUNT ON;\n\n    -- 1) Validació d'existència\n    IF NOT EXISTS (SELECT IdIncidencia FROM dbo.Incidencies WHERE IdIncidencia = @idIncidencia)\n    BEGIN\n        PRINT N'Missatge: no existeix';\n        RETURN;\n    END;\n\n    -- 2) Validació d'estat ja tancada\n    IF EXISTS (SELECT IdIncidencia FROM dbo.Incidencies WHERE IdIncidencia = @idIncidencia AND DataTancament IS NOT NULL)\n    BEGIN\n        PRINT N'Missatge: ja està tancada';\n        RETURN;\n    END;\n\n    -- 3) Tancament\n    UPDATE dbo.Incidencies\n    SET DataTancament = SYSDATETIME()\n    WHERE IdIncidencia = @idIncidencia;\n\n    PRINT N'OK: incidència ' + CAST(@idIncidencia AS NVARCHAR(10)) + N' tancada';\nEND;\nGO\n\nEXEC dbo.usp_TancarIncidencia 5;"
                ]
            ];

            $insertLab = $db->prepare("INSERT INTO lab_exercises (topic_slug, exercise_code, title, difficulty, database_name, source_file, description, steps_json, initial_code, solution_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($labs as $lab) {
                $insertLab->execute([
                    $lab['topic_slug'],
                    $lab['exercise_code'],
                    $lab['title'],
                    $lab['difficulty'],
                    $lab['database_name'],
                    $lab['source_file'],
                    $lab['description'],
                    $lab['steps_json'],
                    $lab['initial_code'],
                    $lab['solution_code']
                ]);
            }
        }
    }
}
