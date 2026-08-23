# Tienda MVC — Análisis y Guía del Proyecto

> Generado el 2026-05-25. Proyecto: e-commerce de venta directa en Colombia.

---

## ¿Qué es este proyecto?

**Tienda MVC** es un e-commerce de **venta directa en Colombia**: una landing page configurable por producto, carrito de compra, y panel de administración. El modelo de negocio está orientado a WhatsApp / Telegram / contra-entrega.

Fue construido sin framework para facilitar el deploy en **hosting compartido (Hostinger)**, priorizando velocidad de lanzamiento sobre arquitectura ideal.

---

## Stack técnico

| Capa | Tecnología |
|---|---|
| Backend | PHP 7.4+, MySQL 8.0, PDO puro |
| Frontend | HTML5, CSS3, JavaScript vanilla ES6+ |
| Routing | MVC manual (`index.php` + `.htaccess`) |
| Deploy | XAMPP local / Hostinger producción |
| Integraciones | Telegram Bot API, Facebook Pixel, WhatsApp links |
| Sin | Composer, npm, frameworks, bundler, tests |

---

## Estructura del proyecto

```
tienda_mvc/
├── index.php              # Punto de entrada + router manual
├── .htaccess              # Rewrites Apache (routing + seguridad uploads)
├── .env                   # Credenciales locales (no commitar)
├── migration_hostinger.sql # SQL de migración idempotente
├── sw.js                  # Service Worker (PWA)
├── serve-upload.php       # Servidor seguro de imágenes subidas
│
├── app/
│   ├── config/
│   │   ├── config.php     # Config centralizada (lee .env o ~/tienda_config.php en prod)
│   │   └── config.example.php
│   ├── core/
│   │   ├── Controller.php # Clase base de controladores (render de vistas)
│   │   ├── Model.php      # Clase base de modelos (acceso a BD)
│   │   └── Database.php   # Singleton PDO
│   ├── controllers/       # 7 controladores
│   ├── models/            # 7 modelos
│   └── views/             # landing/, auth/, admin/
│
└── public/
    ├── css/               # ~11 archivos CSS (sin bundler)
    ├── js/                # main.js, funciones.js, etc.
    ├── img/               # Imágenes estáticas
    └── uploads/           # Imágenes dinámicas (productos/, landing/)
```

---

## Arquitectura MVC

### Routing

```
URL:  /{Controller}/{Method}/{Param1}/{Param2}
Caso especial: /producto/{slug} → LandingController::verPorSlug()
```

El `.htaccess` redirige todo a `index.php?url=...` y el router parsea la URL manualmente.

### Controladores

| Controlador | Rol |
|---|---|
| `LandingController` | Landing page pública, recepción de pedidos, notificación Telegram |
| `AuthController` | Login / logout |
| `AdminProductosController` | CRUD de productos |
| `AdminPedidosController` | Dashboard, métricas, cambio de estado, WhatsApp |
| `AdminLandingController` | Editor visual de landing (~100 campos) |
| `AdminPerfilController` | Perfil y cambio de password |
| `AdminPlantillasWaController` | Templates de mensajes WhatsApp |

### Modelos

| Modelo | Responsabilidad |
|---|---|
| `Producto` | Catálogo, precios, slugs, colores |
| `Pedido` | Órdenes con estado y precios calculados |
| `PedidoColor` | Detalle de colores por pedido |
| `ProductoColor` | Colores disponibles por producto |
| `LandingConfig` | ~100 columnas de configuración per-producto |
| `PlantillaWa` | Templates de mensajes con placeholders |
| `Usuario` | Usuarios del panel admin |

---

## Base de datos

### Tablas principales

