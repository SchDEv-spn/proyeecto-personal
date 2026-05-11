<?php

class Usuario extends Model
{
    public function obtenerPorEmail(string $email)
    {
        $sql = "SELECT * FROM usuarios WHERE email = :email AND activo = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function obtenerPorId(int $id)
    {
        $sql = "SELECT * FROM usuarios WHERE id = :id AND activo = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function actualizarPerfil(int $id, string $nombre, string $email): bool
    {
        $sql = "UPDATE usuarios SET nombre = :nombre, email = :email WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':nombre' => $nombre, ':email' => $email, ':id' => $id]);
    }

    public function cambiarPassword(int $id, string $hash): bool
    {
        $sql = "UPDATE usuarios SET password_hash = :hash WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':hash' => $hash, ':id' => $id]);
    }
}
