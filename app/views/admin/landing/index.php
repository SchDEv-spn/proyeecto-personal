<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Admin - Editar landing</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="manifest" href="<?= BASE_URL ?>/public/manifest.php">
  <meta name="theme-color" content="#C9A84C">
  <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/admin-unified.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script>if('serviceWorker' in navigator) navigator.serviceWorker.register('<?= BASE_URL ?>/sw.js');</script>
</head>

<body>
  <?php
  $config      = $config ?? [];
  $success     = $success ?? '';
  $producto_id = isset($producto_id) ? (int)$producto_id : 1;
  $productos   = $productos ?? [];
  $producto    = $producto ?? null;

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
      'icon'  => '',
    ],
  ];

  if ($producto) {
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

  <div class="app-shell">

    <!-- Sidebar (partial) -->
    <?php require __DIR__ . '/../partials/_sidebar.php'; ?>

    <main class="material-main material-main--simple">
      <?php require __DIR__ . '/../partials/_header.php'; ?>

      <section class="material-content" id="top">

        <?php if (!empty($success)): ?>
          <div class="admin-alert-success">
            <i class="fas fa-circle-check"></i>
            <span><?= htmlspecialchars($success) ?></span>
          </div>
        <?php endif; ?>

        <!-- Layout con índice lateral -->
        <div class="landing-editor-layout">
          <div class="landing-editor-main">

            <!-- Selector producto -->
            <div class="form-card">
              <div class="form-card-header">
                <h3>Seleccionar producto</h3>
              </div>
              <div class="form-card-body">
                <form action="<?= BASE_URL ?>/AdminLanding/index" method="GET" class="admin-form admin-form--compact">
                  <div class="admin-form-group">
                    <label for="producto_id_select">Producto</label>
                    <select name="producto_id" id="producto_id_select" onchange="this.form.submit()">
                      <?php foreach ($productos as $prod): ?>
                        <option value="<?= htmlspecialchars($prod['id']) ?>" <?= ($prod['id'] == $producto_id) ? 'selected' : '' ?>>
                          <?= htmlspecialchars($prod['nombre']) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </form>
              </div>
            </div>

            <!-- FORM PRINCIPAL -->
            <div class="form-card">
              <div class="form-card-header">
                <h3>Configuración de la landing</h3>
              </div>

              <div class="form-card-body">
                <form action="<?= BASE_URL ?>/AdminLanding/guardar" method="POST" class="admin-form" enctype="multipart/form-data">
                  <?= csrf_field() ?>
                  <input type="hidden" name="producto_id" value="<?= htmlspecialchars($producto_id) ?>">

                  <!-- SECCIONES VISIBLES -->
                  <div class="section-block" id="sec-secciones" data-toc="Secciones">
                    <h2>Secciones visibles</h2>
                    <p class="section-hint">Activa o desactiva cada sección de la landing pública.</p>
                    <div class="sections-toggle-grid">

                      <div class="section-toggle-item section-toggle-item--locked">
                        <span class="section-toggle-name">🦸 Hero</span>
                        <span class="section-toggle-badge">Siempre visible</span>
                      </div>

                      <?php
                      $toggles = [
                        'show_benefits'      => ['icon' => '✨', 'label' => 'Beneficios'],
                        'show_gallery'       => ['icon' => '🖼',  'label' => 'Galería'],
                        'show_antes_despues' => ['icon' => '🔄', 'label' => 'Antes y Después'],
                        'show_como_funciona' => ['icon' => '📦', 'label' => 'Cómo funciona'],
                        'show_countdown'     => ['icon' => '⏳', 'label' => 'Contador / Oferta'],
                        'show_porque'        => ['icon' => '💡', 'label' => 'Por qué encantará'],
                        'show_para_quien'    => ['icon' => '👥', 'label' => '¿Para quién es?'],
                        'show_testimonios'   => ['icon' => '⭐', 'label' => 'Testimonios'],
                        'show_faqs'             => ['icon' => '❓', 'label' => 'Preguntas frecuentes'],
                        'show_wa_testimonios' => ['icon' => '📱', 'label' => 'Testimonios WhatsApp'],
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
                          <span class="drag-handle" title="Arrastrar para reordenar">⠿</span>
                          <span class="section-toggle-pos"><?= $i + 1 ?></span>
                          <span class="section-toggle-icon"><?= $icon ?></span>
                          <span class="section-toggle-name"><?= $lbl ?></span>
                          <label class="toggle-label">
                            <input type="hidden"   name="<?= $name ?>" value="0">
                            <input type="checkbox" name="<?= $name ?>" value="1"
                                   class="toggle-cb" <?= $checked ? 'checked' : '' ?>>
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
                        'show_sticky_bar'       => ['icon' => '📌', 'label' => 'Barra de precio sticky'],
                        'show_announcement_bar' => ['icon' => '📢', 'label' => 'Barra de anuncios'],
                        'show_comparison'       => ['icon' => '⚖️', 'label' => 'Tabla comparativa'],
                        'show_resumen_oferta'   => ['icon' => '🏷️', 'label' => 'Resumen de oferta'],
                        'show_cta_sticky'       => ['icon' => '📱', 'label' => 'CTA sticky mobile'],
                        'show_whatsapp_btn'     => ['icon' => '💬', 'label' => 'Botón WhatsApp flotante'],
                        'show_fomo'             => ['icon' => '🔔', 'label' => 'Notificaciones FOMO'],
                        'show_exit_popup'       => ['icon' => '🚪', 'label' => 'Popup de salida'],
                      ];
                      foreach ($fixedToggles as $fname => $fdata):
                        $fchecked = isset($config[$fname]) ? (int)$config[$fname] : 1;
                      ?>
                        <div class="section-toggle-item">
                          <span class="section-toggle-icon"><?= $fdata['icon'] ?></span>
                          <span class="section-toggle-name"><?= $fdata['label'] ?></span>
                          <label class="toggle-label">
                            <input type="hidden"   name="<?= $fname ?>" value="0">
                            <input type="checkbox" name="<?= $fname ?>" value="1"
                                   class="toggle-cb" <?= $fchecked ? 'checked' : '' ?>>
                            <span class="toggle-track"><span class="toggle-thumb"></span></span>
                          </label>
                        </div>
                      <?php endforeach; ?>
                    </div>

                  </div>

                  <!-- HERO -->
                  <div class="section-block" id="sec-hero" data-toc="Hero">
                    <h2>Sección Hero</h2>

                    <div class="form-grid">
                      <div class="admin-form-group">
                        <label for="hero_title">Título principal</label>
                        <input type="text" id="hero_title" name="hero_title"
                          value="<?= htmlspecialchars($config['hero_title'] ?? '') ?>">
                      </div>

                      <div class="admin-form-group">
                        <label for="hero_button_text">Texto del botón principal</label>
                        <input type="text" id="hero_button_text" name="hero_button_text"
                          value="<?= htmlspecialchars($config['hero_button_text'] ?? '¡Necesito el mío!') ?>">
                      </div>

                      <div class="admin-form-group admin-form-group--full">
                        <label for="hero_subtitle">Subtítulo</label>
                        <textarea id="hero_subtitle" name="hero_subtitle" rows="2"><?= htmlspecialchars($config['hero_subtitle'] ?? '') ?></textarea>
                      </div>

                      <div class="admin-form-group admin-form-group--full">
                        <label for="hero_note">Nota debajo del botón</label>
                        <input type="text" id="hero_note" name="hero_note"
                          value="<?= htmlspecialchars($config['hero_note'] ?? '') ?>">
                      </div>

                      <div class="admin-form-group">
                        <label for="hero_badge_stars">Badge — Calificación (ej. 4.9)</label>
                        <input type="text" id="hero_badge_stars" name="hero_badge_stars"
                          value="<?= htmlspecialchars($config['hero_badge_stars'] ?? '4.9') ?>">
                        <small>Número de estrellas que aparece en el badge flotante sobre la imagen.</small>
                      </div>

                      <div class="admin-form-group">
                        <label for="hero_badge_customers">Badge — Texto clientes</label>
                        <input type="text" id="hero_badge_customers" name="hero_badge_customers"
                          value="<?= htmlspecialchars($config['hero_badge_customers'] ?? '+3.200 clientes felices') ?>">
                        <small>Ej: +3.200 clientes felices</small>
                      </div>

                      <div class="admin-form-group">
                        <label>Tipo de media en el hero</label>
                        <?php $heroType = $config['hero_media_type'] ?? 'imagen'; ?>
                        <select name="hero_media_type" onchange="toggleMediaPreview('hero', this.value)">
                          <option value="imagen" <?= $heroType === 'imagen' ? 'selected' : '' ?>>Imagen</option>
                          <option value="video" <?= $heroType === 'video'  ? 'selected' : '' ?>>Video</option>
                        </select>
                      </div>

                      <div class="admin-form-group">
                        <label for="hero_media_file">Subir nueva imagen/video</label>
                        <input type="file" id="hero_media_file" name="hero_media_file" accept="image/*,video/*">
                      </div>

                      <div class="admin-form-group" id="hero-poster-group" style="<?= ($config['hero_media_type'] ?? 'imagen') !== 'video' ? 'display:none' : '' ?>">
                        <label for="hero_poster_file">Thumbnail del video (portada)</label>
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
                        <label>Media actual</label>
                        <div class="media-preview">
                          <?php if (!empty($config['hero_media_path'])): ?>
                            <?php if ($heroType === 'video'): ?>
                              <video src="<?= htmlspecialchars($config['hero_media_path']) ?>" controls></video>
                            <?php else: ?>
                              <img src="<?= htmlspecialchars($config['hero_media_path']) ?>" alt="Hero">
                            <?php endif; ?>
                          <?php else: ?>
                            <div class="media-empty">
                              <i class="fas fa-photo-film"></i>
                              <span>No hay media configurada.</span>
                            </div>
                          <?php endif; ?>
                        </div>

                        <input type="hidden" name="hero_media_path_actual"
                          value="<?= htmlspecialchars($config['hero_media_path'] ?? '') ?>">
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

                      <div class="admin-form-group">
                        <label>Beneficio 1</label>
                        <input type="text" name="benefit_1" value="<?= htmlspecialchars($config['benefit_1'] ?? '') ?>">
                      </div>
                      <div class="admin-form-group">
                        <label>Beneficio 2</label>
                        <input type="text" name="benefit_2" value="<?= htmlspecialchars($config['benefit_2'] ?? '') ?>">
                      </div>
                      <div class="admin-form-group">
                        <label>Beneficio 3</label>
                        <input type="text" name="benefit_3" value="<?= htmlspecialchars($config['benefit_3'] ?? '') ?>">
                      </div>
                      <div class="admin-form-group">
                        <label>Beneficio 4</label>
                        <input type="text" name="benefit_4" value="<?= htmlspecialchars($config['benefit_4'] ?? '') ?>">
                      </div>

                      <!-- ── TIPO DE MEDIO ─────────────────────────────────── -->
                      <div class="admin-form-group">
                        <label for="benefits_media_type">Tipo de medio</label>
                        <select name="benefits_media_type" id="benefits_media_type"
                          onchange="toggleMediaPreview('benefits', this.value)">
                          <option value="imagen"
                            <?= ($config['benefits_media_type'] ?? 'imagen') === 'imagen' ? 'selected' : '' ?>>
                            Imagen (JPG, PNG, WEBP)
                          </option>
                          <option value="gif"
                            <?= ($config['benefits_media_type'] ?? '') === 'gif' ? 'selected' : '' ?>>
                            GIF animado
                          </option>
                          <option value="video"
                            <?= ($config['benefits_media_type'] ?? '') === 'video' ? 'selected' : '' ?>>
                            Video (MP4, MOV, WEBM)
                          </option>
                        </select>
                      </div>

                      <!-- ── SUBIR ARCHIVO ─────────────────────────────────── -->
                      <div class="admin-form-group">
                        <label for="benefits_media_file">Subir nuevo archivo</label>
                        <input type="file"
                          id="benefits_media_file"
                          name="benefits_media_file"
                          accept="<?= ($config['benefits_media_type'] ?? 'imagen') === 'video'
                                    ? 'video/mp4,video/quicktime,video/webm'
                                    : (($config['benefits_media_type'] ?? 'imagen') === 'gif'
                                      ? 'image/gif'
                                      : 'image/jpeg,image/png,image/webp') ?>">
                        <small>Máx. recomendado: imágenes 2MB · videos 10MB</small>
                      </div>

                      <!-- ── PREVIEW ACTUAL ────────────────────────────────── -->
                      <div class="admin-form-group admin-form-group--full">
                        <label>Media actual</label>
                        <div class="media-preview" id="benefits_preview">
                          <?php if (!empty($config['benefits_media_path'])): ?>
                            <?php
                            $ext = strtolower(pathinfo($config['benefits_media_path'], PATHINFO_EXTENSION));
                            $isVideo = in_array($ext, ['mp4', 'mov', 'webm']);
                            ?>
                            <?php if ($isVideo): ?>
                              <video src="<?= htmlspecialchars($config['benefits_media_path']) ?>"
                                muted loop controls
                                style="max-width:100%; border-radius:6px;"></video>
                            <?php else: ?>
                              <img src="<?= htmlspecialchars($config['benefits_media_path']) ?>"
                                alt="Beneficios">
                            <?php endif; ?>
                          <?php else: ?>
                            <div class="media-empty">
                              <i class="fas fa-photo-video"></i>
                              <span>No hay media configurada.</span>
                            </div>
                          <?php endif; ?>
                        </div>

                        <input type="hidden" name="benefits_media_path_actual"
                          value="<?= htmlspecialchars($config['benefits_media_path'] ?? '') ?>">
                      </div>
                    </div>
                  </div>

                  <hr class="section-hr">

                  <!-- GALERÍA -->
                  <div class="section-block" id="sec-galeria" data-toc="Galería">
                    <h2>Galería</h2>

                    <div class="gallery-grid">
                      <?php
                      for ($i = 1; $i <= 4; $i++):
                        $key       = "gallery_{$i}_path";
                        $inputName = "gallery_{$i}_file";
                        $actual    = "gallery_{$i}_path_actual";
                      ?>
                        <div class="gallery-card">
                          <div class="gallery-title">Imagen <?= $i ?></div>

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
                      <?php endfor; ?>
                    </div>
                  </div>

                  <hr class="section-hr">

                  <!-- ANTES Y DESPUÉS -->
                  <div class="section-block" id="sec-antesdespues" data-toc="Antes/Después">
                    <h2>Antes y Después</h2>
                    <p style="opacity:.8; margin-bottom:16px;">Comparador deslizable. Solo aparece en la landing cuando ambas imágenes están cargadas.</p>

                    <div class="form-grid">
                      <div class="admin-form-group">
                        <label for="antes_despues_title">Título de la sección</label>
                        <input type="text" id="antes_despues_title" name="antes_despues_title"
                          value="<?= htmlspecialchars($config['antes_despues_title'] ?? 'Mira la diferencia') ?>">
                      </div>

                      <div class="admin-form-group">
                        <label for="antes_label">Etiqueta "Antes"</label>
                        <input type="text" id="antes_label" name="antes_label"
                          value="<?= htmlspecialchars($config['antes_label'] ?? 'Antes') ?>">
                      </div>

                      <div class="admin-form-group">
                        <label for="despues_label">Etiqueta "Después"</label>
                        <input type="text" id="despues_label" name="despues_label"
                          value="<?= htmlspecialchars($config['despues_label'] ?? 'Después') ?>">
                      </div>
                    </div>

                    <div class="gallery-grid" style="grid-template-columns: repeat(2, 1fr); margin-top:16px;">
                      <div class="gallery-card">
                        <div class="gallery-title">Imagen "Antes"</div>
                        <div class="media-preview">
                          <?php if (!empty($config['antes_path'])): ?>
                            <img src="<?= htmlspecialchars($config['antes_path']) ?>" alt="Antes">
                          <?php else: ?>
                            <div class="media-empty">
                              <i class="fas fa-image"></i>
                              <span>Sin imagen</span>
                            </div>
                          <?php endif; ?>
                        </div>
                        <input type="hidden" name="antes_path_actual" value="<?= htmlspecialchars($config['antes_path'] ?? '') ?>">
                        <div class="admin-form-group">
                          <label for="antes_file">Subir nueva</label>
                          <input type="file" id="antes_file" name="antes_file" accept="image/*">
                        </div>
                      </div>

                      <div class="gallery-card">
                        <div class="gallery-title">Imagen "Después"</div>
                        <div class="media-preview">
                          <?php if (!empty($config['despues_path'])): ?>
                            <img src="<?= htmlspecialchars($config['despues_path']) ?>" alt="Después">
                          <?php else: ?>
                            <div class="media-empty">
                              <i class="fas fa-image"></i>
                              <span>Sin imagen</span>
                            </div>
                          <?php endif; ?>
                        </div>
                        <input type="hidden" name="despues_path_actual" value="<?= htmlspecialchars($config['despues_path'] ?? '') ?>">
                        <div class="admin-form-group">
                          <label for="despues_file">Subir nueva</label>
                          <input type="file" id="despues_file" name="despues_file" accept="image/*">
                        </div>
                      </div>
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
                          min="1" max="1440">
                        <small>El timer persiste entre recargas vía sessionStorage. Cambiarlo aquí reinicia el contador para nuevas sesiones.</small>
                      </div>

                      <div class="admin-form-group">
                        <label for="urgency_stock">Stock inicial (unidades)</label>
                        <input type="number" id="urgency_stock" name="urgency_stock"
                          value="<?= htmlspecialchars($config['urgency_stock'] ?? '12') ?>"
                          min="1" max="999">
                        <small>Número de unidades que aparece en el hero. Decrece automáticamente y se marca crítico al llegar a ≤ 3.</small>
                      </div>
                    </div>
                  </div>

                  <hr class="section-hr">

                  <!-- POR QUÉ -->
                  <div class="section-block" id="sec-porque" data-toc="¿Por qué?">
                    <h2>¿Por qué te encantará?</h2>

                    <div class="form-grid">
                      <div class="admin-form-group">
                        <label for="porque_title">Título</label>
                        <input type="text" id="porque_title" name="porque_title"
                          value="<?= htmlspecialchars($config['porque_title'] ?? '¿Por qué te encantará este producto?') ?>">
                      </div>

                      <div class="admin-form-group admin-form-group--full">
                        <label for="porque_text">Texto principal</label>
                        <textarea id="porque_text" name="porque_text" rows="4"><?= htmlspecialchars($config['porque_text'] ?? '') ?></textarea>
                      </div>

                      <div class="admin-form-group">
                        <label>Bullet 1</label>
                        <input type="text" name="porque_bullet1" value="<?= htmlspecialchars($config['porque_bullet1'] ?? '') ?>">
                      </div>
                      <div class="admin-form-group">
                        <label>Bullet 2</label>
                        <input type="text" name="porque_bullet2" value="<?= htmlspecialchars($config['porque_bullet2'] ?? '') ?>">
                      </div>
                      <div class="admin-form-group">
                        <label>Bullet 3</label>
                        <input type="text" name="porque_bullet3" value="<?= htmlspecialchars($config['porque_bullet3'] ?? '') ?>">
                      </div>

                      <!-- ── TIPO DE MEDIO ─────────────────────────────────── -->
                      <div class="admin-form-group">
                        <label for="porque_media_type">Tipo de medio</label>
                        <select name="porque_media_type" id="porque_media_type"
                          onchange="toggleMediaPreview('porque', this.value)">
                          <option value="imagen"
                            <?= ($config['porque_media_type'] ?? 'imagen') === 'imagen' ? 'selected' : '' ?>>
                            Imagen (JPG, PNG, WEBP)
                          </option>
                          <option value="gif"
                            <?= ($config['porque_media_type'] ?? '') === 'gif' ? 'selected' : '' ?>>
                            GIF animado
                          </option>
                          <option value="video"
                            <?= ($config['porque_media_type'] ?? '') === 'video' ? 'selected' : '' ?>>
                            Video (MP4, MOV, WEBM)
                          </option>
                        </select>
                      </div>

                      <!-- ── SUBIR ARCHIVO ─────────────────────────────────── -->
                      <div class="admin-form-group">
                        <label for="porque_media_file">Subir nuevo archivo</label>
                        <input type="file"
                          id="porque_media_file"
                          name="porque_media_file"
                          accept="<?= ($config['porque_media_type'] ?? 'imagen') === 'video'
                                    ? 'video/mp4,video/quicktime,video/webm'
                                    : (($config['porque_media_type'] ?? 'imagen') === 'gif'
                                      ? 'image/gif'
                                      : 'image/jpeg,image/png,image/webp') ?>">
                        <small>Máx. recomendado: imágenes 2MB · videos 10MB</small>
                      </div>

                      <!-- ── PREVIEW ACTUAL ────────────────────────────────── -->
                      <div class="admin-form-group admin-form-group--full">
                        <label>Media actual</label>
                        <div class="media-preview" id="porque_preview">
                          <?php if (!empty($config['porque_media_path'])): ?>
                            <?php
                            $ext     = strtolower(pathinfo($config['porque_media_path'], PATHINFO_EXTENSION));
                            $isVideo = in_array($ext, ['mp4', 'mov', 'webm']);
                            ?>
                            <?php if ($isVideo): ?>
                              <video src="<?= htmlspecialchars($config['porque_media_path']) ?>"
                                muted loop controls
                                style="max-width:100%; border-radius:6px;"></video>
                            <?php else: ?>
                              <img src="<?= htmlspecialchars($config['porque_media_path']) ?>"
                                alt="Por qué te encantará">
                            <?php endif; ?>
                          <?php else: ?>
                            <div class="media-empty">
                              <i class="fas fa-photo-video"></i>
                              <span>Sin media configurada.</span>
                            </div>
                          <?php endif; ?>
                        </div>

                        <input type="hidden" name="porque_media_path_actual"
                          value="<?= htmlspecialchars($config['porque_media_path'] ?? '') ?>">
                      </div>
                    </div>
                  </div>

                  <hr class="section-hr">

                  <!-- TABLA COMPARATIVA -->
                  <div class="section-block" id="sec-comparison" data-toc="Comparativa">
                    <h2>Tabla Comparativa (Con / Sin)</h2>
                    <p style="opacity:.8; margin-bottom:16px;">
                      Muestra la diferencia entre tener y no tener el producto. Solo aparece si hay al menos 1 fila completa.
                    </p>

                    <div class="form-grid">
                      <div class="admin-form-group admin-form-group--full">
                        <label for="comparison_title">Título de la sección</label>
                        <input type="text" id="comparison_title" name="comparison_title"
                          value="<?= htmlspecialchars($config['comparison_title'] ?? 'La diferencia que hace este producto') ?>">
                      </div>
                    </div>

                    <!-- Imágenes por columna -->
                    <div class="form-grid" style="margin-top:16px;">
                      <div class="admin-form-group">
                        <label>🖼 Imagen columna "Sin el producto"</label>
                        <?php if (!empty($config['comparison_img_without'])): ?>
                          <img src="<?= htmlspecialchars($config['comparison_img_without']) ?>"
                               style="width:100%; max-height:180px; object-fit:cover; border-radius:8px; margin-bottom:8px;">
                        <?php endif; ?>
                        <input type="file" name="comparison_img_without_file"
                               accept="image/*,video/mp4,video/webm">
                        <input type="hidden" name="comparison_img_without_path_actual"
                               value="<?= htmlspecialchars($config['comparison_img_without'] ?? '') ?>">
                      </div>
                      <div class="admin-form-group">
                        <label>🖼 Imagen columna "Con el producto"</label>
                        <?php if (!empty($config['comparison_img_with'])): ?>
                          <img src="<?= htmlspecialchars($config['comparison_img_with']) ?>"
                               style="width:100%; max-height:180px; object-fit:cover; border-radius:8px; margin-bottom:8px;">
                        <?php endif; ?>
                        <input type="file" name="comparison_img_with_file"
                               accept="image/*,video/mp4,video/webm">
                        <input type="hidden" name="comparison_img_with_path_actual"
                               value="<?= htmlspecialchars($config['comparison_img_with'] ?? '') ?>">
                      </div>
                    </div>

                    <div class="stack-cards" style="margin-top:16px;">
                      <?php for ($i = 1; $i <= 5; $i++):
                        $withoutKey = "comparison_{$i}_without";
                        $withKey    = "comparison_{$i}_with";
                      ?>
                        <div class="mini-card">
                          <div class="mini-card-title">
                            <i class="fas fa-arrows-left-right"></i> Fila <?= $i ?>
                          </div>
                          <div class="form-grid">
                            <div class="admin-form-group">
                              <label>❌ Sin el producto</label>
                              <input type="text" name="<?= $withoutKey ?>"
                                value="<?= htmlspecialchars($config[$withoutKey] ?? '') ?>"
                                placeholder="ej. Pierdes tiempo todos los días">
                            </div>
                            <div class="admin-form-group">
                              <label>✅ Con el producto</label>
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

                    <div class="stack-cards">
                      <?php for ($i = 1; $i <= 3; $i++):
                        $nameKey     = "test{$i}_name";
                        $cityKey     = "test{$i}_city";
                        $textKey     = "test{$i}_text";
                        $photoKey    = "test{$i}_photo_path";
                        $photoInput  = "test{$i}_photo_file";
                        $photoActual = "test{$i}_photo_path_actual";
                      ?>
                        <div class="mini-card">
                          <div class="mini-card-title">
                            <i class="fas fa-comment-dots"></i> Testimonio <?= $i ?>
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
                              <label>Texto</label>
                              <textarea name="<?= $textKey ?>" rows="2"><?= htmlspecialchars($config[$textKey] ?? '') ?></textarea>
                            </div>

                            <div class="admin-form-group">
                              <label for="<?= $photoInput ?>">Subir nueva foto</label>
                              <input type="file" id="<?= $photoInput ?>" name="<?= $photoInput ?>" accept="image/*">
                            </div>

                            <div class="admin-form-group admin-form-group--full">
                              <label>Foto actual</label>
                              <div class="media-preview">
                                <?php if (!empty($config[$photoKey])): ?>
                                  <img src="<?= htmlspecialchars($config[$photoKey]) ?>" alt="Testimonio <?= $i ?>">
                                <?php else: ?>
                                  <div class="media-empty">
                                    <i class="fas fa-user"></i>
                                    <span>Sin foto</span>
                                  </div>
                                <?php endif; ?>
                              </div>

                              <input type="hidden" name="<?= $photoActual ?>" value="<?= htmlspecialchars($config[$photoKey] ?? '') ?>">
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
                    <p style="opacity:.8; margin-bottom:16px;">Dos columnas que ayudan al visitante a auto-identificarse. Deja en blanco los ítems que no uses.</p>

                    <div class="stack-cards">
                      <div class="mini-card">
                        <div class="mini-card-title">
                          <i class="fas fa-circle-check" style="color:var(--ok)"></i> Sí es para ti si…
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
                          <i class="fas fa-circle-xmark" style="color:var(--err)"></i> No es para ti si…
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
                    </div>
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
                        <label for="wa_social_counter">Contador social (encima del título)</label>
                        <input type="text" id="wa_social_counter" name="wa_social_counter"
                          value="<?= htmlspecialchars($config['wa_social_counter'] ?? '★ +89 pedidos realizados') ?>"
                          placeholder="Ej: ★ +150 pedidos realizados">
                        <small style="opacity:.7;">Actualízalo cuando crezcan tus ventas.</small>
                      </div>

                      <div class="admin-form-group admin-form-group--full">
                        <label for="wa_footer_note">Nota inferior (debajo de los puntos)</label>
                        <input type="text" id="wa_footer_note" name="wa_footer_note"
                          value="<?= htmlspecialchars($config['wa_footer_note'] ?? '💡 Desliza para ver más • Capturas 100% reales de WhatsApp') ?>">
                      </div>
                    </div>

                    <div class="stack-cards" style="margin-top: 14px;">
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
                            <i class="fab fa-whatsapp"></i> WhatsApp <?= $i ?>
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

                    <p style="margin-top:10px; opacity:.8;">
                      Nota: Mantendremos siempre 5 testimonios para que tu JavaScript del slider no se rompa.
                    </p>
                  </div>

                  <hr class="section-hr">

                  <!-- FAQ -->
                  <div class="section-block" id="sec-faq" data-toc="FAQ">
                    <h2>Preguntas frecuentes</h2>

                    <div class="stack-cards">
                      <?php for ($i = 1; $i <= 6; $i++):
                        $qKey = "faq{$i}_q";
                        $aKey = "faq{$i}_a";
                      ?>
                        <div class="mini-card">
                          <div class="mini-card-title">
                            <i class="fas fa-circle-question"></i> FAQ <?= $i ?>
                            <?php if ($i > 3): ?><small style="opacity:.6;">(opcional)</small><?php endif; ?>
                          </div>

                          <div class="form-grid">
                            <div class="admin-form-group">
                              <label>Pregunta</label>
                              <input type="text" name="<?= $qKey ?>" value="<?= htmlspecialchars($config[$qKey] ?? '') ?>">
                            </div>

                            <div class="admin-form-group admin-form-group--full">
                              <label>Respuesta</label>
                              <textarea name="<?= $aKey ?>" rows="2"><?= htmlspecialchars($config[$aKey] ?? '') ?></textarea>
                            </div>
                          </div>
                        </div>
                      <?php endfor; ?>
                    </div>
                  </div>

                  <hr class="section-hr">

                  <!-- AUTORIDAD / CREDIBILIDAD -->
                  <div class="section-block" id="sec-autoridad" data-toc="Autoridad">
                    <h2>Sección de Autoridad / Credibilidad</h2>
                    <p style="opacity:.8; margin-bottom:16px;">4 estadísticas que generan confianza justo antes del formulario. Actívala cuando tengas números reales.</p>

                    <div class="form-grid">
                      <div class="admin-form-group admin-form-group--full">
                        <label style="display:flex; gap:10px; align-items:center;">
                          <input type="checkbox" name="authority_enabled" value="1"
                            <?= !empty($config['authority_enabled']) ? 'checked' : '' ?>>
                          Activar sección de autoridad
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

                    <div class="stack-cards">
                      <div class="mini-card">
                        <div class="mini-card-title"><i class="fas fa-bullhorn"></i> CTA Beneficios</div>
                        <div class="form-grid">
                          <div class="admin-form-group admin-form-group--full">
                            <label for="cta_benefits_text">Texto</label>
                            <textarea id="cta_benefits_text" name="cta_benefits_text" rows="2"><?= htmlspecialchars($config['cta_benefits_text'] ?? 'Ya sabes lo que hace. El siguiente paso es recibirlo en casa.') ?></textarea>
                          </div>
                          <div class="admin-form-group">
                            <label for="cta_benefits_button">Botón</label>
                            <input type="text" id="cta_benefits_button" name="cta_benefits_button"
                              value="<?= htmlspecialchars($config['cta_benefits_button'] ?? 'Quiero aprovechar la oferta') ?>">
                          </div>
                        </div>
                      </div>

                      <div class="mini-card">
                        <div class="mini-card-title"><i class="fas fa-images"></i> CTA Galería</div>
                        <div class="form-grid">
                          <div class="admin-form-group admin-form-group--full">
                            <label for="cta_gallery_text">Texto</label>
                            <textarea id="cta_gallery_text" name="cta_gallery_text" rows="2"><?= htmlspecialchars($config['cta_gallery_text'] ?? 'Lo que ves es lo que llega. Sin sorpresas, sin excusas.') ?></textarea>
                          </div>
                          <div class="admin-form-group">
                            <label for="cta_gallery_button">Botón</label>
                            <input type="text" id="cta_gallery_button" name="cta_gallery_button"
                              value="<?= htmlspecialchars($config['cta_gallery_button'] ?? 'Lo quiero igual que en las fotos') ?>">
                          </div>
                        </div>
                      </div>

                      <div class="mini-card">
                        <div class="mini-card-title"><i class="fas fa-heart"></i> CTA ¿Por qué?</div>
                        <div class="form-grid">
                          <div class="admin-form-group admin-form-group--full">
                            <label for="cta_porque_text">Texto</label>
                            <textarea id="cta_porque_text" name="cta_porque_text" rows="2"><?= htmlspecialchars($config['cta_porque_text'] ?? 'Miles lo recibieron. Tú eres el siguiente.') ?></textarea>
                          </div>
                          <div class="admin-form-group">
                            <label for="cta_porque_button">Botón</label>
                            <input type="text" id="cta_porque_button" name="cta_porque_button"
                              value="<?= htmlspecialchars($config['cta_porque_button'] ?? 'Quiero sentir ese cambio') ?>">
                          </div>
                        </div>
                      </div>

                      <div class="mini-card">
                        <div class="mini-card-title"><i class="fas fa-star"></i> CTA Testimonios</div>
                        <div class="form-grid">
                          <div class="admin-form-group admin-form-group--full">
                            <label for="cta_testimonials_text">Texto</label>
                            <textarea id="cta_testimonials_text" name="cta_testimonials_text" rows="2"><?= htmlspecialchars($config['cta_testimonials_text'] ?? 'Ellos ya lo tienen. Tu pedido tarda menos de 2 minutos.') ?></textarea>
                          </div>
                          <div class="admin-form-group">
                            <label for="cta_testimonials_button">Botón</label>
                            <input type="text" id="cta_testimonials_button" name="cta_testimonials_button"
                              value="<?= htmlspecialchars($config['cta_testimonials_button'] ?? 'Quiero ser el próximo en recibirlo') ?>">
                          </div>
                        </div>
                      </div>

                      <div class="mini-card">
                        <div class="mini-card-title"><i class="fas fa-shield-halved"></i> CTA FAQ</div>
                        <div class="form-grid">
                          <div class="admin-form-group admin-form-group--full">
                            <label for="cta_faq_text">Texto</label>
                            <textarea id="cta_faq_text" name="cta_faq_text" rows="2"><?= htmlspecialchars($config['cta_faq_text'] ?? 'Dudas resueltas. Esto solo falta: hacer tu pedido.') ?></textarea>
                          </div>
                          <div class="admin-form-group">
                            <label for="cta_faq_button">Botón</label>
                            <input type="text" id="cta_faq_button" name="cta_faq_button"
                              value="<?= htmlspecialchars($config['cta_faq_button'] ?? 'Sí, quiero pedirlo ahora') ?>">
                          </div>
                        </div>
                      </div>

                      <div class="mini-card">
                        <div class="mini-card-title"><i class="fas fa-mobile-screen"></i> CTA fija móvil</div>
                        <div class="form-grid">
                          <div class="admin-form-group admin-form-group--full">
                            <label for="cta_sticky_mobile_text">Texto del botón fijo inferior</label>
                            <input type="text" id="cta_sticky_mobile_text" name="cta_sticky_mobile_text"
                              value="<?= htmlspecialchars($config['cta_sticky_mobile_text'] ?? '🔥 Aprovechar oferta hoy') ?>">
                          </div>
                        </div>
                      </div>

                      <div class="mini-card">
                        <div class="mini-card-title"><i class="fab fa-whatsapp" style="color:#25D366;"></i> Botón flotante de WhatsApp</div>
                        <div class="form-grid">
                          <div class="admin-form-group admin-form-group--full">
                            <label for="wa_phone">Número de WhatsApp (solo dígitos, con código de país)</label>
                            <input type="text" id="wa_phone" name="wa_phone"
                              value="<?= htmlspecialchars($config['wa_phone'] ?? '573023959721') ?>"
                              placeholder="ej. 573001234567">
                            <small>Formato: código de país + número sin + ni espacios. Ej: 573001234567</small>
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
                        <div class="mini-card-title"><i class="fas fa-boxes"></i> Configuración</div>

                        <div class="form-grid">
                          <div class="admin-form-group admin-form-group--full">
                            <label style="display:flex; gap:10px; align-items:center;">
                              <input type="checkbox" name="combo_enabled" value="1" <?= $comboEnabled === 1 ? 'checked' : '' ?>>
                              Activar Combo x2 en la landing
                            </label>
                            <small class="help">Activa esta opción para mostrar el selector “x2” en la landing.</small>
                          </div>

                          <div class="admin-form-group">
                            <label for="combo_price_2">Precio Combo x2 (COP)</label>
                            <input type="number" id="combo_price_2" name="combo_price_2"
                              value="<?= htmlspecialchars((string)$comboPrice2) ?>"
                              min="0" step="1000">
                            <small class="help">Ej: 115000.</small>
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

                        <label class="theme-card <?= ($config['theme'] ?? 'dark-luxury') === 'dark-luxury' ? 'theme-card--active' : '' ?>">
                          <input type="radio" name="theme" value="dark-luxury"
                            <?= ($config['theme'] ?? 'dark-luxury') === 'dark-luxury' ? 'checked' : '' ?>
                            onchange="applyThemePreview('dark-luxury')">
                          <div class="theme-card__preview theme-card__preview--dark-luxury">
                            <span class="theme-card__dot"></span>
                            <span class="theme-card__dot"></span>
                            <span class="theme-card__dot"></span>
                          </div>
                          <div class="theme-card__info">
                            <strong>Dark Luxury</strong>
                            <small>Negro · Dorado cálido · Premium</small>
                          </div>
                        </label>

                        <label class="theme-card <?= ($config['theme'] ?? '') === 'light-luxury' ? 'theme-card--active' : '' ?>">
                          <input type="radio" name="theme" value="light-luxury"
                            <?= ($config['theme'] ?? '') === 'light-luxury' ? 'checked' : '' ?>
                            onchange="applyThemePreview('light-luxury')">
                          <div class="theme-card__preview theme-card__preview--light-luxury">
                            <span class="theme-card__dot"></span>
                            <span class="theme-card__dot"></span>
                            <span class="theme-card__dot"></span>
                          </div>
                          <div class="theme-card__info">
                            <strong>Light Luxury</strong>
                            <small>Crema · Borgoña · Femenino</small>
                          </div>
                        </label>

                        <label class="theme-card <?= ($config['theme'] ?? '') === 'bold-conversion' ? 'theme-card--active' : '' ?>">
                          <input type="radio" name="theme" value="bold-conversion"
                            <?= ($config['theme'] ?? '') === 'bold-conversion' ? 'checked' : '' ?>
                            onchange="applyThemePreview('bold-conversion')">
                          <div class="theme-card__preview theme-card__preview--bold-conversion">
                            <span class="theme-card__dot"></span>
                            <span class="theme-card__dot"></span>
                            <span class="theme-card__dot"></span>
                          </div>
                          <div class="theme-card__info">
                            <strong>Bold Conversion</strong>
                            <small>Blanco · Naranja · Energético</small>
                          </div>
                        </label>

                        <label class="theme-card <?= ($config['theme'] ?? '') === 'minimal-clean' ? 'theme-card--active' : '' ?>">
                          <input type="radio" name="theme" value="minimal-clean"
                            <?= ($config['theme'] ?? '') === 'minimal-clean' ? 'checked' : '' ?>
                            onchange="applyThemePreview('minimal-clean')">
                          <div class="theme-card__preview theme-card__preview--minimal-clean">
                            <span class="theme-card__dot"></span>
                            <span class="theme-card__dot"></span>
                            <span class="theme-card__dot"></span>
                          </div>
                          <div class="theme-card__info">
                            <strong>Minimal Clean</strong>
                            <small>Azul marino · Confianza · Tech</small>
                          </div>
                        </label>

                        <label class="theme-card <?= ($config['theme'] ?? '') === 'femme-rose' ? 'theme-card--active' : '' ?>">
                          <input type="radio" name="theme" value="femme-rose"
                            <?= ($config['theme'] ?? '') === 'femme-rose' ? 'checked' : '' ?>
                            onchange="applyThemePreview('femme-rose')">
                          <div class="theme-card__preview theme-card__preview--femme-rose">
                            <span class="theme-card__dot"></span>
                            <span class="theme-card__dot"></span>
                            <span class="theme-card__dot"></span>
                          </div>
                          <div class="theme-card__info">
                            <strong>Femme Rose</strong>
                            <small>Rosa · Fucsia · Belleza</small>
                          </div>
                        </label>

                        <label class="theme-card <?= ($config['theme'] ?? '') === 'natural-sage' ? 'theme-card--active' : '' ?>">
                          <input type="radio" name="theme" value="natural-sage"
                            <?= ($config['theme'] ?? '') === 'natural-sage' ? 'checked' : '' ?>
                            onchange="applyThemePreview('natural-sage')">
                          <div class="theme-card__preview theme-card__preview--natural-sage">
                            <span class="theme-card__dot"></span>
                            <span class="theme-card__dot"></span>
                            <span class="theme-card__dot"></span>
                          </div>
                          <div class="theme-card__info">
                            <strong>Natural Sage</strong>
                            <small>Verde · Orgánico · Salud</small>
                          </div>
                        </label>

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
                              oninput="syncHex(this, 'color_gold_hex')">
                            <input type="text" id="color_gold_hex" class="color-hex-input"
                              value="<?= htmlspecialchars($config['color_gold'] ?? '#c9a84c') ?>"
                              maxlength="7" oninput="syncPicker(this, 'color_gold')">
                          </div>
                        </div>

                        <div class="admin-form-group">
                          <label>Dorado claro <small>Hover · Precio actual</small></label>
                          <div class="color-picker-wrap">
                            <input type="color" id="color_gold_light" name="color_gold_light"
                              value="<?= htmlspecialchars($config['color_gold_light'] ?? '#e8c96a') ?>"
                              oninput="syncHex(this, 'color_gold_light_hex')">
                            <input type="text" id="color_gold_light_hex" class="color-hex-input"
                              value="<?= htmlspecialchars($config['color_gold_light'] ?? '#e8c96a') ?>"
                              maxlength="7" oninput="syncPicker(this, 'color_gold_light')">
                          </div>
                        </div>

                        <div class="admin-form-group">
                          <label>Éxito <small>Envío gratis · Ahorro · Badges</small></label>
                          <div class="color-picker-wrap">
                            <input type="color" id="color_success" name="color_success"
                              value="<?= htmlspecialchars($config['color_success'] ?? '#4caf7d') ?>"
                              oninput="syncHex(this, 'color_success_hex')">
                            <input type="text" id="color_success_hex" class="color-hex-input"
                              value="<?= htmlspecialchars($config['color_success'] ?? '#4caf7d') ?>"
                              maxlength="7" oninput="syncPicker(this, 'color_success')">
                          </div>
                        </div>

                        <div class="admin-form-group">
                          <label>Countdown <small>Timer de urgencia</small></label>
                          <div class="color-picker-wrap">
                            <input type="color" id="color_countdown" name="color_countdown"
                              value="<?= htmlspecialchars($config['color_countdown'] ?? '#e8c96a') ?>"
                              oninput="syncHex(this, 'color_countdown_hex')">
                            <input type="text" id="color_countdown_hex" class="color-hex-input"
                              value="<?= htmlspecialchars($config['color_countdown'] ?? '#e8c96a') ?>"
                              maxlength="7" oninput="syncPicker(this, 'color_countdown')">
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

                    <!-- ── PREVIEW BAR ───────────────────────────────────────── -->
                    <div class="color-preview-bar" id="colorPreviewBar">
                      <span id="prev_background_color" style="background:<?= htmlspecialchars($config['background_color'] ?? '#0a0a0a') ?>"></span>
                      <span id="prev_text_color" style="background:<?= htmlspecialchars($config['text_color']       ?? '#f0ebe0') ?>"></span>
                      <span id="prev_primary_color" style="background:<?= htmlspecialchars($config['primary_color']    ?? '#c9a84c') ?>"></span>
                      <span id="prev_accent_color" style="background:<?= htmlspecialchars($config['accent_color']     ?? '#e8c96a') ?>"></span>
                      <span id="prev_secondary_color" style="background:<?= htmlspecialchars($config['secondary_color']  ?? '#5c4a1e') ?>"></span>
                      <span id="prev_color_gold" style="background:<?= htmlspecialchars($config['color_gold']       ?? '#c9a84c') ?>"></span>
                      <span id="prev_color_success" style="background:<?= htmlspecialchars($config['color_success']    ?? '#4caf7d') ?>"></span>
                      <span id="prev_color_countdown" style="background:<?= htmlspecialchars($config['color_countdown']  ?? '#e8c96a') ?>"></span>
                    </div>
                    <p class="color-preview-label">Vista previa de la paleta completa</p>

                  </div>


                  <hr class="section-hr">

                  <!-- TÍTULOS DE SECCIÓN -->
                  <div class="section-block" id="sec-titulos" data-toc="Títulos">
                    <h2>Títulos de sección</h2>
                    <p style="opacity:.8; margin-bottom:16px;">Personaliza los encabezados de cada sección visible en la landing.</p>

                    <div class="form-grid">
                      <div class="admin-form-group">
                        <label for="gallery_title">Galería</label>
                        <input type="text" id="gallery_title" name="gallery_title"
                          value="<?= htmlspecialchars($config['gallery_title'] ?? 'Galería') ?>">
                      </div>
                      <div class="admin-form-group">
                        <label for="testimonios_title">Testimonios</label>
                        <input type="text" id="testimonios_title" name="testimonios_title"
                          value="<?= htmlspecialchars($config['testimonios_title'] ?? 'Lo que cuentan nuestros clientes') ?>">
                      </div>
                      <div class="admin-form-group">
                        <label for="para_quien_title">¿Para quién es?</label>
                        <input type="text" id="para_quien_title" name="para_quien_title"
                          value="<?= htmlspecialchars($config['para_quien_title'] ?? '¿Este producto es para ti?') ?>">
                      </div>
                      <div class="admin-form-group">
                        <label for="faq_title">Preguntas frecuentes</label>
                        <input type="text" id="faq_title" name="faq_title"
                          value="<?= htmlspecialchars($config['faq_title'] ?? 'Preguntas frecuentes') ?>">
                      </div>
                    </div>
                  </div>

                  <hr class="section-hr">

                  <!-- ANNOUNCEMENT BAR -->
                  <div class="section-block" id="sec-announcement" data-toc="Barra">
                    <h2>Barra de anuncios</h2>
                    <p style="opacity:.8; margin-bottom:16px;">Ítems del ticker superior. Deja en blanco los que no uses — se usan los defaults si todos están vacíos.</p>

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
                    <p style="opacity:.8; margin-bottom:16px;">Tres ítems que aparecen debajo del botón principal del hero.</p>

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
                    <p style="opacity:.8; margin-bottom:16px;">Título y contenido de los 3 pasos del proceso de compra. La visibilidad se controla desde "Secciones visibles".</p>

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
                        <div class="mini-card-title">Paso <?= $n ?></div>
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
                    <p style="opacity:.8; margin-bottom:16px;">Banda de confianza que aparece antes del formulario. Actívala o desactívala según necesites.</p>

                    <div class="form-grid">
                      <div class="admin-form-group admin-form-group--full">
                        <label style="display:flex; gap:10px; align-items:center;">
                          <input type="hidden" name="show_garantia" value="0">
                          <input type="checkbox" name="show_garantia" value="1"
                            <?= !empty($config['show_garantia']) || !isset($config['show_garantia']) ? 'checked' : '' ?>>
                          Mostrar banner de garantía
                        </label>
                      </div>

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

                  <!-- TRANSPORTADORAS -->
                  <div class="section-block" id="sec-transportadoras" data-toc="Transportadoras">
                    <h2>Transportadoras</h2>
                    <p style="opacity:.8; margin-bottom:16px;">Banda con logos de las transportadoras. Aparece justo antes del formulario de pedido.</p>

                    <div class="form-grid">
                      <div class="admin-form-group admin-form-group--full">
                        <label style="display:flex; gap:10px; align-items:center;">
                          <input type="hidden" name="show_trust_strip" value="0">
                          <input type="checkbox" name="show_trust_strip" value="1"
                            <?= !isset($config['show_trust_strip']) || !empty($config['show_trust_strip']) ? 'checked' : '' ?>>
                          Mostrar logos de transportadoras
                        </label>
                        <small>Interrapidísimo · Envía · Coordinadora</small>
                      </div>
                    </div>
                  </div>

                  <hr class="section-hr">

                  <!-- FORMULARIO — CABECERA -->
                  <div class="section-block" id="sec-form-header" data-toc="Form. pedido">
                    <h2>Formulario — Cabecera</h2>
                    <p style="opacity:.8; margin-bottom:16px;">Título y subtítulo que aparecen sobre el formulario de pedido.</p>

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

                  <!-- FOOTER -->
                  <div class="section-block" id="sec-footer" data-toc="Footer">
                    <h2>Footer</h2>

                    <div class="form-grid">
                      <div class="admin-form-group admin-form-group--full">
                        <label for="footer_text">Texto del footer</label>
                        <input type="text" id="footer_text" name="footer_text"
                          value="<?= htmlspecialchars($config['footer_text'] ?? '') ?>">
                      </div>
                    </div>
                  </div>

                  <!-- Acciones sticky -->
                  <div class="admin-form-actions">
                    <button type="submit" class="btn-estado">
                      <i class="fas fa-save"></i> Guardar cambios
                    </button>
                  </div>

                </form>
              </div>
            </div>

          </div>

          <!-- Índice lateral -->
          <aside class="landing-editor-toc" aria-label="Índice de secciones">
            <div class="toc-card">
              <div class="toc-title">
                <i class="fas fa-list"></i> Secciones
              </div>

              <nav class="toc-nav" id="landingToc">
                <a href="#sec-secciones" data-target="sec-secciones">Secciones</a>
                <a href="#sec-hero" data-target="sec-hero">Hero</a>
                <a href="#sec-beneficios" data-target="sec-beneficios">Beneficios</a>
                <a href="#sec-galeria" data-target="sec-galeria">Galería</a>
                <a href="#sec-antesdespues" data-target="sec-antesdespues">Antes/Después</a>
                <a href="#sec-contador" data-target="sec-contador">Contador</a>
                <a href="#sec-porque" data-target="sec-porque">¿Por qué?</a>
                <a href="#sec-comparison" data-target="sec-comparison">Comparativa</a>
                <a href="#sec-testimonios" data-target="sec-testimonios">Testimonios</a>
                <a href="#sec-paraquien" data-target="sec-paraquien">¿Para quién?</a>
                <a href="#sec-wa" data-target="sec-wa">WhatsApp</a>
                <a href="#sec-faq" data-target="sec-faq">FAQ</a>
                <a href="#sec-autoridad" data-target="sec-autoridad">Autoridad</a>
                <a href="#sec-ctas" data-target="sec-ctas">CTAs</a>
                <a href="#sec-combo" data-target="sec-combo">Modo Combo</a>
                <a href="#sec-colores" data-target="sec-colores">Colores</a>
                <a href="#sec-titulos" data-target="sec-titulos">Títulos</a>
                <a href="#sec-announcement" data-target="sec-announcement">Barra</a>
                <a href="#sec-hero-trust" data-target="sec-hero-trust">Confianza hero</a>
                <a href="#sec-comofunciona-content" data-target="sec-comofunciona-content">Cómo funciona</a>
                <a href="#sec-garantia" data-target="sec-garantia">Garantía</a>
                <a href="#sec-transportadoras" data-target="sec-transportadoras">Transportadoras</a>
                <a href="#sec-form-header" data-target="sec-form-header">Form. pedido</a>
                <a href="#sec-footer" data-target="sec-footer">Footer</a>
              </nav>

              <div class="toc-footer">
                <a class="toc-top" href="#top"><i class="fas fa-arrow-up"></i> Arriba</a>
              </div>
            </div>
          </aside>
        </div>

      </section>
    </main>
  </div><!-- /app-shell -->

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
    const editorForm = document.querySelector('form');
    if (editorForm) {
      editorForm.addEventListener('input', function() { formDirty = true; });
      editorForm.addEventListener('change', function() { formDirty = true; });
      editorForm.addEventListener('submit', function() { formDirty = false; });
    }
    window.addEventListener('beforeunload', function(e) {
      if (!formDirty) return;
      e.preventDefault();
      e.returnValue = '';
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

    // Actualiza preview bar con los 8 colores principales
    function updatePreviewBar() {
      const ids = [
        'background_color', 'text_color', 'primary_color',
        'accent_color', 'secondary_color', 'color_gold',
        'color_success', 'color_countdown'
      ];
      ids.forEach(function(id) {
        const picker = document.getElementById(id);
        const preview = document.getElementById('prev_' + id);
        if (picker && preview) {
          preview.style.background = picker.value;
        }
      });
    }

    // Aplica colores del tema al editor sin guardar
    function applyThemePreview(theme) {
      const themes = {
        'dark-luxury': {
          background_color: '#080808',
          text_color: '#f5ede0',
          primary_color: '#d4a853',
          accent_color: '#f0c472',
          secondary_color: '#6b4c1e',
          color_gold: '#d4a853',
          color_gold_light: '#f0c472',
          color_success: '#4caf7d',
          color_countdown: '#f0c472',
          color_bg_card: '#1c1814',
          color_border: '#d4a853'
        },
        'light-luxury': {
          background_color: '#fdf8f5',
          text_color: '#1a1014',
          primary_color: '#8b2252',
          accent_color: '#b5436e',
          secondary_color: '#4a1228',
          color_gold: '#8b2252',
          color_gold_light: '#b5436e',
          color_success: '#2e7d32',
          color_countdown: '#8b2252',
          color_bg_card: '#f7f0ec',
          color_border: '#8b2252'
        },
        'bold-conversion': {
          background_color: '#ffffff',
          text_color: '#1a1410',
          primary_color: '#e76f51',
          accent_color: '#f4a261',
          secondary_color: '#264653',
          color_gold: '#e76f51',
          color_gold_light: '#f4a261',
          color_success: '#2d6a4f',
          color_countdown: '#e76f51',
          color_bg_card: '#fdf8f5',
          color_border: '#e76f51'
        },
        'minimal-clean': {
          background_color: '#f8fafc',
          text_color: '#0f1d30',
          primary_color: '#1a2e4a',
          accent_color: '#2563eb',
          secondary_color: '#0f1d30',
          color_gold: '#1a2e4a',
          color_gold_light: '#2563eb',
          color_success: '#1b5e20',
          color_countdown: '#2563eb',
          color_bg_card: '#f0f4f8',
          color_border: '#1a2e4a'
        },
        'femme-rose': {
          background_color: '#fff5f7',
          text_color: '#2d1420',
          primary_color: '#c94a6b',
          accent_color: '#e87d9a',
          secondary_color: '#7a1f3d',
          color_gold: '#c94a6b',
          color_gold_light: '#e87d9a',
          color_success: '#2e7d32',
          color_countdown: '#c94a6b',
          color_bg_card: '#ffedf1',
          color_border: '#c94a6b'
        },
        'natural-sage': {
          background_color: '#f4f7f4',
          text_color: '#1a2e22',
          primary_color: '#2d6a4f',
          accent_color: '#52b788',
          secondary_color: '#1b4332',
          color_gold: '#2d6a4f',
          color_gold_light: '#52b788',
          color_success: '#1b4332',
          color_countdown: '#2d6a4f',
          color_bg_card: '#eaf2ec',
          color_border: '#2d6a4f'
        },
      };

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
  <script src="<?= BASE_URL ?>/public/js/funciones.js"></script>

  <!-- JS del índice lateral -->
  <script src="<?= BASE_URL ?>/public/js/admin-landing-toc.js"></script>
  <script src="<?= BASE_URL ?>/public/js/ux-improvements.js"></script>

</body>

</html>