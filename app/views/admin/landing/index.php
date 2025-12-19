<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Admin - Editar landing</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link rel="stylesheet" href="/tienda_mvc/public/css/editarLanding.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
?>

<!-- Sidebar -->
<aside class="material-sidebar" aria-label="Menú admin">
  <div class="sidebar-logo">
    <h2>FEDORA ULTIMATE</h2>
  </div>

  <div class="sidebar-user">
    <img src="/tienda_mvc/public/img/admi/1.jpg?user=<?= substr($usuarioNombre, 0, 2) ?>" alt="User">
    <div class="sidebar-user-text">
      <h4><?= htmlspecialchars($usuarioNombre) ?></h4>
      <small><?= htmlspecialchars($usuarioEmail) ?></small>
    </div>
  </div>

  <nav class="sidebar-nav">
    <a href="/tienda_mvc/AdminPedidos/index">
      <i class="fas fa-box"></i> Pedidos
    </a>
    <a href="/tienda_mvc/AdminProductos/index">
      <i class="fas fa-shopping-bag"></i> Productos
    </a>
    <a href="/tienda_mvc/AdminLanding/index" class="active">
      <i class="fas fa-wand-magic-sparkles"></i> Landing
    </a>
    <a href="/tienda_mvc/Auth/logout">
      <i class="fas fa-sign-out-alt"></i> Cerrar sesión
    </a>
  </nav>
</aside>

