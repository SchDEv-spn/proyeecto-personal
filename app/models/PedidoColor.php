<?php

class PedidoColor extends Model
{
    /**
     * items: ['negro' => 2, 'azul' => 1]
     */
    public function sync(int $pedidoId, array $items): void
    {
        if ($pedidoId <= 0) return;

        $this->db->prepare("DELETE FROM pedido_colores WHERE pedido_id = :pid")
                 ->execute([':pid' => $pedidoId]);

        $ins = $this->db->prepare(
            "INSERT INTO pedido_colores (pedido_id, color, cantidad)
             VALUES (:pid, :color, :cantidad)"
        );

        foreach ($items as $color => $cantidad) {
            $color = trim((string)$color);
            $cantidad = (int)$cantidad;

            if ($color === '' || $cantidad <= 0) continue;

            $ins->execute([
                ':pid'      => $pedidoId,
                ':color'    => mb_substr($color, 0, 50),
                ':cantidad' => $cantidad,
            ]);
        }
    }
}
