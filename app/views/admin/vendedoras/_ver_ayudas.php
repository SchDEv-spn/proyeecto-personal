<?php
/** Formatos y cálculos que comparten la página de una vendedora y su contenido (que también se pide suelto). */
$invalidos = $invalidos ?? [];
$repetidos = $repetidos ?? 0;

$e = static fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$pesos = static fn($n) => '$' . number_format((float)$n, 0, ',', '.');
$plural = static fn(int $n, string $uno, string $varios) => number_format($n, 0, ',', '.') . ' ' . ($n === 1 ? $uno : $varios);
$meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
$fecha = static function (?string $f) use ($meses): string {
    $t = $f ? strtotime($f) : 0;
    return $t ? date('j', $t) . ' ' . $meses[(int)date('n', $t) - 1] : '—';
};
$etiquetas = DropiImport::ETIQUETAS;
$claseEstado = static fn(string $s) => ['otro' => 'contactado', 'devolucion' => 'cancelado'][$s] ?? $s;
$estadoTag = static function (array $o) use ($e, $etiquetas, $claseEstado): string {
    return '<span class="status-tag status-' . $e($claseEstado($o['estado'])) . '">' . $e($etiquetas[$o['estado']] ?? $o['estado']) . '</span>';
};
$cantidadTxt = static function (array $o): string {
    return !empty($o['cantidad_conocida'])
        ? (int)$o['cantidad'] . ' ud' . ((int)$o['cantidad'] === 1 ? '' : 's')
        : '<span class="dash-aviso" title="Esta orden viene de una descarga de Dropi sin productos: no sé cuántas unidades lleva. Cuenta como 1; sube la descarga con productos para confirmarlo.">¿unidades?</span>';
};

$id = (int)$vendedora['id'];
$p  = $pedidos;
$totalPagado = array_sum(array_map(fn($o) => (float)$o['comision_vendedora'], $p['pagadas']));
$valorOk = (float)$vendedora['valor_pedido'] > 0;

