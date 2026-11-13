<?php
/**
 * Plugin Name:       Print's Páginas
 * Description:       Páginas de pedido da Gráfica Print's (envio pelo WhatsApp), montadas em blocos editáveis. Instale uma vez e importe cada página nova em Ferramentas › Print's Páginas.
 * Version:           3.1.0
 * Requires at least: 6.3
 * Requires PHP:      7.0
 * Author:            Gráfica Print's
 * License:           GPL-2.0-or-later
 * Text Domain:       prints-paginas
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PRINTS_PAGINAS_VERSAO', '3.1.0' );
define( 'PRINTS_PAGINAS_MODELO', 'prints-paginas-pagina-inteira' );
define( 'PRINTS_PAGINAS_ARQUIVO', __FILE__ );
define( 'PRINTS_PAGINAS_PREFIXO', 'prints-paginas' );

require_once __DIR__ . '/inc/blocos.php';
require_once __DIR__ . '/inc/importar.php';
require_once __DIR__ . '/inc/tela.php';
require_once __DIR__ . '/inc/estrutura.php';
require_once __DIR__ . '/inc/padroes.php';

/** Modelos de página inteira reconhecidos (o segundo é da versão antiga "Prints Cartões"). */
function prints_paginas_modelos_validos() {
	return array( PRINTS_PAGINAS_MODELO, 'prints-cartoes-pagina-inteira' );
}

/** A página atual usa o modelo de página inteira? */
function prints_paginas_modelo_ativo() {
	return is_singular() && in_array( get_page_template_slug( get_queried_object_id() ), prints_paginas_modelos_validos(), true );
}

/* ---------------------------------------------------------------------------
 * Assets
 * ------------------------------------------------------------------------- */
function prints_paginas_registrar_assets() {
	$base = plugin_dir_url( __FILE__ );
	$v    = PRINTS_PAGINAS_VERSAO;

	wp_register_style( 'prints-paginas-fonte', 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap', array(), null );
	wp_register_style( 'prints-paginas', $base . 'assets/css/style.css', array( 'prints-paginas-fonte' ), $v );
	wp_register_style( 'prints-paginas-editor', $base . 'editor/editor.css', array( 'prints-paginas' ), $v );
	wp_register_script( 'prints-paginas-formulario', $base . 'assets/js/formulario.js', array(), $v, true );
	wp_register_script(
		'prints-paginas-blocos-editor',
		$base . 'editor/blocos.js',
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
		$v,
		true
	);
}
add_action( 'init', 'prints_paginas_registrar_assets', 5 );

/**
 * No modelo de página inteira, remove o CSS do tema (a página fica igual à original).
 * Mantém o CSS dos blocos do WordPress, dos ajustes de bloco (global-styles) e a barra de administração.
 */
function prints_paginas_isolar_estilos() {
	if ( ! prints_paginas_modelo_ativo() ) {
		return;
	}
	foreach ( wp_styles()->queue as $handle ) {
		// Mantém também os estilos do próprio WordPress usados pelos ajustes dos blocos (cores, espaçamentos)
		$manter = 0 === strpos( $handle, PRINTS_PAGINAS_PREFIXO )
			|| 0 === strpos( $handle, 'wp-block-' )
			|| 0 === strpos( $handle, 'core-block-supports' )
			|| in_array( $handle, array( 'admin-bar', 'dashicons', 'global-styles' ), true );
		if ( ! $manter ) {
			wp_dequeue_style( $handle );
		}
	}
	remove_action( 'wp_head', 'wp_custom_css_cb', 101 );
}
add_action( 'wp_enqueue_scripts', 'prints_paginas_isolar_estilos', 9999 );
add_action( 'wp_print_styles', 'prints_paginas_isolar_estilos', 9999 );

/** No modelo de página inteira, mantém os emojis nativos (⚪), sem a troca por imagem do WordPress. */
function prints_paginas_sem_emoji_wp() {
	if ( prints_paginas_modelo_ativo() ) {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
	}
}
add_action( 'wp', 'prints_paginas_sem_emoji_wp' );

/* ---------------------------------------------------------------------------
 * Modelo de página inteira
 * ------------------------------------------------------------------------- */
function prints_paginas_modelos( $modelos ) {
	$modelos[ PRINTS_PAGINAS_MODELO ] = "Print's Páginas (página inteira)";
	return $modelos;
}
add_filter( 'theme_page_templates', 'prints_paginas_modelos' );

function prints_paginas_template_include( $template ) {
	if ( prints_paginas_modelo_ativo() ) {
		return plugin_dir_path( __FILE__ ) . 'templates/pagina-inteira.php';
	}
	return $template;
}
add_filter( 'template_include', 'prints_paginas_template_include', 99 );

/* ---------------------------------------------------------------------------
 * Ativação: desativa a versão antiga (um plugin por página) se estiver ativa
 * ------------------------------------------------------------------------- */
function prints_paginas_ativar() {
	if ( ! function_exists( 'deactivate_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	if ( is_plugin_active( 'prints-cartoes/prints-cartoes.php' ) ) {
		deactivate_plugins( 'prints-cartoes/prints-cartoes.php', true );
	}
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'prints_paginas_ativar' );

/** Se a versão antiga ainda estiver ativa, não registra os blocos duas vezes. */
function prints_paginas_conflito() {
	return function_exists( 'prints_cartoes_registrar_blocos' );
}
