<?php
/**
 * Cria (ou recria) a página /cartoes em blocos, com as imagens na Biblioteca de Mídia.
 * Conteúdo inicial vem de assets/js/dados.js (o mesmo arquivo do site estático).
 * Inclui a tela Ferramentas > Prints Cartões.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Lê o conteúdo original de assets/js/dados.js. */
function prints_cartoes_dados_originais() {
	$arquivo = plugin_dir_path( PRINTS_CARTOES_ARQUIVO ) . 'assets/js/dados.js';
	$texto   = is_readable( $arquivo ) ? file_get_contents( $arquivo ) : '';
	if ( ! preg_match( '/\{.*\}/s', $texto, $m ) ) {
		return null;
	}
	return json_decode( $m[0], true );
}

/**
 * Copia uma imagem do plugin para a Biblioteca de Mídia (uma vez só) e devolve array( id, url ).
 */
function prints_cartoes_imagem_midia( $relativo, $titulo ) {
	$nome = basename( $relativo );

	$existentes = get_posts(
		array(
			'post_type'   => 'attachment',
			'post_status' => 'inherit',
			'numberposts' => 1,
			'meta_key'    => '_prints_cartoes_origem',
			'meta_value'  => $nome,
			'fields'      => 'ids',
		)
	);
	if ( $existentes ) {
		return array( (int) $existentes[0], wp_get_attachment_url( $existentes[0] ) );
	}

	$origem = plugin_dir_path( PRINTS_CARTOES_ARQUIVO ) . $relativo;
	if ( ! is_readable( $origem ) ) {
		return array( 0, plugin_dir_url( PRINTS_CARTOES_ARQUIVO ) . $relativo );
	}
	$envio = wp_upload_bits( 'prints-' . $nome, null, file_get_contents( $origem ) );
	if ( ! empty( $envio['error'] ) ) {
		return array( 0, plugin_dir_url( PRINTS_CARTOES_ARQUIVO ) . $relativo );
	}

	$tipo = wp_check_filetype( $envio['file'] );
	$id   = wp_insert_attachment(
		array(
			'post_title'     => $titulo ? $titulo : pathinfo( $nome, PATHINFO_FILENAME ),
			'post_mime_type' => $tipo['type'],
			'post_status'    => 'inherit',
		),
		$envio['file']
	);
	if ( ! $id || is_wp_error( $id ) ) {
		return array( 0, $envio['url'] );
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $envio['file'] ) );
	update_post_meta( $id, '_prints_cartoes_origem', $nome );

	return array( (int) $id, wp_get_attachment_url( $id ) );
}

