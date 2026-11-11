// Gestão De Angeli — pequenos comportamentos da tela (sem bibliotecas externas).
(function () {
  'use strict';

  var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

  function aviso(texto, erro) {
    var t = document.getElementById('toast');
    if (!t) { return; }
    t.textContent = texto;
    t.className = 'toast' + (erro ? ' erro' : '');
    t.hidden = false;
    clearTimeout(aviso._t);
    aviso._t = setTimeout(function () { t.hidden = true; }, 3500);
  }

  // Confirmação antes de ações sensíveis: <form data-confirmar="Tem certeza?">
  document.addEventListener('submit', function (ev) {
    var msg = ev.target.getAttribute && ev.target.getAttribute('data-confirmar');
    if (msg && !window.confirm(msg)) { ev.preventDefault(); }
  });

  // Copiar texto: <button data-copiar="#id">
  document.addEventListener('click', function (ev) {
    var b = ev.target.closest('[data-copiar]');
    if (!b) { return; }
    var alvo = document.querySelector(b.getAttribute('data-copiar'));
    if (alvo && navigator.clipboard) {
      navigator.clipboard.writeText(alvo.textContent.trim()).then(function () { aviso('Copiado.'); });
    }
  });

  // Filtros: aplica ao escolher
  document.querySelectorAll('.filtros select').forEach(function (s) {
    s.addEventListener('change', function () { s.form.submit(); });
  });

  // Formulários com data-auto enviam ao escolher uma opção (ex.: mover pedido de etapa)
  document.querySelectorAll('form[data-auto] select').forEach(function (s) {
    s.addEventListener('change', function () { if (s.value) { s.form.submit(); } });
  });

  // ------------------------------------------------------------- quadro
  var quadro = document.querySelector('.quadro');
  if (!quadro) { return; }
  var api = quadro.getAttribute('data-api');
  var arrastando = null;

  function atualizarContagens() {
    quadro.querySelectorAll('.coluna').forEach(function (col) {
      var corpo = col.querySelector('.coluna-corpo');
      var n = corpo.querySelectorAll('.cartao-tarefa').length;
      col.querySelector('.cont').textContent = n;
      var vazio = corpo.querySelector('.coluna-vazia');
      if (n === 0 && !vazio) {
        var p = document.createElement('p');
        p.className = 'coluna-vazia';
        p.textContent = 'Nenhuma tarefa';
        corpo.appendChild(p);
      } else if (n > 0 && vazio) {
        vazio.remove();
      }
    });
  }

  function mover(cartao, colunaDestino) {
    var corpoOrigem = cartao.parentElement;
    var proximo = cartao.nextElementSibling;
    var corpoDestino = colunaDestino.querySelector('.coluna-corpo');
    var novoStatus = colunaDestino.getAttribute('data-status');
    if (corpoOrigem === corpoDestino) { return; }

    corpoDestino.insertBefore(cartao, corpoDestino.querySelector('.cartao-tarefa'));
    cartao.querySelector('.mover').value = '';
    atualizarContagens();

    fetch(api + '/' + cartao.getAttribute('data-id') + '/status', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
      body: JSON.stringify({ status: novoStatus })
    }).then(function (r) {
      if (!r.ok) { throw new Error(r.status); }
      aviso('Status: ' + novoStatus);
    }).catch(function () {
      // desfaz na tela se o servidor recusou
      corpoOrigem.insertBefore(cartao, proximo);
      atualizarContagens();
      aviso('Não foi possível mudar o status. Recarregue a página e tente de novo.', true);
    });
  }

  quadro.addEventListener('dragstart', function (ev) {
    var c = ev.target.closest && ev.target.closest('.cartao-tarefa');
    if (!c) { return; }
    arrastando = c;
    c.classList.add('arrastando');
    ev.dataTransfer.effectAllowed = 'move';
    ev.dataTransfer.setData('text/plain', c.getAttribute('data-id'));
  });
  quadro.addEventListener('dragend', function () {
    if (arrastando) { arrastando.classList.remove('arrastando'); }
    arrastando = null;
    quadro.querySelectorAll('.sobre').forEach(function (x) { x.classList.remove('sobre'); });
  });
  quadro.addEventListener('dragover', function (ev) {
    var corpo = ev.target.closest && ev.target.closest('.coluna-corpo, .coluna');
    if (!corpo || !arrastando) { return; }
    ev.preventDefault();
    quadro.querySelectorAll('.sobre').forEach(function (x) { x.classList.remove('sobre'); });
    (corpo.classList.contains('coluna') ? corpo.querySelector('.coluna-corpo') : corpo).classList.add('sobre');
  });
  quadro.addEventListener('drop', function (ev) {
    var col = ev.target.closest && ev.target.closest('.coluna');
    if (!col || !arrastando) { return; }
    ev.preventDefault();
    mover(arrastando, col);
  });

  // Alternativa sem arrastar (celular): menu "Mover para…"
  quadro.addEventListener('change', function (ev) {
    var sel = ev.target.closest && ev.target.closest('.mover');
    if (!sel || !sel.value) { return; }
    var cartao = sel.closest('.cartao-tarefa');
    var destino = Array.prototype.find.call(quadro.querySelectorAll('.coluna'), function (c) {
      return c.getAttribute('data-status') === sel.value;
    });
    if (destino) { mover(cartao, destino); }
  });
})();


