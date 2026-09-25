<?php
// Validações compartilhadas entre o cadastro e o perfil.

/** Telefone brasileiro com DDD: 10 ou 11 dígitos (a máscara e os símbolos são ignorados). */
function telefone_valido(string $telefone): bool
{
    $digitos = preg_replace('/\D/', '', $telefone);
    return strlen($digitos) >= 10 && strlen($digitos) <= 11;
}

/** Devolve a lista de mensagens de erro (vazia se estiver tudo certo). */
function validar_dados_pessoais(string $nome, string $genero, string $dataNascimento, string $telefone): array
{
    $erros = [];

    if (mb_strlen($nome) < 3 || mb_strlen($nome) > 100) {
        $erros[] = 'Informe seu nome completo.';
    }

    if (!isset(ROTULOS_GENERO[$genero])) {
        $erros[] = 'Selecione o gênero.';
    }

    // <input type="date"> envia AAAA-MM-DD, o formato da coluna `date`.
    $data = DateTime::createFromFormat('!Y-m-d', $dataNascimento);
    $dataValida = $data && $data->format('Y-m-d') === $dataNascimento
        && $dataNascimento >= '1900-01-01' && $dataNascimento <= date('Y-m-d');
    if (!$dataValida) {
        $erros[] = 'Informe uma data de nascimento válida.';
    }

    if (!telefone_valido($telefone)) {
        $erros[] = 'Informe um telefone com DDD.';
    }

    return $erros;
}
