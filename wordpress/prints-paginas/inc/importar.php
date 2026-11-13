<?php
/**
 * Importação de páginas a partir de um pacote .zip.
 *
 * Formato do pacote (pode ter várias páginas):
 *   paginas/<slug>/pagina.json
 *   paginas/<slug>/img/<imagens>
 * (ou só pagina.json + img/ na raiz do zip, para uma página)
 *
 * pagina.json:
 *   { "formato": 1, "titulo": "Cartões de Visitas", "slug": "cartoes", "whatsapp": "55...",
 *     "textoBotao": "...", "corPrincipal": "#2ab200", "corFundo": "#ffffff",
 *     "ladoALado": true, "enviarImagem": true,
 *     "campos": [ {tipo: image|header|dropdown|radio, titulo, descricao, imagem: "img/x.png",
 *                  ocultarTitulo, obrigatorio, placeholder, opcoes[]} ] }
 *
 * Cada página vira blocos editáveis (Formulário > Seções / Listas / Opções); as imagens vão para a Biblioteca de Mídia.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PRINTS_PAGINAS_META_ORIGINAL', '_prints_paginas_original' );
define( 'PRINTS_PAGINAS_META_IMAGEM', '_prints_paginas_origem' );

/**
 * Lê o zip e devolve as páginas: array( array( 'dados' => array, 'imagens' => array( caminho => bytes ) ) ).
 *
 * @return array|WP_Error
 */
function prints_paginas_ler_pacote( $arquivo ) {
	if ( ! class_exists( 'ZipArchive' ) ) {
		return new WP_Error( 'sem_zip', 'O servidor não tem a extensão ZipArchive do PHP. Peça à hospedagem para ativá-la.' );
	}
	$zip = new ZipArchive();
	if ( true !== $zip->open( $arquivo ) ) {
		return new WP_Error( 'zip_invalido', 'Não foi possível abrir o arquivo. Envie o pacote .zip gerado para as páginas.' );
	}

	$jsons    = array();
	$imagens  = array();
	$extensao = '/\.(png|jpe?g|gif|webp)$/i';
	for ( $i = 0; $i < $zip->numFiles; $i++ ) {
		$info = $zip->statIndex( $i );
		$nome = str_replace( '\\', '/', $info['name'] );
		if ( '/' === substr( $nome, -1 ) || false !== strpos( $nome, '..' ) || 0 === strpos( $nome, '__MACOSX' ) ) {
			continue;
		}
		if ( 'pagina.json' === basename( $nome ) && $info['size'] < 2 * MB_IN_BYTES ) {
			$jsons[ $nome ] = $zip->getFromIndex( $i );
		} elseif ( preg_match( $extensao, $nome ) && $info['size'] < 20 * MB_IN_BYTES ) {
			$imagens[ $nome ] = $i;
		}
	}

	if ( ! $jsons ) {
		$zip->close();
		return new WP_Error( 'sem_pagina', 'O pacote não tem nenhum pagina.json.' );
	}

	$paginas = array();
	foreach ( $jsons as $caminho => $texto ) {
		$dados = json_decode( preg_replace( '/^\xEF\xBB\xBF/', '', $texto ), true );
		if ( ! is_array( $dados ) || empty( $dados['campos'] ) || ! is_array( $dados['campos'] ) ) {
			$paginas[] = array( 'erro' => $caminho . ': conteúdo inválido.' );
			continue;
		}
		$pasta = dirname( $caminho );
		$pasta = '.' === $pasta ? '' : $pasta . '/';
		$bytes = array();
		foreach ( $imagens as $img => $indice ) {
			if ( '' === $pasta || 0 === strpos( $img, $pasta ) ) {
				$bytes[ substr( $img, strlen( $pasta ) ) ] = $indice;
			}
		}
		$paginas[] = array(
			'dados'   => $dados,
			'imagens' => $bytes,
		);
	}

	// Lê as imagens só agora (apenas as usadas)
	foreach ( $paginas as &$p ) {
		if ( empty( $p['dados'] ) ) {
			continue;
		}
		$usadas = array();
		foreach ( $p['dados']['campos'] as $c ) {
			if ( ! empty( $c['imagem'] ) && isset( $p['imagens'][ ltrim( $c['imagem'], './' ) ] ) ) {
				$rel            = ltrim( $c['imagem'], './' );
				$usadas[ $rel ] = $zip->getFromIndex( $p['imagens'][ $rel ] );
			}
		}
		$p['imagens'] = $usadas;
	}
	unset( $p );
	$zip->close();
	return $paginas;
}

