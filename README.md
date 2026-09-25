# LifeMap

Site de bem-estar com três pilares — **Físico**, **Mental** e **Ingesta** —, feito em PHP + MySQL (XAMPP), com um avaliador de físico que roda no próprio navegador (MediaPipe) e atendimento por profissionais, com chat e chamadas de voz e vídeo.

**O que já existe**

- Cadastro, login e perfil (dados pessoais, altura, objetivo e problema de saúde). O perfil mostra o treino e a dieta indicados e exporta tudo em PDF.
- Cálculo de IMC com histórico e gráfico de evolução (por usuário).
- Treino e dieta do perfil: o plano aparece sozinho, conforme objetivo e idade cadastrados (com opção de simular outra combinação). O conteúdo é fixo, sem IA, e fica em [includes/planos.php](includes/planos.php).
- Rotina: calendário mensal com agenda do dia (estudo, trabalho, treino, refeição, sono, lazer e geral), reserva de horário com aviso de conflito, eventos que atravessam a meia-noite, repetição (diária, dias úteis, semanal), resumo do tempo por categoria e registro de humor por emoji em cada dia.
- Verificador de movimento (GIFs de exercícios, com busca).
- Conteúdo sobre saúde mental e alimentação.
- Avaliador de físico pela câmera: abre a câmera no navegador, mostra o esqueleto ao vivo, avalia postura e proporções de uma foto de frente, e guarda a foto com o resultado no perfil (com histórico e comparação entre avaliações). A foto e o resultado também vão no PDF do perfil.
- **Três perfis**: usuário, profissional (nutricionista, educador físico, psicólogo…) e administrador. O profissional convida um usuário para um atendimento; se a pessoa aceitar, ele vê a ficha dela (dados, IMC, plano, avaliação física, humor e rotina) e os dois conversam por **chat** e por **chamada de voz e vídeo**. Detalhes em [Perfis e atendimento](#perfis-e-atendimento).

## Como rodar

**Requisitos:** XAMPP (Apache + MariaDB, PHP 8.1+) e um navegador atual. A câmera só funciona em `http://localhost` ou `https`.

1. Copie a pasta para `C:\xampp\htdocs\LifeMap` e inicie Apache e MySQL.
2. Crie o banco e as tabelas: no phpMyAdmin, importe [database/academia.sql](database/academia.sql).
   - Se o banco `academia` já existia de uma versão anterior, aplique só as migrações de [database/migracoes/](database/migracoes/), em ordem.
3. Abra <http://localhost/LifeMap/>.
4. Crie o primeiro **administrador** (ninguém vira administrador ou profissional pelo cadastro público):
   `C:\xampp\php\php.exe tools\criar_admin.php "Seu Nome" seu@email.com`. O comando mostra uma senha provisória, que você troca no primeiro acesso. Para transformar uma conta de usuário que já existe em administradora, acrescente `--promover` (ela deixa de usar as telas de usuário). Rodando de novo para um administrador existente, gera outra senha provisória (recuperação de acesso).

O fuso horário do PHP é fixado em `America/Sao_Paulo` no `includes/bootstrap.php` (o padrão do XAMPP pode ser outro e desloca o "hoje").

Outros ambientes: as credenciais do banco vêm de variáveis de ambiente (`LIFEMAP_DB_HOST`, `LIFEMAP_DB_NAME`, `LIFEMAP_DB_USER`, `LIFEMAP_DB_PASS`), com os padrões do XAMPP em [config/database.php](config/database.php). O prefixo da URL é detectado sozinho (ou defina `LIFEMAP_BASE_URL`).

As fotos das avaliações ficam em `storage/avaliacoes/` (criada sozinha, bloqueada para o navegador e fora do git). Só o dono vê a foto, por `fisico/avaliador_foto.php`.

## Estrutura

```
LifeMap/
├── index.php            Página inicial
├── auth/                Login, cadastro, logout e troca de senha (páginas + processadores)
├── perfil/              Perfil do usuário
├── admin/               Painel do administrador: cadastrar, desativar e reativar profissionais
├── profissional/        Painel do profissional (busca, convites, pacientes) e ficha do paciente
├── atendimento/         Atendimento do usuário, chat, chamadas e suas APIs JSON (api.php, chamada_api.php)
├── rotina/              Calendário, agenda e humor (página + API JSON)
├── fisico/              Hub, IMC (+ API), treino, verificador de movimento e avaliador de físico (+ API)
├── mental/              Saúde mental
├── ingesta/             Alimentação e gerador de dieta
├── partials/            HTML compartilhado: head, header, footer, gerador (treino/dieta)
├── includes/            Lógica compartilhada (sem HTML): sessão/URLs, banco, auth e perfis, CSRF, regras de saúde,
│                        planos, rotina, avaliação física, atendimento, chat, chamadas, ficha do paciente e PDF
│                        (vendor/fpdf: biblioteca de terceiros)
├── config/              Credenciais do banco (database.php) e servidores das chamadas (webrtc.php)
├── assets/              css/, js/, img/ (img/marca/ = logo), gif/ e vendor/ (MediaPipe)
├── tools/               Scripts de apoio (gerar_marca.py: logo; criar_admin.php: primeiro administrador;
│                        verificar_css.py: confere os temas claro/escuro)
├── storage/             Fotos das avaliações (privado, não versionado)
└── database/            academia.sql e migracoes/
```

## Convenções

- Toda página começa com `require ... 'includes/bootstrap.php'` (direto ou via `partials/head.php`).
- Links e arquivos estáticos sempre por `url('fisico/imc.php')` e `asset('css/style.css')` — nunca caminhos fixos. `asset()` acrescenta `?v=<data do arquivo>` a CSS e JS, para o navegador baixar a versão nova em vez de reaproveitar uma cópia antiga (por isso nunca escreva `<script src>` ou `<link>` de `assets/css|js` sem passar por ele).
- Um único CSS ([assets/css/style.css](assets/css/style.css)) com os tokens de cor e os componentes (`.card`, `.btn`, `.chip`, `.tema-*`). Não criar CSS por página.
- Formulários que alteram dados enviam o token CSRF (`csrf_campo()` ou cabeçalho `X-CSRF-Token`).
- Controle de acesso pelo perfil da pessoa, **sempre conferido no banco**: páginas usam `exigir_papel('usuario', 'profissional')`; APIs JSON usam `exigir_papel_api(...)` (que também libera o lock da sessão, importante nas chamadas de polling) e respondem com `api_responder()` / `api_erro()` de [includes/api.php](includes/api.php). O perfil guardado na sessão serve só para montar o menu.
- Rótulos e regras de domínio (objetivos, categorias de IMC, limites) ficam em [includes/saude.php](includes/saude.php); o JavaScript recebe esses dados do PHP. O conteúdo dos treinos e dietas fica em [includes/planos.php](includes/planos.php).
- As pastas internas (`config/`, `includes/`, `partials/`, `database/`, `storage/`, `tools/`) têm `.htaccess` bloqueando o acesso direto pelo navegador.

## Perfis e atendimento

**Quem é quem** (`usuarios.papel`): `usuario` (cadastro público), `profissional` e `admin`. Só o administrador cria profissionais (`admin/`: nome, e-mail, especialidade, registro e telefone); o sistema gera uma **senha provisória mostrada uma única vez**, e a pessoa é obrigada a trocá-la no primeiro acesso. Administrador e profissional não usam as telas de usuário (recebem "sem acesso"). O administrador só gerencia contas de profissional e **não lê conversas**. Desativar um profissional (`usuarios.ativo = 0`) derruba a sessão dele, apaga os convites pendentes, encerra os atendimentos e desliga as chamadas em curso.

**Vínculo (tabela `atendimentos`, uma linha por par):** o profissional busca o usuário (por nome ou e-mail, mínimo 3 letras; a busca mostra só o nome e o e-mail parcialmente mascarado) e envia um **convite**. Só quando o usuário **aceita** o profissional passa a ver a ficha (`profissional/paciente.php`): dados pessoais, IMC com gráfico, plano indicado, avaliação física com foto, humor dos últimos 30 dias e resumo da semana da rotina. A regra de acesso é uma só, em `ficha_liberada_para()` ([includes/atendimento.php](includes/atendimento.php)), e vale também para a foto (`fisico/avaliador_foto.php`). O usuário vê quando o profissional abriu a ficha pela última vez, e qualquer um dos dois pode **encerrar**: o acesso à ficha termina na hora e a conversa fica guardada, só para leitura. Depois de uma recusa (ou de o usuário encerrar) o profissional espera 7 dias para convidar de novo, e cada profissional pode ter no máximo 30 convites pendentes.

**Chat** (`atendimento/chat.php`, `atendimento/api.php`, `assets/js/chat.js`): sem conexão aberta; a página pergunta por mensagens novas a cada 2,5 s (10 s com a aba em segundo plano). Texto de até 2000 caracteres, no máximo 30 mensagens por minuto por pessoa, sempre exibido como texto (nunca HTML), com "Enviada/Lida" e histórico paginado. Avisos automáticos (atendimento iniciado/encerrado, chamadas) entram na conversa como mensagens do tipo `sistema`. O contador do menu e o aviso de chamada nas outras páginas vêm de `assets/js/avisos.js`.

**Chamadas de voz e vídeo** (`atendimento/chamada_api.php`, `includes/chamada.php`, `assets/js/chamada.js`): WebRTC direto entre os dois navegadores — **nada de áudio ou vídeo passa pelo servidor nem é gravado**. O servidor só guarda o estado da chamada e os "sinais" de conexão, que os navegadores buscam por polling. Uma chamada por vez em cada atendimento; não atendida em 45 s vira "perdida"; se um lado some (aba fechada, internet caiu) o servidor encerra por falta de batimento (20 s tocando, 30 s em andamento). Requisitos e limites:

- Microfone e câmera só funcionam em `http://localhost` ou `https://`. Em outro computador da rede, use HTTPS.
- Os servidores STUN públicos do Google descobrem o endereço de cada lado (eles enxergam o IP de quem liga). Mude em `LIFEMAP_STUN_URLS` ou desligue com `none` (aí só funciona na mesma rede) — ver [config/webrtc.php](config/webrtc.php).
- Em redes que bloqueiam conexões diretas (rede corporativa, alguns 4G), a chamada só conecta com um servidor **TURN** próprio ou contratado: `LIFEMAP_TURN_URL`, `LIFEMAP_TURN_USER`, `LIFEMAP_TURN_PASS`.

**Migrações:** `database/migracoes/004_papeis_atendimento_chat.sql` (papéis, `profissionais`, `atendimentos`, `mensagens`, `chamadas`, `sinais_chamada`) já está em `academia.sql`.

## Modo escuro

- Os tokens que mudam com o modo (cores de fundo, texto, linhas, tintas das categorias, etc.) têm o valor claro em `:root` e o valor escuro em **dois blocos idênticos** logo abaixo: `@media (prefers-color-scheme: dark) { :root:not([data-theme="light"]) { … } }` (segue o sistema) e `:root[data-theme="dark"] { … }` (escolha manual). Ao criar ou alterar um token dependente do modo, edite os três lugares e rode `python tools/verificar_css.py`, que acusa variável sem definição e blocos escuros divergentes.
- Componentes usam sempre `var(--…)`; cor fixa só para o que não muda com o modo (marca, ilustrações em "azulejo" claro, cores das trilhas Físico/Mental/Ingesta).
- O botão do cabeçalho (`#tema-toggle`, em [assets/js/site.js](assets/js/site.js)) grava `'dark'` ou `'light'` em `localStorage['lifemap-tema']` e define `data-theme` no `<html>`; sem escolha guardada, vale o sistema. Um script inline em [partials/head.php](partials/head.php) aplica a escolha antes da primeira pintura, para não piscar o tema errado.
- Textos novos devem manter contraste mínimo de 4,5:1 (3:1 para texto grande) nos dois modos.

## Logo

`assets/img/marca/logo-original.png` é o arquivo da marca (não editar). As demais imagens da pasta (`logo-completa`, `logo-simbolo`, `favicon`, `apple-touch-icon`) são geradas dele por `python tools/gerar_marca.py assets/img/marca` (precisa de Pillow e numpy). O logo foi desenhado para fundo escuro, por isso o símbolo vira um ícone de cantos arredondados e a versão completa aparece num card escuro (`#070C12`).

## Bibliotecas de terceiros

- [FPDF](http://www.fpdf.org/) 1.9, em `includes/vendor/fpdf/` (licença permissiva em `license.txt`), gera o PDF do perfil (`perfil/exportar_pdf.php`). Só as fontes Helvetica foram incluídas.
- [MediaPipe Tasks Vision](https://ai.google.dev/edge/mediapipe) (Apache-2.0), em `assets/vendor/mediapipe/`: biblioteca JavaScript, WebAssembly e o modelo `pose_landmarker_lite` que detectam o corpo na foto. Tudo roda no navegador, sem enviar a imagem para fora e sem Python.

## Observações


- As tabelas `treino` e `dieta` do banco vêm do projeto original e não são usadas: o plano é calculado a partir do perfil, sem gravar nada.
- O avaliador antigo (Flask + OpenCV + MediaPipe para Python, `avaliador/`) foi substituído: o MediaPipe atual para Python não tem mais a API `solutions` que ele usava. Continua no histórico do git.
- As imagens em `assets/img/nao-usadas/` não são referenciadas por nenhuma página e podem ser removidas.
