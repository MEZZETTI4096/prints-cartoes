<?php
/**
 * Plugin Name:       Prints Cartões
 * Description:       Página de Cartões de Visitas da Gráfica Print's com pedido pelo WhatsApp. Use o modelo de página "Prints Cartões (página inteira)" ou o shortcode [prints_cartoes].
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.0
 * Author:            Gráfica Print's
 * License:           GPL-2.0-or-later
 * Text Domain:       prints-cartoes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PRINTS_CARTOES_VERSAO', '1.0.0' );
define( 'PRINTS_CARTOES_MODELO', 'prints-cartoes-pagina-inteira' );

/** A página atual usa o modelo de página inteira? */
function prints_cartoes_modelo_ativo() {
	return is_singular() && get_page_template_slug( get_queried_object_id() ) === PRINTS_CARTOES_MODELO;
}

/** A página atual contém o shortcode? */
function prints_cartoes_tem_shortcode() {
	$post = get_post( get_queried_object_id() );
	return is_singular() && $post && has_shortcode( $post->post_content, 'prints_cartoes' );
}

function prints_cartoes_registrar_assets() {
	$base = plugin_dir_url( __FILE__ );

	wp_register_style( 'prints-cartoes-fonte', 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap', array(), null );
	wp_register_style( 'prints-cartoes', $base . 'assets/css/style.css', array( 'prints-cartoes-fonte' ), PRINTS_CARTOES_VERSAO );

	wp_register_script( 'prints-cartoes-dados', $base . 'assets/js/dados.js', array(), PRINTS_CARTOES_VERSAO, true );
	wp_add_inline_script( 'prints-cartoes-dados', 'window.FORM_BASE = ' . wp_json_encode( $base ) . ';', 'before' );
	wp_register_script( 'prints-cartoes', $base . 'assets/js/app.js', array( 'prints-cartoes-dados' ), PRINTS_CARTOES_VERSAO, true );

	if ( prints_cartoes_modelo_ativo() || prints_cartoes_tem_shortcode() ) {
		wp_enqueue_style( 'prints-cartoes' );
		wp_enqueue_script( 'prints-cartoes' );
	}
}
add_action( 'wp_enqueue_scripts', 'prints_cartoes_registrar_assets' );

/**
 * No modelo de página inteira, remove o CSS do tema para a página ficar idêntica à original.
 * (Barra de administração continua funcionando.)
 */
function prints_cartoes_isolar_estilos() {
	if ( ! prints_cartoes_modelo_ativo() ) {
		return;
	}
	$manter = array( 'prints-cartoes', 'prints-cartoes-fonte', 'admin-bar', 'dashicons' );
	foreach ( wp_styles()->queue as $handle ) {
		if ( ! in_array( $handle, $manter, true ) ) {
			wp_dequeue_style( $handle );
		}
	}
	remove_action( 'wp_head', 'wp_custom_css_cb', 101 );
}
add_action( 'wp_enqueue_scripts', 'prints_cartoes_isolar_estilos', 9999 );

/** No modelo de página inteira, mantém os emojis nativos (⚪) como no original, sem a troca por imagens do WordPress. */
function prints_cartoes_sem_emoji_wp() {
	if ( prints_cartoes_modelo_ativo() ) {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
	}
}
add_action( 'wp', 'prints_cartoes_sem_emoji_wp' );
add_action( 'wp_print_styles', 'prints_cartoes_isolar_estilos', 9999 );

/** Shortcode: [prints_cartoes] — insere a página dentro do layout do tema. */
function prints_cartoes_shortcode() {
	wp_enqueue_style( 'prints-cartoes' );
	wp_enqueue_script( 'prints-cartoes' );
	$modo = prints_cartoes_modelo_ativo() ? 'pagina' : 'embutido';
	return '<div id="prints-cartoes" data-modo="' . esc_attr( $modo ) . '"></div>';
}
add_shortcode( 'prints_cartoes', 'prints_cartoes_shortcode' );

/** Registra o modelo "Prints Cartões (página inteira)" no seletor de modelos de página. */
function prints_cartoes_modelos( $modelos ) {
	$modelos[ PRINTS_CARTOES_MODELO ] = 'Prints Cartões (página inteira)';
	return $modelos;
}
add_filter( 'theme_page_templates', 'prints_cartoes_modelos' );

function prints_cartoes_template_include( $template ) {
	if ( prints_cartoes_modelo_ativo() ) {
		return plugin_dir_path( __FILE__ ) . 'templates/pagina-inteira.php';
	}
	return $template;
}
add_filter( 'template_include', 'prints_cartoes_template_include', 99 );

/** Ícone do site = logo, se o site não tiver um ícone definido (só no modelo de página inteira). */
function prints_cartoes_favicon() {
	if ( prints_cartoes_modelo_ativo() && ! has_site_icon() ) {
		echo '<link rel="icon" type="image/png" href="' . esc_url( plugin_dir_url( __FILE__ ) . 'assets/img/logo.png' ) . '" />' . "\n";
	}
}
add_action( 'wp_head', 'prints_cartoes_favicon' );
