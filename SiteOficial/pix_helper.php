<?php
/**
 * pix_helper.php
 * Gera o payload EMV do PIX ("Pix Copia e Cola"), usado tanto para
 * mostrar o código de texto quanto para desenhar o QR Code no front-end.
 *
 * Baseado no Manual de Padrões para Iniciação do Pix (Bacen).
 */

function gerarPayloadPix(string $chave, string $nomeRecebedor, string $cidade, float $valor, string $txid): string {
    $payload  = tlvPix('00', '01'); // Payload Format Indicator

    $gui  = tlvPix('00', 'br.gov.bcb.pix');
    $key  = tlvPix('01', $chave);
    $payload .= tlvPix('26', $gui . $key); // Merchant Account Information

    $payload .= tlvPix('52', '0000'); // Merchant Category Code
    $payload .= tlvPix('53', '986');  // Moeda: Real (BRL)

    if ($valor > 0) {
        $payload .= tlvPix('54', number_format($valor, 2, '.', ''));
    }

    $payload .= tlvPix('58', 'BR');
    $payload .= tlvPix('59', limparTextoPix($nomeRecebedor, 25));
    $payload .= tlvPix('60', limparTextoPix($cidade, 15));

    $txidLimpo = preg_replace('/[^A-Za-z0-9]/', '', $txid);
    $txidLimpo = $txidLimpo === '' ? '***' : substr($txidLimpo, 0, 25);
    $payload .= tlvPix('62', tlvPix('05', $txidLimpo)); // Additional Data Field (txid)

    $payload .= '6304'; // Início do CRC16 (ID 63, tamanho 04)
    $payload .= crc16Pix($payload);

    return $payload;
}

/** Monta um campo no formato ID + Tamanho (2 dígitos) + Valor */
function tlvPix(string $id, string $valor): string {
    $tamanho = str_pad((string) strlen($valor), 2, '0', STR_PAD_LEFT);
    return $id . $tamanho . $valor;
}

/** Remove acentos e caracteres especiais (o padrão Pix não aceita) e corta no tamanho máximo */
function limparTextoPix(string $texto, int $tamanhoMax): string {
    $texto = iconv('UTF-8', 'ASCII//TRANSLIT', $texto) ?: $texto;
    $texto = preg_replace('/[^A-Za-z0-9 ]/', '', $texto);
    return substr(trim($texto), 0, $tamanhoMax);
}

/** CRC16/CCITT-FALSE, exigido no final do payload Pix */
function crc16Pix(string $payload): string {
    $polinomio = 0x1021;
    $resultado = 0xFFFF;

    for ($i = 0; $i < strlen($payload); $i++) {
        $resultado ^= (ord($payload[$i]) << 8);
        for ($j = 0; $j < 8; $j++) {
            if (($resultado & 0x8000) !== 0) {
                $resultado = (($resultado << 1) ^ $polinomio) & 0xFFFF;
            } else {
                $resultado = ($resultado << 1) & 0xFFFF;
            }
        }
    }

    return strtoupper(str_pad(dechex($resultado), 4, '0', STR_PAD_LEFT));
}
