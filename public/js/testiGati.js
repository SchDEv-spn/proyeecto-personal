
// ===================================================
// 🚀 GATILLOS DE CONVERSIÓN - VERSIÓN SIMPLIFICADA
// ===================================================

document.addEventListener('DOMContentLoaded', function () {

    // 1. GATILLO DE ESCASEZ (Stock Counter)
    function showStockCounter() {
        // Stock simulado: entre 8 y 15 unidades
        const stock = Math.floor(Math.random() * 8) + 8;
        let sold = Math.floor(Math.random() * (stock - 5)) + 3; // Ya vendidas

        // Crear contador de stock
        const stockEl = document.createElement('div');
        stockEl.className = 'stock-counter';
        stockEl.innerHTML = `
            <div class="stock-header">
                <span class="stock-icon">🔥</span>
                <span class="stock-title">STOCK LIMITADO</span>
                <span class="stock-update">Actualizado hace 1 min</span>
            </div>
            
            <div class="stock-progress-container">
                <div class="stock-progress-bar" style="width: ${(sold / stock) * 100}%"></div>
            </div>
            
            <div class="stock-details">
                <div class="stock-sold">
                    <span class="sold-count">${sold}</span>
                    <span class="sold-label">vendidos</span>
                </div>
                
                <div class="stock-remaining">
                    <span class="remaining-icon">⚠️</span>
                    <span class="remaining-text">Quedan ${stock - sold} unidades</span>
                </div>
            </div>
            
            <div class="stock-warning">
                <span class="warning-icon">⏰</span>
                <span class="warning-text">Última oportunidad de compra</span>
            </div>
        `;

        // Insertar después del precio
        const priceBox = document.querySelector('.price-box');
        if (priceBox && priceBox.parentNode) {
            priceBox.parentNode.insertBefore(stockEl, priceBox.nextSibling);
        }

        // Animar entrada
        setTimeout(() => {
            stockEl.style.opacity = '1';
            stockEl.style.transform = 'translateY(0)';
        }, 100);

        // Actualizar stock cada 45 segundos
        setInterval(() => {
            sold = Math.min(stock, sold + Math.floor(Math.random() * 2) + 1);
            const remaining = stock - sold;

            // Actualizar elementos
            const progressBar = stockEl.querySelector('.stock-progress-bar');
            const remainingText = stockEl.querySelector('.remaining-text');
            const soldCount = stockEl.querySelector('.sold-count');
            const warningText = stockEl.querySelector('.warning-text');

            if (progressBar) progressBar.style.width = `${(sold / stock) * 100}%`;
            if (soldCount) soldCount.textContent = sold;

            // Efectos especiales cuando queda poco stock
            if (remaining <= 3) {
                stockEl.style.background = 'linear-gradient(135deg, #fff5f5, #ffe5e5)';
                stockEl.style.border = '2px solid #ff4757';

                if (remainingText) {
                    remainingText.innerHTML = `¡SOLO ${remaining} ${remaining === 1 ? 'UNIDAD' : 'UNIDADES'}!`;
                    remainingText.style.color = '#ff4757';
                    remainingText.style.fontWeight = '900';
                }

                if (warningText) {
                    warningText.innerHTML = '🚨 ¡APÚRATE! Últimas unidades disponibles';
                    warningText.style.color = '#ff4757';
                }

                // Parpadeo cuando queda 1 unidad
                if (remaining === 1) {
                    stockEl.style.animation = 'lastUnitPulse 1s infinite';
                }
            }

            if (remaining === 0) {
                remainingText.innerHTML = '❌ AGOTADO';
                warningText.innerHTML = 'Lamentamos, ya no hay stock disponible';
                stockEl.style.background = 'linear-gradient(135deg, #f8f9fa, #e9ecef)';
                stockEl.style.border = '2px solid #ddd';
                stockEl.style.color = '#999';
            }

            // Actualizar timestamp
            const updateEl = stockEl.querySelector('.stock-update');
            if (updateEl) {
                updateEl.textContent = 'Actualizado ahora';
                setTimeout(() => {
                    updateEl.textContent = 'Actualizado hace 1 min';
                }, 5000);
            }

        }, 45000); // Cada 45 segundos
    }

    // 2. GATILLO DE PRUEBA SOCIAL EN TIEMPO REAL
    function showRealTimePurchases() {
        const cities = ['Bogotá', 'Medellín', 'Cali', 'Barranquilla', 'Cartagena', 'Bucaramanga', 'Pereira'];
        const names = ['Ana', 'Carlos', 'María', 'Pedro', 'Laura', 'Juan', 'Sofía', 'Andrés', 'Camila', 'David'];

        // Crear contenedor para notificaciones
        const notificationContainer = document.createElement('div');
        notificationContainer.className = 'purchase-notifications';
        notificationContainer.style.cssText = `
            position: fixed;
            bottom: 120px;
            : 20px;
            z-index: 9998;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 300px;
        `;
        document.body.appendChild(notificationContainer);

        // Mostrar primera notificación después de 18 segundos
        setTimeout(() => {
            showPurchaseNotification();
        }, 18000);

        // Mostrar notificaciones periódicamente
        setInterval(() => {
            // Solo mostrar si no hay muchas notificaciones activas
            if (notificationContainer.children.length < 3) {
                showPurchaseNotification();
            }
        }, 65000); // Cada 65 segundos

        function showPurchaseNotification() {
            const randomCity = cities[Math.floor(Math.random() * cities.length)];
            const randomName = names[Math.floor(Math.random() * names.length)];
            const minutesAgo = Math.floor(Math.random() * 8) + 1;

            const notification = document.createElement('div');
            notification.className = 'purchase-notification';
            notification.innerHTML = `
                <div class="purchase-badge">
                    <span class="badge-icon">✅</span>
                    <span class="badge-text">COMPRA VERIFICADA</span>
                </div>
                <div class="purchase-content">
                    <span class="purchase-text">
                        <strong>${randomName}</strong> de ${randomCity} compró hace ${minutesAgo} min
                    </span>
                    <span class="purchase-time">justo ahora</span>
                </div>
            `;

            notificationContainer.appendChild(notification);

            // Animar entrada
            setTimeout(() => {
                notification.style.opacity = '1';
                notification.style.transform = 'translateX(0)';
            }, 10);

            // Auto-remover después de 12 segundos
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 500);
            }, 12000);
        }
    }

    // 3. GATILLO DE "ÚLTIMAS UNIDADES" DINÁMICO
    function updateDynamicStock() {
        // Elementos que mostrarán el stock dinámico
        const stockElements = document.querySelectorAll('.stock-remaining, .last-units, .units-left');
        if (stockElements.length === 0) return;

        // Stock inicial aleatorio entre 3 y 12 unidades
        let dynamicStock = Math.floor(Math.random() * 10) + 3;

        function updateStockDisplay() {
            stockElements.forEach(el => {
                if (dynamicStock <= 3) {
                    // Estilo URGENTE
                    el.innerHTML = `
                        <span class="urgent-icon">🚨</span>
                        <span class="urgent-text">¡ÚLTIMAS ${dynamicStock} UNIDADES!</span>
                        <span class="urgent-sub">No pierdas esta oportunidad</span>
                    `;
                    el.style.background = 'linear-gradient(135deg, #ff4757, #ff3838)';
                    el.style.color = 'white';
                    el.style.fontWeight = '900';
                    el.style.animation = 'urgentPulse 1.5s infinite';

                    // Añadir contador si no existe
                    if (!el.querySelector('.stock-timer')) {
                        const timer = document.createElement('div');
                        timer.className = 'stock-timer';
                        timer.innerHTML = 'Oferta termina en: <span class="timer-count">05:00</span>';
                        el.appendChild(timer);

                        // Iniciar contador de 5 minutos
                        startStockTimer(timer.querySelector('.timer-count'));
                    }
                } else if (dynamicStock <= 6) {
                    // Estilo ADVERTENCIA
                    el.innerHTML = `
                        <span class="warning-icon">⚠️</span>
                        <span class="warning-text">Solo quedan ${dynamicStock} unidades</span>
                    `;
                    el.style.background = 'linear-gradient(135deg, #ffc107, #ff9800)';
                    el.style.color = '#333';
                    el.style.fontWeight = '700';
                } else {
                    // Stock normal
                    el.innerHTML = `
                        <span class="normal-icon">📦</span>
                        <span class="normal-text">Disponible: ${dynamicStock} unidades</span>
                    `;
                    el.style.background = 'linear-gradient(135deg, #e8f5e9, #c8e6c9)';
                    el.style.color = '#2e7d32';
                }
            });
        }

        function startStockTimer(timerElement) {
            let timeLeft = 5 * 60; // 5 minutos en segundos

            const timerInterval = setInterval(() => {
                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    timerElement.textContent = '00:00';
                    // Reducir stock a 0 cuando termina el tiempo
                    dynamicStock = 0;
                    updateStockDisplay();
                    return;
                }

                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                timeLeft--;

                // Reducir stock aleatoriamente durante la cuenta regresiva
                if (timeLeft % 30 === 0 && dynamicStock > 0) { // Cada 30 segundos
                    dynamicStock -= Math.floor(Math.random() * 2);
                    updateStockDisplay();
                }
            }, 1000);
        }

        // Actualizar stock cada minuto
        const stockInterval = setInterval(() => {
            if (dynamicStock > 0) {
                // Reducir stock entre 0 y 2 unidades por minuto
                dynamicStock -= Math.floor(Math.random() * 3);
                if (dynamicStock < 0) dynamicStock = 0;
                updateStockDisplay();

                // Si se agota el stock, detener el intervalo
                if (dynamicStock === 0) {
                    clearInterval(stockInterval);
                }
            }
        }, 60000); // Cada minuto

        // Inicializar display
        updateStockDisplay();

        // También actualizar cuando el usuario hace scroll (para más urgencia)
        let lastScrollUpdate = 0;
        window.addEventListener('scroll', () => {
            const now = Date.now();
            if (now - lastScrollUpdate > 10000) { // Cada 10 segundos máximo
                // 30% de probabilidad de reducir stock durante scroll
                if (Math.random() < 0.3 && dynamicStock > 0) {
                    dynamicStock--;
                    updateStockDisplay();
                }
                lastScrollUpdate = now;
            }
        });
    }

    // 4. GATILLO DE SCROLL TRIGGERED OFFERS (Versión simplificada)
    function setupScrollTriggers() {
        const offers = [
            {
                selector: '.hero',
                message: '🔥 ¡PRIMERA OPORTUNIDAD! No dejes pasar esta oferta',
                icon: '🎯'
            },
            {
                selector: '.benefits-section',
                message: '✅ Estos beneficios pueden ser tuyos hoy mismo',
                icon: '✨'
            },
            {
                selector: '.testimonials-section',
                message: '⭐ Tú puedes ser el próximo en dar un testimonio así',
                icon: '💬'
            },
            {
                selector: '.price-box',
                message: '💰 Este precio es especial por tiempo limitado',
                icon: '⏰'
            },
            {
                selector: '#form-pedido',
                message: '🚀 ¡ÚLTIMO PASO! Completa y recibe tu pedido',
                icon: '📦'
            }
        ];

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const offer = offers.find(o =>
                        entry.target.matches(o.selector) || entry.target.closest(o.selector)
                    );

                    if (offer && !entry.target.dataset.scrollTriggered) {
                        entry.target.dataset.scrollTriggered = 'true';
                        showScrollOffer(offer);
                    }
                }
            });
        }, {
            threshold: 0.3,
            rootMargin: '0px 0px -100px 0px'
        });

        offers.forEach(offer => {
            const element = document.querySelector(offer.selector);
            if (element) observer.observe(element);
        });

        function showScrollOffer(offer) {
            const notification = document.createElement('div');
            notification.className = 'scroll-offer';
            notification.innerHTML = `
                <div class="scroll-offer-icon">${offer.icon}</div>
                <div class="scroll-offer-content">
                    <span class="scroll-offer-text">${offer.message}</span>
                </div>
                <button class="scroll-offer-close">×</button>
            `;

            document.body.appendChild(notification);

            // Animar entrada
            setTimeout(() => {
                notification.classList.add('visible');
            }, 100);

            // Auto remover después de 5 segundos
            setTimeout(() => {
                notification.classList.remove('visible');
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 500);
            }, 5000);

            // Botón cerrar
            notification.querySelector('.scroll-offer-close').addEventListener('click', () => {
                notification.classList.remove('visible');
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 500);
            });
        }
    }

    // ===================================================
    // INICIALIZAR LOS GATILLOS
    // ===================================================

    // Esperar a que la página cargue completamente
    setTimeout(() => {
        // 1. Gatillo de Escasez (Stock Counter)
        showStockCounter();

        // 2. Gatillo de Prueba Social
        showRealTimePurchases();

        // 3. Gatillo de Últimas Unidades Dinámico
        updateDynamicStock();

        // 4. Gatillo de Scroll Triggers
        setupScrollTriggers();

        console.log('✅ Gatillos de conversión activados');
    }, 1500);

    // ===================================================
    // CSS DINÁMICO PARA LOS GATILLOS
    // ===================================================

    const style = document.createElement('style');
    style.textContent = `
    /* STOCK COUNTER */
    .stock-counter {
        background: linear-gradient(135deg, #fff9e6, #ffe8a1);
        border: 2px solid #ffc107;
        border-radius: 15px;
        padding: 20px;
        margin: 25px 0;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.5s ease;
        position: relative;
        overflow: hidden;
    }
    
    .stock-counter::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #ffc107, #ff9800);
    }
    
    .stock-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 15px;
        font-size: 14px;
    }
    
    .stock-icon {
        font-size: 1.2rem;
    }
    
    .stock-title {
        font-weight: 800;
        color: #d97706;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        flex-grow: 1;
        margin-left: 8px;
    }
    
    .stock-update {
        color: #666;
        font-size: 12px;
        font-weight: 500;
    }
    
    .stock-progress-container {
        height: 10px;
        background: #f0f0f0;
        border-radius: 5px;
        overflow: hidden;
        margin: 15px 0;
        position: relative;
    }
    
    .stock-progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #25D366, #1DA851);
        border-radius: 5px;
        transition: width 1s ease;
        position: relative;
        overflow: hidden;
    }
    
    .stock-progress-bar::after {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
        animation: progressShimmer 2s infinite;
    }
    
    .stock-details {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 15px 0;
    }
    
    .stock-sold {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
    }
    
    .sold-count {
        font-size: 1.8rem;
        font-weight: 900;
        color: #25D366;
        line-height: 1;
    }
    
    .sold-label {
        font-size: 12px;
        color: #666;
        margin-top: 2px;
    }
    
    .stock-remaining {
        display: flex;
        align-items: center;
        gap: 8px;
        background: rgba(255, 71, 87, 0.1);
        padding: 8px 15px;
        border-radius: 20px;
        border: 1px solid rgba(255, 71, 87, 0.2);
    }
    
    .remaining-icon {
        font-size: 1.2rem;
    }
    
    .remaining-text {
        font-weight: 700;
        color: #333;
    }
    
    .stock-warning {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin-top: 15px;
        padding: 10px;
        background: rgba(255, 193, 7, 0.1);
        border-radius: 10px;
        font-size: 13px;
        color: #d97706;
    }
    
    .warning-icon {
        font-size: 1.1rem;
    }
    
    .warning-text {
        font-weight: 600;
    }
    
    /* NOTIFICACIONES DE COMPRA EN TIEMPO REAL - MODIFICADO */
    .purchase-notification {
        background: linear-gradient(135deg, #25D366, #128C7E);
        color: white;
        border-radius: 10px;
        padding: 12px;
        box-shadow: 0 6px 20px rgba(37, 211, 102, 0.25);
        margin-bottom: 8px;
        opacity: 0;
        transform: translateX(-100%) scale(0.9);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        max-width: 220px;
        font-size: 12px;
    }
    
    .purchase-notification.visible {
        opacity: 1;
        transform: translateX(0) scale(1);
    }
    
    .purchase-badge {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 8px;
    }
    
    .badge-icon {
        font-size: 1rem;
    }
    
    .badge-text {
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        background: rgba(255,255,255,0.2);
        padding: 2px 6px;
        border-radius: 8px;
    }
    
    .purchase-content {
        font-size: 12px;
        line-height: 1.3;
    }
    
    .purchase-text {
        display: block;
        margin-bottom: 4px;
    }
    
    .purchase-time {
        font-size: 10px;
        opacity: 0.8;
        font-weight: 500;
    }
    
    /* CONTENEDOR DE NOTIFICACIONES */
    .purchase-notifications-container {
        position: fixed;
        top: 20px;
        left: 20px;
        z-index: 9997;
        display: flex;
        flex-direction: column;
        max-height: 70vh;
        overflow-y: auto;
        padding-right: 5px;
    }
    
    /* SCROLL TRIGGERED OFFERS - MODIFICADO */
    .scroll-offer {
        position: fixed;
        top: 20px;
        left: 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px;
        padding: 12px;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.25);
        z-index: 9998;
        max-width: 220px;
        display: flex;
        align-items: center;
        gap: 10px;
        opacity: 0;
        transform: translateX(-100%) scale(0.9);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .scroll-offer.visible {
        opacity: 1;
        transform: translateX(0) scale(1);
    }
    
    .scroll-offer-icon {
        font-size: 1.3rem;
        flex-shrink: 0;
    }
    
    .scroll-offer-content {
        flex-grow: 1;
    }
    
    .scroll-offer-text {
        font-size: 12px;
        font-weight: 600;
        line-height: 1.3;
        display: block;
    }
    
    .scroll-offer-close {
        background: rgba(255,255,255,0.2);
        border: none;
        color: white;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        cursor: pointer;
        font-size: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: background 0.3s ease;
        padding: 0;
    }
    
    .scroll-offer-close:hover {
        background: rgba(255,255,255,0.3);
    }
    
    /* ANIMACIONES */
    @keyframes progressShimmer {
        0% { left: -100%; }
        100% { left: 100%; }
    }
    
    @keyframes lastUnitPulse {
        0%, 100% { 
            box-shadow: 0 10px 30px rgba(255, 71, 87, 0.2);
        }
        50% { 
            box-shadow: 0 10px 40px rgba(255, 71, 87, 0.4);
        }
    }
    
    @keyframes urgentPulse {
        0%, 100% { 
            transform: scale(1);
            box-shadow: 0 10px 30px rgba(255, 71, 87, 0.3);
        }
        50% { 
            transform: scale(1.02);
            box-shadow: 0 10px 40px rgba(255, 71, 87, 0.5);
        }
    }
    
    /* RESPONSIVE */
    @media (max-width: 768px) {
        .stock-counter {
            margin: 15px 0;
            padding: 15px;
        }
        
        .purchase-notifications-container {
            top: 15px;
            left: 10px;
        }
        
        .scroll-offer {
            top: 15px;
            left: 10px;
            max-width: 200px;
        }
        
        .purchase-notification {
            max-width: 200px;
            padding: 10px;
        }
    }
    
    @media (max-width: 480px) {
        .stock-counter {
            padding: 12px;
        }
        
        .stock-details {
            flex-direction: column;
            align-items: stretch;
            gap: 10px;
        }
        
        .stock-sold {
            align-items: center;
        }
        
        .purchase-notifications-container {
            top: 10px;
            left: 10px;
            max-width: 180px;
        }
        
        .scroll-offer {
            top: 10px;
            left: 10px;
            max-width: 180px;
            padding: 10px;
        }
        
        .purchase-notification {
            max-width: 180px;
            padding: 8px;
            font-size: 11px;
        }
        
        .scroll-offer-text,
        .purchase-content {
            font-size: 11px;
        }
        
        .badge-text {
            font-size: 9px;
            padding: 2px 5px;
        }
        
        .purchase-time {
            font-size: 9px;
        }
    }
`;
    document.head.appendChild(style);

    // ===================================================
    // INICIALIZAR SLIDER DE TESTIMONIOS (de tu código)
    // ===================================================

    const track = document.getElementById('sliderTrack');
    const slides = document.querySelectorAll('.testimonial-slide');
    const nextBtn = document.querySelector('.next-btn');
    const prevBtn = document.querySelector('.prev-btn');
    const dots = document.querySelectorAll('.dot');

    let counter = 1;
    const size = slides[0].clientWidth;

    // Inicializar posición
    track.style.transform = 'translateX(' + (-size * counter) + 'px)';

    nextBtn.addEventListener('click', () => {
        if (counter >= slides.length - 1) return;
        track.style.transition = "transform 0.4s ease-in-out";
        counter++;
        track.style.transform = 'translateX(' + (-size * counter) + 'px)';
        updateDots();
    });

    prevBtn.addEventListener('click', () => {
        if (counter <= 0) return;
        track.style.transition = "transform 0.4s ease-in-out";
        counter--;
        track.style.transform = 'translateX(' + (-size * counter) + 'px)';
        updateDots();
    });

    track.addEventListener('transitionend', () => {
        if (slides[counter].dataset.index === '0-clone') {
            track.style.transition = "none";
            counter = 1;
            track.style.transform = 'translateX(' + (-size * counter) + 'px)';
        }
        if (slides[counter].dataset.index === '4-clone') {
            track.style.transition = "none";
            counter = slides.length - 2;
            track.style.transform = 'translateX(' + (-size * counter) + 'px)';
        }
    });

    function updateDots() {
        let dotActive = counter - 1;
        if (counter > 5) dotActive = 0;
        if (counter < 1) dotActive = 4;
        dots.forEach(dot => dot.classList.remove('active'));
        dots[dotActive].classList.add('active');
    }

});
