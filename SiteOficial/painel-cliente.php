<?php
require 'sessao_db.php';

// Só deixa entrar quem fez login como cliente
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'cliente') {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Minha conta — Beca's Cafetery</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700&family=Manrope:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="auth.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<style>
  body{ align-items:flex-start; padding-top:48px; padding-bottom: 80px; }
  .auth-card{ max-width: 920px; }
  .top-row{ display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; }
  .top-row a{ font-size:0.82rem; color:var(--rust); text-decoration:none; font-weight:600; }

  /* Abas simples */
  .tabs{ display:flex; gap:8px; margin: 22px 0 20px; border-bottom:1px solid var(--line); }
  .tab-btn{
    font-family: var(--font-display); font-weight:600; font-size:0.92rem;
    background:none; border:none; padding:10px 4px; cursor:pointer;
    color: var(--muted); border-bottom:2px solid transparent; margin-bottom:-1px;
  }
  .tab-btn.ativa{ color: var(--rust); border-bottom-color: var(--rust); }
  .tab-pane{ display:none; }
  .tab-pane.ativa{ display:block; }

  /* Cardápio */
  .categoria-titulo{
    font-family: var(--font-display); font-size:1.05rem; color:var(--espresso-2);
    margin: 22px 0 10px;
  }
  .produtos-grid{
    display:grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap:14px;
  }
  .produto-card{
    background: var(--cream); border:1px solid var(--line); border-radius:16px;
    padding:14px; display:flex; flex-direction:column; gap:6px;
  }
  .produto-foto{
    width:100%; aspect-ratio: 4/3; border-radius:10px; object-fit:cover;
    background: var(--peach);
  }
  .produto-nome{ font-family: var(--font-display); font-size:0.95rem; color:var(--espresso-2); }
  .produto-desc{ font-size:0.78rem; color:var(--muted); line-height:1.4; min-height: 2.4em; }
  .produto-rodape{ display:flex; align-items:center; justify-content:space-between; margin-top:4px; }
  .produto-preco{ font-weight:700; color:var(--rust); font-size:0.92rem; }
  .btn-add{
    border:none; background:var(--rust); color:var(--cream); font-family:var(--font-display);
    font-weight:600; font-size:0.8rem; padding:7px 12px; border-radius:999px; cursor:pointer;
  }
  .btn-add:hover{ background:var(--espresso); }
  .vazio-msg{ color: var(--muted); font-size:0.88rem; padding: 10px 0; }

  /* Carrinho */
  .carrinho-item{
    display:flex; align-items:center; justify-content:space-between;
    padding:10px 0; border-bottom:1px dashed var(--line); gap:10px;
  }
  .carrinho-item-nome{ font-size:0.88rem; color:var(--espresso-2); font-weight:600; }
  .carrinho-item-preco{ font-size:0.78rem; color:var(--muted); }
  .qtd-controle{ display:flex; align-items:center; gap:8px; }
  .qtd-btn{
    width:26px; height:26px; border-radius:50%; border:1px solid var(--line);
    background:var(--cream); cursor:pointer; font-weight:700; color:var(--espresso-2);
  }
  .carrinho-total{
    display:flex; justify-content:space-between; font-family:var(--font-display);
    font-size:1.1rem; color:var(--espresso-2); margin-top:14px; padding-top:14px;
    border-top:1px solid var(--line);
  }
  .carrinho-total b{ color:var(--rust); }

  .campo{ margin: 14px 0; }
  .campo label{ display:block; font-size:0.82rem; font-weight:600; margin-bottom:6px; }
  .campo select, .campo input{
    width:100%; padding:10px 12px; border-radius:10px; border:1px solid var(--line);
    background:var(--cream); font-family:var(--font-body); font-size:0.9rem; color:var(--espresso-2);
  }

  /* PIX */
  .pix-box{
    background:var(--cream); border:1px dashed var(--rust); border-radius:14px;
    padding:16px; margin-top:16px; text-align:center;
  }
  .pix-chave{
    font-family: monospace; font-size:0.85rem; background:var(--latte);
    padding:10px; border-radius:8px; margin:10px 0; word-break:break-all;
  }
  #pix-qrcode{ display:flex; justify-content:center; margin: 12px 0; }
  #pix-qrcode img{ border-radius:10px; border:1px solid var(--line); }
  .btn-copiar{
    border:none; background:var(--espresso); color:var(--cream); font-family:var(--font-display);
    font-weight:600; font-size:0.82rem; padding:8px 16px; border-radius:999px; cursor:pointer;
  }

  /* Pedidos */
  .pedido-card{
    border:1px solid var(--line); border-radius:14px; padding:14px 16px; margin-bottom:12px;
    background: var(--cream);
  }
  .pedido-topo{ display:flex; justify-content:space-between; align-items:center; }
  .badge{
    font-size:0.72rem; font-weight:700; padding:4px 10px; border-radius:999px;
    text-transform:uppercase; letter-spacing:0.02em;
  }
  .badge.aguardando_pagamento{ background:#F4E3C7; color:#8A5B1E; }
  .badge.pago{ background:#DCEACB; color:#4B6A1E; }
  .badge.preparando{ background:#F4E3C7; color:#8A5B1E; }
  .badge.saiu_para_entrega{ background:#D8E6EE; color:#245674; }
  .badge.concluido{ background:#DCEACB; color:#3B5B1E; }
  .badge.cancelado{ background:#F4D9D2; color:#7A2A1B; }

  .msg{ padding:12px 14px; border-radius:12px; font-size:0.85rem; margin-bottom:14px; }
  .msg.erro{ background:#F4D9D2; color:#7A2A1B; }
  .msg.sucesso{ background:#E3ECC7; color:#4B5A1E; }
</style>
</head>
<body>
  <div class="auth-card">
    <div class="top-row">
      <span></span>
      <a href="logout.php">Sair</a>
    </div>
    <h1>Olá, <?= htmlspecialchars($_SESSION['nome']) ?>! ☕</h1>
    <p class="sub">Essa é a sua área de cliente na Beca's Cafetery.</p>

    <div class="tabs">
      <button class="tab-btn ativa" data-tab="cardapio">Cardápio</button>
      <button class="tab-btn" data-tab="carrinho">Carrinho <span id="contagem-carrinho"></span></button>
      <button class="tab-btn" data-tab="pedidos">Meus pedidos</button>
    </div>

    <!-- CARDÁPIO -->
    <div class="tab-pane ativa" id="pane-cardapio">
      <div id="cardapio-conteudo">Carregando cardápio...</div>
    </div>

    <!-- CARRINHO -->
    <div class="tab-pane" id="pane-carrinho">
      <div id="carrinho-msg"></div>
      <div id="carrinho-conteudo">Carregando carrinho...</div>

      <div id="checkout-form" style="display:none;">
        <div class="campo">
          <label for="tipo_entrega">Como você quer receber?</label>
          <select id="tipo_entrega">
            <option value="retirada">Retirar na loja</option>
            <option value="entrega">Entrega</option>
          </select>
        </div>
        <div class="campo" id="campo-endereco" style="display:none;">
          <label for="endereco">Endereço de entrega</label>
          <input type="text" id="endereco" placeholder="Rua, número, bairro">
        </div>
        <button class="btn" id="btn-finalizar">Finalizar pedido</button>
      </div>

      <div class="pix-box" id="pix-box" style="display:none;"></div>
    </div>

    <!-- PEDIDOS -->
    <div class="tab-pane" id="pane-pedidos">
      <div id="pedidos-conteudo">Carregando pedidos...</div>
    </div>
  </div>

<script>
const API = {
  cardapio: 'cardapio_listar.php',
  carrinho: 'carrinho.php',
  pedidoCriar: 'pedido_criar.php',
  pedidoStatus: 'pedido_status.php',
};

const STATUS_LABEL = {
  aguardando_pagamento: 'Aguardando pagamento',
  pago: 'Pago',
  preparando: 'Preparando',
  saiu_para_entrega: 'Saiu para entrega',
  concluido: 'Concluído',
  cancelado: 'Cancelado',
};

// ---------- Navegação por abas ----------
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('ativa'));
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('ativa'));
    btn.classList.add('ativa');
    document.getElementById('pane-' + btn.dataset.tab).classList.add('ativa');

    if (btn.dataset.tab === 'carrinho') carregarCarrinho();
    if (btn.dataset.tab === 'pedidos') carregarPedidos();
  });
});

// ---------- Cardápio ----------
async function carregarCardapio() {
  const el = document.getElementById('cardapio-conteudo');
  try {
    const res = await fetch(API.cardapio);
    const dados = await res.json();
    if (!dados.sucesso) throw new Error();

    if (dados.produtos.length === 0) {
      el.innerHTML = '<p class="vazio-msg">Nenhum produto disponível no momento.</p>';
      return;
    }

    const porCategoria = {};
    dados.produtos.forEach(p => {
      (porCategoria[p.categoria] = porCategoria[p.categoria] || []).push(p);
    });

    const nomesCategoria = { doce: 'Doces', bebida: 'Bebidas' };

    el.innerHTML = Object.entries(porCategoria).map(([cat, itens]) => `
      <h2 class="categoria-titulo">${nomesCategoria[cat] || cat}</h2>
      <div class="produtos-grid">
        ${itens.map(p => `
          <div class="produto-card">
            <img class="produto-foto" src="${p.foto || 'assets/produto-padrao.png'}" alt="${escapeHtml(p.nome)}" onerror="this.style.background='var(--peach)'; this.src='';">
            <div class="produto-nome">${escapeHtml(p.nome)}</div>
            <div class="produto-desc">${escapeHtml(p.descricao || '')}</div>
            <div class="produto-rodape">
              <span class="produto-preco">R$ ${p.preco.toFixed(2).replace('.', ',')}</span>
              <button class="btn-add" data-id="${p.id}">Adicionar</button>
            </div>
          </div>
        `).join('')}
      </div>
    `).join('');

    el.querySelectorAll('.btn-add').forEach(btn => {
      btn.addEventListener('click', () => adicionarAoCarrinho(btn.dataset.id, btn));
    });
  } catch (e) {
    el.innerHTML = '<p class="vazio-msg">Não foi possível carregar o cardápio agora.</p>';
  }
}

async function adicionarAoCarrinho(produtoId, btn) {
  btn.disabled = true;
  const textoOriginal = btn.textContent;
  btn.textContent = 'Adicionando...';
  try {
    const res = await fetch(API.carrinho, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ produto_id: Number(produtoId), quantidade: 1 }),
    });
    const dados = await res.json();
    if (!dados.sucesso) throw new Error(dados.erro || 'Erro ao adicionar.');
    btn.textContent = 'Adicionado ✓';
    atualizarContagemCarrinho();
    setTimeout(() => { btn.textContent = textoOriginal; btn.disabled = false; }, 1200);
  } catch (e) {
    btn.textContent = 'Erro, tente de novo';
    setTimeout(() => { btn.textContent = textoOriginal; btn.disabled = false; }, 1500);
  }
}

