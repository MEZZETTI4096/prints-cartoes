/* Página em blocos (WordPress): validação e envio do pedido para o WhatsApp.
   Lê os campos na ordem em que aparecem; campos sem título usam o último título de seção acima. */
(function () {
  function limpo(s) { return (s || '').replace(/⚪/g, '').replace(/\s+/g, ' ').trim(); }

  function iniciar(form) {
    form.addEventListener('change', function (e) {
      var item = e.target.closest('.form-item');
      if (item) item.classList.remove('tem-erro');
      if (e.target.tagName === 'SELECT') e.target.classList.toggle('vazio', !e.target.value);
    });

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var linhas = [];
      var ultimoTitulo = '';
      var primeiroErro = null;
      var nos = form.querySelectorAll('.pc-secao h1, .pc-secao h2, .pc-secao h3, .pc-secao h4, .pc-secao h5, .pc-secao h6, .pc-campo');

      Array.prototype.forEach.call(nos, function (n) {
        if (!n.classList.contains('pc-campo')) {
          var t = limpo(n.textContent);
          if (t) ultimoTitulo = t;
          return;
        }
        var item = n.querySelector('.form-item');
        var sel = n.querySelector('select');
        var marcado = n.querySelector('input:checked');
        var valor = sel ? sel.value : (marcado ? marcado.value : '');
        var titulo = limpo(n.getAttribute('data-titulo')) || ultimoTitulo;

        if (n.hasAttribute('data-obrigatorio') && !valor) {
          item.classList.add('tem-erro');
          primeiroErro = primeiroErro || item;
        }
        if (valor && valor !== 'Nenhuma das opções') linhas.push('*' + titulo + '*\n' + valor);
      });

      if (primeiroErro) {
        primeiroErro.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
      }
      var numero = (form.getAttribute('data-whatsapp') || '').replace(/\D/g, '');
      window.open('https://wa.me/' + numero + '?text=' + encodeURIComponent(linhas.join('\n\n')), '_blank');
    });
  }

  function pronto() { Array.prototype.forEach.call(document.querySelectorAll('form.pc-form'), iniciar); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', pronto); else pronto();
})();
