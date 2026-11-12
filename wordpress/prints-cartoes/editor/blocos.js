/* Editor dos blocos Prints Cartões (sem etapa de build: usa wp.element.createElement). */
(function (wp) {
  var el = wp.element.createElement;
  var be = wp.blockEditor;
  var c = wp.components;
  var useBlockProps = be.useBlockProps;
  var InnerBlocks = be.InnerBlocks;
  var InspectorControls = be.InspectorControls;

  var ZAP = el('svg', { viewBox: '64 64 896 896', width: 18, height: 18, fill: 'currentColor', 'aria-hidden': true },
    el('path', { d: 'M713.5 599.9c-10.9-5.6-65.2-32.2-75.3-35.8-10.1-3.8-17.5-5.6-24.8 5.6-7.4 11.1-28.4 35.8-35 43.3-6.4 7.4-12.9 8.3-23.8 2.8-64.8-32.4-107.3-57.8-150-131.1-11.3-19.5 11.3-18.1 32.4-60.2 3.6-7.4 1.8-13.7-1-19.3-2.8-5.6-24.8-59.8-34-81.9-8.9-21.5-18.1-18.5-24.8-18.9-6.4-.4-13.7-.4-21.1-.4-7.4 0-19.3 2.8-29.4 13.7-10.1 11.1-38.6 37.8-38.6 92s39.5 106.7 44.9 114.1c5.6 7.4 77.7 118.6 188.4 166.5 70 30.2 97.4 32.8 132.4 27.6 21.3-3.2 65.2-26.6 74.3-52.5 9.1-25.8 9.1-47.9 6.4-52.5-2.7-4.9-10.1-7.7-21-13z' }),
    el('path', { d: 'M925.2 338.4c-22.6-53.7-55-101.9-96.3-143.3a444.35 444.35 0 00-143.3-96.3C630.6 75.7 572.2 64 512 64h-2c-60.6.3-119.3 12.3-174.5 35.9a445.35 445.35 0 00-142 96.5c-40.9 41.3-73 89.3-95.2 142.8-23 55.4-34.6 114.3-34.3 174.9A449.4 449.4 0 00112 714v152a46 46 0 0046 46h152.1A449.4 449.4 0 00510 960h2.1c59.9 0 118-11.6 172.7-34.3a444.48 444.48 0 00142.8-95.2c41.3-40.9 73.8-88.7 96.5-142 23.6-55.2 35.6-113.9 35.9-174.5.3-60.9-11.5-120-34.8-175.6zm-151.1 438C704 845.8 611 884 512 884h-1.7c-60.3-.3-120.2-15.3-173.1-43.5l-8.4-4.5H188V695.2l-4.5-8.4C155.3 633.9 140.3 574 140 513.7c-.4-99.7 37.7-193.3 107.6-263.8 69.8-70.5 163.1-109.5 262.8-109.9h1.7c50 0 98.5 9.7 144.2 28.9 44.6 18.7 84.6 45.6 119 80 34.3 34.3 61.3 74.4 80 119 19.4 46.2 29.1 95.2 28.9 145.8-.6 99.6-39.7 192.9-110.1 262.7z' }));

  function salvarInterno() { return el(InnerBlocks.Content); }

  /* Formulário: envolve a página */
  wp.blocks.registerBlockType('prints/formulario', {
    edit: function (p) {
      var a = p.attributes;
      return el('div', useBlockProps({ className: 'pc-app pc-editor' }),
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
            })
          )
        ),
        el('div', { className: 'form-container' },
          el(InnerBlocks, { renderAppender: InnerBlocks.ButtonBlockAppender }),
          el('div', { className: 'form-group' },
            el('span', { className: 'submit-button pc-botao-editor', title: 'Número e texto do botão: barra lateral do bloco Formulário' },
              ZAP, el('span', null, a.textoBotao || 'Enviar no WhatsApp'))
          )
        )
      );
    },
    save: salvarInterno
  });

  /* Seção: título + imagem + parágrafos (blocos nativos) */
  wp.blocks.registerBlockType('prints/secao', {
    edit: function (p) {
      var a = p.attributes;
      return el('div', useBlockProps({ className: 'form-group pc-secao' }),
        el(InspectorControls, null,
          el(c.PanelBody, { title: 'Seção' },
            el(c.SelectControl, {
              label: 'Largura do conteúdo',
              value: a.estilo,
              options: [
                { label: 'Produto (96%)', value: 'produto' },
                { label: 'Cabeçalho / texto (85%)', value: 'cabecalho' }
              ],
              onChange: function (v) { p.setAttributes({ estilo: v }); }
            })
          )
        ),
        el('div', { className: a.estilo === 'cabecalho' ? 'bloco-header' : 'bloco' },
          el(InnerBlocks, {
            template: [
              ['core/heading', { level: 1, placeholder: 'Nome do produto' }],
              ['core/image', {}],
              ['core/paragraph', { placeholder: 'Descrição (papel, acabamento, prazo...)' }]
            ]
          })
        )
      );
    },
    save: salvarInterno
  });

  /* Campos: lista suspensa e opções de marcar */
  function linhas(v) { return v.split('\n'); }

  function editarCampo(tipo) {
    return function (p) {
      var a = p.attributes;
      var opcoes = a.opcoes || [];
      var campos = [
        el('div', { key: 'cab', className: 'pc-campo-cab' },
          el('span', { className: 'pc-campo-tag' }, tipo === 'lista' ? 'Lista de opções' : 'Opções de marcar'),
          el(c.ToggleControl, {
            label: 'Obrigatório',
            checked: !!a.obrigatorio,
            onChange: function (v) { p.setAttributes({ obrigatorio: v }); }
          })
        ),
        el(c.TextControl, {
          key: 'titulo',
          label: 'Título do campo',
          help: tipo === 'opcoes' ? 'Pode ficar vazio: na mensagem do WhatsApp usa o nome do produto acima.' : null,
          value: a.titulo,
          onChange: function (v) { p.setAttributes({ titulo: v }); }
        }),
        el(c.TextareaControl, {
          key: 'opcoes',
          label: 'Opções (uma por linha)',
          rows: Math.max(3, opcoes.length + 1),
          value: opcoes.join('\n'),
          onChange: function (v) { p.setAttributes({ opcoes: linhas(v) }); }
        })
      ];
      if (tipo === 'lista') {
        campos.push(el(c.TextControl, {
          key: 'ph',
          label: 'Texto antes de escolher',
          value: a.placeholder,
          onChange: function (v) { p.setAttributes({ placeholder: v }); }
        }));
      }
      return el('div', useBlockProps({ className: 'form-group pc-campo-editor' }), campos);
    };
  }

  wp.blocks.registerBlockType('prints/lista', { edit: editarCampo('lista'), save: function () { return null; } });
  wp.blocks.registerBlockType('prints/opcoes', { edit: editarCampo('opcoes'), save: function () { return null; } });
})(window.wp);