// Administração → Aparência: mostra as cores escolhidas na hora, antes de salvar.
document.addEventListener('DOMContentLoaded', function () {
  var form = document.getElementById('form-aparencia');
  if (!form) { return; }
  var raiz = document.documentElement;
  var marca = document.getElementById('cor-marca');
  function misturar(c, pct, com) { return 'color-mix(in srgb, ' + c + ' ' + pct + '%, ' + com + ')'; }
  function aplicarMarca() {
    var c = marca.value;
    raiz.style.setProperty('--marca', c);
    raiz.style.setProperty('--marca-escura', misturar(c, 72, '#000'));
    raiz.style.setProperty('--marca-clara', misturar(c, 13, 'var(--superficie)'));
    var r = parseInt(c.substr(1, 2), 16) / 255, g = parseInt(c.substr(3, 2), 16) / 255, b = parseInt(c.substr(5, 2), 16) / 255;
    var l = function (x) { return x <= 0.03928 ? x / 12.92 : Math.pow((x + 0.055) / 1.055, 2.4); };
    var lum = 0.2126 * l(r) + 0.7152 * l(g) + 0.0722 * l(b);
    raiz.style.setProperty('--marca-texto', lum > 0.38 ? '#1d2420' : '#ffffff');
  }
  marca.addEventListener('input', aplicarMarca);
  form.querySelectorAll('[data-cor-alvo]').forEach(function (bt) {
    bt.addEventListener('click', function () { marca.value = bt.getAttribute('data-cor'); aplicarMarca(); });
  });
  form.querySelectorAll('input[data-previa]').forEach(function (inp) {
    inp.addEventListener('input', function () {
      var alvo = inp.parentNode.querySelector('[data-alvo-previa]');
      alvo.style.setProperty('--cor', inp.value);
    });
  });
});

