<?php
/**
 * Blocos do editor (nomes "prints/..." mantidos da versão Prints Cartões, para as páginas antigas continuarem válidas):
 *  - prints/formulario  : envolve a página; WhatsApp, botão, cores, layout no computador
 *  - prints/produto     : um produto inteiro (título, imagem, texto e lista de preços dentro) — move/copia junto
 *  - prints/secao       : cabeçalho, faixa ou texto (blocos nativos do WordPress dentro)
 *  - prints/lista       : lista suspensa (quantidade / preço)
 *  - prints/opcoes      : opções de marcar (radio)
 * Produto e Seção aceitam qualquer bloco do WordPress dentro e têm cor de fundo e espaçamento nativos.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function prints_paginas_categoria_blocos( $categorias ) {
	array_unshift(
		$categorias,
		array(
			'slug'  => 'prints-paginas',
			'title' => "Gráfica Print's",
			'icon'  => null,
		)
	);
	return $categorias;
}
add_filter( 'block_categories_all', 'prints_paginas_categoria_blocos' );

function prints_paginas_registrar_blocos() {
	if ( prints_paginas_conflito() ) {
		return; // versão antiga ativa: ela já registrou os blocos
	}
	$comum = array(
		'api_version'   => 3,
		'category'      => 'prints-paginas',
		'editor_script' => 'prints-paginas-blocos-editor',
		'supports'      => array( 'html' => false ),
	);

	register_block_type(
		'prints/formulario',
		array_merge(
			$comum,
			array(
				'title'           => 'Formulário de pedido (WhatsApp)',
				'description'     => 'Página de pedido: coloque dentro as seções de produto e os campos. O botão envia as escolhas para o WhatsApp.',
				'icon'            => 'feedback',
				'attributes'      => array(
					'whatsapp'   => array(
						'type'    => 'string',
						'default' => '5519983190031',
					),
					'textoBotao' => array(
						'type'    => 'string',
						'default' => 'Enviar no WhatsApp',
					),
					'corPrincipal' => array(
						'type'    => 'string',
						'default' => '#2ab200',
					),
					'corFundo'     => array(
						'type'    => 'string',
						'default' => '#ffffff',
					),
					'ladoALado'    => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'enviarImagem' => array(
						'type'    => 'boolean',
						'default' => true,
					),
				),
				'style_handles'   => array( 'prints-paginas' ),
				'editor_style_handles' => array( 'prints-paginas', 'prints-paginas-editor' ),
				'view_script_handles'  => array( 'prints-paginas-formulario' ),
				'render_callback' => 'prints_paginas_render_formulario',
			)
		)
	);

	// Ajustes nativos do WordPress (barra lateral › Estilos): cor de fundo, espaçamento, âncora
	$nativos = array(
		'html'    => false,
		'anchor'  => true,
		'color'   => array(
			'background' => true,
			'text'       => false,
		),
		'spacing' => array(
			'padding' => true,
			'margin'  => array( 'top', 'bottom' ),
		),
	);

	register_block_type(
		'prints/produto',
		array_merge(
			$comum,
			array(
				'title'           => 'Produto',
				'description'     => 'Produto completo: título, imagem, descrição e lista de preços num bloco só. Mova, copie ou duplique tudo junto. No computador a imagem fica ao lado.',
				'icon'            => 'cart',
				'ancestor'        => array( 'prints/formulario' ),
				'supports'        => $nativos,
				'attributes'      => array(
					'ocultarTitulo' => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'imagemDireita' => array(
						'type'    => 'boolean',
						'default' => false,
					),
				),
				'render_callback' => 'prints_paginas_render_produto',
			)
		)
	);

	register_block_type(
		'prints/secao',
		array_merge(
			$comum,
			array(
				'title'           => 'Seção (cabeçalho, faixa ou texto)',
				'description'     => 'Cabeçalho, logo, faixa ou texto. Dentro dela use Título, Imagem, Parágrafo ou outros blocos.',
				'icon'            => 'id-alt',
				'ancestor'        => array( 'prints/formulario' ),
				'supports'        => $nativos,
				'attributes'      => array(
					'estilo'        => array(
						'type'    => 'string',
						'default' => 'produto',
					),
					'ocultarTitulo' => array(
						'type'    => 'boolean',
						'default' => false,
					),
				),
				'render_callback' => 'prints_paginas_render_secao',
			)
		)
	);

	$campo = array(
		'titulo'      => array(
			'type'    => 'string',
			'default' => '',
		),
		'obrigatorio' => array(
			'type'    => 'boolean',
			'default' => false,
		),
		'opcoes'      => array(
			'type'    => 'array',
			'default' => array(),
			'items'   => array( 'type' => 'string' ),
		),
	);

	register_block_type(
		'prints/lista',
		array_merge(
			$comum,
			array(
				'title'           => 'Lista de opções (preços)',
				'description'     => 'Lista suspensa, ex.: quantidade e preço. Uma opção por linha.',
				'icon'            => 'list-view',
				'ancestor'        => array( 'prints/formulario' ),
				'attributes'      => array_merge(
					$campo,
					array(
						'placeholder' => array(
							'type'    => 'string',
							'default' => 'Click aqui para escolher',
						),
					)
				),
				'render_callback' => 'prints_paginas_render_lista',
			)
		)
	);

	register_block_type(
		'prints/opcoes',
		array_merge(
			$comum,
			array(
				'title'           => 'Opções de marcar',
				'description'     => 'Opções em que o cliente marca uma (ex.: cor, acabamento, confirmação). Uma opção por linha.',
				'icon'            => 'yes-alt',
				'ancestor'        => array( 'prints/formulario' ),
				'attributes'      => $campo,
				'render_callback' => 'prints_paginas_render_opcoes',
			)
		)
	);
}
add_action( 'init', 'prints_paginas_registrar_blocos' );

/* ---------------------------------------------------------------------------
 * Renderização (front-end)
 * ------------------------------------------------------------------------- */

