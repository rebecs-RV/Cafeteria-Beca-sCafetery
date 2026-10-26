<?php
/**
 * Configuração de conexão com o banco de dados MySQL.
 *
 * Troque os 4 valores abaixo pelos dados do SEU banco na Aeonfree.
 * Você encontra isso no painel: Databases > MySQL Databases
 * (ou "phpMyAdmin", que também mostra o nome do banco/usuário no topo).
 *
 * IMPORTANTE: nunca deixe este arquivo público em um repositório
 * de código aberto com a senha real preenchida.
 */

define('DB_HOST', 'sql300.iceiy.com');                 // normalmente 'localhost' na Aeonfree
define('DB_NAME', 'icei_42703377_usuarios');   // ex: "epiz_12345678_becascafetery"
define('DB_USER', 'icei_42703377');          // usuário do banco (não é o do FTP)
define('DB_PASS', 'becacafetery12');         // senha do banco

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    // Em produção, evite mostrar o erro completo pro visitante.
    die('Não foi possível conectar ao banco de dados. Verifique config.php.');
}