<!-- Main -->
<main class="material-main material-main--simple">
  <!-- Header fijo -->
  <header class="material-header">
    <div class="header-greeting header-greeting--with-menu">
      <button class="btn-menu" id="btnMenu" aria-label="Abrir menú">
        <i class="fas fa-bars"></i>
      </button>

      <div>
        <h3>Editar landing</h3>
        <p>Producto: <strong><?= htmlspecialchars($nombreProducto) ?></strong></p>
      </div>
    </div>

    <div class="header-actions">
      <a href="/tienda_mvc/AdminPedidos/index" class="btn-detail">
        <i class="fas fa-arrow-left"></i> Panel pedidos
      </a>

      <?php if ($producto): ?>
        <a href="/tienda_mvc/Landing/index?producto_id=<?= htmlspecialchars($producto_id) ?>"
           target="_blank" rel="noopener"
           class="btn-primary btn-primary--soft">
          <i class="fas fa-up-right-from-square"></i> Ver landing
        </a>
      <?php endif; ?>
    </div>
  </header>

  <section class="material-content">
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
            <form action="/tienda_mvc/AdminLanding/index" method="GET" class="admin-form admin-form--compact">
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
        <div class="form-card" id="top">
          <div class="form-card-header">
            <h3>Configuración de la landing</h3>
          </div>

          <div class="form-card-body">
            <form action="/tienda_mvc/AdminLanding/guardar" method="POST" class="admin-form" enctype="multipart/form-data">
              <input type="hidden" name="producto_id" value="<?= htmlspecialchars($producto_id) ?>">

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
                    <label>Tipo de media en el hero</label>
                    <?php $heroType = $config['hero_media_type'] ?? 'imagen'; ?>
                    <select name="hero_media_type">
                      <option value="imagen" <?= $heroType === 'imagen' ? 'selected' : '' ?>>Imagen</option>
                      <option value="video"  <?= $heroType === 'video'  ? 'selected' : '' ?>>Video</option>
                    </select>
                  </div>

                  <div class="admin-form-group">
                    <label for="hero_media_file">Subir nueva imagen/video</label>
                    <input type="file" id="hero_media_file" name="hero_media_file" accept="image/*,video/*">
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

                  <div class="admin-form-group">
                    <label for="benefits_media_file">Subir nueva imagen</label>
                    <input type="file" id="benefits_media_file" name="benefits_media_file" accept="image/*">
                  </div>

                  <div class="admin-form-group admin-form-group--full">
                    <label>Imagen actual</label>
                    <div class="media-preview">
                      <?php if (!empty($config['benefits_media_path'])): ?>
                        <img src="<?= htmlspecialchars($config['benefits_media_path']) ?>" alt="Beneficios">
                      <?php else: ?>
                        <div class="media-empty">
                          <i class="fas fa-image"></i>
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
                  <?php for ($i = 1; $i <= 3; $i++):
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

                  <div class="admin-form-group">
                    <label for="porque_media_file">Subir nueva imagen</label>
                    <input type="file" id="porque_media_file" name="porque_media_file" accept="image/*">
                  </div>

                  <div class="admin-form-group admin-form-group--full">
                    <label>Imagen actual</label>
                    <div class="media-preview">
                      <?php if (!empty($config['porque_media_path'])): ?>
                        <img src="<?= htmlspecialchars($config['porque_media_path']) ?>" alt="Por qué">
                      <?php else: ?>
                        <div class="media-empty">
                          <i class="fas fa-image"></i>
                          <span>Sin imagen</span>
                        </div>
                      <?php endif; ?>
                    </div>

                    <input type="hidden" name="porque_media_path_actual"
                           value="<?= htmlspecialchars($config['porque_media_path'] ?? '') ?>">
                  </div>
                </div>
              </div>

              <hr class="section-hr">

              <!-- TESTIMONIOS -->
              <div class="section-block" id="sec-testimonios" data-toc="Testimonios">
                <h2>Testimonios</h2>

                <div class="stack-cards">
                  <?php for ($i = 1; $i <= 3; $i++):
                    $nameKey     = "test{$i}_name";
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

              <!-- FAQ -->
              <div class="section-block" id="sec-faq" data-toc="FAQ">
                <h2>Preguntas frecuentes</h2>

                <div class="stack-cards">
                  <?php for ($i = 1; $i <= 3; $i++):
                    $qKey = "faq{$i}_q";
                    $aKey = "faq{$i}_a";
                  ?>
                    <div class="mini-card">
                      <div class="mini-card-title">
                        <i class="fas fa-circle-question"></i> FAQ <?= $i ?>
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

              <!-- CTAs -->
              <div class="section-block" id="sec-ctas" data-toc="CTAs">
                <h2>Textos de llamadas a la acción</h2>

                <div class="stack-cards">
                  <div class="mini-card">
                    <div class="mini-card-title"><i class="fas fa-bullhorn"></i> CTA Beneficios</div>
                    <div class="form-grid">
                      <div class="admin-form-group admin-form-group--full">
                        <label for="cta_benefits_text">Texto</label>
                        <textarea id="cta_benefits_text" name="cta_benefits_text" rows="2"><?= htmlspecialchars($config['cta_benefits_text'] ?? 'Si estos beneficios encajan contigo, haz tu pedido ahora y asegura el precio de hoy.') ?></textarea>
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
                        <textarea id="cta_gallery_text" name="cta_gallery_text" rows="2"><?= htmlspecialchars($config['cta_gallery_text'] ?? 'Todo lo que ves en las fotos es exactamente lo que recibirás en casa.') ?></textarea>
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
                        <textarea id="cta_porque_text" name="cta_porque_text" rows="2"><?= htmlspecialchars($config['cta_porque_text'] ?? 'Si quieres sentir estos mismos resultados, haz tu pedido en menos de 1 minuto.') ?></textarea>
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
                        <textarea id="cta_testimonials_text" name="cta_testimonials_text" rows="2"><?= htmlspecialchars($config['cta_testimonials_text'] ?? 'Cada día más personas reciben su pedido y quedan igual de felices que ellos.') ?></textarea>
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
                        <textarea id="cta_faq_text" name="cta_faq_text" rows="2"><?= htmlspecialchars($config['cta_faq_text'] ?? 'Si ya resolviste tus dudas, el siguiente paso es hacer tu pedido. Es rápido y seguro.') ?></textarea>
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
                </div>
              </div>

              <hr class="section-hr">

              <!-- COLORES -->
              <div class="section-block" id="sec-colores" data-toc="Colores">
                <h2>Colores de la landing</h2>

                <div class="colors-grid">
                  <div class="admin-form-group">
                    <label for="primary_color">Color principal</label>
                    <input type="color" id="primary_color" name="primary_color"
                           value="<?= htmlspecialchars($config['primary_color'] ?? '#28a745') ?>">
                  </div>

                  <div class="admin-form-group">
                    <label for="secondary_color">Color secundario</label>
                    <input type="color" id="secondary_color" name="secondary_color"
                           value="<?= htmlspecialchars($config['secondary_color'] ?? '#007bff') ?>">
                  </div>

                  <div class="admin-form-group">
                    <label for="accent_color">Color acento</label>
                    <input type="color" id="accent_color" name="accent_color"
                           value="<?= htmlspecialchars($config['accent_color'] ?? '#ffc107') ?>">
                  </div>

                  <div class="admin-form-group">
                    <label for="background_color">Fondo</label>
                    <input type="color" id="background_color" name="background_color"
                           value="<?= htmlspecialchars($config['background_color'] ?? '#f5f5f5') ?>">
                  </div>

                  <div class="admin-form-group">
                    <label for="text_color">Texto</label>
                    <input type="color" id="text_color" name="text_color"
                           value="<?= htmlspecialchars($config['text_color'] ?? '#222222') ?>">
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
            <a href="#sec-hero"        data-target="sec-hero">Hero</a>
            <a href="#sec-beneficios"  data-target="sec-beneficios">Beneficios</a>
            <a href="#sec-galeria"     data-target="sec-galeria">Galería</a>
            <a href="#sec-contador"    data-target="sec-contador">Contador</a>
            <a href="#sec-porque"      data-target="sec-porque">¿Por qué?</a>
            <a href="#sec-testimonios" data-target="sec-testimonios">Testimonios</a>
            <a href="#sec-faq"         data-target="sec-faq">FAQ</a>
            <a href="#sec-ctas"        data-target="sec-ctas">CTAs</a>
            <a href="#sec-colores"     data-target="sec-colores">Colores</a>
            <a href="#sec-footer"      data-target="sec-footer">Footer</a>
          </nav>

          <div class="toc-footer">
            <a class="toc-top" href="#top"><i class="fas fa-arrow-up"></i> Arriba</a>
          </div>
        </div>
      </aside>
    </div>
  </section>
</main>

<!-- JS global del admin (menú) -->
<script src="/tienda_mvc/public/js/funciones.js"></script>

<!-- JS del índice lateral -->
<script src="/tienda_mvc/public/js/admin-landing-toc.js"></script>

</body>
</html>
