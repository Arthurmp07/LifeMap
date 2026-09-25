<?php
// Senhas provisórias (contas criadas pelo administrador): a pessoa é obrigada a trocá-las no primeiro acesso.

/** Senha legível, sem caracteres que se confundem (0/O, 1/l/I). */
function senha_provisoria(int $tamanho = 14): string
{
    $alfabeto = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $senha = '';
    for ($i = 0; $i < $tamanho; $i++) {
        $senha .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
    }
    return $senha;
}
