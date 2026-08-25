<?php

class AdminProductosController extends Controller
{
    private function requireLogin()
    {
        if (empty($_SESSION['usuario_id'])) {
            header("Location: " . BASE_URL . "/Auth/login");
            exit;
        }
    }

    /** Genera un slug básico a partir del nombre */
    private function generarSlug(string $texto): string
    {
        if (function_exists('iconv')) {
            $texto = iconv('UTF-8', 'ASCII//TRANSLIT', $texto);
        }

        $texto = strtolower($texto);
        $texto = preg_replace('/[^a-z0-9]+/', '-', $texto);
        $texto = trim($texto, '-');

        if ($texto === '') {
            $texto = 'producto-' . time();
        }

        return $texto;
    }

    /** Normaliza colores recibidos del form */
    private function normalizarColores($input): array
    {
        $colores = $input ?? [];
        if (!is_array($colores)) $colores = [];

        $colores = array_map(fn($c) => trim((string)$c), $colores);
        $colores = array_filter($colores, fn($c) => $c !== '');
        $colores = array_values(array_unique($colores));

        if (count($colores) > 30) {
            $colores = array_slice($colores, 0, 30);
        }

        return $colores;
    }

    /**
     * $coloresDropi: filas [color, dropi_variation_id] tal como están en BD.
     * $postVariaciones: lo que el admin acaba de escribir en el form
     * ($_POST['dropi_variation'][color] = id). Se usa para re-renderizar el
     * form tras un error de validación sin perder lo que ya había tipeado.
     */
    private function overlayVariacionesDropi(array $coloresDropi, array $postVariaciones): array
    {
        foreach ($coloresDropi as &$c) {
            if (array_key_exists($c['color'], $postVariaciones)) {
                $v = trim((string)$postVariaciones[$c['color']]);
                $c['dropi_variation_id'] = $v !== '' ? (int)$v : null;
            }
        }
        unset($c);
        return $coloresDropi;
    }

    /** Limpia y acota descuento 0..100 */
    private function clampDescuento($v, int $default): int
    {
        if ($v === null || $v === '') return $default;
        $v = (int)$v;
        return max(0, min(100, $v));
    }

    /**
     * Coherencia económica del producto. Antes, un descuento del 300% se
     * recortaba a 100% en silencio y un precio de proveedor mayor al de venta
     * se guardaba tal cual, dejando márgenes negativos en el listado.
     */
    private function validarEconomia($d2Raw, $d3Raw, float $precioVenta, float $precioProveedor, array &$errores): void
    {
        foreach ([['2da', $d2Raw], ['3ra+', $d3Raw]] as [$etiqueta, $raw]) {
            if ($raw === null || $raw === '') continue;
            $n = (int)$raw;
            if ($n < 0 || $n > 90) {
                $errores[] = "El descuento de {$etiqueta} unidad debe estar entre 0% y 90%.";
            }
        }

        if ($precioVenta > 0 && $precioProveedor >= $precioVenta) {
            $errores[] = "El precio del proveedor ($" . number_format($precioProveedor, 0, ',', '.')
                . ") es mayor o igual al de venta ($" . number_format($precioVenta, 0, ',', '.')
                . "). Cada venta daría pérdida.";
        }
    }