/**
 * Coloca uma imagem do pacote na Biblioteca de Mídia (reaproveita se uma imagem idêntica já foi importada).
 *
 * @return array( id, url ) ou array( 0, '' ) se falhar.
 */
function prints_paginas_imagem_midia( $slug, $nome, $bytes, $titulo ) {
	// Mesma imagem (mesmo conteúdo) em várias páginas, ex.: o logo, é reaproveitada
	$origem = 'md5:' . md5( $bytes );

	$existentes = get_posts(
		array(
			'post_type'   => 'attachment',
			'post_status' => 'inherit',
			'numberposts' => 1,
			'meta_key'    => PRINTS_PAGINAS_META_IMAGEM,
			'meta_value'  => $origem,
			'fields'      => 'ids',
		)
	);
	if ( $existentes && wp_get_attachment_url( $existentes[0] ) ) {
		return array( (int) $existentes[0], wp_get_attachment_url( $existentes[0] ) );
	}

	$arquivo = sanitize_file_name( $slug . '-' . basename( $nome ) );
	$envio   = wp_upload_bits( $arquivo, null, $bytes );
	if ( ! empty( $envio['error'] ) ) {
		return array( 0, '' );
	}
	// Confere se o conteúdo é mesmo uma imagem
	$tipo = wp_check_filetype_and_ext( $envio['file'], $arquivo );
	if ( empty( $tipo['type'] ) || 0 !== strpos( $tipo['type'], 'image/' ) ) {
		wp_delete_file( $envio['file'] );
		return array( 0, '' );
	}

	$id = wp_insert_attachment(
		array(
			'post_title'     => $titulo ? wp_strip_all_tags( $titulo ) : pathinfo( $nome, PATHINFO_FILENAME ),
			'post_mime_type' => $tipo['type'],
			'post_status'    => 'inherit',
		),
		$envio['file']
	);
	if ( ! $id || is_wp_error( $id ) ) {
		return array( 0, '' );
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $envio['file'] ) );
	update_post_meta( $id, PRINTS_PAGINAS_META_IMAGEM, $origem );

	return array( (int) $id, wp_get_attachment_url( $id ) );
}

/** Monta um bloco no formato aceito por serialize_blocks(). */
function prints_paginas_bloco( $nome, $attrs = array(), $html = '', $internos = array() ) {
	if ( $internos ) {
		$conteudo = array_fill( 0, count( $internos ), null );
	} else {
		$conteudo = '' === $html ? array() : array( $html );
	}
	return array(
		'blockName'    => $nome,
		'attrs'        => $attrs,
		'innerBlocks'  => $internos,
		'innerHTML'    => $html,
		'innerContent' => $conteudo,
	);
}

/** Lista de textos limpa (opções). */
function prints_paginas_textos( $lista ) {
	if ( ! is_array( $lista ) ) {
		return array();
	}
	return array_values( array_filter( array_map( 'sanitize_text_field', $lista ), 'strlen' ) );
}

/**
 * Gera os blocos da página.
 *
 * @param array $dados   conteúdo do pagina.json
 * @param array $imagens caminho => bytes
 */
