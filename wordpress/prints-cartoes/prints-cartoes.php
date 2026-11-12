<?php
/**
 * Plugin Name:       Prints Cartões
 * Description:       Página de Cartões de Visitas da Gráfica Print's com pedido pelo WhatsApp, editável pelo editor de blocos. Ao ativar, cria a página /cartoes.
 * Version:           2.0.0
 * Requires at least: 6.3
 * Requires PHP:      7.0
 * Author:            Gráfica Print's
 * License:           GPL-2.0-or-later
 * Text Domain:       prints-cartoes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PRINTS_CARTOES_VERSAO', '2.0.0' );
define( 'PRINTS_CARTOES_MODELO', 'prints-cartoes-pagina-inteira' );
define( 'PRINTS_CARTOES_ARQUIVO', __FILE__ );

require_once __DIR__ . '/inc/blocos.php';
require_once __DIR__ . '/inc/pagina.php';

/** A página atual usa o modelo de página inteira? */
function prints_cartoes_modelo_ativo() {
	return is_singular() && get_page_template_slug( get_queried_object_id() ) === PRINTS_CARTOES_MODELO;
}

/* ---------------------------------------------------------------------------
 * Assets
 * ------------------------------------------------------------------------- */
function prints_cartoes_registrar_assets() {
	$base = plugin_dir_url( __FILE__ );
	$v    = PRINTS_CARTOES_VERSAO;

	wp_register_style( 'prints-cartoes-fonte', 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap', array(), null );
	wp_register_style( 'prints-cartoes', $base . 'assets/css/style.css', array( 'prints-cartoes-fonte' ), $v );
	wp_register_style( 'prints-cartoes-editor', $base . 'editor/editor.css', array( 'prints-cartoes' ), $v );

	// Página em blocos
	wp_register_script( 'prints-cartoes-formulario', $base . 'assets/js/formulario.js', array(), $v, true );
	wp_register_script(
		'prints-cartoes-blocos-editor',
		$base . 'editor/blocos.js',
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
		$v,
		true
	);

	// Versão antiga (shortcode [prints_cartoes], conteúdo em dados.js)
	wp_register_script( 'prints-cartoes-dados', $base . 'assets/js/dados.js', array(), $v, true );
	wp_add_inline_script( 'prints-cartoes-dados', 'window.FORM_BASE = ' . wp_json_encode( $base ) . ';', 'before' );
	wp_register_script( 'prints-cartoes', $base . 'assets/js/app.js', array( 'prints-cartoes-dados' ), $v, true );
}
add_action( 'init', 'prints_cartoes_registrar_assets', 5 );

/**
 * No modelo de página inteira, remove o CSS do tema para a página ficar idêntica à original.
 * Mantém o CSS dos blocos do WordPress (para outros blocos que forem adicionados) e a barra de administração.
 */
function prints_cartoes_isolar_estilos() {
	if ( ! prints_cartoes_modelo_ativo() ) {
		return;
	}
	foreach ( wp_styles()->queue as $handle ) {
		$manter = 0 === strpos( $handle, 'prints-cartoes' )
			|| 0 === strpos( $handle, 'wp-block-' )
			|| in_array( $handle, array( 'admin-bar', 'dashicons' ), true );
		if ( ! $manter ) {
			wp_dequeue_style( $handle );
		}
	}
	remove_action( 'wp_head', 'wp_custom_css_cb', 101 );
}
add_action( 'wp_enqueue_scripts', 'prints_cartoes_isolar_estilos', 9999 );
add_action( 'wp_print_styles', 'prints_cartoes_isolar_estilos', 9999 );

/** No modelo de página inteira, mantém os emojis nativos (⚪) como no original. */
function prints_cartoes_sem_emoji_wp() {
	if ( prints_cartoes_modelo_ativo() ) {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
	}
}
add_action( 'wp', 'prints_cartoes_sem_emoji_wp' );

/* ---------------------------------------------------------------------------
 * Modelo de página inteira
 * ------------------------------------------------------------------------- */
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

function prints_cartoes_favicon() {
	if ( prints_cartoes_modelo_ativo() && ! has_site_icon() ) {
		echo '<link rel="icon" type="image/png" href="' . esc_url( plugin_dir_url( __FILE__ ) . 'assets/img/logo.png' ) . '" />' . "\n";
	}
}
add_action( 'wp_head', 'prints_cartoes_favicon' );

/* ---------------------------------------------------------------------------
 * Shortcode antigo (mantido para compatibilidade)
 * ------------------------------------------------------------------------- */
function prints_cartoes_shortcode() {
	wp_enqueue_style( 'prints-cartoes' );
	wp_enqueue_script( 'prints-cartoes' );
	$modo = prints_cartoes_modelo_ativo() ? 'pagina' : 'embutido';
	return '<div id="prints-cartoes" data-modo="' . esc_attr( $modo ) . '"></div>';
}
add_shortcode( 'prints_cartoes', 'prints_cartoes_shortcode' );

/* ---------------------------------------------------------------------------
 * Ativação: cria a página /cartoes (se ainda não existir)
 * ------------------------------------------------------------------------- */
function prints_cartoes_ativar() {
	prints_cartoes_registrar_assets();
	prints_cartoes_registrar_blocos();
	prints_cartoes_criar_pagina( false );
}
register_activation_hook( __FILE__, 'prints_cartoes_ativar' );
