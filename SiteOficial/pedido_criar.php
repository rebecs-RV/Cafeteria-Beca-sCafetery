<?php
/**
 * pedido_criar.php
 * POST — transforma o carrinho atual do cliente logado em um pedido.
 *
 * body: {
 *   "tipo_entrega": "retirada" | "entrega",
 *   "endereco_entrega": "Rua X, 123"   // obrigatório só se tipo_entrega = "entrega"
 * }
 *
 * O pedido nasce com status = 'aguardando_pagamento'.
 * O carrinho é esvaziado após a criação do pedido.
 */

require __DIR__ . '/config.php';
require __DIR__ . '/auth_check.php';
require __DIR__ . '/pix_config.php';
require __DIR__ . '/pix_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido.']);
    exit;
}

$usuario_id = exigir_login_cliente();
$dados      = ler_json_body();

$tipo_entrega     = $dados['tipo_entrega'] ?? 'retirada';
$endereco_entrega = trim($dados['endereco_entrega'] ?? '');

if (!in_array($tipo_entrega, ['retirada', 'entrega'], true)) {
    http_response_code(422);
    echo json_encode(['erro' => "tipo_entrega deve ser 'retirada' ou 'entrega'."]);
    exit;
}

if ($tipo_entrega === 'entrega' && $endereco_entrega === '') {
    http_response_code(422);
    echo json_encode(['erro' => 'Endereço de entrega é obrigatório para esse tipo de pedido.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Busca os itens do carrinho, travando os preços atuais
    $stmt = $pdo->prepare(
        "SELECT ci.produto_id, ci.quantidade, p.nome, p.preco
         FROM carrinho_itens ci
         JOIN produtos p ON p.id = ci.produto_id
         WHERE ci.usuario_id = ? AND p.disponivel = 1
         FOR UPDATE"
    );
    $stmt->execute([$usuario_id]);
    $itens = $stmt->fetchAll();

    if (empty($itens)) {
        $pdo->rollBack();
        http_response_code(422);
        echo json_encode(['erro' => 'Seu carrinho está vazio.']);
        exit;
    }

    // 2. Calcula o total
    $total = 0;
    foreach ($itens as $item) {
        $total += $item['preco'] * $item['quantidade'];
    }

    // 3. Cria o pedido
    $stmt = $pdo->prepare(
        "INSERT INTO pedidos (usuario_id, tipo_entrega, endereco_entrega, total, status)
         VALUES (?, ?, ?, ?, 'aguardando_pagamento')"
    );
    $stmt->execute([
        $usuario_id,
        $tipo_entrega,
        $tipo_entrega === 'entrega' ? $endereco_entrega : null,
        round($total, 2),
    ]);
    $pedido_id = (int) $pdo->lastInsertId();

    // 4. Copia os itens do carrinho para pedido_itens (guarda nome/preço no momento da compra)
    $stmtItem = $pdo->prepare(
        "INSERT INTO pedido_itens (pedido_id, produto_id, nome_produto, quantidade, preco_unitario)
         VALUES (?, ?, ?, ?, ?)"
    );
    foreach ($itens as $item) {
        $stmtItem->execute([
            $pedido_id,
            $item['produto_id'],
            $item['nome'],
            $item['quantidade'],
            $item['preco'],
        ]);
    }

    // 5. Esvazia o carrinho
    $pdo->prepare("DELETE FROM carrinho_itens WHERE usuario_id = ?")->execute([$usuario_id]);

    $pdo->commit();

    $payloadPix = gerarPayloadPix(
        PIX_CHAVE,
        PIX_NOME_RECEBEDOR,
        PIX_CIDADE,
        $total,
        'PED' . $pedido_id
    );

    echo json_encode([
        'sucesso'   => true,
        'pedido_id' => $pedido_id,
        'total'     => round($total, 2),
        'status'    => 'aguardando_pagamento',
        'pix'       => [
            'chave'    => PIX_CHAVE,
            'nome'     => PIX_NOME_RECEBEDOR,
            'cidade'   => PIX_CIDADE,
            'payload'  => $payloadPix,
            'mensagem' => 'Faça o PIX no valor total e aguarde a confirmação do pagamento.',
        ],
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao criar o pedido.']);
}
