<?php
require_once __DIR__ . '/../includes/bootstrap.php';

exigir_papel('usuario');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirecionar(url('perfil/'));
}

$nome = trim($_POST['nome'] ?? '');
$genero = $_POST['genero'] ?? '';
$data_nascimento = trim($_POST['data_nascimento'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$altura_texto = trim($_POST['altura'] ?? '');
$objetivo = $_POST['objetivo'] ?? '';
$problema_saude = trim($_POST['problema_saude'] ?? '');

$antigo = [
    'nome' => $nome,
    'genero' => $genero,
    'data_nascimento' => $data_nascimento,
    'telefone' => $telefone,
    'altura' => $altura_texto,
    'objetivo' => $objetivo,
    'problema_saude' => $problema_saude,
];

if (!csrf_valido()) {
    flash_set('perfil_erros', ['Sua sessão expirou. Tente salvar novamente.']);
    flash_set('perfil_old', $antigo);
    redirecionar(url('perfil/'));
}

$erros = validar_dados_pessoais($nome, $genero, $data_nascimento, $telefone);

// Altura é opcional; aceita "1,75", "1.75" ou centímetros ("175").
$altura = null;
if ($altura_texto !== '') {
    $altura = ler_decimal($altura_texto);
    if ($altura !== null && $altura > 3) {
        $altura = $altura / 100;
    }
    if ($altura === null || $altura < ALTURA_MIN || $altura > ALTURA_MAX) {
        $erros[] = 'Informe uma altura entre 0,5 e 2,8 m.';
    } else {
        $altura = round($altura, 2);
    }
}

if ($objetivo !== '' && !isset(ROTULOS_OBJETIVO[$objetivo])) {
    $erros[] = 'Escolha um objetivo válido.';
}

if (mb_strlen($problema_saude) > 255) {
    $erros[] = 'Descreva o problema de saúde em até 255 caracteres.';
}

if ($erros) {
    flash_set('perfil_erros', $erros);
    flash_set('perfil_old', $antigo);
    redirecionar(url('perfil/'));
}

// O cadastro fica "completo" quando os dados que alimentam os geradores estão preenchidos.
$completo = ($altura !== null && $objetivo !== '') ? 1 : 0;

try {
    $stmt = db()->prepare(
        'UPDATE usuarios
            SET nome = ?, genero = ?, data_nascimento = ?, telefone = ?,
                altura = ?, objetivo = ?, problema_saude = ?, cadastro_completo = ?
          WHERE id = ?'
    );
    $stmt->execute([
        $nome, $genero, $data_nascimento, $telefone,
        $altura, $objetivo !== '' ? $objetivo : null, $problema_saude !== '' ? $problema_saude : null,
        $completo, $_SESSION['user_id'],
    ]);
} catch (PDOException $e) {
    error_log('Erro ao salvar perfil: ' . $e->getMessage());
    flash_set('perfil_erros', ['Não foi possível salvar agora. Tente novamente em instantes.']);
    flash_set('perfil_old', $antigo);
    redirecionar(url('perfil/'));
}

$_SESSION['user_nome'] = $nome;
$_SESSION['cadastro_completo'] = $completo;

flash_set('perfil_ok', 'Perfil atualizado! Os geradores de treino e dieta já usam esses dados.');
redirecionar(url('perfil/'));
