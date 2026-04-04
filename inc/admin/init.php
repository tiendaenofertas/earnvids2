<?php
/**
 * Inicializador del Panel - ARQUITECTURA MODULAR INDEPENDIENTE
 * Cada pestaña tiene su propio formulario y grupo de seguridad.
 */

// 1. Carga de Módulos
require_once get_template_directory() . '/inc/admin/modules/general.php';
require_once get_template_directory() . '/inc/admin/modules/performance.php';
require_once get_template_directory() . '/inc/admin/modules/security.php';

require_once get_template_directory() . '/inc/admin/modules/seo.php';
require_once get_template_directory() . '/inc/admin/modules/cookies.php';

// 2. Menú
function mi_tema_admin_menu() {
    add_menu_page('Configuración Mainter', 'Mainter', 'manage_options', 'mi_tema_opciones', 'mi_tema_render_panel', 'dashicons-layout', 60);
}
add_action('admin_menu', 'mi_tema_admin_menu');

// 3. Renderizado
function mi_tema_render_panel() {
    settings_errors(); // Muestra "Ajustes guardados"
    ?>
    <div class="wrap mi-tema-wrap">
        <h1> Mainter Panel</h1>

        
        <style>
            :root { --main-color: #4f46e5; --light-bg: #e0e7ff; --text-dark: #1f2937; }
            .mi-tema-wrap { max-width: 850px; margin-top: 30px;  font-family: sans-serif; }
            .mi-tema-wrap h1{margin-bottom: 30px;}
            .mi-tema-tabs { display: flex; gap: 5px; border-bottom: 2px solid #e5e7eb; }
            .mi-tema-tab-link { padding: 12px 24px; cursor: pointer; background: #f9fafb; border: 1px solid #e5e7eb; border-bottom: none; border-radius: 8px 8px 0 0; font-weight: 600; color: #6b7280; }
            .mi-tema-tab-link:hover { color: var(--main-color); background: #f3f4f6; }
            .mi-tema-tab-link.active { background: #fff; color: var(--main-color); border-bottom-color: #fff; margin-bottom: -2px; }
            .tab-content { display: none; background: #fff; padding: 40px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 8px 8px; }
            .option-row { display: flex; padding: 20px 0; border-bottom: 1px solid #eee; }
            .switch { position: relative; width: 48px; height: 26px; margin-right: 20px; flex-shrink: 0; }
            .switch input { opacity: 0; width: 0; height: 0; }
            .slider { position: absolute; cursor: pointer; inset: 0; background: #ccc; transition: .4s; border-radius: 34px; }
            .slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 3px; bottom: 3px; background: white; transition: .4s; border-radius: 50%; }
            input:checked + .slider { background: var(--main-color); }
            input:checked + .slider:before { transform: translateX(22px); }
            .option-desc h4 { margin: 0 0 5px; }
            .option-desc p { margin: 0; color: #666; font-size: 0.9em; }
            /* Botón guardar alineado a la derecha dentro de cada tab */
            .form-actions { margin-top: 30px; text-align: right; border-top: 1px solid #eee; padding-top: 20px; }
        </style>

        <div class="mi-tema-tabs">
            <div class="mi-tema-tab-link" onclick="openTab(event, 'tab-general')">General</div>
            <div class="mi-tema-tab-link" onclick="openTab(event, 'tab-rendimiento')">Rendimiento</div>
            <div class="mi-tema-tab-link" onclick="openTab(event, 'tab-seguridad')">Seguridad</div>
            <div class="mi-tema-tab-link" onclick="openTab(event, 'tab-seo')">SEO</div>
            <div class="mi-tema-tab-link" onclick="openTab(event, 'tab-cookies')">Aviso cookies</div>
            
        </div>

           <div id="tab-general" class="tab-content">
            <form method="post" action="options.php">
                <?php 
                settings_fields('mainter_general_group'); // Grupo único
                mi_tema_render_general_content(); 
                ?>
                <div class="form-actions">
                    <?php submit_button('Guardar general', 'primary large'); ?>
                </div>
            </form>
        </div>

        <div id="tab-rendimiento" class="tab-content">
            <form method="post" action="options.php">
                <?php 
                settings_fields('mainter_perf_group'); // Grupo único
                mi_tema_render_performance_content(); 
                ?>
                <div class="form-actions">
                    <?php submit_button('Guardar Rendimiento', 'primary large'); ?>
                </div>
            </form>
        </div>

        <div id="tab-seguridad" class="tab-content">
            <form method="post" action="options.php">
                <?php 
                settings_fields('mainter_sec_group'); // Grupo único
                mi_tema_render_security_content(); 
                ?>
                <div class="form-actions">
                    <?php submit_button('Guardar Seguridad', 'primary large'); ?>
                </div>
            </form>
        </div>

        <div id="tab-seo" class="tab-content">
            <form method="post" action="options.php">
                <?php 
                settings_fields('mainter_seo_group'); // Grupo único
                if(function_exists('mi_tema_render_seo_content')) mi_tema_render_seo_content(); 
                ?>
                <div class="form-actions">
                    <?php submit_button('Guardar SEO', 'primary large'); ?>
                </div>
            </form>
        </div>

        <div id="tab-cookies" class="tab-content">
            <form method="post" action="options.php">
                <?php 
                settings_fields('mainter_cookie_group'); // Grupo único
                if(function_exists('mi_tema_render_cookie_content')) mi_tema_render_cookie_content(); 
                ?>
                <div class="form-actions">
                    <?php submit_button('Guardar Cookies', 'primary large'); ?>
                </div>
            </form>
        </div>


    </div>

    <script>
    // Tu script de pestañas se mantiene IGUAL
    // (Gracias al localStorage, al guardar y recargar la página, volverás a la misma pestaña)
    document.addEventListener("DOMContentLoaded", function() {
        let activeTab = localStorage.getItem("miTemaActiveTab") || 'tab-rendimiento';
        let link = document.querySelector(`[onclick*='${activeTab}']`);
        if(link) link.click();
    });

    function openTab(evt, tabName) {
        document.querySelectorAll(".tab-content").forEach(c => c.style.display = "none");
        document.querySelectorAll(".mi-tema-tab-link").forEach(l => l.classList.remove("active"));
        document.getElementById(tabName).style.display = "block";
        evt.currentTarget.classList.add("active");
        localStorage.setItem("miTemaActiveTab", tabName);
    }
    </script>
    <?php
}