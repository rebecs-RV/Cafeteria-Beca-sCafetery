<?php
/**
 * admin/pedido_atualizar_status.php
 * POST — usado pelo admin para confirmar o pagamento PIX (manual) ou
 * avançar o pedido pelas etapas seguintes.
 *
 * body: { "pedido_id": 12, "status": "pago" }
 *
 * Ajuste a lista de STATUS_VALIDOS abaixo se o enum real da coluna
 * `status` na tabela `pedidos` for diferente do que assumimos.
 */

require __DIR__ . '/../config.php';
require __DIR__ . '/../auth_check.php';

exigir_login_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido.']);
    exit;
}

// Assumido a partir do enum visto no phpMyAdmin — confirme/ajuste se necessário.
const STATUS_VALIDOS = [
    'aguardando_pagamento',
    'pago',
    'preparando',
    'saiu_para_entrega',
    'concluido',
    'cancelado',
];

$dados     = ler_json_body();
$pedido_id = (int) ($dados['pedido_id'] ?? 0);
$status    = $dados['status'] ?? '';

if ($pedido_id <= 0) {
    http_response_code(422);
    echo json_encode(['erro' => 'pedido_id inválido.']);
    exit;
}

if (!in_array($status, STATUS_VALIDOS, true)) {
    http_response_code(422);
    echo json_encode(['erro' => 'Status inválido. Use um de: ' . implode(', ', STATUS_VALIDOS)]);
    exit;
}

try {
    // Registra a data do pagamento (pago_em) pro relatório de faturamento:
    //  - status "pago" ou posteriores: grava a data/hora só na 1ª vez
    //    (COALESCE mantém a data original se já existir)
    //  - voltou pra "aguardando_pagamento": limpa a data
    //  - "cancelado": mantém o que já estava
    if (in_array($status, ['pago', 'preparando', 'saiu_para_entrega', 'concluido'], true)) {
        $sql = "UPDATE pedidos SET status = ?, pago_em = COALESCE(pago_em, NOW()) WHERE id = ?";
    } elseif ($status === 'aguardando_pagamento') {
        $sql = "UPDATE pedidos SET status = ?, pago_em = NULL WHERE id = ?";
    } else {
        $sql = "UPDATE pedidos SET status = ? WHERE id = ?";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$status, $pedido_id]);

    if ($stmt->rowCount() === 0) {
        // Pode ser porque o pedido não existe OU porque já estava nesse status
        $check = $pdo->prepare("SELECT id FROM pedidos WHERE id = ?");
        $check->execute([$pedido_id]);
        if (!$check->fetch()) {
            http_response_code(404);
            echo json_encode(['erro' => 'Pedido não encontrado.']);
            exit;
        }
    }

    echo json_encode(['sucesso' => true, 'pedido_id' => $pedido_id, 'novo_status' => $status]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao atualizar status.']);
}
