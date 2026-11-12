=== Prints Cartões ===
Requires at least: 5.2
Requires PHP: 7.0
Stable tag: 1.0.0
License: GPLv2 or later

Página de Cartões de Visitas da Gráfica Print's com pedido pelo WhatsApp.

== Instalação ==

1. Painel do WordPress > Plugins > Adicionar novo > Enviar plugin > escolha prints-cartoes.zip > Instalar > Ativar.
2. Ferramentas > Importar > WordPress > envie importar-pagina-cartoes.xml.
   Isso cria a página "Cartões de Visitas" no endereço /cartoes, já usando o modelo de página inteira.
   (Sem importar: crie uma página, escolha o modelo "Prints Cartões (página inteira)" e coloque o shortcode [prints_cartoes].)

== Uso ==

* Modelo "Prints Cartões (página inteira)": página idêntica à original, sem cabeçalho/rodapé do tema.
* Shortcode [prints_cartoes]: insere a página dentro do layout do tema, em qualquer página.

== Editar preços e produtos ==

Edite assets/js/dados.js (Plugins > Editor de arquivos de plugins > Prints Cartões, ou pelo Gerenciador de Arquivos da Hostinger em
wp-content/plugins/prints-cartoes/assets/js/dados.js). O campo "whatsapp" é o número que recebe os pedidos.