| Tabla | Columnas clave |
|---|---|
| `productos` | id, nombre, slug, precio_venta, precio_regular, precio_proveedor, costo_envio, descuento_2da, descuento_3ra, activo |
| `pedidos` | id, producto_id, nombre, telefono, color, cantidad_total, departamento, municipio, tipo_entrega, precio_total, utilidad_total, estado, created_at |
| `pedido_colores` | pedido_id, color, cantidad |
| `producto_colores` | producto_id, color, activo |
| `landing_config` | producto_id + 100+ columnas de configuración (secciones, textos, imágenes, toggles) |
| `plantillas_wa` | estado, titulo, mensaje (con placeholders `{nombre}`, `{guia}`, etc.) |
| `usuarios` | email, password_hash, nombre, activo |

### Estados de pedido (flujo)

```
nuevo → contactado → confirmado → enviado → en_oficina → entregado
                                                        ↘ cancelado
```

---

## Funcionalidades

### Landing pública

- **Landing configurable por producto**: hero (título, subtítulos, CTA, imagen/video), beneficios, cómo funciona, comparativa, para quién, testimonios, FAQs, garantía, anuncios
- **Catálogo** con galería, lightbox, zoom
- **Carrito** con selección de color + cantidad por ítem
- **Sistema de descuentos**:
  - Multicantidad: 2da unidad `descuento_2da`%, 3ra+ `descuento_3ra`%
  - Combo x2 a precio fijo
  - Ambos son toggleables por producto
- **Formulario de pedido** en modal overlay (convertido en mayo 2026)
  - Validación: teléfono 10 dígitos colombiano (empieza en 3)
  - Selects de Departamento/Municipio (JSON con todos los de Colombia)
  - Tipo de entrega: Domicilio o Recoge en oficina
  - Anti-duplicado: mismo teléfono + producto en 15 min
  - CSRF protection
- **Social proof**: contador de pedidos recientes (últimos 30 días), testimonios con foto y ciudad
- **UI enhancements**: countdown timer, sticky CTA, botón WhatsApp flotante, FOMO, exit popup
- **PWA**: Service Worker (network-first), iconos 192/512

### Panel Admin

- **Dashboard de pedidos**: métricas (ventas, utilidad, ticket promedio, pedidos nuevos), tendencias vs período anterior, filtros Hoy/Ayer/Semana/Mes, búsqueda y listado
- **Gestión de pedidos**: cambiar estado, ver detalle en modal, enviar template WhatsApp, guardar datos de guía/transportadora
- **CRUD de productos**: nombre, slug, precios, descuentos, colores, imagen, toggle activo
- **Editor de landing**: ~100+ campos con upload de imágenes y videos
- **Plantillas WhatsApp**: CRUD con placeholders `{nombre}`, `{producto}`, `{municipio}`, `{guia}`, `{rastreo}`, `{transportadora}`
- **Perfil**: editar nombre/email, cambiar password

---

## Seguridad actual

| Aspecto | Estado |
|---|---|
| SQL Injection | Protegido — PDO con prepared statements en todo el proyecto |
| CSRF | Protegido — tokens en todos los formularios POST |
| Passwords | Protegido — bcrypt (PASSWORD_BCRYPT) |
| Sessions | Protegido — `session_regenerate_id()` en login |
| Credenciales | Protegido — en `.env` / `~/tienda_config.php` (fuera del webroot en prod) |
| XSS | Parcial — `htmlspecialchars()` en vistas, revisar consistencia |
| Rate limiting | Ausente — solo anti-duplicado básico (15 min) |
| Uploads | Protegido — `serve-upload.php` controla acceso |

---

## Deuda técnica priorizada

### Crítica — puede romper datos o abrir vulnerabilidades

1. **Sin transacciones DB al crear pedidos**
   - Crear pedido + `pedido_colores` son 2 queries separadas sin `BEGIN/COMMIT`
   - Si la segunda query falla, queda un pedido sin detalle de colores
   - Fix: envolver en `$db->beginTransaction()` / `$db->commit()`

2. **Lógica de precios duplicada y divergente**
   - `totalConDescuento()` existe en `LandingController` (usa datos del producto) Y en `Pedido` (hardcoded al 15% y 20%)
   - Riesgo: si se cambian los descuentos, una versión queda desactualizada
   - Fix: extraer a clase `PrecioCalculator` como única fuente de verdad

