<?php
/**
 * Ferramentas › Print's Páginas: importar pacotes de páginas e restaurar páginas ao original.
 * Disponível para quem pode editar páginas e enviar arquivos (Administrador e Editor).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function prints_paginas_pode_importar() {
	return current_user_can( 'edit_pages' ) && current_user_can( 'publish_pages' ) && current_user_can( 'upload_files' );
}

function prints_paginas_menu() {
	add_management_page( "Print's Páginas", "Print's Páginas", 'edit_pages', 'prints-paginas', 'prints_paginas_tela' );
}
add_action( 'admin_menu', 'prints_paginas_menu' );

/** Link "Importar páginas" na lista de plugins. */
function prints_paginas_link_plugin( $links ) {
	array_unshift( $links, '<a href="' . esc_url( admin_url( 'tools.php?page=prints-paginas' ) ) . '">Importar páginas</a>' );
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( PRINTS_PAGINAS_ARQUIVO ), 'prints_paginas_link_plugin' );

/** Processa os formulários da tela. Retorna array( tipo, mensagem, resultados ). */
function prints_paginas_processar() {
	if ( 'POST' !== $_SERVER['REQUEST_METHOD'] || empty( $_POST['prints_paginas_acao'] ) ) {
		return null;
	}
	$acao = sanitize_key( $_POST['prints_paginas_acao'] );

	if ( 'importar' === $acao ) {
		check_admin_referer( 'prints_paginas_importar' );
		if ( ! prints_paginas_pode_importar() ) {
			return array( 'error', 'Seu usuário não tem permissão para criar páginas e enviar arquivos.', array() );
		}
		if ( empty( $_FILES['pacote']['tmp_name'] ) || ! is_uploaded_file( $_FILES['pacote']['tmp_name'] ) ) {
			$erro = isset( $_FILES['pacote']['error'] ) ? (int) $_FILES['pacote']['error'] : 0;
			$msg  = in_array( $erro, array( UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE ), true )
				? 'O arquivo é maior que o limite de envio da hospedagem (' . size_format( wp_max_upload_size() ) . ').'
				: 'Escolha o pacote .zip das páginas.';
			return array( 'error', $msg, array() );
		}
		if ( ! preg_match( '/\.zip$/i', sanitize_file_name( wp_unslash( $_FILES['pacote']['name'] ) ) ) ) {
			return array( 'error', 'O pacote precisa ser um arquivo .zip.', array() );
		}
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 300 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		}
		$r = prints_paginas_importar_zip( $_FILES['pacote']['tmp_name'], ! empty( $_POST['substituir'] ) );
		if ( is_wp_error( $r ) ) {
			return array( 'error', $r->get_error_message(), array() );
		}
		return array( 'success', 'Importação concluída.', $r );
	}

	if ( 'restaurar' === $acao ) {
		$id = isset( $_POST['pagina'] ) ? absint( $_POST['pagina'] ) : 0;
		check_admin_referer( 'prints_paginas_restaurar_' . $id );
		if ( ! $id || ! current_user_can( 'edit_post', $id ) ) {
			return array( 'error', 'Sem permissão para editar esta página.', array() );
		}
		return prints_paginas_restaurar( $id )
			? array( 'success', '"' . get_the_title( $id ) . '" voltou ao conteúdo original.', array() )
			: array( 'error', 'Não foi possível restaurar a página.', array() );
	}
	return null;
}

