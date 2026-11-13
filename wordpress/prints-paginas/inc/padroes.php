<?php
/**
 * Modelos prontos (Padrões) no inseridor "+" › Padrões › Gráfica Print's:
 * produto com lista de preços, produto com opções, cabeçalho, faixa/logo e confirmação obrigatória.
 * Só aparecem dentro do Formulário de pedido (onde os blocos do plugin podem ser usados).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function prints_paginas_registrar_padroes() {
	if ( ! function_exists( 'register_block_pattern' ) ) {
		return;
	}
	register_block_pattern_category( 'prints-paginas', array( 'label' => "Gráfica Print's" ) );

	$titulo = '<!-- wp:heading {"level":1} --><h1 class="wp-block-heading">Nome do produto</h1><!-- /wp:heading -->';
	$imagem = '<!-- wp:image {"sizeSlug":"full","linkDestination":"none"} --><figure class="wp-block-image size-full"><img alt=""/></figure><!-- /wp:image -->';
	$texto  = '<!-- wp:paragraph --><p><strong>IMPRESSÃO: FRENTE</strong></p><!-- /wp:paragraph -->'
		. '<!-- wp:paragraph --><p>Papel: </p><!-- /wp:paragraph -->'
		. '<!-- wp:paragraph --><p>Produção: dias úteis aprox.</p><!-- /wp:paragraph -->';
	$lista  = array(
		'titulo'      => '⚪ Produto | tamanho',
		'placeholder' => 'Click aqui para escolher',
		'opcoes'      => array( 'Nenhuma das opções', '50 UND | R$ 0.00 | prazo | tamanho | papel', '100 UND | R$ 0.00 | prazo | tamanho | papel' ),
	);
	$opcoes = array(
		'titulo' => '',
		'opcoes' => array( 'Acabamento | R$0 Milheiro' ),
	);

	$padroes = array(
		'produto-lista'  => array(
			'title'       => 'Produto com lista de preços',
			'description' => 'Título, imagem, descrição e lista de quantidades/preços num bloco Produto.',
			'content'     => '<!-- wp:prints/produto -->' . $titulo . $imagem . $texto
				. '<!-- wp:prints/lista ' . wp_json_encode( $lista, JSON_UNESCAPED_UNICODE ) . ' /-->'
				. '<!-- /wp:prints/produto -->',
		),
		'produto-opcoes' => array(
			'title'       => 'Produto com opções de marcar',
			'description' => 'Título, imagem, descrição e opções de marcar (ex.: acabamento).',
			'content'     => '<!-- wp:prints/produto -->' . $titulo . $imagem . $texto
				. '<!-- wp:prints/opcoes ' . wp_json_encode( $opcoes, JSON_UNESCAPED_UNICODE ) . ' /-->'
				. '<!-- /wp:prints/produto -->',
		),
		'cabecalho'      => array(
			'title'       => 'Cabeçalho com texto',
			'description' => 'Título e texto (ex.: "Pagamento & Produção").',
			'content'     => '<!-- wp:prints/secao {"estilo":"cabecalho"} -->'
				. '<!-- wp:heading {"level":1} --><h1 class="wp-block-heading">Título</h1><!-- /wp:heading -->'
				. '<!-- wp:paragraph --><p>Texto</p><!-- /wp:paragraph -->'
				. '<!-- /wp:prints/secao -->',
		),
		'faixa'          => array(
			'title'       => 'Faixa ou logo (imagem)',
			'description' => 'Imagem sozinha, ex.: logo ou banner.',
			'content'     => '<!-- wp:prints/secao -->' . $imagem . '<!-- /wp:prints/secao -->',
		),
		'confirmacao'    => array(
			'title'       => 'Confirmação obrigatória',
			'description' => 'Opção que o cliente precisa marcar (ex.: forma de pagamento, prazo).',
			'content'     => '<!-- wp:prints/opcoes ' . wp_json_encode(
				array(
					'titulo'      => '⚪ Pagamento',
					'obrigatorio' => true,
					'opcoes'      => array( 'Só imprimimos mediante Pagamento Integral (Pix ou Cartão)' ),
				),
				JSON_UNESCAPED_UNICODE
			) . ' /-->',
		),
	);

	foreach ( $padroes as $nome => $p ) {
		register_block_pattern(
			'prints-paginas/' . $nome,
			array(
				'title'       => $p['title'],
				'description' => $p['description'],
				'categories'  => array( 'prints-paginas' ),
				'content'     => $p['content'],
			)
		);
	}
}
add_action( 'init', 'prints_paginas_registrar_padroes' );
