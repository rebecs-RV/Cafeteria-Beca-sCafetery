<?php
/**
 * carrinho.php
 * Gerencia o carrinho (tabela carrinho_itens) do cliente logado.
 *
 * GET    carrinho.php              -> lista os itens do carrinho + total
 * POST   carrinho.php              -> adiciona um produto (ou soma qtd se já existir)
 *        body: { "produto_id": 3, "quantidade": 1 }
 * PUT    carrinho.php              -> atualiza a quantidade de um item
 *        body: { "produto_id": 3, "quantidade": 2 }
 * DELETE carrinho.php?produto_id=3 -> remove um item do carrinho
 */

require __DIR__ . '/config.php';
require __DIR__ . '/auth_check.php';

$usuario_id = exigir_login_cliente();
$metodo     = $_SERVER['REQUEST_METHOD'];

try {
    if ($metodo === 'GET') {
        listar_carrinho($pdo, $usuario_id);
    } elseif ($metodo === 'POST') {
        adicionar_item($pdo, $usuario_id);
    } elseif ($metodo === 'PUT') {
        atualizar_quantidade($pdo, $usuario_id);
    } elseif ($metodo === 'DELETE') {
        remover_item($pdo, $usuario_id);
    } else {
        http_response_code(405);
        echo json_encode(['erro' => 'Método não permitido.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro no servidor ao processar o carrinho.']);
}

// ---------------------------------------------------------------------

function listar_carrinho(PDO $pdo, int $usuario_id): void {
    $stmt = $pdo->prepare(
        "SELECT ci.produto_id, ci.quantidade,
                p.nome, p.preco, p.foto, p.disponivel
         FROM carrinho_itens ci
         JOIN produtos p ON p.id = ci.produto_id
         WHERE ci.usuario_id = ?
         ORDER BY ci.adicionado_em"
    );
    $stmt->execute([$usuario_id]);
    $itens = $stmt->fetchAll();

    $total = 0;
    foreach ($itens as &$item) {
        $item['preco']      = (float) $item['preco'];
        $item['quantidade'] = (int) $item['quantidade'];
        $item['subtotal']   = round($item['preco'] * $item['quantidade'], 2);
        $total += $item['subtotal'];
    }

    echo json_encode([
        'sucesso' => true,
        'itens'   => $itens,
        'total'   => round($total, 2),
    ]);
}

function adicionar_item(PDO $pdo, int $usuario_id): void {
    $dados      = ler_json_body();
    $produto_id = (int) ($dados['produto_id'] ?? 0);
    $quantidade = max(1, (int) ($dados['quantidade'] ?? 1));

    if ($produto_id <= 0) {
        http_response_code(422);
        echo json_encode(['erro' => 'produto_id inválido.']);
        return;
    }

    // Confere se o produto existe e está disponível
    $check = $pdo->prepare("SELECT id FROM produtos WHERE id = ? AND disponivel = 1");
    $check->execute([$produto_id]);
    if (!$check->fetch()) {
        http_response_code(404);
        echo json_encode(['erro' => 'Produto não encontrado ou indisponível.']);
        return;
    }

    // Graças ao índice único (usuario_id, produto_id), isso soma a
    // quantidade se o item já estiver no carrinho.
    $stmt = $pdo->prepare(
        "INSERT INTO carrinho_itens (usuario_id, produto_id, quantidade)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE quantidade = quantidade + VALUES(quantidade)"
    );
    $stmt->execute([$usuario_id, $produto_id, $quantidade]);

    echo json_encode(['sucesso' => true, 'mensagem' => 'Item adicionado ao carrinho.']);
}

function atualizar_quantidade(PDO $pdo, int $usuario_id): void {
    $dados      = ler_json_body();
    $produto_id = (int) ($dados['produto_id'] ?? 0);
    $quantidade = (int) ($dados['quantidade'] ?? 0);

    if ($produto_id <= 0) {
        http_response_code(422);
        echo json_encode(['erro' => 'produto_id inválido.']);
        return;
    }

    if ($quantidade <= 0) {
        // quantidade zero ou negativa = remove o item
        $stmt = $pdo->prepare("DELETE FROM carrinho_itens WHERE usuario_id = ? AND produto_id = ?");
        $stmt->execute([$usuario_id, $produto_id]);
        echo json_encode(['sucesso' => true, 'mensagem' => 'Item removido.']);
        return;
    }

    $stmt = $pdo->prepare(
        "UPDATE carrinho_itens SET quantidade = ? WHERE usuario_id = ? AND produto_id = ?"
    );
    $stmt->execute([$quantidade, $usuario_id, $produto_id]);

    echo json_encode(['sucesso' => true, 'mensagem' => 'Quantidade atualizada.']);
}

function remover_item(PDO $pdo, int $usuario_id): void {
    $produto_id = (int) ($_GET['produto_id'] ?? 0);

    if ($produto_id <= 0) {
        http_response_code(422);
        echo json_encode(['erro' => 'produto_id inválido.']);
        return;
    }

    $stmt = $pdo->prepare("DELETE FROM carrinho_itens WHERE usuario_id = ? AND produto_id = ?");
    $stmt->execute([$usuario_id, $produto_id]);

    echo json_encode(['sucesso' => true, 'mensagem' => 'Item removido.']);
}
