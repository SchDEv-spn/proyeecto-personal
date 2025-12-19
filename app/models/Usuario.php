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
}