function prints_paginas_conteudo( $dados, $imagens ) {
	$slug     = sanitize_title( $dados['slug'] );
	$internos = array();

	foreach ( $dados['campos'] as $c ) {
		if ( ! is_array( $c ) || empty( $c['tipo'] ) ) {
			continue;
		}
		$tipo   = $c['tipo'];
		$titulo = isset( $c['titulo'] ) ? sanitize_text_field( $c['titulo'] ) : '';

		if ( 'dropdown' === $tipo || 'radio' === $tipo ) {
			$attrs = array(
				'titulo' => $titulo,
				'opcoes' => prints_paginas_textos( isset( $c['opcoes'] ) ? $c['opcoes'] : array() ),
			);
			if ( ! empty( $c['obrigatorio'] ) ) {
				$attrs['obrigatorio'] = true;
			}
			if ( 'dropdown' === $tipo && ! empty( $c['placeholder'] ) ) {
				$attrs['placeholder'] = sanitize_text_field( $c['placeholder'] );
			}
			$internos[] = prints_paginas_bloco( 'dropdown' === $tipo ? 'prints/lista' : 'prints/opcoes', $attrs );
			continue;
		}
		if ( 'image' !== $tipo && 'header' !== $tipo ) {
			continue; // tipo desconhecido
		}

		// Seção: título + imagem + parágrafos (blocos nativos, editáveis)
		$filhos = array();
		if ( '' !== $titulo || 'header' === $tipo ) {
			$filhos[] = prints_paginas_bloco( 'core/heading', array( 'level' => 1 ), '<h1 class="wp-block-heading">' . esc_html( $titulo ) . '</h1>' );
		}
		if ( ! empty( $c['imagem'] ) ) {
			$rel = ltrim( $c['imagem'], './' );
			$id  = 0;
			$url = '';
			if ( isset( $imagens[ $rel ] ) ) {
				list( $id, $url ) = prints_paginas_imagem_midia( $slug, $rel, $imagens[ $rel ], $titulo );
			} elseif ( preg_match( '#^https?://#i', $c['imagem'] ) ) {
				$url = $c['imagem'];
			}
			if ( $url ) {
				$attrs = array(
					'sizeSlug'        => 'full',
					'linkDestination' => 'none',
				);
				$classe = '';
				if ( $id ) {
					$attrs  = array( 'id' => $id ) + $attrs;
					$classe = ' class="wp-image-' . $id . '"';
				}
				$filhos[] = prints_paginas_bloco( 'core/image', $attrs, '<figure class="wp-block-image size-full"><img src="' . esc_url( $url ) . '" alt="' . esc_attr( $titulo ) . '"' . $classe . '/></figure>' );
			}
		}
		if ( ! empty( $c['descricao'] ) && preg_match_all( '#<p[^>]*>(.*?)</p>#s', wp_kses_post( $c['descricao'] ), $ps ) ) {
			foreach ( $ps[1] as $p ) {
				$filhos[] = prints_paginas_bloco( 'core/paragraph', array(), '<p>' . $p . '</p>' );
			}
		}
		$attrs_secao = array();
		if ( 'header' === $tipo ) {
			$attrs_secao['estilo'] = 'cabecalho';
		}
		if ( ! empty( $c['ocultarTitulo'] ) ) {
			$attrs_secao['ocultarTitulo'] = true;
		}
		$internos[] = prints_paginas_bloco( 'prints/secao', $attrs_secao, '', $filhos );
	}

	$form = array(
		'whatsapp'   => preg_replace( '/\D/', '', isset( $dados['whatsapp'] ) ? $dados['whatsapp'] : '' ),
		'textoBotao' => isset( $dados['textoBotao'] ) ? sanitize_text_field( $dados['textoBotao'] ) : 'Enviar no WhatsApp',
	);
	foreach ( array( 'corPrincipal', 'corFundo' ) as $cor ) {
		if ( ! empty( $dados[ $cor ] ) && sanitize_hex_color( $dados[ $cor ] ) ) {
			$form[ $cor ] = sanitize_hex_color( $dados[ $cor ] );
		}
	}
	foreach ( array( 'ladoALado', 'enviarImagem' ) as $opcao ) {
		if ( isset( $dados[ $opcao ] ) && ! $dados[ $opcao ] ) {
			$form[ $opcao ] = false;
		}
	}
	// Seção de produto + listas viram um bloco Produto; Formulário protegido contra exclusão
	return serialize_blocks( prints_paginas_estruturar( array( prints_paginas_bloco( 'prints/formulario', $form, '', $internos ) ) ) );
}

