<?php
require 'sessao_db.php';

// Só deixa entrar quem fez login como admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->query('SELECT nome, email, criado_em FROM usuarios WHERE tipo = "cliente" ORDER BY criado_em DESC LIMIT 20');
$clientes = $stmt->fetchAll();

$totalClientes = $pdo->query('SELECT COUNT(*) AS total FROM usuarios WHERE tipo = "cliente"')->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Painel admin — Beca's Cafetery</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700&family=Manrope:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="auth.css">
<style>
  body{ align-items:flex-start; padding-top:48px; padding-bottom:80px; }
  .auth-card{ max-width:920px; }
  .top-row{ display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; }
  .top-row a{ font-size:0.82rem; color:var(--rust); text-decoration:none; font-weight:600; }
  .stat{ font-family:var(--font-display); font-size:2rem; color:var(--rust); }
  table{ width:100%; border-collapse:collapse; margin-top:18px; font-size:0.86rem; }
  th, td{ text-align:left; padding:9px 6px; border-bottom:1px dashed var(--line); }
  th{ color:var(--espresso-2); }
  td{ color:var(--espresso); }

  .tabs{ display:flex; gap:8px; margin: 22px 0 20px; border-bottom:1px solid var(--line); flex-wrap:wrap; }
  .tab-btn{
    font-family: var(--font-display); font-weight:600; font-size:0.92rem;
    background:none; border:none; padding:10px 4px; cursor:pointer;
    color: var(--muted); border-bottom:2px solid transparent; margin-bottom:-1px;
  }
  .tab-btn.ativa{ color: var(--rust); border-bottom-color: var(--rust); }
  .tab-pane{ display:none; }
  .tab-pane.ativa{ display:block; }

  .barra-topo{ display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; }
  .btn-mini{
    border:none; background:var(--rust); color:var(--cream); font-family:var(--font-display);
    font-weight:600; font-size:0.8rem; padding:8px 14px; border-radius:999px; cursor:pointer;
  }
  .btn-mini:hover{ background:var(--espresso); }
  .btn-mini.secundario{ background:var(--muted); }
  .btn-mini.perigo{ background:#A9432E; }

  .card-produto-admin{
    border:1px solid var(--line); border-radius:14px; padding:12px 14px; margin-bottom:10px;
    background:var(--cream); display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;
  }
  .card-produto-admin .info b{ color:var(--espresso-2); }
  .card-produto-admin .info span{ display:block; font-size:0.78rem; color:var(--muted); }
  .acoes{ display:flex; gap:8px; }
  .indisponivel{ opacity:0.55; }

  .filtro-status{
    padding:8px 12px; border-radius:10px; border:1px solid var(--line);
    background:var(--cream); font-family:var(--font-body); font-size:0.85rem; margin-bottom:14px;
  }

  .pedido-admin-card{
    border:1px solid var(--line); border-radius:14px; padding:14px 16px; margin-bottom:12px; background:var(--cream);
  }
  .pedido-admin-topo{ display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; }
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
  select.status-select{
    padding:6px 10px; border-radius:8px; border:1px solid var(--line);
    background:var(--latte); font-size:0.8rem; font-family:var(--font-body);
  }

  /* Modal simples de produto */
  .overlay{
    display:none; position:fixed; inset:0; background:rgba(62,36,21,0.45);
    align-items:center; justify-content:center; padding:20px; z-index:50;
  }
  .overlay.ativo{ display:flex; }
  .modal{
    background:var(--latte); border-radius:18px; padding:26px; width:100%; max-width:420px;
    max-height:90vh; overflow-y:auto;
  }
  .modal h3{ font-family:var(--font-display); margin-top:0; }
  .campo{ margin-bottom:12px; }
  .campo label{ display:block; font-size:0.8rem; font-weight:600; margin-bottom:5px; }
  .campo input, .campo select, .campo textarea{
    width:100%; padding:9px 11px; border-radius:10px; border:1px solid var(--line);
    background:var(--cream); font-family:var(--font-body); font-size:0.88rem; color:var(--espresso-2);
  }
  .campo textarea{ min-height:60px; resize:vertical; }
  .campo-check{ display:flex; align-items:center; gap:8px; }
  .campo-check label{ margin:0; }
  .modal-acoes{ display:flex; gap:8px; margin-top:16px; }

  .msg{ padding:12px 14px; border-radius:12px; font-size:0.85rem; margin-bottom:14px; }
  .msg.erro{ background:#F4D9D2; color:#7A2A1B; }
  .msg.sucesso{ background:#E3ECC7; color:#4B5A1E; }

  /* Faturamento */
  .cards-resumo{ display:flex; gap:12px; flex-wrap:wrap; margin-bottom:6px; }
  .card-resumo{
    flex:1; min-width:150px; background:var(--cream); border:1px solid var(--line);
    border-radius:14px; padding:14px 16px;
  }
  .card-resumo small{
    display:block; font-size:0.72rem; color:var(--muted);
    text-transform:uppercase; letter-spacing:0.05em; margin-bottom:4px;
  }
  .card-resumo strong{ font-family:var(--font-display); font-size:1.4rem; color:var(--espresso-2); }
  .card-resumo.destaque{ flex:2; min-width:220px; }
  .card-resumo.destaque strong{ font-size:2rem; color:var(--rust); }
  .tabela-scroll{ overflow-x:auto; }
  .tabela-scroll table{ min-width:640px; }
  .sem-data{ color:var(--muted); font-style:italic; }
</style>
</head>
<body>
  <div class="auth-card">
    <div class="top-row">
      <span></span>
      <a href="logout.php">Sair</a>
    </div>
    <h1>Painel administrativo</h1>
    <p class="sub">Bem-vinda(o), <?= htmlspecialchars($_SESSION['nome']) ?>.</p>

    <div class="tabs">
      <button class="tab-btn ativa" data-tab="clientes">Clientes</button>
      <button class="tab-btn" data-tab="cardapio">Cardápio</button>
      <button class="tab-btn" data-tab="pedidos">Pedidos</button>
      <button class="tab-btn" data-tab="faturamento">Faturamento</button>
    </div>

    <!-- CLIENTES (como já existia) -->
    <div class="tab-pane ativa" id="pane-clientes">
      <p>Clientes cadastrados</p>
      <div class="stat"><?= (int) $totalClientes ?></div>

      <table>
        <thead>
          <tr><th>Nome</th><th>E-mail</th><th>Cadastro</th></tr>
        </thead>
        <tbody>
          <?php foreach ($clientes as $c): ?>
          <tr>
            <td><?= htmlspecialchars($c['nome']) ?></td>
            <td><?= htmlspecialchars($c['email']) ?></td>
            <td><?= htmlspecialchars(date('d/m/Y', strtotime($c['criado_em']))) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$clientes): ?>
          <tr><td colspan="3">Nenhum cliente cadastrado ainda.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- CARDÁPIO -->
    <div class="tab-pane" id="pane-cardapio">
      <div id="cardapio-msg"></div>
      <div class="barra-topo">
        <span></span>
        <button class="btn-mini" id="btn-novo-produto">+ Novo produto</button>
      </div>
      <div id="cardapio-admin-lista">Carregando...</div>
    </div>

    <!-- PEDIDOS -->
    <div class="tab-pane" id="pane-pedidos">
      <select class="filtro-status" id="filtro-status">
        <option value="">Todos os status</option>
        <option value="aguardando_pagamento">Aguardando pagamento</option>
        <option value="pago">Pago</option>
        <option value="preparando">Preparando</option>
        <option value="saiu_para_entrega">Saiu para entrega</option>
        <option value="concluido">Concluído</option>
        <option value="cancelado">Cancelado</option>
      </select>
      <div id="pedidos-admin-lista">Carregando...</div>
    </div>

    <!-- FATURAMENTO -->
    <div class="tab-pane" id="pane-faturamento">
      <div id="faturamento-conteudo">Carregando...</div>
    </div>
  </div>

  <!-- Modal de produto (criar/editar) -->
  <div class="overlay" id="overlay-produto">
    <div class="modal">
      <h3 id="modal-titulo">Novo produto</h3>
      <input type="hidden" id="produto-id">
      <div class="campo">
        <label>Nome</label>
        <input type="text" id="produto-nome">
      </div>
      <div class="campo">
        <label>Descrição</label>
        <textarea id="produto-descricao"></textarea>
      </div>
      <div class="campo">
        <label>Preço (R$)</label>
        <input type="number" step="0.01" min="0" id="produto-preco">
      </div>
      <div class="campo">
        <label>Categoria</label>
        <select id="produto-categoria">
          <option value="doce">Doce</option>
          <option value="bebida">Bebida</option>
        </select>
      </div>
      <div class="campo">
        <label>URL da foto (opcional)</label>
        <input type="text" id="produto-foto" placeholder="assets/produtos/brigadeiro.jpg">
      </div>
      <div class="campo campo-check">
        <input type="checkbox" id="produto-disponivel" checked>
        <label for="produto-disponivel">Disponível no cardápio</label>
      </div>
      <div class="modal-acoes">
        <button class="btn-mini" id="btn-salvar-produto">Salvar</button>
        <button class="btn-mini secundario" id="btn-cancelar-produto">Cancelar</button>
      </div>
    </div>
  </div>

<script>
const API = {
  produtos: 'admin/produtos_crud.php',
  pedidosListar: 'admin/pedidos_listar.php',
  pedidoStatus: 'admin/pedido_atualizar_status.php',
  faturamento: 'admin/faturamento.php',
};

const STATUS_LABEL = {
  aguardando_pagamento: 'Aguardando pagamento',
  pago: 'Pago',
  preparando: 'Preparando',
  saiu_para_entrega: 'Saiu para entrega',
  concluido: 'Concluído',
  cancelado: 'Cancelado',
};
const STATUS_ORDEM = Object.keys(STATUS_LABEL);

// ---------- Navegação por abas ----------
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('ativa'));
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('ativa'));
    btn.classList.add('ativa');
    document.getElementById('pane-' + btn.dataset.tab).classList.add('ativa');

    if (btn.dataset.tab === 'cardapio') carregarProdutosAdmin();
    if (btn.dataset.tab === 'pedidos') carregarPedidosAdmin();
    if (btn.dataset.tab === 'faturamento') carregarFaturamento();
  });
});

