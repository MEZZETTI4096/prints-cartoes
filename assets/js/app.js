/* Renderiza o formulário a partir de dados.js e envia o pedido para o WhatsApp. */
(function () {
  var cfg = window.FORM_CONFIG;
  var raiz = document.getElementById('campos');
  var campos = []; // { def, el, titulo, ler() }
  var ultimoTitulo = '';

  var SETA = '<svg viewBox="64 64 896 896" fill="currentColor" aria-hidden="true"><path d="M884 256h-75c-5.1 0-9.9 2.5-12.9 6.6L512 654.2 227.9 262.6c-3-4.1-7.8-6.6-12.9-6.6h-75c-6.5 0-10.3 7.4-6.5 12.7l352.6 486.1c12.8 17.6 39 17.6 51.7 0l352.6-486.1c3.9-5.3.1-12.7-6.4-12.7z"/></svg>';

  function h(tag, attrs, html) {
    var el = document.createElement(tag);
    for (var k in attrs || {}) el.setAttribute(k, attrs[k]);
    if (html != null) el.innerHTML = html;
    return el;
  }
  function texto(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
  function limpo(s) { return (s || '').replace(/⚪/g, '').replace(/\s+/g, ' ').trim(); }

  function blocoConteudo(def) {
    var g = h('div', { 'class': 'form-group' });
    var b = h('div', { 'class': def.tipo === 'header' ? 'bloco-header' : 'bloco' });
    // Blocos "header" sempre têm o título (mesmo vazio, como no original); blocos de imagem só se houver título
    if (def.titulo || def.tipo === 'header') b.appendChild(h('h1', { 'class': 'header-title' }, def.titulo ? texto(def.titulo) : ''));
    if (def.imagem) {
      var c = h('div', { 'class': 'image-container banner' });
      c.appendChild(h('img', { src: def.imagem, alt: def.titulo || '', loading: 'lazy' }));
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
    var id = 'campo-' + i;
    if (def.titulo) {
      item.appendChild(h('label', {
        'class': 'form-item-label' + (def.obrigatorio ? ' required' : ''),
        'for': def.tipo === 'dropdown' ? id : ''
      }, texto(def.titulo)));
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
        var lab = h('label', { 'class': 'radio' });
        var inp = h('input', { type: 'radio', name: id, value: o });
        inp.addEventListener('change', function () { item.classList.remove('tem-erro'); });
        lab.appendChild(inp);
        lab.appendChild(h('span', { 'class': 'inner' }));
        lab.appendChild(h('span', { 'class': 'txt' }, texto(o)));
        grupo.appendChild(lab);
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
    var el = (def.tipo === 'dropdown' || def.tipo === 'radio') ? campo(def, i) : blocoConteudo(def);
    raiz.appendChild(el);
  });
  document.getElementById('texto-botao').textContent = cfg.textoBotao || 'Enviar no WhatsApp';

  document.getElementById('pedido').addEventListener('submit', function (e) {
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
    var msg = linhas.join('\n\n');
    window.open('https://wa.me/' + cfg.whatsapp + '?text=' + encodeURIComponent(msg), '_blank');
  });
})();
