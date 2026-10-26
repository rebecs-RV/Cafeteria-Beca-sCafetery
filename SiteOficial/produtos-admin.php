<?php
require 'sessao_db.php';
require 'helpers.php';

exigir_login('admin');

$erro = '';
$sucesso = '';

$pastaFotos = __DIR__ . '/produtos-fotos/';
if (!is_dir($pastaFotos)) {
    mkdir($pastaFotos, 0755, true);
}

/**
 * Trata o upload da foto (se enviada) e devolve o caminho relativo salvo,
 * ou null se nenhuma foto nova foi enviada.
 */
function tratar_upload_foto(): ?string
{
    global $erro, $pastaFotos;

    if (empty($_FILES['foto']['name'])) {
        return null;
    }

    $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];
    $extensao = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));

    if (!in_array($extensao, $extensoesPermitidas, true)) {
        $erro = 'Formato de imagem não aceito. Use JPG, PNG ou WEBP.';
        return null;
    }

    if ($_FILES['foto']['size'] > 3 * 1024 * 1024) {
        $erro = 'A imagem precisa ter no máximo 3 MB.';
        return null;
    }

    $nomeArquivo = 'produtos-fotos/' . uniqid('produto_') . '.' . $extensao;
    move_uploaded_file($_FILES['foto']['tmp_name'], __DIR__ . '/' . $nomeArquivo);

    return $nomeArquivo;
}

// Criar ou editar produto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'salvar') {
    $id         = (int) ($_POST['id'] ?? 0);
    $nome       = trim($_POST['nome'] ?? '');
    $descricao  = trim($_POST['descricao'] ?? '');
    $preco      = str_replace(',', '.', $_POST['preco'] ?? '0');
    $categoria  = ($_POST['categoria'] ?? 'doce') === 'bebida' ? 'bebida' : 'doce';
    $disponivel = isset($_POST['disponivel']) ? 1 : 0;

    if ($nome === '' || !is_numeric($preco) || (float) $preco <= 0) {
        $erro = 'Preencha nome e um preço válido.';
    } else {
        $novaFoto = tratar_upload_foto();

        // Alternativa: se a pessoa já subiu a foto pelo File Manager,
        // ela pode digitar só o nome do arquivo aqui em vez de usar o upload.
        $nomeArquivoManual = trim($_POST['foto_nome_arquivo'] ?? '');
        if (!$novaFoto && $nomeArquivoManual !== '') {
            $caminhoTeste = 'produtos-fotos/' . basename($nomeArquivoManual);
            if (file_exists(__DIR__ . '/' . $caminhoTeste)) {
                $novaFoto = $caminhoTeste;
            } else {
                $erro = 'Não encontrei o arquivo "' . htmlspecialchars($nomeArquivoManual) . '" dentro da pasta produtos-fotos. Confira se o nome está exatamente igual (maiúsculas/minúsculas contam) e se o upload pelo File Manager já terminou.';
            }
        }

        if ($erro === '') {
            if ($id > 0) {
                if ($novaFoto) {
                    $stmt = $pdo->prepare(
                        'UPDATE produtos SET nome=?, descricao=?, preco=?, categoria=?, disponivel=?, foto=? WHERE id=?'
                    );
                    $stmt->execute([$nome, $descricao, $preco, $categoria, $disponivel, $novaFoto, $id]);
                } else {
                    $stmt = $pdo->prepare(
                        'UPDATE produtos SET nome=?, descricao=?, preco=?, categoria=?, disponivel=? WHERE id=?'
                    );
                    $stmt->execute([$nome, $descricao, $preco, $categoria, $disponivel, $id]);
                }
                $sucesso = 'Produto atualizado!';
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO produtos (nome, descricao, preco, categoria, disponivel, foto) VALUES (?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([$nome, $descricao, $preco, $categoria, $disponivel, $novaFoto]);
                $sucesso = 'Produto cadastrado!';
            }
        }
    }
}

// Excluir produto
if (isset($_GET['excluir'])) {
    $stmt = $pdo->prepare('DELETE FROM produtos WHERE id = ?');
    $stmt->execute([(int) $_GET['excluir']]);
    header('Location: produtos-admin.php');
    exit;
}

// Produto sendo editado agora (se veio ?editar=)
$produtoEditando = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare('SELECT * FROM produtos WHERE id = ?');
    $stmt->execute([(int) $_GET['editar']]);
    $produtoEditando = $stmt->fetch();
}

