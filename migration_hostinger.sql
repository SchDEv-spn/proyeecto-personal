-- ============================================================
-- MIGRACIÓN PARA HOSTINGER — Ejecutar en phpMyAdmin
-- Todas las sentencias usan IF NOT EXISTS / IF EXISTS:
-- son idempotentes (se pueden correr varias veces sin error)
-- ============================================================

-- ── landing_config: columnas de visibilidad de secciones ──
ALTER TABLE landing_config
    ADD COLUMN IF NOT EXISTS show_benefits      TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS show_gallery       TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS show_antes_despues TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS show_como_funciona TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS show_countdown     TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS show_porque        TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS show_para_quien    TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS show_testimonios   TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS show_faqs          TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS section_order      VARCHAR(500) NULL;

-- ── landing_config: títulos de secciones configurables ──
ALTER TABLE landing_config
    ADD COLUMN IF NOT EXISTS gallery_title      TEXT NULL,
    ADD COLUMN IF NOT EXISTS testimonios_title  TEXT NULL,
    ADD COLUMN IF NOT EXISTS para_quien_title   TEXT NULL,
    ADD COLUMN IF NOT EXISTS faq_title          TEXT NULL;

-- ── landing_config: barra de anuncios ──
ALTER TABLE landing_config
    ADD COLUMN IF NOT EXISTS announcement_item_1 TEXT NULL,
    ADD COLUMN IF NOT EXISTS announcement_item_2 TEXT NULL,
    ADD COLUMN IF NOT EXISTS announcement_item_3 TEXT NULL,
    ADD COLUMN IF NOT EXISTS announcement_item_4 TEXT NULL,
    ADD COLUMN IF NOT EXISTS announcement_item_5 TEXT NULL,
    ADD COLUMN IF NOT EXISTS announcement_item_6 TEXT NULL;

-- ── landing_config: hero trust badges ──
ALTER TABLE landing_config
    ADD COLUMN IF NOT EXISTS hero_trust_1 TEXT NULL,
    ADD COLUMN IF NOT EXISTS hero_trust_2 TEXT NULL,
    ADD COLUMN IF NOT EXISTS hero_trust_3 TEXT NULL;

-- ── landing_config: "cómo funciona" ──
ALTER TABLE landing_config
    ADD COLUMN IF NOT EXISTS cf_title      TEXT NULL,
    ADD COLUMN IF NOT EXISTS cf_step1_icon  VARCHAR(20) NULL,
    ADD COLUMN IF NOT EXISTS cf_step1_title TEXT NULL,
    ADD COLUMN IF NOT EXISTS cf_step1_desc  TEXT NULL,
    ADD COLUMN IF NOT EXISTS cf_step2_icon  VARCHAR(20) NULL,
    ADD COLUMN IF NOT EXISTS cf_step2_title TEXT NULL,
    ADD COLUMN IF NOT EXISTS cf_step2_desc  TEXT NULL,
    ADD COLUMN IF NOT EXISTS cf_step3_icon  VARCHAR(20) NULL,
    ADD COLUMN IF NOT EXISTS cf_step3_title TEXT NULL,
    ADD COLUMN IF NOT EXISTS cf_step3_desc  TEXT NULL;

-- ── landing_config: garantía ──
ALTER TABLE landing_config
    ADD COLUMN IF NOT EXISTS show_garantia  TINYINT(1) NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS garantia_title TEXT NULL,
    ADD COLUMN IF NOT EXISTS garantia_desc  TEXT NULL,
    ADD COLUMN IF NOT EXISTS garantia_item1 TEXT NULL,
    ADD COLUMN IF NOT EXISTS garantia_item2 TEXT NULL,
    ADD COLUMN IF NOT EXISTS garantia_item3 TEXT NULL,
    ADD COLUMN IF NOT EXISTS garantia_item4 TEXT NULL;

-- ── landing_config: trust strip ──
ALTER TABLE landing_config
    ADD COLUMN IF NOT EXISTS show_trust_strip TINYINT(1) NULL DEFAULT 1;

-- ── landing_config: toggles de elementos fijos ──
ALTER TABLE landing_config
    ADD COLUMN IF NOT EXISTS show_announcement_bar TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS show_sticky_bar       TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS show_comparison       TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS show_resumen_oferta   TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS show_cta_sticky       TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS show_whatsapp_btn     TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS show_fomo             TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS show_exit_popup       TINYINT(1) NOT NULL DEFAULT 1;

-- ── landing_config: imágenes antes/después en comparativa ──
ALTER TABLE landing_config
    ADD COLUMN IF NOT EXISTS comparison_img_without VARCHAR(300) NULL,
    ADD COLUMN IF NOT EXISTS comparison_img_with    VARCHAR(300) NULL;