// ---------------------------------------------------------------------------
// Arrastar e soltar (calendários e quadros de pedidos/ideias do blog)
(function () {
  'use strict';
  var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

  function aviso(texto, erro) {
    var t = document.getElementById('toast');
    if (!t) { return; }
    t.textContent = texto;
    t.className = 'toast' + (erro ? ' erro' : '');
    t.hidden = false;
    clearTimeout(aviso._t);
    aviso._t = setTimeout(function () { t.hidden = true; }, 3500);
  }
  function guardar(k, v) { try { sessionStorage.setItem(k, v); } catch (e) { /* sem armazenamento: tudo bem */ } }
  function ler(k) { try { var v = sessionStorage.getItem(k); sessionStorage.removeItem(k); return v; } catch (e) { return null; } }

  // Depois de recarregar: volta para onde a pessoa estava e mostra o aviso guardado.
  var y = ler('rolagem');
  if (y) { window.scrollTo(0, parseInt(y, 10) || 0); }
  var msg = ler('aviso');
  if (msg) { aviso(msg); }

  function recarregar(mensagem) {
    guardar('rolagem', String(window.scrollY));
    if (mensagem) { guardar('aviso', mensagem); }
    location.reload();
  }

  // Formulários que enviam ao escolher (ex.: situação do post) não jogam a página para o topo.
  document.addEventListener('submit', function (ev) {
    if (ev.target.hasAttribute && ev.target.hasAttribute('data-auto')) { guardar('rolagem', String(window.scrollY)); }
  });

  function limparSobre() {
    document.querySelectorAll('.sobre').forEach(function (x) { x.classList.remove('sobre'); });
  }
  function falha() { aviso('Não foi possível mover. Você pode não ter permissão — recarregue a página e tente de novo.', true); }

  var arrastado = null;
  document.addEventListener('dragstart', function (ev) {
    var el = ev.target.closest && ev.target.closest('.evt[data-api], .ed-post[data-api], .cartao-tarefa[data-acao]');
    if (!el) { return; }
    arrastado = el;
    el.classList.add('arrastando');
    ev.dataTransfer.effectAllowed = 'move';
    ev.dataTransfer.setData('text/plain', el.getAttribute('data-api') || el.getAttribute('data-acao'));
  });
  document.addEventListener('dragend', function () {
    if (arrastado) { arrastado.classList.remove('arrastando'); }
    arrastado = null;
    limparSobre();
  });
  document.addEventListener('dragover', function (ev) {
    if (!arrastado) { return; }
    var alvo = arrastado.hasAttribute('data-api')
      ? ev.target.closest('.cal-dia[data-dia], .ed-cel[data-dia]')
      : ev.target.closest('[data-mover-campo] .coluna');
    if (!alvo) { return; }
    ev.preventDefault();
    limparSobre();
    alvo.classList.add('sobre');
  });
  document.addEventListener('drop', function (ev) {
    if (!arrastado) { return; }
    var el = arrastado;

    if (el.hasAttribute('data-api')) {                       // calendário: muda o dia
      var dia = ev.target.closest('.cal-dia[data-dia], .ed-cel[data-dia]');
      if (!dia) { return; }
      ev.preventDefault();
      var de = el.getAttribute('data-dia'), para = dia.getAttribute('data-dia');
      if (de === para) { return; }
      fetch(el.getAttribute('data-api'), {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        body: JSON.stringify({ de: de, para: para })
      }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
        .then(function (x) { if (x.ok) { recarregar(x.j.mensagem); } else { aviso(x.j.erro || 'Não foi possível mover.', true); } })
        .catch(falha);
      return;
    }

    var col = ev.target.closest('[data-mover-campo] .coluna');  // quadro: muda de coluna
    if (!col) { return; }
    ev.preventDefault();
    var quadro = col.closest('[data-mover-campo]');
    var valor = col.getAttribute('data-valor');
    if (col.contains(el)) { return; }
    var corpo = new URLSearchParams();
    corpo.set('_csrf', csrf);
    corpo.set(quadro.getAttribute('data-mover-campo'), valor);
    fetch(el.getAttribute('data-acao'), {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'fetch' },
      body: corpo.toString()
    }).then(function (r) { return r.ok ? r.json() : Promise.reject(r.status); })
      .then(function (j) { recarregar(j.mensagem || 'Movido.'); })
      .catch(falha);
  });
})();

