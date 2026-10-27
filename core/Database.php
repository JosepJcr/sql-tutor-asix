<?php
declare(strict_types=1);

namespace SqlTutor;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $dbPath = __DIR__ . '/../data/app.db';
            $dir = dirname($dbPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            try {
                self::$instance = new PDO('sqlite:' . $dbPath);
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                // Activar claus foranes a SQLite
                self::$instance->exec('PRAGMA foreign_keys = ON;');
                // Millorar rendiment d'escriptura
                self::$instance->exec('PRAGMA journal_mode = WAL;');
            } catch (PDOException $e) {
                die('Error connectant a la base de dades local SQLite: ' . $e->getMessage());
            }
        }

        return self::$instance;
    }
}