/** Monta um bloco no formato aceito por serialize_blocks(). */
function prints_cartoes_bloco( $nome, $attrs = array(), $html = '', $internos = array() ) {
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

/** Gera o conteúdo da página (blocos) a partir dos dados originais. */
function prints_cartoes_conteudo_inicial() {
	$dados = prints_cartoes_dados_originais();
	if ( ! $dados ) {
		return '';
	}

	$internos = array();
	foreach ( $dados['campos'] as $c ) {
		$tipo   = $c['tipo'];
		$titulo = isset( $c['titulo'] ) ? $c['titulo'] : '';

		if ( 'dropdown' === $tipo || 'radio' === $tipo ) {
			$attrs = array(
				'titulo' => $titulo,
				'opcoes' => isset( $c['opcoes'] ) ? $c['opcoes'] : array(),
			);
			if ( ! empty( $c['obrigatorio'] ) ) {
				$attrs['obrigatorio'] = true;
			}
			if ( 'dropdown' === $tipo && ! empty( $c['placeholder'] ) ) {
				$attrs['placeholder'] = $c['placeholder'];
			}
			$internos[] = prints_cartoes_bloco( 'dropdown' === $tipo ? 'prints/lista' : 'prints/opcoes', $attrs );
			continue;
		}

		// Seção de conteúdo (título + imagem + parágrafos)
		$filhos = array();
		if ( '' !== $titulo || 'header' === $tipo ) {
			$filhos[] = prints_cartoes_bloco( 'core/heading', array( 'level' => 1 ), '<h1 class="wp-block-heading">' . esc_html( $titulo ) . '</h1>' );
		}
		if ( ! empty( $c['imagem'] ) ) {
			list( $id, $url ) = prints_cartoes_imagem_midia( $c['imagem'], $titulo );
			$attrs            = array(
				'sizeSlug'        => 'full',
				'linkDestination' => 'none',
			);
			$classe_img       = '';
			if ( $id ) {
				$attrs      = array( 'id' => $id ) + $attrs;
				$classe_img = ' class="wp-image-' . $id . '"';
			}
			$filhos[] = prints_cartoes_bloco( 'core/image', $attrs, '<figure class="wp-block-image size-full"><img src="' . esc_url( $url ) . '" alt="' . esc_attr( $titulo ) . '"' . $classe_img . '/></figure>' );
		}
		if ( ! empty( $c['descricao'] ) && preg_match_all( '#<p>(.*?)</p>#s', $c['descricao'], $ps ) ) {
			foreach ( $ps[1] as $p ) {
				$filhos[] = prints_cartoes_bloco( 'core/paragraph', array(), '<p>' . $p . '</p>' );
			}
		}
		$attrs_secao = 'header' === $tipo ? array( 'estilo' => 'cabecalho' ) : array();
		$internos[]  = prints_cartoes_bloco( 'prints/secao', $attrs_secao, '', $filhos );
	}

	$form = prints_cartoes_bloco(
		'prints/formulario',
		array(
			'whatsapp'   => $dados['whatsapp'],
			'textoBotao' => isset( $dados['textoBotao'] ) ? $dados['textoBotao'] : 'Enviar no WhatsApp',
		),
		'',
		$internos
	);
	return serialize_blocks( array( $form ) );
}

/**
 * Cria a página /cartoes. Se já existir: mantém (padrão) ou substitui o conteúdo ($sobrescrever).
 * Retorna o ID da página.
 */
function prints_cartoes_criar_pagina( $sobrescrever = false ) {
	$pagina = get_page_by_path( 'cartoes', OBJECT, 'page' );
	if ( $pagina && ! $sobrescrever ) {
		return (int) $pagina->ID;
	}

	$dados = array(
		'post_title'     => 'Cartões de Visitas',
		'post_name'      => 'cartoes',
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'comment_status' => 'closed',
		'post_content'   => wp_slash( prints_cartoes_conteudo_inicial() ),
	);
	if ( $pagina ) {
		$dados['ID'] = $pagina->ID;
		$id          = wp_update_post( $dados );
	} else {
		$id = wp_insert_post( $dados );
	}
	if ( $id && ! is_wp_error( $id ) ) {
		update_post_meta( $id, '_wp_page_template', PRINTS_CARTOES_MODELO );
	}
	return (int) $id;
}

/* ---------------------------------------------------------------------------
 * Ferramentas > Prints Cartões
 * ------------------------------------------------------------------------- */
function prints_cartoes_menu() {
	add_management_page( 'Prints Cartões', 'Prints Cartões', 'manage_options', 'prints-cartoes', 'prints_cartoes_tela' );
}
add_action( 'admin_menu', 'prints_cartoes_menu' );

function prints_cartoes_tela() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$msg = '';
	if ( isset( $_POST['prints_cartoes_acao'] ) && check_admin_referer( 'prints_cartoes_recriar' ) ) {
		$sobrescrever = 'recriar' === $_POST['prints_cartoes_acao'];
		$id           = prints_cartoes_criar_pagina( $sobrescrever );
		$msg          = $id ? ( $sobrescrever ? 'Página recriada com o conteúdo original.' : 'Página criada.' ) : 'Não foi possível criar a página.';
	}
	$pagina = get_page_by_path( 'cartoes', OBJECT, 'page' );
	?>
	<div class="wrap">
		<h1>Prints Cartões</h1>
		<?php if ( $msg ) : ?>
			<div class="notice notice-success"><p><?php echo esc_html( $msg ); ?></p></div>
		<?php endif; ?>

		<?php if ( $pagina ) : ?>
			<p>A página de cartões está em <a href="<?php echo esc_url( get_permalink( $pagina ) ); ?>" target="_blank"><?php echo esc_html( get_permalink( $pagina ) ); ?></a>.</p>
			<p><a class="button button-primary" href="<?php echo esc_url( get_edit_post_link( $pagina->ID ) ); ?>">Editar a página no editor de blocos</a></p>
			<h2>Como editar</h2>
			<ul style="list-style:disc;padding-left:20px">
				<li><strong>Textos, títulos e imagens</strong>: clique e edite direto. Para trocar uma imagem, selecione-a e use <em>Substituir</em>.</li>
				<li><strong>Preços</strong>: clique na <em>Lista de opções</em> e edite as linhas (uma opção por linha).</li>
				<li><strong>Novo produto</strong>: selecione uma <em>Seção</em> e use <em>Duplicar</em> (menu ⋮), ou adicione os blocos da categoria <em>Gráfica Print's</em>.</li>
				<li><strong>Número do WhatsApp e texto do botão</strong>: selecione o bloco <em>Formulário de pedido</em> e veja a barra lateral.</li>
			</ul>
			<hr>
			<form method="post" onsubmit="return confirm('Isto apaga as alterações feitas na página e volta ao conteúdo original. Continuar?');">
				<?php wp_nonce_field( 'prints_cartoes_recriar' ); ?>
				<input type="hidden" name="prints_cartoes_acao" value="recriar">
				<p><button class="button">Recriar a página com o conteúdo original</button></p>
				<p class="description">Use só se quiser descartar as alterações. (Também é possível voltar versões em <em>Revisões</em>, no editor.)</p>
			</form>
		<?php else : ?>
			<form method="post">
				<?php wp_nonce_field( 'prints_cartoes_recriar' ); ?>
				<input type="hidden" name="prints_cartoes_acao" value="criar">
				<p><button class="button button-primary">Criar a página de cartões</button></p>
			</form>
		<?php endif; ?>
	</div>
	<?php
}

/** Link "Configurar" na lista de plugins. */
function prints_cartoes_link_plugin( $links ) {
	array_unshift( $links, '<a href="' . esc_url( admin_url( 'tools.php?page=prints-cartoes' ) ) . '">Configurar</a>' );
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( PRINTS_CARTOES_ARQUIVO ), 'prints_cartoes_link_plugin' );