// ---------- Cardápio (admin) ----------
async function carregarProdutosAdmin() {
  const el = document.getElementById('cardapio-admin-lista');
  try {
    const res = await fetch(API.produtos);
    const dados = await res.json();
    if (!dados.sucesso) throw new Error();

    if (dados.produtos.length === 0) {
      el.innerHTML = '<p class="vazio-msg">Nenhum produto cadastrado ainda.</p>';
      return;
    }

    el.innerHTML = dados.produtos.map(p => `
      <div class="card-produto-admin ${p.disponivel ? '' : 'indisponivel'}">
        <div class="info">
          <b>${escapeHtml(p.nome)}</b>
          <span>${p.categoria === 'doce' ? 'Doce' : 'Bebida'} · R$ ${p.preco.toFixed(2).replace('.', ',')} ${p.disponivel ? '' : '· Indisponível'}</span>
        </div>
        <div class="acoes">
          <button class="btn-mini secundario" data-acao="editar" data-id="${p.id}">Editar</button>
          <button class="btn-mini perigo" data-acao="excluir" data-id="${p.id}">Excluir</button>
        </div>
      </div>
    `).join('');

    el.querySelectorAll('[data-acao="editar"]').forEach(btn => {
      const produto = dados.produtos.find(p => p.id == btn.dataset.id);
      btn.addEventListener('click', () => abrirModalProduto(produto));
    });
    el.querySelectorAll('[data-acao="excluir"]').forEach(btn => {
      btn.addEventListener('click', () => excluirProduto(btn.dataset.id));
    });
  } catch (e) {
    el.innerHTML = '<p class="vazio-msg">Não foi possível carregar o cardápio.</p>';
  }
}

