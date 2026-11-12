<?php
/**
 * Modelo "Prints Cartões (página inteira)": sem cabeçalho/rodapé do tema, igual à página original.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<?php if ( ! current_theme_supports( 'title-tag' ) ) : ?>
		<title><?php echo esc_html( wp_get_document_title() ); ?></title>
	<?php endif; ?>
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'pc-pagina' ); ?>>
<?php
if ( function_exists( 'wp_body_open' ) ) {
	wp_body_open();
}
echo prints_cartoes_shortcode(); // phpcs:ignore WordPress.Security.EscapeOutput -- HTML fixo e escapado na função.
wp_footer();
?>
</body>
</html>
