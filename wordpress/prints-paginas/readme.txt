=== Print's Páginas ===
Requires at least: 6.3
Requires PHP: 7.0
Stable tag: 3.1.0
License: GPL-2.0-or-later

Páginas de pedido da Gráfica Print's (envio pelo WhatsApp), montadas em blocos editáveis.

== Instalação (uma vez) ==
1. Plugins > Adicionar novo > Enviar plugin > escolha prints-paginas.zip > Instalar agora > Ativar.
   (Se a versão antiga "Prints Cartões" estiver ativa, ela é desativada automaticamente; as páginas continuam.)

== Cada página nova ==
1. Ferramentas > Print's Páginas > escolha o pacote (ex.: pagina-cartoes.zip) > Importar.
   Um pacote pode trazer várias páginas. As imagens vão para a Biblioteca de Mídia.
2. A página é criada no endereço do pacote (ex.: /cartoes), com o modelo "Print's Páginas (página inteira)".
   Editores também podem importar (precisam poder publicar páginas e enviar arquivos).

== Editar ==
Páginas > (a página) > Editar. O editor mostra a página como no site. Tudo é bloco:
* Formulário de pedido (barra lateral; protegido por cadeado): número do WhatsApp, texto do botão,
  cor principal, cor de fundo, imagem ao lado do produto no computador, link da imagem na mensagem.
* Produto: título, imagem, texto e campos juntos (move, copia, cola e duplica inteiro);
  "Imagem à direita" e "Ocultar título" na barra do bloco; cor de fundo e espaçamento em Estilos.
* Seção: cabeçalho, faixa/logo ou texto; "Ocultar título".
* Lista de opções / Opções de marcar: título digitado no campo, opções em linhas (adicionar, mover,
  remover) ou como texto; obrigatório; "Transformar em" troca lista <-> opções.
* Padrões prontos: + > Padrões > Gráfica Print's (produto com lista, produto com opções, cabeçalho,
  faixa, confirmação obrigatória).
Ao atualizar o plugin, páginas de versões anteriores são convertidas uma vez para blocos Produto
(a versão anterior fica em Revisões).
Para voltar ao conteúdo importado: Ferramentas > Print's Páginas > Restaurar original.

== Mensagem do WhatsApp ==
Título do campo (com os emojis), a opção escolhida com só a quantidade e o preço em negrito e o link da imagem
do produto (o WhatsApp mostra a prévia do primeiro link).
