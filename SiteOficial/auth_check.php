<?php
/**
 * auth_check.php
 * Funções auxiliares de autenticação/autorização.
 *
 * IMPORTANTE: este arquivo assume que o seu login.php, ao autenticar
 * o usuário, guarda na sessão:
 *   $_SESSION['usuario_id'] = $row['id'];
 *   $_SESSION['tipo']       = $row['tipo']; // 'cliente' ou 'admin'
 *
 * Se o seu login usa outros nomes de variável de sessão, me manda o
 * login.php que eu ajusto essas duas funções pra bater certinho.
 */

if (session_status() === PHP_SESSION_NONE) {
    // Sessão guardada no banco (veja sessao_db.php) — a mesma usada pelo login.
    require_once __DIR__ . '/sessao_db.php';
}

// Evita que o navegador (ou qualquer cache no meio do caminho) guarde
// essa resposta — inclusive o cabeçalho Set-Cookie — e reaplique um
// cookie de sessão antigo em requisições futuras.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

header('Content-Type: application/json; charset=utf-8');

/**
 * Garante que existe um cliente logado.
 * Se não houver, interrompe a requisição com 401.
 */
function exigir_login_cliente(): int {
    if (empty($_SESSION['usuario_id'])) {
        http_response_code(401);
        echo json_encode(['erro' => 'Você precisa estar logado.']);
        exit;
    }
    return (int) $_SESSION['usuario_id'];
}

/**
 * Garante que existe um admin logado.
 * Se não houver, interrompe a requisição com 401/403.
 */
function exigir_login_admin(): int {
    if (empty($_SESSION['usuario_id'])) {
        http_response_code(401);
        echo json_encode(['erro' => 'Você precisa estar logado.']);
        exit;
    }
    if (($_SESSION['tipo'] ?? '') !== 'admin') {
        http_response_code(403);
        echo json_encode(['erro' => 'Acesso restrito ao administrador.']);
        exit;
    }
    return (int) $_SESSION['usuario_id'];
}

/**
 * Lê o corpo JSON da requisição (para métodos POST/PUT).
 */
function ler_json_body(): array {
    $dados = json_decode(file_get_contents('php://input'), true);
    return is_array($dados) ? $dados : [];
}