function prints_paginas_tela() {
	if ( ! current_user_can( 'edit_pages' ) ) {
		return;
	}
	$retorno = prints_paginas_processar();
	$paginas = get_posts(
		array(
			'post_type'   => 'page',
			'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ),
			'numberposts' => -1,
			'meta_key'    => PRINTS_PAGINAS_META_ORIGINAL,
			'orderby'     => 'title',
			'order'       => 'ASC',
		)
	);
	// Página criada pela versão antiga (Prints Cartões) também aparece, se existir
	$antiga = get_posts(
		array(
			'post_type'   => 'page',
			'numberposts' => -1,
			'meta_key'    => '_wp_page_template',
			'meta_value'  => 'prints-cartoes-pagina-inteira',
		)
	);
	foreach ( $antiga as $a ) {
		if ( ! in_array( $a->ID, wp_list_pluck( $paginas, 'ID' ), true ) ) {
			$paginas[] = $a;
		}
	}
	$nomes = array(
		'criada'     => 'criada',
		'atualizada' => 'atualizada (conteúdo substituído)',
		'existente'  => 'já existia, não foi alterada',
	);
	?>
	<div class="wrap">
		<h1>Print's Páginas</h1>

		<?php if ( $retorno ) : ?>
			<div class="notice notice-<?php echo esc_attr( $retorno[0] ); ?>">
				<p><strong><?php echo esc_html( $retorno[1] ); ?></strong></p>
				<?php if ( $retorno[2] ) : ?>
					<ul style="list-style:disc;padding-left:20px">
						<?php foreach ( $retorno[2] as $r ) : ?>
							<li>
								<?php if ( ! empty( $r['erro'] ) ) : ?>
									<?php echo esc_html( ( isset( $r['slug'] ) ? $r['slug'] . ': ' : '' ) . $r['erro'] ); ?>
								<?php else : ?>
									<a href="<?php echo esc_url( get_permalink( $r['id'] ) ); ?>" target="_blank"><?php echo esc_html( $r['titulo'] ); ?></a>
									(/<?php echo esc_html( $r['slug'] ); ?>): <?php echo esc_html( $nomes[ $r['acao'] ] ); ?>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<h2>Importar páginas</h2>
		<?php if ( prints_paginas_pode_importar() ) : ?>
			<form method="post" enctype="multipart/form-data">
				<?php wp_nonce_field( 'prints_paginas_importar' ); ?>
				<input type="hidden" name="prints_paginas_acao" value="importar">
				<p><input type="file" name="pacote" accept=".zip,application/zip" required></p>
				<p><label><input type="checkbox" name="substituir" value="1"> Substituir o conteúdo de páginas que já existem com o mesmo endereço</label></p>
				<p class="description">Envie o pacote <code>.zip</code> gerado para as páginas (pode conter várias). Limite de envio desta hospedagem: <?php echo esc_html( size_format( wp_max_upload_size() ) ); ?>.</p>
				<p><button class="button button-primary">Importar</button></p>
			</form>
		<?php else : ?>
			<p>Seu usuário não pode criar páginas e enviar arquivos. Peça a um administrador.</p>
		<?php endif; ?>

		<h2>Páginas</h2>
		<?php if ( ! $paginas ) : ?>
			<p>Nenhuma página importada ainda.</p>
		<?php else : ?>
			<table class="widefat striped" style="max-width:900px">
				<thead><tr><th>Página</th><th>Endereço</th><th></th></tr></thead>
				<tbody>
				<?php foreach ( $paginas as $pg ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $pg->post_title ); ?></strong></td>
						<td><a href="<?php echo esc_url( get_permalink( $pg ) ); ?>" target="_blank"><?php echo esc_html( wp_make_link_relative( get_permalink( $pg ) ) ); ?></a></td>
						<td style="text-align:right">
							<?php if ( current_user_can( 'edit_post', $pg->ID ) ) : ?>
								<a class="button button-primary" href="<?php echo esc_url( get_edit_post_link( $pg->ID ) ); ?>">Editar</a>
								<?php if ( get_post_meta( $pg->ID, PRINTS_PAGINAS_META_ORIGINAL, true ) ) : ?>
									<form method="post" style="display:inline" onsubmit="return confirm('Isto apaga as alterações feitas nesta página e volta ao conteúdo importado. Continuar?');">
										<?php wp_nonce_field( 'prints_paginas_restaurar_' . $pg->ID ); ?>
										<input type="hidden" name="prints_paginas_acao" value="restaurar">
										<input type="hidden" name="pagina" value="<?php echo esc_attr( $pg->ID ); ?>">
										<button class="button">Restaurar original</button>
									</form>
								<?php endif; ?>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<h2>Como editar uma página</h2>
		<ul style="list-style:disc;padding-left:20px">
			<li><strong>O editor mostra a página como no site</strong> (no computador, a imagem fica ao lado do produto).</li>
			<li><strong>Cada produto é um bloco <em>Produto</em></strong> com título, imagem, texto e lista de preços juntos: mova com as setas ou arraste, e use <em>Duplicar</em>, <em>Copiar</em>/<em>Colar</em> (menu ⋮ ou Ctrl+C/Ctrl+V) para criar outro.</li>
			<li><strong>Textos, títulos e imagens</strong>: clique e digite. Para trocar uma imagem, selecione-a e use <em>Substituir</em>.</li>
			<li><strong>Preços e opções</strong>: clique na lista; o título se edita no próprio campo e as opções aparecem em linhas (adicionar, subir, descer, remover) ou como texto para colar várias de uma vez.</li>
			<li><strong>Lista ↔ opções de marcar</strong>: botão do tipo de bloco na barra (<em>Transformar em</em>).</li>
			<li><strong>Ocultar título, imagem à direita, obrigatório</strong>: botões na barra do bloco ou na barra lateral.</li>
			<li><strong>Novo produto pronto</strong>: botão <em>+</em> › <em>Padrões</em> › <em>Gráfica Print's</em>.</li>
			<li><strong>Número do WhatsApp, texto do botão e cores</strong>: selecione o <em>Formulário de pedido</em> (está com cadeado para não ser apagado sem querer) e veja a barra lateral.</li>
			<li>Cor de fundo e espaçamento de um produto ou seção: barra lateral › <em>Estilos</em>. Versões anteriores: <em>Revisões</em>.</li>
		</ul>
	</div>
	<?php
}
