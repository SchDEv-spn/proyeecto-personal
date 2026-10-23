<?php

/**
 * Lector mínimo de .xlsx: solo la primera hoja y solo texto/números tal como
 * se ven. Un .xlsx es un zip con XML, así que basta ZipArchive + SimpleXML:
 * Hostinger no tiene composer, y traer una librería entera para leer una
 * tabla plana sería de más.
 *
 * Entiende los dos modos en que Excel guarda el texto (tabla de textos
 * compartidos y texto en la propia celda, que es como lo escribe Dropi). No
 * evalúa fórmulas ni convierte fechas: devuelve lo que hay en la celda.
 */
class XlsxLector
{
    /** Tope de lo que se descomprime (protege de un zip que se infla al abrirlo). */
    private const MAX_XML_BYTES = 25 * 1024 * 1024;

    /**
     * @return array<int, array<int, string>>  filas en orden; cada fila indexada
     *         por columna (0 = A). Las celdas vacías no aparecen en la fila.
     * @throws RuntimeException  si no es un .xlsx legible
     */
    public static function filas(string $ruta): array
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('Este servidor no tiene habilitada la extensión zip de PHP, necesaria para leer archivos .xlsx.');
        }

        $zip = new ZipArchive();
        if ($zip->open($ruta) !== true) {
            throw new RuntimeException('El archivo no es un Excel (.xlsx) válido.');
        }

        try {
            $hoja = self::rutaPrimeraHoja($zip);
            $xmlHoja = self::leerXml($zip, $hoja);
            if ($xmlHoja === null) {
                throw new RuntimeException('No se pudo leer la hoja del Excel.');
            }

            $textos = [];
            $xmlTextos = self::leerXml($zip, 'xl/sharedStrings.xml');
            if ($xmlTextos !== null) {
                foreach ($xmlTextos->si as $si) $textos[] = self::textoDe($si);
            }

            $filas = [];
            foreach ($xmlHoja->sheetData->row as $row) {
                $celdas = [];
                foreach ($row->c as $c) {
                    $tipo = (string)$c['t'];
                    if ($tipo === 's')              $v = $textos[(int)$c->v] ?? '';
                    elseif ($tipo === 'inlineStr')  $v = self::textoDe($c->is);
                    else                            $v = self::numeroPlano((string)$c->v);

                    $v = trim($v);
                    if ($v === '') continue;
                    $celdas[self::columna((string)$c['r'])] = $v;
                }
                if ($celdas) $filas[] = $celdas;
            }
            return $filas;
        } finally {
            $zip->close();
        }
    }

    /** "ruta dentro del zip" de la primera hoja del libro (casi siempre xl/worksheets/sheet1.xml). */
    private static function rutaPrimeraHoja(ZipArchive $zip): string
    {
        $libro = self::leerXml($zip, 'xl/workbook.xml');
        $rels  = self::leerXml($zip, 'xl/_rels/workbook.xml.rels');
        if ($libro !== null && $rels !== null && isset($libro->sheets->sheet[0])) {
            $rid = (string)$libro->sheets->sheet[0]->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
            foreach ($rels->Relationship as $r) {
                if ((string)$r['Id'] === $rid) {
                    $destino = ltrim((string)$r['Target'], '/');
                    return strpos($destino, 'xl/') === 0 ? $destino : 'xl/' . $destino;
                }
            }
        }
        return 'xl/worksheets/sheet1.xml';
    }

    private static function leerXml(ZipArchive $zip, string $nombre): ?SimpleXMLElement
    {
        $stat = $zip->statName($nombre);
        if ($stat === false) return null;
        if ($stat['size'] > self::MAX_XML_BYTES) {
            throw new RuntimeException('El Excel es demasiado grande para importarlo de una vez. Descarga un rango de fechas más corto.');
        }

        $contenido = $zip->getFromName($nombre);
        if ($contenido === false) return null;

        // Sin LIBXML_NOENT ni carga de red: el XML del archivo no puede traer nada de afuera.
        $previo = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($contenido, 'SimpleXMLElement', LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previo);

        return $xml === false ? null : $xml;
    }

    /** Texto de un <si> o <is>: puede venir en un <t> o partido en varios <r><t>. */
    private static function textoDe(?SimpleXMLElement $nodo): string
    {
        if ($nodo === null) return '';
        if (isset($nodo->t)) return (string)$nodo->t;

        $texto = '';
        foreach ($nodo->r as $r) $texto .= (string)$r->t;
        return $texto;
    }

    /**
     * Excel guarda los números largos en notación científica ("1.14015669260E+11");
     * un número de guía o un teléfono no puede salir así. Los enteros se devuelven
     * en su forma completa; todo lo demás, tal cual.
     */
    private static function numeroPlano(string $v): string
    {
        if (!preg_match('/^-?\d+(\.\d+)?[eE][+-]?\d+$/', $v)) return $v;

        $n = (float)$v;
        return ($n == floor($n) && abs($n) < 1e15) ? sprintf('%.0f', $n) : $v;
    }

    /** "AB12" → 27 (índice de columna base 0). */
    private static function columna(string $ref): int
    {
        if (!preg_match('/^[A-Z]+/', $ref, $m)) return 0;
        $n = 0;
        foreach (str_split($m[0]) as $letra) $n = $n * 26 + (ord($letra) - 64);
        return $n - 1;
    }
}
