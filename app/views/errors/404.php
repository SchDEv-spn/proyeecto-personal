<?php
$mensaje = $mensaje ?? 'No encontramos la página que buscas.';
$volver  = $volver  ?? '';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Página no encontrada</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css">
    <style>
        .err-wrap {
            min-height: 70vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 18px;
            text-align: center;
            padding: 48px 24px;
        }

        .err-code {
            font-size: 13px;
            letter-spacing: .18em;
            text-transform: uppercase;
            opacity: .6;
        }

        .err-title {
            font-size: clamp(1.6rem, 5vw, 2.4rem);
            margin: 0;
        }

        .err-text {
            max-width: 46ch;
            margin: 0;
            opacity: .8;
        }
    </style>
</head>

<body>
    <main class="err-wrap">
        <span class="err-code">Error 404</span>
        <h1 class="err-title">Página no encontrada</h1>
        <p class="err-text"><?= htmlspecialchars($mensaje) ?></p>
        <?php if ($volver !== ''): ?>
            <a href="<?= htmlspecialchars($volver) ?>" class="btn-primary">Ver el producto</a>
        <?php endif; ?>
    </main>
</body>

</html>
