<?php

namespace Lib;

use Ramsey\Uuid\Uuid;

require_once __DIR__.'/../vendor/autoload.php';

class Database {
    private array $db;
    private string $path = __DIR__.'/../data/db.json';

    public function __construct() {
        $this->db = json_decode(file_get_contents($this->path), true); 
    }

    public function fetchAll(): array {
        return $this->db;
    }

    public function add(array $record): void {
        $id = Uuid::uuid4()->toString();
        
        $this->db[$id] = $record;
        $this->write();
    }

    public function update(string $id, array $record): void {
        $this->db[$id] = $record;
        $this->write();
    }

    public function delete(string $id): void {
        unset($this->db[$id]);
        $this->write();
    }

    public function search(string $id): ?array {
        if (!isset($this->db[$id])) {
            return null;
        }

        return $this->db[$id];
    }

    public function write() {
        file_put_contents($this->path, json_encode($this->db));
    }
}