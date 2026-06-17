<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Perfil</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="manifest" href="<?= BASE_URL ?>/public/manifest.php">
    <meta name="theme-color" content="#C9A84C">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/admin-unified.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script>if('serviceWorker' in navigator) navigator.serviceWorker.register('<?= BASE_URL ?>/sw.js');</script>
    <style>
        .perfil-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        @media (max-width: 860px) {
            .perfil-grid { grid-template-columns: 1fr; }
        }

        .perfil-card {
            background: var(--bg-elevated);
            border: 1px solid var(--bd-subtle);
            border-radius: var(--r-xl);
            padding: 1.75rem 1.75rem 2rem;
        }

        .perfil-card-title {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--tx-secondary);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }
        .perfil-card-title i {
            color: var(--gold);
        }

        .field-group {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .field-label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .07em;
            text-transform: uppercase;
            color: var(--tx-secondary);
            margin-bottom: .35rem;
        }

        .field-input {
            width: 100%;
            background: var(--bg-overlay);
            border: 1px solid var(--bd-default);
            border-radius: var(--r-sm);
            color: var(--tx-primary);
            font-family: var(--font-body);
            font-size: 14px;
            padding: .65rem .9rem;
            transition: border-color .18s, box-shadow .18s;
            box-sizing: border-box;
        }
        .field-input:focus {
            outline: none;
            border-color: var(--gold-dim);
            box-shadow: 0 0 0 3px var(--gold-ghost);
        }
        .field-input::placeholder {
            color: var(--tx-muted);
        }

        .btn-save {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            background: var(--gold);
            color: var(--tx-inverse);
            border: none;
            border-radius: var(--r-sm);
            font-family: var(--font-display);
            font-size: 13px;
            font-weight: 700;
            letter-spacing: .05em;
            padding: .65rem 1.4rem;
            cursor: pointer;
            transition: opacity .18s, transform .12s;
        }
        .btn-save:hover { opacity: .88; transform: translateY(-1px); }
        .btn-save:active { transform: translateY(0); opacity: 1; }

        .flash-banner {
            display: flex;
            align-items: center;
            gap: .65rem;
            padding: .75rem 1rem;
            border-radius: var(--r-sm);
            font-size: 13.5px;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }
        .flash-ok {
            background: var(--ok-bg);
            color: var(--ok);
            border: 1px solid var(--ok-bd);
        }
        .flash-error {
            background: var(--err-bg);
            color: var(--err);
            border: 1px solid var(--err-bd);
        }

        .avatar-ring {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: var(--gold-ghost);
            border: 2px solid var(--gold-dim);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: 800;
            color: var(--gold);
            font-family: var(--font-display);
            margin-bottom: 1.25rem;
            text-transform: uppercase;
        }

        .divider {
            border: none;
            border-top: 1px solid var(--bd-subtle);
            margin: 1.5rem 0;
        }

        .hint {
            font-size: 11.5px;
            color: var(--tx-muted);
            margin-top: .25rem;
        }
    </style>
</head>
<body>
<?php
$usuario       = $usuario       ?? [];
$flash         = $flash         ?? null;
$usuarioNombre = $_SESSION['usuario_nombre'] ?? ($usuario['nombre'] ?? 'Admin');
$usuarioEmail  = $_SESSION['usuario_email']  ?? ($usuario['email']  ?? '');

$initials = strtoupper(substr($usuarioNombre, 0, 1) . (strpos($usuarioNombre, ' ') !== false ? substr($usuarioNombre, strpos($usuarioNombre, ' ') + 1, 1) : ''));
?>

