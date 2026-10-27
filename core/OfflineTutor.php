<?php
declare(strict_types=1);

namespace SqlTutor;

class OfflineTutor
{
    public static function getFeedback(array $exercise, int $stepNum, string $userCode, ?string $sqlError, string $userQuestion = ''): string
    {
        $steps = json_decode($exercise['steps_json'] ?? '[]', true);
        $currentStep = null;
        foreach ($steps as $s) {
            if ($s['step_num'] === $stepNum) {
                $currentStep = $s;
                break;
            }
        }

        // Si l'alumne té un error SQL de compilació/execució
        if (!empty($sqlError)) {
            if (stripos($sqlError, 'Must declare the scalar variable') !== false) {
                return "💡 **Pista del Tutor:** SQL Server no troba una de les variables que fas servir. Revisa les línies superiors: has fet un `DECLARE @variable tipus` abans d'assignar-li valor o usar-la? Recorda que a T-SQL totes les variables comencen amb `@`.";
            }
            if (stripos($sqlError, 'Invalid object name') !== false) {
                return "🔍 **On mirar:** SQL Server diu que no troba l'objecte (taula o vista). Estàs a la base de dades correcta (`USE HelpdeskTIC;`)? O potser has oblidat afegir el prefix `dbo.` davant del nom?";
            }
            if (stripos($sqlError, 'Incorrect syntax near') !== false) {
                return "⚠️ **Revisa la sintaxi:** Tens un petit error de puntuació o paraules clau. Comprova si has posat un punt i coma `;` entre un `END` i un `ELSE` (a T-SQL `END; ELSE` dona error de sintaxi!), o si t'has deixat algun parèntesi.";
            }
        }

        // Anàlisi de bones pràctiques d'ASIX en el codi de l'alumne
        if (stripos($userCode, 'CREATE PROCEDURE') !== false && stripos($userCode, 'SET NOCOUNT ON') === false) {
            return "💬 **Consell d'administrador/a ASIX:** Veig que estàs creant un Stored Procedure. Recordes quina instrucció recomana sempre en David Porti posar just després de `BEGIN` per evitar trànsit inútil de «(N rows affected)» a la xarxa?";
        }

        if (stripos($userCode, 'CREATE FUNCTION') !== false && stripos($userCode, 'dbo.') === false) {
            return "📌 **Recordatori important:** A SQL Server, és obligatori indicar l'esquema `dbo.` tant en crear com en cridar una funció escalar. Has posat `dbo.nomFuncio`?";
        }

        // Resposta socràtica orientada al pas actual
        if ($currentStep && !empty($currentStep['instruction'])) {
            $h1 = $currentStep['hints'][1] ?? "Llegeix amb calma l'objectiu del pas.";
            return "🎯 **Objectiu del pas {$stepNum}:**\n\n" .
                   $currentStep['instruction'] . "\n\n" .
                   "💭 **Pregunta guia:** " . $h1 . "\n\n" .
                   "_Pots fer servir el botó de pistes si vols un detall més concret del concepte o un fragment de codi._";
        }

        return "Molt bon intent! Revisa l'enunciat i prova d'executar el codi per veure la sortida real.";
    }

    public static function getTieredHint(array $exercise, int $stepNum, int $hintLevel): array
    {
        $steps = json_decode($exercise['steps_json'] ?? '[]', true);
        $currentStep = null;
        foreach ($steps as $s) {
            if ($s['step_num'] === $stepNum) {
                $currentStep = $s;
                break;
            }
        }

        $hintText = "No hi ha cap pista disponible per a aquest nivell.";
        $xpCost = match ($hintLevel) {
            1 => 5,
            2 => 15,
            3 => 30,
            default => 10
        };

        if ($currentStep && isset($currentStep['hints'][$hintLevel])) {
            $hintText = $currentStep['hints'][$hintLevel];
        }

        return [
            'level' => $hintLevel,
            'xp_cost' => $xpCost,
            'text' => $hintText
        ];
    }
}
