/* Páginas de pedido: validação e envio para o WhatsApp.
   Usado pelo WordPress (blocos) e pelo site estático (app.js monta o mesmo HTML).

   Mensagem:
   - percorre seções e campos na ordem da página; campo sem título usa o último título de seção acima;
   - mantém os emojis do título (⚪);
   - só a quantidade e o preço vão em negrito (*50 UND* | *R$ 95.99* | ...);
   - cada produto escolhido leva o link da imagem da sua seção (form[data-enviar-imagem]). */
(function () {
  var TITULOS = 'h1, h2, h3, h4, h5, h6';

  function limpo(s) { return (s || '').replace(/\s+/g, ' ').trim(); }

  // Quantidade no início ("50 UND", "10mil und", "1.000 un") e preços ("R$ 95.99")
  var QTD = /^\d[\d.,]*\s*(?:mil\b)?\s*(?:(?:unidades|unid|und|un|p[çc]s|pe[çc]as|folhas|blocos|caixas|cx|milheiros?)(?![a-zà-ú])\.?|(?=\s*\|))/i;
  var PRECO = /R\$\s*\d[\d.,]*\d|R\$\s*\d/g;
  function destacar(valor) {
    var v = limpo(valor);
    var ini = '';
    var m = v.match(QTD);
    if (m && limpo(m[0])) {
      ini = '*' + limpo(m[0]) + '*' + (/\s$/.test(m[0]) ? ' ' : '');
      v = v.slice(m[0].length);
    }
    return ini + v.replace(PRECO, function (p) { return '*' + limpo(p) + '*'; });
  }

  function ehProduto(secao) {
    return !!(secao.querySelector(TITULOS) && secao.querySelector('img'));
  }
  /* O layout no computador (imagem ao lado) é só CSS (container query em style.css). */

  /* ---------- Envio ---------- */
  function mensagem(form) {
    var linhas = [];
    var erro = null;
    var titulo = '';
    var imagem = '';
    var enviadas = {};
    var comImagem = form.hasAttribute('data-enviar-imagem');

    Array.prototype.forEach.call(form.querySelectorAll('.pc-secao, .pc-campo'), function (n) {
      if (n.classList.contains('pc-secao')) {
        var h = n.querySelector(TITULOS);
        var t = h ? limpo(h.textContent) : '';
        if (t) titulo = t;
        var img = n.classList.contains('pc-produto') ? n.querySelector('img') : null;
        imagem = img ? img.src : '';
        return;
      }
      var item = n.querySelector('.form-item');
      var sel = n.querySelector('select');
      var marcado = n.querySelector('input:checked');
      var valor = sel ? sel.value : (marcado ? marcado.value : '');
      if (n.hasAttribute('data-obrigatorio') && !valor) {
        item.classList.add('tem-erro');
        erro = erro || item;
      }
      if (!valor || /^nenhuma das op/i.test(valor)) return;
      var partes = [limpo(n.getAttribute('data-titulo')) || titulo, destacar(valor)];
      if (comImagem && imagem && !enviadas[imagem]) {
        partes.push(imagem);
        enviadas[imagem] = true;
      }
      linhas.push(partes.filter(Boolean).join('\n'));
    });
    return { texto: linhas.join('\n\n'), erro: erro };
  }

  function iniciar(form) {
    if (form._pcIniciado) return;
    form._pcIniciado = true;

    Array.prototype.forEach.call(form.querySelectorAll('.pc-secao'), function (s) {
      if (ehProduto(s)) s.classList.add('pc-produto');
    });

    form.addEventListener('change', function (e) {
      var item = e.target.closest('.form-item');
      if (item) item.classList.remove('tem-erro');
      if (e.target.tagName === 'SELECT') e.target.classList.toggle('vazio', !e.target.value);
    });

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var m = mensagem(form);
      if (m.erro) {
        m.erro.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
      }
      var numero = (form.getAttribute('data-whatsapp') || '').replace(/\D/g, '');
      window.open('https://wa.me/' + numero + '?text=' + encodeURIComponent(m.texto), '_blank');
    });
  }

  function pronto() { Array.prototype.forEach.call(document.querySelectorAll('form.pc-form'), iniciar); }
  window.PCFormulario = { iniciar: iniciar, destacar: destacar };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', pronto); else pronto();
})();