function prints_paginas_icone_whatsapp() {
	return '<svg viewBox="64 64 896 896" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M713.5 599.9c-10.9-5.6-65.2-32.2-75.3-35.8-10.1-3.8-17.5-5.6-24.8 5.6-7.4 11.1-28.4 35.8-35 43.3-6.4 7.4-12.9 8.3-23.8 2.8-64.8-32.4-107.3-57.8-150-131.1-11.3-19.5 11.3-18.1 32.4-60.2 3.6-7.4 1.8-13.7-1-19.3-2.8-5.6-24.8-59.8-34-81.9-8.9-21.5-18.1-18.5-24.8-18.9-6.4-.4-13.7-.4-21.1-.4-7.4 0-19.3 2.8-29.4 13.7-10.1 11.1-38.6 37.8-38.6 92s39.5 106.7 44.9 114.1c5.6 7.4 77.7 118.6 188.4 166.5 70 30.2 97.4 32.8 132.4 27.6 21.3-3.2 65.2-26.6 74.3-52.5 9.1-25.8 9.1-47.9 6.4-52.5-2.7-4.9-10.1-7.7-21-13z"/><path d="M925.2 338.4c-22.6-53.7-55-101.9-96.3-143.3a444.35 444.35 0 00-143.3-96.3C630.6 75.7 572.2 64 512 64h-2c-60.6.3-119.3 12.3-174.5 35.9a445.35 445.35 0 00-142 96.5c-40.9 41.3-73 89.3-95.2 142.8-23 55.4-34.6 114.3-34.3 174.9A449.4 449.4 0 00112 714v152a46 46 0 0046 46h152.1A449.4 449.4 0 00510 960h2.1c59.9 0 118-11.6 172.7-34.3a444.48 444.48 0 00142.8-95.2c41.3-40.9 73.8-88.7 96.5-142 23.6-55.2 35.6-113.9 35.9-174.5.3-60.9-11.5-120-34.8-175.6zm-151.1 438C704 845.8 611 884 512 884h-1.7c-60.3-.3-120.2-15.3-173.1-43.5l-8.4-4.5H188V695.2l-4.5-8.4C155.3 633.9 140.3 574 140 513.7c-.4-99.7 37.7-193.3 107.6-263.8 69.8-70.5 163.1-109.5 262.8-109.9h1.7c50 0 98.5 9.7 144.2 28.9 44.6 18.7 84.6 45.6 119 80 34.3 34.3 61.3 74.4 80 119 19.4 46.2 29.1 95.2 28.9 145.8-.6 99.6-39.7 192.9-110.1 262.7z"/></svg>';
}

function prints_paginas_render_formulario( $attrs, $content ) {
	$classes = 'pc-app' . ( prints_paginas_modelo_ativo() ? '' : ' pc-embutido' );
	$numero  = preg_replace( '/\D/', '', isset( $attrs['whatsapp'] ) ? $attrs['whatsapp'] : '' );
	$botao   = isset( $attrs['textoBotao'] ) && '' !== $attrs['textoBotao'] ? $attrs['textoBotao'] : 'Enviar no WhatsApp';
	$cor     = sanitize_hex_color( isset( $attrs['corPrincipal'] ) ? $attrs['corPrincipal'] : '' );
	$fundo   = sanitize_hex_color( isset( $attrs['corFundo'] ) ? $attrs['corFundo'] : '' );
	$estilo  = ( $cor ? '--pc-cor:' . $cor . ';' : '' ) . ( $fundo ? '--pc-fundo:' . $fundo . ';' : '' );
	$lado    = ! isset( $attrs['ladoALado'] ) || $attrs['ladoALado'];
	$imagem  = ! isset( $attrs['enviarImagem'] ) || $attrs['enviarImagem'];

	return '<div class="' . esc_attr( $classes ) . '"' . ( $estilo ? ' style="' . esc_attr( $estilo ) . '"' : '' ) . '><div class="form-container">'
		. '<form class="pc-form" novalidate data-whatsapp="' . esc_attr( $numero ) . '"'
		. ( $lado ? ' data-lado-a-lado="1"' : '' ) . ( $imagem ? ' data-enviar-imagem="1"' : '' ) . '>'
		. $content
		. '<div class="form-group"><button type="submit" class="submit-button">' . prints_paginas_icone_whatsapp() . '<span>' . esc_html( $botao ) . '</span></button></div>'
		. '</form></div></div>';
}