async function atualizarContagemCarrinho() {
  try {
    const res = await fetch(API.carrinho);
    const dados = await res.json();
    const totalItens = (dados.itens || []).reduce((s, i) => s + i.quantidade, 0);
    document.getElementById('contagem-carrinho').textContent = totalItens > 0 ? `(${totalItens})` : '';
  } catch (e) { /* silencioso */ }
}

// ---------- Carrinho ----------
async function carregarCarrinho() {
  const el = document.getElementById('carrinho-conteudo');
  document.getElementById('pix-box').style.display = 'none';
  document.getElementById('carrinho-msg').innerHTML = '';
  try {
    const res = await fetch(API.carrinho);
    const dados = await res.json();
    if (!dados.sucesso) throw new Error();

    atualizarContagemCarrinho();

    if (dados.itens.length === 0) {
      el.innerHTML = '<p class="vazio-msg">Seu carrinho está vazio. Que tal dar uma olhada no cardápio?</p>';
      document.getElementById('checkout-form').style.display = 'none';
      return;
    }

    el.innerHTML = dados.itens.map(i => `
      <div class="carrinho-item" data-produto-id="${i.produto_id}">
        <div>
          <div class="carrinho-item-nome">${escapeHtml(i.nome)}</div>
          <div class="carrinho-item-preco">R$ ${i.preco.toFixed(2).replace('.', ',')} cada</div>
        </div>
        <div class="qtd-controle">
          <button class="qtd-btn" data-acao="menos">−</button>
          <span>${i.quantidade}</span>
          <button class="qtd-btn" data-acao="mais">+</button>
        </div>
      </div>
    `).join('') + `
      <div class="carrinho-total"><span>Total</span> <b>R$ ${dados.total.toFixed(2).replace('.', ',')}</b></div>
    `;

    el.querySelectorAll('.carrinho-item').forEach(itemEl => {
      const produtoId = itemEl.dataset.produtoId;
      const qtdSpan = itemEl.querySelector('.qtd-controle span');
      itemEl.querySelectorAll('.qtd-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
          let novaQtd = parseInt(qtdSpan.textContent, 10) + (btn.dataset.acao === 'mais' ? 1 : -1);
          await fetch(API.carrinho, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ produto_id: Number(produtoId), quantidade: novaQtd }),
          });
          carregarCarrinho();
        });
      });
    });

    document.getElementById('checkout-form').style.display = 'block';
  } catch (e) {
    el.innerHTML = '<p class="vazio-msg">Não foi possível carregar o carrinho agora.</p>';
  }
}

