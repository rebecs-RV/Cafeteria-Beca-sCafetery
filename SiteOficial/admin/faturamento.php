<?php
/**
 * admin/faturamento.php
 * GET — relatório de faturamento (só admin).
 *
 * Considera "vendido/arrecadado" apenas os pedidos que já foram pagos:
 * pago, preparando, saiu_para_entrega e concluido.
 * Pedidos "aguardando_pagamento" e "cancelado" ficam de fora.
 *
 * Resposta:
 * {
 *   "sucesso": true,
 *   "total_arrecadado": 123.50,
 *   "qtd_pedidos": 7,
 *   "qtd_itens": 15,
 *   "itens": [
 *     { "pedido_id":3, "nome_produto":"...", "quantidade":2, "preco_unitario":12.5,
 *       "subtotal":25, "cliente_nome":"...", "criado_em":"...", "pago_em":"..." },
 *     ...
 *   ]
 * }
 */

require __DIR__ . '/../config.php';
require __DIR__ . '/../auth_check.php';

exigir_login_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido.']);
    exit;
}

const STATUS_PAGOS = ['pago', 'preparando', 'saiu_para_entrega', 'concluido'];

try {
    $marcadores = implode(',', array_fill(0, count(STATUS_PAGOS), '?'));

    // Uma linha por produto vendido
    $stmt = $pdo->prepare(
        "SELECT pi.pedido_id, pi.nome_produto, pi.quantidade, pi.preco_unitario,
                u.nome AS cliente_nome, ped.criado_em, ped.pago_em
         FROM pedido_itens pi
         JOIN pedidos ped ON ped.id = pi.pedido_id
         JOIN usuarios u  ON u.id  = ped.usuario_id
         WHERE ped.status IN ($marcadores)
         ORDER BY COALESCE(ped.pago_em, ped.criado_em) DESC, pi.pedido_id DESC, pi.id"
    );
    $stmt->execute(STATUS_PAGOS);
    $itens = $stmt->fetchAll();

    $qtdItens = 0;
    foreach ($itens as &$item) {
        $item['pedido_id']      = (int) $item['pedido_id'];
        $item['quantidade']     = (int) $item['quantidade'];
        $item['preco_unitario'] = (float) $item['preco_unitario'];
        $item['subtotal']       = round($item['preco_unitario'] * $item['quantidade'], 2);
        $qtdItens += $item['quantidade'];
    }
    unset($item);

    // Total arrecadado: soma dos PEDIDOS pagos (consulta separada pra não
    // contar o mesmo pedido várias vezes, uma por produto).
    $stmtTotal = $pdo->prepare(
        "SELECT COUNT(*) AS qtd_pedidos, COALESCE(SUM(total), 0) AS total
         FROM pedidos
         WHERE status IN ($marcadores)"
    );
    $stmtTotal->execute(STATUS_PAGOS);
    $totais = $stmtTotal->fetch();

    echo json_encode([
        'sucesso'          => true,
        'total_arrecadado' => round((float) $totais['total'], 2),
        'qtd_pedidos'      => (int) $totais['qtd_pedidos'],
        'qtd_itens'        => $qtdItens,
        'itens'            => $itens,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'erro' => 'Não foi possível gerar o faturamento. Você já rodou o arquivo schema-faturamento.sql no phpMyAdmin?',
    ]);
}