/**
 * Cria ou atualiza a página. Retorna array( 'id', 'acao' ) ou WP_Error.
 */
function prints_paginas_salvar_pagina( $dados, $conteudo, $substituir ) {
	$slug   = sanitize_title( $dados['slug'] );
	$titulo = ! empty( $dados['titulo'] ) ? sanitize_text_field( $dados['titulo'] ) : $slug;
	$pagina = get_page_by_path( $slug, OBJECT, 'page' );

	if ( $pagina && ! $substituir ) {
		return array(
			'id'   => (int) $pagina->ID,
			'acao' => 'existente',
		);
	}

	$post = array(
		'post_title'     => $titulo,
		'post_name'      => $slug,
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'comment_status' => 'closed',
		'post_content'   => wp_slash( $conteudo ),
		'page_template'  => PRINTS_PAGINAS_MODELO, // também troca o modelo antigo "prints-cartoes-pagina-inteira"
	);
	if ( $pagina ) {
		$post['ID'] = $pagina->ID;
		$id         = wp_update_post( $post, true );
	} else {
		$id = wp_insert_post( $post, true );
	}
	if ( is_wp_error( $id ) ) {
		return $id;
	}
	update_post_meta( $id, '_wp_page_template', PRINTS_PAGINAS_MODELO );
	update_post_meta( $id, PRINTS_PAGINAS_META_ORIGINAL, wp_slash( $conteudo ) );
	return array(
		'id'   => (int) $id,
		'acao' => $pagina ? 'atualizada' : 'criada',
	);
}

/**
 * Importa todas as páginas de um pacote .zip.
 *
 * @param string $arquivo    caminho do .zip
 * @param bool   $substituir substituir o conteúdo de páginas que já existem com o mesmo endereço
 * @return array|WP_Error lista de resultados: array( titulo, slug, id, acao, erro )
 */
function prints_paginas_importar_zip( $arquivo, $substituir = false ) {
	$paginas = prints_paginas_ler_pacote( $arquivo );
	if ( is_wp_error( $paginas ) ) {
		return $paginas;
	}

	$resultado = array();
	foreach ( $paginas as $p ) {
		if ( ! empty( $p['erro'] ) ) {
			$resultado[] = array( 'erro' => $p['erro'] );
			continue;
		}
		$dados = $p['dados'];
		if ( empty( $dados['slug'] ) || '' === sanitize_title( $dados['slug'] ) ) {
			$resultado[] = array( 'erro' => 'Página sem "slug" (endereço) no pagina.json.' );
			continue;
		}
		$slug   = sanitize_title( $dados['slug'] );
		$existe = get_page_by_path( $slug, OBJECT, 'page' );
		if ( $existe && ! $substituir ) {
			$resultado[] = array(
				'titulo' => $existe->post_title,
				'slug'   => $slug,
				'id'     => (int) $existe->ID,
				'acao'   => 'existente',
			);
			continue;
		}

		$conteudo = prints_paginas_conteudo( $dados, $p['imagens'] );
		$salvo    = prints_paginas_salvar_pagina( $dados, $conteudo, $substituir );
		if ( is_wp_error( $salvo ) ) {
			$resultado[] = array(
				'slug' => $slug,
				'erro' => $salvo->get_error_message(),
			);
			continue;
		}
		$resultado[] = array(
			'titulo' => get_the_title( $salvo['id'] ),
			'slug'   => $slug,
			'id'     => $salvo['id'],
			'acao'   => $salvo['acao'],
		);
	}
	return $resultado;
}

/** Volta a página ao conteúdo de quando foi importada. */
function prints_paginas_restaurar( $id ) {
	$original = get_post_meta( $id, PRINTS_PAGINAS_META_ORIGINAL, true );
	if ( ! $original ) {
		return false;
	}
	$r = wp_update_post(
		array(
			'ID'           => $id,
			'post_content' => wp_slash( $original ),
		),
		true
	);
	return ! is_wp_error( $r );
}
