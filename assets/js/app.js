/* Monta a página a partir de dados.js dentro de #prints-cartoes e envia o pedido para o WhatsApp.
   window.FORM_BASE (opcional) = URL base das imagens; usado pelo plugin do WordPress. */
(function () {
  var cfg = window.FORM_CONFIG;
  var base = window.FORM_BASE || '';
  var alvo = document.getElementById('prints-cartoes');
  if (!cfg || !alvo) return;

  var campos = []; // { def, el, titulo, ler() }
  var ultimoTitulo = '';

  var SETA = '<svg viewBox="64 64 896 896" fill="currentColor" aria-hidden="true"><path d="M884 256h-75c-5.1 0-9.9 2.5-12.9 6.6L512 654.2 227.9 262.6c-3-4.1-7.8-6.6-12.9-6.6h-75c-6.5 0-10.3 7.4-6.5 12.7l352.6 486.1c12.8 17.6 39 17.6 51.7 0l352.6-486.1c3.9-5.3.1-12.7-6.4-12.7z"/></svg>';
  var ZAP = '<svg viewBox="64 64 896 896" fill="currentColor" aria-hidden="true"><path d="M713.5 599.9c-10.9-5.6-65.2-32.2-75.3-35.8-10.1-3.8-17.5-5.6-24.8 5.6-7.4 11.1-28.4 35.8-35 43.3-6.4 7.4-12.9 8.3-23.8 2.8-64.8-32.4-107.3-57.8-150-131.1-11.3-19.5 11.3-18.1 32.4-60.2 3.6-7.4 1.8-13.7-1-19.3-2.8-5.6-24.8-59.8-34-81.9-8.9-21.5-18.1-18.5-24.8-18.9-6.4-.4-13.7-.4-21.1-.4-7.4 0-19.3 2.8-29.4 13.7-10.1 11.1-38.6 37.8-38.6 92s39.5 106.7 44.9 114.1c5.6 7.4 77.7 118.6 188.4 166.5 70 30.2 97.4 32.8 132.4 27.6 21.3-3.2 65.2-26.6 74.3-52.5 9.1-25.8 9.1-47.9 6.4-52.5-2.7-4.9-10.1-7.7-21-13z"/><path d="M925.2 338.4c-22.6-53.7-55-101.9-96.3-143.3a444.35 444.35 0 00-143.3-96.3C630.6 75.7 572.2 64 512 64h-2c-60.6.3-119.3 12.3-174.5 35.9a445.35 445.35 0 00-142 96.5c-40.9 41.3-73 89.3-95.2 142.8-23 55.4-34.6 114.3-34.3 174.9A449.4 449.4 0 00112 714v152a46 46 0 0046 46h152.1A449.4 449.4 0 00510 960h2.1c59.9 0 118-11.6 172.7-34.3a444.48 444.48 0 00142.8-95.2c41.3-40.9 73.8-88.7 96.5-142 23.6-55.2 35.6-113.9 35.9-174.5.3-60.9-11.5-120-34.8-175.6zm-151.1 438C704 845.8 611 884 512 884h-1.7c-60.3-.3-120.2-15.3-173.1-43.5l-8.4-4.5H188V695.2l-4.5-8.4C155.3 633.9 140.3 574 140 513.7c-.4-99.7 37.7-193.3 107.6-263.8 69.8-70.5 163.1-109.5 262.8-109.9h1.7c50 0 98.5 9.7 144.2 28.9 44.6 18.7 84.6 45.6 119 80 34.3 34.3 61.3 74.4 80 119 19.4 46.2 29.1 95.2 28.9 145.8-.6 99.6-39.7 192.9-110.1 262.7z"/></svg>';

  function h(tag, attrs, html) {
    var el = document.createElement(tag);
    for (var k in attrs || {}) el.setAttribute(k, attrs[k]);
    if (html != null) el.innerHTML = html;
    return el;
  }
  function texto(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
  function limpo(s) { return (s || '').replace(/⚪/g, '').replace(/\s+/g, ' ').trim(); }

  // Estrutura: .pc-app > .form-container > form > (campos) + botão
  var app = h('div', { 'class': 'pc-app' + (alvo.getAttribute('data-modo') === 'embutido' ? ' pc-embutido' : '') });
  var cont = h('div', { 'class': 'form-container' });
  var form = h('form', { novalidate: '' });
  var raiz = h('div');
  form.appendChild(raiz);
  cont.appendChild(form);
  app.appendChild(cont);

  function blocoConteudo(def) {
    var g = h('div', { 'class': 'form-group' });
    var b = h('div', { 'class': def.tipo === 'header' ? 'bloco-header' : 'bloco' });
    // Blocos "header" sempre têm o título (mesmo vazio, como no original); blocos de imagem só se houver título
    if (def.titulo || def.tipo === 'header') b.appendChild(h('h1', { 'class': 'header-title' }, def.titulo ? texto(def.titulo) : ''));
    if (def.imagem) {
      var c = h('div', { 'class': 'image-container banner' });
      var src = /^(https?:)?\/\//.test(def.imagem) ? def.imagem : base + def.imagem;
      c.appendChild(h('img', { src: src, alt: def.titulo || '', loading: 'lazy' }));
      b.appendChild(c);
    }
    if (def.descricao) b.appendChild(h('div', { 'class': 'sub-label' }, def.descricao));
    g.appendChild(b);
    if (def.titulo) ultimoTitulo = def.titulo;
    return g;
  }

  function campo(def, i) {
    var g = h('div', { 'class': 'form-group' });
    var item = h('div', { 'class': 'form-item' });
    var id = 'pc-campo-' + i;
    if (def.titulo) {
      var lab = h('label', { 'class': 'form-item-label' + (def.obrigatorio ? ' required' : '') }, texto(def.titulo));
      if (def.tipo === 'dropdown') lab.setAttribute('for', id);
      item.appendChild(lab);
    }
    var outer = h('div', { 'class': 'form-item-control' });
    var ctrl = h('div', { 'class': 'form-item-input' });
    outer.appendChild(ctrl);
    var ler;

    if (def.tipo === 'dropdown') {
      var wrap = h('div', { 'class': 'select-wrap' });
      var sel = h('select', { id: id, name: id, 'class': 'vazio' });
      sel.appendChild(h('option', { value: '', disabled: '', selected: '', hidden: '' }, texto(def.placeholder || 'Selecione')));
      def.opcoes.forEach(function (o) { sel.appendChild(h('option', { value: o }, texto(o))); });
      sel.addEventListener('change', function () { sel.classList.toggle('vazio', !sel.value); item.classList.remove('tem-erro'); });
      wrap.appendChild(sel);
      wrap.insertAdjacentHTML('beforeend', SETA);
      ctrl.appendChild(wrap);
      ler = function () { return sel.value; };
    } else {
      var grupo = h('div', { 'class': 'radio-group', role: 'radiogroup' });
      def.opcoes.forEach(function (o) {
        var l = h('label', { 'class': 'radio' });
        var inp = h('input', { type: 'radio', name: id, value: o });
        inp.addEventListener('change', function () { item.classList.remove('tem-erro'); });
        l.appendChild(inp);
        l.appendChild(h('span', { 'class': 'inner' }));
        l.appendChild(h('span', { 'class': 'txt' }, texto(o)));
        grupo.appendChild(l);
      });
      ctrl.appendChild(grupo);
      ler = function () { var c = grupo.querySelector('input:checked'); return c ? c.value : ''; };
    }

    outer.appendChild(h('div', { 'class': 'erro' }, 'Por favor, preencha este campo'));
    item.appendChild(outer);
    g.appendChild(item);
    // Campos sem título (ex.: cor do HotStamping) usam o título do produto acima na mensagem
    campos.push({ def: def, el: item, titulo: limpo(def.titulo) || limpo(ultimoTitulo), ler: ler });
    return g;
  }

  cfg.campos.forEach(function (def, i) {
    raiz.appendChild((def.tipo === 'dropdown' || def.tipo === 'radio') ? campo(def, i) : blocoConteudo(def));
  });

  var gBtn = h('div', { 'class': 'form-group' });
  var btn = h('button', { type: 'submit', 'class': 'submit-button' }, ZAP + '<span>' + texto(cfg.textoBotao || 'Enviar no WhatsApp') + '</span>');
  gBtn.appendChild(btn);
  form.appendChild(gBtn);

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var primeiroErro = null;
    var linhas = [];
    campos.forEach(function (c) {
      var v = c.ler();
      if (c.def.obrigatorio && !v) {
        c.el.classList.add('tem-erro');
        primeiroErro = primeiroErro || c.el;
      }
      if (v && v !== 'Nenhuma das opções') linhas.push('*' + c.titulo + '*\n' + v);
    });
    if (primeiroErro) {
      primeiroErro.scrollIntoView({ behavior: 'smooth', block: 'center' });
      return;
    }
    window.open('https://wa.me/' + cfg.whatsapp + '?text=' + encodeURIComponent(linhas.join('\n\n')), '_blank');
  });

  alvo.appendChild(app);
})();
