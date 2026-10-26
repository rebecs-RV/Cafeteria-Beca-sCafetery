<?php
/**
 * cardapio_listar.php
 * GET público — lista os produtos disponíveis, agrupados por categoria.
 *
 * Resposta:
 * {
 *   "sucesso": true,
 *   "produtos": [
 *     { "id":1, "nome":"...", "descricao":"...", "preco":12.50,
 *       "categoria":"doce", "foto":"...", "disponivel":1 },
 *     ...
 *   ]
 * }
 */

require __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $stmt = $pdo->query(
        "SELECT id, nome, descricao, preco, categoria, foto, disponivel
         FROM produtos
         WHERE disponivel = 1
         ORDER BY categoria, nome"
    );
    $produtos = $stmt->fetchAll();

    // Converte preco pra número (o PDO às vezes retorna string de DECIMAL)
    foreach ($produtos as &$p) {
        $p['preco']      = (float) $p['preco'];
        $p['disponivel'] = (int) $p['disponivel'];
    }

    echo json_encode(['sucesso' => true, 'produtos' => $produtos]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'erro' => 'Erro ao buscar cardápio.']);
}
