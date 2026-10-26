<?php
/**
 * admin/pedidos_listar.php
 * GET — lista todos os pedidos (para a tela de gestão de pedidos no admin).
 *
 * Filtros opcionais via query string:
 *   ?status=aguardando_pagamento
 *   ?id=12   -> retorna 1 pedido com os itens
 */

require __DIR__ . '/../config.php';
require __DIR__ . '/../auth_check.php';

exigir_login_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido.']);
    exit;
}

try {
    $pedido_id = isset($_GET['id']) ? (int) $_GET['id'] : null;

    if ($pedido_id) {
        $stmt = $pdo->prepare(
            "SELECT ped.*, u.nome AS cliente_nome, u.email AS cliente_email
             FROM pedidos ped
             JOIN usuarios u ON u.id = ped.usuario_id
             WHERE ped.id = ?"
        );
        $stmt->execute([$pedido_id]);
        $pedido = $stmt->fetch();

        if (!$pedido) {
            http_response_code(404);
            echo json_encode(['erro' => 'Pedido não encontrado.']);
            exit;
        }

        $stmtItens = $pdo->prepare("SELECT * FROM pedido_itens WHERE pedido_id = ?");
        $stmtItens->execute([$pedido_id]);
        $pedido['itens'] = $stmtItens->fetchAll();
        $pedido['total'] = (float) $pedido['total'];

        echo json_encode(['sucesso' => true, 'pedido' => $pedido]);
        exit;
    }

    $status = $_GET['status'] ?? null;

    $sql = "SELECT ped.id, ped.usuario_id, u.nome AS cliente_nome,
                   ped.tipo_entrega, ped.total, ped.status, ped.criado_em
            FROM pedidos ped
            JOIN usuarios u ON u.id = ped.usuario_id";
    $params = [];

    if ($status) {
        $sql .= " WHERE ped.status = ?";
        $params[] = $status;
    }
    $sql .= " ORDER BY ped.criado_em DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $pedidos = $stmt->fetchAll();

    foreach ($pedidos as &$p) {
        $p['total'] = (float) $p['total'];
    }

    echo json_encode(['sucesso' => true, 'pedidos' => $pedidos]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao buscar pedidos.']);
}
