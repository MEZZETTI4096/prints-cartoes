<?php
/**
 * Estrutura das páginas em blocos:
 *  - "Seção com título + imagem" seguida de listas/opções vira um bloco Produto (tudo junto: move e copia inteiro);
 *  - o Formulário fica protegido contra exclusão acidental (cadeado do WordPress; dá para destravar no editor).
 * Usado na importação e para atualizar, uma vez, as páginas criadas por versões anteriores
 * (o WordPress guarda a versão anterior em Revisões).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PRINTS_PAGINAS_ESTRUTURA', 2 );

function prints_paginas_e_campo( $b ) {
	return in_array( $b['blockName'], array( 'prints/lista', 'prints/opcoes' ), true );
}

/** Seção antiga que é um produto: tem título e imagem. */
function prints_paginas_secao_e_produto( $b ) {
	if ( 'prints/secao' !== $b['blockName'] ) {
		return false;
	}
	$nomes = wp_list_pluck( $b['innerBlocks'], 'blockName' );
	return in_array( 'core/heading', $nomes, true ) && in_array( 'core/image', $nomes, true );
}

/** Agrupa "Seção de produto + campos logo abaixo" em blocos Produto. */
function prints_paginas_agrupar_produtos( $blocos ) {
	$saida = array();
	$total = count( $blocos );
	for ( $i = 0; $i < $total; $i++ ) {
		$b = $blocos[ $i ];
		if ( empty( $b['blockName'] ) ) {
			continue; // espaços em branco entre blocos
		}
		if ( ! prints_paginas_secao_e_produto( $b ) ) {
			$saida[] = $b;
			continue;
		}
		$filhos = $b['innerBlocks'];
		while ( $i + 1 < $total && ( empty( $blocos[ $i + 1 ]['blockName'] ) || prints_paginas_e_campo( $blocos[ $i + 1 ] ) ) ) {
			$i++;
			if ( ! empty( $blocos[ $i ]['blockName'] ) ) {
				$filhos[] = $blocos[ $i ];
			}
		}
		$attrs = isset( $b['attrs'] ) ? $b['attrs'] : array();
		unset( $attrs['estilo'] ); // mantém ocultarTitulo, cores, espaçamento, âncora
		$saida[] = prints_paginas_bloco( 'prints/produto', $attrs, '', $filhos );
	}
	return $saida;
}

/** Aplica a estrutura atual a uma lista de blocos (resultado de parse_blocks). */
function prints_paginas_estruturar( $blocos ) {
	foreach ( $blocos as &$b ) {
		if ( isset( $b['blockName'] ) && 'prints/formulario' === $b['blockName'] ) {
			$b['innerBlocks']  = prints_paginas_agrupar_produtos( $b['innerBlocks'] );
			$b['innerContent'] = array_fill( 0, count( $b['innerBlocks'] ), null );
			$b['innerHTML']    = '';
			if ( ! isset( $b['attrs']['lock'] ) ) {
				$b['attrs']['lock'] = array( 'remove' => true );
			}
		}
	}
	unset( $b );
	return $blocos;
}

/** Devolve o conteúdo (HTML de blocos) na estrutura atual; igual ao original se não houver Formulário. */
function prints_paginas_estruturar_conteudo( $conteudo ) {
	if ( false === strpos( $conteudo, '<!-- wp:prints/formulario' ) ) {
		return $conteudo;
	}
	return serialize_blocks( prints_paginas_estruturar( parse_blocks( $conteudo ) ) );
}

/** Atualiza uma vez as páginas criadas por versões anteriores do plugin. */
function prints_paginas_atualizar_estrutura() {
	if ( (int) get_option( 'prints_paginas_estrutura', 0 ) >= PRINTS_PAGINAS_ESTRUTURA ) {
		return;
	}
	global $wpdb;
	$ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'page' AND post_status <> 'trash' AND post_content LIKE %s",
			'%' . $wpdb->esc_like( '<!-- wp:prints/formulario' ) . '%'
		)
	);
	kses_remove_filters(); // o conteúdo é do próprio plugin; não deixar o filtro de HTML mexer nos blocos
	foreach ( $ids as $id ) {
		$post = get_post( $id );
		$novo = prints_paginas_estruturar_conteudo( $post->post_content );
		if ( $novo !== $post->post_content ) {
			wp_update_post(
				array(
					'ID'           => $id,
					'post_content' => wp_slash( $novo ),
				)
			);
		}
		$original = get_post_meta( $id, PRINTS_PAGINAS_META_ORIGINAL, true );
		if ( $original ) {
			update_post_meta( $id, PRINTS_PAGINAS_META_ORIGINAL, wp_slash( prints_paginas_estruturar_conteudo( $original ) ) );
		}
	}
	kses_init();
	update_option( 'prints_paginas_estrutura', PRINTS_PAGINAS_ESTRUTURA );
}
add_action( 'admin_init', 'prints_paginas_atualizar_estrutura' );