/** Atributos do elemento externo, com as classes/estilos dos ajustes nativos (cor, espaçamento, âncora). */
function prints_paginas_wrapper( $classes ) {
	return function_exists( 'get_block_wrapper_attributes' )
		? get_block_wrapper_attributes( array( 'class' => $classes ) )
		: 'class="' . esc_attr( $classes ) . '"';
}

function prints_paginas_render_secao( $attrs, $content ) {
	$cabecalho = isset( $attrs['estilo'] ) && 'cabecalho' === $attrs['estilo'];
	$classes   = 'form-group pc-secao';
	// Seção antiga com título e imagem também é tratada como produto (imagem ao lado no computador)
	if ( preg_match( '/<h[1-6][\s>]/i', $content ) && false !== stripos( $content, '<img' ) ) {
		$classes .= ' pc-produto';
	}
	if ( ! empty( $attrs['ocultarTitulo'] ) ) {
		$classes .= ' pc-sem-titulo';
	}
	return '<div ' . prints_paginas_wrapper( $classes ) . '><div class="' . ( $cabecalho ? 'bloco-header' : 'bloco' ) . '">' . $content . '</div></div>';
}

function prints_paginas_render_produto( $attrs, $content ) {
	$classes = 'form-group pc-secao pc-produto';
	if ( ! empty( $attrs['ocultarTitulo'] ) ) {
		$classes .= ' pc-sem-titulo';
	}
	if ( ! empty( $attrs['imagemDireita'] ) ) {
		$classes .= ' pc-imagem-direita';
	}
	return '<div ' . prints_paginas_wrapper( $classes ) . '><div class="bloco">' . $content . '</div></div>';
}

function prints_paginas_opcoes_limpas( $attrs ) {
	$lista = isset( $attrs['opcoes'] ) && is_array( $attrs['opcoes'] ) ? $attrs['opcoes'] : array();
	return array_values( array_filter( array_map( 'trim', $lista ), 'strlen' ) );
}

function prints_paginas_inicio_campo( $tipo, $attrs, $id_label ) {
	$titulo = isset( $attrs['titulo'] ) ? $attrs['titulo'] : '';
	$obrig  = ! empty( $attrs['obrigatorio'] );
	$html   = '<div class="form-group pc-campo" data-tipo="' . $tipo . '" data-titulo="' . esc_attr( $titulo ) . '"' . ( $obrig ? ' data-obrigatorio="1"' : '' ) . '><div class="form-item">';
	if ( '' !== trim( $titulo ) ) {
		$html .= '<label class="form-item-label' . ( $obrig ? ' required' : '' ) . '"' . ( $id_label ? ' for="' . esc_attr( $id_label ) . '"' : '' ) . '>' . esc_html( $titulo ) . '</label>';
	}
	return $html . '<div class="form-item-control"><div class="form-item-input">';
}

function prints_paginas_fim_campo() {
	return '</div><div class="erro">Por favor, preencha este campo</div></div></div></div>';
}

function prints_paginas_render_lista( $attrs ) {
	$id   = wp_unique_id( 'pc-campo-' );
	$ph   = isset( $attrs['placeholder'] ) && '' !== $attrs['placeholder'] ? $attrs['placeholder'] : 'Selecione';
	$html = prints_paginas_inicio_campo( 'lista', $attrs, $id );
	$html .= '<div class="select-wrap"><select id="' . esc_attr( $id ) . '" name="' . esc_attr( $id ) . '" class="vazio">';
	$html .= '<option value="" disabled selected hidden>' . esc_html( $ph ) . '</option>';
	foreach ( prints_paginas_opcoes_limpas( $attrs ) as $op ) {
		$html .= '<option value="' . esc_attr( $op ) . '">' . esc_html( $op ) . '</option>';
	}
	$html .= '</select><svg viewBox="64 64 896 896" fill="currentColor" aria-hidden="true"><path d="M884 256h-75c-5.1 0-9.9 2.5-12.9 6.6L512 654.2 227.9 262.6c-3-4.1-7.8-6.6-12.9-6.6h-75c-6.5 0-10.3 7.4-6.5 12.7l352.6 486.1c12.8 17.6 39 17.6 51.7 0l352.6-486.1c3.9-5.3.1-12.7-6.4-12.7z"/></svg></div>';
	return $html . prints_paginas_fim_campo();
}

function prints_paginas_render_opcoes( $attrs ) {
	$nome = wp_unique_id( 'pc-campo-' );
	$html = prints_paginas_inicio_campo( 'opcoes', $attrs, '' );
	$html .= '<div class="radio-group" role="radiogroup">';
	foreach ( prints_paginas_opcoes_limpas( $attrs ) as $op ) {
		$html .= '<label class="radio"><input type="radio" name="' . esc_attr( $nome ) . '" value="' . esc_attr( $op ) . '"><span class="inner"></span><span class="txt">' . esc_html( $op ) . '</span></label>';
	}
	$html .= '</div>';
	return $html . prints_paginas_fim_campo();
}
