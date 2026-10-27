<?php
declare(strict_types=1);

namespace SqlTutor;

use PDO;

class Indexer
{
    private PDO $db;
    private string $syllabusPath;

    public function __construct(PDO $db, string $syllabusPath)
    {
        $this->db = $db;
        $this->syllabusPath = rtrim($syllabusPath, "\\/");
    }

    public function sync(): array
    {
        if (!is_dir($this->syllabusPath)) {
            return [
                'success' => false,
                'message' => "La carpeta del temari no existeix o no és accessible: {$this->syllabusPath}",
                'scanned' => 0,
                'new' => 0,
                'modified' => 0,
                'unchanged' => 0,
                'new_topics' => []
            ];
        }

        $files = $this->scanDirectory($this->syllabusPath);
        $newCount = 0;
        $modCount = 0;
        $unchangedCount = 0;
        $newTopicsDiscovered = [];

        $selectStmt = $this->db->prepare("SELECT file_hash FROM syllabus_files WHERE file_path = ?");
        $insertStmt = $this->db->prepare("
            INSERT INTO syllabus_files (filename, file_path, file_hash, file_type, topic_slug, file_size, indexed_at)
            VALUES (?, ?, ?, ?, ?, ?, datetime('now', 'localtime'))
        ");
        $updateStmt = $this->db->prepare("
            UPDATE syllabus_files 
            SET file_hash = ?, file_size = ?, indexed_at = datetime('now', 'localtime')
            WHERE file_path = ?
        ");

        foreach ($files as $file) {
            $path = $file['path'];
            $hash = md5_file($path);
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $topicSlug = $this->detectTopicSlug($file['name'], $path);

            // Comprovar si el tema existeix a la taula topics, si no el creem
            $this->ensureTopicExists($topicSlug, $newTopicsDiscovered);

            $selectStmt->execute([$path]);
            $existing = $selectStmt->fetch();

            if (!$existing) {
                $insertStmt->execute([
                    $file['name'],
                    $path,
                    $hash,
                    $ext,
                    $topicSlug,
                    $file['size']
                ]);
                $newCount++;
            } elseif ($existing['file_hash'] !== $hash) {
                $updateStmt->execute([$hash, $file['size'], $path]);
                $modCount++;
            } else {
                $unchangedCount++;
            }
        }

        return [
            'success' => true,
            'message' => 'Temari sincronitzat amb èxit.',
            'scanned' => count($files),
            'new' => $newCount,
            'modified' => $modCount,
            'unchanged' => $unchangedCount,
            'new_topics' => array_values(array_unique($newTopicsDiscovered))
        ];
    }

    public function getIndexedSummary(): array
    {
        $stmt = $this->db->query("
            SELECT topic_slug, COUNT(*) as file_count, 
                   GROUP_CONCAT(filename, ' || ') as filenames,
                   MAX(indexed_at) as last_indexed
            FROM syllabus_files
            GROUP BY topic_slug
        ");
        return $stmt->fetchAll();
    }

    private function scanDirectory(string $dir): array
    {
        $results = [];
        $allowedExtensions = ['pdf', 'sql', 'docx', 'doc', 'txt', 'md', 'html'];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $item) {
            if ($item->isFile()) {
                $ext = strtolower($item->getExtension());
                if (in_array($ext, $allowedExtensions, true)) {
                    $results[] = [
                        'name' => $item->getFilename(),
                        'path' => $item->getPathname(),
                        'size' => $item->getSize()
                    ];
                }
            }
        }

        return $results;
    }

    private function detectTopicSlug(string $filename, string $fullPath): string
    {
        $combined = strtolower($filename . ' ' . $fullPath);

        if (str_contains($combined, 'trigger')) {
            return 'tema03-triggers';
        }
        if (str_contains($combined, 'cursor')) {
            return 'tema04-cursors';
        }
        if (str_contains($combined, 'stored procedure') || 
            str_contains($combined, 'proceudre') || 
            str_contains($combined, 'procedure') || 
            str_contains($combined, 'procediment') || 
            str_contains($combined, 'helpdesk')) {
            return 'tema02-sp';
        }
        if (str_contains($combined, 'udf') || 
            str_contains($combined, 'funcion') || 
            str_contains($combined, 'funció') || 
            str_contains($combined, 'sqlquery') ||
            str_contains($combined, 'dia1')) {
            return 'tema01-udf';
        }

        return 'tema00-general';
    }

    private function ensureTopicExists(string $topicSlug, array &$newTopics): void
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM topics WHERE slug = ?");
        $stmt->execute([$topicSlug]);
        if ((int)$stmt->fetchColumn() === 0) {
            $title = match ($topicSlug) {
                'tema03-triggers' => 'Tema 3: Triggers (Desencadenadors)',
                'tema04-cursors' => 'Tema 4: Cursors en T-SQL',
                'tema00-general' => 'Material General i Repàs',
                default => 'Tema Nou: ' . ucfirst($topicSlug)
            };

            $desc = "Contingut detectat automàticament des dels nous arxius de classe.";
            $insert = $this->db->prepare("INSERT INTO topics (slug, title, description, order_num, is_unlocked, icon) VALUES (?, ?, ?, 99, 1, '📁')");
            $insert->execute([$topicSlug, $title, $desc]);
            $newTopics[] = $title;
        }
    }
}