// Editor de texto formatado (campos <textarea data-rico>). Sem JavaScript, o campo continua sendo texto simples.
(function () {
  var areas = document.querySelectorAll('textarea[data-rico]');
  if (!areas.length || !document.execCommand) { return; }

  var BOTOES = [
    ['bold', 'N', 'Negrito', null, 'b'], ['italic', 'I', 'Itálico', null, 'i'], ['underline', 'S', 'Sublinhado', null, 'u'],
    '|', ['formatBlock', 'Título', 'Título', '<h2>'], ['formatBlock', 'Subtítulo', 'Subtítulo', '<h3>'], ['formatBlock', 'Texto', 'Parágrafo normal', '<p>'],
    '|', ['insertUnorderedList', '• Lista', 'Lista com marcadores'], ['insertOrderedList', '1. Lista', 'Lista numerada'], ['formatBlock', '❝ Citação', 'Citação', '<blockquote>'],
    '|', ['link', 'Link', 'Inserir link'], ['unlink', 'Tirar link', 'Remover link'], ['removeFormat', 'Limpar', 'Limpar formatação']
  ];
  var TROCA = { b: 'strong', strong: 'strong', i: 'em', em: 'em', u: 'u', s: 's', strike: 's', del: 's', h1: 'h2', h2: 'h2', h3: 'h3', h4: 'h3', h5: 'h3', h6: 'h3', p: 'p', div: 'p', ul: 'ul', ol: 'ol', li: 'li', blockquote: 'blockquote' };
  var DESCARTA = { script: 1, style: 1, iframe: 1, object: 1, embed: 1, template: 1, svg: 1, math: 1, noscript: 1, head: 1, title: 1, meta: 1, link: 1 };

  function esc(t) { return t.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }

  // Limpa o que foi colado (Word, Google Docs, sites): fica só a formatação básica.
  function limpa(no) {
    if (no.nodeType === 3) { return esc(no.nodeValue || ''); }
    if (no.nodeType !== 1) { return ''; }
    var t = no.tagName.toLowerCase();
    if (DESCARTA[t]) { return ''; }
    var d = ''; for (var c = no.firstChild; c; c = c.nextSibling) { d += limpa(c); }
    var st = no.getAttribute('style') || '';
    if (t === 'br') { return '<br>'; }
    if (t === 'a') {
      var h = (no.getAttribute('href') || '').trim();
      return /^(https?:\/\/|mailto:)/i.test(h) ? '<a href="' + esc(h) + '">' + d + '</a>' : d;
    }
    if (TROCA[t]) {
      if (TROCA[t] === 'strong' && /font-weight\s*:\s*(normal|400)/i.test(st)) { return d; }
      return '<' + TROCA[t] + '>' + d + '</' + TROCA[t] + '>';
    }
    if (t === 'span') {
      if (/font-weight\s*:\s*(bold|[6-9]00)/i.test(st)) { d = '<strong>' + d + '</strong>'; }
      if (/font-style\s*:\s*italic/i.test(st)) { d = '<em>' + d + '</em>'; }
      if (/text-decoration[^;]*underline/i.test(st)) { d = '<u>' + d + '</u>'; }
    }
    return d;
  }

  areas.forEach(function (ta) {
    var caixa = document.createElement('div'); caixa.className = 'rico';
    var barra = document.createElement('div'); barra.className = 'rico-barra'; barra.setAttribute('role', 'toolbar'); barra.setAttribute('aria-label', 'Formatação do texto');
    var ed = document.createElement('div'); ed.className = 'rico-area texto-rico'; ed.contentEditable = 'true'; ed.setAttribute('role', 'textbox'); ed.setAttribute('aria-multiline', 'true');
    if (ta.getAttribute('aria-labelledby')) { ed.setAttribute('aria-labelledby', ta.getAttribute('aria-labelledby')); }
    if (ta.hasAttribute('data-rico-baixo')) { ed.classList.add('baixo'); }
    ed.innerHTML = ta.value;   // o servidor já entregou o texto limpo

    BOTOES.forEach(function (b) {
      if (b === '|') { var sp = document.createElement('span'); sp.className = 'rico-sep'; sp.setAttribute('aria-hidden', 'true'); barra.appendChild(sp); return; }
      var bt = document.createElement('button'); bt.type = 'button'; bt.className = 'rico-bt'; bt.title = b[2]; bt.setAttribute('aria-label', b[2]);
      if (b[4]) { var el = document.createElement(b[4]); el.textContent = b[1]; bt.appendChild(el); } else { bt.textContent = b[1]; }
      bt.addEventListener('mousedown', function (ev) { ev.preventDefault(); });   // não perde a seleção do texto
      bt.addEventListener('click', function () {
        ed.focus();
        if (b[0] === 'link') {
          var end = window.prompt('Endereço do link (começa com https://):', 'https://');
          if (!end) { return; }
          end = end.trim();
          if (!/^(https?:\/\/|mailto:)/i.test(end)) { end = 'https://' + end; }
          document.execCommand('createLink', false, end);
        } else if (b[0] === 'removeFormat') {
          document.execCommand('removeFormat'); document.execCommand('formatBlock', false, '<p>');
        } else {
          document.execCommand(b[0], false, b[3] || null);
        }
      });
      barra.appendChild(bt);
    });

    ed.addEventListener('paste', function (ev) {
      var cd = ev.clipboardData; if (!cd) { return; }
      ev.preventDefault();
      var html = cd.getData('text/html');
      if (html) {
        var doc = new DOMParser().parseFromString(html, 'text/html');
        document.execCommand('insertHTML', false, limpa(doc.body));
      } else {
        document.execCommand('insertText', false, cd.getData('text/plain'));
      }
    });
    ed.addEventListener('keydown', function (ev) {
      if ((ev.ctrlKey || ev.metaKey) && !ev.shiftKey && ev.key.toLowerCase() === 'k') { ev.preventDefault(); barra.querySelector('[title="Inserir link"]').click(); }
    });

    ta.hidden = true;
    ta.parentNode.insertBefore(caixa, ta);
    caixa.appendChild(barra); caixa.appendChild(ed);
    var form = ta.form;
    if (form) {
      form.addEventListener('submit', function () {
        var vazio = ed.textContent.trim() === '' && !ed.querySelector('li,br,img');
        ta.value = vazio ? '' : ed.innerHTML;
      });
    }
  });
})();