3. **Sin rate limiting en `enviarPedido()`**
   - Solo hay anti-duplicado por teléfono (15 min)
   - Vulnerable a spam de pedidos con diferentes teléfonos
   - Fix: rate limiting por IP (máx N pedidos por minuto)

### Alta — dificulta crecer y mantener

4. `LandingController::enviarPedido()` tiene ~280 líneas mezclando validación, cálculos, BD, Telegram
5. `landing/index.php` es una vista de **2253 líneas** — todo el HTML en un archivo
6. `AdminLandingController::guardar()` tiene ~500 líneas
7. **Sin ningún test** — los cálculos de precios son el área de mayor riesgo
8. **Sin paginación** en admin — carga hasta 300 pedidos de una vez en producción

### Media

9. Estados de pedido como **magic strings** (`'nuevo'`, `'contactado'`, etc.) sin constantes o enum
10. `landing_config` con 100+ columnas en una tabla — difícil de extender con nuevas secciones
11. Sin minificación ni versionado de assets (CSS y JS se sirven sin cache-busting)
12. Inconsistencia naming: `snake_case` en BD vs `camelCase` en PHP

---

## Hoja de ruta sugerida

### Fase 1 — Estabilizar (sin romper nada existente)

- [ ] Transacciones DB en creación de pedidos
- [ ] Centralizar lógica de precios en `PrecioCalculator`
- [ ] Constantes para estados de pedido
- [ ] Rate limiting básico por IP en `enviarPedido()`
- [ ] Paginación en dashboard de pedidos

### Fase 2 — Mejorar arquitectura

- [ ] Dividir `landing/index.php` en partials/componentes PHP
- [ ] Extraer `PedidoService` de `LandingController`
- [ ] Tests unitarios para cálculos de precios
- [ ] Minificación de CSS y JS (sin bundler: script PHP o manual)

### Fase 3 — Escalar

- [ ] Evaluar migración a Laravel (requiere hosting con Composer)
- [ ] API REST para el admin (base para futura app móvil)
- [ ] Sistema multi-tenant real (varios negocios en una instalación)
- [ ] Cache de datos estáticos (departamentos/municipios en sesión o Redis)

---

## Configuración de entornos

### Local (XAMPP)
Configura `app/config/config.php` con los valores de `.env` o crea `app/config/config.php` directamente.

### Producción (Hostinger)
Crea el archivo `~/tienda_config.php` **fuera del webroot** con:
```php
<?php
define('DB_HOST', '...');
define('DB_NAME', '...');
define('DB_USER', '...');
define('DB_PASS', '...');
putenv('TELEGRAM_BOT_TOKEN=...');
putenv('TELEGRAM_CHAT_ID=...');
```
El `config.php` lo detecta automáticamente y lo usa sobre las variables locales.

---

## Variables de entorno necesarias

| Variable | Descripción |
|---|---|
| `DB_HOST` | Host de MySQL |
| `DB_NAME` | Nombre de la base de datos |
| `DB_USER` | Usuario MySQL |
| `DB_PASS` | Contraseña MySQL |
| `TELEGRAM_BOT_TOKEN` | Token del bot para notificaciones de pedidos |
| `TELEGRAM_CHAT_ID` | Chat ID donde llegan las notificaciones |

---

## Features recomendadas para escalar el proyecto

> Análisis honesto de qué implementar, en qué orden, y qué evitar.

---

### Prioridad 1 — Mueven dinero directamente

#### 1. Seguimiento de pedido para el cliente
El cliente hoy no sabe qué pasó con su pedido. Una página pública `/pedido/{telefono}` o un link por WhatsApp que muestre el estado actual reduciría el 80% de los mensajes de "¿cuándo llega mi pedido?".
- Costo técnico: bajo
- Impacto operativo: alto