    /** Valida imagen subida (mínimo) */
    private function validarImagenUpload(array $file, array &$errores): bool
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) return false;

        $origName = $file['name'] ?? '';
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        $permitidas = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $permitidas, true)) {
            $errores[] = "La imagen debe ser JPG, PNG o WEBP.";
            return false;
        }

        $info = @getimagesize($file['tmp_name']);
        if ($info === false) {
            $errores[] = "El archivo subido no parece ser una imagen válida.";
            return false;
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $mimeReal = $finfo->file($file['tmp_name']);
        if (!in_array($mimeReal, $allowedMimes, true)) {
            $errores[] = "El contenido del archivo no coincide con una imagen JPG, PNG o WEBP válida.";
            return false;
        }

        return true;
    }

    public function index()
    {
        $this->requireLogin();

        $productoModel = new Producto();
        $productos     = $productoModel->obtenerTodos();

        $success = '';
        if (!empty($_SESSION['admin_productos_success'])) {
            $success = $_SESSION['admin_productos_success'];
            unset($_SESSION['admin_productos_success']);
        }

        $this->view('admin/productos/index', [
            'productos' => $productos,
            'success'   => $success,
        ]);
    }

    public function crear()
    {
        $this->requireLogin();

        $old = [
            'nombre'           => '',
            'slug'             => '',
            'precio_venta'     => '',
            'precio_regular'   => '',
            'precio_proveedor' => '',
            'costo_envio'      => 0,
            'activo'           => 1,
            'colores'          => [''],

            // ✅ Descuentos multicantidad (defaults)
            'descuento_2da' => 15,
            'descuento_3ra' => 20,
            'descuento_multicantidad_activo' => 1,
        ];

        $this->view('admin/productos/crear', [
            'errores' => [],
            'old'     => $old,
        ]);
    }

    /** Formulario para pegar el ID del producto en Dropi */
    public function importarDropi()
    {
        $this->requireLogin();
        $this->view('admin/productos/importar_dropi', [
            'errores'        => [],
            'dropiId'        => '',
            'tieneDropiToken'=> (new AppSettings())->hasKey('dropi_api_token'),
        ]);
    }

    /** Guarda el token de integración de Dropi en app_settings */
    public function guardarDropiToken()
    {
        $this->requireLogin();
        $this->requireCsrf();
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
            return;
        }

        $token = trim((string)($_POST['dropi_api_token'] ?? ''));
        if ($token === '') {
            echo json_encode(['ok' => false, 'error' => 'Ingresa un token válido.']);
            return;
        }

        (new AppSettings())->set('dropi_api_token', $token);
        echo json_encode(['ok' => true]);
    }

    /**
     * Trae el producto desde Dropi y abre "Crear producto" ya lleno con su
     * nombre, foto, costo y colores/variantes. El admin revisa y ajusta el
     * precio de venta antes de guardar — nada se crea todavía acá.
     */
    public function buscarDropi()
    {
        $this->requireLogin();
        $this->requireCsrf();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "/AdminProductos/importarDropi");
            exit;
        }

        $dropiIdRaw = trim((string)($_POST['dropi_product_id'] ?? ''));
        $dropiId = (int)$dropiIdRaw;
        $tieneDropiToken = (new AppSettings())->hasKey('dropi_api_token');

        if ($dropiId <= 0) {
            $this->view('admin/productos/importar_dropi', [
                'errores'         => ['Ingresa un ID de producto de Dropi válido.'],
                'dropiId'         => $dropiIdRaw,
                'tieneDropiToken' => $tieneDropiToken,
            ]);
            return;
        }

        $dropi = new Dropi();
        if (!$dropi->configurado()) {
            $this->view('admin/productos/importar_dropi', [
                'errores'         => ['Falta configurar el token de Dropi antes de poder importar (más abajo en esta página).'],
                'dropiId'         => $dropiIdRaw,
                'tieneDropiToken' => $tieneDropiToken,
            ]);
            return;
        }

        $resp = $dropi->obtenerProducto($dropiId);
        if (!$resp['ok']) {
            $this->view('admin/productos/importar_dropi', [
                'errores'         => [$resp['error']],
                'dropiId'         => $dropiIdRaw,
                'tieneDropiToken' => $tieneDropiToken,
            ]);
            return;
        }

        $dp = $resp['producto'];

        // Costo: lo que Dropi te cobra a vos, no el precio sugerido al público.
        $costoProveedor = (float)($dp['price'] ?? ($dp['suggested_price'] ?? 0));

        // Foto principal: la forma exacta de "photos" no está documentada
        // públicamente, así que se prueban las variantes más comunes y si
        // ninguna calza, se deja vacía para que el admin la suba a mano.
        $fotoUrl = '';
        $primeraFoto = $dp['photos'][0] ?? null;
        if (is_string($primeraFoto)) {
            $fotoUrl = $primeraFoto;
        } elseif (is_array($primeraFoto)) {
            $fotoUrl = $primeraFoto['image'] ?? ($primeraFoto['url'] ?? ($primeraFoto['path'] ?? ''));
        }

        // Colores/variantes: cada variación puede tener varios atributos
        // (color, talla...) — se juntan en una sola etiqueta legible.
        $colores = [];
        $variacionesDropi = [];
        foreach ((array)($dp['variations'] ?? []) as $variacion) {
            $partes = [];
            foreach ((array)($variacion['attribute_values'] ?? []) as $attr) {
                $valor = trim((string)($attr['value'] ?? ''));
                if ($valor !== '') $partes[] = $valor;
            }
            $etiqueta = trim(implode(' / ', $partes));
            if ($etiqueta === '' || !isset($variacion['id'])) continue;

            // Dos variaciones con la misma etiqueta (atributos que no
            // alcanzamos a distinguir) no pueden compartir la misma clave:
            // se perdería el mapeo de una de las dos en silencio.
            if (isset($variacionesDropi[$etiqueta])) {
                $etiqueta .= ' (' . $variacion['id'] . ')';
            }

            $colores[] = $etiqueta;
            $variacionesDropi[$etiqueta] = (int)$variacion['id'];
        }
        if (empty($colores)) $colores = [''];

        $old = [
            'nombre'                 => $dp['name'] ?? '',
            'slug'                   => '',
            'precio_venta'           => '',
            'precio_regular'         => '',
            'precio_proveedor'       => $costoProveedor > 0 ? $costoProveedor : '',
            'costo_envio'            => 0,
            'activo'                 => 1,
            'colores'                => $colores,
            'dropi_product_id'       => (string)$dropiId,
            'dropi_variation'        => $variacionesDropi,
            'imagen_principal_actual'=> $fotoUrl,

            'descuento_2da' => 15,
            'descuento_3ra' => 20,
            'descuento_multicantidad_activo' => 1,
        ];

        $this->view('admin/productos/crear', [
            'errores'          => [],
            'old'              => $old,
            'importadoDeDropi' => true,
            'dropiSuggested'   => (float)($dp['suggested_price'] ?? 0),
        ]);
    }

    public function guardarNuevo()
    {
        $this->requireLogin();
        $this->requireCsrf();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "/AdminProductos/index");
            exit;
        }

        $nombre    = trim($_POST['nombre'] ?? '');
        $slugInput = trim($_POST['slug'] ?? '');

        $precioVenta      = (float)($_POST['precio_venta'] ?? 0);
        $precioRegular    = (float)($_POST['precio_regular'] ?? 0);
        $precioProveedor  = (float)($_POST['precio_proveedor'] ?? 0);
        $costoEnvio       = (float)($_POST['costo_envio'] ?? 0);

        // ✅ Descuentos
        $descuento2 = $this->clampDescuento($_POST['descuento_2da'] ?? null, 15);
        $descuento3 = $this->clampDescuento($_POST['descuento_3ra'] ?? null, 20);
        // ✅ Si el campo no viene en el POST, asumimos ACTIVO por defecto
        $descActivo = isset($_POST['descuento_multicantidad_activo'])
            ? (($_POST['descuento_multicantidad_activo'] == '1') ? 1 : 0)
            : 1;



        $activo = (isset($_POST['activo']) && $_POST['activo'] == '1') ? 1 : 0;
        if ($costoEnvio < 0) $costoEnvio = 0;

        $colores = $this->normalizarColores($_POST['colores'] ?? []);

        $dropiProductIdRaw = trim((string)($_POST['dropi_product_id'] ?? ''));
        $dropiProductId = $dropiProductIdRaw !== '' ? (int)$dropiProductIdRaw : null;
        $imagenUrlDropi = trim((string)($_POST['imagen_principal_actual'] ?? ''));
        $variacionesDropi = $_POST['dropi_variation'] ?? [];
        if (!is_array($variacionesDropi)) $variacionesDropi = [];

        // ✅ Si el admin escribe slug manual, lo sanitizamos
        $slug = $slugInput !== '' ? $this->generarSlug($slugInput) : $this->generarSlug($nombre);

        $errores = [];

        if ($nombre === '') {
            $errores[] = "El nombre es obligatorio.";
        }
        if ($precioVenta <= 0) {
            $errores[] = "El precio de venta debe ser mayor a 0.";
        }
        if ($precioRegular <= 0) {
            $errores[] = "El precio regular (antes) es obligatorio.";
        }
        if ($precioRegular > 0 && $precioRegular < $precioVenta) {
            $errores[] = "El precio regular debe ser mayor o igual al precio de venta.";
        }
        if ($precioProveedor < 0) {
            $errores[] = "El precio del proveedor no puede ser negativo.";
        }
        if ($costoEnvio < 0) {
            $errores[] = "El costo de envío no puede ser negativo.";
        }

        // Opcional recomendado: coherencia
        if ($descuento3 < $descuento2) {
            $errores[] = "El descuento 3ra+ debería ser mayor o igual al de 2da unidad.";
        }

        $this->validarEconomia($_POST['descuento_2da'] ?? null, $_POST['descuento_3ra'] ?? null, $precioVenta, $precioProveedor, $errores);

        $old = [
            'nombre'           => $nombre,
            'slug'             => $slugInput,
            'precio_venta'     => $precioVenta,
            'precio_regular'   => $precioRegular,
            'precio_proveedor' => $precioProveedor,
            'costo_envio'      => $costoEnvio,
            'activo'           => $activo,
            'colores'          => $colores,
            'dropi_product_id' => $dropiProductIdRaw,
            'imagen_principal_actual' => $imagenUrlDropi,
            'dropi_variation'  => $variacionesDropi,

            'descuento_2da' => $descuento2,
            'descuento_3ra' => $descuento3,
            'descuento_multicantidad_activo' => $descActivo,
        ];

        // Manejo de imagen principal: un archivo nuevo siempre gana; si no
        // se sube nada pero se venía de "Importar de Dropi" con una URL de
        // foto, se usa esa (imagen_principal admite URL absoluta, igual
        // que en el editor).
        $imagenPrincipal = (preg_match('#^https?://#i', $imagenUrlDropi)) ? $imagenUrlDropi : null;

        $persistentBase = dirname(dirname(dirname($_SERVER['DOCUMENT_ROOT']))) . '/uploads';
        $uploadDir = is_dir($persistentBase)
            ? $persistentBase . '/productos/'
            : dirname(__DIR__, 2) . '/public/uploads/productos/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (
            isset($_FILES['imagen_principal_file']) &&
            $_FILES['imagen_principal_file']['error'] === UPLOAD_ERR_OK
        ) {
            if ($this->validarImagenUpload($_FILES['imagen_principal_file'], $errores)) {
                $tmpName  = $_FILES['imagen_principal_file']['tmp_name'];
                $origName = $_FILES['imagen_principal_file']['name'];

                $ext     = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                $newName = 'prod_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;

                $destPath = $uploadDir . $newName;
                if (move_uploaded_file($tmpName, $destPath)) {
                    $imagenPrincipal = BASE_URL . '/public/uploads/productos/' . $newName;
                } else {
                    $errores[] = "No se pudo guardar la imagen. Intenta nuevamente.";
                }
            }
        }

        if (!empty($errores)) {
            $this->view('admin/productos/crear', [
                'errores' => $errores,
                'old'     => $old,
            ]);
            return;
        }

        $productoModel = new Producto();

        $productoId = $productoModel->crearConId([
            'nombre'           => $nombre,
            'slug'             => $slug,
            'precio_venta'     => $precioVenta,
            'precio_regular'   => $precioRegular,
            'precio_proveedor' => $precioProveedor,
            'costo_envio'      => $costoEnvio,
            'imagen_principal' => $imagenPrincipal,
            'activo'           => $activo,

            // ✅ descuentos
            'descuento_2da' => $descuento2,
            'descuento_3ra' => $descuento3,
            'descuento_multicantidad_activo' => $descActivo,
        ]);

        if ($productoId <= 0) {
            $this->view('admin/productos/crear', [
                'errores' => ["No se pudo crear el producto. Intenta nuevamente."],
                'old'     => $old,
            ]);
            return;
        }

        $productoModel->syncColoresProducto((int)$productoId, $colores);
        $productoModel->guardarDropiProductId((int)$productoId, $dropiProductId);

        if (!empty($variacionesDropi)) {
            $productoModel->guardarVariacionesDropi((int)$productoId, $variacionesDropi);
        }

        $_SESSION['admin_productos_success'] = "Producto creado correctamente.";
        header("Location: " . BASE_URL . "/AdminProductos/index");
        exit;
    }

    public function editar()
    {
        $this->requireLogin();

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            header("Location: " . BASE_URL . "/AdminProductos/index");
            exit;
        }

        $productoModel = new Producto();
        $producto      = $productoModel->obtenerPorId($id);

        if (!$producto) {
            $_SESSION['admin_productos_success'] = "El producto no existe.";
            header("Location: " . BASE_URL . "/AdminProductos/index");
            exit;
        }

        $colores = $productoModel->getColoresByProducto($id);
        $coloresDropi = $productoModel->obtenerColoresConVariacionDropi($id);

        $old = [
            'id'               => $producto['id'],
            'nombre'           => $producto['nombre'],
            'slug'             => $producto['slug'] ?? '',
            'precio_venta'     => $producto['precio_venta'],
            'precio_regular'   => $producto['precio_regular'] ?? 0,
            'precio_proveedor' => $producto['precio_proveedor'],
            'costo_envio'      => $producto['costo_envio'] ?? 0,
            'activo'           => $producto['activo'] ?? 1,
            'imagen_principal' => $producto['imagen_principal'] ?? '',
            'colores'          => $colores,
            'dropi_product_id' => $producto['dropi_product_id'] ?? '',

            // ✅ descuentos
            'descuento_2da' => $producto['descuento_2da'] ?? 15,
            'descuento_3ra' => $producto['descuento_3ra'] ?? 20,
            'descuento_multicantidad_activo' => $producto['descuento_multicantidad_activo'] ?? 1,
        ];

        $this->view('admin/productos/editar', [
            'producto'     => $producto,
            'errores'      => [],
            'old'          => $old,
            'colores'      => $colores,
            'coloresDropi' => $coloresDropi,
        ]);
    }

    public function actualizar()
    {
        $this->requireLogin();
        $this->requireCsrf();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "/AdminProductos/index");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            header("Location: " . BASE_URL . "/AdminProductos/index");
            exit;
        }

        $productoModel     = new Producto();
        $productoExistente = $productoModel->obtenerPorId($id);

        if (!$productoExistente) {
            $_SESSION['admin_productos_success'] = "El producto no existe.";
            header("Location: " . BASE_URL . "/AdminProductos/index");
            exit;
        }

        $nombre    = trim($_POST['nombre'] ?? '');
        $slugInput = trim($_POST['slug'] ?? '');

        $precioVenta      = (float)($_POST['precio_venta'] ?? 0);
        $precioRegular    = (float)($_POST['precio_regular'] ?? 0);
        $precioProveedor  = (float)($_POST['precio_proveedor'] ?? 0);
        $costoEnvio       = (float)($_POST['costo_envio'] ?? 0);

        // ✅ descuentos
        $descuento2 = $this->clampDescuento($_POST['descuento_2da'] ?? null, (int)($productoExistente['descuento_2da'] ?? 15));
        $descuento3 = $this->clampDescuento($_POST['descuento_3ra'] ?? null, (int)($productoExistente['descuento_3ra'] ?? 20));
        // ✅ Si el campo no viene, NO lo apagues; conserva el valor actual del producto
        $descActivo = isset($_POST['descuento_multicantidad_activo'])
            ? (($_POST['descuento_multicantidad_activo'] == '1') ? 1 : 0)
            : (int)($productoExistente['descuento_multicantidad_activo'] ?? 1);



        $activo          = (isset($_POST['activo']) && $_POST['activo'] == '1') ? 1 : 0;
        $imagenPrincipal = $_POST['imagen_principal_actual'] ?? ($productoExistente['imagen_principal'] ?? null);

        if ($costoEnvio < 0) $costoEnvio = 0;

        $colores = $this->normalizarColores($_POST['colores'] ?? []);

        $dropiProductIdRaw = trim((string)($_POST['dropi_product_id'] ?? ''));
        $dropiProductId = $dropiProductIdRaw !== '' ? (int)$dropiProductIdRaw : null;
        $variacionesDropi = $_POST['dropi_variation'] ?? [];
        if (!is_array($variacionesDropi)) $variacionesDropi = [];

        // ✅ Sanitizar slug manual
        $slug = $slugInput !== '' ? $this->generarSlug($slugInput) : ($productoExistente['slug'] ?? $this->generarSlug($nombre));

        $errores = [];

        if ($nombre === '') {
            $errores[] = "El nombre es obligatorio.";
        }
        if ($precioVenta <= 0) {
            $errores[] = "El precio de venta debe ser mayor a 0.";
        }
        if ($precioRegular <= 0) {
            $errores[] = "El precio regular (antes) es obligatorio.";
        }
        if ($precioRegular > 0 && $precioRegular < $precioVenta) {
            $errores[] = "El precio regular debe ser mayor o igual al precio de venta.";
        }
        if ($precioProveedor < 0) {
            $errores[] = "El precio del proveedor no puede ser negativo.";
        }
        if ($costoEnvio < 0) {
            $errores[] = "El costo de envío no puede ser negativo.";
        }

        if ($descuento3 < $descuento2) {
            $errores[] = "El descuento 3ra+ debería ser mayor o igual al de 2da unidad.";
        }

        $this->validarEconomia($_POST['descuento_2da'] ?? null, $_POST['descuento_3ra'] ?? null, $precioVenta, $precioProveedor, $errores);

        $old = [
            'id'               => $id,
            'nombre'           => $nombre,
            'slug'             => $slugInput,
            'precio_venta'     => $precioVenta,
            'precio_regular'   => $precioRegular,
            'precio_proveedor' => $precioProveedor,
            'costo_envio'      => $costoEnvio,
            'activo'           => $activo,
            'imagen_principal' => $imagenPrincipal,
            'colores'          => $colores,
            'dropi_product_id' => $dropiProductIdRaw,

            'descuento_2da' => $descuento2,
            'descuento_3ra' => $descuento3,
            'descuento_multicantidad_activo' => $descActivo,
        ];

        // Manejo de imagen
        $persistentBase = dirname(dirname(dirname($_SERVER['DOCUMENT_ROOT']))) . '/uploads';
        $uploadDir = is_dir($persistentBase)
            ? $persistentBase . '/productos/'
            : dirname(__DIR__, 2) . '/public/uploads/productos/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (
            isset($_FILES['imagen_principal_file']) &&
            $_FILES['imagen_principal_file']['error'] === UPLOAD_ERR_OK
        ) {
            if ($this->validarImagenUpload($_FILES['imagen_principal_file'], $errores)) {
                $tmpName  = $_FILES['imagen_principal_file']['tmp_name'];
                $origName = $_FILES['imagen_principal_file']['name'];

                $ext     = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                $newName = 'prod_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;

                $destPath = $uploadDir . $newName;
                if (move_uploaded_file($tmpName, $destPath)) {
                    $imagenPrincipal = BASE_URL . '/public/uploads/productos/' . $newName;
                } else {
                    $errores[] = "No se pudo guardar la imagen. Intenta nuevamente.";
                }
            }
        }

        if (!empty($errores)) {
            $this->view('admin/productos/editar', [
                'producto'     => $productoExistente,
                'errores'      => $errores,
                'old'          => $old,
                'colores'      => $colores,
                'coloresDropi' => $this->overlayVariacionesDropi(
                    $productoModel->obtenerColoresConVariacionDropi($id),
                    $variacionesDropi
                ),
            ]);
            return;
        }

        $ok = $productoModel->actualizar($id, [
            'nombre'           => $nombre,
            'slug'             => $slug,
            'precio_venta'     => $precioVenta,
            'precio_regular'   => $precioRegular,
            'precio_proveedor' => $precioProveedor,
            'costo_envio'      => $costoEnvio,
            'imagen_principal' => $imagenPrincipal,
            'activo'           => $activo,

            'descuento_2da' => $descuento2,
            'descuento_3ra' => $descuento3,
            'descuento_multicantidad_activo' => $descActivo,
        ]);

        if (!$ok) {
            $this->view('admin/productos/editar', [
                'producto'     => $productoExistente,
                'errores'      => ["No se pudo actualizar el producto. Intenta nuevamente."],
                'old'          => $old,
                'colores'      => $colores,
                'coloresDropi' => $this->overlayVariacionesDropi(
                    $productoModel->obtenerColoresConVariacionDropi($id),
                    $variacionesDropi
                ),
            ]);
            return;
        }

        $productoModel->syncColoresProducto($id, $colores);
        $productoModel->guardarDropiProductId($id, $dropiProductId);

        if (!empty($variacionesDropi)) {
            $productoModel->guardarVariacionesDropi($id, $variacionesDropi);
        }

        $_SESSION['admin_productos_success'] = "Producto actualizado correctamente.";
        header("Location: " . BASE_URL . "/AdminProductos/index");
        exit;
    }
}
