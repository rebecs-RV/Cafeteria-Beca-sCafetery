<?php
/**
 * admin/produtos_crud.php
 * CRUD de produtos — acesso restrito a usuários com tipo = 'admin'.
 *
 * GET    produtos_crud.php          -> lista todos os produtos (inclusive indisponíveis)
 * POST   produtos_crud.php          -> cria um produto
 *        body: { nome, descricao, preco, categoria, foto, disponivel }
 * PUT    produtos_crud.php?id=5     -> atualiza um produto
 * DELETE produtos_crud.php?id=5     -> remove um produto
 */

require __DIR__ . '/../config.php';
require __DIR__ . '/../auth_check.php';

exigir_login_admin();

$metodo = $_SERVER['REQUEST_METHOD'];

try {
    if ($metodo === 'GET') {
        listar($pdo);
    } elseif ($metodo === 'POST') {
        criar($pdo);
    } elseif ($metodo === 'PUT') {
        atualizar($pdo);
    } elseif ($metodo === 'DELETE') {
        remover($pdo);
    } else {
        http_response_code(405);
        echo json_encode(['erro' => 'Método não permitido.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro no servidor.', 'detalhe' => $e->getMessage()]);
}

// ---------------------------------------------------------------------

function listar(PDO $pdo): void {
    $stmt = $pdo->query("SELECT * FROM produtos ORDER BY categoria, nome");
    $produtos = $stmt->fetchAll();
    foreach ($produtos as &$p) {
        $p['preco']      = (float) $p['preco'];
        $p['disponivel'] = (int) $p['disponivel'];
    }
    echo json_encode(['sucesso' => true, 'produtos' => $produtos]);
}

function criar(PDO $pdo): void {
    $d = ler_json_body();

    [$ok, $erro] = validar_produto($d);
    if (!$ok) {
        http_response_code(422);
        echo json_encode(['erro' => $erro]);
        return;
    }

    $stmt = $pdo->prepare(
        "INSERT INTO produtos (nome, descricao, preco, categoria, foto, disponivel)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $d['nome'],
        $d['descricao'] ?? null,
        $d['preco'],
        $d['categoria'],
        $d['foto'] ?? null,
        isset($d['disponivel']) ? (int) $d['disponivel'] : 1,
    ]);

    echo json_encode(['sucesso' => true, 'id' => (int) $pdo->lastInsertId()]);
}

function atualizar(PDO $pdo): void {
    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(422);
        echo json_encode(['erro' => 'id inválido.']);
        return;
    }

    $d = ler_json_body();
    [$ok, $erro] = validar_produto($d);
    if (!$ok) {
        http_response_code(422);
        echo json_encode(['erro' => $erro]);
        return;
    }

    $stmt = $pdo->prepare(
        "UPDATE produtos
         SET nome = ?, descricao = ?, preco = ?, categoria = ?, foto = ?, disponivel = ?
         WHERE id = ?"
    );
    $stmt->execute([
        $d['nome'],
        $d['descricao'] ?? null,
        $d['preco'],
        $d['categoria'],
        $d['foto'] ?? null,
        isset($d['disponivel']) ? (int) $d['disponivel'] : 1,
        $id,
    ]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['erro' => 'Produto não encontrado.']);
        return;
    }

    echo json_encode(['sucesso' => true]);
}

function remover(PDO $pdo): void {
    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(422);
        echo json_encode(['erro' => 'id inválido.']);
        return;
    }

    $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['erro' => 'Produto não encontrado.']);
        return;
    }

    echo json_encode(['sucesso' => true]);
}

/**
 * Valida os campos de um produto.
 * Categorias aceitas hoje: 'doce', 'bebida' (bate com o enum da tabela).
 * Se você adicionar mais categorias no banco, atualize a lista abaixo também.
 */
function validar_produto(array $d): array {
    $categoriasValidas = ['doce', 'bebida'];

    if (empty($d['nome']) || trim($d['nome']) === '') {
        return [false, 'Nome é obrigatório.'];
    }
    if (!isset($d['preco']) || !is_numeric($d['preco']) || $d['preco'] < 0) {
        return [false, 'Preço inválido.'];
    }
    if (empty($d['categoria']) || !in_array($d['categoria'], $categoriasValidas, true)) {
        return [false, 'Categoria inválida. Use: ' . implode(', ', $categoriasValidas)];
    }
    return [true, null];
}