$produtos = $pdo->query('SELECT * FROM produtos ORDER BY categoria, nome')->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gerenciar cardápio — Beca's Cafetery</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700&family=Manrope:wght@400;500;600&family=Space+Mono:wght@700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="loja.css">
</head>
<body>
  <div class="topbar">
    <span class="brand">Beca's Cafetery · Admin</span>
    <nav>
      <a href="painel-admin.php">Painel</a>
      <a href="pedidos-admin.php">Pedidos</a>
      <a href="logout.php">Sair</a>
    </nav>
  </div>

  <div class="page">
    <h1><?= $produtoEditando ? 'Editar produto' : 'Novo produto' ?></h1>

    <?php if ($erro): ?><div class="msg erro"><?= htmlspecialchars($erro) ?></div><?php endif; ?>
    <?php if ($sucesso): ?><div class="msg sucesso"><?= htmlspecialchars($sucesso) ?></div><?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="resumo" style="margin-top:0;">
      <input type="hidden" name="acao" value="salvar">
      <input type="hidden" name="id" value="<?= $produtoEditando['id'] ?? 0 ?>">

      <div class="field">
        <label>Nome</label>
        <input type="text" name="nome" required value="<?= htmlspecialchars($produtoEditando['nome'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Descrição</label>
        <textarea name="descricao" rows="2"><?= htmlspecialchars($produtoEditando['descricao'] ?? '') ?></textarea>
      </div>
      <div class="field">
        <label>Preço (R$)</label>
        <input type="text" name="preco" required value="<?= $produtoEditando['preco'] ?? '' ?>" placeholder="Ex: 12.50">
      </div>
      <div class="field">
        <label>Categoria</label>
        <select name="categoria">
          <option value="doce" <?= (($produtoEditando['categoria'] ?? '') === 'doce') ? 'selected' : '' ?>>Doce</option>
          <option value="bebida" <?= (($produtoEditando['categoria'] ?? '') === 'bebida') ? 'selected' : '' ?>>Bebida</option>
        </select>
      </div>
      <div class="field">
        <label>Foto <?= $produtoEditando ? '(deixe em branco pra manter a atual)' : '' ?></label>
        <input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp">
      </div>
      <div class="field">
        <label>
          Ou: nome do arquivo já enviado pelo File Manager
          <span style="font-weight:400; font-size:0.78rem; color:var(--muted);">(pasta produtos-fotos/)</span>
        </label>
        <input type="text" name="foto_nome_arquivo" placeholder="ex: bolo-vulcao.jpg">
        <p style="margin:6px 0 0; font-size:0.78rem; color:var(--muted);">
          Se o upload direto acima não funcionar, suba a foto pelo File Manager
          na pasta <strong>produtos-fotos</strong> e escreva o nome do arquivo aqui.
        </p>
      </div>
      <div class="field">
        <label><input type="checkbox" name="disponivel" <?= (!$produtoEditando || $produtoEditando['disponivel']) ? 'checked' : '' ?> style="width:auto;"> Disponível no cardápio</label>
      </div>

      <button type="submit" class="btn btn-primary btn-full"><?= $produtoEditando ? 'Salvar alterações' : 'Cadastrar produto' ?></button>
      <?php if ($produtoEditando): ?>
        <a href="produtos-admin.php" class="btn btn-ghost btn-full" style="margin-top:8px;">Cancelar edição</a>
      <?php endif; ?>
    </form>

    <h2 class="categoria-titulo">Produtos cadastrados</h2>
    <table class="admin-table">
      <thead>
        <tr><th>Foto</th><th>Nome</th><th>Categoria</th><th>Preço</th><th>Disponível</th><th>Ações</th></tr>
      </thead>
      <tbody>
        <?php foreach ($produtos as $p): ?>
        <tr>
          <td><?= foto_produto_html($p['foto'], $p['nome']) ?></td>
          <td><?= htmlspecialchars($p['nome']) ?></td>
          <td><?= $p['categoria'] === 'doce' ? 'Doce' : 'Bebida' ?></td>
          <td><?= formatar_preco($p['preco']) ?></td>
          <td><?= $p['disponivel'] ? 'Sim' : 'Não' ?></td>
          <td>
            <a href="?editar=<?= (int) $p['id'] ?>">editar</a> ·
            <a href="?excluir=<?= (int) $p['id'] ?>" onclick="return confirm('Excluir este produto?')">excluir</a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$produtos): ?>
        <tr><td colspan="6">Nenhum produto cadastrado ainda.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</body>
</html>
