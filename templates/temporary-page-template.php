<?php
/**
 * Template Name: Temporary Page
 * 
 * A blank template that only shows the page content without header/footer.
 *
 * @package AlmostReadyTemporaryPage
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue inline styles for the temporary page template.
 */
function artp_template_inline_styles() {
	wp_register_style( 'artp-template-style', false );
	wp_enqueue_style( 'artp-template-style' );
	wp_add_inline_style(
		'artp-template-style',
		'body { margin: 0; padding: 0; }
		#wpadminbar { display: none !important; }
		html { margin-top: 0 !important; }
		html, body { height: 100%; }'
	);
}
add_action( 'wp_enqueue_scripts', 'artp_template_inline_styles' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
	<?php wp_body_open(); ?>
	<?php
	// Output the page content.
	while ( have_posts() ) :
		the_post();
		?>
		<div class="wp-site-blocks">
			<?php the_content(); ?>
		</div>
		<?php
	endwhile;
	?>
	<?php wp_footer(); ?>
</body>
</html>
