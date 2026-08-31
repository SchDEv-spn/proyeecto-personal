<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Admin - Editar landing</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="manifest" href="<?= BASE_URL ?>/public/manifest.php">
  <meta name="theme-color" content="#C9A84C">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
  <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/admin-unified.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script>if('serviceWorker' in navigator) navigator.serviceWorker.register('<?= BASE_URL ?>/sw.js');</script>
</head>

<body>
  <?php
  $config      = $config ?? [];
  $success     = $success ?? '';
  $error       = $error ?? '';
  $producto_id = isset($producto_id) ? (int)$producto_id : 1;
  $productos   = $productos ?? [];
  $producto    = $producto ?? null;
  $recomendaciones = $recomendaciones ?? null;

  $usuarioNombre = $_SESSION['usuario_nombre'] ?? 'Admin';
  $usuarioEmail  = $_SESSION['usuario_email'] ?? 'admin@tuempresa.com';

  $nombreProducto = $producto['nombre'] ?? 'Producto';

  // =========================
  // HEADER (partial)
  // =========================
  $pageTitle = 'Editar landing';
  $pageSubtitle = 'Producto: ' . $nombreProducto;

  // En esta vista no necesitas rango ni buscador
  $showRangeFilter = false;
  $showSearch = false;

  // CTAs del header (tu _header.php ya los usa en las otras vistas)
  $headerCtas = [
    [
      'href'  => BASE_URL . '/AdminProductos/index',
      'label' => '← Volver a productos',
      'class' => 'btn-detail',
      'icon'  => 'fa-arrow-left',
    ],
  ];

  // Recomendaciones de IA pendientes (para el badge del botón).
  $recoPendientes = count($recomendaciones['pendientes'] ?? []);

  if ($producto) {
    $headerCtas[] = [
      'href'    => 'javascript:void(0)',
      'label'   => $recoPendientes > 0 ? "Sugerencias IA ({$recoPendientes})" : 'Sugerencias IA',
      'class'   => 'btn-detail btn-reco-trigger',
      'icon'    => 'fa-lightbulb',
      'onclick' => 'window.openRecoPanel && window.openRecoPanel()',
    ];
    $headerCtas[] = [
      'href'    => 'javascript:void(0)',
      'label'   => 'Preview',
      'class'   => 'btn-detail btn-preview-trigger',
      'icon'    => 'fa-eye',
      'onclick' => 'window.openLandingPreview && window.openLandingPreview()',
    ];
    $headerCtas[] = [
      'href'   => BASE_URL . '/Landing/index?producto_id=' . urlencode((string)$producto_id),
      'label'  => 'Ver landing',
      'class'  => 'btn-primary btn-primary--soft',
      'icon'   => 'fa-up-right-from-square',
      'target' => '_blank',
      'rel'    => 'noopener',
    ];
  }

  // (Opcional) compatibilidad por si tu _header.php antiguo esperaba un CTA único:
  $headerCta = $headerCtas[0];
  ?>

  <div class="sidebar-overlay" aria-hidden="true"></div>

  <!-- Mini diálogo de confirmación accesible (reemplaza confirm() nativo) -->
  <div id="confirmDialog" role="alertdialog" aria-modal="true" aria-labelledby="confirmDialogMsg"
       style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.55);align-items:center;justify-content:center;">
    <div style="background:var(--bg-surface,#1a1a2e);border:1px solid var(--bd-default,#2a2a3a);border-radius:12px;padding:24px 28px;max-width:380px;width:90%;box-shadow:0 8px 40px rgba(0,0,0,.5);">
      <p id="confirmDialogMsg" style="margin:0 0 20px;font-size:14px;color:var(--tx-primary,#e0e0f0);line-height:1.5;"></p>
      <div style="display:flex;gap:10px;justify-content:flex-end;">
        <button id="confirmDialogCancel" style="padding:8px 18px;border-radius:8px;border:1px solid var(--bd-default,#2a2a3a);background:var(--bg-elevated,#222236);color:var(--tx-secondary,#888);cursor:pointer;font-size:13px;">Cancelar</button>
        <button id="confirmDialogOk" style="padding:8px 18px;border-radius:8px;border:none;background:var(--gold,#c9a84c);color:#000;cursor:pointer;font-size:13px;font-weight:700;">Continuar</button>
      </div>
    </div>
  </div>

  <div class="app-shell">

    <!-- Sidebar (partial) -->
    <?php require __DIR__ . '/../partials/_sidebar.php'; ?>

    <main class="material-main material-main--simple">
      <?php require __DIR__ . '/../partials/_header.php'; ?>

      <section class="material-content" id="top">

        <?= alert_success($success) ?>
        <?= alert_error(!empty($error) ? [$error] : []) ?>

        <?php
        // Toda la landing puede quedar sin un solo botón que abra el formulario
        // de pedido si se apagan a la vez las secciones que los contienen.
        $fuentesCta = [
          'show_price_box'          => ['Caja de precio', 'sec-hero'],
          'show_cta_sticky'         => ['CTA fija móvil', 'sec-ctas'],
          'show_cta_benefits'       => ['CTA Beneficios', 'sec-ctas'],
          'show_cta_gallery'        => ['CTA Galería', 'sec-ctas'],
          'show_cta_porque'         => ['CTA ¿Por qué?', 'sec-ctas'],
          'show_cta_testimonials'   => ['CTA Testimonios', 'sec-ctas'],
          'show_cta_faq'            => ['CTA FAQ', 'sec-ctas'],
          'show_cta_como_funciona'  => ['CTA Cómo funciona', 'sec-ctas'],
          'show_cta_comparison'     => ['CTA Comparativa', 'sec-ctas'],
          'show_cta_para_quien'     => ['CTA Para quién es', 'sec-ctas'],
          'show_cta_wa_testimonios' => ['CTA WA Testimonios', 'sec-ctas'],
        ];
        $padresCta = [
          'show_cta_benefits'       => 'show_benefits',
          'show_cta_gallery'        => 'show_gallery',
          'show_cta_porque'         => 'show_porque',
          'show_cta_testimonials'   => 'show_testimonios',
          'show_cta_faq'            => 'show_faqs',
          'show_cta_como_funciona'  => 'show_como_funciona',
          'show_cta_comparison'     => 'show_comparison',
          'show_cta_para_quien'     => 'show_para_quien',
          'show_cta_wa_testimonios' => 'show_wa_testimonios',
        ];
        $ctasEfectivos = 0;
        foreach ($fuentesCta as $campo => $_info) {
            if ((int)($config[$campo] ?? 1) !== 1) continue;
            $padre = $padresCta[$campo] ?? null;
            if ($padre !== null && (int)($config[$padre] ?? 1) !== 1) continue; // CTA huérfano
            $ctasEfectivos++;
        }
        ?>
        <?php if ($ctasEfectivos === 0): ?>
        <div class="admin-alert-error" role="alert">
          <div class="admin-alert-title">
            <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
            Esta landing no tiene ningún botón de compra
          </div>
          <ul>
            <li>Nadie puede hacer un pedido: no hay nada que abra el formulario.</li>
            <li>Activa <b>Caja de precio</b> en la sección Hero, o <b>CTA fija móvil</b> en la sección CTAs.</li>
          </ul>
        </div>
        <?php endif; ?>

        <!-- Layout con índice lateral -->
        <div class="landing-editor-layout">

          <div class="landing-editor-main">

            <!-- Barra de navegación rápida — solo visible en mobile (<992px) -->
            <nav class="toc-mobile-bar" aria-label="Navegar por secciones">
              <i class="fas fa-list toc-mobile-bar__icon" aria-hidden="true"></i>
              <select id="tocJumpSelect" class="toc-mobile-bar__select" aria-label="Ir a sección">
                <option value="">Ir a sección…</option>
                <option value="sec-secciones">Secciones visibles</option>
                <option value="sec-hero">Hero</option>
                <option value="sec-beneficios">Beneficios</option>
                <option value="sec-galeria">Galería</option>
                <option value="sec-caracteristicas">Características</option>
                <option value="sec-contador">Contador / Oferta</option>
                <option value="sec-porque">¿Por qué?</option>
                <option value="sec-comparison">Tabla comparativa</option>
                <option value="sec-testimonios">Testimonios</option>
                <option value="sec-paraquien">¿Para quién?</option>
                <option value="sec-wa">Testimonios WhatsApp</option>
                <option value="sec-faq">Preguntas frecuentes</option>
                <option value="sec-autoridad">Autoridad</option>
                <option value="sec-ctas">CTAs</option>
                <option value="sec-combo">Modo Combo</option>
                <option value="sec-colores">Colores</option>
                <option value="sec-announcement">Barra anuncios</option>
                <option value="sec-hero-trust">Confianza hero</option>
                <option value="sec-comofunciona-content">Cómo funciona</option>
                <option value="sec-garantia">Garantía</option>
                <option value="sec-form-header">Formulario</option>
                <option value="sec-regalo">Regalo</option>
                <option value="sec-footer">Footer</option>
              </select>
            </nav>

            <!-- Selector producto -->
            <div class="form-card form-card--product-selector">
              <div class="form-card-header">
                <h2>Seleccionar producto</h2>
              </div>
              <div class="form-card-body">
                <form action="<?= BASE_URL ?>/AdminLanding/index" method="GET" class="admin-form admin-form--compact">
                  <div class="admin-form-group">
                    <label for="producto_id_select">Producto</label>
                    <div class="product-switcher-row">
                      <select name="producto_id" id="producto_id_select"
                        data-current="<?= htmlspecialchars((string)$producto_id) ?>">
                        <?php foreach ($productos as $prod): ?>
                          <option value="<?= htmlspecialchars($prod['id']) ?>" <?= ($prod['id'] == $producto_id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($prod['nombre']) ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                      <button type="button" class="btn-switch-product"
                        data-confirm="Tienes cambios sin guardar en la landing actual. ¿Cambiar de producto de todas formas?">
                        <i class="fas fa-arrow-right-arrow-left" aria-hidden="true"></i> Cambiar
                      </button>
                    </div>
                  </div>
                </form>
              </div>
            </div>

            <!-- Copiar orden de secciones desde otro producto -->
            <div class="form-card form-card--product-selector">
              <div class="form-card-header">
                <h2>Copiar orden de secciones</h2>
              </div>
              <div class="form-card-body">
                <p class="section-hint" style="margin-top:0;">
                  Copia el <strong>orden</strong> de las secciones y cuáles están <strong>visibles/ocultas</strong>
                  (incluyendo los elementos fijos: barra sticky, anuncios, WhatsApp flotante, FOMO, popup de salida, etc.)
                  desde otra landing. No se modifican textos, imágenes ni colores de esta landing.
                </p>
                <form id="formCopiarOrden" action="<?= BASE_URL ?>/AdminLanding/copiarOrden" method="POST" class="admin-form admin-form--compact">
                  <?= csrf_field() ?>
                  <input type="hidden" name="producto_id" value="<?= htmlspecialchars($producto_id) ?>">
                  <div class="admin-form-group">
                    <label for="producto_id_origen_select">Copiar orden y secciones visibles desde</label>
                    <div class="product-switcher-row">
                      <select name="producto_id_origen" id="producto_id_origen_select" required>
                        <option value="">Selecciona un producto…</option>
                        <?php foreach ($productos as $prod): if ((int)$prod['id'] === $producto_id) continue; ?>
                          <option value="<?= htmlspecialchars($prod['id']) ?>">
                            <?= htmlspecialchars($prod['nombre']) ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                      <button type="submit" class="btn-switch-product"
                        onclick="return confirm('Esto reemplazará el orden y las secciones visibles/ocultas de esta landing por las de la landing seleccionada. Los textos, imágenes y colores no se modifican. ¿Continuar?');">
                        <i class="fas fa-arrows-rotate" aria-hidden="true"></i> Copiar
                      </button>
                    </div>
                  </div>
                </form>
              </div>
            </div>

            <!-- IA: Botón generar -->
            <?php $tieneApiKey = $tiene_api_key ?? false; ?>
            <div class="ia-banner">
              <div class="ia-banner__left">
                <span class="ia-banner__icon">✨</span>
                <div>
                  <strong>Generar landing completa con IA</strong>
                  <p>Claude escribe todos los textos optimizados para conversión colombiana en segundos.</p>
                </div>
              </div>
              <button type="button" class="ia-banner__btn" id="btnAbrirIA">
                ✨ Generar con IA
              </button>
            </div>

            <!-- FORM PRINCIPAL -->
            <div class="form-card form-card--main">
              <div class="form-card-header">
                <h2>Configuración de la landing</h2>
              </div>

              <div class="form-card-body">
                <!-- Breadcrumb de sección activa (actualizado por JS en modo CMS) -->
                <div class="cms-section-breadcrumb" id="cmsBreadcrumb" style="display:none">
                  <span class="cms-section-breadcrumb__path">Landing</span>
                  <span class="cms-section-breadcrumb__sep">›</span>
                  <span class="cms-section-breadcrumb__name" id="cmsBcName">—</span>
                  <span class="cms-section-breadcrumb__status cms-bc-empty" id="cmsBcStatus"></span>
                </div>

                <!-- Banner "sección desactivada" -->
                <div class="cms-disabled-banner" id="cmsDisabledBannerEl">
                  <i class="fas fa-eye-slash"></i>
                  <span>Esta sección está <strong>desactivada</strong> en la landing pública.</span>
                  <button type="button" class="cms-disabled-banner__btn" id="cmsActivateSection">Activar ahora</button>
                </div>

                <form id="formLanding" action="<?= BASE_URL ?>/AdminLanding/guardar" method="POST" class="admin-form" enctype="multipart/form-data">
                  <?= csrf_field() ?>
                  <input type="hidden" name="producto_id" value="<?= htmlspecialchars($producto_id) ?>">

                  <!-- SECCIONES VISIBLES -->
                  <div class="section-block" id="sec-secciones" data-toc="Secciones">
                    <h2>Secciones visibles</h2>
                    <p class="section-hint">Activa o desactiva cada sección de la landing pública.</p>
                    <div class="sections-toggle-grid">

                      <div class="section-toggle-item section-toggle-item--locked">
                        <i class="fas fa-star section-toggle-fa-icon" aria-hidden="true"></i>
                        <span class="section-toggle-name">Hero</span>
                        <span class="section-toggle-badge">Siempre visible</span>
                      </div>

                      <?php
                      $toggles = [
                        'show_benefits'        => ['icon' => 'fa-bolt',             'label' => 'Beneficios'],
                        'show_gallery'         => ['icon' => 'fa-images',            'label' => 'Galería'],
                        'show_caracteristicas' => ['icon' => 'fa-layer-group',       'label' => 'Características'],
                        'show_como_funciona'   => ['icon' => 'fa-list-check',        'label' => 'Cómo funciona'],
                        'show_countdown'       => ['icon' => 'fa-clock',             'label' => 'Contador / Oferta'],
                        'show_porque'          => ['icon' => 'fa-heart',             'label' => 'Por qué encantará'],
                        'show_comparison'      => ['icon' => 'fa-scale-balanced',    'label' => 'Tabla comparativa'],
                        'show_para_quien'      => ['icon' => 'fa-user-check',        'label' => '¿Para quién es?'],
                        'show_testimonios'     => ['icon' => 'fa-star',              'label' => 'Testimonios'],
                        'show_faqs'            => ['icon' => 'fa-circle-question',   'label' => 'Preguntas frecuentes'],
                        'show_wa_testimonios'  => ['icon' => 'fa-mobile-screen',     'label' => 'Testimonios WhatsApp'],
                        'show_garantia'        => ['icon' => 'fa-shield-halved',     'label' => 'Banner de garantía'],
                        'show_regalo'          => ['icon' => 'fa-gift',              'label' => 'Regalo incluido'],
                        'show_price_box'       => ['icon' => 'fa-tag',               'label' => 'Caja de precio / CTA'],
                      ];

                      // Render toggles in saved order
                      $savedSecOrder = array_filter(array_map('trim', explode(',', $config['section_order'] ?? '')));
                      $toggleKeys    = array_keys($toggles);
                      $savedShowKeys = array_map(fn($k) => 'show_' . $k, $savedSecOrder);
                      $orderedKeys   = array_merge(
                          array_values(array_filter($savedShowKeys, fn($k) => isset($toggles[$k]))),
                          array_diff($toggleKeys, $savedShowKeys)
                      );

                      foreach ($orderedKeys as $i => $name):
                        $sec     = str_replace('show_', '', $name);
                        $icon    = $toggles[$name]['icon'];
                        $lbl     = $toggles[$name]['label'];
                        $checked = isset($config[$name]) ? (int)$config[$name] : 1;
                      ?>
                        <div class="section-toggle-item" draggable="true" data-key="<?= $sec ?>">
                          <span class="drag-handle" title="Arrastrar para reordenar"><i class="fas fa-grip-vertical" aria-hidden="true"></i></span>
                          <span class="section-toggle-pos"><?= $i + 1 ?></span>
                          <i class="fas <?= htmlspecialchars($icon) ?> section-toggle-fa-icon" aria-hidden="true"></i>
                          <span class="section-toggle-name"><?= $lbl ?></span>
                          <label class="toggle-label">
                            <input type="hidden"   name="<?= $name ?>" value="0">
                            <input type="checkbox" name="<?= $name ?>" value="1"
                                   class="toggle-cb" <?= $checked ? 'checked' : '' ?>
                                   aria-label="<?= htmlspecialchars($lbl) ?>">
                            <span class="toggle-track"><span class="toggle-thumb"></span></span>
                          </label>
                        </div>
                      <?php endforeach; ?>

                      <input type="hidden" name="section_order" id="section_order_input"
                             value="<?= htmlspecialchars($config['section_order'] ?? '') ?>">

                    </div>

                    <!-- Elementos fijos -->
                    <p class="section-hint" style="margin-top:1.5rem; font-weight:600; color:var(--gold-light);">Elementos fijos (sin orden)</p>
                    <div class="sections-toggle-grid">
                      <?php
                      $fixedToggles = [
                        'show_sticky_bar'       => ['icon' => 'fa-thumbtack',        'label' => 'Barra de precio sticky'],
                        'show_announcement_bar' => ['icon' => 'fa-bullhorn',          'label' => 'Barra de anuncios'],
                        'show_resumen_oferta'   => ['icon' => 'fa-receipt',           'label' => 'Resumen de oferta'],
                        'show_cta_sticky'       => ['icon' => 'fa-mobile-screen',     'label' => 'CTA sticky mobile'],
                        'show_whatsapp_btn'     => ['icon' => 'fa-comment-dots',      'label' => 'Botón WhatsApp flotante'],
                        'show_fomo'             => ['icon' => 'fa-bell',              'label' => 'Notificaciones FOMO'],
                        'show_exit_popup'       => ['icon' => 'fa-door-open',         'label' => 'Popup de salida'],
                      ];
                      foreach ($fixedToggles as $fname => $fdata):
                        $fchecked = isset($config[$fname]) ? (int)$config[$fname] : 1;
                      ?>
                        <div class="section-toggle-item">
                          <i class="fas <?= htmlspecialchars($fdata['icon']) ?> section-toggle-fa-icon" aria-hidden="true"></i>
                          <span class="section-toggle-name"><?= $fdata['label'] ?></span>
                          <label class="toggle-label">
                            <input type="hidden"   name="<?= $fname ?>" value="0">
                            <input type="checkbox" name="<?= $fname ?>" value="1"
                                   class="toggle-cb" <?= $fchecked ? 'checked' : '' ?>
                                   aria-label="<?= htmlspecialchars($fdata['label']) ?>">
                            <span class="toggle-track"><span class="toggle-thumb"></span></span>
                          </label>
                        </div>
                      <?php endforeach; ?>
                    </div>

                  </div>

                  <!-- HERO -->
                  <div class="section-block" id="sec-hero" data-toc="Hero">
                    <h2>Sección Hero</h2>

                    <div class="stack-cards stack-cards--2col">

                      <!-- Textos -->
                      <div class="mini-card">
                        <div class="mini-card-title">
                          <span><i class="fas fa-pencil" aria-hidden="true"></i> Textos</span>
                        </div>
                        <div class="form-grid">
                          <div class="admin-form-group admin-form-group--full">
                            <label for="hero_title">Título principal</label>
                            <input type="text" id="hero_title" name="hero_title"
                              value="<?= htmlspecialchars($config['hero_title'] ?? '') ?>">
                          </div>
                          <div class="admin-form-group admin-form-group--full">
                            <label for="hero_subtitle">Subtítulo</label>
                            <textarea id="hero_subtitle" name="hero_subtitle" rows="2"><?= htmlspecialchars($config['hero_subtitle'] ?? '') ?></textarea>
                          </div>
                          <div class="admin-form-group admin-form-group--full">
                            <label for="hero_button_text">Texto del botón</label>
                            <input type="text" id="hero_button_text" name="hero_button_text"
                              value="<?= htmlspecialchars($config['hero_button_text'] ?? '¡Necesito el mío!') ?>">
                          </div>
                          <div class="admin-form-group admin-form-group--full">
                            <label for="hero_note">Nota debajo del botón</label>
                            <input type="text" id="hero_note" name="hero_note"
                              value="<?= htmlspecialchars($config['hero_note'] ?? '') ?>">
                          </div>
                          <div class="admin-form-group">
                            <label for="hero_badge_stars">Badge — Calificación</label>
                            <input type="text" id="hero_badge_stars" name="hero_badge_stars"
                              value="<?= htmlspecialchars($config['hero_badge_stars'] ?? '4.9') ?>"
                              placeholder="4.9">
                          </div>
                          <div class="admin-form-group">
                            <label for="hero_badge_customers">Badge — Clientes</label>
                            <input type="text" id="hero_badge_customers" name="hero_badge_customers"
                              value="<?= htmlspecialchars($config['hero_badge_customers'] ?? '+3.200 clientes felices') ?>"
                              placeholder="+3.200 clientes felices">
                          </div>
                        </div>
                      </div>

                      <!-- Media -->
                      <div class="mini-card">
                        <div class="mini-card-title">
                          <span><i class="fas fa-photo-film" aria-hidden="true"></i> Media</span>
                        </div>
                        <div class="form-grid">
                          <?php $heroType = $config['hero_media_type'] ?? 'imagen'; ?>
                          <div class="admin-form-group admin-form-group--full">
                            <label for="hero_media_type">Tipo de media</label>
                            <select id="hero_media_type" name="hero_media_type" onchange="toggleMediaPreview('hero', this.value)">
                              <option value="imagen" <?= $heroType === 'imagen' ? 'selected' : '' ?>>Imagen</option>
                              <option value="video"  <?= $heroType === 'video'  ? 'selected' : '' ?>>Video</option>
                            </select>
                          </div>
                          <div class="admin-form-group admin-form-group--full">
                            <label for="hero_media_file">Subir imagen / video</label>
                            <input type="file" id="hero_media_file" name="hero_media_file" accept="image/*,video/*">
                          </div>
                          <div class="admin-form-group admin-form-group--full" id="hero-poster-group"
                               style="<?= $heroType !== 'video' ? 'display:none' : '' ?>">
                            <label for="hero_poster_file">Thumbnail del video</label>
                            <input type="file" id="hero_poster_file" name="hero_poster_file" accept="image/*">
                            <?php if (!empty($config['hero_poster_path'])): ?>
                              <div class="media-preview" style="margin-top:.5rem">
                                <img src="<?= htmlspecialchars($config['hero_poster_path']) ?>" alt="Thumbnail actual">
                              </div>
                            <?php endif; ?>
                            <input type="hidden" name="hero_poster_path_actual"
                              value="<?= htmlspecialchars($config['hero_poster_path'] ?? '') ?>">
                          </div>
                          <div class="admin-form-group admin-form-group--full">
                            <label>Preview actual</label>
                            <div class="media-preview">
                              <?php if (!empty($config['hero_media_path'])): ?>
                                <?php if (es_video($config['hero_media_path'])): ?>
                                  <video src="<?= htmlspecialchars($config['hero_media_path']) ?>" controls></video>
                                <?php else: ?>
                                  <img src="<?= htmlspecialchars($config['hero_media_path']) ?>" alt="Hero" class="js-media-check">
                                <?php endif; ?>
                              <?php else: ?>
                                <div class="media-empty">
                                  <i class="fas fa-photo-film"></i>
                                  <span>Sin media configurada</span>
                                </div>
                              <?php endif; ?>
                            </div>
                            <input type="hidden" name="hero_media_path_actual"
                              value="<?= htmlspecialchars($config['hero_media_path'] ?? '') ?>">
                          </div>
                        </div>
                      </div>

                    </div>
                  </div>

                  <hr class="section-hr">

                  <!-- BENEFICIOS -->
                  <div class="section-block" id="sec-beneficios" data-toc="Beneficios">
                    <h2>Sección Beneficios</h2>

                    <div class="form-grid">
                      <div class="admin-form-group admin-form-group--full">
                        <label for="benefits_title">Título de la sección</label>
                        <input type="text" id="benefits_title" name="benefits_title"
                          value="<?= htmlspecialchars($config['benefits_title'] ?? 'Beneficios clave para ti') ?>">
                      </div>

                    </div><!-- /form-grid título -->

                    <!-- Grilla 2×2 de beneficios -->
                    <div class="benefits-grid">
                      <?php for ($bi = 1; $bi <= 4; $bi++):
                        $bImg = $config['benefit_' . $bi . '_img'] ?? '';
                      ?>
                      <div class="benefit-card">
                        <div class="benefit-card__header">
                          <span class="benefit-card__badge"><?= $bi ?></span>
                          <span class="benefit-card__title">Beneficio <?= $bi ?></span>
                        </div>
                        <div class="benefit-card__body">
                          <div class="admin-form-group">
                            <input type="text" name="benefit_<?= $bi ?>"
                              value="<?= htmlspecialchars($config['benefit_' . $bi] ?? '') ?>"
                              placeholder="Describe el beneficio <?= $bi ?>…"
                              aria-label="Texto del beneficio <?= $bi ?>">
                          </div>

                          <div class="benefit-card__img-preview media-preview" id="benefit_thumb_<?= $bi ?>">
                            <?php if (!empty($bImg)): ?>
                              <img src="<?= htmlspecialchars($bImg) ?>" alt="Foto beneficio <?= $bi ?>">
                            <?php else: ?>
                              <div class="media-empty">
                                <i class="fas fa-image" aria-hidden="true"></i>
                                <span>Sin imagen</span>
                              </div>
                            <?php endif; ?>
                          </div>

                          <div class="admin-form-group">
                            <label class="benefit-card__upload-label">
                              <i class="fas fa-image" aria-hidden="true"></i> Foto
                              <small>(opcional)</small>
                            </label>
                            <input type="file" name="benefit_<?= $bi ?>_img_file"
                              accept="image/jpeg,image/png,image/webp,image/gif">
                          </div>

                          <input type="hidden" name="benefit_<?= $bi ?>_img_actual"
                            value="<?= htmlspecialchars($bImg) ?>">
                        </div>
                      </div>
                      <?php endfor; ?>
                    </div>

                  </div>

                  <hr class="section-hr">

                  <!-- GALERÍA -->
                  <div class="section-block" id="sec-galeria" data-toc="Galería">
                    <h2>Galería</h2>

                    <div class="form-grid" style="margin-bottom:16px;">
                      <div class="admin-form-group admin-form-group--full">
                        <label for="gallery_title">Título de la sección</label>
                        <input type="text" id="gallery_title" name="gallery_title"
                          value="<?= htmlspecialchars($config['gallery_title'] ?? 'Galería') ?>">
                      </div>
                    </div>

                    <div class="gallery-grid">
                      <?php
                      for ($i = 1; $i <= 4; $i++):
                        $key       = "gallery_{$i}_path";
                        $inputName = "gallery_{$i}_file";
                        $actual    = "gallery_{$i}_path_actual";
                      ?>
                        <div class="gallery-card">
                          <div class="gallery-card__head">
                            <i class="fas fa-image card-head-icon" aria-hidden="true"></i>
                            <span class="gallery-title">Imagen <?= $i ?></span>
                          </div>
                          <div class="gallery-card__body">
                            <div class="media-preview">
                              <?php if (!empty($config[$key])): ?>
                                <img src="<?= htmlspecialchars($config[$key]) ?>" alt="Galería <?= $i ?>">
                              <?php else: ?>
                                <div class="media-empty">
                                  <i class="fas fa-image"></i>
                                  <span>Sin imagen</span>
                                </div>
                              <?php endif; ?>
                            </div>
                            <input type="hidden" name="<?= $actual ?>" value="<?= htmlspecialchars($config[$key] ?? '') ?>">
                            <div class="admin-form-group">
                              <label for="<?= $inputName ?>">Subir nueva</label>
                              <input type="file" id="<?= $inputName ?>" name="<?= $inputName ?>" accept="image/*">
                            </div>
                          </div>
                        </div>
                      <?php endfor; ?>
                    </div>

                    <!-- VARIANTES DE COLOR -->
                    <?php
                    $cvData = [];
                    if (!empty($config['color_variants'])) {
                        $cvDecoded = json_decode($config['color_variants'], true);
                        if (is_array($cvDecoded)) $cvData = $cvDecoded;
                    }
                    // Rellenar hasta 4 slots vacíos
                    while (count($cvData) < 4) $cvData[] = ['name' => '', 'hex' => '#000000', 'images' => ['', '', '', '']];
                    ?>
                    <div class="admin-form-group admin-form-group--full" style="margin-top:24px;">
                      <label style="font-weight:700; font-size:1rem; display:block; margin-bottom:6px;">
                        <i class="fas fa-palette" aria-hidden="true"></i> Variantes de color
                      </label>
                      <p style="font-size:0.85rem; color:var(--tx-muted); margin-bottom:14px;">
                        Define hasta 4 colores. Al activar uno en la landing, la galería mostrará sus imágenes.
                        Deja el nombre en blanco para desactivar esa variante.
                      </p>

                      <div class="stack-cards stack-cards--4col" style="align-items:start;">
                        <?php for ($ci = 1; $ci <= 4; $ci++):
                          $cv     = $cvData[$ci - 1] ?? [];
                          $cvName = htmlspecialchars($cv['name'] ?? '');
                          $cvHex  = htmlspecialchars($cv['hex']  ?? '#000000');
                          $cvImgs = array_pad($cv['images'] ?? [], 4, '');
                        ?>
                        <div class="mini-card" id="cvCard<?= $ci ?>">
                          <div class="mini-card-title">
                            <span><i class="fas fa-circle" aria-hidden="true" style="color:<?= htmlspecialchars($cv['hex'] ?? '#888') ?>"></i> Color <?= $ci ?></span>
                            <button type="button" class="btn-cv-remove" title="Quitar este color y sus imágenes"
                              onclick="landingCvRemoveColor(<?= $ci ?>)">
                              <i class="fas fa-trash-alt" aria-hidden="true"></i> Quitar
                            </button>
                          </div>

                          <div class="admin-form-group" style="margin-bottom:8px;">
                            <label for="cv<?= $ci ?>_name">Nombre</label>
                            <input type="text" id="cv<?= $ci ?>_name" name="cv<?= $ci ?>_name"
                              value="<?= $cvName ?>" placeholder="ej. Rojo, Azul marino…" style="font-size:0.9rem;">
                          </div>

                          <div class="admin-form-group" style="margin-bottom:12px;">
                            <label for="cv<?= $ci ?>_hex">Color (hex)</label>
                            <div style="display:flex; gap:8px; align-items:center;">
                              <input type="color" id="cv<?= $ci ?>_hex" name="cv<?= $ci ?>_hex"
                                value="<?= $cvHex ?>" style="width:44px; height:36px; padding:2px; border-radius:6px; cursor:pointer; border:1px solid rgba(255,255,255,0.15);">
                              <input type="text" value="<?= $cvHex ?>"
                                style="font-size:0.85rem; font-family:monospace; width:90px; text-transform:uppercase;"
                                oninput="document.getElementById('cv<?= $ci ?>_hex').value=this.value"
                                onchange="document.getElementById('cv<?= $ci ?>_hex').value=this.value">
                              <script>
                              (function(){
                                var cp = document.getElementById('cv<?= $ci ?>_hex');
                                if (cp) cp.addEventListener('input', function(){
                                  var txt = cp.closest('.admin-form-group').querySelector('input[type=text]');
                                  if (txt) txt.value = cp.value.toUpperCase();
                                });
                              })();
                              </script>
                            </div>
                          </div>

                          <?php for ($gi = 1; $gi <= 4; $gi++):
                            $gSrc    = htmlspecialchars($cvImgs[$gi - 1] ?? '');
                            $gActual = "cv{$ci}_g{$gi}_actual";
                            $gFile   = "cv{$ci}_g{$gi}_file";
                          ?>
                          <div class="admin-form-group" style="margin-bottom:8px;">
                            <label style="font-size:0.8rem;">Imagen <?= $gi ?></label>
                            <div class="media-preview" id="cv<?= $ci ?>_g<?= $gi ?>_preview" style="margin-bottom:4px; height:80px;">
                              <?php if ($gSrc !== ''): ?>
                                <img src="<?= $gSrc ?>" alt="Color <?= $ci ?> img <?= $gi ?>" style="height:100%; object-fit:cover; border-radius:6px;">
                                <button type="button" class="btn-media-remove" title="Quitar imagen"
                                  onclick="landingCvRemoveImage(<?= $ci ?>, <?= $gi ?>)">
                                  <i class="fas fa-times" aria-hidden="true"></i>
                                </button>
                              <?php else: ?>
                                <div class="media-empty" style="height:80px;">
                                  <i class="fas fa-image"></i>
                                </div>
                              <?php endif; ?>
                            </div>
                            <input type="hidden" id="<?= $gActual ?>" name="<?= $gActual ?>" value="<?= $gSrc ?>">
                            <input type="file" id="<?= $gFile ?>" name="<?= $gFile ?>" accept="image/*" style="font-size:0.8rem;">
                          </div>
                          <?php endfor; ?>
                        </div>
                        <?php endfor; ?>
                      </div>
                    </div>

                    <script>
                    function landingCvRemoveImage(ci, gi) {
                      var actual  = document.getElementById('cv' + ci + '_g' + gi + '_actual');
                      var file    = document.getElementById('cv' + ci + '_g' + gi + '_file');
                      var preview = document.getElementById('cv' + ci + '_g' + gi + '_preview');
                      if (actual) actual.value = '';
                      if (file)   file.value = '';
                      if (preview) {
                        preview.innerHTML = '<div class="media-empty" style="height:80px;"><i class="fas fa-image"></i></div>';
                      }
                    }

                    function landingCvRemoveColor(ci) {
                      if (!confirm('¿Quitar este color y todas sus imágenes?')) return;
                      var name = document.getElementById('cv' + ci + '_name');
                      if (name) name.value = '';
                      var hex = document.getElementById('cv' + ci + '_hex');
                      if (hex) {
                        hex.value = '#000000';
                        var txt = hex.closest('.admin-form-group').querySelector('input[type=text]');
                        if (txt) txt.value = '#000000';
                      }
                      for (var gi = 1; gi <= 4; gi++) landingCvRemoveImage(ci, gi);
                    }
                    </script>

                  </div>

                  <hr class="section-hr">

                  <!-- CARACTERÍSTICAS -->
                  <div class="section-block" id="sec-caracteristicas" data-toc="Características">
                    <h2>Características del producto</h2>
                    <p class="field-section-desc">Carousel de hasta 4 tarjetas. Cada una puede tener imagen, video o gif, título y descripción.</p>

                    <div class="admin-form-group" style="margin-bottom:20px;">
                      <label for="caract_section_title">Título de la sección</label>
                      <input type="text" id="caract_section_title" name="caract_section_title"
                        value="<?= htmlspecialchars($config['caract_section_title'] ?? 'Características del producto') ?>">
                    </div>

                    <div class="stack-cards stack-cards--4col">
                    <?php for ($cn = 1; $cn <= 4; $cn++):
                      $cPath   = $config["caract{$cn}_media_path"] ?? '';
                      $cType   = $config["caract{$cn}_media_type"] ?? 'image';
                      $cActive = (int)($config["caract{$cn}_active"] ?? 1);
                    ?>
                    <div class="mini-card">
                      <div class="mini-card-title">
                        <span><i class="fas fa-layer-group" aria-hidden="true"></i> Característica <?= $cn ?></span>
                        <label class="toggle-label card-vis-toggle">
                          <input type="hidden" name="caract<?= $cn ?>_active" value="0">
                          <input type="checkbox" name="caract<?= $cn ?>_active" value="1"
                                 class="toggle-cb" <?= $cActive ? 'checked' : '' ?>>
                          <span class="toggle-track<?= $cActive ? ' is-on' : '' ?>"><span class="toggle-thumb"></span></span>
                          <span class="card-vis-text">VISIBLE</span>
                        </label>
                      </div>

                      <div class="gallery-card" style="margin-bottom:12px;">
                        <div class="media-preview">
                          <?php if (!empty($cPath)): ?>
                            <?php if (es_video($cPath)): ?>
                              <video src="<?= htmlspecialchars($cPath) ?>" muted controls style="width:100%;border-radius:6px;"></video>
                            <?php else: ?>
                              <img src="<?= htmlspecialchars($cPath) ?>" alt="Característica <?= $cn ?>" class="js-media-check">
                            <?php endif; ?>
                          <?php else: ?>
                            <div class="media-empty">
                              <i class="fas fa-photo-video"></i>
                              <span>Sin media</span>
                            </div>
                          <?php endif; ?>
                        </div>
                        <input type="hidden" name="caract<?= $cn ?>_media_path_actual" value="<?= htmlspecialchars($cPath) ?>">
                        <div class="form-grid" style="margin-top:10px;">
                          <div class="admin-form-group">
                            <label for="caract<?= $cn ?>_media_type">Tipo de media</label>
                            <select id="caract<?= $cn ?>_media_type" name="caract<?= $cn ?>_media_type" class="admin-select">
                              <option value="image" <?= $cType === 'image' ? 'selected' : '' ?>>Imagen / GIF</option>
                              <option value="video" <?= $cType === 'video' ? 'selected' : '' ?>>Video</option>
                            </select>
                          </div>
                          <div class="admin-form-group">
                            <label for="caract<?= $cn ?>_media_file">Subir media</label>
                            <input type="file" id="caract<?= $cn ?>_media_file" name="caract<?= $cn ?>_media_file" accept="image/*,video/*,.gif">
                          </div>
                        </div>
                      </div>

                      <div class="form-grid">
                        <div class="admin-form-group">
                          <label for="caract<?= $cn ?>_title">Título</label>
                          <input type="text" id="caract<?= $cn ?>_title" name="caract<?= $cn ?>_title"
                            value="<?= htmlspecialchars($config["caract{$cn}_title"] ?? '') ?>">
                        </div>
                        <div class="admin-form-group admin-form-group--full">
                          <label for="caract<?= $cn ?>_text">Descripción</label>
                          <textarea id="caract<?= $cn ?>_text" name="caract<?= $cn ?>_text" rows="3"><?= htmlspecialchars($config["caract{$cn}_text"] ?? '') ?></textarea>
                        </div>
                      </div>
                    </div>
                    <?php endfor; ?>
                    </div>
                  </div>

                  <hr class="section-hr">

                  <!-- COUNTDOWN -->
                  <div class="section-block" id="sec-contador" data-toc="Contador">
                    <h2>Sección contador</h2>

                    <div class="form-grid">
                      <div class="admin-form-group">
                        <label for="countdown_title">Título sobre el contador</label>
                        <input type="text" id="countdown_title" name="countdown_title"
                          value="<?= htmlspecialchars($config['countdown_title'] ?? 'La promoción termina en:') ?>">
                      </div>

                      <div class="admin-form-group admin-form-group--full">
                        <label for="countdown_text">Texto debajo del contador</label>
                        <textarea id="countdown_text" name="countdown_text" rows="2"><?= htmlspecialchars($config['countdown_text'] ?? 'Después de que el contador llegue a cero, el precio puede volver a la normalidad.') ?></textarea>
                      </div>

                      <div class="admin-form-group">
                        <label for="countdown_minutes">Duración del countdown (minutos)</label>
                        <input type="number" id="countdown_minutes" name="countdown_minutes"
                          value="<?= htmlspecialchars($config['countdown_minutes'] ?? '25') ?>"
                          min="1" max="1440" aria-describedby="countdown_minutes_hint">
                        <small id="countdown_minutes_hint">El timer persiste entre recargas vía sessionStorage. Cambiarlo aquí reinicia el contador para nuevas sesiones.</small>
                      </div>

                      <div class="admin-form-group">
                        <label for="urgency_stock">Stock inicial (unidades)</label>
                        <input type="number" id="urgency_stock" name="urgency_stock"
                          value="<?= htmlspecialchars($config['urgency_stock'] ?? '12') ?>"
                          min="1" max="999" aria-describedby="urgency_stock_hint">
                        <small id="urgency_stock_hint">Número de unidades que aparece en el hero. Decrece automáticamente y se marca crítico al llegar a ≤ 3.</small>
                      </div>
                    </div>
                  </div>

                  <hr class="section-hr">

                  <!-- POR QUÉ -->
                  <div class="section-block" id="sec-porque" data-toc="¿Por qué?">
                    <h2>¿Por qué te encantará?</h2>

                    <div class="stack-cards stack-cards--2col">

                      <!-- Textos -->
                      <div class="mini-card">
                        <div class="mini-card-title">
                          <span><i class="fas fa-pencil" aria-hidden="true"></i> Textos</span>
                        </div>
                        <div class="form-grid">
                          <div class="admin-form-group admin-form-group--full">
                            <label for="porque_title">Título</label>
                            <input type="text" id="porque_title" name="porque_title"
                              value="<?= htmlspecialchars($config['porque_title'] ?? '¿Por qué te encantará este producto?') ?>">
                          </div>
                          <div class="admin-form-group admin-form-group--full">
                            <label for="porque_text">Texto principal</label>
                            <textarea id="porque_text" name="porque_text" rows="4"><?= htmlspecialchars($config['porque_text'] ?? '') ?></textarea>
                          </div>
                          <div class="admin-form-group admin-form-group--full">
                            <label>Punto 1</label>
                            <input type="text" name="porque_bullet1" value="<?= htmlspecialchars($config['porque_bullet1'] ?? '') ?>" placeholder="Ventaja clave…">
                          </div>
                          <div class="admin-form-group admin-form-group--full">
                            <label>Punto 2</label>
                            <input type="text" name="porque_bullet2" value="<?= htmlspecialchars($config['porque_bullet2'] ?? '') ?>" placeholder="Ventaja clave…">
                          </div>
                          <div class="admin-form-group admin-form-group--full">
                            <label>Punto 3</label>
                            <input type="text" name="porque_bullet3" value="<?= htmlspecialchars($config['porque_bullet3'] ?? '') ?>" placeholder="Ventaja clave…">
                          </div>
                        </div>
                      </div>

                      <!-- Media -->
                      <div class="mini-card">
                        <div class="mini-card-title">
                          <span><i class="fas fa-photo-film" aria-hidden="true"></i> Media</span>
                        </div>
                        <div class="form-grid">
                          <?php $porqueType = $config['porque_media_type'] ?? 'imagen'; ?>
                          <div class="admin-form-group admin-form-group--full">
                            <label for="porque_media_type">Tipo de medio</label>
                            <select name="porque_media_type" id="porque_media_type"
                              onchange="toggleMediaPreview('porque', this.value)">
                              <option value="imagen" <?= $porqueType === 'imagen' ? 'selected' : '' ?>>Imagen (JPG, PNG, WEBP)</option>
                              <option value="gif"    <?= $porqueType === 'gif'    ? 'selected' : '' ?>>GIF animado</option>
                              <option value="video"  <?= $porqueType === 'video'  ? 'selected' : '' ?>>Video (MP4, MOV, WEBM)</option>
                            </select>
                          </div>
                          <div class="admin-form-group admin-form-group--full">
                            <label for="porque_media_file">Subir archivo</label>
                            <input type="file" id="porque_media_file" name="porque_media_file"
                              accept="<?= $porqueType === 'video'
                                        ? 'video/mp4,video/quicktime,video/webm'
                                        : ($porqueType === 'gif' ? 'image/gif' : 'image/jpeg,image/png,image/webp') ?>">
                          </div>
                          <div class="admin-form-group admin-form-group--full">
                            <label>Preview actual</label>
                            <div class="media-preview" id="porque_preview">
                              <?php if (!empty($config['porque_media_path'])): ?>
                                <?php if (es_video($config['porque_media_path'])): ?>
                                  <video src="<?= htmlspecialchars($config['porque_media_path']) ?>" muted loop controls></video>
                                <?php else: ?>
                                  <img src="<?= htmlspecialchars($config['porque_media_path']) ?>" alt="Por qué te encantará" class="js-media-check">
                                <?php endif; ?>
                              <?php else: ?>
                                <div class="media-empty">
                                  <i class="fas fa-photo-film"></i>
                                  <span>Sin media configurada</span>
                                </div>
                              <?php endif; ?>
                            </div>
                            <input type="hidden" name="porque_media_path_actual"
                              value="<?= htmlspecialchars($config['porque_media_path'] ?? '') ?>">
                          </div>
                        </div>
                      </div>

                    </div>
                  </div>

                  <hr class="section-hr">

                  <!-- TABLA COMPARATIVA -->
                  <div class="section-block" id="sec-comparison" data-toc="Comparativa">
                    <h2>Tabla Comparativa (Con / Sin)</h2>
                    <p class="field-section-desc">
                      Muestra la diferencia entre tener y no tener el producto. Solo aparece si hay al menos 1 fila completa.
                    </p>

                    <div class="form-grid">
                      <div class="admin-form-group admin-form-group--full">
                        <label for="comparison_title">Título de la sección</label>
                        <input type="text" id="comparison_title" name="comparison_title"
                          value="<?= htmlspecialchars($config['comparison_title'] ?? 'La diferencia que hace este producto') ?>">
                      </div>
                      <div class="admin-form-group">
                        <label for="comparison_label_without">Encabezado columna izquierda</label>
                        <input type="text" id="comparison_label_without" name="comparison_label_without"
                          value="<?= htmlspecialchars($config['comparison_label_without'] ?? 'Sin el producto') ?>"
                          placeholder="ej. Sin el producto">
                      </div>
                      <div class="admin-form-group">
                        <label for="comparison_label_with">Encabezado columna derecha</label>
                        <input type="text" id="comparison_label_with" name="comparison_label_with"
                          value="<?= htmlspecialchars($config['comparison_label_with'] ?? 'Con el producto') ?>"
                          placeholder="ej. Con BOLSO HOBO">
                      </div>
                    </div>

                    <!-- Imágenes por columna -->
                    <div class="form-grid" style="margin-top:16px;">
                      <div class="admin-form-group">
                        <label>Imagen columna "Sin el producto"</label>
                        <div class="media-preview">
                          <?php if (!empty($config['comparison_img_without'])): ?>
                            <img src="<?= htmlspecialchars($config['comparison_img_without']) ?>" alt="Sin el producto">
                          <?php else: ?>
                            <div class="media-empty"><i class="fas fa-image"></i><span>Sin imagen</span></div>
                          <?php endif; ?>
                        </div>
                        <input type="hidden" name="comparison_img_without_path_actual"
                               value="<?= htmlspecialchars($config['comparison_img_without'] ?? '') ?>">
                        <input type="file" name="comparison_img_without_file"
                               accept="image/jpeg,image/png,image/webp,image/gif">
                      </div>
                      <div class="admin-form-group">
                        <label>Imagen columna "Con el producto"</label>
                        <div class="media-preview">
                          <?php if (!empty($config['comparison_img_with'])): ?>
                            <img src="<?= htmlspecialchars($config['comparison_img_with']) ?>" alt="Con el producto">
                          <?php else: ?>
                            <div class="media-empty"><i class="fas fa-image"></i><span>Sin imagen</span></div>
                          <?php endif; ?>
                        </div>
                        <input type="hidden" name="comparison_img_with_path_actual"
                               value="<?= htmlspecialchars($config['comparison_img_with'] ?? '') ?>">
                        <input type="file" name="comparison_img_with_file"
                               accept="image/jpeg,image/png,image/webp,image/gif">
                      </div>
                    </div>

                    <div class="stack-cards" style="margin-top:16px;">
                      <?php for ($i = 1; $i <= 5; $i++):
                        $withoutKey = "comparison_{$i}_without";
                        $withKey    = "comparison_{$i}_with";
                      ?>
                        <div class="mini-card">
                          <div class="mini-card-title">
                            <i class="fas fa-arrows-left-right" aria-hidden="true"></i> Fila <?= $i ?>
                          </div>
                          <div class="form-grid">
                            <div class="admin-form-group">
                              <label>Sin el producto</label>
                              <input type="text" name="<?= $withoutKey ?>"
                                value="<?= htmlspecialchars($config[$withoutKey] ?? '') ?>"
                                placeholder="ej. Pierdes tiempo todos los días">
                            </div>
                            <div class="admin-form-group">
                              <label>Con el producto</label>
                              <input type="text" name="<?= $withKey ?>"
                                value="<?= htmlspecialchars($config[$withKey] ?? '') ?>"
                                placeholder="ej. Todo resuelto en minutos">
                            </div>
                          </div>
                        </div>
                      <?php endfor; ?>
                    </div>
                  </div>

                  <hr class="section-hr">

                  <!-- TESTIMONIOS -->
                  <div class="section-block" id="sec-testimonios" data-toc="Testimonios">
                    <h2>Testimonios</h2>

                    <div class="form-grid" style="margin-bottom:16px;">
                      <div class="admin-form-group admin-form-group--full">
                        <label for="testimonios_title">Título de la sección</label>
                        <input type="text" id="testimonios_title" name="testimonios_title"
                          value="<?= htmlspecialchars($config['testimonios_title'] ?? 'Lo que cuentan nuestros clientes') ?>">
                      </div>
                    </div>

                    <div class="stack-cards stack-cards--3col">
                      <?php for ($i = 1; $i <= 3; $i++):
                        $nameKey      = "test{$i}_name";
                        $cityKey      = "test{$i}_city";
                        $textKey      = "test{$i}_text";
                        $photoKey     = "test{$i}_photo_path";
                        $photoInput   = "test{$i}_photo_file";
                        $photoActual  = "test{$i}_photo_path_actual";
                        $bannerKey    = "test{$i}_banner_path";
                        $bannerInput  = "test{$i}_banner_file";
                        $bannerActual = "test{$i}_banner_path_actual";
                      ?>
                        <div class="mini-card">
                          <div class="mini-card-title">
                            <i class="fas fa-comment-dots" aria-hidden="true"></i> Testimonio <?= $i ?>
                          </div>

                          <div class="form-grid">
                            <div class="admin-form-group">
                              <label>Nombre</label>
                              <input type="text" name="<?= $nameKey ?>" value="<?= htmlspecialchars($config[$nameKey] ?? '') ?>">
                            </div>

                            <div class="admin-form-group">
                              <label>Ciudad</label>
                              <input type="text" name="<?= $cityKey ?>" value="<?= htmlspecialchars($config[$cityKey] ?? '') ?>" placeholder="Ej: Bogotá">
                            </div>

                            <div class="admin-form-group admin-form-group--full">
                              <label style="display:flex;justify-content:space-between;align-items:center;">
                                Texto del testimonio
                                <span class="char-counter" id="counter_<?= $textKey ?>">
                                  <?= mb_strlen($config[$textKey] ?? '') ?>/100
                                </span>
                              </label>
                              <textarea name="<?= $textKey ?>" rows="2" maxlength="100"
                                data-char-counter="counter_<?= $textKey ?>"
                              ><?= htmlspecialchars($config[$textKey] ?? '') ?></textarea>
                            </div>

                            <!-- Banner -->
                            <div class="admin-form-group admin-form-group--full">
                              <div class="media-preview media-preview--banner">
                                <?php if (!empty($config[$bannerKey])): ?>
                                  <img src="<?= htmlspecialchars($config[$bannerKey]) ?>" alt="Banner testimonio <?= $i ?>">
                                <?php else: ?>
                                  <div class="media-empty">
                                    <i class="fas fa-panorama"></i>
                                    <span>Banner</span>
                                  </div>
                                <?php endif; ?>
                              </div>
                              <input type="hidden" name="<?= $bannerActual ?>" value="<?= htmlspecialchars($config[$bannerKey] ?? '') ?>">
                              <input type="file" id="<?= $bannerInput ?>" name="<?= $bannerInput ?>" accept="image/*">
                            </div>

                            <!-- Foto de perfil -->
                            <div class="admin-form-group admin-form-group--full upload-avatar-row">
                              <div class="media-preview media-preview--avatar">
                                <?php if (!empty($config[$photoKey])): ?>
                                  <img src="<?= htmlspecialchars($config[$photoKey]) ?>" alt="Testimonio <?= $i ?>">
                                <?php else: ?>
                                  <div class="media-empty"><i class="fas fa-user"></i></div>
                                <?php endif; ?>
                              </div>
                              <div class="upload-avatar-row__dropzone">
                                <input type="file" id="<?= $photoInput ?>" name="<?= $photoInput ?>" accept="image/*">
                                <input type="hidden" name="<?= $photoActual ?>" value="<?= htmlspecialchars($config[$photoKey] ?? '') ?>">
                              </div>
                            </div>
                          </div>
                        </div>
                      <?php endfor; ?>
                    </div>
                  </div>

                  <hr class="section-hr">

                  <!-- PARA QUIÉN ES -->
                  <div class="section-block" id="sec-paraquien" data-toc="¿Para quién?">
                    <h2>¿Para quién es?</h2>

                    <div class="form-grid" style="margin-bottom:16px;">
                      <div class="admin-form-group admin-form-group--full">
                        <label for="para_quien_title">Título de la sección</label>
                        <input type="text" id="para_quien_title" name="para_quien_title"
                          value="<?= htmlspecialchars($config['para_quien_title'] ?? '¿Este producto es para ti?') ?>">
                      </div>
                    </div>

                    <p class="field-section-desc">Deja en blanco los ítems que no uses.</p>

                    <div class="paraquien-grid">
                      <div class="mini-card">
                        <div class="mini-card-title">
                          <i class="fas fa-circle-check" style="color:var(--ok)" aria-hidden="true"></i> Sí es para ti si…
                        </div>
                        <div class="form-grid">
                          <div class="admin-form-group">
                            <label>Ítem 1</label>
                            <input type="text" name="para_quien_si_1"
                              value="<?= htmlspecialchars($config['para_quien_si_1'] ?? '') ?>">
                          </div>
                          <div class="admin-form-group">
                            <label>Ítem 2</label>
                            <input type="text" name="para_quien_si_2"
                              value="<?= htmlspecialchars($config['para_quien_si_2'] ?? '') ?>">
                          </div>
                          <div class="admin-form-group">
                            <label>Ítem 3</label>
                            <input type="text" name="para_quien_si_3"
                              value="<?= htmlspecialchars($config['para_quien_si_3'] ?? '') ?>">
                          </div>
                          <div class="admin-form-group">
                            <label>Ítem 4</label>
                            <input type="text" name="para_quien_si_4"
                              value="<?= htmlspecialchars($config['para_quien_si_4'] ?? '') ?>">
                          </div>
                        </div>
                      </div>

                      <div class="mini-card">
                        <div class="mini-card-title">
                          <i class="fas fa-circle-xmark" style="color:var(--err)" aria-hidden="true"></i> No es para ti si…
                        </div>
                        <div class="form-grid">
                          <div class="admin-form-group">
                            <label>Ítem 1</label>
                            <input type="text" name="para_quien_no_1"
                              value="<?= htmlspecialchars($config['para_quien_no_1'] ?? '') ?>">
                          </div>
                          <div class="admin-form-group">
                            <label>Ítem 2</label>
                            <input type="text" name="para_quien_no_2"
                              value="<?= htmlspecialchars($config['para_quien_no_2'] ?? '') ?>">
                          </div>
                          <div class="admin-form-group">
                            <label>Ítem 3</label>
                            <input type="text" name="para_quien_no_3"
                              value="<?= htmlspecialchars($config['para_quien_no_3'] ?? '') ?>">
                          </div>
                        </div>
                      </div>
                    </div><!-- /paraquien-grid -->
                  </div>

                  <hr class="section-hr">

                  <!-- TESTIMONIOS WHATSAPP -->
                  <div class="section-block" id="sec-wa" data-toc="WhatsApp">
                    <h2>Testimonios Reales de WhatsApp</h2>

                    <div class="form-grid">
                      <div class="admin-form-group admin-form-group--full">
                        <label for="wa_title">Título</label>
                        <input type="text" id="wa_title" name="wa_title"
                          value="<?= htmlspecialchars($config['wa_title'] ?? '📱 Testimonios Reales de WhatsApp') ?>">
                      </div>

                      <div class="admin-form-group admin-form-group--full">
                        <label for="wa_subtitle">Subtítulo</label>
                        <textarea id="wa_subtitle" name="wa_subtitle" rows="2"><?= htmlspecialchars($config['wa_subtitle'] ?? 'Capturas reales de conversaciones con nuestros clientes') ?></textarea>
                      </div>


                      <div class="admin-form-group admin-form-group--full">
                        <label for="wa_footer_note">Nota inferior (debajo de los puntos)</label>
                        <input type="text" id="wa_footer_note" name="wa_footer_note"
                          value="<?= htmlspecialchars($config['wa_footer_note'] ?? '💡 Desliza para ver más • Capturas 100% reales de WhatsApp') ?>">
                      </div>
                    </div>

                    <div class="stack-cards stack-cards--3col" style="margin-top: 14px;">
                      <?php for ($i = 1; $i <= 5; $i++):
                        $nameKey     = "wa{$i}_name";
                        $timeKey     = "wa{$i}_time";
                        $textKey     = "wa{$i}_text";
                        $imgKey      = "wa{$i}_image_path";
                        $imgInput    = "wa{$i}_image_file";
                        $imgActual   = "wa{$i}_image_path_actual";
                      ?>
                        <div class="mini-card">
                          <div class="mini-card-title">
                            <i class="fab fa-whatsapp" aria-hidden="true"></i> WhatsApp <?= $i ?>
                          </div>

                          <div class="form-grid">
                            <div class="admin-form-group">
                              <label>Nombre</label>
                              <input type="text" name="<?= $nameKey ?>" value="<?= htmlspecialchars($config[$nameKey] ?? '') ?>">
                            </div>

                            <div class="admin-form-group">
                              <label>Tiempo (ej. Hace 4 días)</label>
                              <input type="text" name="<?= $timeKey ?>" value="<?= htmlspecialchars($config[$timeKey] ?? '') ?>">
                            </div>

                            <div class="admin-form-group admin-form-group--full">
                              <label>Texto</label>
                              <textarea name="<?= $textKey ?>" rows="2"><?= htmlspecialchars($config[$textKey] ?? '') ?></textarea>
                            </div>

                            <div class="admin-form-group">
                              <label for="<?= $imgInput ?>">Subir captura</label>
                              <input type="file" id="<?= $imgInput ?>" name="<?= $imgInput ?>" accept="image/*">
                            </div>

                            <div class="admin-form-group admin-form-group--full">
                              <label>Imagen actual</label>
                              <div class="media-preview">
                                <?php if (!empty($config[$imgKey])): ?>
                                  <img src="<?= htmlspecialchars($config[$imgKey]) ?>" alt="WhatsApp <?= $i ?>">
                                <?php else: ?>
                                  <div class="media-empty">
                                    <i class="fas fa-image"></i>
                                    <span>Sin imagen</span>
                                  </div>
                                <?php endif; ?>
                              </div>

                              <input type="hidden" name="<?= $imgActual ?>" value="<?= htmlspecialchars($config[$imgKey] ?? '') ?>">
                            </div>
                          </div>
                        </div>
                      <?php endfor; ?>
                    </div>

                  </div>

                  <hr class="section-hr">

                  <!-- FAQ -->
                  <div class="section-block" id="sec-faq" data-toc="FAQ">
                    <h2>Preguntas frecuentes</h2>

                    <div class="form-grid" style="margin-bottom:16px;">
                      <div class="admin-form-group admin-form-group--full">
                        <label for="faq_title">Título de la sección</label>
                        <input type="text" id="faq_title" name="faq_title"
                          value="<?= htmlspecialchars($config['faq_title'] ?? 'Preguntas frecuentes') ?>">
                      </div>
                    </div>

                    <div class="faq-grid">
                      <?php for ($i = 1; $i <= 6; $i++):
                        $qKey = "faq{$i}_q";
                        $aKey = "faq{$i}_a";
                      ?>
                        <div class="mini-card">
                          <div class="mini-card-title">
                            <i class="fas fa-circle-question" aria-hidden="true"></i>
                            Pregunta <?= $i ?>
                            <?php if ($i > 3): ?><small style="opacity:.85;font-weight:400;text-transform:none;letter-spacing:0;">(opcional)</small><?php endif; ?>
                          </div>

                          <div class="admin-form-group">
                            <label>Pregunta</label>
                            <input type="text" name="<?= $qKey ?>" value="<?= htmlspecialchars($config[$qKey] ?? '') ?>">
                          </div>
                          <div class="admin-form-group">
                            <label>Respuesta</label>
                            <textarea name="<?= $aKey ?>" rows="3"><?= htmlspecialchars($config[$aKey] ?? '') ?></textarea>
                          </div>
                        </div>
                      <?php endfor; ?>
                    </div>
                  </div>

                  <hr class="section-hr">

                  <!-- AUTORIDAD / CREDIBILIDAD -->
                  <div class="section-block" id="sec-autoridad" data-toc="Autoridad">
                    <h2>Sección de Autoridad / Credibilidad</h2>
                    <p class="field-section-desc">4 estadísticas que generan confianza justo antes del formulario. Actívala cuando tengas números reales.</p>

                    <div class="form-grid">
                      <div class="admin-form-group admin-form-group--full">
                        <label class="toggle-label--row">
                          <input type="hidden"   name="authority_enabled" value="0">
                          <input type="checkbox" name="authority_enabled" value="1" class="toggle-cb"
                            <?= !empty($config['authority_enabled']) ? 'checked' : '' ?>>
                          <span class="toggle-track<?= !empty($config['authority_enabled']) ? ' is-on' : '' ?>"><span class="toggle-thumb"></span></span>
                          <span class="toggle-label-text">Activar sección de autoridad</span>
                        </label>
                      </div>

                      <div class="admin-form-group admin-form-group--full">
                        <label for="authority_title">Título</label>
                        <input type="text" id="authority_title" name="authority_title"
                          value="<?= htmlspecialchars($config['authority_title'] ?? '¿Por qué confiar en nosotros?') ?>">
                      </div>

                      <div class="admin-form-group">
                        <label for="authority_years">Años en el mercado</label>
                        <input type="text" id="authority_years" name="authority_years"
                          value="<?= htmlspecialchars($config['authority_years'] ?? '3') ?>"
                          placeholder="ej. 3">
                      </div>

                      <div class="admin-form-group">
                        <label for="authority_deliveries">Pedidos entregados</label>
                        <input type="text" id="authority_deliveries" name="authority_deliveries"
                          value="<?= htmlspecialchars($config['authority_deliveries'] ?? '5.000+') ?>"
                          placeholder="ej. 5.000+">
                      </div>

                      <div class="admin-form-group">
                        <label for="authority_rating">Calificación promedio</label>
                        <input type="text" id="authority_rating" name="authority_rating"
                          value="<?= htmlspecialchars($config['authority_rating'] ?? '4.9') ?>"
                          placeholder="ej. 4.9">
                      </div>

                      <div class="admin-form-group">
                        <label for="authority_guarantee">Texto de garantía</label>
                        <input type="text" id="authority_guarantee" name="authority_guarantee"
                          value="<?= htmlspecialchars($config['authority_guarantee'] ?? 'Garantía de satisfacción') ?>"
                          placeholder="ej. Garantía de satisfacción">
                      </div>
                    </div>
                  </div>

                  <hr class="section-hr">

                  <!-- CTAs -->
                  <div class="section-block" id="sec-ctas" data-toc="CTAs">
                    <h2>Textos de llamadas a la acción</h2>

                    <div class="stack-cards stack-cards--3col">
                      <?php
                      $sectionCtas = [
                        'benefits'        => ['icon' => 'fa-bullhorn',        'label' => 'CTA Beneficios',       'has_text' => true,  'text_default' => 'Ya sabes lo que hace. El siguiente paso es recibirlo en casa.', 'btn_default' => 'Quiero aprovechar la oferta'],
                        'gallery'         => ['icon' => 'fa-images',          'label' => 'CTA Galería',          'has_text' => true,  'text_default' => 'Lo que ves es lo que llega. Sin sorpresas, sin excusas.',       'btn_default' => 'Lo quiero igual que en las fotos'],
                        'porque'          => ['icon' => 'fa-heart',           'label' => 'CTA ¿Por qué?',        'has_text' => true,  'text_default' => 'Miles lo recibieron. Tú eres el siguiente.',                    'btn_default' => 'Quiero sentir ese cambio'],
                        'testimonials'    => ['icon' => 'fa-star',            'label' => 'CTA Testimonios',      'has_text' => true,  'text_default' => 'Ellos ya lo tienen. Tu pedido tarda menos de 2 minutos.',      'btn_default' => 'Quiero ser el próximo en recibirlo'],
                        'faq'             => ['icon' => 'fa-shield-halved',   'label' => 'CTA FAQ',              'has_text' => true,  'text_default' => 'Dudas resueltas. Esto solo falta: hacer tu pedido.',            'btn_default' => 'Sí, quiero pedirlo ahora'],
                        'como_funciona'   => ['icon' => 'fa-list-check',      'label' => 'CTA Cómo funciona',    'has_text' => true,  'text_default' => 'Así de simple. ¿Listo para empezar?',                          'btn_default' => 'Hacer mi pedido ahora →'],
                        'comparison'      => ['icon' => 'fa-scale-balanced',  'label' => 'CTA Comparativa',      'has_text' => false, 'text_default' => '',                                                              'btn_default' => 'Quiero experimentar la diferencia →'],
                        'para_quien'      => ['icon' => 'fa-user-check',      'label' => 'CTA Para quién es',    'has_text' => false, 'text_default' => '',                                                              'btn_default' => 'Sí, es para mí →'],
                        'wa_testimonios'  => ['icon' => 'fa-whatsapp',        'label' => 'CTA WA Testimonios',   'has_text' => false, 'text_default' => '',                                                              'btn_default' => 'Yo también lo quiero →'],
                      ];
                      // Cada CTA vive dentro de una sección. Si la sección está
                      // oculta, el CTA no se renderiza aunque esté activado.
                      $ctaParents = [
                        'benefits'       => 'show_benefits',
                        'gallery'        => 'show_gallery',
                        'porque'         => 'show_porque',
                        'testimonials'   => 'show_testimonios',
                        'faq'            => 'show_faqs',
                        'como_funciona'  => 'show_como_funciona',
                        'comparison'     => 'show_comparison',
                        'para_quien'     => 'show_para_quien',
                        'wa_testimonios' => 'show_wa_testimonios',
                      ];

                      foreach ($sectionCtas as $ctaKey => $ctaData):
                        $showField   = 'show_cta_' . $ctaKey;
                        $isOn        = (int)($config[$showField] ?? 1);
                        $parentField = $ctaParents[$ctaKey] ?? null;
                        $parentOn    = $parentField === null ? true : (int)($config[$parentField] ?? 1) === 1;
                      ?>
                      <div class="mini-card<?= $parentOn ? '' : ' mini-card--inactiva' ?>">
                        <div class="mini-card-title">
                          <span><i class="fas <?= $ctaData['icon'] ?>"></i> <?= $ctaData['label'] ?></span>
                          <label class="toggle-label" style="margin:0;">
                            <input type="hidden"   name="<?= $showField ?>" value="0">
                            <input type="checkbox" name="<?= $showField ?>" value="1"
                                   class="toggle-cb" <?= $isOn ? 'checked' : '' ?>
                                   aria-label="Mostrar <?= htmlspecialchars($ctaData['label']) ?>">
                            <span class="toggle-track"><span class="toggle-thumb"></span></span>
                          </label>
                        </div>
                        <?php if (!$parentOn): ?>
                        <p class="cta-huerfano">
                          <i class="fas fa-eye-slash" aria-hidden="true"></i>
                          Su sección está oculta, así que este CTA no aparece en la landing aunque esté activado.
                        </p>
                        <?php endif; ?>
                        <div class="form-grid" style="margin-top:12px;">
                          <?php if (!empty($ctaData['has_text'])): ?>
                          <div class="admin-form-group admin-form-group--full">
                            <label for="cta_<?= $ctaKey ?>_text">Texto</label>
                            <textarea id="cta_<?= $ctaKey ?>_text" name="cta_<?= $ctaKey ?>_text" rows="2"><?= htmlspecialchars($config['cta_' . $ctaKey . '_text'] ?? $ctaData['text_default']) ?></textarea>
                          </div>
                          <?php endif; ?>
                          <div class="admin-form-group <?= empty($ctaData['has_text']) ? 'admin-form-group--full' : '' ?>">
                            <label for="cta_<?= $ctaKey ?>_button">Botón</label>
                            <input type="text" id="cta_<?= $ctaKey ?>_button" name="cta_<?= $ctaKey ?>_button"
                              value="<?= htmlspecialchars($config['cta_' . $ctaKey . '_button'] ?? $ctaData['btn_default']) ?>">
                          </div>
                        </div>
                      </div>
                      <?php endforeach; ?>

                      <div class="mini-card">
                        <div class="mini-card-title"><i class="fas fa-mobile-screen" aria-hidden="true"></i> CTA fija móvil</div>
                        <div class="form-grid">
                          <div class="admin-form-group admin-form-group--full">
                            <label for="cta_sticky_mobile_text">Texto del botón fijo inferior</label>
                            <input type="text" id="cta_sticky_mobile_text" name="cta_sticky_mobile_text"
                              value="<?= htmlspecialchars($config['cta_sticky_mobile_text'] ?? 'Lo quiero ahora') ?>">
                          </div>
                        </div>
                      </div>

                      <div class="mini-card">
                        <div class="mini-card-title"><i class="fab fa-whatsapp" style="color:#25D366;" aria-hidden="true"></i> Botón flotante de WhatsApp</div>
                        <div class="form-grid">
                          <div class="admin-form-group admin-form-group--full">
                            <label for="wa_phone">Número de WhatsApp (solo dígitos, con código de país)</label>
                            <input type="text" id="wa_phone" name="wa_phone"
                              value="<?= htmlspecialchars($config['wa_phone'] ?? '573023959721') ?>"
                              placeholder="ej. 573001234567"
                              aria-describedby="wa_phone_hint">
                            <small id="wa_phone_hint">Formato: código de país + número sin + ni espacios. Ej: 573001234567</small>
                          </div>
                        </div>
                      </div>

                      <div class="mini-card">
                        <div class="mini-card-title"><i class="fas fa-chart-line" aria-hidden="true"></i> Analítica de esta landing</div>
                        <div class="form-grid">
                          <div class="admin-form-group">
                            <label for="pixel_id">ID del Facebook Pixel</label>
                            <input type="text" id="pixel_id" name="pixel_id"
                              value="<?= htmlspecialchars($config['pixel_id'] ?? '') ?>"
                              placeholder="Vacío = <?= htmlspecialchars(fb_pixel_id()) ?> (por defecto)"
                              aria-describedby="pixel_id_hint">
                            <small id="pixel_id_hint">Déjalo vacío para usar el pixel por defecto del sitio.</small>
                          </div>
                          <div class="admin-form-group">
                            <label for="clarity_id">ID de Microsoft Clarity</label>
                            <input type="text" id="clarity_id" name="clarity_id"
                              value="<?= htmlspecialchars($config['clarity_id'] ?? '') ?>"
                              placeholder="Vacío = wm68pleap5 (por defecto)"
                              aria-describedby="clarity_id_hint">
                            <small id="clarity_id_hint">Déjalo vacío para usar el proyecto de Clarity por defecto.</small>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <hr class="section-hr">

                  <!-- MODO COMBO -->
                  <div class="section-block" id="sec-combo" data-toc="Modo Combo">
                    <h2>Modo Combo</h2>

                    <?php
                    $comboEnabled = (int)($config['combo_enabled'] ?? 0);
                    $comboPrice2  = (int)($config['combo_price_2'] ?? 0);
                    ?>

                    <div class="stack-cards">
                      <div class="mini-card">
                        <div class="mini-card-title"><i class="fas fa-boxes" aria-hidden="true"></i> Configuración</div>

                        <div class="form-grid">
                          <div class="admin-form-group admin-form-group--full">
                            <label class="toggle-label--row">
                              <input type="hidden"   name="combo_enabled" value="0">
                              <input type="checkbox" name="combo_enabled" value="1" class="toggle-cb" <?= $comboEnabled === 1 ? 'checked' : '' ?>>
                              <span class="toggle-track<?= $comboEnabled === 1 ? ' is-on' : '' ?>"><span class="toggle-thumb"></span></span>
                              <span class="toggle-label-text">Activar Combo x2 en la landing</span>
                            </label>
                            <small class="help">Activa esta opción para mostrar el selector “x2” en la landing.</small>
                          </div>

                          <div class="admin-form-group">
                            <label for="combo_price_2">Precio Combo x2 (COP)</label>
                            <input type="number" id="combo_price_2" name="combo_price_2"
                              value="<?= htmlspecialchars((string)$comboPrice2) ?>"
                              min="0" step="1000" aria-describedby="combo_price_2_hint">
                            <small id="combo_price_2_hint" class="help">Ej: 115000.</small>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <hr class="section-hr">

                  <!-- COLORES -->
                  <div class="section-block" id="sec-colores" data-toc="Colores">
                    <h2>Apariencia de la landing</h2>

                    <!-- ── SELECTOR DE TEMA ─────────────────────────────────── -->
                    <div class="theme-selector">
                      <p class="theme-selector__label">Elige una base visual</p>
                      <div class="theme-selector__grid">

                        <?php
                        /* Las tarjetas salen de la fuente única de temas, y ahora
                           también sus colores: la miniatura se pinta con tres vars
                           en línea en vez de con una regla por tema escrita a mano
                           en admin-unified.css. Un color que cambie en themes.php
                           cambia aquí solo. */
                        $temasCfg = LandingConfig::temasValidos();

                        /* resolverTema() en vez de un slug escrito aquí: una landing
                           guardada con uno de los nombres viejos tiene que aparecer
                           con su tarjeta ya marcada, no con la del primero. */
                        $currentTheme = LandingConfig::resolverTema($config['theme'] ?? null);

                        /* Un tema claro necesita contorno en la miniatura o se funde
                           con el fondo del editor. Se deduce de la luminancia de su
                           propio fondo en vez de con una lista aparte que mantener. */
                        $esClaro = function (string $hex): bool {
                            $hex = ltrim($hex, '#');
                            if (strlen($hex) !== 6) return false;
                            $c = [];
                            foreach ([0, 2, 4] as $i) {
                                $v = hexdec(substr($hex, $i, 2)) / 255;
                                $c[] = $v <= 0.03928 ? $v / 12.92 : pow(($v + 0.055) / 1.055, 2.4);
                            }
                            return (0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2]) > 0.5;
                        };

                        foreach ($temasCfg as $themeVal => $themeData):
                            $pal   = $themeData['paleta'];
                            $vars  = '--tp-bg:'  . $pal['background_color']
                                   . ';--tp-fg:' . $pal['text_color']
                                   . ';--tp-cta:' . $themeData['cta'];
                            if ($esClaro($pal['background_color'])) {
                                $vars .= ';--tp-edge:rgba(0,0,0,.10)';
                            }
                        ?>
                        <label class="theme-card <?= $currentTheme === $themeVal ? 'theme-card--active' : '' ?>">
                          <input type="radio" name="theme" value="<?= htmlspecialchars($themeVal) ?>"
                            <?= $currentTheme === $themeVal ? 'checked' : '' ?>
                            onchange="applyThemePreview('<?= htmlspecialchars($themeVal) ?>')">
                          <div class="theme-card__preview" style="<?= htmlspecialchars($vars) ?>">
                            <span class="theme-card__mock-header"></span>
                            <span class="theme-card__mock-line"></span>
                            <span class="theme-card__mock-line theme-card__mock-line--short"></span>
                            <span class="theme-card__mock-btn"></span>
                          </div>
                          <div class="theme-card__info">
                            <span class="theme-card__nicho"><?= htmlspecialchars($themeData['nicho']) ?></span>
                            <strong><?= htmlspecialchars($themeData['nombre']) ?></strong>
                            <small><?= htmlspecialchars($themeData['desc']) ?></small>
                          </div>
                        </label>
                        <?php endforeach; ?>

                      </div>
                    </div>

                    <!-- ── PALETA BASE (5 vars existentes) ──────────────────── -->
                    <div class="colors-section">
                      <p class="colors-section__label">
                        Colores base
                        <small>Sobreescriben el tema seleccionado</small>
                      </p>
                      <div class="colors-grid">

                        <div class="admin-form-group">
                          <label>Fondo</label>
                          <div class="color-picker-wrap">
                            <input type="color" id="background_color" name="background_color"
                              value="<?= htmlspecialchars($config['background_color'] ?? '#0a0a0a') ?>"
                              oninput="syncHex(this, 'background_color_hex'); updatePreviewBar()">
                            <input type="text" id="background_color_hex" class="color-hex-input"
                              value="<?= htmlspecialchars($config['background_color'] ?? '#0a0a0a') ?>"
                              maxlength="7" oninput="syncPicker(this, 'background_color'); updatePreviewBar()">
                          </div>
                        </div>

                        <div class="admin-form-group">
                          <label>Texto</label>
                          <div class="color-picker-wrap">
                            <input type="color" id="text_color" name="text_color"
                              value="<?= htmlspecialchars($config['text_color'] ?? '#f0ebe0') ?>"
                              oninput="syncHex(this, 'text_color_hex'); updatePreviewBar()">
                            <input type="text" id="text_color_hex" class="color-hex-input"
                              value="<?= htmlspecialchars($config['text_color'] ?? '#f0ebe0') ?>"
                              maxlength="7" oninput="syncPicker(this, 'text_color'); updatePreviewBar()">
                          </div>
                        </div>

                        <div class="admin-form-group">
                          <label>Principal <small>Botones · CTAs</small></label>
                          <div class="color-picker-wrap">
                            <input type="color" id="primary_color" name="primary_color"
                              value="<?= htmlspecialchars($config['primary_color'] ?? '#c9a84c') ?>"
                              oninput="syncHex(this, 'primary_color_hex'); updatePreviewBar()">
                            <input type="text" id="primary_color_hex" class="color-hex-input"
                              value="<?= htmlspecialchars($config['primary_color'] ?? '#c9a84c') ?>"
                              maxlength="7" oninput="syncPicker(this, 'primary_color'); updatePreviewBar()">
                          </div>
                        </div>

                        <div class="admin-form-group">
                          <label>Acento <small>Hover · Detalles</small></label>
                          <div class="color-picker-wrap">
                            <input type="color" id="accent_color" name="accent_color"
                              value="<?= htmlspecialchars($config['accent_color'] ?? '#e8c96a') ?>"
                              oninput="syncHex(this, 'accent_color_hex'); updatePreviewBar()">
                            <input type="text" id="accent_color_hex" class="color-hex-input"
                              value="<?= htmlspecialchars($config['accent_color'] ?? '#e8c96a') ?>"
                              maxlength="7" oninput="syncPicker(this, 'accent_color'); updatePreviewBar()">
                          </div>
                        </div>

                        <div class="admin-form-group">
                          <label>Secundario <small>Soporte · Bordes</small></label>
                          <div class="color-picker-wrap">
                            <input type="color" id="secondary_color" name="secondary_color"
                              value="<?= htmlspecialchars($config['secondary_color'] ?? '#5c4a1e') ?>"
                              oninput="syncHex(this, 'secondary_color_hex'); updatePreviewBar()">
                            <input type="text" id="secondary_color_hex" class="color-hex-input"
                              value="<?= htmlspecialchars($config['secondary_color'] ?? '#5c4a1e') ?>"
                              maxlength="7" oninput="syncPicker(this, 'secondary_color'); updatePreviewBar()">
                          </div>
                        </div>

                      </div>
                    </div>

                    <!-- ── PALETA EXTENDIDA (6 vars nuevas) ─────────────────── -->
                    <div class="colors-section">
                      <p class="colors-section__label">
                        Colores extendidos
                        <small>Control fino sobre elementos específicos</small>
                      </p>
                      <div class="colors-grid">

                        <div class="admin-form-group">
                          <label>Dorado principal <small>Precios · Títulos dorados</small></label>
                          <div class="color-picker-wrap">
                            <input type="color" id="color_gold" name="color_gold"
                              value="<?= htmlspecialchars($config['color_gold'] ?? '#c9a84c') ?>"
                              oninput="syncHex(this, 'color_gold_hex'); updatePreviewBar()">
                            <input type="text" id="color_gold_hex" class="color-hex-input"
                              value="<?= htmlspecialchars($config['color_gold'] ?? '#c9a84c') ?>"
                              maxlength="7" oninput="syncPicker(this, 'color_gold'); updatePreviewBar()">
                          </div>
                        </div>

                        <div class="admin-form-group">
                          <label>Dorado claro <small>Hover · Precio actual</small></label>
                          <div class="color-picker-wrap">
                            <input type="color" id="color_gold_light" name="color_gold_light"
                              value="<?= htmlspecialchars($config['color_gold_light'] ?? '#e8c96a') ?>"
                              oninput="syncHex(this, 'color_gold_light_hex'); updatePreviewBar()">
                            <input type="text" id="color_gold_light_hex" class="color-hex-input"
                              value="<?= htmlspecialchars($config['color_gold_light'] ?? '#e8c96a') ?>"
                              maxlength="7" oninput="syncPicker(this, 'color_gold_light'); updatePreviewBar()">
                          </div>
                        </div>

                        <div class="admin-form-group">
                          <label>Éxito <small>Envío gratis · Ahorro · Badges</small></label>
                          <div class="color-picker-wrap">
                            <input type="color" id="color_success" name="color_success"
                              value="<?= htmlspecialchars($config['color_success'] ?? '#4caf7d') ?>"
                              oninput="syncHex(this, 'color_success_hex'); updatePreviewBar()">
                            <input type="text" id="color_success_hex" class="color-hex-input"
                              value="<?= htmlspecialchars($config['color_success'] ?? '#4caf7d') ?>"
                              maxlength="7" oninput="syncPicker(this, 'color_success'); updatePreviewBar()">
                          </div>
                        </div>

                        <div class="admin-form-group">
                          <label>Countdown <small>Timer de urgencia</small></label>
                          <div class="color-picker-wrap">
                            <input type="color" id="color_countdown" name="color_countdown"
                              value="<?= htmlspecialchars($config['color_countdown'] ?? '#e8c96a') ?>"
                              oninput="syncHex(this, 'color_countdown_hex'); updatePreviewBar()">
                            <input type="text" id="color_countdown_hex" class="color-hex-input"
                              value="<?= htmlspecialchars($config['color_countdown'] ?? '#e8c96a') ?>"
                              maxlength="7" oninput="syncPicker(this, 'color_countdown'); updatePreviewBar()">
                          </div>
                        </div>

                        <div class="admin-form-group">
                          <label>Fondo cards <small>Testimonios · Beneficios</small></label>
                          <div class="color-picker-wrap">
                            <input type="color" id="color_bg_card" name="color_bg_card"
                              value="<?= htmlspecialchars($config['color_bg_card'] ?? '#1a1a1a') ?>"
                              oninput="syncHex(this, 'color_bg_card_hex')">
                            <input type="text" id="color_bg_card_hex" class="color-hex-input"
                              value="<?= htmlspecialchars($config['color_bg_card'] ?? '#1a1a1a') ?>"
                              maxlength="7" oninput="syncPicker(this, 'color_bg_card')">
                          </div>
                        </div>

                        <div class="admin-form-group">
                          <label>Bordes <small>Cards · Inputs · Separadores</small></label>
                          <div class="color-picker-wrap">
                            <input type="color" id="color_border" name="color_border"
                              value="<?= htmlspecialchars($config['color_border'] ?? '#c9a84c') ?>"
                              oninput="syncHex(this, 'color_border_hex')">
                            <input type="text" id="color_border_hex" class="color-hex-input"
                              value="<?= htmlspecialchars($config['color_border'] ?? '#c9a84c') ?>"
                              maxlength="7" oninput="syncPicker(this, 'color_border')">
                          </div>
                        </div>

                      </div>
                    </div>

                    <!-- ── PALETTE PREVIEW ───────────────────────────────────── -->
                    <div class="palette-preview" id="colorPreviewBar"
                         style="background:<?= htmlspecialchars($config['background_color'] ?? '#0a0a0a') ?>">
                      <p class="palette-preview__title"
                         style="color:<?= htmlspecialchars($config['text_color'] ?? '#f0ebe0') ?>">
                        Así se verá tu landing
                      </p>
                      <p class="palette-preview__body"
                         style="color:<?= htmlspecialchars($config['text_color'] ?? '#f0ebe0') ?>; opacity:.7">
                        Texto de ejemplo — subtítulo o descripción del producto.
                      </p>
                      <div class="palette-preview__actions">
                        <button type="button" class="palette-preview__btn-primary"
                                style="background:<?= htmlspecialchars($config['primary_color'] ?? '#c9a84c') ?>;color:<?= htmlspecialchars($config['background_color'] ?? '#0a0a0a') ?>">
                          Comprar ahora
                        </button>
                        <span class="palette-preview__badge"
                              style="color:<?= htmlspecialchars($config['color_success'] ?? '#4caf7d') ?>;border-color:<?= htmlspecialchars($config['color_success'] ?? '#4caf7d') ?>">
                          ✔ Envío gratis
                        </span>
                        <span class="palette-preview__timer"
                              style="color:<?= htmlspecialchars($config['color_countdown'] ?? '#e8c96a') ?>">
                          ⏱ 12:34
                        </span>
                      </div>
                      <div class="palette-preview__swatches">
                        <span class="palette-preview__swatch" id="prev_background_color"
                              style="background:<?= htmlspecialchars($config['background_color'] ?? '#0a0a0a') ?>"
                              title="Fondo"></span>
                        <span class="palette-preview__swatch" id="prev_text_color"
                              style="background:<?= htmlspecialchars($config['text_color'] ?? '#f0ebe0') ?>"
                              title="Texto"></span>
                        <span class="palette-preview__swatch" id="prev_primary_color"
                              style="background:<?= htmlspecialchars($config['primary_color'] ?? '#c9a84c') ?>"
                              title="Principal"></span>
                        <span class="palette-preview__swatch" id="prev_accent_color"
                              style="background:<?= htmlspecialchars($config['accent_color'] ?? '#e8c96a') ?>"
                              title="Acento"></span>
                        <span class="palette-preview__swatch" id="prev_secondary_color"
                              style="background:<?= htmlspecialchars($config['secondary_color'] ?? '#5c4a1e') ?>"
                              title="Secundario"></span>
                        <span class="palette-preview__swatch" id="prev_color_gold"
                              style="background:<?= htmlspecialchars($config['color_gold'] ?? '#c9a84c') ?>"
                              title="Dorado"></span>
                        <span class="palette-preview__swatch" id="prev_color_success"
                              style="background:<?= htmlspecialchars($config['color_success'] ?? '#4caf7d') ?>"
                              title="Éxito"></span>
                        <span class="palette-preview__swatch" id="prev_color_countdown"
                              style="background:<?= htmlspecialchars($config['color_countdown'] ?? '#e8c96a') ?>"
                              title="Countdown"></span>
                      </div>
                    </div>

                  </div>


                  <hr class="section-hr">

                  <!-- ANNOUNCEMENT BAR -->
                  <div class="section-block" id="sec-announcement" data-toc="Barra">
                    <h2>Barra de anuncios</h2>
                    <p class="field-section-desc">Ítems del ticker superior. Deja en blanco los que no uses — se usan los defaults si todos están vacíos.</p>

                    <div class="form-grid">
                      <?php for ($i = 1; $i <= 6; $i++): $k = "announcement_item_{$i}"; ?>
                      <div class="admin-form-group">
                        <label>Ítem <?= $i ?></label>
                        <input type="text" name="<?= $k ?>"
                          value="<?= htmlspecialchars($config[$k] ?? '') ?>"
                          placeholder="<?= $i === 1 ? '🔥 Quedan pocas unidades' : ($i === 2 ? '🚚 Envío gratis a todo el país' : ($i === 3 ? '💳 Pago contraentrega' : ($i === 4 ? '⭐ +3.200 clientes felices' : ($i === 5 ? '📦 Empaque discreto y seguro' : 'Ítem opcional')))) ?>">
                      </div>
                      <?php endfor; ?>
                    </div>
                  </div>

                  <hr class="section-hr">

                  <!-- HERO TRUST ROW -->
                  <div class="section-block" id="sec-hero-trust" data-toc="Confianza hero">
                    <h2>Íconos de confianza del hero</h2>
                    <p class="field-section-desc">Tres ítems que aparecen debajo del botón principal del hero.</p>

                    <div class="form-grid">
                      <div class="admin-form-group">
                        <label for="hero_trust_1">Ítem 1</label>
                        <input type="text" id="hero_trust_1" name="hero_trust_1"
                          value="<?= htmlspecialchars($config['hero_trust_1'] ?? '✅ Pago al recibir') ?>">
                      </div>
                      <div class="admin-form-group">
                        <label for="hero_trust_2">Ítem 2</label>
                        <input type="text" id="hero_trust_2" name="hero_trust_2"
                          value="<?= htmlspecialchars($config['hero_trust_2'] ?? '🚚 Envío gratis') ?>">
                      </div>
                      <div class="admin-form-group">
                        <label for="hero_trust_3">Ítem 3</label>
                        <input type="text" id="hero_trust_3" name="hero_trust_3"
                          value="<?= htmlspecialchars($config['hero_trust_3'] ?? '🔄 Cambios sin problema') ?>">
                      </div>
                    </div>
                  </div>

                  <hr class="section-hr">

                  <!-- CÓMO FUNCIONA — CONTENIDO -->
                  <div class="section-block" id="sec-comofunciona-content" data-toc="Cómo funciona">
                    <h2>Cómo funciona — Pasos</h2>
                    <p class="field-section-desc">Título y contenido de los 3 pasos del proceso de compra. La visibilidad se controla desde "Secciones visibles".</p>

                    <div class="form-grid">
                      <div class="admin-form-group admin-form-group--full">
                        <label for="cf_title">Título de la sección</label>
                        <input type="text" id="cf_title" name="cf_title"
                          value="<?= htmlspecialchars($config['cf_title'] ?? 'Así de simple es recibirlo en casa') ?>">
                      </div>
                    </div>

                    <div class="stack-cards" style="margin-top:16px;">
                      <?php
                      $cfSteps = [
                        1 => ['icon'=>'📋','title'=>'Haz tu pedido','desc'=>'Llena el formulario en menos de 2 minutos. Sin registro previo ni tarjeta de crédito.'],
                        2 => ['icon'=>'📦','title'=>'Empacamos y enviamos','desc'=>'Al día siguiente hábil despachamos tu pedido, empacado con cuidado hacia tu puerta.'],
                        3 => ['icon'=>'🏠','title'=>'Lo recibes y pagas','desc'=>'El mensajero llega a tu casa. Revisas el producto y pagas solo cuando estás satisfecho.'],
                      ];
                      foreach ($cfSteps as $n => $def):
                      ?>
                      <div class="mini-card">
                        <div class="mini-card-title">
                          <i class="fas fa-circle-<?= $n ?>" style="color:var(--gold)" aria-hidden="true"></i> Paso <?= $n ?>
                        </div>
                        <div class="form-grid">
                          <div class="admin-form-group">
                            <label>Ícono (emoji)</label>
                            <input type="text" name="cf_step<?= $n ?>_icon"
                              value="<?= htmlspecialchars($config["cf_step{$n}_icon"] ?? $def['icon']) ?>"
                              maxlength="4" style="max-width:90px;">
                          </div>
                          <div class="admin-form-group">
                            <label>Título</label>
                            <input type="text" name="cf_step<?= $n ?>_title"
                              value="<?= htmlspecialchars($config["cf_step{$n}_title"] ?? $def['title']) ?>">
                          </div>
                          <div class="admin-form-group admin-form-group--full">
                            <label>Descripción</label>
                            <textarea name="cf_step<?= $n ?>_desc" rows="2"><?= htmlspecialchars($config["cf_step{$n}_desc"] ?? $def['desc']) ?></textarea>
                          </div>
                        </div>
                      </div>
                      <?php endforeach; ?>
                    </div>
                  </div>

                  <hr class="section-hr">

                  <!-- GARANTÍA -->
                  <div class="section-block" id="sec-garantia" data-toc="Garantía">
                    <h2>Banner de Garantía</h2>
                    <p class="field-section-desc">Banda de confianza que aparece antes del formulario. Actívala o desactívala según necesites.</p>

                    <div class="form-grid">
                      <div class="admin-form-group admin-form-group--full">
                        <label for="garantia_title">Título</label>
                        <input type="text" id="garantia_title" name="garantia_title"
                          value="<?= htmlspecialchars($config['garantia_title'] ?? 'Tu compra está 100% protegida') ?>">
                      </div>

                      <div class="admin-form-group admin-form-group--full">
                        <label for="garantia_desc">Descripción</label>
                        <textarea id="garantia_desc" name="garantia_desc" rows="3"><?= htmlspecialchars($config['garantia_desc'] ?? 'Si el producto llega dañado, diferente a lo descrito o simplemente no te convence, te lo solucionamos. Sin burocracia, sin excusas. Nuestra promesa es tu tranquilidad.') ?></textarea>
                      </div>

                      <div class="admin-form-group">
                        <label>Ítem 1</label>
                        <input type="text" name="garantia_item1"
                          value="<?= htmlspecialchars($config['garantia_item1'] ?? '💳 Pagas solo cuando recibes el producto en tus manos') ?>">
                      </div>
                      <div class="admin-form-group">
                        <label>Ítem 2</label>
                        <input type="text" name="garantia_item2"
                          value="<?= htmlspecialchars($config['garantia_item2'] ?? '🚚 Envío gratis incluido a cualquier ciudad') ?>">
                      </div>
                      <div class="admin-form-group">
                        <label>Ítem 3</label>
                        <input type="text" name="garantia_item3"
                          value="<?= htmlspecialchars($config['garantia_item3'] ?? '🔄 Si llega dañado o incorrecto, lo reponemos') ?>">
                      </div>
                      <div class="admin-form-group">
                        <label>Ítem 4</label>
                        <input type="text" name="garantia_item4"
                          value="<?= htmlspecialchars($config['garantia_item4'] ?? '💬 Asesor en WhatsApp disponible para ti') ?>">
                      </div>
                    </div>
                  </div>

                  <hr class="section-hr">

                  <!-- FORMULARIO — CABECERA -->
                  <div class="section-block" id="sec-form-header" data-toc="Form. pedido">
                    <h2>Formulario — Cabecera</h2>
                    <p class="field-section-desc">Título y subtítulo que aparecen sobre el formulario de pedido.</p>

                    <div class="form-grid">
                      <div class="admin-form-group admin-form-group--full">
                        <label for="form_title">Título del formulario</label>
                        <input type="text" id="form_title" name="form_title"
                          value="<?= htmlspecialchars($config['form_title'] ?? 'Haz tu pedido — Pago al recibir') ?>">
                      </div>
                      <div class="admin-form-group admin-form-group--full">
                        <label for="form_subtitle">Subtítulo</label>
                        <input type="text" id="form_subtitle" name="form_subtitle"
                          value="<?= htmlspecialchars($config['form_subtitle'] ?? 'Sin adelantos · El mensajero llega a tu puerta') ?>">
                      </div>
                    </div>
                  </div>

                  <hr class="section-hr">

                  <!-- REGALO / BONUS -->
                  <div class="section-block" id="sec-regalo" data-toc="Regalo">
                    <h2>Regalo incluido 🎁</h2>
                    <p class="field-section-desc">Imagen del artículo de regalo que se muestra en el resumen de oferta antes del formulario. Déjala vacía si no ofreces regalo.</p>

                    <div class="form-grid">
                      <div class="admin-form-group admin-form-group--full">
                        <label for="regalo_label">Texto del regalo</label>
                        <input type="text" id="regalo_label" name="regalo_label"
                          placeholder="Ej: Cartera a juego incluida de regalo"
                          value="<?= htmlspecialchars($config['regalo_label'] ?? '') ?>">
                      </div>

                      <div class="admin-form-group admin-form-group--full">
                        <label>Imagen del regalo</label>
                        <?php if (!empty($config['regalo_image_path'])): ?>
                        <div style="margin-bottom:8px;">
                          <img src="<?= htmlspecialchars($config['regalo_image_path']) ?>"
                               alt="Regalo actual" style="max-height:120px; border-radius:8px; object-fit:cover;">
                        </div>
                        <?php endif; ?>
                        <input type="hidden" name="regalo_image_path"
                               value="<?= htmlspecialchars($config['regalo_image_path'] ?? '') ?>">
                        <input type="file" id="regalo_image_file" name="regalo_image_file" accept="image/*">
                      </div>
                    </div>
                  </div>

                  <hr class="section-hr">

                  <!-- FOOTER -->
                  <div class="section-block" id="sec-footer" data-toc="Footer">
                    <h2>Footer</h2>

                    <div class="form-grid">
                      <div class="admin-form-group admin-form-group--full">
                        <?php $showFooter = !isset($config['show_footer']) || !empty($config['show_footer']); ?>
                        <label class="toggle-label--row">
                          <input type="hidden"   name="show_footer" value="0">
                          <input type="checkbox" name="show_footer" value="1" class="toggle-cb"
                            <?= $showFooter ? 'checked' : '' ?>>
                          <span class="toggle-track<?= $showFooter ? ' is-on' : '' ?>"><span class="toggle-thumb"></span></span>
                          <span class="toggle-label-text">Mostrar footer</span>
                        </label>
                      </div>
                      <div class="admin-form-group admin-form-group--full">
                        <label for="footer_text">Texto del footer</label>
                        <input type="text" id="footer_text" name="footer_text"
                          value="<?= htmlspecialchars($config['footer_text'] ?? '') ?>">
                      </div>
                    </div>
                  </div>

                  <!-- Acciones sticky — solo visible en mobile (en desktop el TOC tiene el botón) -->
                  <div class="admin-form-actions admin-form-actions--mobile-only">
                    <button type="submit" class="btn-estado">
                      <i class="fas fa-save"></i>
                      <span>Guardar cambios</span>
                    </button>
                  </div>

                </form>
              </div>
            </div>

          </div><!-- /landing-editor-main -->

          <!-- TOC: rail derecho en ≥1200px, horizontal en ≥992px, oculto en mobile -->
          <aside class="landing-editor-toc" aria-label="Índice de secciones">
            <div class="toc-card">
              <div class="toc-card__head">
                <span class="toc-card__label">Secciones</span>
                <button type="button" id="tocVerTodo" class="toc-ver-todo"
                        title="Expandir o colapsar todas las secciones"
                        aria-label="Colapsar todas las secciones">Colapsar todo</button>
              </div>
              <nav class="toc-nav" id="landingToc" aria-label="Índice de secciones">
                <a href="#sec-secciones" data-target="sec-secciones">Secciones</a>
                <a href="#sec-hero" data-target="sec-hero">Hero</a>
                <a href="#sec-beneficios" data-target="sec-beneficios">Beneficios</a>
                <a href="#sec-galeria" data-target="sec-galeria">Galería</a>
                <a href="#sec-caracteristicas" data-target="sec-caracteristicas">Características</a>
                <a href="#sec-contador" data-target="sec-contador">Contador</a>
                <a href="#sec-porque" data-target="sec-porque">¿Por qué?</a>
                <a href="#sec-comparison" data-target="sec-comparison">Comparativa</a>
                <a href="#sec-testimonios" data-target="sec-testimonios">Testimonios</a>
                <a href="#sec-paraquien" data-target="sec-paraquien">¿Para quién?</a>
                <a href="#sec-wa" data-target="sec-wa">WhatsApp</a>
                <a href="#sec-faq" data-target="sec-faq">FAQ</a>
                <a href="#sec-autoridad" data-target="sec-autoridad">Autoridad</a>
                <a href="#sec-ctas" data-target="sec-ctas">CTAs</a>
                <a href="#sec-combo" data-target="sec-combo">Combo</a>
                <a href="#sec-colores" data-target="sec-colores">Colores</a>
                <a href="#sec-announcement" data-target="sec-announcement">Barra</a>
                <a href="#sec-hero-trust" data-target="sec-hero-trust">Confianza</a>
                <a href="#sec-comofunciona-content" data-target="sec-comofunciona-content">Cómo funciona</a>
                <a href="#sec-garantia" data-target="sec-garantia">Garantía</a>
                <a href="#sec-form-header" data-target="sec-form-header">Formulario</a>
                <a href="#sec-regalo" data-target="sec-regalo">Regalo</a>
                <a href="#sec-footer" data-target="sec-footer">Footer</a>
              </nav>
              <button type="submit" form="formLanding" class="btn-estado toc-save-btn" id="btnGuardar">
                <i class="fas fa-save" id="btnGuardarIcon"></i>
                <span id="btnGuardarLabel">Guardar cambios</span>
              </button>
            </div>
          </aside>

        </div><!-- /landing-editor-layout -->

      </section><!-- /material-content -->
    </main>
  </div><!-- /app-shell -->

  <!-- ===== PANEL DE PREVIEW IFRAME ======================================== -->
  <div id="previewPanel" class="preview-panel" aria-hidden="true">
    <div class="preview-panel__header">
      <div class="preview-panel__title">
        <i class="fas fa-eye" aria-hidden="true"></i> Vista previa
      </div>
      <div class="preview-panel__breakpoints" role="group" aria-label="Tamaño de pantalla">
        <button type="button" class="preview-bp preview-bp--active" data-width="375" aria-label="Móvil">
          <i class="fas fa-mobile-screen" aria-hidden="true"></i>
        </button>
        <button type="button" class="preview-bp" data-width="768" aria-label="Tablet">
          <i class="fas fa-tablet-screen-button" aria-hidden="true"></i>
        </button>
        <button type="button" class="preview-bp" data-width="1280" aria-label="Desktop">
          <i class="fas fa-desktop" aria-hidden="true"></i>
        </button>
      </div>
      <button type="button" id="previewPanelClose" class="preview-panel__close" aria-label="Cerrar preview">
        <i class="fas fa-xmark" aria-hidden="true"></i>
      </button>
    </div>
    <div class="preview-panel__viewport" id="previewViewport">
      <div class="preview-panel__iframe-wrap" id="previewIframeWrap">
        <iframe id="previewIframe"
                src=""
                title="Vista previa de la landing"
                loading="lazy"
                sandbox="allow-scripts allow-same-origin allow-forms">
        </iframe>
      </div>
    </div>
    <div class="preview-panel__footer">
      <span class="preview-panel__url" id="previewUrl"></span>
      <a id="previewOpenTab" href="#" target="_blank" rel="noopener" class="preview-panel__open-tab">
        <i class="fas fa-up-right-from-square" aria-hidden="true"></i> Abrir en nueva pestaña
      </a>
    </div>
  </div>
  <div id="previewPanelBackdrop" class="preview-panel__backdrop" style="display:none;" aria-hidden="true"></div>

  <!-- ===== PANEL DE RECOMENDACIONES IA ================================== -->
  <div id="recoPanel" class="reco-panel" aria-hidden="true">
    <div class="reco-panel__head">
      <i class="fas fa-lightbulb" aria-hidden="true"></i>
      <h2>Recomendaciones de IA</h2>
      <button type="button" id="recoPanelClose" class="reco-panel__close" aria-label="Cerrar recomendaciones">
        <i class="fas fa-xmark" aria-hidden="true"></i>
      </button>
    </div>
    <div class="reco-panel__body" id="recoPanelBody"></div>
    <div class="reco-panel__foot" id="recoPanelFoot"></div>
  </div>
  <div id="recoPanelBackdrop" class="preview-panel__backdrop" style="display:none;" aria-hidden="true"></div>

  <script>
  window.__RECO__ = <?= json_encode($recomendaciones ?? null, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
  </script>

  <script>
  (function() {
    const LANDING_URL = '<?= BASE_URL ?>/Landing/index?producto_id=<?= (int)$producto_id ?>';
    const panel    = document.getElementById('previewPanel');
    const backdrop = document.getElementById('previewPanelBackdrop');
    const iframe   = document.getElementById('previewIframe');
    const wrap     = document.getElementById('previewIframeWrap');
    const urlLabel = document.getElementById('previewUrl');
    const openTab  = document.getElementById('previewOpenTab');

    let loaded = false;

    function openPreview() {
      if (!loaded) {
        iframe.src = LANDING_URL;
        if (urlLabel) urlLabel.textContent = LANDING_URL;
        if (openTab)  openTab.href = LANDING_URL;
        loaded = true;
      }
      panel.classList.add('is-open');
      panel.setAttribute('aria-hidden', 'false');
      backdrop.style.display = 'block';
      document.body.classList.add('preview-panel-open');
    }

    function closePreview() {
      panel.classList.remove('is-open');
      panel.setAttribute('aria-hidden', 'true');
      backdrop.style.display = 'none';
      document.body.classList.remove('preview-panel-open');
    }

    // Breakpoint buttons
    document.querySelectorAll('.preview-bp').forEach(function(btn) {
      btn.addEventListener('click', function() {
        document.querySelectorAll('.preview-bp').forEach(b => b.classList.remove('preview-bp--active'));
        btn.classList.add('preview-bp--active');
        const w = parseInt(btn.dataset.width, 10);
        if (wrap) {
          wrap.style.width  = w + 'px';
          wrap.style.margin = '0 auto';
        }
      });
    });

    // Close
    document.getElementById('previewPanelClose').addEventListener('click', closePreview);
    backdrop.addEventListener('click', closePreview);
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && panel.classList.contains('is-open')) closePreview();
    });

    // Reload iframe when form is saved — watch for success alert appearing
    const successAlert = document.querySelector('.admin-alert-success');
    if (successAlert) {
      iframe.src = LANDING_URL;
      loaded = true;
    }

    // Expose opener globally so the header CTA can use it
    window.openLandingPreview = openPreview;
  })();
  </script>

  <script>
    function toggleMediaPreview(section, type) {
      const input = document.getElementById(section + '_media_file');
      if (!input) return;

      if (type === 'video') {
        input.setAttribute('accept', 'video/mp4,video/quicktime,video/webm');
      } else if (type === 'gif') {
        input.setAttribute('accept', 'image/gif');
      } else {
        input.setAttribute('accept', 'image/jpeg,image/png,image/webp,image/gif');
      }

      if (section === 'hero') {
        const posterGroup = document.getElementById('hero-poster-group');
        if (posterGroup) posterGroup.style.display = type === 'video' ? '' : 'none';
      }
    }

    // Drag-and-drop reordering de secciones
    (function() {
      const grid    = document.querySelector('.sections-toggle-grid');
      const orderIn = document.getElementById('section_order_input');
      if (!grid || !orderIn) return;

      let dragged = null;

      function saveOrder() {
        const items = grid.querySelectorAll('.section-toggle-item[data-key]');
        const order = Array.from(items).map(el => el.dataset.key);
        orderIn.value = order.join(',');
        // Actualiza los números de posición
        items.forEach(function(el, idx) {
          const pos = el.querySelector('.section-toggle-pos');
          if (pos) pos.textContent = idx + 1;
        });
      }

      // Alternativa de teclado para los drag handles
      grid.querySelectorAll('.drag-handle').forEach(function(handle) {
        handle.setAttribute('tabindex', '0');
        handle.setAttribute('role', 'button');
        handle.setAttribute('aria-label', 'Mover sección. Usa flechas arriba/abajo para reordenar');
        handle.addEventListener('keydown', function(e) {
          if (e.key !== 'ArrowUp' && e.key !== 'ArrowDown') return;
          e.preventDefault();
          const item = handle.closest('.section-toggle-item[data-key]');
          if (!item) return;
          const items = Array.from(grid.querySelectorAll('.section-toggle-item[data-key]'));
          const idx = items.indexOf(item);
          if (e.key === 'ArrowUp' && idx > 0) {
            grid.insertBefore(item, items[idx - 1]);
          } else if (e.key === 'ArrowDown' && idx < items.length - 1) {
            grid.insertBefore(items[idx + 1], item);
          }
          saveOrder();
          handle.focus();
        });
      });

      grid.addEventListener('dragstart', function(e) {
        const item = e.target.closest('.section-toggle-item[data-key]');
        if (!item) return;
        dragged = item;
        setTimeout(function() { item.classList.add('dragging'); }, 0);
        e.dataTransfer.effectAllowed = 'move';
      });

      grid.addEventListener('dragend', function() {
        if (dragged) dragged.classList.remove('dragging');
        grid.querySelectorAll('.drag-over').forEach(el => el.classList.remove('drag-over'));
        saveOrder();
        dragged = null;
      });

      grid.addEventListener('dragover', function(e) {
        e.preventDefault();
        const item = e.target.closest('.section-toggle-item[data-key]');
        if (!item || item === dragged) return;
        grid.querySelectorAll('.drag-over').forEach(el => el.classList.remove('drag-over'));
        item.classList.add('drag-over');
      });

      grid.addEventListener('dragleave', function(e) {
        const item = e.target.closest('.section-toggle-item[data-key]');
        if (item) item.classList.remove('drag-over');
      });

      grid.addEventListener('drop', function(e) {
        e.preventDefault();
        const target = e.target.closest('.section-toggle-item[data-key]');
        if (!target || !dragged || target === dragged) return;
        target.classList.remove('drag-over');

        // Insert before or after target based on pointer position
        const rect   = target.getBoundingClientRect();
        const middle = rect.top + rect.height / 2;
        if (e.clientY < middle) {
          grid.insertBefore(dragged, target);
        } else {
          grid.insertBefore(dragged, target.nextSibling);
        }
        saveOrder();
      });
    })();

    // Preview universal + validación de tamaño para todos los file inputs
    const MAX_IMG = 2 * 1024 * 1024;   // 2 MB
    const MAX_VID = 10 * 1024 * 1024;  // 10 MB

    function findPreview(input) {
      let el = input;
      while (el && el !== document.body) {
        el = el.parentElement;
        const p = el ? el.querySelector('.media-preview') : null;
        if (p) return p;
      }
      return null;
    }

    document.querySelectorAll('input[type="file"]').forEach(function(input) {
      input.addEventListener('change', function() {
        const file = this.files[0];
        if (!file) return;

        const isVideo = file.type.startsWith('video/');
        const limit = isVideo ? MAX_VID : MAX_IMG;
        const limitLabel = isVideo ? '10 MB' : '2 MB';

        if (file.size > limit) {
          alert('El archivo "' + file.name + '" pesa ' +
            (file.size / 1024 / 1024).toFixed(1) + ' MB. ' +
            'El máximo permitido es ' + limitLabel + '.');
          this.value = '';
          return;
        }

        const preview = findPreview(this);
        if (!preview) return;

        const url = URL.createObjectURL(file);
        if (isVideo) {
          preview.innerHTML = '<video src="' + url + '" muted loop controls></video>';
        } else {
          const dim = '<span class="media-preview-dim js-dim" style="position:absolute;bottom:6px;right:8px;font-size:10px;font-weight:700;color:var(--tx-muted);background:var(--bg-elevated);padding:2px 7px;border-radius:6px;letter-spacing:.04em;"></span>';
          preview.innerHTML = '<img src="' + url + '" alt="Preview">' + dim;
          const img = preview.querySelector('img');
          img.onload = function() {
            const d = preview.querySelector('.js-dim');
            if (d) d.textContent = img.naturalWidth + ' × ' + img.naturalHeight + ' px';
          };
        }
      });
    });

    // Etiqueta de dimensiones en imágenes ya cargadas
    document.querySelectorAll('.media-preview img').forEach(function(img) {
      function addDim() {
        if (!img.naturalWidth) return;
        const wrap = img.closest('.media-preview');
        if (!wrap || wrap.querySelector('.js-dim')) return;
        wrap.style.position = 'relative';
        const d = document.createElement('span');
        d.className = 'js-dim';
        d.style.cssText = 'position:absolute;bottom:6px;right:8px;font-size:10px;font-weight:700;color:var(--tx-muted);background:var(--bg-elevated);padding:2px 7px;border-radius:6px;letter-spacing:.04em;';
        d.textContent = img.naturalWidth + ' × ' + img.naturalHeight + ' px';
        wrap.appendChild(d);
      }
      if (img.complete) addDim(); else img.addEventListener('load', addDim);
    });

    // Aviso de cambios sin guardar al salir
    let formDirty = false;
    // BUG FIX: usar getElementById para evitar capturar el form del panel
    const editorForm = document.getElementById('formLanding');
    if (editorForm) {
      editorForm.addEventListener('input',  function() { formDirty = true;  window.formDirty = true; });
      editorForm.addEventListener('change', function() { formDirty = true;  window.formDirty = true; });
      editorForm.addEventListener('submit', function() {
        formDirty = false;
        window.formDirty = false;
        const btn   = document.getElementById('btnGuardar');
        const icon  = document.getElementById('btnGuardarIcon');
        const label = document.getElementById('btnGuardarLabel');
        if (btn) {
          btn.disabled = true;
          if (icon)  icon.className  = 'fas fa-spinner fa-spin';
          if (label) label.textContent = 'Guardando…';
        }
      });
    }
    window.addEventListener('beforeunload', function(e) {
      if (!formDirty) return;
      e.preventDefault();
      e.returnValue = '';
    });

    // Sync toggle-track .is-on class with checkbox state at runtime
    document.querySelectorAll('.toggle-cb').forEach(function(cb) {
      const track = cb.nextElementSibling;
      if (!track || !track.classList.contains('toggle-track')) return;
      cb.addEventListener('change', function() {
        track.classList.toggle('is-on', cb.checked);
      });
    });

    // Char counters: event delegation for [data-char-counter] textareas
    document.addEventListener('input', function(e) {
      const ta = e.target.closest('textarea[data-char-counter]');
      if (!ta) return;
      const counter = document.getElementById(ta.dataset.charCounter);
      if (counter) counter.textContent = ta.value.length + '/' + (ta.maxLength || 100);
    });
  </script>

  <script>
    // Sincroniza picker → hex
    function syncHex(picker, hexId) {
      const el = document.getElementById(hexId);
      if (el) el.value = picker.value.toUpperCase();
    }

    // Sincroniza hex → picker
    function syncPicker(hexInput, pickerId) {
      const val = hexInput.value.trim();
      if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
        const picker = document.getElementById(pickerId);
        if (picker) picker.value = val;
      }
    }

    // Actualiza palette preview (mini-mockup + swatches)
    function updatePreviewBar() {
      const ids = [
        'background_color', 'text_color', 'primary_color',
        'accent_color', 'secondary_color', 'color_gold',
        'color_success', 'color_countdown'
      ];
      ids.forEach(function(id) {
        const picker  = document.getElementById(id);
        const swatch  = document.getElementById('prev_' + id);
        if (picker && swatch) swatch.style.background = picker.value;
      });

      const bar = document.getElementById('colorPreviewBar');
      if (!bar) return;

      const get = (id, def) => { const el = document.getElementById(id); return el ? el.value : def; };
      const bg      = get('background_color', '#0a0a0a');
      const tx      = get('text_color',       '#f0ebe0');
      const primary = get('primary_color',    '#c9a84c');
      const success = get('color_success',    '#4caf7d');
      const timer   = get('color_countdown',  '#e8c96a');

      bar.style.background = bg;

      const title = bar.querySelector('.palette-preview__title');
      const body  = bar.querySelector('.palette-preview__body');
      if (title) title.style.color = tx;
      if (body)  body.style.color  = tx;

      const btnP = bar.querySelector('.palette-preview__btn-primary');
      if (btnP) { btnP.style.background = primary; btnP.style.color = bg; }

      const badge = bar.querySelector('.palette-preview__badge');
      if (badge) { badge.style.color = success; badge.style.borderColor = success; }

      const timerEl = bar.querySelector('.palette-preview__timer');
      if (timerEl) timerEl.style.color = timer;
    }

    // Aplica colores del tema al editor sin guardar
    function applyThemePreview(theme) {
      /* El mapa de paletas se emite desde app/config/themes.php: es la
         misma fuente que usan las tarjetas del editor, el validador del
         controlador y la vista publica. Antes era una cuarta copia a
         mano y bastaba olvidar una para que el tema fallara en silencio. */
      const themes = <?= json_encode(array_map(fn($t) => $t["paleta"], $temasCfg), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?>;

      const palette = themes[theme];
      if (!palette) return;

      Object.keys(palette).forEach(function(key) {
        const picker = document.getElementById(key);
        const hex = document.getElementById(key + '_hex');
        const val = palette[key];
        if (picker) picker.value = val;
        if (hex) hex.value = val.toUpperCase();
      });

      // Marcar tarjeta activa
      document.querySelectorAll('.theme-card').forEach(function(card) {
        card.classList.remove('theme-card--active');
      });
      const radio = document.querySelector('input[name="theme"][value="' + theme + '"]');
      if (radio && radio.closest('.theme-card')) {
        radio.closest('.theme-card').classList.add('theme-card--active');
      }

      updatePreviewBar();
    }
  </script>


  <!-- JS global del admin (menú) -->
  <script src="<?= BASE_URL ?>/public/js/modal-a11y.js"></script>
  <script src="<?= BASE_URL ?>/public/js/form-labels.js"></script>
  <script src="<?= BASE_URL ?>/public/js/funciones.js"></script>

  <!-- JS del índice lateral -->
  <script src="<?= BASE_URL ?>/public/js/admin-landing-toc.js"></script>
  <script src="<?= BASE_URL ?>/public/js/ux-improvements.js"></script>
  <!-- Panel de recomendaciones de IA (Estadísticas → Analizar) -->
  <script src="<?= BASE_URL ?>/public/js/admin-landing-reco.js" defer></script>

  <!-- ===== MODAL IA ===================================================== -->
  <div class="ia-modal-overlay" id="iaModalOverlay" aria-hidden="true">
    <div class="ia-modal" role="dialog" aria-labelledby="iaModalTitle" aria-modal="true">

      <div class="ia-modal__header">
        <h2 id="iaModalTitle">✨ Generar landing con IA</h2>
        <button type="button" class="ia-modal__close" id="iaModalClose" aria-label="Cerrar">✕</button>
      </div>

      <!-- Paso 1: Datos del producto -->
      <div class="ia-modal__body" id="iaStep1">
        <p class="ia-modal__hint">
          Describe tu producto y Claude genera <strong>todos los textos</strong> optimizados para convertir en Colombia.
          El proceso toma ~20 segundos.
        </p>

        <div class="ia-modal__field">
          <label for="ia_nombre">Nombre del producto <span class="ia-req">*</span></label>
          <input type="text" id="ia_nombre" placeholder="Ej: Bolso Hobo de cuero sintético"
                 value="<?= htmlspecialchars($producto['nombre'] ?? '') ?>">
        </div>

        <div class="ia-modal__field">
          <label for="ia_descripcion">¿Qué hace o qué problema resuelve? <span class="ia-req">*</span></label>
          <textarea id="ia_descripcion" rows="3"
                    placeholder="Ej: Bolso espacioso para mujer con múltiples compartimentos, correa ajustable, cierre magnético y diseño elegante que combina con cualquier outfit. Perfecto para uso diario."></textarea>
        </div>

        <div class="ia-modal__row">
          <div class="ia-modal__field">
            <label for="ia_publico">Público objetivo</label>
            <input type="text" id="ia_publico" placeholder="Ej: Mujeres colombianas de 25-45 años">
          </div>
          <div class="ia-modal__field">
            <label for="ia_precio">Precio (COP)</label>
            <input type="text" id="ia_precio" placeholder="Ej: 89.900">
          </div>
        </div>

        <?php $brief = $brief ?? []; $notasMarca = $notas_marca ?? ''; ?>
        <div class="ia-brief">
          <div class="ia-brief__head">
            <p class="ia-brief__lead">Entre más concreto seas aquí, menos genérico sale el copy.</p>
            <button type="button" class="ia-brief__suggest" id="iaBtnSugerirBrief">✨ Ayúdame con el brief</button>
          </div>
          <p class="ia-brief__dolor" id="iaBriefDolor" hidden></p>

          <div class="ia-modal__field">
            <label for="ia_avatar">¿Quién es exactamente tu cliente?</label>
            <textarea id="ia_avatar" rows="2"
              placeholder="Ej: Mamá de 30-40 en Bogotá, trabaja, carga bolso lleno; le da rabia no encontrar nada rápido"><?= htmlspecialchars($brief['avatar'] ?? '') ?></textarea>
          </div>

          <div class="ia-modal__field">
            <label for="ia_escena">¿En qué momento vive el dolor?</label>
            <input type="text" id="ia_escena"
              placeholder="Ej: en la fila del banco buscando las llaves con el niño llorando"
              value="<?= htmlspecialchars($brief['escena'] ?? '') ?>">
          </div>

          <div class="ia-modal__row">
            <div class="ia-modal__field">
              <label for="ia_objecion">¿Qué lo frena para comprar?</label>
              <input type="text" id="ia_objecion" placeholder="Ej: miedo a que la calidad sea mala"
                value="<?= htmlspecialchars($brief['objecion'] ?? '') ?>">
            </div>
            <div class="ia-modal__field">
              <label for="ia_alternativa">¿Qué usa hoy en su lugar?</label>
              <input type="text" id="ia_alternativa" placeholder="Ej: un bolso viejo que ya no cierra"
                value="<?= htmlspecialchars($brief['alternativa'] ?? '') ?>">
            </div>
          </div>

          <div class="ia-modal__row">
            <div class="ia-modal__field">
              <label for="ia_voz">Voz de marca</label>
              <?php $vozSel = $brief['voz'] ?? 'cercana'; ?>
              <select id="ia_voz" class="ia-brief__select">
                <option value="cercana" <?= $vozSel === 'cercana' ? 'selected' : '' ?>>Cercana y cálida</option>
                <option value="experta" <?= $vozSel === 'experta' ? 'selected' : '' ?>>Experta y segura</option>
                <option value="picara"  <?= $vozSel === 'picara'  ? 'selected' : '' ?>>Pícara / con humor</option>
                <option value="premium" <?= $vozSel === 'premium' ? 'selected' : '' ?>>Premium y sobria</option>
              </select>
            </div>
            <div class="ia-modal__field">
              <?php $agr = (int)($brief['agresividad'] ?? 3); ?>
              <label for="ia_agresividad">Agresividad del copy: <b id="ia_agresividad_val"><?= $agr ?></b>/5</label>
              <input type="range" id="ia_agresividad" class="ia-brief__range"
                min="1" max="5" step="1" value="<?= $agr ?>">
              <small class="ia-brief__range-hint">1 = informativo · 5 = confronta desde el dolor</small>
            </div>
          </div>

          <details class="ia-brief__notas"<?= trim($notasMarca) !== '' ? ' open' : '' ?>>
            <summary>Notas de marca · copys que sí venden (opcional)</summary>
            <div class="ia-modal__field">
              <textarea id="ia_notas_marca" rows="4"
                placeholder="Pega 2-3 frases o textos tuyos que SÍ conectan y venden — Claude imita ese tono. También puedes anotar palabras que nunca quieres ver."><?= htmlspecialchars($notasMarca) ?></textarea>
              <small>Se guarda para todas tus landings, no solo esta.</small>
            </div>
          </details>
        </div>

        <?php if (!$tieneApiKey): ?>
        <div class="ia-modal__key-section" id="iaKeySection">
          <div class="ia-modal__key-label">
            <i class="fas fa-key" aria-hidden="true"></i> API Key de Claude
            <a href="https://console.anthropic.com/settings/keys" target="_blank" rel="noopener" class="ia-modal__key-link">
              Obtener key →
            </a>
          </div>
          <input type="password" id="ia_api_key" placeholder="sk-ant-api03-...">
          <small>Se guarda de forma segura en tu base de datos. Solo la ingresas una vez.</small>
        </div>
        <?php else: ?>
        <div class="ia-modal__key-saved">
          <i class="fas fa-circle-check" aria-hidden="true"></i> API Key configurada
          <button type="button" class="ia-modal__key-change" id="btnCambiarKey">Cambiar</button>
        </div>
        <div class="ia-modal__key-section" id="iaKeySection" style="display:none;">
          <input type="password" id="ia_api_key" placeholder="sk-ant-api03-...">
        </div>
        <?php endif; ?>

        <div class="ia-modal__error" id="iaError" style="display:none;"></div>

        <button type="button" class="ia-modal__submit" id="iaBtnAngulos">
          ✨ Proponer 3 ángulos de venta →
        </button>
        <button type="button" class="ia-modal__linkbtn" id="iaBtnDirecto">
          o generar la landing directo, sin elegir ángulo
        </button>
      </div>

      <!-- Paso 1b: Elegir ángulo -->
      <div class="ia-modal__body" id="iaStepAngulos" style="display:none;">
        <p class="ia-modal__hint">Elige el ángulo con el que Claude va a escribir <strong>toda</strong> la landing. Puedes ajustar el que elijas antes de generar.</p>
        <div class="ia-angulos" id="iaAngulosList"></div>

        <details class="ia-brief__notas" id="iaAnguloAjuste">
          <summary>Ajustar el ángulo elegido</summary>
          <div class="ia-modal__field">
            <label for="iaAngDolor">Dolor central</label>
            <textarea id="iaAngDolor" rows="2"></textarea>
          </div>
          <div class="ia-modal__field">
            <label for="iaAngIdea">Gran idea / promesa</label>
            <textarea id="iaAngIdea" rows="2"></textarea>
          </div>
          <div class="ia-modal__field">
            <label for="iaAngHeadline">Dirección del titular</label>
            <input type="text" id="iaAngHeadline">
          </div>
          <div class="ia-modal__field">
            <label for="iaAngQuien">Le habla a</label>
            <input type="text" id="iaAngQuien">
          </div>
        </details>

        <div class="ia-modal__error" id="iaAngulosError" style="display:none;"></div>
        <button type="button" class="ia-modal__submit" id="iaBtnGenerarConAngulo">
          ✨ Generar landing con este ángulo
        </button>
        <button type="button" class="ia-modal__linkbtn" id="iaBtnVolverBrief">← volver al brief</button>
      </div>

      <!-- Paso 2: Cargando -->
      <div class="ia-modal__body ia-modal__loading" id="iaStep2" style="display:none;">
        <div class="ia-spinner"></div>
        <p id="iaStep2Msg">Claude está escribiendo tu landing...</p>
        <small id="iaStep2Sub">Generando ~60 textos optimizados para el mercado colombiano. Espera ~20-30 segundos.</small>
      </div>

      <!-- Paso 3: Éxito -->
      <div class="ia-modal__body ia-modal__success" id="iaStep3" style="display:none;">
        <div class="ia-success-icon">✅</div>
        <h2>¡Landing generada!</h2>
        <p id="iaSuccessMsg">Todos los textos fueron rellenados. Revisa, ajusta lo que quieras y guarda.</p>
        <div class="ia-ang-resumen" id="iaAngResumen" hidden></div>
        <button type="button" class="ia-modal__submit" id="iaBtnCerrarOk">Revisar y guardar →</button>
      </div>

    </div>
  </div>

  <script>
  (() => {
    const BASE      = '<?= BASE_URL ?>';
    const PRODUCTO_ID = <?= json_encode((string)$producto_id) ?>;
    const overlay   = document.getElementById('iaModalOverlay');
    const btnAbrir  = document.getElementById('btnAbrirIA');
    const btnCerrar = document.getElementById('iaModalClose');
    const btnCerrarOk = document.getElementById('iaBtnCerrarOk');
    const btnAngulos  = document.getElementById('iaBtnAngulos');
    const btnDirecto  = document.getElementById('iaBtnDirecto');
    const btnGenAng   = document.getElementById('iaBtnGenerarConAngulo');
    const btnVolver   = document.getElementById('iaBtnVolverBrief');
    const step1     = document.getElementById('iaStep1');
    const stepA     = document.getElementById('iaStepAngulos');
    const step2     = document.getElementById('iaStep2');
    const step3     = document.getElementById('iaStep3');
    const errEl     = document.getElementById('iaError');
    const errAng    = document.getElementById('iaAngulosError');
    const btnCambiarKey = document.getElementById('btnCambiarKey');
    const keySection    = document.getElementById('iaKeySection');
    const btnSugerir    = document.getElementById('iaBtnSugerirBrief');

    let _angulos = [];   // ángulos propuestos por la IA en este ciclo

    let _modalOpener = null;
    const openModal  = (triggerEl) => {
      _modalOpener = triggerEl || document.activeElement;
      overlay.classList.add('is-open');
      overlay.setAttribute('aria-hidden', 'false');
      const firstFocusable = overlay.querySelector('input:not([type="hidden"]), textarea, button, [tabindex]');
      if (firstFocusable) firstFocusable.focus();
    };
    const closeModal = () => {
      overlay.classList.remove('is-open');
      overlay.setAttribute('aria-hidden', 'true');
      showStep(1);
      if (_modalOpener) { _modalOpener.focus(); _modalOpener = null; }
    };

    const showStep = (n) => {
      step1.style.display = n === 1  ? '' : 'none';
      stepA.style.display = n === 'A' ? '' : 'none';
      step2.style.display = n === 2  ? '' : 'none';
      step3.style.display = n === 3  ? '' : 'none';
    };

    if (btnAbrir)    btnAbrir.addEventListener('click', (e) => openModal(e.currentTarget));
    if (btnCerrar)   btnCerrar.addEventListener('click', closeModal);
    if (btnCerrarOk) btnCerrarOk.addEventListener('click', closeModal);
    overlay.addEventListener('click', (e) => { if (e.target === overlay) closeModal(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });

    if (btnCambiarKey && keySection) {
      btnCambiarKey.addEventListener('click', () => {
        keySection.style.display = keySection.style.display === 'none' ? '' : 'none';
      });
    }

    const agrRange = document.getElementById('ia_agresividad');
    const agrVal   = document.getElementById('ia_agresividad_val');
    if (agrRange && agrVal) {
      agrRange.addEventListener('input', () => { agrVal.textContent = agrRange.value; });
    }

    const setError    = (msg) => { errEl.textContent = msg;  errEl.style.display  = msg ? 'block' : 'none'; };
    const setAngError = (msg) => { errAng.textContent = msg; errAng.style.display = msg ? 'block' : 'none'; };

    const val = (id) => (document.getElementById(id)?.value || '').trim();

    const baseFields = () => ({
      producto_id: PRODUCTO_ID,
      nombre:      val('ia_nombre'),
      descripcion: val('ia_descripcion'),
      publico:     val('ia_publico'),
      precio:      val('ia_precio'),
    });

    const briefFields = () => ({
      brief_avatar:      val('ia_avatar'),
      brief_escena:      val('ia_escena'),
      brief_objecion:    val('ia_objecion'),
      brief_alternativa: val('ia_alternativa'),
      brief_voz:         document.getElementById('ia_voz')?.value || 'cercana',
      brief_agresividad: document.getElementById('ia_agresividad')?.value || '3',
      notas_marca:       val('ia_notas_marca'),
    });

    // Guarda la API key si el usuario escribió una nueva. Devuelve true si todo ok.
    const guardarKeyEscrita = async () => {
      const el = document.getElementById('ia_api_key');
      const key = el ? el.value.trim() : '';
      if (!key) return true;
      try {
        const r = await fetch(BASE + '/AdminLanding/guardarApiKey', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({ api_key: key, csrf_token: window.__CSRF__ || '' }),
        });
        const d = await r.json();
        if (!d.ok) { setError(d.error || 'Error al guardar la API key.'); return false; }
      } catch (e) {
        setError('Error de red al guardar la API key.');
        return false;
      }
      return true;
    };

    const post = async (accion, obj) => {
      const body = new URLSearchParams(Object.assign({ csrf_token: window.__CSRF__ || '' }, obj));
      const res  = await fetch(BASE + '/AdminLanding/' + accion, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body,
      });
      return res.json();
    };

    const marcarOrig = (el, v) => { if (window.__iaMarcarOriginal) window.__iaMarcarOriginal(el, v); };

    // Rellena el formulario con la respuesta de Claude. Ignora las claves _*.
    const rellenar = (fields) => {
      let filled = 0;
      Object.keys(fields || {}).forEach((key) => {
        const v = fields[key];
        if (!v || key.charAt(0) === '_') return;
        const input = document.querySelector('input[name="' + key + '"]');
        if (input && ['hidden', 'file', 'checkbox', 'radio'].indexOf(input.type) === -1) {
          input.value = v;
          input.dispatchEvent(new Event('input', { bubbles: true }));
          marcarOrig(input, v);
          filled++;
          return;
        }
        const ta = document.querySelector('textarea[name="' + key + '"]');
        if (ta) {
          ta.value = v;
          ta.dispatchEvent(new Event('input', { bubbles: true }));
          marcarOrig(ta, v);
          filled++;
        }
      });
      return filled;
    };

    const irAExito = (fields, filled) => {
      document.getElementById('iaSuccessMsg').textContent =
        filled + ' campos rellenados. Revisa, ajusta lo que quieras y guarda.';
      const box = document.getElementById('iaAngResumen');
      if (fields._dolor || fields._angulo) {
        box.innerHTML =
          (fields._dolor  ? '<b>Dolor:</b> ' + escapeHtml(fields._dolor) + '<br>' : '') +
          (fields._angulo ? '<b>Ángulo:</b> ' + escapeHtml(fields._angulo) : '');
        box.hidden = false;
      } else {
        box.hidden = true;
      }
      showStep(3);
    };

    const escapeHtml = (s) => String(s).replace(/[&<>"]/g, (c) =>
      ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

    // ── Validación común ────────────────────────────────────────────────────
    const validarBase = () => {
      if (!val('ia_nombre') || !val('ia_descripcion')) {
        setError('El nombre y la descripción del producto son obligatorios.');
        return false;
      }
      return true;
    };

    // ── "Ayúdame con el brief": Claude propone público + dolor real ────────
    const setBriefVal = (id, v) => {
      const el = document.getElementById(id);
      if (el && v) { el.value = v; el.dispatchEvent(new Event('input', { bubbles: true })); }
    };
    if (btnSugerir) {
      btnSugerir.addEventListener('click', async () => {
        setError('');
        if (!val('ia_nombre') && !val('ia_descripcion')) {
          setError('Escribe al menos el nombre o una frase de qué es el producto.');
          return;
        }
        if (!(await guardarKeyEscrita())) return;

        const txtOrig = btnSugerir.textContent;
        btnSugerir.disabled = true;
        btnSugerir.textContent = '✨ Pensando el brief…';
        try {
          const data = await post('sugerirBriefIA', {
            nombre: val('ia_nombre'), descripcion: val('ia_descripcion'), precio: val('ia_precio'),
          });
          if (!data.ok) {
            setError(data.error === 'no_key' ? 'Primero ingresa tu API key de Claude.' : (data.error || 'No se pudo. Intenta de nuevo.'));
            return;
          }
          const b = data.brief || {};
          setBriefVal('ia_publico',     b.publico);
          setBriefVal('ia_avatar',      b.avatar);
          setBriefVal('ia_escena',      b.escena);
          setBriefVal('ia_objecion',    b.objecion);
          setBriefVal('ia_alternativa', b.alternativa);
          if (b.voz) { const s = document.getElementById('ia_voz'); if (s) s.value = b.voz; }
          if (b.agresividad) {
            const r = document.getElementById('ia_agresividad');
            if (r) { r.value = b.agresividad; if (agrVal) agrVal.textContent = b.agresividad; }
          }
          const dolorEl = document.getElementById('iaBriefDolor');
          if (dolorEl && b.dolor_principal) {
            dolorEl.textContent = '🎯 Dolor detectado: ' + b.dolor_principal;
            dolorEl.hidden = false;
          }
        } catch (err) {
          setError('Error de red: ' + err.message);
        } finally {
          btnSugerir.disabled = false;
          btnSugerir.textContent = txtOrig;
        }
      });
    }

    // ── Paso 1 → proponer ángulos ──────────────────────────────────────────
    if (btnAngulos) {
      btnAngulos.addEventListener('click', async () => {
        setError('');
        if (!validarBase()) return;
        if (!(await guardarKeyEscrita())) return;

        document.getElementById('iaStep2Msg').textContent = 'Claude está pensando los ángulos...';
        document.getElementById('iaStep2Sub').textContent = 'Unos 10 segundos.';
        showStep(2);

        try {
          const data = await post('generarAngulosIA', Object.assign(baseFields(), briefFields()));
          if (!data.ok) {
            showStep(1);
            setError(data.error === 'no_key' ? 'Primero ingresa tu API key de Claude.' : (data.error || 'Error. Intenta de nuevo.'));
            return;
          }
          _angulos = data.angulos || [];
          renderAngulos();
          showStep('A');
        } catch (err) {
          showStep(1);
          setError('Error de red: ' + err.message);
        }
      });
    }

    // ── Render de las tarjetas de ángulo ───────────────────────────────────
    const renderAngulos = () => {
      const list = document.getElementById('iaAngulosList');
      list.innerHTML = '';
      _angulos.forEach((a, i) => {
        const lbl = document.createElement('label');
        lbl.className = 'ia-angulo';
        lbl.innerHTML =
          '<input type="radio" name="ia_angulo_pick" value="' + i + '"' + (i === 0 ? ' checked' : '') + '>' +
          '<span class="ia-angulo__body">' +
            '<span class="ia-angulo__headline">' + escapeHtml(a.headline || '') + '</span>' +
            '<span class="ia-angulo__line"><b>Dolor:</b> ' + escapeHtml(a.dolor || '') + '</span>' +
            '<span class="ia-angulo__line"><b>Idea:</b> ' + escapeHtml(a.gran_idea || '') + '</span>' +
            (a.a_quien || a.por_que
              ? '<span class="ia-angulo__meta">' + escapeHtml([a.a_quien, a.por_que].filter(Boolean).join(' · ')) + '</span>'
              : '') +
          '</span>';
        list.appendChild(lbl);
      });
      list.querySelectorAll('input[name="ia_angulo_pick"]').forEach((r) => {
        r.addEventListener('change', () => cargarAjuste(parseInt(r.value, 10)));
      });
      cargarAjuste(0);
    };

    const cargarAjuste = (idx) => {
      const a = _angulos[idx] || {};
      document.getElementById('iaAngDolor').value    = a.dolor || '';
      document.getElementById('iaAngIdea').value     = a.gran_idea || '';
      document.getElementById('iaAngHeadline').value = a.headline || '';
      document.getElementById('iaAngQuien').value    = a.a_quien || '';
    };

    if (btnVolver) btnVolver.addEventListener('click', () => { setAngError(''); showStep(1); });

    // ── Genera la landing en 3 lotes cortos, rellenando a medida ───────────
    const LOTES = [
      { id: 'gancho', label: 'gancho (hero, beneficios, por qué)' },
      { id: 'prueba', label: 'prueba social (comparativa, testimonios, WhatsApp)' },
      { id: 'cierre', label: 'cierre (FAQ, autoridad, botones)' },
    ];

    const generarPorLotes = async (extra, backStep, showErr) => {
      document.getElementById('iaStep2Msg').textContent = 'Claude está escribiendo tu landing...';
      showStep(2);

      const merged = {};
      let filledTotal = 0;
      let ancla = Object.assign({}, extra); // tras el 1er lote, fija el dolor/ángulo

      for (let i = 0; i < LOTES.length; i++) {
        document.getElementById('iaStep2Sub').textContent =
          'Lote ' + (i + 1) + ' de ' + LOTES.length + ': ' + LOTES[i].label + '…';
        let data;
        try {
          data = await post('generarConIA', Object.assign({}, baseFields(), briefFields(), ancla, { lote: LOTES[i].id }));
        } catch (err) {
          showStep(backStep);
          showErr('Error de red en el lote "' + LOTES[i].id + '": ' + err.message +
                  (filledTotal ? ' (se alcanzaron a llenar ' + filledTotal + ' campos)' : ''));
          return;
        }
        if (!data.ok) {
          showStep(backStep);
          showErr(data.error === 'no_key'
            ? 'Primero ingresa tu API key de Claude.'
            : ('Falló el lote "' + LOTES[i].id + '": ' + (data.error || 'error') +
               (filledTotal ? '. Los lotes anteriores sí se llenaron.' : '')));
          return;
        }
        Object.assign(merged, data.fields || {});
        filledTotal += rellenar(data.fields || {});

        // Coherencia entre lotes: si no había ángulo fijado, el 1er lote lo fija.
        const f = data.fields || {};
        if (!ancla.angulo_dolor && (f._dolor || f._angulo)) {
          ancla = Object.assign({}, ancla, {
            angulo_dolor:     f._dolor  || '',
            angulo_gran_idea: f._angulo || '',
          });
        }
      }

      irAExito(merged, filledTotal);
    };

    // ── Generar landing con el ángulo elegido ──────────────────────────────
    if (btnGenAng) {
      btnGenAng.addEventListener('click', () => {
        setAngError('');
        if (!val('iaAngDolor') && !val('iaAngHeadline')) {
          setAngError('Elige o escribe un ángulo antes de generar.');
          return;
        }
        generarPorLotes({
          angulo_dolor:     val('iaAngDolor'),
          angulo_gran_idea: val('iaAngIdea'),
          angulo_headline:  val('iaAngHeadline'),
          angulo_a_quien:   val('iaAngQuien'),
        }, 'A', setAngError);
      });
    }

    // ── Generar directo, sin elegir ángulo ─────────────────────────────────
    if (btnDirecto) {
      btnDirecto.addEventListener('click', async () => {
        setError('');
        if (!validarBase()) return;
        if (!(await guardarKeyEscrita())) return;
        generarPorLotes({}, 1, setError);
      });
    }
  })();
  </script>

  <!-- ===== MODAL: GENERACIÓN DE TEXTO POR SECCIÓN ========================= -->
  <div id="iaTxtOverlay" class="ia-img-overlay" style="display:none;" aria-hidden="true">
  <div id="iaTxtPanel" class="ia-img-panel ia-txt-panel" role="dialog" aria-labelledby="iaTxtPanelTitle" aria-modal="true">
    <div class="ia-img-panel__header">
      <span id="iaTxtPanelTitle">✨ Generar texto</span>
      <button type="button" id="iaTxtClose" class="ia-img-panel__close" aria-label="Cerrar">✕</button>
    </div>
    <div class="ia-img-panel__body">

      <div class="ia-img-panel__field">
        <label for="iaTxtExtra">Instrucciones adicionales <span style="font-weight:400;text-transform:none;opacity:.7">(opcional)</span></label>
        <textarea id="iaTxtExtra" rows="2"
          placeholder="Ej: tono más formal, enfócate en durabilidad, menciona el color dorado..."></textarea>
      </div>

      <div id="iaTxtError" class="ia-img-error" style="display:none;"></div>

      <button type="button" id="iaTxtGenerar" class="ia-img-btn-generar">✨ Generar esta sección</button>

      <div id="iaTxtLoading" style="display:none; text-align:center; padding:16px 0;">
        <div class="ia-spinner"></div>
        <p style="margin:10px 0 4px; font-size:13px; font-weight:600;">Escribiendo...</p>
        <small style="color:var(--tx-secondary,#888);">Claude está generando el copy para esta sección</small>
      </div>

    </div>
  </div>
  </div><!-- /#iaTxtOverlay -->

  <script>
  (() => {
    const BASE    = '<?= BASE_URL ?>';
    const overlay = document.getElementById('iaTxtOverlay');
    const panel   = document.getElementById('iaTxtPanel');
    const titulo  = document.getElementById('iaTxtPanelTitle');

    // Map: section-block id → section key for backend
    const sectionKeyMap = {
      'sec-hero':            'hero',
      'sec-beneficios':      'beneficios',
      'sec-caracteristicas': 'caracteristicas',
      'sec-contador':        'countdown',
      'sec-porque':          'porque',
      'sec-comparison':      'comparativa',
      'sec-testimonios':     'testimonios',
      'sec-paraquien':       'paraquien',
      'sec-wa':              'wa',
      'sec-faq':             'faq',
      'sec-autoridad':       'autoridad',
      'sec-ctas':            'ctas',
    };
    const sectionLabels = {
      'hero':'Hero', 'beneficios':'Beneficios', 'caracteristicas':'Características',
      'countdown':'Contador/Oferta', 'porque':'¿Por qué?', 'comparativa':'Tabla comparativa',
      'testimonios':'Testimonios', 'paraquien':'¿Para quién es?', 'wa':'Testimonios WhatsApp',
      'faq':'Preguntas frecuentes', 'autoridad':'Autoridad', 'ctas':'CTAs',
    };

    let currentSection = null;

    // ── Inject "✨ IA" buttons after ux-improvements.js rebuilds the DOM ────
    // ux-improvements.js dispatches 'ux:sections-ready' once initCollapsibleSections()
    // completes. At that point .sec-toggle-title elements exist.
    function injectTxtButtons() {
      Object.keys(sectionKeyMap).forEach(blockId => {
        const block = document.getElementById(blockId);
        if (!block) return;
        if (block.querySelector('.ia-txt-trigger')) return; // already injected

        const titleSpan = block.querySelector('.sec-toggle-title');
        if (!titleSpan) return;

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'ia-txt-trigger';
        btn.innerHTML = '<span aria-hidden="true">✨</span> IA';
        btn.title = 'Generar texto de esta sección con IA';
        btn.setAttribute('aria-label', 'Generar texto con IA para ' + (sectionLabels[sectionKeyMap[blockId]] || blockId));
        btn.dataset.section = sectionKeyMap[blockId];
        titleSpan.insertAdjacentElement('afterend', btn);

        btn.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          openTxtPanel(btn, sectionKeyMap[blockId]);
        });
      });
    }

    document.addEventListener('ux:sections-ready', injectTxtButtons, { once: true });

    // ── Open panel ───────────────────────────────────────────────────────────
    let _txtPanelOpener = null;
    function openTxtPanel(triggerBtn, section) {
      _txtPanelOpener = triggerBtn;
      currentSection = section;
      titulo.textContent = '✨ Generar — ' + (sectionLabels[section] || section);
      setTxtError('');
      setTxtLoading(false);
      document.getElementById('iaTxtExtra').value = '';

      overlay.style.display = 'flex';
      overlay.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';

      const firstFocusable = panel.querySelector('textarea, button');
      if (firstFocusable) setTimeout(() => firstFocusable.focus(), 50);
    }

    // ── Close ────────────────────────────────────────────────────────────────
    function closeTxtPanel() {
      overlay.style.display = 'none';
      overlay.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
      if (_txtPanelOpener) { _txtPanelOpener.focus(); _txtPanelOpener = null; }
    }
    document.getElementById('iaTxtClose').addEventListener('click', closeTxtPanel);
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && overlay.style.display !== 'none') closeTxtPanel(); });
    overlay.addEventListener('click', e => { if (e.target === overlay) closeTxtPanel(); });

    // ── Helpers ──────────────────────────────────────────────────────────────
    function setTxtError(msg) {
      const el = document.getElementById('iaTxtError');
      el.textContent = msg; el.style.display = msg ? 'block' : 'none';
    }
    function setTxtLoading(on) {
      document.getElementById('iaTxtLoading').style.display = on ? 'block' : 'none';
      document.getElementById('iaTxtGenerar').style.display = on ? 'none'  : 'block';
    }
    function getCtx() {
      return {
        nombre:      (document.querySelector('[name="hero_title"]')?.value
                   || <?= json_encode($producto['nombre'] ?? '') ?>).trim(),
        descripcion: (document.querySelector('[name="hero_subtitle"]')?.value
                   || document.querySelector('[name="porque_text"]')?.value || '').trim(),
      };
    }

    // ── Fill form fields from Claude response ─────────────────────────────────
    function fillFields(fields) {
      let filled = 0;
      const marcar = (el, v) => { if (window.__iaMarcarOriginal) window.__iaMarcarOriginal(el, v); };
      Object.entries(fields).forEach(([key, val]) => {
        if (!val || key.charAt(0) === '_') return;
        const input = document.querySelector(`input[name="${key}"]`);
        if (input && !['hidden','file','checkbox','radio'].includes(input.type)) {
          input.value = val;
          input.dispatchEvent(new Event('input', { bubbles: true }));
          marcar(input, val);
          filled++; return;
        }
        const ta = document.querySelector(`textarea[name="${key}"]`);
        if (ta) {
          marcar(ta, val);
          ta.value = val;
          ta.dispatchEvent(new Event('input', { bubbles: true }));
          filled++;
        }
      });
      return filled;
    }

    // ── Generate ─────────────────────────────────────────────────────────────
    document.getElementById('iaTxtGenerar').addEventListener('click', async () => {
      setTxtError('');
      const ctx   = getCtx();
      const extra = document.getElementById('iaTxtExtra').value.trim();

      if (!ctx.nombre) { setTxtError('Agrega el nombre del producto en el campo "Título principal" del Hero primero.'); return; }

      setTxtLoading(true);
      try {
        const body = new URLSearchParams({
          producto_id: <?= json_encode((string)$producto_id) ?>,
          seccion: currentSection, nombre: ctx.nombre,
          descripcion: ctx.descripcion, extra,
          csrf_token: window.__CSRF__ || '',
        });
        const res  = await fetch(BASE + '/AdminLanding/generarSeccionIA', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body,
        });
        const data = await res.json();
        setTxtLoading(false);

        if (!data.ok) {
          setTxtError(data.error === 'no_key'
            ? 'Configura primero la API key de Claude en "✨ Generar con IA".'
            : (data.error || 'Error generando el texto.'));
          return;
        }

        const filled = fillFields(data.fields || {});

        // Brief success flash then close
        const btn = document.getElementById('iaTxtGenerar');
        btn.textContent = `✅ ${filled} campos aplicados`;
        btn.style.background = '#22c55e';
        btn.style.display = 'block';
        setTimeout(() => {
          btn.textContent = '✨ Generar esta sección';
          btn.style.background = '';
          closeTxtPanel();
        }, 1800);

      } catch(e) { setTxtLoading(false); setTxtError('Error de red: ' + e.message); }
    });
  })();
  </script>

  <!-- ===== REESCRIBIR UN CAMPO CON INSTRUCCIÓN ============================ -->
  <div id="iaCampoPanel" class="ia-campo-panel" hidden>
    <div class="ia-campo-panel__label" id="iaCampoNombre">campo</div>
    <textarea id="iaCampoInstr" rows="2"
      placeholder="¿Qué le cambio? Ej: más corto · más directo · que haga una pregunta desde el dolor · menos cursi"></textarea>
    <label class="ia-campo-panel__opt">
      <input type="checkbox" id="iaCampoTres"> Mostrarme 3 opciones para elegir
    </label>
    <div class="ia-campo-panel__row">
      <button type="button" id="iaCampoGen" class="ia-campo-panel__go">✨ Reescribir</button>
      <button type="button" id="iaCampoCancel" class="ia-campo-panel__x">Cancelar</button>
    </div>
    <div class="ia-campo-variantes" id="iaCampoVariantes" hidden></div>
    <div class="ia-campo-panel__err" id="iaCampoErr" hidden></div>
  </div>

  <script>
  (() => {
    const BASE  = '<?= BASE_URL ?>';
    const PID   = <?= json_encode((string)$producto_id) ?>;
    const form  = document.getElementById('formLanding');
    if (!form) return;

    const panel  = document.getElementById('iaCampoPanel');
    const taI    = document.getElementById('iaCampoInstr');
    const errEl  = document.getElementById('iaCampoErr');
    const nameEl = document.getElementById('iaCampoNombre');
    const btnGo  = document.getElementById('iaCampoGen');
    const btnX   = document.getElementById('iaCampoCancel');
    const chkTres  = document.getElementById('iaCampoTres');
    const varsEl   = document.getElementById('iaCampoVariantes');
    let target   = null;

    // Feature 7: al rellenar un campo con IA se guarda el valor original;
    // si luego el dueño lo edita a mano, se registra la corrección.
    const marcarIA = (el, v) => { if (el) el.dataset.iaOriginal = v; };
    window.__iaMarcarOriginal = marcarIA;

    form.addEventListener('change', (e) => {
      const el = e.target;
      if (!el || !el.name || !('iaOriginal' in el.dataset)) return;
      const nuevo = (el.value || '').trim();
      const prev  = (el.dataset.iaOriginal || '').trim();
      if (!nuevo || nuevo === prev) return;
      el.dataset.iaOriginal = nuevo; // evita reenviar el mismo cambio
      try {
        fetch(BASE + '/AdminLanding/registrarEdicionIA', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({
            csrf_token: window.__CSRF__ || '', campo: el.name, ia: prev, humano: nuevo,
          }),
          keepalive: true,
        });
      } catch (_) {}
    }, true);

    const SKIP = /(_path|_actual|_id$|hex|color|_img|pixel|clarity|phone|section_order|_minutes|_stock|combo_price|_icon$|_rating$|_years$|_deliveries$|badge|^cv\d|_type$|theme)/;

    const elegible = (el) => {
      if (!el.name || SKIP.test(el.name)) return false;
      if (el.tagName === 'TEXTAREA') return true;
      return el.tagName === 'INPUT' && (el.type === 'text' || el.type === '');
    };

    const etiqueta = (el) => {
      const l = el.labels && el.labels[0];
      return (l ? l.textContent.replace(/\s+/g, ' ').trim().slice(0, 40) : '')
        || el.getAttribute('aria-label') || el.name;
    };

    const inject = () => {
      form.querySelectorAll('input, textarea').forEach((el) => {
        if (!elegible(el)) return;
        const grp = el.closest('.admin-form-group') || el.parentElement;
        if (!grp || grp.querySelector('.ia-campo-trigger')) return;
        grp.classList.add('has-ia-campo');
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'ia-campo-trigger';
        b.textContent = '✨';
        b.title = 'Reescribir este texto con IA';
        b.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); abrir(el, b); });
        grp.appendChild(b);
      });
    };

    const cerrar = () => { panel.hidden = true; target = null; };

    let _anchorRect = null;
    const reclamar = () => {
      const r = _anchorRect;
      if (!r) return;
      panel.style.top  = Math.max(8, Math.min(window.innerHeight - panel.offsetHeight - 8, r.bottom + 6)) + 'px';
      panel.style.left = Math.max(8, Math.min(window.innerWidth - panel.offsetWidth - 8, r.right - panel.offsetWidth)) + 'px';
    };

    const aplicar = (v) => {
      target.value = v;
      target.dispatchEvent(new Event('input', { bubbles: true }));
      marcarIA(target, v);
      target.classList.add('ia-campo-flash');
      const el = target;
      setTimeout(() => el.classList.remove('ia-campo-flash'), 1200);
      cerrar();
    };

    const abrir = (el, anchor) => {
      target = el;
      nameEl.textContent = etiqueta(el);
      taI.value = '';
      errEl.hidden = true;
      varsEl.hidden = true;
      varsEl.innerHTML = '';
      chkTres.checked = false;
      panel.hidden = false;
      _anchorRect = anchor.getBoundingClientRect();
      reclamar();
      taI.focus();
    };

    btnX.addEventListener('click', cerrar);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !panel.hidden) cerrar(); });
    document.addEventListener('click', (e) => {
      if (!panel.hidden && !panel.contains(e.target) && !e.target.classList.contains('ia-campo-trigger')) cerrar();
    });
    window.addEventListener('scroll', () => { if (!panel.hidden) cerrar(); }, true);

    btnGo.addEventListener('click', async () => {
      if (!target) return;
      errEl.hidden = true;
      varsEl.hidden = true;
      varsEl.innerHTML = '';
      const tres = chkTres.checked;
      const orig = btnGo.textContent;
      btnGo.disabled = true;
      btnGo.textContent = tres ? '✨ Buscando opciones…' : '✨ Escribiendo…';
      try {
        const body = new URLSearchParams({
          csrf_token:   window.__CSRF__ || '',
          producto_id:  PID,
          campo:        target.name,
          valor_actual: target.value || '',
          instruccion:  taI.value.trim(),
          n:            tres ? '3' : '1',
          nombre:       (document.querySelector('[name="hero_title"]') || {}).value || '',
          descripcion:  (document.querySelector('[name="hero_subtitle"]') || {}).value || '',
        });
        const res = await fetch(BASE + '/AdminLanding/regenerarCampoIA', {
          method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body,
        });
        const d = await res.json();
        if (!d.ok) {
          errEl.textContent = d.error === 'no_key'
            ? 'Configura primero la API key de Claude en "✨ Generar con IA".'
            : (d.error || 'No se pudo. Intenta de nuevo.');
          errEl.hidden = false;
          return;
        }
        if (tres) {
          (d.variantes || []).forEach((v) => {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'ia-campo-variante';
            b.textContent = v;
            b.addEventListener('click', () => aplicar(v));
            varsEl.appendChild(b);
          });
          varsEl.hidden = false;
          reclamar();
          return;
        }
        aplicar(d.valor);
      } catch (e) {
        errEl.textContent = 'Error de red: ' + e.message;
        errEl.hidden = false;
      } finally {
        btnGo.disabled = false;
        btnGo.textContent = orig;
      }
    });

    document.addEventListener('ux:sections-ready', inject, { once: true });
    setTimeout(inject, 1600); // respaldo si el evento ya ocurrió
  })();
  </script>

  <!-- ===== MODAL: GENERACIÓN DE IMÁGENES IA ================================ -->
  <?php $tieneReplicateKey = $tiene_replicate_key ?? false; ?>

  <div id="iaImgOverlay" class="ia-img-overlay" style="display:none;" aria-hidden="true">
  <div id="iaImgPanel" class="ia-img-panel" role="dialog" aria-labelledby="iaImgPanelTitle" aria-modal="true">
    <div class="ia-img-panel__header">
      <span id="iaImgPanelTitle">✨ Generar imagen</span>
      <button type="button" id="iaImgClose" class="ia-img-panel__close" aria-label="Cerrar">✕</button>
    </div>

    <div class="ia-img-panel__body">

      <!-- Replicate key (solo si no está guardada) -->
      <?php if (!$tieneReplicateKey): ?>
      <div class="ia-img-key-section" id="iaImgKeySection">
        <div class="ia-img-key-label">
          <i class="fas fa-key" aria-hidden="true"></i> API Key de Replicate
          <a href="https://replicate.com/account/api-tokens" target="_blank" rel="noopener">Obtener →</a>
        </div>
        <input type="password" id="ia_replicate_key" placeholder="r8_...">
        <small>Solo la ingresas una vez. Se guarda en tu BD.</small>
      </div>
      <?php else: ?>
      <div id="iaImgKeySection" style="display:none;">
        <input type="password" id="ia_replicate_key" placeholder="r8_...">
      </div>
      <div class="ia-img-key-saved">
        <i class="fas fa-circle-check" aria-hidden="true"></i> Replicate configurado
        <button type="button" class="ia-modal__key-change" id="btnCambiarReplicate">Cambiar</button>
      </div>
      <?php endif; ?>

      <!-- Referencia global del producto -->
      <div class="ia-ref-section">
        <div class="ia-ref-header">
          <span><i class="fas fa-image"></i> Referencia global del producto</span>
          <span class="ia-ref-badge" id="iaRefBadge" style="display:none;">✅ Cargada</span>
        </div>
        <div class="ia-ref-body" id="iaRefBody">
          <input type="file" id="iaRefFile" accept="image/jpeg,image/png,image/webp"
                 data-no-dropzone style="font-size:12px; width:100%;">
          <small>Foto base de tu producto — aplica a <strong>todas</strong> las secciones.</small>
        </div>
        <div id="iaRefPreview" style="display:none; margin-top:8px;">
          <img id="iaRefPreviewImg" alt="Referencia global"
               style="width:100%; max-height:100px; object-fit:contain; border-radius:6px; border:1px solid var(--bd-default,#2a2a3a);">
          <button type="button" id="iaRefClear" class="ia-ref-clear">✕ Quitar referencia global</button>
        </div>
      </div>

      <!-- Referencia específica de esta sección -->
      <div class="ia-ref-section ia-ref-section--sec">
        <div class="ia-ref-header">
          <span><i class="fas fa-crop-alt"></i> Referencia de <em id="iaRefSecLabel">esta sección</em></span>
          <span class="ia-ref-badge" id="iaRefSecBadge" style="display:none;">✅ Cargada</span>
        </div>
        <div class="ia-ref-body" id="iaRefSecBody">
          <input type="file" id="iaRefSecFile" accept="image/jpeg,image/png,image/webp"
                 data-no-dropzone style="font-size:12px; width:100%;">
          <small>Foto específica para esta sección (ej: medidas, detalle, ángulo). <strong>Sobreescribe</strong> la global solo aquí. Se resetea al cambiar de sección.</small>
        </div>
        <div id="iaRefSecPreview" style="display:none; margin-top:8px;">
          <img id="iaRefSecPreviewImg" alt="Referencia sección"
               style="width:100%; max-height:100px; object-fit:contain; border-radius:6px; border:1px solid rgba(93,104,255,.4);">
          <button type="button" id="iaRefSecClear" class="ia-ref-clear">✕ Quitar referencia de sección</button>
        </div>
      </div>

      <!-- Fidelidad (aplica a cualquier referencia activa) -->
      <div class="ia-ref-strength" id="iaRefStrength">
        <label for="iaStrengthSelect">Fidelidad a la referencia</label>
        <select id="iaStrengthSelect">
          <option value="0.60">Alta — muy parecida a la foto</option>
          <option value="0.75" selected>Balanceada — respeta el producto + mejora</option>
          <option value="0.90">Creativa — inspirada en la foto</option>
        </select>
      </div>

      <!-- Prompt -->
      <div class="ia-img-panel__field">
        <div class="ia-img-panel__field-header">
          <label for="iaImgPrompt">Prompt de imagen</label>
          <button type="button" id="iaImgSugerir" class="ia-img-sugerir">✨ Sugerir con IA</button>
        </div>
        <textarea id="iaImgPrompt" rows="4"
          placeholder="Sin referencia: describe la imagen completa en inglés.&#10;Con referencia: escribe qué hacer con el producto.&#10;Ej: Place this watch on a marble surface with soft golden lighting"></textarea>
      </div>

      <div id="iaImgError" class="ia-img-error" style="display:none;"></div>

      <button type="button" id="iaImgGenerar" class="ia-img-btn-generar">🎨 Generar imagen</button>

      <!-- Loading -->
      <div id="iaImgLoading" style="display:none; text-align:center; padding:20px 0;">
        <div class="ia-spinner"></div>
        <p style="margin:12px 0 4px; font-size:14px; font-weight:600;">Generando imagen...</p>
        <small style="color:var(--tx-secondary,#888);">Flux 1.1 Pro · ~15-30 segundos · WebP optimizado</small>
      </div>

      <!-- Preview + acciones -->
      <div id="iaImgPreviewWrap" style="display:none;">
        <img id="iaImgPreviewImg" alt="Imagen generada"
             style="width:100%; border-radius:8px; margin-top:12px; border:1px solid var(--bd-default,#2a2a3a);">
        <div class="ia-img-preview-actions">
          <button type="button" id="iaImgUsar" class="ia-img-btn-usar">✅ Usar esta imagen</button>
          <button type="button" id="iaImgRegen" class="ia-img-btn-regen">🔄 Regenerar</button>
        </div>
      </div>

    </div>
  </div>
  </div><!-- /#iaImgOverlay -->

  <script>
  (() => {
    const BASE    = '<?= BASE_URL ?>';
    const overlay = document.getElementById('iaImgOverlay');
    const panel   = document.getElementById('iaImgPanel');
    const titulo  = document.getElementById('iaImgPanelTitle');

    // Map: input name → section ID
    const sectionMap = {
      'hero_media_file':             'hero',
      'benefits_media_file':         'benefits',
      'benefit_1_img_file':          'benefit_1',
      'benefit_2_img_file':          'benefit_2',
      'benefit_3_img_file':          'benefit_3',
      'benefit_4_img_file':          'benefit_4',
      'gallery_1_file':              'gallery_1',
      'gallery_2_file':              'gallery_2',
      'gallery_3_file':              'gallery_3',
      'gallery_4_file':              'gallery_4',
      'caract1_media_file':          'caract1',
      'caract2_media_file':          'caract2',
      'caract3_media_file':          'caract3',
      'caract4_media_file':          'caract4',
      'porque_media_file':           'porque',
      'comparison_img_without_file': 'comparison_without',
      'comparison_img_with_file':    'comparison_with',
      'test1_banner_file':           'test1_banner',
      'test2_banner_file':           'test2_banner',
      'test3_banner_file':           'test3_banner',
      // Variantes de color: cv1_g1_file … cv4_g4_file
      ...Object.fromEntries([1,2,3,4].flatMap(ci => [1,2,3,4].map(gi => [`cv${ci}_g${gi}_file`, `cv${ci}_g${gi}`]))),
    };
    // Map: section ID → hidden _actual field name
    const actualMap = {
      'hero':               'hero_media_path_actual',
      'benefits':           'benefits_media_path_actual',
      'benefit_1':          'benefit_1_img_actual',
      'benefit_2':          'benefit_2_img_actual',
      'benefit_3':          'benefit_3_img_actual',
      'benefit_4':          'benefit_4_img_actual',
      'gallery_1':          'gallery_1_path_actual',
      'gallery_2':          'gallery_2_path_actual',
      'gallery_3':          'gallery_3_path_actual',
      'gallery_4':          'gallery_4_path_actual',
      'caract1':            'caract1_media_path_actual',
      'caract2':            'caract2_media_path_actual',
      'caract3':            'caract3_media_path_actual',
      'caract4':            'caract4_media_path_actual',
      'porque':             'porque_media_path_actual',
      'comparison_without': 'comparison_img_without_path_actual',
      'comparison_with':    'comparison_img_with_path_actual',
      'test1_banner':       'test1_banner_path_actual',
      'test2_banner':       'test2_banner_path_actual',
      'test3_banner':       'test3_banner_path_actual',
      // Variantes de color
      ...Object.fromEntries([1,2,3,4].flatMap(ci => [1,2,3,4].map(gi => [`cv${ci}_g${gi}`, `cv${ci}_g${gi}_actual`]))),
    };
    const sectionLabels = {
      'hero': 'Hero', 'benefits': 'Beneficios',
      'benefit_1': 'Beneficio 1', 'benefit_2': 'Beneficio 2',
      'benefit_3': 'Beneficio 3', 'benefit_4': 'Beneficio 4',
      'gallery_1': 'Galería 1', 'gallery_2': 'Galería 2',
      'gallery_3': 'Galería 3', 'gallery_4': 'Galería 4',
      'caract1': 'Característica 1', 'caract2': 'Característica 2',
      'caract3': 'Característica 3', 'caract4': 'Característica 4',
      'porque': '¿Por qué?',
      'comparison_without': 'Comparativa — Sin', 'comparison_with': 'Comparativa — Con',
      'test1_banner': 'Banner Testimonio 1', 'test2_banner': 'Banner Testimonio 2',
      'test3_banner': 'Banner Testimonio 3',
      // Variantes de color — el label se sobreescribe dinámicamente en openPanel()
      ...Object.fromEntries([1,2,3,4].flatMap(ci => [1,2,3,4].map(gi => [`cv${ci}_g${gi}`, `Color ${ci} — Foto ${gi}`]))),
    };

    let currentSection  = null;
    let currentFileInput = null;

    // ── Inject "✨ IA" buttons next to matching file inputs ──────────────────
    Object.keys(sectionMap).forEach(inputName => {
      const input = document.querySelector(`input[name="${inputName}"]`);
      if (!input) return;

      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'ia-img-trigger';
      btn.innerHTML = '<span aria-hidden="true">✨</span> IA';
      btn.title = 'Generar imagen con IA';
      btn.setAttribute('aria-label', 'Generar imagen con IA para ' + (sectionLabels[sectionMap[inputName]] || inputName));
      btn.dataset.section = sectionMap[inputName];
      btn.dataset.inputName = inputName;
      // Si el input ya fue envuelto por initDropzones(), insertar después del wrapper
      const insertTarget = input.closest('.ux-dropzone') || input;
      insertTarget.insertAdjacentElement('afterend', btn);

      btn.addEventListener('click', (e) => {
        e.preventDefault();
        openPanel(btn, sectionMap[inputName], input);
      });
    });

    // ── Open modal ───────────────────────────────────────────────────────────
    let _imgPanelOpener = null;
    function openPanel(triggerBtn, section, fileInput) {
      _imgPanelOpener  = triggerBtn;
      currentSection   = section;
      currentFileInput = fileInput;

      // Para variantes de color, mostrar nombre real del color en el título
      let label = sectionLabels[section] || section;
      const cvMatch = section.match(/^cv(\d)_g(\d)$/);
      if (cvMatch) {
        const ci = cvMatch[1];
        const gi = cvMatch[2];
        const colorName = document.querySelector(`[name="cv${ci}_name"]`)?.value?.trim() || `Color ${ci}`;
        label = `${colorName} — Foto ${gi}`;
      }
      titulo.textContent = '✨ Generar imagen — ' + label;

      setError('');
      setLoading(false);
      showPreview(null);
      document.getElementById('iaImgPrompt').value = '';

      overlay.style.display = 'flex';
      overlay.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';

      const firstFocusable = panel.querySelector('textarea, button');
      if (firstFocusable) setTimeout(() => firstFocusable.focus(), 50);
    }

    // ── Close ────────────────────────────────────────────────────────────────
    function closePanel() {
      overlay.style.display = 'none';
      overlay.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
      if (_imgPanelOpener) { _imgPanelOpener.focus(); _imgPanelOpener = null; }
    }
    document.getElementById('iaImgClose').addEventListener('click', closePanel);
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closePanel(); });
    overlay.addEventListener('click', e => { if (e.target === overlay) closePanel(); });

    // ── Cambiar key Replicate ────────────────────────────────────────────────
    const btnCambiarR = document.getElementById('btnCambiarReplicate');
    const keySectionEl = document.getElementById('iaImgKeySection');
    if (btnCambiarR && keySectionEl) {
      btnCambiarR.addEventListener('click', () => {
        keySectionEl.style.display = keySectionEl.style.display === 'none' ? '' : 'none';
      });
    }

    // ── Helpers UI ───────────────────────────────────────────────────────────
    function setError(msg) {
      const el = document.getElementById('iaImgError');
      el.textContent = msg;
      el.style.display = msg ? 'block' : 'none';
    }
    function setLoading(on) {
      document.getElementById('iaImgLoading').style.display  = on ? 'block' : 'none';
      document.getElementById('iaImgGenerar').style.display  = on ? 'none'  : 'block';
      document.getElementById('iaImgSugerir').disabled = on;
    }
    function showPreview(url) {
      const wrap = document.getElementById('iaImgPreviewWrap');
      if (!url) { wrap.style.display = 'none'; return; }
      const img = document.getElementById('iaImgPreviewImg');
      img.onerror = () => { wrap.style.display = 'none'; setError('La imagen se generó pero no pudo cargarse. Revisa la consola.'); };
      img.src = url;
      wrap.style.display = 'block';
    }

    // ── Get product context from form ────────────────────────────────────────
    function getProductoCtx() {
      return {
        producto:    (document.querySelector('[name="hero_title"]')?.value || <?= json_encode($producto['nombre'] ?? '') ?>).trim(),
        descripcion: (document.querySelector('[name="hero_subtitle"]')?.value || document.querySelector('[name="porque_text"]')?.value || '').trim(),
      };
    }

    // ── Extrae título y texto del campo de la sección activa ────────────────
    function getSectionContent(section) {
      const v = n => document.querySelector(`[name="${n}"]`)?.value?.trim() || '';

      // Secciones con título + texto propios
      const simple = {
        'hero':    { titulo: v('hero_title'),    texto: v('hero_subtitle') },
        'porque':  { titulo: v('porque_title'),  texto: v('porque_text')  },
        'caract1': { titulo: v('caract1_title'), texto: v('caract1_text') },
        'caract2': { titulo: v('caract2_title'), texto: v('caract2_text') },
        'caract3': { titulo: v('caract3_title'), texto: v('caract3_text') },
        'caract4': { titulo: v('caract4_title'), texto: v('caract4_text') },
      };
      if (simple[section]) return simple[section];

      // Benefits: texto del beneficio como contexto
      const benefitNum = { 'benefit_1':'1','benefit_2':'2','benefit_3':'3','benefit_4':'4' }[section];
      if (benefitNum) {
        return { titulo: v('hero_title'), texto: v(`benefit_${benefitNum}`) };
      }

      // Galería: producto + hint de posición
      const galleryHints = {
        'gallery_1': 'Main product shot — full product, professional studio lighting, clean background',
        'gallery_2': 'Angle or detail shot — different perspective showing design quality',
        'gallery_3': 'Lifestyle — product being used in real-life context',
        'gallery_4': 'Packaging — product with its original box, case or accessories',
      };
      if (galleryHints[section]) {
        return { titulo: v('hero_title'), texto: galleryHints[section] };
      }

      // Testimonios: nombre, ciudad y texto del cliente
      const testNum = { 'test1_banner': '1', 'test2_banner': '2', 'test3_banner': '3' }[section];
      if (testNum) {
        const name = v(`test${testNum}_name`);
        const city = v(`test${testNum}_city`);
        const text = v(`test${testNum}_text`);
        return {
          titulo: name ? `Customer testimonial — ${name}${city ? ', ' + city : ''}` : 'Customer testimonial',
          texto:  text || '',
        };
      }

      // Comparativa: label de columna + filas
      if (section === 'comparison_without') {
        const label = v('comparison_label_without') || 'Without the product';
        const rows  = [1,2,3].map(i => v(`comparison_${i}_without`)).filter(Boolean).join('. ');
        return { titulo: label, texto: rows };
      }
      if (section === 'comparison_with') {
        const label = v('comparison_label_with') || 'With the product';
        const rows  = [1,2,3].map(i => v(`comparison_${i}_with`)).filter(Boolean).join('. ');
        return { titulo: label, texto: rows };
      }

      // Variantes de color — cv{ci}_g{gi}
      const cvMatch = section.match(/^cv(\d)_g(\d)$/);
      if (cvMatch) {
        const ci = cvMatch[1];
        const gi = parseInt(cvMatch[2], 10);
        const colorName = document.querySelector(`[name="cv${ci}_name"]`)?.value?.trim() || `Color ${ci}`;
        const shotHints = [
          'Main product shot — full product visible, clean background',
          'Different angle or detail — shows design and texture clearly',
          'Lifestyle — product in real-life context or being worn/used',
          'Flat lay or packaging — product with accessories, top-down view',
        ];
        return {
          titulo: `${v('hero_title')} — color: ${colorName}`,
          texto:  `${shotHints[gi - 1] || 'Product shot'} — color variant: ${colorName}`,
        };
      }

      return { titulo: '', texto: '' };
    }

    // ── Sugerir prompt con Claude ────────────────────────────────────────────
    document.getElementById('iaImgSugerir').addEventListener('click', async () => {
      setError('');
      const btn = document.getElementById('iaImgSugerir');
      const orig = btn.textContent;
      btn.textContent = '...'; btn.disabled = true;

      try {
        const ctx = getProductoCtx();
        const sec = getSectionContent(currentSection);
        const promptActual = document.getElementById('iaImgPrompt').value.trim();
        const body = new URLSearchParams({
          producto: ctx.producto, descripcion: ctx.descripcion,
          seccion: currentSection, prompt_actual: promptActual,
          seccion_titulo: sec.titulo, seccion_texto: sec.texto,
          csrf_token: window.__CSRF__ || '',
        });
        const res  = await fetch(BASE + '/AdminLanding/sugerirPrompt', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body });
        const data = await res.json();
        if (data.ok) {
          document.getElementById('iaImgPrompt').value = data.text;
        } else {
          setError(data.error === 'no_claude_key' ? 'Configura primero la API key de Claude en "✨ Generar con IA".' : (data.error || 'Error al sugerir prompt.'));
        }
      } catch(e) { setError('Error de red: ' + e.message); }
      finally { btn.textContent = orig; btn.disabled = false; }
    });

    // ── Referencias: global + por sección ────────────────────────────────────
    let referenciaGlobalUrl  = null;
    let referenciaSeccionUrl = null;

    async function uploadRef(file, onSuccess, onError, badgeEl) {
      badgeEl.textContent = '⏳ Subiendo...';
      badgeEl.style.display = 'inline';
      const fd = new FormData();
      fd.append('referencia', file);
      fd.append('csrf_token', window.__CSRF__ || '');
      try {
        const res  = await fetch(BASE + '/AdminLanding/subirReferencia', { method: 'POST', body: fd });
        const data = await res.json();
        if (!data.ok) { setError(data.error || 'Error subiendo imagen.'); badgeEl.style.display='none'; onError && onError(); return; }
        badgeEl.textContent = '✅ Cargada';
        onSuccess(data.url);
      } catch(e) { setError('Error: ' + e.message); badgeEl.style.display='none'; onError && onError(); }
    }

    // Global
    const refFile    = document.getElementById('iaRefFile');
    const refBadge   = document.getElementById('iaRefBadge');
    const refPreview = document.getElementById('iaRefPreview');
    const refBody    = document.getElementById('iaRefBody');

    refFile.addEventListener('change', async () => {
      const file = refFile.files[0];
      if (!file) return;
      await uploadRef(file, (url) => {
        referenciaGlobalUrl = url;
        document.getElementById('iaRefPreviewImg').src = url;
        refPreview.style.display = 'block';
        refBody.style.display    = 'none';
      }, null, refBadge);
    });
    document.getElementById('iaRefClear').addEventListener('click', () => {
      referenciaGlobalUrl = null;
      refFile.value = '';
      refPreview.style.display = 'none';
      refBody.style.display    = '';
      refBadge.style.display   = 'none';
    });

    // Por sección
    const refSecFile    = document.getElementById('iaRefSecFile');
    const refSecBadge   = document.getElementById('iaRefSecBadge');
    const refSecPreview = document.getElementById('iaRefSecPreview');
    const refSecBody    = document.getElementById('iaRefSecBody');

    refSecFile.addEventListener('change', async () => {
      const file = refSecFile.files[0];
      if (!file) return;
      await uploadRef(file, (url) => {
        referenciaSeccionUrl = url;
        document.getElementById('iaRefSecPreviewImg').src = url;
        refSecPreview.style.display = 'block';
        refSecBody.style.display    = 'none';
      }, null, refSecBadge);
    });
    document.getElementById('iaRefSecClear').addEventListener('click', () => {
      referenciaSeccionUrl = null;
      refSecFile.value = '';
      refSecPreview.style.display = 'none';
      refSecBody.style.display    = '';
      refSecBadge.style.display   = 'none';
    });

    // Resetear referencia de sección al abrir otro panel
    const _origOpenPanel = openPanel;
    openPanel = function(triggerBtn, section, fileInput) {
      referenciaSeccionUrl = null;
      refSecFile.value = '';
      refSecPreview.style.display = 'none';
      refSecBody.style.display    = '';
      refSecBadge.style.display   = 'none';
      document.getElementById('iaRefSecLabel').textContent = sectionLabels[section] || section;
      _origOpenPanel(triggerBtn, section, fileInput);
    };

    // ── Guardar key Replicate si se ingresó ──────────────────────────────────
    async function saveReplicateKeyIfNeeded() {
      const keyInput = document.getElementById('ia_replicate_key');
      const key = keyInput ? keyInput.value.trim() : '';
      if (!key) return true;
      const res  = await fetch(BASE + '/AdminLanding/guardarApiKey', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ tipo: 'replicate', api_key: key, csrf_token: window.__CSRF__ || '' }),
      });
      const data = await res.json();
      if (!data.ok) { setError(data.error || 'Error guardando la key de Replicate.'); return false; }
      if (keyInput) keyInput.value = '';
      return true;
    }

    // ── Generar imagen ───────────────────────────────────────────────────────
    let lastGeneratedUrl = null;

    async function doGenerate() {
      setError('');
      const prompt = document.getElementById('iaImgPrompt').value.trim();
      if (!prompt) { setError('Escribe o sugiere un prompt primero.'); return; }

      if (!await saveReplicateKeyIfNeeded()) return;

      setLoading(true);
      showPreview(null);

      try {
        const strength    = document.getElementById('iaStrengthSelect')?.value || '0.75';
        const refActiva   = referenciaSeccionUrl || referenciaGlobalUrl || '';
        const body = new URLSearchParams({
          prompt,
          seccion:         currentSection,
          referencia_url:  refActiva,
          prompt_strength: strength,
          csrf_token:      window.__CSRF__ || '',
        });
        const res  = await fetch(BASE + '/AdminLanding/generarImagenIA', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body,
        });
        const data = await res.json();
        setLoading(false);
        if (!data.ok) {
          if (data.error === 'no_replicate_key') {
            setError('Ingresa tu API key de Replicate arriba.');
            if (keySectionEl) keySectionEl.style.display = '';
          } else {
            setError(data.error || 'Error generando la imagen.');
          }
          return;
        }
        lastGeneratedUrl = data.url;
        showPreview(data.url);
      } catch(e) { setLoading(false); setError('Error de red: ' + e.message); }
    }

    document.getElementById('iaImgGenerar').addEventListener('click', doGenerate);
    document.getElementById('iaImgRegen').addEventListener('click', doGenerate);

    // ── Usar imagen generada ─────────────────────────────────────────────────
    document.getElementById('iaImgUsar').addEventListener('click', () => {
      if (!lastGeneratedUrl || !currentSection) return;

      // Update hidden _actual field
      const actualName = actualMap[currentSection];
      if (actualName) {
        const hidden = document.querySelector(`[name="${actualName}"]`);
        if (hidden) hidden.value = lastGeneratedUrl;
      }

      // Update nearest .media-preview
      const container = currentFileInput?.closest('.admin-form-group, .gallery-card, .mini-card');
      const previewParent = container?.closest('div')?.querySelector('.media-preview')
                         || currentFileInput?.closest('form')?.querySelector(`#${currentSection.replace('_','-')}-preview`)
                         || null;

      // Find the media-preview in the same card/block (benefit-card first to evitar coger el primero de la sección)
      const sectionBlock = currentFileInput?.closest('.benefit-card, .mini-card, .gallery-card, .section-block');
      const mediaPreview = sectionBlock?.querySelector('.media-preview');
      if (mediaPreview) {
        mediaPreview.innerHTML = `<img src="${lastGeneratedUrl}" alt="Imagen IA" style="max-width:100%;border-radius:6px;">`;
      }

      // Clear the file input so the hidden _actual takes precedence
      if (currentFileInput) currentFileInput.value = '';

      closePanel();
    });
  })();
  </script>

  <script>
  /* ── Funciones de soporte sin panel CMS ─────────────────── */
  (function () {

    // ── Dirty tracking ────────────────────────────────────────
    var editorForm = document.getElementById('formLanding');
    if (editorForm) {
      editorForm.addEventListener('input',  function () { window.formDirty = true; });
      editorForm.addEventListener('change', function () { window.formDirty = true; });
      editorForm.addEventListener('submit', function () { window.formDirty = false; });
    }

    // ── Ctrl+S / Cmd+S ────────────────────────────────────────
    document.addEventListener('keydown', function (e) {
      if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        var form = document.getElementById('formLanding');
        if (form) form.submit();
      }
    });

    // ── Validación countdown_minutes ─────────────────────────
    document.addEventListener('ux:sections-ready', function () {
      var input = document.getElementById('countdown_minutes');
      if (!input) return;
      input.addEventListener('change', function () {
        var val = parseInt(this.value, 10);
        var existingErr = this.parentNode.querySelector('.field-err-msg');
        if (isNaN(val) || val < 1 || val > 1440) {
          this.style.borderColor = 'var(--err)';
          this.style.boxShadow   = '0 0 0 3px rgba(248,113,113,.12)';
          if (!existingErr) {
            var msg = document.createElement('span');
            msg.className = 'field-note field-err-msg';
            msg.textContent = 'Debe ser entre 1 y 1440 minutos.';
            this.parentNode.appendChild(msg);
          }
        } else {
          this.style.borderColor = '';
          this.style.boxShadow   = '';
          if (existingErr) existingErr.remove();
        }
      });
    });

  })();
  </script>

  <script>
  /* STUB: referencias al panel CMS eliminado — sale inmediatamente */
  (function () {
    if (!document.getElementById('cmsPanel')) return;

    var allSectionBlocks = [];
    var panelItems       = [];
    var activeSectionId  = null;
    var hideTimeouts     = {}; // race-condition guard para fade-out

    /* ── Etiquetas de sección para el breadcrumb ─────────────── */
    var sectionLabels = {
      'sec-hero':               'Hero',
      'sec-beneficios':         'Beneficios',
      'sec-galeria':            'Galería',
      'sec-caracteristicas':    'Características',
      'sec-comofunciona-content':'Cómo funciona',
      'sec-contador':           'Contador',
      'sec-porque':             '¿Por qué?',
      'sec-comparison':         'Comparativa',
      'sec-paraquien':          'Para quién',
      'sec-testimonios':        'Testimonios',
      'sec-faq':                'FAQs',
      'sec-wa':                 'WhatsApp',
      'sec-garantia':           'Garantía',
      'sec-regalo':             'Regalo',
      'sec-autoridad':          'Autoridad',
      'sec-ctas':               'CTAs',
      'sec-combo':              'Modo Combo',
      'sec-colores':            'Colores',
      'sec-announcement':       'Barra anuncios',
      'sec-hero-trust':         'Confianza hero',
      'sec-form-header':        'Formulario',
      'sec-footer':             'Footer',
    };

    /* ── C1: Breadcrumb de sección activa ────────────────────── */
    function updateBreadcrumb(sectionId) {
      var bc = document.getElementById('cmsBreadcrumb');
      if (!bc) return;
      if (window.innerWidth < 992) { bc.style.display = 'none'; return; }
      bc.style.display = '';

      var nameEl   = document.getElementById('cmsBcName');
      var statusEl = document.getElementById('cmsBcStatus');
      if (nameEl) nameEl.textContent = sectionLabels[sectionId] || sectionId;

      // Estado: leer del punto del panel
      if (statusEl) {
        var dot = document.getElementById('cps-' + sectionId);
        statusEl.className = 'cms-section-breadcrumb__status';
        statusEl.textContent = '';
        if (dot) {
          if (dot.classList.contains('toc-dot--complete')) {
            statusEl.classList.add('cms-bc-complete'); statusEl.textContent = 'Completo';
          } else if (dot.classList.contains('toc-dot--partial')) {
            statusEl.classList.add('cms-bc-partial'); statusEl.textContent = 'Incompleto';
          } else {
            statusEl.classList.add('cms-bc-empty'); statusEl.textContent = 'Vacío';
          }
        }
      }
    }

    /* ── D2: Banner sección desactivada ─────────────────────── */
    function updateDisabledBanner(sectionId) {
      var banner = document.getElementById('cmsDisabledBannerEl');
      if (!banner) return;
      var item  = document.querySelector('.cms-section-item[data-section="' + sectionId + '"]');
      var isOff = item && item.classList.contains('cms-si--off');
      banner.classList.toggle('is-visible', !!isOff);
      if (isOff && item) banner.dataset.key = item.dataset.showKey || '';
    }

    var activateBtn = document.getElementById('cmsActivateSection');
    if (activateBtn) {
      activateBtn.addEventListener('click', function () {
        var banner = document.getElementById('cmsDisabledBannerEl');
        var key    = banner && banner.dataset.key;
        if (!key) return;
        var cb = document.querySelector('input[type="checkbox"][name="' + key + '"]');
        if (cb) { cb.checked = true; cb.dispatchEvent(new Event('change', { bubbles: true })); }
        var item = document.querySelector('.cms-section-item[data-show-key="' + key + '"]');
        if (item) {
          var track = item.querySelector('.toggle-track');
          if (track) track.classList.add('is-on');
          item.classList.remove('cms-si--off');
        }
        banner.classList.remove('is-visible');
      });
    }

    /* ── C2: Scroll a sección + highlight en panel ──────────── */
    function activateSection(sectionId) {
      if (window.innerWidth < 992) return;

      activeSectionId = sectionId;

      // Asegurar que la sección esté expandida (acordeón)
      var target = document.getElementById(sectionId);
      if (target && typeof target._uxToggle === 'function') target._uxToggle(true);

      // Scroll suave a la sección
      if (target) {
        var headerH = parseInt(
          getComputedStyle(document.documentElement).getPropertyValue('--header-h')
        ) || 68;
        var top = target.getBoundingClientRect().top + window.scrollY - headerH - 20;
        window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
      }

      // Item activo en el panel
      panelItems.forEach(function (item) {
        item.classList.toggle('is-active', item.dataset.section === sectionId);
      });

      // Sincronizar TOC
      document.dispatchEvent(new CustomEvent('cms:section-activated', { detail: { id: sectionId } }));

      // Banner de sección desactivada
      updateDisabledBanner(sectionId);
    }

    /* ── Clics en items del panel ───────────────────────────── */
    function bindPanelItems() {
      panelItems = Array.from(document.querySelectorAll('.cms-section-item[data-section]'));
      panelItems.forEach(function (item) {
        item.addEventListener('click', function (e) {
          if (e.target.closest('.cms-si__toggle-btn') || e.target.closest('.cms-si__handle')) return;
          activateSection(item.dataset.section);
        });
      });
    }

    /* ── Sync toggle panel → checkbox real del form ─────────── */
    function bindPanelToggles() {
      panelItems.forEach(function (item) {
        var btn = item.querySelector('.cms-si__toggle-btn');
        var key = item.dataset.showKey;
        if (!btn || !key) return;
        btn.addEventListener('click', function (e) {
          e.stopPropagation();
          var cb = document.querySelector('input[type="checkbox"][name="' + key + '"]');
          if (!cb) return;
          cb.checked = !cb.checked;
          cb.dispatchEvent(new Event('change', { bubbles: true }));
          var track = btn.querySelector('.toggle-track');
          if (track) track.classList.toggle('is-on', cb.checked);
          item.classList.toggle('cms-si--off', !cb.checked);
          // Si es la sección activa, actualizar el banner
          if (item.dataset.section === activeSectionId) {
            updateDisabledBanner(activeSectionId);
          }
        });
      });
    }

    /* ── C4: Drag reorder en el panel ───────────────────────── */
    function initPanelDrag() {
      var container  = document.getElementById('cmsPanelSections');
      var orderInput = document.getElementById('section_order_input');
      if (!container || !orderInput) return;

      var dragged = null;

      container.addEventListener('dragstart', function (e) {
        var item = e.target.closest('.cms-section-item[data-order-key]');
        if (!item) return;
        dragged = item;
        setTimeout(function () { item.classList.add('dragging'); }, 0);
        e.dataTransfer.effectAllowed = 'move';
      });

      container.addEventListener('dragend', function () {
        if (dragged) dragged.classList.remove('dragging');
        container.querySelectorAll('.drag-over-top,.drag-over-bottom').forEach(function (el) {
          el.classList.remove('drag-over-top', 'drag-over-bottom');
        });
        savePanelOrder();
        dragged = null;
      });

      container.addEventListener('dragover', function (e) {
        e.preventDefault();
        var item = e.target.closest('.cms-section-item[data-order-key]');
        if (!item || item === dragged) return;
        container.querySelectorAll('.drag-over-top,.drag-over-bottom').forEach(function (el) {
          el.classList.remove('drag-over-top', 'drag-over-bottom');
        });
        var rect = item.getBoundingClientRect();
        item.classList.add(e.clientY < rect.top + rect.height / 2 ? 'drag-over-top' : 'drag-over-bottom');
      });

      container.addEventListener('dragleave', function (e) {
        var item = e.target.closest('.cms-section-item[data-order-key]');
        if (item) item.classList.remove('drag-over-top', 'drag-over-bottom');
      });

      container.addEventListener('drop', function (e) {
        e.preventDefault();
        var target = e.target.closest('.cms-section-item[data-order-key]');
        if (!target || !dragged || target === dragged) return;
        target.classList.remove('drag-over-top', 'drag-over-bottom');
        var rect = target.getBoundingClientRect();
        if (e.clientY < rect.top + rect.height / 2) {
          container.insertBefore(dragged, target);
        } else {
          container.insertBefore(dragged, target.nextSibling);
        }
        savePanelOrder();
      });

      function savePanelOrder() {
        var items = container.querySelectorAll('.cms-section-item[data-order-key]');
        orderInput.value = Array.from(items).map(function (el) { return el.dataset.orderKey; }).join(',');
      }

      // Alternativa de teclado para los handles del panel
      container.querySelectorAll('.cms-si__handle').forEach(function(handle) {
        handle.setAttribute('tabindex', '0');
        handle.setAttribute('role', 'button');
        handle.setAttribute('aria-label', 'Mover sección. Usa flechas arriba/abajo para reordenar');
        handle.addEventListener('keydown', function(e) {
          if (e.key !== 'ArrowUp' && e.key !== 'ArrowDown') return;
          e.preventDefault();
          var item = handle.closest('.cms-section-item[data-order-key]');
          if (!item) return;
          var items = Array.from(container.querySelectorAll('.cms-section-item[data-order-key]'));
          var idx = items.indexOf(item);
          if (e.key === 'ArrowUp' && idx > 0) {
            container.insertBefore(item, items[idx - 1]);
          } else if (e.key === 'ArrowDown' && idx < items.length - 1) {
            container.insertBefore(items[idx + 1], item);
          }
          savePanelOrder();
          handle.focus();
        });
      });
    }

    /* ── Sync puntos de estado: TOC → panel ─────────────────── */
    function syncDot(sectionId) {
      var tocLink  = document.querySelector('#landingToc a[data-target="' + sectionId + '"]');
      var panelDot = document.getElementById('cps-' + sectionId);
      if (!tocLink || !panelDot) return;
      var tocDot = tocLink.querySelector('.toc-dot');
      if (tocDot) panelDot.className = tocDot.className;
    }
    function syncAllDots() {
      document.querySelectorAll('#landingToc a[data-target]').forEach(function (a) { syncDot(a.dataset.target); });
    }
    function observeDots() {
      var tocLinks = document.querySelectorAll('#landingToc a[data-target]');
      if (!tocLinks.length || typeof MutationObserver === 'undefined') return;
      var mo = new MutationObserver(function (mutations) {
        mutations.forEach(function (m) {
          var link = m.target.closest('a[data-target]');
          if (link) {
            syncDot(link.dataset.target);
            // Si es la sección activa, actualizar el breadcrumb
            if (link.dataset.target === activeSectionId) updateBreadcrumb(activeSectionId);
          }
        });
      });
      tocLinks.forEach(function (link) {
        var dot = link.querySelector('.toc-dot');
        if (dot) mo.observe(dot, { attributes: true, attributeFilter: ['class'] });
      });
    }

    /* ── Progreso en el panel ───────────────────────────────── */
    function syncProgress() {
      var masterFill  = document.getElementById('ux-prog-fill');
      var masterLabel = document.getElementById('ux-prog-label');
      var panelFill   = document.getElementById('cmsProgFill');
      var panelLabel  = document.getElementById('cmsProgLabel');
      if (masterFill && panelFill)   panelFill.style.width   = masterFill.style.width;
      if (masterLabel && panelLabel) panelLabel.textContent   = masterLabel.textContent;
    }
    function observeProgress() {
      var masterFill = document.getElementById('ux-prog-fill');
      if (!masterFill || typeof MutationObserver === 'undefined') return;
      new MutationObserver(syncProgress).observe(masterFill, { attributes: true, attributeFilter: ['style'] });
    }

    /* ── I3: Botón guardar — loading state ───────────────────── */
    function bindSaveBtn() {
      var saveBtn = document.getElementById('cmsPanelSave');
      var formEl  = document.getElementById('formLanding');
      if (!saveBtn || !formEl) return;

      saveBtn.addEventListener('click', function (e) {
        e.preventDefault();
        saveBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Guardando…';
        saveBtn.disabled = true;
        saveBtn.classList.remove('is-dirty');
        // BUG FIX: dispatch submit event para que formDirty se limpie
        // antes de que beforeunload pueda dispararse al navegar
        formEl.dispatchEvent(new Event('submit', { bubbles: true, cancelable: false }));
        window.formDirty = false;
        setTimeout(function () { formEl.submit(); }, 80);
      });
    }

    /* ── "Sin guardar" indicador ──────────────────────────────── */
    function bindDirty() {
      var form      = document.getElementById('formLanding');
      var dirtyPill = document.getElementById('cmsDirtyPill');
      var saveBtn   = document.getElementById('cmsPanelSave');
      if (!form) return;
      function markDirty() {
        if (dirtyPill) dirtyPill.style.display = '';
        if (saveBtn && !saveBtn.disabled) saveBtn.classList.add('is-dirty');
      }
      form.addEventListener('input',  markDirty);
      form.addEventListener('change', markDirty);
    }

    /* ── D1: Ctrl+S / Cmd+S ──────────────────────────────────── */
    document.addEventListener('keydown', function (e) {
      if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        var saveBtn = document.getElementById('cmsPanelSave');
        if (saveBtn && !saveBtn.disabled) saveBtn.click();
        else {
          var form = document.getElementById('formLanding');
          if (form) form.submit();
        }
      }
    });

    /* ── D3: Validación visual countdown_minutes ─────────────── */
    function bindCountdownValidation() {
      var input = document.getElementById('countdown_minutes');
      if (!input) return;
      input.addEventListener('change', function () {
        var val = parseInt(this.value, 10);
        var existingErr = this.parentNode.querySelector('.field-err-msg');
        if (isNaN(val) || val < 1 || val > 1440) {
          this.style.borderColor = 'var(--err)';
          this.style.boxShadow   = '0 0 0 3px rgba(248,113,113,.12)';
          if (!existingErr) {
            var msg = document.createElement('span');
            msg.className = 'field-note field-err-msg';
            msg.textContent = 'Debe ser entre 1 y 1440 minutos.';
            this.parentNode.appendChild(msg);
          }
        } else {
          this.style.borderColor = '';
          this.style.boxShadow   = '';
          if (existingErr) existingErr.remove();
        }
      });
    }

    /* ── Botón IA del panel → botón real ─────────────────────── */
    function bindIA() {
      var cmsBtnIA  = document.getElementById('cmsBtnIA');
      var realBtnIA = document.getElementById('btnAbrirIA');
      if (cmsBtnIA && realBtnIA) cmsBtnIA.addEventListener('click', function () { realBtnIA.click(); });
    }

    /* ── C5: Ajustar left del panel al sidebar ───────────────── */
    function syncPanelLeft() {
      var panel = document.querySelector('.cms-panel');
      var sidebar = document.querySelector('.sidebar, .admin-sidebar, [class*="sidebar"]');
      if (!panel) return;
      var sidebarW = sidebar ? sidebar.getBoundingClientRect().width : 0;
      if (sidebarW > 0) panel.style.left = sidebarW + 'px';
    }

    /* ── INIT ────────────────────────────────────────────────── */
    document.addEventListener('ux:sections-ready', function () {
      allSectionBlocks = Array.from(document.querySelectorAll('.section-block[id]'));
      bindPanelItems();
      bindPanelToggles();
      initPanelDrag();
      observeDots();
      observeProgress();
      bindDirty();
      bindSaveBtn();
      bindIA();
      bindCountdownValidation();
      syncPanelLeft();

      // Observa cambios de ancho del sidebar (colapso/expansión)
      var sidebarEl = document.querySelector('.sidebar, .admin-sidebar, [class*="sidebar"]');
      if (sidebarEl && typeof ResizeObserver !== 'undefined') {
        new ResizeObserver(syncPanelLeft).observe(sidebarEl);
      }

      if (window.innerWidth >= 992 && panelItems.length) {
        setTimeout(function () {
          syncAllDots();
          syncProgress();
          // Destacar el primer item sin scroll (todas las secciones ya son visibles)
          if (panelItems[0]) panelItems[0].classList.add('is-active');
        }, 80);
      }
    });

  })();
  </script>

  <script>
  // Previews clickables: clic en una imagen cargada abre el selector de archivo
  document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.media-preview').forEach(function(preview) {
      // Cursor pointer en JS como fallback para navegadores sin :has()
      if (preview.querySelector('img, video')) preview.style.cursor = 'pointer';

      preview.addEventListener('click', function() {
        if (!preview.querySelector('img, video')) return;

        // Busca el input[type="file"] en el contenedor más cercano primero
        var candidates = [
          preview.closest('.upload-avatar-row'),
          preview.closest('.gallery-card'),
          preview.closest('.mini-card'),
          preview.closest('.admin-form-group'),
          preview.closest('.form-grid'),
          preview.closest('.section-block'),
        ];
        for (var ci = 0; ci < candidates.length; ci++) {
          var c = candidates[ci];
          if (!c) continue;
          var inp = c.querySelector('input[type="file"]');
          if (inp) { inp.click(); return; }
        }
      });
    });
  });
  </script>

  <script>
  // ── Mini diálogo de confirmación accesible ─────────────────────────────
  (function() {
    const dialog   = document.getElementById('confirmDialog');
    const msgEl    = document.getElementById('confirmDialogMsg');
    const btnOk    = document.getElementById('confirmDialogOk');
    const btnCancel= document.getElementById('confirmDialogCancel');
    let _resolve   = null;
    let _opener    = null;

    function openConfirm(msg, openerEl) {
      return new Promise(function(resolve) {
        _resolve = resolve;
        _opener  = openerEl || document.activeElement;
        msgEl.textContent = msg;
        dialog.style.display = 'flex';
        btnOk.focus();
      });
    }

    btnOk.addEventListener('click', function() {
      dialog.style.display = 'none';
      if (_opener) { _opener.focus(); _opener = null; }
      if (_resolve) { _resolve(true); _resolve = null; }
    });
    btnCancel.addEventListener('click', function() {
      dialog.style.display = 'none';
      if (_opener) { _opener.focus(); _opener = null; }
      if (_resolve) { _resolve(false); _resolve = null; }
    });
    document.addEventListener('keydown', function(e) {
      if (dialog.style.display === 'flex' && e.key === 'Escape') {
        btnCancel.click();
      }
    });

    // Maneja el cambio de producto con botón explícito
    document.querySelectorAll('.btn-switch-product').forEach(function(btn) {
      btn.addEventListener('click', async function() {
        const sel  = this.closest('form').querySelector('select[name="producto_id"]');
        if (!sel) return;
        if (sel.value === sel.dataset.current) return;
        if (!window.formDirty) { sel.form.submit(); return; }
        const ok = await openConfirm(this.dataset.confirm || '¿Continuar?', this);
        if (ok) { sel.form.submit(); }
        else    { sel.value = sel.dataset.current; }
      });
    });
  })();
  </script>

  <script>
  // ── Acordeón: guardar estado antes de guardar, restaurar al volver ──
  (function() {
    var STATE_KEY = 'landing_sec_state_<?= (int)$producto_id ?>';

    // Restaurar estado guardado justo después de que ux-improvements
    // inicialice los acordeones
    document.addEventListener('ux:sections-ready', function() {
      var raw = sessionStorage.getItem(STATE_KEY);
      if (!raw) return;
      sessionStorage.removeItem(STATE_KEY);
      try {
        var state = JSON.parse(raw);
        Object.keys(state).forEach(function(id) {
          var block = document.getElementById(id);
          if (block && typeof block._uxToggle === 'function') {
            block._uxToggle(!!state[id]);
          }
        });
      } catch(e) {}
    });

    // Guardar estado de cada sección justo antes del submit
    var form = document.getElementById('formLanding');
    if (form) {
      form.addEventListener('submit', function() {
        var state = {};
        document.querySelectorAll('.section-block[id]').forEach(function(block) {
          state[block.id] = !!block.querySelector('.sec-body--open');
        });
        sessionStorage.setItem(STATE_KEY, JSON.stringify(state));
      });
    }
  })();
  </script>

  <script>
  // Mobile TOC jump bar: navega a la sección y la abre si está colapsada
  (function() {
    var sel = document.getElementById('tocJumpSelect');
    if (!sel) return;
    sel.addEventListener('change', function() {
      var id = this.value;
      if (!id) return;
      var el = document.getElementById(id);
      if (el) {
        if (typeof el._uxToggle === 'function') el._uxToggle(true);
        var headerH = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--header-h')) || 64;
        var top = el.getBoundingClientRect().top + window.scrollY - headerH - 16;
        window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
      }
      this.value = '';
    });
  })();
  </script>

