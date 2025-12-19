document.addEventListener('DOMContentLoaded', function () {
    initTipoEntrega();
    initDepartamentoMunicipio();
    initSlider();
    initCountdown();
    initAccordion();
});

// =====================
// Departamento / Municipio
// =====================
function initDepartamentoMunicipio() {
    const selectDept = document.getElementById('departamento');
    const selectMun  = document.getElementById('municipio');

    if (!selectDept || !selectMun) return;

    // Ajusta la ruta si tu proyecto NO se llama tienda_mvc
    fetch('/tienda_mvc/public/js/colombia.json')
        .then(response => {
            if (!response.ok) {
                throw new Error('No se pudo cargar colombia.json');
            }
            return response.json();
        })
        .then(data => {
            // data: [{ departamento: "Antioquia", ciudades: ["Medellín", ...] }, ...]
            const excluidos = [
                "Amazonas",
                "Guaviare",
                "Vichada",
                "San Andrés y Providencia",
                "San Andres y Providencia",
                "San Andrés Islas",
                "San Andres Islas"
            ];

            const departamentos = data.filter(d => !excluidos.includes(d.departamento));

            const oldDept = selectDept.dataset.old || '';
            const oldMun  = selectMun.dataset.old || '';

            // Poblar departamentos
            departamentos.forEach(dep => {
                const opt = document.createElement('option');
                opt.value = dep.departamento;
                opt.textContent = dep.departamento;
                selectDept.appendChild(opt);
            });

            // Restaurar selección anterior si la hay
            if (oldDept) {
                selectDept.value = oldDept;
            }

            function poblarMunicipios() {
                const deptSeleccionado = selectDept.value;

                // Limpiar municipios
                selectMun.innerHTML = '';

                if (!deptSeleccionado) {
                    const placeholder = document.createElement('option');
                    placeholder.value = '';
                    placeholder.textContent = 'Selecciona primero un departamento';
                    selectMun.appendChild(placeholder);
                    return;
                }

                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = 'Selecciona un municipio';
                selectMun.appendChild(placeholder);

                const depObj = departamentos.find(d => d.departamento === deptSeleccionado);
                const municipios = depObj && Array.isArray(depObj.ciudades)
                    ? depObj.ciudades
                    : [];

                municipios.forEach(m => {
                    const opt = document.createElement('option');
                    opt.value = m;
                    opt.textContent = m;
                    selectMun.appendChild(opt);
                });
            }

            // Evento cambio de departamento
            selectDept.addEventListener('change', poblarMunicipios);

            // Poblar municipios al cargar (por si ya venía un departamento seleccionado)
            poblarMunicipios();

            // Restaurar municipio anterior si aplica
            if (oldMun) {
                selectMun.value = oldMun;
            }
        })
        .catch(err => {
            console.error('Error cargando departamentos/municipios:', err);
        });
}

// =====================
// Tipo de entrega (domicilio / oficina)
// =====================
function initTipoEntrega() {
    const radiosEntrega  = document.querySelectorAll('input[name="tipo_entrega"]');
    const grupoDireccion = document.getElementById('grupo-direccion');
    const inputDireccion = document.getElementById('direccion');

    if (!radiosEntrega.length || !grupoDireccion || !inputDireccion) return;

    function actualizarDireccion() {
        let tipoSeleccionado = '';
        radiosEntrega.forEach(r => {
            if (r.checked) tipoSeleccionado = r.value;
        });

        if (tipoSeleccionado === 'domicilio') {
            grupoDireccion.style.display = 'block';
            inputDireccion.setAttribute('required', 'required');
        } else {
            grupoDireccion.style.display = 'none';
            inputDireccion.removeAttribute('required');
            inputDireccion.value = '';
        }
    }

    radiosEntrega.forEach(r => {
        r.addEventListener('change', actualizarDireccion);
    });

    // Ejecutar una vez al cargar
    actualizarDireccion();
}

// =====================
// Slider automático
// =====================
function initSlider() {
    const slider = document.getElementById('slider');
    const slides = document.getElementById('slides');

    if (!slider || !slides) return;

    const total = slides.children.length;
    if (!total) return;

    // Ancho proporcional para que el translateX en % funcione bien
    const perSlide = 100 / total;
    slides.style.width = `${total * 100}%`;

    Array.from(slides.children).forEach(child => {
        child.style.width = `${perSlide}%`;
        child.style.flexShrink = '0';
    });

    let index = 0;

    function goToSlide(i) {
        slides.style.transform = `translateX(-${perSlide * i}%)`;
    }

    setInterval(() => {
        index = (index + 1) % total;
        goToSlide(index);
    }, 3000);
}

// =====================
// Countdown (1 hora)
// =====================
function initCountdown() {
    const countdownEl = document.getElementById('countdown-timer');
    if (!countdownEl) return;

    let tiempoRestante = 60 * 60; // 1 hora en segundos

    function actualizarCountdown() {
        const minutos = String(Math.floor(tiempoRestante / 60)).padStart(2, '0');
        const segundos = String(tiempoRestante % 60).padStart(2, '0');
        countdownEl.textContent = `${minutos}:${segundos}`;

        if (tiempoRestante > 0) {
            tiempoRestante--;
        }
    }

    actualizarCountdown();
    setInterval(actualizarCountdown, 1000);
}

// =====================
// Acordeón FAQ
// =====================
function initAccordion() {
    const headers = document.querySelectorAll('.accordion-header');
    if (!headers.length) return;

    headers.forEach(header => {
        header.addEventListener('click', () => {
            const body = header.nextElementSibling;
            const isVisible = body.style.display === 'block';

            // Cerrar todos
            document.querySelectorAll('.accordion-body').forEach(b => {
                b.style.display = 'none';
            });

            // Abrir el que se clickea si no estaba abierto
            if (!isVisible) {
                body.style.display = 'block';
            }
        });
    });
}