#### 2. Integración con transportadoras colombianas
Servientrega, Interrapidísimo, 472, Coordinadora. Hoy el admin pega el número de guía manualmente. Con una integración básica se puede consultar el estado automáticamente y enviarlo al cliente sin intervención manual. Estas transportadoras tienen APIs o scraping posible.

#### 3. Recuperación de carritos abandonados
Gente que llena el formulario hasta el teléfono y no lo envía. Con JS en el frontend se puede capturar ese número parcial y lanzar un mensaje WhatsApp de recuperación. En Colombia el dropoff en checkout es alto — esto puede valer 20–30% más de conversión.

#### 4. Control de inventario con alertas
Hoy se puede vender algo que no hay en stock. Mínimo viable: campo `stock` en productos, bloqueo automático cuando llega a 0, alerta por Telegram cuando queda bajo.

#### 5. Export a Excel/CSV de pedidos
El admin o contador lo va a pedir tarde o temprano. Un botón "Exportar CSV" con filtros de fecha. Una tarde de trabajo que evita horas de copiar y pegar.

---

### Prioridad 2 — Escala operativa

#### 6. Analytics de conversión internos
Facebook Pixel ya está, pero no se sabe en qué sección se va la gente, cuántos llegan al formulario, cuántos lo abren pero no envían. Un funnel interno básico (sin terceros) para saber qué secciones de la landing convierten y cuáles sobran.

#### 7. Múltiples usuarios admin con roles
Hoy es una sola cuenta. Si hay equipo, se necesitan roles: `operador` (solo ve pedidos) vs `admin` (acceso total). Sin esto no se puede delegar.

#### 8. Upsell en el checkout
Justo antes de confirmar el pedido: "¿Quieres agregar una unidad más con X% de descuento?". Con los combos y descuentos multicantidad ya existentes, el dato está — solo falta el momento UI. Es dinero directo.

#### 9. Notificaciones push al admin (PWA ya está)
El Service Worker ya existe. Con Web Push API se puede enviar notificación al celular del admin cuando llega un pedido nuevo, sin depender de Telegram. Para quien opera desde móvil es mucho más confiable.

---

### Lo que NO implementaría (aunque parezca buena idea)

| Feature | Razón para no hacerlo |
|---|---|
| **Cuentas de cliente / login de comprador** | Para el modelo contra-entrega en Colombia esto agrega fricción sin valor. La gente no vuelve a "su cuenta". |
| **Pasarela de pagos online** (por ahora) | Wompi, PayU — complejo, costoso en comisiones. El modelo contra-entrega funciona precisamente porque no piden tarjeta. Solo si el negocio lo demanda. |
| **Migrar a Laravel ahora** | El código funciona en producción. Reescribir sin tests y sin equipo es alto riesgo por ganancia arquitectónica que el cliente no ve. |

---

### Lo que haría HOY antes que cualquier feature

Antes de agregar cualquier cosa nueva: **transacciones DB en creación de pedidos**.

Hay pedidos que pueden estar quedando sin detalle de colores en producción ahora mismo. Crear pedido + `pedido_colores` son 2 queries separadas sin `BEGIN/COMMIT`. Si la segunda falla, el pedido queda incompleto.

Es media hora de trabajo y protege datos reales de clientes que ya están en producción.

```php
// Fix en LandingController::enviarPedido()
$db->beginTransaction();
try {
    $pedidoId = $pedido->crearConId($datos);
    $pedidoColor->sync($pedidoId, $colores);
    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    // manejar error
}
```

---

## Glosario del dominio

| Término | Significado |
|---|---|
| **Landing** | Página de venta pública de un producto |
| **Pedido** | Orden de compra de un cliente (no hay cuenta de usuario cliente) |
| **Combo x2** | Precio especial para llevar 2 unidades |
| **Descuento multicantidad** | Descuento automático a partir de la 2da y 3ra unidad |
| **Plantilla WA** | Template de mensaje WhatsApp parametrizado por estado de pedido |
| **Tipo de entrega** | `domicilio` (el producto llega a casa) o `oficina` (recoge en transportadora) |
| **Guía** | Número de guía de la transportadora |
