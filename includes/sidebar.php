<div id="mobile-overlay" class="mobile-overlay"></div>

<div class="sidebar" id="main-sidebar">
    <div class="logo">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/>
        </svg>
        <h1>XVIDS<span>PRO</span></h1>
    </div>
    
    <nav>
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="/admin/" class="nav-link">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="/upload.php" class="nav-link">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z"/></svg>
                    <span>Subir</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="/admin/videos.php" class="nav-link">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 3v2h-2V3H8v2H6V3H4v18h2v-2h2v2h8v-2h2v2h2V3h-2zM8 17H6v-2h2v2zm0-4H6v-2h2v2zm0-4H6V7h2v2zm10 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z"/></svg>
                    <span>Mis Videos</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="/admin/domains.php" class="nav-link">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
                    <span>Dominios</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="/mes_gratis.php" class="nav-link" style="color: var(--accent-green);">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.41l9 9c.36.36.86.58 1.41.58s1.05-.22 1.41-.59l7-7c.36-.36.58-.86.58-1.41s-.22-1.05-.58-1.41zM5.5 7C4.67 7 4 6.33 4 5.5S4.67 4 5.5 4 7 4.67 7 5.5 6.33 7 5.5 7z"/></svg>
                    <span style="font-weight: bold;">MESGratis 🆓</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="/account.php" class="nav-link">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>
                    <span>Cuenta</span>
                </a>
            </li>
            
            <?php if (isAdmin()): ?>
            <li class="nav-section">
                <li class="nav-item">
                    <a href="/admin/notifications.php" class="nav-link">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                        <span>Notificaciones</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="/admin/licenses.php" class="nav-link">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg>
                        <span>Licencias</span>
                    </a>
                </li>
          
                <li class="nav-item">
                    <a href="/admin/users.php" class="nav-link">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                        <span>Usuarios</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="/admin/payments.php" class="nav-link">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
                        <span>Pagos</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="/admin/storage.php" class="nav-link">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M2 20h20v-4H2v4zm2-3h2v2H4v-2zM2 4v4h20V4H2zm4 3H4V5h2v2zm-4 7h20v-4H2v4zm2-3h2v2H4v-2z"/></svg>
                        <span>Almacenamiento</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="/admin/payment_settings.php" class="nav-link">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg>
                        <span>Config. de Pago</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="/admin/settings.php" class="nav-link">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H8c0-2.21 1.79-4 4-4s4 1.79 4 4c0 .88-.36 1.68-.93 2.25z"/></svg>
                        <span>Configuración</span>
                    </a>
                </li>
            </li>
            <?php endif; ?>
            
            <li class="nav-section">
                <li class="nav-item">
                    <a href="/logout.php" class="nav-link">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>
                        <span>Cerrar Sesión</span>
                    </a>
                </li>
            </li>
        </ul>
    </nav>
</div>

<button id="menu-toggle" class="menu-toggle" style="display: none;">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor">
        <path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/>
    </svg>
</button>

