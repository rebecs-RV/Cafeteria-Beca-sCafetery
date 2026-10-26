<?php
/**
 * pedido_status.php
 * GET — lista os pedidos do cliente logado (mais recentes primeiro).
 * Opcional: ?id=5 para ver só um pedido específico, com os itens.
 */

require __DIR__ . '/config.php';
require __DIR__ . '/auth_check.php';

$usuario_id = exigir_login_cliente();
$pedido_id  = isset($_GET['id']) ? (int) $_GET['id'] : null;

try {
    if ($pedido_id) {
        // Detalhe de um pedido específico (só se for do próprio cliente)
        $stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$pedido_id, $usuario_id]);
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
    } else {
        // Lista resumida de todos os pedidos do cliente
        $stmt = $pdo->prepare(
            "SELECT id, tipo_entrega, total, status, criado_em
             FROM pedidos WHERE usuario_id = ? ORDER BY criado_em DESC"
        );
        $stmt->execute([$usuario_id]);
        $pedidos = $stmt->fetchAll();

        foreach ($pedidos as &$p) {
            $p['total'] = (float) $p['total'];
        }

        echo json_encode(['sucesso' => true, 'pedidos' => $pedidos]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao buscar pedido(s).']);
}
