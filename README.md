# LifeMap

Site de bem-estar com três pilares — **Físico**, **Mental** e **Ingesta** —, feito em PHP + MySQL (XAMPP), com um avaliador de físico em Python (Flask + MediaPipe).

**O que já existe**

- Cadastro, login e perfil (dados pessoais, altura, objetivo e problema de saúde). O perfil mostra o treino e a dieta indicados e exporta tudo em PDF.
- Cálculo de IMC com histórico e gráfico de evolução (por usuário).
- Treino e dieta do perfil: o plano aparece sozinho, conforme objetivo e idade cadastrados (com opção de simular outra combinação). O conteúdo é fixo, sem IA, e fica em [includes/planos.php](includes/planos.php).
- Rotina: calendário mensal com agenda do dia (estudo, trabalho, treino, refeição, sono, lazer e geral), reserva de horário com aviso de conflito, eventos que atravessam a meia-noite, repetição (diária, dias úteis, semanal), resumo do tempo por categoria e registro de humor por emoji em cada dia.
- Verificador de movimento (GIFs de exercícios, com busca).
- Conteúdo sobre saúde mental e alimentação.
- Avaliador de físico pela câmera: abre a câmera no navegador, mostra o esqueleto ao vivo, avalia postura e proporções de uma foto de frente, e guarda a foto com o resultado no perfil (com histórico e comparação entre avaliações). A foto e o resultado também vão no PDF do perfil.

## Como rodar

**Requisitos:** XAMPP (Apache + MariaDB, PHP 8.1+) e um navegador atual. A câmera só funciona em `http://localhost` ou `https`.

1. Copie a pasta para `C:\xampp\htdocs\LifeMap` e inicie Apache e MySQL.
2. Crie o banco e as tabelas: no phpMyAdmin, importe [database/academia.sql](database/academia.sql).
   - Se o banco `academia` já existia de uma versão anterior, aplique só as migrações de [database/migracoes/](database/migracoes/), em ordem.
3. Abra <http://localhost/LifeMap/>.

O fuso horário do PHP é fixado em `America/Sao_Paulo` no `includes/bootstrap.php` (o padrão do XAMPP pode ser outro e desloca o "hoje").

Outros ambientes: as credenciais do banco vêm de variáveis de ambiente (`LIFEMAP_DB_HOST`, `LIFEMAP_DB_NAME`, `LIFEMAP_DB_USER`, `LIFEMAP_DB_PASS`), com os padrões do XAMPP em [config/database.php](config/database.php). O prefixo da URL é detectado sozinho (ou defina `LIFEMAP_BASE_URL`).

As fotos das avaliações ficam em `storage/avaliacoes/` (criada sozinha, bloqueada para o navegador e fora do git). Só o dono vê a foto, por `fisico/avaliador_foto.php`.

## Estrutura

```
LifeMap/
├── index.php            Página inicial
├── auth/                Login, cadastro e logout (páginas + processadores)
├── perfil/              Perfil do usuário
├── rotina/              Calendário, agenda e humor (página + API JSON)
├── fisico/              Hub, IMC (+ API), treino, verificador de movimento e avaliador de físico (+ API)
├── mental/              Saúde mental
├── ingesta/             Alimentação e gerador de dieta
├── partials/            HTML compartilhado: head, header, footer, gerador (treino/dieta)
├── includes/            Lógica compartilhada (sem HTML): sessão/URLs, banco, auth, CSRF, regras de saúde,
│                        planos, rotina, avaliação física e PDF (vendor/fpdf: biblioteca de terceiros)
├── config/              Credenciais do banco
├── assets/              css/, js/, img/ (img/marca/ = logo), gif/ e vendor/ (MediaPipe)
├── tools/               Scripts de apoio (gerar_marca.py: variantes do logo)
├── storage/             Fotos das avaliações (privado, não versionado)
└── database/            academia.sql e migracoes/
```

## Convenções

- Toda página começa com `require ... 'includes/bootstrap.php'` (direto ou via `partials/head.php`).
- Links e arquivos estáticos sempre por `url('fisico/imc.php')` e `asset('css/style.css')` — nunca caminhos fixos.
- Um único CSS ([assets/css/style.css](assets/css/style.css)) com os tokens de cor e os componentes (`.card`, `.btn`, `.chip`, `.tema-*`). Não criar CSS por página.
- Formulários que alteram dados enviam o token CSRF (`csrf_campo()` ou cabeçalho `X-CSRF-Token`).
- Rótulos e regras de domínio (objetivos, categorias de IMC, limites) ficam em [includes/saude.php](includes/saude.php); o JavaScript recebe esses dados do PHP. O conteúdo dos treinos e dietas fica em [includes/planos.php](includes/planos.php).
- As pastas internas (`config/`, `includes/`, `partials/`, `database/`, `storage/`, `tools/`) têm `.htaccess` bloqueando o acesso direto pelo navegador.

## Logo

`assets/img/marca/logo-original.png` é o arquivo da marca (não editar). As demais imagens da pasta (`logo-completa`, `logo-simbolo`, `favicon`, `apple-touch-icon`) são geradas dele por `python tools/gerar_marca.py assets/img/marca` (precisa de Pillow e numpy). O logo foi desenhado para fundo escuro, por isso o símbolo vira um ícone de cantos arredondados e a versão completa aparece num card escuro (`#070C12`).

## Bibliotecas de terceiros

- [FPDF](http://www.fpdf.org/) 1.9, em `includes/vendor/fpdf/` (licença permissiva em `license.txt`), gera o PDF do perfil (`perfil/exportar_pdf.php`). Só as fontes Helvetica foram incluídas.
- [MediaPipe Tasks Vision](https://ai.google.dev/edge/mediapipe) (Apache-2.0), em `assets/vendor/mediapipe/`: biblioteca JavaScript, WebAssembly e o modelo `pose_landmarker_lite` que detectam o corpo na foto. Tudo roda no navegador, sem enviar a imagem para fora e sem Python.

## Observações


- As tabelas `treino` e `dieta` do banco vêm do projeto original e não são usadas: o plano é calculado a partir do perfil, sem gravar nada.
- O avaliador antigo (Flask + OpenCV + MediaPipe para Python, `avaliador/`) foi substituído: o MediaPipe atual para Python não tem mais a API `solutions` que ele usava. Continua no histórico do git.
- As imagens em `assets/img/nao-usadas/` não são referenciadas por nenhuma página e podem ser removidas.
