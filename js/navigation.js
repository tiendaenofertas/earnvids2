(function() {
    // 1. SELECTORES
    const toggleBtn = document.getElementById('theme-toggle');
    const body = document.body;

    // 2. FUNCIÓN DE INICIO (Evita parpadeo)
    // Verificamos localStorage
    const currentTheme = localStorage.getItem('mainter_theme');
    
    // Si hay tema guardado 'dark', lo aplicamos
    if ( currentTheme === 'dark' ) {
        body.classList.add('dark-mode');
    }

    // 3. EVENTO CLICK
    if ( toggleBtn ) {
        toggleBtn.addEventListener('click', function() {
            // Alternar clase
            body.classList.toggle('dark-mode');

            // Guardar preferencia
            let theme = 'light';
            if ( body.classList.contains('dark-mode') ) {
                theme = 'dark';
            }
            localStorage.setItem('mainter_theme', theme);
        });
    }
})();



document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('mobile-menu-toggle');
    const closeBtn  = document.getElementById('mobile-menu-close');
    const nav       = document.getElementById('site-navigation');
    const body      = document.body;

    if ( toggleBtn && nav ) {
        // Abrir Menú
        toggleBtn.addEventListener('click', function() {
            nav.classList.add('is-open');
            toggleBtn.setAttribute('aria-expanded', 'true');
            body.classList.add('menu-open'); // Para el fondo oscuro
        });

        // Cerrar Menú (Botón X)
        if ( closeBtn ) {
            closeBtn.addEventListener('click', function() {
                nav.classList.remove('is-open');
                toggleBtn.setAttribute('aria-expanded', 'false');
                body.classList.remove('menu-open');
            });
        }

        // Cerrar al hacer clic fuera (Opcional pero recomendado)
        document.addEventListener('click', function(event) {
            if ( nav.classList.contains('is-open') && !nav.contains(event.target) && !toggleBtn.contains(event.target) ) {
                nav.classList.remove('is-open');
                toggleBtn.setAttribute('aria-expanded', 'false');
                body.classList.remove('menu-open');
            }
        });
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('mobile-menu-toggle');
    const nav       = document.getElementById('site-navigation');
    const body      = document.body;

    // 1. Lógica del Menú Principal (Off-Canvas)
    if ( toggleBtn && nav ) {
        toggleBtn.addEventListener('click', function() {
            nav.classList.add('is-open');
            toggleBtn.setAttribute('aria-expanded', 'true');
            body.classList.add('menu-open');
        });

        const closeBtn = document.getElementById('mobile-menu-close');
        if ( closeBtn ) {
            closeBtn.addEventListener('click', function() {
                nav.classList.remove('is-open');
                toggleBtn.setAttribute('aria-expanded', 'false');
                body.classList.remove('menu-open');
            });
        }

        // Cerrar al tocar fuera
        document.addEventListener('click', function(event) {
            if ( nav.classList.contains('is-open') && !nav.contains(event.target) && !toggleBtn.contains(event.target) ) {
                nav.classList.remove('is-open');
                toggleBtn.setAttribute('aria-expanded', 'false');
                body.classList.remove('menu-open');
            }
        });
    }

    // 2. LÓGICA DE SUBMENÚS (ACORDEÓN MÓVIL)
    // Solo se ejecuta si estamos en pantalla móvil/tablet
    if ( window.innerWidth < 992 ) {
        const menuItems = nav.querySelectorAll('.menu-item-has-children');

        menuItems.forEach(item => {
            // Creamos el botón de flecha
            const dropdownBtn = document.createElement('button');
            dropdownBtn.className = 'dropdown-toggle';
            dropdownBtn.setAttribute('aria-expanded', 'false');
            
            // Icono Chevron (CSS Puro) dentro del botón
            dropdownBtn.innerHTML = '<span class="chevron"></span>';

            // Insertamos el botón DESPUÉS del enlace (<a>)
            const link = item.querySelector('a');
            if ( link ) {
                link.after(dropdownBtn);
            }

            // Al hacer clic en la flecha
            dropdownBtn.addEventListener('click', function(e) {
                e.preventDefault(); // Evita saltos
                e.stopPropagation(); // Evita cerrar el menú principal
                
                // Alternar clase 'active' en el LI padre
                const isOpen = item.classList.toggle('submenu-active');
                dropdownBtn.setAttribute('aria-expanded', isOpen);
            });
        });
    }
});

/**
 * ♿ Parche de Accesibilidad: "El Vigilante"
 * Detecta botones dropdown dinámicos y les pone etiqueta al instante.
 */
(function() {
    // Función que busca y arregla los botones
    function fixDropdownButtons() {
        // Seleccionamos los botones que NO tengan etiqueta todavía
        const buttons = document.querySelectorAll('button.dropdown-toggle:not([aria-label])');
        
        buttons.forEach(function(btn) {
            btn.setAttribute('aria-label', 'Desplegar submenú');
            // Opcional: Agregar texto oculto dentro si Lighthouse se pone muy estricto
            if (btn.innerHTML.trim() === '') {
                 // Si el botón está vacío (sin icono), le ponemos algo visual o un span
            }
        });
    }

    // 1. Ejecutar inmediatamente al cargar todo
    window.addEventListener('load', fixDropdownButtons);
    
    // 2. Ejecutar también en DOMContentLoaded (por si acaso)
    document.addEventListener('DOMContentLoaded', fixDropdownButtons);

    // 3. LA CLAVE: Observar cambios en el menú (MutationObserver)
    // Si algún script del tema inyecta el botón tarde, esto lo detecta.
    const menuObserver = new MutationObserver(function(mutations) {
        fixDropdownButtons();
    });

    // Empezar a vigilar el cuerpo de la página
    const target = document.body;
    if (target) {
        menuObserver.observe(target, { childList: true, subtree: true });
    }
})();