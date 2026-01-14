<?php

declare(strict_types=1);

class Database
{
    private PDO $pdo;

    public function __construct()
    {
        $dbPath = __DIR__ . '/../data/todos.sqlite';
        $this->pdo = new PDO('sqlite:' . $dbPath);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->initializeDatabase();
    }

    private function initializeDatabase(): void
    {
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                text TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');

        // Insert default items if table is empty
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM items');
        $count = $stmt->fetchColumn();

        if ($count == 0) {
            $defaultItems = ['Passport', 'Maps and directions', 'Travel guide'];
            $insertStmt = $this->pdo->prepare('INSERT INTO items (text) VALUES (?)');
            foreach ($defaultItems as $item) {
                $insertStmt->execute([$item]);
            }
        }
    }

    public function getItems(): array
    {
        $stmt = $this->pdo->query('SELECT id, text FROM items ORDER BY id ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addItem(string $text): array
    {
        $stmt = $this->pdo->prepare('INSERT INTO items (text) VALUES (?)');
        $stmt->execute([$text]);
        return [
            'id' => (int)$this->pdo->lastInsertId(),
            'text' => $text
        ];
    }

    public function deleteItem(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM items WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
