# Cartões de Visitas | Gráfica Print's

Réplica estática da página `app.prints.art.br/cartoes` (antes hospedada no WhatsForm).
HTML, CSS e JavaScript puros, sem build, sem dependências e sem backend. Roda em qualquer hospedagem estática (Hostinger, GitHub Pages, Netlify).

## Estrutura

```
index.html            página
assets/css/style.css  visual (medidas copiadas da página original)
assets/js/dados.js    CONTEÚDO: produtos, preços, textos, imagens e número do WhatsApp
assets/js/app.js      monta o formulário e envia o pedido para o WhatsApp
assets/img/           imagens dos produtos e logo
```

## Editar preços e produtos

Tudo fica em `assets/js/dados.js`. Cada item da lista `campos` é um bloco da página:

| tipo       | o que é                                         |
|------------|-------------------------------------------------|
| `image`    | título + imagem + descrição do produto          |
| `header`   | título + texto                                  |
| `dropdown` | lista de opções (quantidade / preço)            |
| `radio`    | opções de marcar (`"obrigatorio": true` exige)   |

O número do WhatsApp que recebe os pedidos é o campo `whatsapp` (formato `55 + DDD + número`, só dígitos).

## Como funciona o envio

Ao clicar em **Enviar no WhatsApp**, a página verifica os campos obrigatórios, monta a mensagem com
os itens escolhidos (opções "Nenhuma das opções" são ignoradas) e abre `wa.me/<número>` com o texto pronto.
Nada é salvo em servidor.

## WordPress (Hostinger com WordPress)

A pasta `wordpress/` tem a mesma página como plugin:

| arquivo | para quê |
|---|---|
| `wordpress/dist/prints-cartoes.zip` | plugin pronto: **Plugins → Adicionar novo → Enviar plugin** → Ativar |
| `wordpress/importar-pagina-cartoes.xml` | **Ferramentas → Importar → WordPress**: cria a página "Cartões de Visitas" em `/cartoes` |
| `wordpress/prints-cartoes/` | código-fonte do plugin (PHP) |
| `wordpress/build.py` | gera o zip de novo depois de editar `assets/` ou o plugin: `python wordpress/build.py` |

Duas formas de exibir:

- **Modelo "Prints Cartões (página inteira)"** (padrão da página importada): idêntica à original, sem cabeçalho/rodapé e sem o CSS do tema.
- **Shortcode `[prints_cartoes]`**: em qualquer página ou post, dentro do layout do tema.

No WordPress, os preços ficam em `wp-content/plugins/prints-cartoes/assets/js/dados.js`
(editável em **Plugins → Editor de arquivos de plugins**). Se a página `/cartoes` já existir, o importador
pula a criação; nesse caso crie a página manualmente com o modelo e o shortcode acima.

## Publicar na Hostinger (site estático, sem WordPress)

1. hPanel → **Sites** → o domínio → **Gerenciador de Arquivos** → `public_html` (ou a pasta do subdomínio).
2. Envie `index.html` e a pasta `assets/` mantendo a estrutura.
   Para servir em `/cartoes`, crie a pasta `public_html/cartoes/` e envie lá dentro.
3. Alternativa com Git: hPanel → **Avançado → Git** → informe a URL deste repositório e a branch `main`,
   diretório de destino `public_html` (ou `public_html/cartoes`). Depois, cada push pode ser implantado pelo botão **Deploy**
   (ou automaticamente, configurando o webhook que o próprio hPanel mostra).

## Rodar localmente

```bash
python -m http.server 8000
```

Abra http://localhost:8000.