-- ── landing_config: testimonios de WhatsApp toggle ──
ALTER TABLE landing_config
    ADD COLUMN IF NOT EXISTS show_wa_testimonios TINYINT(1) NOT NULL DEFAULT 1;

-- ── productos: descuentos multicantidad ──
ALTER TABLE productos
    ADD COLUMN IF NOT EXISTS precio_regular               DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    ADD COLUMN IF NOT EXISTS descuento_multicantidad_activo TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS descuento_2da                TINYINT(3) UNSIGNED NOT NULL DEFAULT 15,
    ADD COLUMN IF NOT EXISTS descuento_3ra                TINYINT(3) UNSIGNED NOT NULL DEFAULT 20;

-- ── pedidos: totales desglosados ──
ALTER TABLE pedidos
    ADD COLUMN IF NOT EXISTS descuento_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    ADD COLUMN IF NOT EXISTS precio_total    DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    ADD COLUMN IF NOT EXISTS utilidad_total  DECIMAL(12,2) NOT NULL DEFAULT 0.00;

-- ── Tabla pedido_colores (detalle por color) ──
CREATE TABLE IF NOT EXISTS pedido_colores (
    id          INT(11) NOT NULL AUTO_INCREMENT,
    pedido_id   INT(11) NOT NULL,
    color       VARCHAR(50) NOT NULL,
    cantidad    INT(11) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_pedido (pedido_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── Tabla producto_colores ──
CREATE TABLE IF NOT EXISTS producto_colores (
    id          INT(11) NOT NULL AUTO_INCREMENT,
    producto_id INT(11) NOT NULL,
    color       VARCHAR(50) NOT NULL,
    activo      TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_producto (producto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── Tabla plantillas_wa ──
CREATE TABLE IF NOT EXISTS plantillas_wa (
    id         INT(11) NOT NULL AUTO_INCREMENT,
    tipo       VARCHAR(50) NOT NULL,
    nombre     VARCHAR(100) NOT NULL,
    mensaje    TEXT NOT NULL,
    activo     TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── landing_config: encabezados editables de tabla comparativa ──
ALTER TABLE landing_config
    ADD COLUMN IF NOT EXISTS comparison_label_without VARCHAR(120) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS comparison_label_with    VARCHAR(120) DEFAULT NULL;

-- ── landing_config: formulario modal ──
ALTER TABLE landing_config
    ADD COLUMN IF NOT EXISTS form_title    TEXT NULL,
    ADD COLUMN IF NOT EXISTS form_subtitle TEXT NULL;

-- ── landing_config: hero subtítulos adicionales y pie de página ──
ALTER TABLE landing_config
    ADD COLUMN IF NOT EXISTS hero_subtitle_2 TEXT NULL,
    ADD COLUMN IF NOT EXISTS hero_subtitle_3 TEXT NULL,
    ADD COLUMN IF NOT EXISTS show_footer     TINYINT(1) NOT NULL DEFAULT 1;

-- ── landing_config: imágenes por beneficio ──
ALTER TABLE landing_config
    ADD COLUMN IF NOT EXISTS benefit_1_img TEXT NULL,
    ADD COLUMN IF NOT EXISTS benefit_2_img TEXT NULL,
    ADD COLUMN IF NOT EXISTS benefit_3_img TEXT NULL,
    ADD COLUMN IF NOT EXISTS benefit_4_img TEXT NULL;

-- ── landing_config: toggles de CTA por sección ──
ALTER TABLE landing_config
    ADD COLUMN IF NOT EXISTS show_cta_benefits    TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS show_cta_gallery     TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS show_cta_porque      TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS show_cta_testimonials TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS show_cta_faq         TINYINT(1) NOT NULL DEFAULT 1;

-- ── landing_config: sección características (carrusel) ──
ALTER TABLE landing_config
    ADD COLUMN IF NOT EXISTS show_caracteristicas TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS caract_section_title TEXT NULL,
    ADD COLUMN IF NOT EXISTS caract1_media_path   TEXT NULL,
    ADD COLUMN IF NOT EXISTS caract1_media_type   TEXT NULL,
    ADD COLUMN IF NOT EXISTS caract1_title        TEXT NULL,
    ADD COLUMN IF NOT EXISTS caract1_text         TEXT NULL,
    ADD COLUMN IF NOT EXISTS caract2_media_path   TEXT NULL,
    ADD COLUMN IF NOT EXISTS caract2_media_type   TEXT NULL,
    ADD COLUMN IF NOT EXISTS caract2_title        TEXT NULL,
    ADD COLUMN IF NOT EXISTS caract2_text         TEXT NULL,
    ADD COLUMN IF NOT EXISTS caract3_media_path   TEXT NULL,
    ADD COLUMN IF NOT EXISTS caract3_media_type   TEXT NULL,
    ADD COLUMN IF NOT EXISTS caract3_title        TEXT NULL,
    ADD COLUMN IF NOT EXISTS caract3_text         TEXT NULL,
    ADD COLUMN IF NOT EXISTS caract4_media_path   TEXT NULL,
    ADD COLUMN IF NOT EXISTS caract4_media_type   TEXT NULL,
    ADD COLUMN IF NOT EXISTS caract4_title        TEXT NULL,
    ADD COLUMN IF NOT EXISTS caract4_text         TEXT NULL;

-- ── landing_config: CTAs configurables por sección ──
ALTER TABLE landing_config
    ADD COLUMN IF NOT EXISTS show_cta_como_funciona   TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS cta_como_funciona_text   TEXT NULL,
    ADD COLUMN IF NOT EXISTS cta_como_funciona_button TEXT NULL,
    ADD COLUMN IF NOT EXISTS show_cta_comparison      TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS cta_comparison_button    TEXT NULL,
    ADD COLUMN IF NOT EXISTS show_cta_para_quien      TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS cta_para_quien_button    TEXT NULL,
    ADD COLUMN IF NOT EXISTS show_cta_wa_testimonios  TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS cta_wa_testimonios_button TEXT NULL;

-- ── landing_config: ciudad del testimonio ──
ALTER TABLE landing_config
    ADD COLUMN IF NOT EXISTS test1_city TEXT NULL,
    ADD COLUMN IF NOT EXISTS test2_city TEXT NULL,
    ADD COLUMN IF NOT EXISTS test3_city TEXT NULL;

-- ── app_settings: tabla global de configuración (API keys, etc.) ──
CREATE TABLE IF NOT EXISTS app_settings (
    id         INT(11)      NOT NULL AUTO_INCREMENT,
    `key`      VARCHAR(100) NOT NULL,
    `value`    TEXT         NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_key (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── pedidos: atribución de Facebook (fbclid, _fbp, _fbc) ──
-- Sin esto ningún pedido se puede unir a un anuncio ni reenviar a la
-- Conversions API (ver AUDITORIA.md C3).
ALTER TABLE pedidos
    ADD COLUMN IF NOT EXISTS fbclid VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS fbp    VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS fbc    VARCHAR(255) NULL;

-- ── landing_config: Pixel de Facebook y Clarity por producto ──
-- Antes vivían escritos a mano en la vista — cambiarlos exigía tocar
-- código y hacer push a main (ver AUDITORIA.md M8). Vacío = usa el
-- valor por defecto (fb_pixel_id() en app/helpers.php).
ALTER TABLE landing_config
    ADD COLUMN IF NOT EXISTS pixel_id   VARCHAR(50) NULL,
    ADD COLUMN IF NOT EXISTS clarity_id VARCHAR(50) NULL;

-- ── Integración con Dropi (creación de órdenes en el proveedor) ──
-- dropi_product_id: liga el producto local con el producto en Dropi. Sin
-- esto, un pedido de ese producto nunca intenta sincronizarse (no-op).
ALTER TABLE productos
    ADD COLUMN IF NOT EXISTS dropi_product_id INT NULL;

-- dropi_variation_id: solo se llena para productos VARIABLE en Dropi (uno
-- por color). Si el producto es SIMPLE en Dropi, se deja NULL en todos los
-- colores y el pedido se manda como un solo ítem sin variación.
ALTER TABLE producto_colores
    ADD COLUMN IF NOT EXISTS dropi_variation_id INT NULL;

-- dropi_order_id: id de la orden ya creada en Dropi — funciona como
-- guarda de idempotencia (si ya tiene valor, no se reintenta el envío).
-- dropi_sync_error: último mensaje de error de Dropi, visible en el panel
-- para que el admin sepa por qué no se sincronizó (ciudad no reconocida,
-- producto sin mapear, etc.) sin tener que mirar los logs del servidor.
-- dropi_syncing: mutex de una sola fila. Un pedido puede llegar a
-- cambiarEstado() dos veces casi a la vez (doble clic, o el fallback de
-- funciones.js reintentando por fetch fallido) — sin esto, ambas peticiones
-- pasan el chequeo de "¿ya tiene dropi_order_id?" antes de que la primera
-- termine de llamar a Dropi, y se crean dos órdenes para el mismo pedido.
ALTER TABLE pedidos
    ADD COLUMN IF NOT EXISTS dropi_order_id   INT NULL,
    ADD COLUMN IF NOT EXISTS dropi_sync_error VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS dropi_syncing    TINYINT(1) NOT NULL DEFAULT 0;

-- ── Fin de migración ──
SELECT 'Migración completada OK' AS resultado;
