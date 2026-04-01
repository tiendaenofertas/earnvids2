<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$opts = get_option( 'mainter_seo_settings', [] );
?>

<style>
    /* --- RESET Y BASES MAINTER --- */
    html, body { margin: 0; padding: 0; background: #f0f0f1; }
    #wpcontent { padding-left: 0; }
    #wpbody-content { padding-bottom: 0; float: none; }
    #wpfooter { display: none; }
    .update-nag, .notice { display: none; } /* Oculta avisos nativos de WP */

    /* VARIABLES */
    :root {
        --m-sidebar-bg: #ffffff; --m-content-bg: #f8f9fa; --m-border: #e2e4e7;
        --m-primary: oklch(50% 0.134 242.749); --m-primary-hover: oklch(29.3% 0.066 243.157);
        --m-text: #1d2327; --m-text-light: #64748b; --m-sidebar-w: 240px;    
    }

    /* TOAST NOTIFICATION (Flotante) */
    .m-seo-toast {
        position: fixed; top: 40px; right: 40px; background: #2e7d32; color: #fff;
        padding: 15px 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        display: flex; align-items: center; gap: 10px; font-weight: 500; z-index: 9999;
        transform: translateY(-20px); opacity: 0; transition: all 0.3s ease;
    }
    .m-seo-toast.show { transform: translateY(0); opacity: 1; }

    /* LAYOUT PRINCIPAL */
    .mainter-wrapper { display: flex; height: calc(100vh - 32px); overflow: hidden; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
    .mainter-sidebar { width: var(--m-sidebar-w); background: var(--m-sidebar-bg); border-right: 1px solid var(--m-border); display: flex; flex-direction: column; flex-shrink: 0; z-index: 20; }
    .sidebar-brand { padding: 20px 24px; display: flex; align-items: center; gap: 10px; height: 60px; box-sizing: border-box; border-bottom: 1px solid transparent; }
    .brand-icon { color: var(--m-primary); font-size: 24px; }
    .brand-name { font-size: 18px; font-weight: 700; color: #111; letter-spacing: -0.5px; }

    .sidebar-nav { flex: 1; padding: 20px 0; display: flex; flex-direction: column; gap: 2px; }
    .nav-link { width: 100%; display: flex; align-items: center; gap: 12px; padding: 12px 24px; background: transparent; border: none; text-align: left; cursor: pointer; color: var(--m-text-light); font-size: 14px; font-weight: 500; border-left: 3px solid transparent; transition: all 0.2s; }
    .nav-link:hover { background: #f9fafb; color: var(--m-primary); }
    .nav-link.active { color: var(--m-primary); border-left-color: var(--m-primary); font-weight: 600; }
    .nav-link .dashicons { font-size: 18px; color: inherit; }

    .sidebar-footer { padding: 15px 24px; border-top: 1px solid var(--m-border); font-size: 11px; color: var(--m-text-light); display: flex; justify-content: space-between; }
    .sidebar-footer a { color: var(--m-text-light); text-decoration: none; }

    /* ÁREA DE CONTENIDO */
    .mainter-content { flex: 1; background: var(--m-content-bg); overflow-y:auto; padding: 40px 60px; position: relative; }
    .content-container { max-width: 900px; width: 100%; margin: 0; padding-bottom: 100px; }
    .pane-header { margin-bottom: 20px; border-bottom: 1px solid var(--m-border); padding-bottom: 20px; }
    .pane-header h2 { font-size: 24px; font-weight: 700; color: #111; margin: 0 0 8px 0; padding: 0; }
    .pane-header p { font-size: 14px; color: var(--m-text-light); margin: 0; }
    
    .m-seo-tab-pane { display: none; }
    .m-seo-tab-pane.active { display: block; animation: fadeIn 0.3s ease; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

    .settings-list { display: flex; flex-direction: column; background: transparent; }
    .option-row { display: flex; gap: 20px; align-items: flex-start; border-bottom: 1px solid var(--m-border); padding: 25px 0; }
    .option-info { flex: 1; }
    .option-info h4 { margin: 0 0 6px 0; font-size: 15px; font-weight: 600; color: var(--m-text); }
    .option-info p { margin: 0 0 10px 0; font-size: 13px; color: var(--m-text-light); line-height: 1.5; }
    
    .mainter-input { width: 100%; padding: 8px 12px; border: 1px solid #ccd0d4; border-radius: 4px; font-family: inherit; }
    .mainter-textarea { width: 100%; padding: 8px 12px; border: 1px solid #ccd0d4; border-radius: 4px; font-family: inherit; resize: vertical; }
    .seo-tag { display: inline-block; background: #e2e4e7; color: #1d2327; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-family: monospace; margin-right: 5px; cursor: help; }

    .switch { position: relative; display: inline-block; width: 44px; height: 24px; flex-shrink: 0; margin-top: 2px; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .4s; border-radius: 34px; }
    .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; box-shadow: 0 1px 2px rgba(0,0,0,0.2); }
    input:checked + .slider { background-color: var(--m-primary); }
    input:checked + .slider:before { transform: translateX(20px); }

    .sticky-footer { margin-top: 30px; text-align: left; z-index: 5; }
    .large-btn { background: var(--m-primary) !important; border-color: var(--m-primary) !important; color: #fff !important; font-weight: 600 !important; padding: 8px 24px !important; border-radius: 6px !important; font-size: 14px !important; cursor: pointer; border: none; transition: 0.2s; }
    .large-btn:hover { background: var(--m-primary-hover) !important; }

    /* RESPONSIVE */
    @media (max-width: 768px) {
        .mainter-wrapper { flex-direction: column; height: auto; overflow: visible; }
        .mainter-sidebar { width: 100%; height: auto; border-right: none; border-bottom: 1px solid var(--m-border); position: sticky; top: 32px; }
        .sidebar-brand, .sidebar-footer { display: none; }
        .sidebar-nav { flex-direction: row; padding: 10px 15px; overflow-x: auto; white-space: nowrap; gap: 10px; background: #fff; }
        .nav-link { width: auto; padding: 6px 16px; background: #f3f4f6; border-radius: 20px; font-size: 13px; flex-shrink: 0; }
        .nav-link.active { background: var(--m-primary); color: #fff; }
        .nav-link .dashicons { display: none; }
        .mainter-content { padding: 20px 15px; }
    }
</style>

<?php if ( isset( $_GET['settings-updated'] ) ) : ?>
    <div id="m-seo-toast" class="m-seo-toast">
        <span class="dashicons dashicons-saved"></span> Configuración guardada con éxito
    </div>
<?php endif; ?>

<div class="mainter-wrapper">
    
    <aside class="mainter-sidebar">
        <div class="sidebar-brand">
            <span class="dashicons dashicons-search brand-icon"></span>
            <span class="brand-name">Mainter SEO</span>
        </div>
        <div class="sidebar-nav">
            <button class="nav-link active" data-target="tab-home">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"><path d="M6.133 21C4.955 21 4 20.02 4 18.81v-8.802c0-.665.295-1.295.8-1.71l5.867-4.818a2.09 2.09 0 0 1 2.666 0l5.866 4.818c.506.415.801 1.045.801 1.71v8.802c0 1.21-.955 2.19-2.133 2.19z"/><path d="M9.5 21v-5.5a2 2 0 0 1 2-2h1a2 2 0 0 1 2 2V21"/></g></svg> SEO de Inicio
            </button>
            <button class="nav-link" data-target="tab-social">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 64 64"><path fill="currentColor" d="M29.5 42.6c1.2 0 2.3-1 2.3-2.3v-15c0-5.4 4.4-9.8 9.8-9.8h11.9L47.7 21c-.9.9-.9 2.3-.1 3.2c.4.5 1 .7 1.6.7q.9 0 1.5-.6l9.2-8.7c.6-.6 1-1.5 1-2.4s-.4-1.7-1-2.3l-9.2-8.5c-.9-.8-2.3-.8-3.2.1c-.8.9-.8 2.3.1 3.2l5.8 5.4h-12c-7.9 0-14.3 6.4-14.3 14.3v15c.2 1.2 1.2 2.2 2.4 2.2"/><path fill="currentColor" d="M59 38.1c-1.2 0-2.3 1-2.3 2.3v14.5c0 1.6-1.3 2.9-2.9 2.9H10.2c-1.6 0-2.9-1.3-2.9-2.9V40.3c0-1.2-1-2.3-2.3-2.3s-2.3 1-2.3 2.3v14.5c0 4.1 3.3 7.4 7.4 7.4h43.7c4.1 0 7.4-3.3 7.4-7.4V40.3c.1-1.2-.9-2.2-2.2-2.2"/></svg> Metadatos
            </button>
            <button class="nav-link" data-target="tab-robots">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10a7 7 0 1 0 14 0a7 7 0 1 0-14 0m18 11l-6-6"/></svg> Indexación 
            </button>
            <button class="nav-link" data-target="tab-schema">
               <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 32 32"><path fill="currentColor" d="M31 11v10h-2l-2-6v6h-2V11h2l2 6v-6zm-9.666 10h-2.667A1.67 1.67 0 0 1 17 19.334v-6.667A1.67 1.67 0 0 1 18.666 11h2.667A1.67 1.67 0 0 1 23 12.666v6.667A1.67 1.67 0 0 1 21.334 21M19 19h2v-6h-2Zm-5.666 2H9v-2h4v-2h-2a2 2 0 0 1-2-2v-2.334A1.67 1.67 0 0 1 10.666 11H15v2h-4v2h2a2 2 0 0 1 2 2v2.334A1.67 1.67 0 0 1 13.334 21m-8 0H2.667A1.67 1.67 0 0 1 1 19.334V17h2v2h2v-8h2v8.334A1.67 1.67 0 0 1 5.334 21"/></svg> Datos Estructurados
            </button>
			<button class="nav-link" data-target="tab-sitemap">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"><path fill="currentColor" d="M9 3h5v5h-2v4h5a3 3 0 0 1 3 3v2h2v5h-5v-5h2v-2a2 2 0 0 0-2-2h-5v4h2v5H9v-5h2v-4H6a2 2 0 0 0-2 2v2h2v5H1v-5h2v-2a3 3 0 0 1 3-3h5V8H9zm4 4V4h-3v3zM5 21v-3H2v3zm8 0v-3h-3v3zm8 0v-3h-3v3z"/></svg> Sitemap XML
            </button>
			
		
        </div>
        <div class="sidebar-footer">
            <span>Versión 1.0</span>
            <a href="#">Soporte</a>
        </div>
    </aside>

    <main class="mainter-content">
        <div class="content-container">
            <form method="post" action="options.php">
                <?php settings_fields( 'mainter_seo_group' ); ?>

                <div id="tab-home" class="m-seo-tab-pane active">
                    <div class="pane-header">
                        <h2>Configuración de Inicio</h2>
                    </div>
                    <div class="settings-list">
                        <div class="option-row">
                            <label class="switch">
                                <input type="checkbox" name="mainter_seo_settings[home_module_active]" value="1" <?php checked( 1, $opts['home_module_active'] ?? 0 ); ?>>
                                <span class="slider"></span>
                            </label>
                            <div class="option-info">
                                <h4>Activar SEO de Inicio</h4>
                            </div>
                        </div>
                        <div class="option-row" style="flex-direction: column; gap: 10px;">
                            <div class="option-info" style="width: 100%;">
                                <h4>Meta Título (Inicio)</h4>
                                <p>Variables dinámicas: <span class="seo-tag" title="Nombre del sitio">%%sitename%%</span> <span class="seo-tag" title="Descripción corta del sitio">%%sitedesc%%</span> <span class="seo-tag" title="Separador (-)">%%sep%%</span></p>
                                <input type="text" name="mainter_seo_settings[home_title]" value="<?php echo esc_attr( $opts['home_title'] ?? '%%sitename%% %%sep%% %%sitedesc%%' ); ?>" class="mainter-input">
                            </div>
                        </div>
                        <div class="option-row" style="flex-direction: column; gap: 10px;">
                            <div class="option-info" style="width: 100%;">
                                <h4>Meta Descripción (Inicio)</h4>
                                <textarea name="mainter_seo_settings[home_desc]" rows="3" class="mainter-textarea"><?php echo esc_textarea( $opts['home_desc'] ?? '' ); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

               <div id="tab-social" class="m-seo-tab-pane">
                    <div class="pane-header">
                        <h2>Meta Tags Esenciales</h2>
                    </div>
                    <div class="settings-list">
                        <div class="option-row">
                            <label class="switch"><input type="checkbox" name="mainter_seo_settings[enable_canonical]" value="1" <?php checked( 1, $opts['enable_canonical'] ?? 0 ); ?>><span class="slider"></span></label>
                            <div class="option-info"><h4>Enlaces Canónicos (Canonical URL)</h4></div>
                        </div>
                        
                        <div class="option-row">
                            <label class="switch"><input type="checkbox" name="mainter_seo_settings[enable_og]" value="1" <?php checked( 1, $opts['enable_og'] ?? 0 ); ?>><span class="slider"></span></label>
                            <div class="option-info"><h4>Open Graph (Facebook, WhatsApp)</h4></div>
                        </div>
						
						 <div class="option-row">
                            <label class="switch"><input type="checkbox" name="mainter_seo_settings[enable_tw]" value="1" <?php checked( 1, $opts['enable_tw'] ?? 0 ); ?>><span class="slider"></span></label>
                            <div class="option-info"><h4>Twitter Cards (X)</h4></div>
                        </div>
						
                        <div class="option-row" style="flex-direction: column; gap: 10px; padding-top: 0; border-bottom: none;">
                            <div class="option-info" style="width: 100%;">
                                <h3 style=" margin:1.25rem 0px;">Imagen Open Graph (Solo para Inicio):</h3>
                                <input type="url" name="mainter_seo_settings[home_og_image]" value="<?php echo esc_url( $opts['home_og_image'] ?? '' ); ?>" class="mainter-input" style="margin-top: 5px;">
                            </div>
                        </div>
						
						
                        
                        <div class="option-row" style="flex-direction: column; gap: 10px; padding-top: 0;">
                            <div class="option-info" style="width: 100%;">
                                <h3 style=" margin:1.25rem 0px;">URL de Facebook del Sitio (Publisher):</h3>
                                <input type="url" name="mainter_seo_settings[social_facebook]" value="<?php echo esc_url( $opts['social_facebook'] ?? '' ); ?>" class="mainter-input" style="margin-top: 5px;" placeholder="Ej: https://www.facebook.com/tu-pagina">
                                <p style="margin-top:5px; font-size: 13px; color:#646970;">Esta URL se usará para la etiqueta <code>article:publisher</code> al compartir entradas.</p>
                            </div>
                        </div>

                       
                        <div class="option-row" style="flex-direction: column; gap: 10px; padding-top: 0;">
                            <div class="option-info" style="width: 100%;">
                                <h3>Imagen Twitter Card (Solo para Inicio):</h3>
                                <input type="url" name="mainter_seo_settings[home_tw_image]" value="<?php echo esc_url( $opts['home_tw_image'] ?? '' ); ?>" class="mainter-input" style="margin-top: 5px;">
                            </div>
                        </div>
                    </div>
                </div>

                <div id="tab-robots" class="m-seo-tab-pane">
                    <div class="pane-header">
                        <h2>Reglas de Indexación (Robots)</h2>
                    </div>
                    <div class="settings-list">
                        
                   

                        <?php 
                        $tipos_robots = [ 'robots_home' => 'Página de Inicio', 'robots_posts' => 'Entradas (Blog)', 'robots_pages' => 'Páginas', 'robots_cats' => 'Categorías', 'robots_tags' => 'Etiquetas (Tags)' ];
                        foreach ($tipos_robots as $key => $label) : 
                            $val = $opts[$key] ?? (($key === 'robots_tags') ? 'noindex, follow' : 'index, follow');
                        ?>
                        <div class="option-row" style="align-items: center; padding: 15px 0;">
                            <div class="option-info" style="width: 250px; flex: none;"><h4 style="margin: 0;"><?php echo $label; ?></h4></div>
                            <div style="flex: 1;">
                                <select name="mainter_seo_settings[<?php echo $key; ?>]" class="mainter-input" style="max-width: 400px;">
                                    <option value="index, follow" <?php selected($val, 'index, follow'); ?>> Indexar y Seguir enlaces</option>
                                    <option value="noindex, follow" <?php selected($val, 'noindex, follow'); ?>> NO Indexar, pero Seguir</option>
                                    <option value="noindex, nofollow" <?php selected($val, 'noindex, nofollow'); ?>> Ocultar Totalmente</option>
                                </select>
                            </div>
                        </div>
                        <?php endforeach; ?>

        
                           <div class="option-row">
                            <label class="switch">
                                <input type="checkbox" name="mainter_seo_settings[robots_max_image]" value="1" <?php checked( 1, $opts['robots_max_image'] ?? 1 ); ?>>
                                <span class="slider"></span>
                            </label>
                            <div class="option-info">
                                <h4>Max Image Preview: Large</h4>
                            </div>
                        </div>
                        <div class="option-row">
                            <label class="switch"><input type="checkbox" name="mainter_seo_settings[external_blank]" value="1" <?php checked( 1, $opts['external_blank'] ?? 0 ); ?>><span class="slider"></span></label>
                            <div class="option-info"><h4>Abrir en nueva pestaña</h4></div>
                        </div>
                        <div class="option-row">
                            <label class="switch"><input type="checkbox" name="mainter_seo_settings[external_nofollow]" value="1" <?php checked( 1, $opts['external_nofollow'] ?? 0 ); ?>><span class="slider"></span></label>
                            <div class="option-info"><h4>Forzar Nofollow</h4></div>
                        </div>
                    </div>
                </div>

                <div id="tab-schema" class="m-seo-tab-pane">
                    <div class="pane-header">
                        <h2>Datos Estructurados (Schema JSON-LD)</h2>
                    </div>
                    <div class="settings-list">
                        
                        <div class="option-row">
                            <label class="switch">
                                <input type="checkbox" name="mainter_seo_settings[schema_org]" value="1" <?php checked( 1, $opts['schema_org'] ?? 0 ); ?>>
                                <span class="slider"></span>
                            </label>
                            <div class="option-info">
                                <h4>Organización y Logotipo</h4>
                                <p>Marca la Home como una entidad/marca oficial (Organization Schema). Toma el logo nativo de WordPress.</p>
                            </div>
                        </div>

                        <div class="option-row">
                            <label class="switch">
                                <input type="checkbox" name="mainter_seo_settings[schema_search]" value="1" <?php checked( 1, $opts['schema_search'] ?? 0 ); ?>>
                                <span class="slider"></span>
                            </label>
                            <div class="option-info">
                                <h4>Caja de Búsqueda Web (Sitelinks Searchbox)</h4>
                                <p>Habilita la posibilidad de que Google muestre una barra de búsqueda de tu web directamente en sus resultados.</p>
                            </div>
                        </div>

                        <div class="option-row">
                            <label class="switch">
                                <input type="checkbox" name="mainter_seo_settings[schema_article]" value="1" <?php checked( 1, $opts['schema_article'] ?? 0 ); ?>>
                                <span class="slider"></span>
                            </label>
                            <div class="option-info">
                                <h4>Artículo de Blog (BlogPosting)</h4>
                                <p>Añade datos ricos en las entradas (Autor, Fechas de publicación/modificación, Imagen). Esencial para aparecer en Google Discover.</p>
                            </div>
                        </div>

                        <div class="option-row">
                            <label class="switch">
                                <input type="checkbox" name="mainter_seo_settings[schema_breadcrumbs]" value="1" <?php checked( 1, $opts['schema_breadcrumbs'] ?? 0 ); ?>>
                                <span class="slider"></span>
                            </label>
                            <div class="option-info">
                                <h4>Migas de Pan (BreadcrumbsList)</h4>
                                <p>Muestra la ruta de navegación (Ej: Inicio > Categoría > Post) en los resultados móviles de Google en lugar de una URL larga.</p>
                            </div>
                        </div>

                    </div>
                </div>
				<div id="tab-sitemap" class="m-seo-tab-pane">
                    <div class="pane-header">
                        <h2>Mapa del Sitio (Sitemap XML)</h2>
                        <p>Genera un índice automático y visual (estilo Rank Math) para Google Search Console.</p>
                    </div>
                    <div class="settings-list">
                        
                        <div class="option-row">
                            <label class="switch">
                                <input type="checkbox" name="mainter_seo_settings[enable_sitemap]" value="1" <?php checked( 1, $opts['enable_sitemap'] ?? 0 ); ?>>
                                <span class="slider"></span>
                            </label>
                            <div class="option-info">
                                <h4>Activar Sitemap Index</h4>
                                <p>Crea la URL principal <code>/sitemap_index.xml</code> con un diseño visual estilizado.</p>
                            </div>
                        </div>

                        <?php if ( ! empty( $opts['enable_sitemap'] ) ) : ?>
                        <div class="option-row" style="background: #f0f6fc; border-left: 4px solid #2271b1; padding: 20px; border-radius: 4px; margin-top: 15px;">
                            <div class="option-info" style="width: 100%;">
                                <h4 style="margin: 0 0 8px 0; color: #2271b1;">✅ Tu Sitemap Índice está listo</h4>
                                <p style="margin: 0;">Envía esta URL exacta a <strong>Google Search Console</strong>:</p>
                                <p style="margin: 10px 0 0 0; font-size: 16px;">
                                    <a href="<?php echo esc_url( home_url( '/sitemap_index.xml' ) ); ?>" target="_blank" style="font-weight: 700; color: #2271b1; text-decoration: none;">👉 <?php echo esc_url( home_url( '/sitemap_index.xml' ) ); ?></a>
                                </p>
                            </div>
                        </div>

                        <h3 style="margin-top: 40px; font-size: 18px; border-bottom: 1px solid #e2e4e7; padding-bottom: 10px;">¿Qué quieres incluir en el Sitemap?</h3>
                        <p style="color: #646970; font-size: 13px; margin-bottom: 20px;">Los elementos marcados como "noindex" se excluirán automáticamente aunque actives estas opciones.</p>

                        <div class="option-row">
                            <label class="switch"><input type="checkbox" name="mainter_seo_settings[sitemap_posts]" value="1" <?php checked( 1, $opts['sitemap_posts'] ?? 1 ); ?>><span class="slider"></span></label>
                            <div class="option-info"><h4>Entradas (post-sitemap.xml)</h4></div>
                        </div>
                        <div class="option-row">
                            <label class="switch"><input type="checkbox" name="mainter_seo_settings[sitemap_pages]" value="1" <?php checked( 1, $opts['sitemap_pages'] ?? 1 ); ?>><span class="slider"></span></label>
                            <div class="option-info"><h4>Páginas (page-sitemap.xml)</h4></div>
                        </div>
                        <div class="option-row">
                            <label class="switch"><input type="checkbox" name="mainter_seo_settings[sitemap_cats]" value="1" <?php checked( 1, $opts['sitemap_cats'] ?? 1 ); ?>><span class="slider"></span></label>
                            <div class="option-info"><h4>Categorías (category-sitemap.xml)</h4></div>
                        </div>
                        <div class="option-row">
                            <label class="switch"><input type="checkbox" name="mainter_seo_settings[sitemap_tags]" value="1" <?php checked( 1, $opts['sitemap_tags'] ?? 0 ); ?>><span class="slider"></span></label>
                            <div class="option-info"><h4>Etiquetas (tag-sitemap.xml)</h4><p>Por lo general se recomienda dejarlo apagado para evitar contenido duplicado.</p></div>
                        </div>
                        <?php endif; ?>

                    </div>
                </div>
			
				
				
                <div class="sticky-footer">
                    <button type="submit" class="large-btn">Guardar configuración</button>
                </div>
            </form>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Mostrar y ocultar el TOAST Notification
    const toast = document.getElementById('m-seo-toast');
    if (toast) {
        setTimeout(() => { toast.classList.add('show'); }, 100);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => { toast.remove(); }, 300);
        }, 3000);
    }

    // 2. Sistema de Pestañas con LocalStorage
    const buttons = document.querySelectorAll('.nav-link');
    const panes = document.querySelectorAll('.m-seo-tab-pane');

    const activeTab = localStorage.getItem('mainterSeoActiveTab');
    if (activeTab) {
        buttons.forEach(b => b.classList.remove('active'));
        panes.forEach(p => p.classList.remove('active'));
        
        const targetBtn = document.querySelector(`.nav-link[data-target="${activeTab}"]`);
        const targetPane = document.getElementById(activeTab);
        
        if (targetBtn && targetPane) {
            targetBtn.classList.add('active');
            targetPane.classList.add('active');
        }
    }

    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            buttons.forEach(b => b.classList.remove('active'));
            panes.forEach(p => p.classList.remove('active'));
            
            this.classList.add('active');
            const targetId = this.getAttribute('data-target');
            document.getElementById(targetId).classList.add('active');

            localStorage.setItem('mainterSeoActiveTab', targetId);
        });
    });
});

</script>