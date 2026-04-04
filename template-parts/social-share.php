<?php
/**
 * Botones de Compartir Redes Sociales (Sin Plugins)
 */

// Si el usuario lo desactivó en el panel, no mostramos nada.
if ( ! get_theme_mod( 'enable_share_buttons', true ) ) return;

// Datos del post actual
$post_url   = urlencode( get_permalink() );
$post_title = urlencode( get_the_title() );
$share_title = get_theme_mod( 'share_buttons_title', 'Comparte este artículo:' );

// URLs de compartir oficiales
$facebook_url = "https://www.facebook.com/sharer/sharer.php?u={$post_url}";
$twitter_url  = "https://twitter.com/intent/tweet?text={$post_title}&url={$post_url}";
$whatsapp_url = "https://api.whatsapp.com/send?text={$post_title}%20{$post_url}";
$linkedin_url = "https://www.linkedin.com/shareArticle?mini=true&url={$post_url}&title={$post_title}";

?>

<div class="mainter-share-section">
    <?php if ( $share_title ) : ?>
        <h4 class="share-title"><?php echo esc_html( $share_title ); ?></h4>
    <?php endif; ?>

    <div class="share-buttons-grid">
        
        <a href="<?php echo esc_url( $facebook_url ); ?>" target="_blank" rel="noopener noreferrer" class="share-btn btn-fb" aria-label="Compartir en Facebook">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
            <span>Facebook</span>
        </a>

        <a href="<?php echo esc_url( $twitter_url ); ?>" target="_blank" rel="noopener noreferrer" class="share-btn btn-x" aria-label="Compartir en X">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4l11.733 16h4.267l-11.733 -16z" /><path d="M4 20l6.768 -6.768m2.46 -2.46l6.772 -6.772" /></svg>
            <span>Twitter</span>
        </a>

        <a href="<?php echo esc_url( $whatsapp_url ); ?>" target="_blank" rel="noopener noreferrer" class="share-btn btn-wa" aria-label="Compartir en WhatsApp">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
            <span>WhatsApp</span>
        </a>

        <a href="<?php echo esc_url( $linkedin_url ); ?>" target="_blank" rel="noopener noreferrer" class="share-btn btn-li" aria-label="Compartir en LinkedIn">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path><rect x="2" y="9" width="4" height="12"></rect><circle cx="4" cy="4" r="2"></circle></svg>
            <span>LinkedIn</span>
        </a>

    </div>
</div>