<div class="sidebar-overlay" aria-hidden="true"></div>
<div class="app-shell">

    <?php require __DIR__ . '/../partials/_sidebar.php'; ?>

    <main class="material-main">
        <?php
        $pageTitle       = 'Mi Perfil';
        $pageSubtitle    = 'Administra tu información de acceso';
        $showRangeFilter = false;
        $showSearch      = false;
        require __DIR__ . '/../partials/_header.php';
        ?>

        <section class="material-content">

            <?php if ($flash): ?>
                <div class="flash-banner flash-<?= $flash['tipo'] === 'ok' ? 'ok' : 'error' ?>">
                    <i class="fas fa-<?= $flash['tipo'] === 'ok' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                    <?= htmlspecialchars($flash['msg']) ?>
                </div>
            <?php endif; ?>

            <div class="perfil-grid">

                <!-- ── Información personal ── -->
                <div class="perfil-card">
                    <div class="avatar-ring"><?= htmlspecialchars($initials ?: '?') ?></div>

                    <div class="perfil-card-title">
                        <i class="fas fa-id-card"></i>
                        Información personal
                    </div>

                    <form method="POST" action="<?= BASE_URL ?>/AdminPerfil/guardar" autocomplete="off">
                        <?= csrf_field() ?>
                        <input type="hidden" name="accion" value="perfil">

                        <div class="field-group">
                            <div>
                                <label class="field-label" for="nombre">Nombre completo</label>
                                <input
                                    id="nombre"
                                    type="text"
                                    name="nombre"
                                    class="field-input"
                                    value="<?= htmlspecialchars($usuario['nombre'] ?? '') ?>"
                                    required
                                    maxlength="120"
                                >
                            </div>
                            <div>
                                <label class="field-label" for="email">Correo electrónico</label>
                                <input
                                    id="email"
                                    type="email"
                                    name="email"
                                    class="field-input"
                                    value="<?= htmlspecialchars($usuario['email'] ?? '') ?>"
                                    required
                                    maxlength="180"
                                >
                            </div>
                        </div>

                        <button type="submit" class="btn-save">
                            <i class="fas fa-save"></i> Guardar cambios
                        </button>
                    </form>
                </div>

                <!-- ── Cambiar contraseña ── -->
                <div class="perfil-card">
                    <div class="perfil-card-title">
                        <i class="fas fa-lock"></i>
                        Cambiar contraseña
                    </div>

                    <form method="POST" action="<?= BASE_URL ?>/AdminPerfil/guardar" autocomplete="new-password">
                        <?= csrf_field() ?>
                        <input type="hidden" name="accion" value="password">

                        <div class="field-group">
                            <div>
                                <label class="field-label" for="password_actual">Contraseña actual</label>
                                <input
                                    id="password_actual"
                                    type="password"
                                    name="password_actual"
                                    class="field-input"
                                    placeholder="••••••••"
                                    required
                                    autocomplete="current-password"
                                >
                            </div>

                            <hr class="divider">

                            <div>
                                <label class="field-label" for="password_nueva">Nueva contraseña</label>
                                <input
                                    id="password_nueva"
                                    type="password"
                                    name="password_nueva"
                                    class="field-input"
                                    placeholder="Mínimo 8 caracteres"
                                    required
                                    minlength="8"
                                    autocomplete="new-password"
                                >
                                <p class="hint">Al menos 8 caracteres.</p>
                            </div>
                            <div>
                                <label class="field-label" for="password_confirmar">Confirmar nueva contraseña</label>
                                <input
                                    id="password_confirmar"
                                    type="password"
                                    name="password_confirmar"
                                    class="field-input"
                                    placeholder="Repite la nueva contraseña"
                                    required
                                    minlength="8"
                                    autocomplete="new-password"
                                >
                            </div>
                        </div>

                        <button type="submit" class="btn-save" id="btnCambiarPass">
                            <i class="fas fa-key"></i> Cambiar contraseña
                        </button>
                    </form>
                </div>

            </div>

        </section>
    </main>
</div>

<script src="<?= BASE_URL ?>/public/js/funciones.js"></script>
<script>
    // Confirm password match before submit
    document.getElementById('btnCambiarPass').closest('form').addEventListener('submit', function(e) {
        const nueva     = document.getElementById('password_nueva').value;
        const confirmar = document.getElementById('password_confirmar').value;
        if (nueva !== confirmar) {
            e.preventDefault();
            alert('Las contraseñas no coinciden.');
        }
    });
</script>
</body>
</html>
