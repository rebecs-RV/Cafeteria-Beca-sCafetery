<?php
/**
 * sessao_db.php
 *
 * Guarda as sessões de login no banco de dados, em vez de arquivos no
 * servidor (a Aeonfree apagava os arquivos de sessão cedo demais e derrubava
 * o login sem avisar).
 *
 * Como usar: em TODO arquivo que precisa saber quem está logado, no lugar de
 *     session_start();
 * use:
 *     require 'sessao_db.php';        // na raiz do site
 *     require __DIR__ . '/../sessao_db.php';   // dentro de uma subpasta (ex: admin/)
 *
 * Este arquivo já carrega o config.php (o $pdo) e já chama session_start().
 *
 * Cuidados desta versão:
 *  - Funciona em PHP 7.3 ou mais novo (não usa recursos exclusivos do PHP 8).
 *  - Cria a tabela "sessoes" sozinho se ela ainda não existir.
 *  - Se algo der errado com o banco, registra o motivo no log de erros do
 *    servidor e segue em frente (a pessoa só cai pro login), em vez de
 *    derrubar o site inteiro com erro 500.
 */

require_once __DIR__ . '/config.php';

class SessaoBancoDados implements SessionHandlerInterface
{
    private $pdo;
    private $duracaoSegundos;
    private $jaTentouCriarTabela = false;

    public function __construct($pdo, $duracaoSegundos)
    {
        $this->pdo = $pdo;
        $this->duracaoSegundos = (int) $duracaoSegundos;
    }

    private function criarTabela()
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS sessoes (
               id VARCHAR(128) NOT NULL PRIMARY KEY,
               dados MEDIUMTEXT NOT NULL,
               expira_em INT NOT NULL,
               atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    /**
     * Roda uma consulta. Se a tabela "sessoes" não existir, cria e tenta de
     * novo. Em qualquer outro problema, registra no log e devolve false
     * (nunca deixa a exceção subir e virar erro 500).
     */
    private function executar($sql, $parametros)
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($parametros);
            return $stmt;
        } catch (PDOException $e) {
            if ($e->getCode() === '42S02' && !$this->jaTentouCriarTabela) {
                // 42S02 = "tabela não existe"
                $this->jaTentouCriarTabela = true;
                try {
                    $this->criarTabela();
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute($parametros);
                    return $stmt;
                } catch (PDOException $e2) {
                    error_log('[sessao_db] não consegui criar/usar a tabela sessoes: ' . $e2->getMessage());
                    return false;
                }
            }
            error_log('[sessao_db] ' . $e->getMessage());
            return false;
        }
    }

    // O #[\ReturnTypeWillChange] evita avisos no PHP 8.1+; no PHP 7 é só um comentário.

    #[\ReturnTypeWillChange]
    public function open($path, $name)
    {
        return true;
    }

    #[\ReturnTypeWillChange]
    public function close()
    {
        return true;
    }

    #[\ReturnTypeWillChange]
    public function read($id)
    {
        $stmt = $this->executar('SELECT dados FROM sessoes WHERE id = ? AND expira_em > ?', array($id, time()));
        if (!$stmt) {
            return '';
        }
        $linha = $stmt->fetch();
        return $linha ? (string) $linha['dados'] : '';
    }

    #[\ReturnTypeWillChange]
    public function write($id, $dados)
    {
        $stmt = $this->executar(
            'INSERT INTO sessoes (id, dados, expira_em) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE dados = VALUES(dados), expira_em = VALUES(expira_em)',
            array($id, $dados, time() + $this->duracaoSegundos)
        );
        return $stmt !== false;
    }

    #[\ReturnTypeWillChange]
    public function destroy($id)
    {
        $this->executar('DELETE FROM sessoes WHERE id = ?', array($id));
        return true;
    }

    #[\ReturnTypeWillChange]
    public function gc($maxlifetime)
    {
        $stmt = $this->executar('DELETE FROM sessoes WHERE expira_em < ?', array(time()));
        return $stmt ? $stmt->rowCount() : 0;
    }
}

if (session_status() === PHP_SESSION_NONE) {
    // A sessão fica válida por 4 horas sem uso — ajuste aqui se quiser.
    $duracaoSessaoSegundos = 60 * 60 * 4;

    session_set_save_handler(new SessaoBancoDados($pdo, $duracaoSessaoSegundos), true);

    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params(array(
            'lifetime' => $duracaoSessaoSegundos,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ));
    } else {
        session_set_cookie_params($duracaoSessaoSegundos, '/', '', false, true);
    }

    session_start();
}
