<?php
require 'sessao_db.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($email === '' || $senha === '') {
        $erro = 'Preencha e-mail e senha.';
    } else {
        $stmt = $pdo->prepare('SELECT id, nome, senha, tipo FROM usuarios WHERE email = ?');
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            // Login certo: guarda os dados na sessão
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['nome']       = $usuario['nome'];
            $_SESSION['tipo']       = $usuario['tipo'];
            $_SESSION['email']      = $email;

            if ($usuario['tipo'] === 'admin') {
                header('Location: painel-admin.php');
            } else {
                header('Location: painel-cliente.php');
            }
            exit;
        } else {
            $erro = 'E-mail ou senha incorretos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Entrar — Beca's Cafetery</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700&family=Manrope:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="auth.css">
</head>
<body>
  <div class="auth-card">
    <h1>Bem-vindo de volta</h1>
    <p class="sub">Entre com seu e-mail e senha.</p>

    <?php if ($erro): ?>
      <div class="msg erro"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <form method="post" novalidate>
      <div class="field">
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="senha">Senha</label>
        <input type="password" id="senha" name="senha" required>
      </div>
      <button type="submit" class="btn">Entrar</button>
    </form>

    <p class="auth-links">Ainda não tem conta? <a href="cadastro.php">Cadastre-se</a></p>
  </div>
</body>
</html>
