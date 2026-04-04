document.addEventListener('DOMContentLoaded', function() {
    
    // --- VARIABLES ---
    const header = document.getElementById('masthead');
    const toggleBtn = document.getElementById('mobile-menu-toggle');
    const offcanvas = document.getElementById('mobile-offcanvas');
    const overlay = document.getElementById('mobile-menu-overlay');
    const body = document.body;
    let isMenuOpen = false;

    // --- SCROLL ANIMATION ---
    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
            // Usuario ha hecho scroll: Añadir fondo blanco y sombra
            header.classList.add('bg-white', 'shadow-md', 'py-2');
            header.classList.remove('py-4'); // Reduce padding para efecto "compacto"
        } else {
            // Usuario está arriba: Transparente o estado inicial
            header.classList.remove('bg-white', 'shadow-md', 'py-2');
            header.classList.add('py-4');
        }
    });

    // --- OFFCANVAS LOGIC ---
    function toggleMenu() {
        isMenuOpen = !isMenuOpen;
        
        // Toggle Icon Animation (Simple cross effect)
        const spans = toggleBtn.querySelectorAll('span');
        if(isMenuOpen) {
            spans[0].classList.add('rotate-45', 'translate-y-2.5');
            spans[1].classList.add('opacity-0');
            spans[2].classList.add('-rotate-45', '-translate-y-2.5');
            
            // Show Menu
            offcanvas.classList.remove('translate-x-full');
            overlay.classList.remove('hidden');
            setTimeout(() => overlay.classList.remove('opacity-0'), 10); // Fade in
            body.classList.add('overflow-hidden'); // Prevent body scroll
        } else {
            spans[0].classList.remove('rotate-45', 'translate-y-2.5');
            spans[1].classList.remove('opacity-0');
            spans[2].classList.remove('-rotate-45', '-translate-y-2.5');
            
            // Hide Menu
            offcanvas.classList.add('translate-x-full');
            overlay.classList.add('opacity-0');
            setTimeout(() => overlay.classList.add('hidden'), 300); // Wait for fade out
            body.classList.remove('overflow-hidden');
        }
    }

    toggleBtn.addEventListener('click', toggleMenu);
    overlay.addEventListener('click', toggleMenu);
});