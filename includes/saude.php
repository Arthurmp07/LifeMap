<?php
// Regras e rótulos do domínio (objetivos, faixas etárias, IMC).
// É a única fonte desses dados: PHP e JavaScript leem daqui.

const ROTULOS_OBJETIVO = [
    'perder_peso'    => 'Perder peso',
    'ganhar_peso'    => 'Ganhar peso',
    'ganhar_musculo' => 'Ganhar músculo',
    'manter_saude'   => 'Manter-se saudável',
];

const ROTULOS_FAIXA_ETARIA = [
    '18-50' => 'De 18 a 50 anos',
    '50+'   => 'Mais de 50 anos',
];

const ROTULOS_GENERO = [
    'masculino' => 'Masculino',
    'feminino'  => 'Feminino',
];

// 'limite' é o IMC (exclusivo) em que a categoria termina; null = sem teto.
const IMC_CATEGORIAS = [
    ['chave' => 'abaixo',    'nome' => 'Abaixo do peso',   'faixa' => 'Menor que 18,5', 'limite' => 18.5],
    ['chave' => 'normal',    'nome' => 'Peso normal',      'faixa' => '18,5 a 24,9',    'limite' => 25.0],
    ['chave' => 'sobrepeso', 'nome' => 'Sobrepeso',        'faixa' => '25 a 29,9',      'limite' => 30.0],
    ['chave' => 'ob1',       'nome' => 'Obesidade grau 1', 'faixa' => '30 a 34,9',      'limite' => 35.0],
    ['chave' => 'ob2',       'nome' => 'Obesidade grau 2', 'faixa' => '35 a 39,9',      'limite' => 40.0],
    ['chave' => 'ob3',       'nome' => 'Obesidade grau 3', 'faixa' => '40 ou mais',     'limite' => null],
];

// Limites aceitos nos formulários (mesmos valores validados no navegador).
const PESO_MIN = 20.0;
const PESO_MAX = 500.0;
const ALTURA_MIN = 0.5;
const ALTURA_MAX = 2.8;

function calcular_imc(float $peso, float $altura): float
{
    return round($peso / ($altura * $altura), 2);
}

function imc_categoria(float $imc): array
{
    foreach (IMC_CATEGORIAS as $categoria) {
        if ($categoria['limite'] === null || $imc < $categoria['limite']) {
            return $categoria;
        }
    }
    return IMC_CATEGORIAS[array_key_last(IMC_CATEGORIAS)];
}

/** Converte "70", "70,5" ou "70.5" em float; null se não for número. */
function ler_decimal($texto): ?float
{
    $limpo = str_replace(',', '.', trim((string) $texto));
    $numero = filter_var($limpo, FILTER_VALIDATE_FLOAT);
    return $numero === false ? null : (float) $numero;
}

function formatar_decimal(float $numero, int $casas = 1): string
{
    return number_format($numero, $casas, ',', '');
}

/** Peso sem casa decimal quando for inteiro: 70 -> "70", 70.5 -> "70,5". */
function formatar_peso(float $peso): string
{
    return preg_replace('/,0$/', '', number_format($peso, 1, ',', ''));
}

/** Idade em anos completos a partir de "AAAA-MM-DD"; null se a data for inválida. */
function idade_de(?string $dataNascimento): ?int
{
    $nascimento = $dataNascimento ? DateTime::createFromFormat('!Y-m-d', $dataNascimento) : false;
    return $nascimento ? $nascimento->diff(new DateTime('today'))->y : null;
}

/** Faixa etária usada pelos geradores; null para menores de 18 anos. */
function faixa_etaria_de(?string $dataNascimento): ?string
{
    $idade = idade_de($dataNascimento);
    if ($idade === null || $idade < 18) {
        return null;
    }
    return $idade >= 50 ? '50+' : '18-50';
}

/** Cálculo de IMC mais recente salvo pelo usuário (peso, altura, imc, criado_em), ou null. */
function ultimo_imc(int $usuarioId): ?array
{
    $stmt = db()->prepare(
        'SELECT peso, altura, resultado_imc AS imc, criado_em FROM imc
          WHERE usuario_id = ? ORDER BY criado_em DESC, id DESC LIMIT 1'
    );
    $stmt->execute([$usuarioId]);
    return $stmt->fetch() ?: null;
}
