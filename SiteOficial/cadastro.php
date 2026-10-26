<?php
require 'config.php';

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome           = trim($_POST['nome'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $senha          = $_POST['senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';

    if ($nome === '' || $email === '' || $senha === '') {
        $erro = 'Preencha todos os campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Digite um e-mail válido.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha precisa ter pelo menos 6 caracteres.';
    } elseif ($senha !== $confirmarSenha) {
        $erro = 'As senhas não coincidem.';
    } else {
        // Verifica se o e-mail já está cadastrado
        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $erro = 'Esse e-mail já está cadastrado. Tente fazer login.';
        } else {
            $hash = password_hash($senha, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare(
                'INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, "cliente")'
            );
            $stmt->execute([$nome, $email, $hash]);

            $sucesso = 'Cadastro feito com sucesso! Você já pode fazer login.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Criar conta — Beca's Cafetery</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700&family=Manrope:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="auth.css">
</head>
<body>
  <div class="auth-card">
    <h1>Criar conta</h1>
    <p class="sub">Cadastre-se pra acompanhar seus pedidos na Beca's Cafetery.</p>

    <?php if ($erro): ?>
      <div class="msg erro"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <?php if ($sucesso): ?>
      <div class="msg sucesso"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <form method="post" novalidate>
      <div class="field">
        <label for="nome">Nome</label>
        <input type="text" id="nome" name="nome" required value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="senha">Senha</label>
        <input type="password" id="senha" name="senha" required minlength="6">
      </div>
      <div class="field">
        <label for="confirmar_senha">Confirmar senha</label>
        <input type="password" id="confirmar_senha" name="confirmar_senha" required minlength="6">
      </div>
      <button type="submit" class="btn">Criar conta</button>
    </form>

    <p class="auth-links">Já tem conta? <a href="login.php">Fazer login</a></p>
  </div>
</body>
</html>
