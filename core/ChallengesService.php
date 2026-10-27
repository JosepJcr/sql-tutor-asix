<?php
declare(strict_types=1);

namespace SqlTutor;

use PDO;

class ChallengesService
{
    public static function getChallenges(): array
    {
        return [
            [
                'id' => 'ch_01',
                'type' => 'find_bug',
                'title' => 'Troba l\'error: Funció en línia vs BEGIN...END',
                'badge' => 'Bug Hunter',
                'description' => "Un company de classe ha escrit aquesta funció per retornar una llista d'empleats per departament, però SQL Server li rebutja. On és l'error?",
                'code' => "CREATE OR ALTER FUNCTION dbo.llistar(@dept VARCHAR(50))\nRETURNS TABLE\nAS\nBEGIN\n    RETURN (SELECT nom, cognom1 FROM dbo.Empleats WHERE Departament = @dept);\nEND;\nGO",
                'options' => [
                    "A les funcions de taula en línia (RETURNS TABLE), no s'utilitza BEGIN...END sinó directament RETURN (SELECT...)",
                    "El paràmetre @dept hauria de ser de tipus INT",
                    "Falta posar SET NOCOUNT ON dins de la funció",
                    "Cal posar DROP FUNCTION abans del CREATE"
                ],
                'correct' => 0,
                'explanation' => "Exacte! Com vam veure a la diapositiva 27 del Tema 1: les funcions en línia (inline) no porten BEGIN...END. Es declaren directament amb RETURNS TABLE AS RETURN (SELECT ...);"
            ],
            [
                'id' => 'ch_02',
                'type' => 'what_prints',
                'title' => 'Què imprimeix aquest bloc T-SQL?',
                'badge' => 'Mental Execution',
                'description' => "Avalua mentalment el següent bloc condicional executat a SQL Server:",
                'code' => "DECLARE @total INT = 3;\nIF @total > 5\n    PRINT 'A';\nELSE IF @total > 0\n    PRINT 'B';\nELSE\n    PRINT 'C';",
                'options' => [
                    "A",
                    "B",
                    "C",
                    "Dona error de sintaxi perquè T-SQL no permet ELSE IF"
                ],
                'correct' => 1,
                'explanation' => "Correcte! @total val 3, que no és > 5 però sí > 0. Per tant s'executa la segona branca i imprimeix 'B'."
            ],
            [
                'id' => 'ch_03',
                'type' => 'find_bug',
                'title' => 'Troba l\'error: Paràmetre OUTPUT a l\'EXEC',
                'badge' => 'Parameter Pro',
                'description' => "Observa com s'està cridant aquest procediment emmagatzemat:",
                'code' => "-- Definició:\n-- CREATE PROCEDURE dbo.usp_Total @res INT OUTPUT AS ...\n\nDECLARE @total INT;\nEXEC dbo.usp_Total @total;\nSELECT @total AS Resultat;",
                'options' => [
                    "Falta la paraula clau OUTPUT a la sentència EXEC (ha de ser: EXEC dbo.usp_Total @total OUTPUT;)",
                    "No es pot fer un SELECT d'una variable que comenci per @",
                    "La variable @total s'ha d'inicialitzar obligatòriament a 1",
                    "El nom de la variable ha de coincidir amb el paràmetre de la definició"
                ],
                'correct' => 0,
                'explanation' => "Molt bé! Tal com s'adverteix a les diapositives de Stored Procedures, si no escrius OUTPUT també a la crida EXEC, la variable no rep el valor de tornada!"
            ],
            [
                'id' => 'ch_04',
                'type' => 'find_bug',
                'title' => 'Troba l\'error: Punt i coma entre END i ELSE',
                'badge' => 'Syntax Master',
                'description' => "Quin problema té aquest fragment de codi T-SQL?",
                'code' => "IF @total > 10\nBEGIN\n    PRINT 'Superat';\nEND;\nELSE\nBEGIN\n    PRINT 'Pendent';\nEND;",
                'options' => [
                    "A T-SQL no es pot posar un punt i coma ';' entre l'END i l'ELSE (END; ELSE dona error de sintaxi)",
                    "PRINT no pot contenir text en català",
                    "Falta afegir un GO després de cada PRINT",
                    "Les variables sempre han de ser numèriques"
                ],
                'correct' => 0,
                'explanation' => "Exacte! Diapositiva 9 del Tema 1: 'Compte: no posis ; entre END i ELSE (END; ELSE dona error de sintaxi)'."
            ]
        ];
    }

    public static function getPendingReviews(PDO $db): array
    {
        return $db->query("
            SELECT concept, COUNT(*) as count 
            FROM mistakes_log 
            WHERE date(review_due) <= date('now', 'localtime')
            GROUP BY concept
        ")->fetchAll();
    }
}