<style>
    /* 📱 ESTILOS EXCLUSIVOS PARA MÓVILES */
    .mobile-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9998; opacity: 0; visibility: hidden; transition: all 0.3s ease; backdrop-filter: blur(3px); }
    .mobile-overlay.active { opacity: 1; visibility: visible; }
    
    @media (max-width: 768px) {
        /* Ocultamos el sidebar por defecto fuera de la pantalla */
        .sidebar { position: fixed; top: 0; left: -280px; width: 260px; height: 100vh; z-index: 9999; transition: all 0.3s ease; box-shadow: 5px 0 15px rgba(0,0,0,0.5); overflow-y: auto; }
        .sidebar.active { left: 0; }
        
        /* Ajustamos el contenido para que no tenga márgenes forzados */
        .main-content { margin-left: 0 !important; padding: 20px 15px; padding-top: 80px; width: 100%; box-sizing: border-box; }
        
        /* Botón hamburguesa fijo arriba a la izquierda */
        .menu-toggle { display: flex !important; align-items: center; justify-content: center; position: fixed; top: 15px; left: 15px; width: 45px; height: 45px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px; color: #fff; z-index: 10000; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 10px rgba(0,0,0,0.3); }
        .menu-toggle:hover { border-color: var(--accent-green); color: var(--accent-green); }
        
        /* La campana de notificaciones se mueve a la derecha para no chocar */
        .notif-widget { top: 15px !important; right: 15px !important; }
    }

    /* 🔔 ESTILOS DEL WIDGET DE NOTIFICACIONES */
    .notif-widget { position: fixed; top: 20px; right: 30px; z-index: 9999; }
    .bell-btn { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 50%; width: 45px; height: 45px; cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--text-primary); transition: 0.2s; position: relative; box-shadow: 0 4px 10px rgba(0,0,0,0.3); }
    .bell-btn:hover { border-color: var(--accent-green); color: var(--accent-green); transform: scale(1.05); }
    .bell-badge { position: absolute; top: -5px; right: -5px; background: #ff3b3b; color: #fff; font-size: 11px; font-weight: bold; padding: 2px 6px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.5); }
    
    .notif-dropdown { position: absolute; top: 55px; right: 0; width: 340px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.6); overflow: hidden; display: flex; flex-direction: column; max-height: 450px; opacity: 0; pointer-events: none; transform: translateY(-10px); transition: all 0.2s; }
    .notif-dropdown.show { opacity: 1; pointer-events: auto; transform: translateY(0); }
    
    .notif-header { padding: 15px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(0,0,0,0.2); }
    .notif-header h3 { margin: 0; font-size: 16px; color: #fff; }
    .notif-header button { background: none; border: none; color: var(--accent-green); cursor: pointer; font-size: 12px; font-weight: bold; }
    .notif-header button:hover { text-decoration: underline; }
    
    .notif-body { overflow-y: auto; flex-grow: 1; padding: 0; margin: 0; list-style: none; }
    .notif-body::-webkit-scrollbar { width: 6px; }
    .notif-body::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 3px; }
    
    .notif-item { padding: 15px; border-bottom: 1px solid var(--border-color); transition: 0.2s; position: relative; cursor: pointer; }
    .notif-item:hover { background: rgba(255,255,255,0.02); }
    .notif-item.unread { background: rgba(0, 255, 136, 0.05); border-left: 3px solid var(--accent-green); }
    .notif-item h4 { margin: 0 0 5px 0; font-size: 14px; color: #fff; padding-right: 25px; }
    .notif-item p { margin: 0 0 8px 0; font-size: 13px; color: var(--text-secondary); line-height: 1.4; word-wrap: break-word; }
    .notif-date { font-size: 11px; color: #888; }
    
    .notif-delete { position: absolute; top: 15px; right: 15px; background: none; border: none; color: #ff3b3b; cursor: pointer; opacity: 0.3; padding: 5px; }
    .notif-item:hover .notif-delete { opacity: 1; }
    .notif-empty { padding: 30px 15px; text-align: center; color: var(--text-secondary); font-size: 14px; }
    
    @media (max-width: 768px) {
        .notif-dropdown { width: 300px; right: -10px; }
    }
</style>

<div class="notif-widget">
    <button class="bell-btn" id="bellBtn" onclick="toggleNotifications()">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>
        <span class="bell-badge" id="bellBadge" style="display:none;">0</span>
    </button>
    
    <div class="notif-dropdown" id="notifDropdown">
        <div class="notif-header">
            <h3>Notificaciones</h3>
            <button onclick="notifAction('mark_read', 'all', event)">Marcar todas</button>
        </div>
        <div class="notif-body" id="notifList">
            <div class="notif-empty">Cargando...</div>
        </div>
    </div>
</div>

<script>
    // --- LÓGICA DEL MENÚ MÓVIL (HAMBURGUESA) ---
    const sidebar = document.getElementById('main-sidebar');
    const menuToggle = document.getElementById('menu-toggle');
    const mobileOverlay = document.getElementById('mobile-overlay');

    function toggleMenu() {
        sidebar.classList.toggle('active');
        mobileOverlay.classList.toggle('active');
        
        // Cambiar ícono a "X" cuando está abierto
        if(sidebar.classList.contains('active')) {
            menuToggle.innerHTML = '<svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>';
            menuToggle.style.color = "var(--accent-green)";
            menuToggle.style.borderColor = "var(--accent-green)";
        } else {
            // Volver a hamburguesa cuando está cerrado
            menuToggle.innerHTML = '<svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>';
            menuToggle.style.color = "#fff";
            menuToggle.style.borderColor = "var(--border-color)";
        }
    }

    // Eventos para abrir/cerrar menú
    if (menuToggle) menuToggle.addEventListener('click', toggleMenu);
    if (mobileOverlay) mobileOverlay.addEventListener('click', toggleMenu);

    // Cerrar el menú automáticamente al hacer clic en cualquier enlace (Mejora de UX)
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 768 && sidebar.classList.contains('active')) {
                toggleMenu();
            }
        });
    });

    // --- LÓGICA DE NOTIFICACIONES ---
    const bellBadge = document.getElementById('bellBadge');
    const notifDropdown = document.getElementById('notifDropdown');
    const notifList = document.getElementById('notifList');
    
    function toggleNotifications() {
        notifDropdown.classList.toggle('show');
        if (notifDropdown.classList.contains('show')) fetchNotifications();
    }

    // Cerrar panel de notificaciones al hacer clic fuera
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.notif-widget')) {
            notifDropdown.classList.remove('show');
        }
    });

    function fetchNotifications() {
        fetch('/api/notifications.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action: 'fetch' })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (data.unread > 0) {
                    bellBadge.innerText = data.unread;
                    bellBadge.style.display = 'block';
                } else {
                    bellBadge.style.display = 'none';
                }
                
                notifList.innerHTML = '';
                if (data.notifications.length === 0) {
                    notifList.innerHTML = '<div class="notif-empty">No tienes notificaciones nuevas.</div>';
                    return;
                }
                
                data.notifications.forEach(n => {
                    const isUnread = n.is_read == 0 ? 'unread' : '';
                    const dateObj = new Date(n.created_at);
                    const dateStr = dateObj.toLocaleDateString() + ' ' + dateObj.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    
                    notifList.innerHTML += `
                        <div class="notif-item ${isUnread}" onclick="if(${n.is_read} == 0) notifAction('mark_read', ${n.id}, event)">
                            <h4>${escapeHTML(n.title)}</h4>
                            <p>${escapeHTML(n.message)}</p>
                            <span class="notif-date">${dateStr}</span>
                            <button class="notif-delete" onclick="notifAction('delete', ${n.id}, event)" title="Eliminar">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                            </button>
                        </div>
                    `;
                });
            }
        });
    }

    function notifAction(action, id, event) {
        if (event) event.stopPropagation(); 
        
        fetch('/api/notifications.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action: action, id: id })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) fetchNotifications(); 
        });
    }

    function escapeHTML(str) {
        return str.replace(/[&<>'"]/g, tag => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
        }[tag] || tag));
    }

    setTimeout(fetchNotifications, 500);
</script>
