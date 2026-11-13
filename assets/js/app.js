/* Site estático: monta a página a partir de dados.js dentro de #pc-raiz, com o mesmo HTML dos blocos do WordPress.
   O envio para o WhatsApp e o layout no computador ficam em formulario.js (carregado depois deste arquivo).
   window.FORM_BASE (opcional) = URL base das imagens. */
(function () {
  var cfg = window.FORM_CONFIG;
  var base = window.FORM_BASE || '';
  var alvo = document.getElementById('pc-raiz');
  if (!cfg || !alvo) return;

  var SETA = '<svg viewBox="64 64 896 896" fill="currentColor" aria-hidden="true"><path d="M884 256h-75c-5.1 0-9.9 2.5-12.9 6.6L512 654.2 227.9 262.6c-3-4.1-7.8-6.6-12.9-6.6h-75c-6.5 0-10.3 7.4-6.5 12.7l352.6 486.1c12.8 17.6 39 17.6 51.7 0l352.6-486.1c3.9-5.3.1-12.7-6.4-12.7z"/></svg>';
  var ZAP = '<svg viewBox="64 64 896 896" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M713.5 599.9c-10.9-5.6-65.2-32.2-75.3-35.8-10.1-3.8-17.5-5.6-24.8 5.6-7.4 11.1-28.4 35.8-35 43.3-6.4 7.4-12.9 8.3-23.8 2.8-64.8-32.4-107.3-57.8-150-131.1-11.3-19.5 11.3-18.1 32.4-60.2 3.6-7.4 1.8-13.7-1-19.3-2.8-5.6-24.8-59.8-34-81.9-8.9-21.5-18.1-18.5-24.8-18.9-6.4-.4-13.7-.4-21.1-.4-7.4 0-19.3 2.8-29.4 13.7-10.1 11.1-38.6 37.8-38.6 92s39.5 106.7 44.9 114.1c5.6 7.4 77.7 118.6 188.4 166.5 70 30.2 97.4 32.8 132.4 27.6 21.3-3.2 65.2-26.6 74.3-52.5 9.1-25.8 9.1-47.9 6.4-52.5-2.7-4.9-10.1-7.7-21-13z"/><path d="M925.2 338.4c-22.6-53.7-55-101.9-96.3-143.3a444.35 444.35 0 00-143.3-96.3C630.6 75.7 572.2 64 512 64h-2c-60.6.3-119.3 12.3-174.5 35.9a445.35 445.35 0 00-142 96.5c-40.9 41.3-73 89.3-95.2 142.8-23 55.4-34.6 114.3-34.3 174.9A449.4 449.4 0 00112 714v152a46 46 0 0046 46h152.1A449.4 449.4 0 00510 960h2.1c59.9 0 118-11.6 172.7-34.3a444.48 444.48 0 00142.8-95.2c41.3-40.9 73.8-88.7 96.5-142 23.6-55.2 35.6-113.9 35.9-174.5.3-60.9-11.5-120-34.8-175.6zm-151.1 438C704 845.8 611 884 512 884h-1.7c-60.3-.3-120.2-15.3-173.1-43.5l-8.4-4.5H188V695.2l-4.5-8.4C155.3 633.9 140.3 574 140 513.7c-.4-99.7 37.7-193.3 107.6-263.8 69.8-70.5 163.1-109.5 262.8-109.9h1.7c50 0 98.5 9.7 144.2 28.9 44.6 18.7 84.6 45.6 119 80 34.3 34.3 61.3 74.4 80 119 19.4 46.2 29.1 95.2 28.9 145.8-.6 99.6-39.7 192.9-110.1 262.7z"/></svg>';

  function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML.replace(/"/g, '&quot;'); }

  function ehProduto(def) { return def && def.tipo !== 'header' && def.titulo && def.imagem; }
  function ehCampo(def) { return def && (def.tipo === 'dropdown' || def.tipo === 'radio'); }

  /* Produto (bloco prints/produto): título, imagem, texto e campos juntos num só bloco */
  function secao(def, campos) {
    var produto = !!campos;
    var classes = 'form-group pc-secao' + (produto ? ' pc-produto' : '') + (def.ocultarTitulo ? ' pc-sem-titulo' : '');
    var html = '<div class="' + classes + '"><div class="' + (def.tipo === 'header' ? 'bloco-header' : 'bloco') + '">';
    if (def.titulo || def.tipo === 'header') html += '<h1 class="wp-block-heading">' + esc(def.titulo || '') + '</h1>';
    if (def.imagem) {
      var src = /^(https?:)?\/\//.test(def.imagem) ? def.imagem : base + def.imagem;
      html += '<figure class="wp-block-image size-full"><img src="' + esc(src) + '" alt="' + esc(def.titulo || '') + '" loading="lazy"></figure>';
    }
    if (def.descricao) html += def.descricao; // HTML com <p>, como os parágrafos do WordPress
    if (produto) html += campos;
    return html + '</div></div>';
  }

  function campo(def, i) {
    var lista = def.tipo === 'dropdown';
    var id = 'pc-campo-' + i;
    var html = '<div class="form-group pc-campo" data-tipo="' + (lista ? 'lista' : 'opcoes') + '" data-titulo="' + esc(def.titulo || '') + '"' + (def.obrigatorio ? ' data-obrigatorio="1"' : '') + '><div class="form-item">';
    if (def.titulo) html += '<label class="form-item-label' + (def.obrigatorio ? ' required' : '') + '"' + (lista ? ' for="' + id + '"' : '') + '>' + esc(def.titulo) + '</label>';
    html += '<div class="form-item-control"><div class="form-item-input">';
    if (lista) {
      html += '<div class="select-wrap"><select id="' + id + '" name="' + id + '" class="vazio"><option value="" disabled selected hidden>' + esc(def.placeholder || 'Selecione') + '</option>';
      (def.opcoes || []).forEach(function (o) { html += '<option value="' + esc(o) + '">' + esc(o) + '</option>'; });
      html += '</select>' + SETA + '</div>';
    } else {
      html += '<div class="radio-group" role="radiogroup">';
      (def.opcoes || []).forEach(function (o) {
        html += '<label class="radio"><input type="radio" name="' + id + '" value="' + esc(o) + '"><span class="inner"></span><span class="txt">' + esc(o) + '</span></label>';
      });
      html += '</div>';
    }
    return html + '</div><div class="erro">Por favor, preencha este campo</div></div></div></div>';
  }

  var estilo = (cfg.corPrincipal ? '--pc-cor:' + cfg.corPrincipal + ';' : '') + (cfg.corFundo ? '--pc-fundo:' + cfg.corFundo + ';' : '');
  var html = '<div class="pc-app"' + (estilo ? ' style="' + esc(estilo) + '"' : '') + '><div class="form-container">'
    + '<form class="pc-form" novalidate data-whatsapp="' + esc(String(cfg.whatsapp || '').replace(/\D/g, '')) + '"'
    + (cfg.ladoALado === false ? '' : ' data-lado-a-lado="1"')
    + (cfg.enviarImagem === false ? '' : ' data-enviar-imagem="1"') + '>';
  var defs = cfg.campos;
  for (var i = 0; i < defs.length; i++) {
    var def = defs[i];
    if (ehCampo(def)) { html += campo(def, i); continue; }
    if (!ehProduto(def)) { html += secao(def); continue; }
    var campos = '';
    while (ehCampo(defs[i + 1])) { i++; campos += campo(defs[i], i); }
    html += secao(def, campos);
  }
  html += '<div class="form-group"><button type="submit" class="submit-button">' + ZAP + '<span>' + esc(cfg.textoBotao || 'Enviar no WhatsApp') + '</span></button></div></form></div></div>';
  alvo.innerHTML = html;
})();
