<?php

class AppSettings extends Model
{
    public function get(string $key, ?string $default = null): ?string
    {
        $stmt = $this->db->conn->prepare(
            'SELECT value FROM app_settings WHERE `key` = ? LIMIT 1'
        );
        $stmt->execute([$key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return ($row !== false) ? $row['value'] : $default;
    }

    public function set(string $key, ?string $value): void
    {
        $stmt = $this->db->conn->prepare(
            'INSERT INTO app_settings (`key`, `value`) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), updated_at = NOW()'
        );
        $stmt->execute([$key, $value]);
    }

    public function hasKey(string $key): bool
    {
        $stmt = $this->db->conn->prepare(
            'SELECT 1 FROM app_settings WHERE `key` = ? AND value IS NOT NULL AND value != "" LIMIT 1'
        );
        $stmt->execute([$key]);
        return (bool)$stmt->fetchColumn();
    }
}