function abrirModalProduto(produto) {
  document.getElementById('modal-titulo').textContent = produto ? 'Editar produto' : 'Novo produto';
  document.getElementById('produto-id').value = produto ? produto.id : '';
  document.getElementById('produto-nome').value = produto ? produto.nome : '';
  document.getElementById('produto-descricao').value = produto ? (produto.descricao || '') : '';
  document.getElementById('produto-preco').value = produto ? produto.preco : '';
  document.getElementById('produto-categoria').value = produto ? produto.categoria : 'doce';
  document.getElementById('produto-foto').value = produto ? (produto.foto || '') : '';
  document.getElementById('produto-disponivel').checked = produto ? !!produto.disponivel : true;
  document.getElementById('overlay-produto').classList.add('ativo');
}

document.getElementById('btn-novo-produto').addEventListener('click', () => abrirModalProduto(null));
document.getElementById('btn-cancelar-produto').addEventListener('click', () => {
  document.getElementById('overlay-produto').classList.remove('ativo');
});

document.getElementById('btn-salvar-produto').addEventListener('click', async () => {
  const id = document.getElementById('produto-id').value;
  const corpo = {
    nome: document.getElementById('produto-nome').value.trim(),
    descricao: document.getElementById('produto-descricao').value.trim(),
    preco: parseFloat(document.getElementById('produto-preco').value),
    categoria: document.getElementById('produto-categoria').value,
    foto: document.getElementById('produto-foto').value.trim(),
    disponivel: document.getElementById('produto-disponivel').checked ? 1 : 0,
  };

  const msgEl = document.getElementById('cardapio-msg');
  try {
    const url = id ? `${API.produtos}?id=${id}` : API.produtos;
    const res = await fetch(url, {
      method: id ? 'PUT' : 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(corpo),
    });
    const dados = await res.json();
    if (!dados.sucesso) throw new Error(dados.erro || 'Erro ao salvar.');

    document.getElementById('overlay-produto').classList.remove('ativo');
    msgEl.innerHTML = '<div class="msg sucesso">Produto salvo com sucesso.</div>';
    carregarProdutosAdmin();
  } catch (e) {
    msgEl.innerHTML = `<div class="msg erro">${escapeHtml(e.message)}</div>`;
  }
});