<script>
// Rutas guardadas en BD cuyo archivo ya no existe en disco: mostrar un aviso
// legible en vez del icono de imagen rota del navegador.
(function () {
    function marcarRota(img) {
        if (img.dataset.rotaMarcada) return;
        img.dataset.rotaMarcada = "1";
        var box = document.createElement("div");
        box.className = "media-empty media-empty--missing";
        box.innerHTML = '<i class="fas fa-link-slash" aria-hidden="true"></i>' +
            '<span>Archivo no encontrado</span>';
        box.title = img.getAttribute("src") || "";
        img.replaceWith(box);
    }
    document.querySelectorAll(".media-preview img, .gallery-card img").forEach(function (img) {
        if (img.complete && img.naturalWidth === 0) { marcarRota(img); return; }
        img.addEventListener("error", function () { marcarRota(img); });
    });
})();
</script>
<script>
// Aviso de cambios sin guardar: el formulario tiene ~377 campos y cerrar la
// pestaña por error descartaba todo el trabajo sin preguntar.
(function () {
    var form = document.getElementById("formLanding");
    if (!form) return;
    var sucio = false;
    var marcar = function () { sucio = true; };
    form.addEventListener("input", marcar);
    form.addEventListener("change", marcar);
    form.addEventListener("submit", function () { sucio = false; });
    window.addEventListener("beforeunload", function (e) {
        if (!sucio) return;
        e.preventDefault();
        e.returnValue = "";
    });
})();

// Volver a la sección en la que se estaba trabajando tras guardar.
(function () {
    var form = document.getElementById("formLanding");
    if (!form) return;
    form.addEventListener("submit", function () {
        var visible = "";
        document.querySelectorAll(".section-block[id]").forEach(function (sec) {
            if (visible) return;
            var r = sec.getBoundingClientRect();
            if (r.bottom > 80) visible = sec.id;
        });
        try { sessionStorage.setItem("landingSeccion", visible); } catch (err) {}
    });
    var vuelta = null;
    try { vuelta = sessionStorage.getItem("landingSeccion"); } catch (err) {}
    if (!vuelta) return;
    try { sessionStorage.removeItem("landingSeccion"); } catch (err) {}
    var destino = document.getElementById(vuelta);
    if (destino) setTimeout(function () { destino.scrollIntoView({ block: "start" }); }, 120);
})();
</script>
</body>

</html>