document.getElementById('tipo_entrega').addEventListener('change', (e) => {
  document.getElementById('campo-endereco').style.display = e.target.value === 'entrega' ? 'block' : 'none';
});

document.getElementById('btn-finalizar').addEventListener('click', async () => {
  const tipo_entrega = document.getElementById('tipo_entrega').value;
  const endereco_entrega = document.getElementById('endereco').value.trim();
  const msgEl = document.getElementById('carrinho-msg');
  const btn = document.getElementById('btn-finalizar');

  if (tipo_entrega === 'entrega' && endereco_entrega === '') {
    msgEl.innerHTML = '<div class="msg erro">Informe o endereço de entrega.</div>';
    return;
  }

  btn.disabled = true;
  btn.textContent = 'Enviando...';

  try {
    const res = await fetch(API.pedidoCriar, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ tipo_entrega, endereco_entrega }),
    });
    const dados = await res.json();
    if (!dados.sucesso) throw new Error(dados.erro || 'Erro ao criar pedido.');

    msgEl.innerHTML = `<div class="msg sucesso">Pedido #${dados.pedido_id} criado! Agora é só pagar via PIX abaixo.</div>`;
    document.getElementById('checkout-form').style.display = 'none';

    const pixBox = document.getElementById('pix-box');
    pixBox.style.display = 'block';
    pixBox.innerHTML = `
      <div><b>Total do pedido:</b> R$ ${dados.total.toFixed(2).replace('.', ',')}</div>
      <div id="pix-qrcode"></div>
      <div style="font-size:0.8rem; color:var(--muted);">Escaneie com o app do seu banco, ou copie o código abaixo:</div>
      <div class="pix-chave" id="pix-chave-texto">${escapeHtml(dados.pix.payload)}</div>
      <button class="btn-copiar" id="btn-copiar-pix">Copiar código PIX</button>
      <p style="font-size:0.78rem; color:var(--muted); margin-top:10px;">
        Depois de pagar, acompanhe a confirmação em "Meus pedidos".
      </p>
    `;
    new QRCode(document.getElementById('pix-qrcode'), {
      text: dados.pix.payload,
      width: 180,
      height: 180,
    });
    document.getElementById('btn-copiar-pix').addEventListener('click', () => {
      navigator.clipboard.writeText(dados.pix.payload);
      document.getElementById('btn-copiar-pix').textContent = 'Copiado ✓';
    });

    carregarCarrinho();
  } catch (e) {
    msgEl.innerHTML = `<div class="msg erro">${escapeHtml(e.message)}</div>`;
  } finally {
    btn.disabled = false;
    btn.textContent = 'Finalizar pedido';
  }
});

