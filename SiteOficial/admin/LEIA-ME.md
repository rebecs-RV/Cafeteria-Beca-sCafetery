# API Beca Cafetery — Cardápio, Carrinho, Pedido (PIX manual) e Admin

## 1. Instalação

1. Envie a pasta `becacafetery-api/` (via FTP) para dentro da mesma pasta onde já está o seu `login.php` / `config.php` na Aeonfree.
2. **Copie o seu `config.php` já existente** para dentro desta pasta (ele não veio incluso aqui — reaproveite o que você já tem, com `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`).
3. Abra `pix_config.php` e preencha `PIX_CHAVE`, `PIX_NOME_RECEBEDOR` e `PIX_CIDADE` com os seus dados reais.
4. Confirme que o seu `login.php` grava na sessão exatamente:
   ```php
   $_SESSION['usuario_id'] = $row['id'];
   $_SESSION['tipo']       = $row['tipo']; // 'cliente' ou 'admin'
   ```
   Se os nomes forem diferentes, me manda o `login.php` que eu ajusto o `auth_check.php`.

## 2. Endpoints criados

### Público / Cliente logado
| Arquivo | Método | O que faz |
|---|---|---|
| `cardapio_listar.php` | GET | Lista produtos disponíveis |
| `carrinho.php` | GET | Lista itens do carrinho do cliente logado |
| `carrinho.php` | POST | Adiciona produto ao carrinho `{produto_id, quantidade}` |
| `carrinho.php` | PUT | Atualiza quantidade `{produto_id, quantidade}` |
| `carrinho.php` | DELETE | Remove item `?produto_id=3` |
| `pedido_criar.php` | POST | Fecha o carrinho como pedido (`aguardando_pagamento`) e devolve os dados do PIX |
| `pedido_status.php` | GET | Lista os pedidos do cliente (ou `?id=5` para detalhe) |

### Admin (exige `tipo = 'admin'` na sessão)
| Arquivo | Método | O que faz |
|---|---|---|
| `admin/produtos_crud.php` | GET/POST/PUT/DELETE | CRUD do cardápio |
| `admin/pedidos_listar.php` | GET | Lista todos os pedidos (`?status=...` filtra, `?id=..` detalha) |
| `admin/pedido_atualizar_status.php` | POST | Admin confirma pagamento / avança status `{pedido_id, status}` |

## 3. Confirmado com você

- **Status de `pedidos`** (confirmado): `aguardando_pagamento, pago, preparando, saiu_para_entrega, concluido, cancelado`.
- **Sessão do login** (confirmado, seu `login.php` já bate certinho): `$_SESSION['usuario_id']`, `$_SESSION['nome']`, `$_SESSION['tipo']`.
- Os campos `mp_preference_id` e `mp_payment_id` da tabela `pedidos` ficam sem uso por enquanto (são de uma eventual integração futura com Mercado Pago).
- **Categorias de produto**: hoje o banco só aceita `'doce'` e `'bebida'` (é um `enum`). Se seu cardápio tiver mais categorias (salgados, combos etc.), me avisa que eu te mando o `ALTER TABLE` certo e ajusto a validação em `admin/produtos_crud.php`.

## 3.1. Páginas de front-end (pasta `becacafetery-front/`)

- **`painel-cliente.php`** — substitui o seu atual: abas Cardápio / Carrinho / Meus pedidos, tudo consumindo os endpoints acima via `fetch`. Ao finalizar, mostra a chave PIX e um botão de copiar.
- **`painel-admin.php`** — substitui o seu atual: mantém a aba "Clientes" que já existia (renderizada em PHP puro, igual antes) e adiciona "Cardápio" (CRUD com modal de criar/editar) e "Pedidos" (lista com filtro por status e um `<select>` pra você mudar o status, inclusive confirmar o PIX marcando "Pago").
- Ambas usam o mesmo `auth.css` e a mesma paleta (`--peach`, `--latte`, `--rust` etc.) que você já tinha — não criei CSS novo separado, só adicionei estilos extras dentro de `<style>` no próprio arquivo, do mesmo jeito que seu `painel-admin.php` original já fazia.
- **Onde colocar**: essas duas páginas vão na mesma pasta dos arquivos da API (raiz do site, junto com `login.php`, `config.php`, `auth.css`). Elas chamam os endpoints com caminho relativo (`cardapio_listar.php`, `admin/produtos_crud.php` etc.), então a pasta `admin/` da API precisa estar dentro dessa mesma pasta.

## 4. Fluxo do PIX manual

1. Cliente monta o carrinho (`carrinho.php`).
2. Cliente finaliza (`pedido_criar.php`) → pedido criado como `aguardando_pagamento`, resposta já traz a chave PIX pra você mostrar na tela (texto copia-e-cola; se quiser eu gero o QR Code depois).
3. Cliente paga no app do banco dele.
4. Você confere no seu banco e confirma no painel admin → chama `admin/pedido_atualizar_status.php` com `status: "pago"`.
5. Você vai avançando o status (`preparando` → `saiu_entrega` → `finalizado`) pelo mesmo endpoint conforme prepara o pedido.

## 5. Próximos passos (ainda não feitos)

- **Gerar QR Code do PIX** (hoje só mostra a chave em texto pra copiar). Dá pra gerar 100% no front, sem custo, usando o padrão "BR Code" (Pix Copia e Cola virando QR). Se quiser, eu adiciono isso.
- **Fotos dos produtos**: o campo `foto` espera uma URL/caminho de imagem (ex: `assets/produtos/brigadeiro.jpg`). Você vai precisar subir essas imagens na hospedagem e preencher esse campo ao cadastrar cada produto no admin.
- Testar tudo direto na Aeonfree depois de subir os arquivos (copiar `config.php` pra dentro de `becacafetery-api/`, preencher `pix_config.php`, e colocar as duas páginas de `becacafetery-front/` na raiz do site substituindo as antigas).
