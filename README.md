# Páginas de pedido | Gráfica Print's

Réplicas das páginas de pedido da Gráfica Print's (antes no WhatsForm, ex.: `app.prints.art.br/cartoes`):
site estático (HTML, CSS e JavaScript puros, sem build nem backend) **e** um plugin WordPress único em que cada
página é importada como blocos editáveis.

## Estrutura

```
index.html                lista das páginas (gerado por wordpress/build.py)
cartoes/                  uma pasta por página (endereço /cartoes)
  index.html              página
  dados.js                CONTEÚDO: produtos, preços, textos, imagens e número do WhatsApp
  img/                    imagens da página
assets/css/style.css      visual de todas as páginas
assets/js/app.js          monta a página a partir do dados.js (mesmo HTML dos blocos do WordPress)
assets/js/formulario.js   layout no computador e envio do pedido (usado também pelo plugin)
wordpress/prints-paginas/ plugin WordPress (instalado uma vez)
wordpress/build.py        gera o plugin, os pacotes das páginas e o index.html
```

Página nova: criar a pasta `<pagina>/` com `index.html` (copiar de `cartoes/`), `dados.js` e `img/`
e rodar `python wordpress/build.py`.

## Editar preços e produtos

Tudo fica no `dados.js` da página (ex.: `cartoes/dados.js`). Cada item da lista `campos` é um bloco da página:

| tipo       | o que é                                         |
|------------|-------------------------------------------------|
| `image`    | título + imagem + descrição do produto          |
| `header`   | título + texto                                  |
| `dropdown` | lista de opções (quantidade / preço)            |
| `radio`    | opções de marcar (`"obrigatorio": true` exige)   |

O número do WhatsApp que recebe os pedidos é o campo `whatsapp` (formato `55 + DDD + número`, só dígitos).
Opcionais: `corPrincipal`, `corFundo`, `ladoALado` (false desliga o layout de computador), `enviarImagem`,
e `"ocultarTitulo": true` em uma seção. `titulo` e `slug` são o nome e o endereço da página no WordPress.

## Como funciona o envio

Ao clicar em **Enviar no WhatsApp**, a página verifica os campos obrigatórios, monta a mensagem com
os itens escolhidos (opções "Nenhuma das opções" são ignoradas) e abre `wa.me/<número>` com o texto pronto.
Na mensagem: o título do campo com o ⚪, a opção com **só a quantidade e o preço em negrito** e o link da imagem
do produto (o WhatsApp mostra a prévia do primeiro link). Nada é salvo em servidor.

No computador (tela ≥ 992px) cada produto fica com a imagem à esquerda e título, descrição e opções à direita.
No celular a página fica empilhada como a original.

## WordPress (Hostinger com WordPress)

Um plugin só, **Print's Páginas**, instalado uma vez; cada página é um **pacote** importado.

| arquivo | para quê |
|---|---|
| `wordpress/dist/prints-paginas.zip` | plugin: **Plugins → Adicionar novo → Enviar plugin** → Ativar (uma vez; precisa de administrador) |
| `wordpress/dist/pagina-<pagina>.zip` | pacote de uma página: **Ferramentas → Print's Páginas → Importar** (Editor também pode) |
| `wordpress/dist/paginas-todas.zip` | todas as páginas num pacote só |
| `wordpress/prints-paginas/` | código-fonte do plugin (PHP + blocos do editor) |
| `wordpress/build.py` | gera os zips e o `index.html` depois de editar páginas, `assets/` ou o plugin: `python wordpress/build.py` |
| `wordpress/demo-blueprint.json` | demonstração no WordPress Playground (plugin + pacote) |

Um pacote é um zip com `paginas/<slug>/pagina.json` + `paginas/<slug>/img/` e pode trazer várias páginas.
Ao importar, cada página vira blocos editáveis e as imagens vão para a Biblioteca de Mídia (imagens idênticas,
como o logo, são reaproveitadas entre páginas). Endereço já existente só é substituído se a opção for marcada.
**Restaurar original** volta a página ao conteúdo importado.

Editar em **Páginas → (a página) → Editar**:

| bloco | o que edita |
|---|---|
| Título, Imagem, Parágrafo (nativos) | nomes de produto, fotos (Substituir), descrições, links |
| **Produto** | título + imagem + descrição + listas juntos (move/copia/cola/duplica inteiro); **Imagem à direita**, **Ocultar título**; cor de fundo e espaçamento |
| **Seção** | cabeçalho, faixa/logo ou texto; largura; **Ocultar título** |
| **Lista de opções (preços)** | título digitado no campo, opções em linhas (adicionar/mover/remover) ou como texto, obrigatório, texto antes de escolher; *Transformar em* opções |
| **Opções de marcar** | idem; *Transformar em* lista |
| **Formulário de pedido** | WhatsApp, texto do botão, cor principal, cor de fundo, imagem ao lado no computador, link da imagem na mensagem (com cadeado contra exclusão) |

O editor mostra a página como no site. Produtos prontos em **+ › Padrões › Gráfica Print's**. Ao atualizar para a
3.1, páginas antigas são convertidas uma vez para blocos Produto (a anterior fica em Revisões).

Os blocos ficam na categoria **Gráfica Print's** do inseridor (+). O modelo **"Print's Páginas (página inteira)"**
mostra a página sem cabeçalho/rodapé nem CSS do tema. A versão antiga (plugin "Prints Cartões") é desativada
automaticamente ao ativar o novo; as páginas dela continuam funcionando.

## Publicar na Hostinger (site estático, sem WordPress)

1. hPanel → **Sites** → o domínio → **Gerenciador de Arquivos** → `public_html` (ou a pasta do subdomínio).
2. Envie `index.html`, a pasta `assets/` e as pastas das páginas (ex.: `cartoes/`) mantendo a estrutura.
   Cada página fica em `/<pasta>` (ex.: `seusite.com.br/cartoes`).
3. Alternativa com Git: hPanel → **Avançado → Git** → informe a URL deste repositório e a branch `main`,
   diretório de destino `public_html`. Depois, cada push pode ser implantado pelo botão **Deploy**
   (ou automaticamente, configurando o webhook que o próprio hPanel mostra).

## Rodar localmente

```bash
python -m http.server 8000
```

Abra http://localhost:8000 (lista) ou http://localhost:8000/cartoes/.