async function excluirProduto(id) {
  if (!confirm('Tem certeza que quer excluir este produto?')) return;
  const msgEl = document.getElementById('cardapio-msg');
  try {
    const res = await fetch(`${API.produtos}?id=${id}`, { method: 'DELETE' });
    const dados = await res.json();
    if (!dados.sucesso) throw new Error(dados.erro || 'Erro ao excluir.');
    msgEl.innerHTML = '<div class="msg sucesso">Produto excluído.</div>';
    carregarProdutosAdmin();
  } catch (e) {
    msgEl.innerHTML = `<div class="msg erro">${escapeHtml(e.message)}</div>`;
  }
}

// ---------- Pedidos (admin) ----------
async function carregarPedidosAdmin() {
  const el = document.getElementById('pedidos-admin-lista');
  const status = document.getElementById('filtro-status').value;
  try {
    const url = status ? `${API.pedidosListar}?status=${encodeURIComponent(status)}` : API.pedidosListar;
    const res = await fetch(url);
    const dados = await res.json();
    if (!dados.sucesso) throw new Error();

    if (dados.pedidos.length === 0) {
      el.innerHTML = '<p class="vazio-msg">Nenhum pedido encontrado.</p>';
      return;
    }

    el.innerHTML = dados.pedidos.map(p => `
      <div class="pedido-admin-card" data-id="${p.id}">
        <div class="pedido-admin-topo">
          <div>
            <b>Pedido #${p.id}</b> — ${escapeHtml(p.cliente_nome)}
            <div style="font-size:0.8rem; color:var(--muted);">
              ${p.tipo_entrega === 'entrega' ? 'Entrega' : 'Retirada'} · R$ ${p.total.toFixed(2).replace('.', ',')} ·
              ${new Date(p.criado_em).toLocaleString('pt-BR')}
            </div>
          </div>
          <div style="display:flex; align-items:center; gap:8px;">
            <span class="badge ${p.status}">${STATUS_LABEL[p.status] || p.status}</span>
            <select class="status-select" data-id="${p.id}">
              ${STATUS_ORDEM.map(s => `<option value="${s}" ${s === p.status ? 'selected' : ''}>${STATUS_LABEL[s]}</option>`).join('')}
            </select>
          </div>
        </div>
      </div>
    `).join('');

    el.querySelectorAll('.status-select').forEach(sel => {
      sel.addEventListener('change', async () => {
        try {
          const res = await fetch(API.pedidoStatus, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ pedido_id: Number(sel.dataset.id), status: sel.value }),
          });
          const dados = await res.json();
          if (!dados.sucesso) throw new Error(dados.erro || 'Erro ao atualizar status.');
          carregarPedidosAdmin();
        } catch (e) {
          alert(e.message);
        }
      });
    });
  } catch (e) {
    el.innerHTML = '<p class="vazio-msg">Não foi possível carregar os pedidos.</p>';
  }
}