// ---------- Pedidos ----------
async function carregarPedidos() {
  const el = document.getElementById('pedidos-conteudo');
  try {
    const res = await fetch(API.pedidoStatus);
    const dados = await res.json();
    if (!dados.sucesso) throw new Error();

    if (dados.pedidos.length === 0) {
      el.innerHTML = '<p class="vazio-msg">Você ainda não fez nenhum pedido.</p>';
      return;
    }

    el.innerHTML = dados.pedidos.map(p => `
      <div class="pedido-card">
        <div class="pedido-topo">
          <b>Pedido #${p.id}</b>
          <span class="badge ${p.status}">${STATUS_LABEL[p.status] || p.status}</span>
        </div>
        <div style="font-size:0.85rem; color:var(--muted); margin-top:6px;">
          ${p.tipo_entrega === 'entrega' ? 'Entrega' : 'Retirada na loja'} ·
          R$ ${p.total.toFixed(2).replace('.', ',')} ·
          ${new Date(p.criado_em).toLocaleDateString('pt-BR')}
        </div>
      </div>
    `).join('');
  } catch (e) {
    el.innerHTML = '<p class="vazio-msg">Não foi possível carregar seus pedidos agora.</p>';
  }
}

function escapeHtml(str) {
  const d = document.createElement('div');
  d.textContent = str ?? '';
  return d.innerHTML;
}

carregarCardapio();
atualizarContagemCarrinho();
</script>
</body>
</html>
