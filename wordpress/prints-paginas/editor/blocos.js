/* Editor dos blocos Print's Páginas (sem etapa de build: usa wp.element.createElement).
   O editor mostra a página como no site (mesmo CSS, imagem ao lado no computador) e tudo é editável no lugar:
   títulos e opções digitados direto no bloco, blocos movidos/copiados/duplicados como qualquer bloco do WordPress. */
(function (wp) {
  var el = wp.element.createElement;
  var useState = wp.element.useState;
  var be = wp.blockEditor;
  var c = wp.components;
  var blocks = wp.blocks;
  var useBlockProps = be.useBlockProps;
  var useInnerBlocksProps = be.useInnerBlocksProps || be.__experimentalUseInnerBlocksProps;
  var InnerBlocks = be.InnerBlocks;
  var InspectorControls = be.InspectorControls;
  var BlockControls = be.BlockControls;
  var PlainText = be.PlainText;

  var ZAP = el('svg', { viewBox: '64 64 896 896', width: 18, height: 18, fill: 'currentColor', 'aria-hidden': true },
    el('path', { d: 'M713.5 599.9c-10.9-5.6-65.2-32.2-75.3-35.8-10.1-3.8-17.5-5.6-24.8 5.6-7.4 11.1-28.4 35.8-35 43.3-6.4 7.4-12.9 8.3-23.8 2.8-64.8-32.4-107.3-57.8-150-131.1-11.3-19.5 11.3-18.1 32.4-60.2 3.6-7.4 1.8-13.7-1-19.3-2.8-5.6-24.8-59.8-34-81.9-8.9-21.5-18.1-18.5-24.8-18.9-6.4-.4-13.7-.4-21.1-.4-7.4 0-19.3 2.8-29.4 13.7-10.1 11.1-38.6 37.8-38.6 92s39.5 106.7 44.9 114.1c5.6 7.4 77.7 118.6 188.4 166.5 70 30.2 97.4 32.8 132.4 27.6 21.3-3.2 65.2-26.6 74.3-52.5 9.1-25.8 9.1-47.9 6.4-52.5-2.7-4.9-10.1-7.7-21-13z' }),
    el('path', { d: 'M925.2 338.4c-22.6-53.7-55-101.9-96.3-143.3a444.35 444.35 0 00-143.3-96.3C630.6 75.7 572.2 64 512 64h-2c-60.6.3-119.3 12.3-174.5 35.9a445.35 445.35 0 00-142 96.5c-40.9 41.3-73 89.3-95.2 142.8-23 55.4-34.6 114.3-34.3 174.9A449.4 449.4 0 00112 714v152a46 46 0 0046 46h152.1A449.4 449.4 0 00510 960h2.1c59.9 0 118-11.6 172.7-34.3a444.48 444.48 0 00142.8-95.2c41.3-40.9 73.8-88.7 96.5-142 23.6-55.2 35.6-113.9 35.9-174.5.3-60.9-11.5-120-34.8-175.6zm-151.1 438C704 845.8 611 884 512 884h-1.7c-60.3-.3-120.2-15.3-173.1-43.5l-8.4-4.5H188V695.2l-4.5-8.4C155.3 633.9 140.3 574 140 513.7c-.4-99.7 37.7-193.3 107.6-263.8 69.8-70.5 163.1-109.5 262.8-109.9h1.7c50 0 98.5 9.7 144.2 28.9 44.6 18.7 84.6 45.6 119 80 34.3 34.3 61.3 74.4 80 119 19.4 46.2 29.1 95.2 28.9 145.8-.6 99.6-39.7 192.9-110.1 262.7z' }));
  var SETA = el('svg', { viewBox: '64 64 896 896', fill: 'currentColor', 'aria-hidden': true },
    el('path', { d: 'M884 256h-75c-5.1 0-9.9 2.5-12.9 6.6L512 654.2 227.9 262.6c-3-4.1-7.8-6.6-12.9-6.6h-75c-6.5 0-10.3 7.4-6.5 12.7l352.6 486.1c12.8 17.6 39 17.6 51.7 0l352.6-486.1c3.9-5.3.1-12.7-6.4-12.7z' }));

  /* Ícones em SVG (dentro do editor em iframe a fonte dashicons não é carregada) */
  function icone(d) { return el('svg', { viewBox: '0 0 24 24', width: 20, height: 20, 'aria-hidden': true }, el('path', { d: d, fill: 'currentColor' })); }
  var ICONE = {
    subir: icone('M6.5 14.5 12 9l5.5 5.5-1.1 1.1L12 11.2l-4.4 4.4z'),
    descer: icone('M17.5 9.5 12 15 6.5 9.5l1.1-1.1 4.4 4.4 4.4-4.4z'),
    remover: icone('M12 10.9 16.6 6.3l1.1 1.1-4.6 4.6 4.6 4.6-1.1 1.1-4.6-4.6-4.6 4.6-1.1-1.1 4.6-4.6-4.6-4.6 1.1-1.1z'),
    mais: icone('M11.25 5h1.5v6.25H19v1.5h-6.25V19h-1.5v-6.25H5v-1.5h6.25z')
  };

  function salvarInterno() { return el(InnerBlocks.Content); }

  /* ---------------------------------------------------------------- Formulário */
  blocks.registerBlockType('prints/formulario', {
    edit: function (p) {
      var a = p.attributes;
      var estilo = {};
      if (a.corPrincipal) estilo['--pc-cor'] = a.corPrincipal;
      if (a.corFundo) estilo['--pc-fundo'] = a.corFundo;
      var blockProps = useBlockProps({ className: 'pc-app pc-editor', style: estilo });
      var innerProps = useInnerBlocksProps(
        { className: 'pc-form pc-form-editor', 'data-lado-a-lado': a.ladoALado !== false ? '1' : undefined },
        { renderAppender: InnerBlocks.ButtonBlockAppender }
      );
      return el('div', blockProps,
        el(InspectorControls, null,
          el(c.PanelBody, { title: 'Envio pelo WhatsApp', initialOpen: true },
            el(c.TextControl, {
              label: 'Número do WhatsApp',
              help: 'Só números: 55 + DDD + número. Ex.: 5519983190031',
              value: a.whatsapp,
              onChange: function (v) { p.setAttributes({ whatsapp: v.replace(/\D/g, '') }); }
            }),
            el(c.TextControl, {
              label: 'Texto do botão',
              value: a.textoBotao,
              onChange: function (v) { p.setAttributes({ textoBotao: v }); }
            }),
            el(c.ToggleControl, {
              label: 'Enviar o link da imagem do produto',
              help: 'Na mensagem, cada produto escolhido leva o link da imagem do produto (o WhatsApp mostra a prévia).',
              checked: a.enviarImagem !== false,
              onChange: function (v) { p.setAttributes({ enviarImagem: v }); }
            })
          ),
          el(c.PanelBody, { title: 'Aparência', initialOpen: true },
            el(c.BaseControl, { label: 'Cor principal (botão e “Click aqui para escolher”)', id: 'pc-cor' },
              el(c.ColorPalette, {
                colors: [{ name: 'Verde WhatsApp', color: '#2ab200' }],
                value: a.corPrincipal,
                onChange: function (v) { p.setAttributes({ corPrincipal: v || '#2ab200' }); }
              })
            ),
            el(c.BaseControl, { label: 'Cor de fundo da página', id: 'pc-fundo' },
              el(c.ColorPalette, {
                colors: [{ name: 'Branco', color: '#ffffff' }, { name: 'Cinza claro', color: '#f4f5f6' }],
                value: a.corFundo,
                onChange: function (v) { p.setAttributes({ corFundo: v || '#ffffff' }); }
              })
            ),
            el(c.ToggleControl, {
              label: 'No computador: imagem ao lado do produto',
              help: 'Em telas de notebook/PC a imagem fica ao lado do título, do texto e das opções. O celular não muda.',
              checked: a.ladoALado !== false,
              onChange: function (v) { p.setAttributes({ ladoALado: v }); }
            })
          )
        ),
        el('div', { className: 'form-container' },
          el('div', innerProps),
          el('div', { className: 'form-group' },
            el('span', { className: 'submit-button pc-botao-editor', title: 'Número e texto do botão: barra lateral do bloco Formulário' },
              ZAP, el('span', null, a.textoBotao || 'Enviar no WhatsApp'))
          )
        )
      );
    },
    save: salvarInterno
  });

  /* ---------------------------------------------------------------- Produto e Seção */
  function botaoBarra(icone, rotulo, ativo, aoClicar) {
    return el(c.ToolbarButton, { icon: icone, label: rotulo, isPressed: !!ativo, onClick: aoClicar });
  }

  blocks.registerBlockType('prints/produto', {
    edit: function (p) {
      var a = p.attributes;
      var classes = 'form-group pc-secao pc-produto' + (a.ocultarTitulo ? ' pc-sem-titulo-editor' : '') + (a.imagemDireita ? ' pc-imagem-direita' : '');
      var blockProps = useBlockProps({ className: classes });
      var innerProps = useInnerBlocksProps({ className: 'bloco' }, {
        template: [
          ['core/heading', { level: 1, placeholder: 'Nome do produto' }],
          ['core/image', { sizeSlug: 'full', linkDestination: 'none' }],
          ['core/paragraph', { placeholder: 'Descrição (papel, acabamento, prazo...)' }],
          ['prints/lista', { titulo: '', opcoes: ['Nenhuma das opções'] }]
        ]
      });
      return el('div', blockProps,
        el(BlockControls, { group: 'block' },
          botaoBarra('image-flip-horizontal', 'Imagem à direita (no computador)', a.imagemDireita, function () { p.setAttributes({ imagemDireita: !a.imagemDireita }); }),
          botaoBarra('hidden', a.ocultarTitulo ? 'Mostrar título' : 'Ocultar título', a.ocultarTitulo, function () { p.setAttributes({ ocultarTitulo: !a.ocultarTitulo }); })
        ),
        el(InspectorControls, null,
          el(c.PanelBody, { title: 'Produto' },
            el(c.ToggleControl, {
              label: 'Imagem à direita (no computador)',
              checked: !!a.imagemDireita,
              onChange: function (v) { p.setAttributes({ imagemDireita: v }); }
            }),
            el(c.ToggleControl, {
              label: 'Ocultar título',
              help: 'O título continua no editor (apagado) e na mensagem do WhatsApp, mas não aparece na página.',
              checked: !!a.ocultarTitulo,
              onChange: function (v) { p.setAttributes({ ocultarTitulo: v }); }
            }),
            el('p', { className: 'components-base-control__help' },
              'Dentro do produto vale qualquer bloco: arraste, copie e cole títulos, imagens, textos e listas. A primeira imagem fica ao lado no computador.')
          )
        ),
        el('div', innerProps)
      );
    },
    save: salvarInterno,
    transforms: {
      to: [{
        type: 'block', blocks: ['prints/secao'],
        transform: function (a, filhos) { return blocks.createBlock('prints/secao', { ocultarTitulo: a.ocultarTitulo }, filhos); }
      }]
    }
  });

  blocks.registerBlockType('prints/secao', {
    edit: function (p) {
      var a = p.attributes;
      var blockProps = useBlockProps({ className: 'form-group pc-secao' + (a.ocultarTitulo ? ' pc-sem-titulo-editor' : '') });
      var innerProps = useInnerBlocksProps({ className: a.estilo === 'cabecalho' ? 'bloco-header' : 'bloco' }, {
        template: [
          ['core/heading', { level: 1, placeholder: 'Título' }],
          ['core/paragraph', { placeholder: 'Texto' }]
        ]
      });
      return el('div', blockProps,
        el(BlockControls, { group: 'block' },
          botaoBarra('hidden', a.ocultarTitulo ? 'Mostrar título' : 'Ocultar título', a.ocultarTitulo, function () { p.setAttributes({ ocultarTitulo: !a.ocultarTitulo }); })
        ),
        el(InspectorControls, null,
          el(c.PanelBody, { title: 'Seção' },
            el(c.SelectControl, {
              label: 'Largura do conteúdo',
              value: a.estilo,
              options: [
                { label: 'Normal (96%)', value: 'produto' },
                { label: 'Cabeçalho / texto (85%)', value: 'cabecalho' }
              ],
              onChange: function (v) { p.setAttributes({ estilo: v }); }
            }),
            el(c.ToggleControl, {
              label: 'Ocultar título',
              help: 'O título continua aqui no editor (apagado), mas não aparece na página.',
              checked: !!a.ocultarTitulo,
              onChange: function (v) { p.setAttributes({ ocultarTitulo: v }); }
            })
          )
        ),
        el('div', innerProps)
      );
    },
    save: salvarInterno,
    transforms: {
      to: [{
        type: 'block', blocks: ['prints/produto'],
        transform: function (a, filhos) { return blocks.createBlock('prints/produto', { ocultarTitulo: a.ocultarTitulo }, filhos); }
      }]
    }
  });

  /* ---------------------------------------------------------------- Campos: lista e opções */
  function EditorOpcoes(props) {
    var opcoes = props.opcoes;
    var mudar = props.onChange;
    var estado = useState(false);
    var comoTexto = estado[0];
    var setComoTexto = estado[1];

    function trocar(i, v) { var n = opcoes.slice(); n[i] = v; mudar(n); }
    function mover(i, d) {
      var j = i + d;
      if (j < 0 || j >= opcoes.length) return;
      var n = opcoes.slice(); var t = n[i]; n[i] = n[j]; n[j] = t; mudar(n);
    }
    function remover(i) { var n = opcoes.slice(); n.splice(i, 1); mudar(n); }

    var corpo;
    if (comoTexto) {
      corpo = el(c.TextareaControl, {
        label: 'Opções (uma por linha)',
        rows: Math.max(4, opcoes.length + 1),
        value: opcoes.join('\n'),
        onChange: function (v) { mudar(v.split('\n')); }
      });
    } else {
      corpo = el('div', { className: 'pc-opcoes-linhas' },
        opcoes.map(function (o, i) {
          return el('div', { key: i, className: 'pc-opcao-linha' },
            el('span', { className: 'pc-opcao-num' }, i + 1),
            el('input', {
              type: 'text', className: 'pc-opcao-texto', value: o, 'aria-label': 'Opção ' + (i + 1),
              onChange: function (e) { trocar(i, e.target.value); }
            }),
            el(c.Button, { icon: ICONE.subir, label: 'Subir', size: 'small', disabled: i === 0, onClick: function () { mover(i, -1); } }),
            el(c.Button, { icon: ICONE.descer, label: 'Descer', size: 'small', disabled: i === opcoes.length - 1, onClick: function () { mover(i, 1); } }),
            el(c.Button, { icon: ICONE.remover, label: 'Remover opção', size: 'small', isDestructive: true, onClick: function () { remover(i); } })
          );
        }),
        el(c.Button, { variant: 'secondary', icon: ICONE.mais, className: 'pc-opcao-add', onClick: function () { mudar(opcoes.concat([''])); } }, 'Adicionar opção')
      );
    }
    return el('div', { className: 'pc-opcoes-editor' },
      corpo,
      el(c.ToggleControl, {
        label: 'Editar como texto (colar várias de uma vez)',
        checked: comoTexto,
        onChange: setComoTexto
      })
    );
  }

  function editarCampo(tipo) {
    return function (p) {
      var a = p.attributes;
      var opcoes = a.opcoes || [];
      var visiveis = opcoes.filter(function (o) { return o && o.trim(); });
      var blockProps = useBlockProps({ className: 'form-group pc-campo pc-campo-editor' + (p.isSelected ? ' pc-campo-ativo' : '') });

      var controle;
      if (tipo === 'lista') {
        controle = el('div', { className: 'select-wrap' },
          el('div', { className: 'pc-select-falso vazio', title: visiveis.length + ' opções' }, a.placeholder || 'Selecione'),
          SETA);
      } else {
        controle = el('div', { className: 'radio-group' },
          (visiveis.length ? visiveis : ['(sem opções)']).map(function (o, i) {
            return el('span', { key: i, className: 'radio' }, el('span', { className: 'inner' }), el('span', { className: 'txt' }, o));
          }));
      }

      return el('div', blockProps,
        el(BlockControls, { group: 'block' },
          botaoBarra('star-filled', a.obrigatorio ? 'Obrigatório (clique para tornar opcional)' : 'Tornar obrigatório', a.obrigatorio,
            function () { p.setAttributes({ obrigatorio: !a.obrigatorio }); })
        ),
        el(InspectorControls, null,
          el(c.PanelBody, { title: tipo === 'lista' ? 'Lista de opções' : 'Opções de marcar' },
            el(c.ToggleControl, {
              label: 'Obrigatório',
              help: 'O pedido só é enviado se o cliente escolher.',
              checked: !!a.obrigatorio,
              onChange: function (v) { p.setAttributes({ obrigatorio: v }); }
            }),
            tipo === 'lista' ? el(c.TextControl, {
              label: 'Texto antes de escolher',
              value: a.placeholder,
              onChange: function (v) { p.setAttributes({ placeholder: v }); }
            }) : null,
            el('p', { className: 'components-base-control__help' },
              'A opção "Nenhuma das opções" não entra na mensagem. Na mensagem, a quantidade e o preço saem em negrito.')
          )
        ),
        el('div', { className: 'form-item' },
          (!a.titulo && !p.isSelected) ? null : el('label', { className: 'form-item-label' + (a.obrigatorio ? ' required' : '') },
            el(PlainText, {
              className: 'pc-titulo-campo',
              value: a.titulo,
              placeholder: tipo === 'opcoes' ? 'Título (opcional: sem título usa o nome do produto)' : 'Título do campo (ex.: ⚪ Cartão | 9x5cm)',
              onChange: function (v) { p.setAttributes({ titulo: v.replace(/\n/g, ' ') }); }
            })
          ),
          el('div', { className: 'form-item-control' }, el('div', { className: 'form-item-input' }, controle))
        ),
        p.isSelected ? el(EditorOpcoes, { opcoes: opcoes, onChange: function (n) { p.setAttributes({ opcoes: n }); } }) : null
      );
    };
  }

  function converterPara(destino) {
    return {
      type: 'block', blocks: [destino],
      transform: function (a) {
        return blocks.createBlock(destino, { titulo: a.titulo, obrigatorio: a.obrigatorio, opcoes: a.opcoes });
      }
    };
  }

  blocks.registerBlockType('prints/lista', {
    edit: editarCampo('lista'),
    save: function () { return null; },
    transforms: { to: [converterPara('prints/opcoes')] }
  });
  blocks.registerBlockType('prints/opcoes', {
    edit: editarCampo('opcoes'),
    save: function () { return null; },
    transforms: { to: [converterPara('prints/lista')] }
  });
})(window.wp);