document.getElementById('filtro-status').addEventListener('change', carregarPedidosAdmin);

// ---------- Faturamento (admin) ----------
function formatarMoeda(valor) {
  return Number(valor).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

function formatarDataHora(valor) {
  if (!valor) return '<span class="sem-data">não registrada</span>';
  const d = new Date(String(valor).replace(' ', 'T'));
  if (isNaN(d)) return escapeHtml(valor);
  return d.toLocaleString('pt-BR', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  });
}

async function carregarFaturamento() {
  const el = document.getElementById('faturamento-conteudo');
  try {
    const res = await fetch(API.faturamento);
    const dados = await res.json();
    if (!dados.sucesso) throw new Error(dados.erro || 'Erro ao carregar o faturamento.');

    const linhas = dados.itens.map(i => `
      <tr>
        <td>#${i.pedido_id}</td>
        <td>${escapeHtml(i.nome_produto)}</td>
        <td>${i.quantidade}</td>
        <td>${formatarMoeda(i.subtotal)}</td>
        <td>${escapeHtml(i.cliente_nome)}</td>
        <td>${formatarDataHora(i.criado_em)}</td>
        <td>${formatarDataHora(i.pago_em)}</td>
      </tr>
    `).join('');

    el.innerHTML = `
      <div class="cards-resumo">
        <div class="card-resumo destaque">
          <small>Total arrecadado até agora</small>
          <strong>${formatarMoeda(dados.total_arrecadado)}</strong>
        </div>
        <div class="card-resumo">
          <small>Pedidos pagos</small>
          <strong>${dados.qtd_pedidos}</strong>
        </div>
        <div class="card-resumo">
          <small>Itens vendidos</small>
          <strong>${dados.qtd_itens}</strong>
        </div>
      </div>

      <div class="tabela-scroll">
        <table>
          <thead>
            <tr>
              <th>Pedido</th><th>Produto</th><th>Qtd</th><th>Valor</th>
              <th>Cliente</th><th>Data do pedido</th><th>Data do pagamento</th>
            </tr>
          </thead>
          <tbody>
            ${linhas || '<tr><td colspan="7">Nenhuma venda paga ainda. Os pedidos aparecem aqui depois que você marca como "Pago" na aba Pedidos.</td></tr>'}
          </tbody>
        </table>
      </div>
    `;
  } catch (e) {
    el.innerHTML = `<div class="msg erro">${escapeHtml(e.message)}</div>`;
  }
}

function escapeHtml(str) {
  const d = document.createElement('div');
  d.textContent = str ?? '';
  return d.innerHTML;
}
</script>
</body>
</html>
