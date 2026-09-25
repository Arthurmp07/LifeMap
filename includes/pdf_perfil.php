<?php
// PDF do perfil do usuário (dados, IMC, treino e dieta indicados), feito com FPDF.
// O FPDF trabalha com as fontes padrão em Latin-1/cp1252, que cobre o português.
require_once __DIR__ . '/vendor/fpdf/fpdf.php';

/** UTF-8 -> cp1252, o que o FPDF espera nas fontes padrão. */
function pdf_texto(string $texto): string
{
    // A conversão automática troca alguns símbolos por "?"; normalizamos os mais comuns antes.
    $texto = strtr($texto, ['…' => '...', '→' => '->', '✓' => '+', '✕' => 'x', '’' => "'", '‘' => "'"]);
    return iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $texto) ?: '';
}

class PerfilPdf extends FPDF
{
    public const VERDE = [47, 125, 107];
    public const AMBAR = [199, 138, 0];
    public const FOLHA = [37, 107, 64];
    public const VERMELHO = [179, 55, 47];
    public const TEXTO = [31, 42, 36];
    public const SUAVE = [85, 100, 91];

    public string $nomePessoa = '';
    public string $geradoEm = '';

    private const MARGEM = 16;

    public function Header(): void
    {
        $this->SetFillColor(...self::VERDE);
        $this->Rect(0, 0, 210, 24, 'F');

        $this->Image(BASE_PATH . '/assets/img/marca/logo-simbolo.png', self::MARGEM, 4.5, 15, 15);

        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Helvetica', 'B', 20);
        $this->SetXY(self::MARGEM + 19, 6.5);
        $this->Cell(60, 10, 'LifeMap');

        $this->SetFont('Helvetica', '', 9);
        $this->SetXY(90, 7.5);
        $this->Cell(210 - 90 - self::MARGEM, 4.5, pdf_texto('Perfil de ' . $this->nomePessoa), 0, 2, 'R');
        $this->Cell(210 - 90 - self::MARGEM, 4.5, pdf_texto('Gerado em ' . $this->geradoEm), 0, 0, 'R');

        $this->SetY(33);
        $this->SetTextColor(...self::TEXTO);
    }

    public function Footer(): void
    {
        $this->SetY(-15);
        $this->SetFont('Helvetica', '', 7.5);
        $this->SetTextColor(...self::SUAVE);
        $this->Cell(0, 4, pdf_texto('Conteúdo informativo. Não substitui a orientação de médicos, nutricionistas, educadores físicos ou psicólogos.'), 0, 1, 'C');
        $this->Cell(0, 4, pdf_texto('LifeMap · página ' . $this->PageNo() . ' de {nb}'), 0, 0, 'C');
    }

    /** Quebra de página antes de um bloco que não cabe no que sobrou. */
    public function garantirEspaco(float $mm): void
    {
        if ($this->GetY() + $mm > $this->PageBreakTrigger) {
            $this->AddPage();
        }
    }

    public function secao(string $titulo, array $cor): void
    {
        $this->garantirEspaco(22);
        $this->Ln(3);
        $this->SetFont('Helvetica', 'B', 13);
        $this->SetTextColor(...$cor);
        $this->Cell(0, 7, pdf_texto($titulo), 0, 1);
        $this->SetDrawColor(...$cor);
        $this->SetLineWidth(0.5);
        $this->Line(self::MARGEM, $this->GetY(), 210 - self::MARGEM, $this->GetY());
        $this->Ln(3);
        $this->SetTextColor(...self::TEXTO);
        $this->SetLineWidth(0.2);
    }

    /** Linha "Rótulo   Valor" (o valor pode ocupar várias linhas). */
    public function campo(string $rotulo, string $valor): void
    {
        $this->garantirEspaco(8);
        $y = $this->GetY();
        $this->SetFont('Helvetica', 'B', 9);
        $this->SetTextColor(...self::SUAVE);
        $this->Cell(42, 5.5, pdf_texto($rotulo));
        $this->SetFont('Helvetica', '', 10.5);
        $this->SetTextColor(...self::TEXTO);
        $this->SetXY(self::MARGEM + 42, $y);
        $this->MultiCell(0, 5.5, pdf_texto($valor));
        $this->Ln(0.8);
    }

    public function subtitulo(string $texto): void
    {
        $this->garantirEspaco(16);
        $this->Ln(1.5);
        $this->SetFont('Helvetica', 'B', 10.5);
        $this->SetTextColor(...self::TEXTO);
        $this->MultiCell(0, 5.5, pdf_texto($texto));
        $this->Ln(0.8);
    }

    /** Lista com marcadores, um item por linha (o texto pode quebrar). */
    public function lista(array $itens, array $corMarcador, string $marcador = "\x95"): void
    {
        $this->SetFont('Helvetica', '', 10);
        foreach ($itens as $item) {
            $this->garantirEspaco(8);
            $y = $this->GetY();
            $this->SetTextColor(...$corMarcador);
            $this->SetX(self::MARGEM + 2);
            $this->Cell(5, 5.2, $marcador);
            $this->SetTextColor(...self::TEXTO);
            $this->SetXY(self::MARGEM + 7, $y);
            $this->MultiCell(0, 5.2, pdf_texto($item));
        }
        $this->Ln(1);
    }

    /** Lista curta em duas colunas (itens que cabem numa linha). */
    public function listaEmColunas(array $itens, array $corMarcador, string $marcador = "\x95"): void
    {
        $larguraColuna = (210 - 2 * self::MARGEM) / 2;
        $this->SetFont('Helvetica', '', 10);
        for ($i = 0; $i < count($itens); $i += 2) {
            $this->garantirEspaco(8);
            $y = $this->GetY();
            foreach ([0, 1] as $coluna) {
                if (!isset($itens[$i + $coluna])) {
                    continue;
                }
                $x = self::MARGEM + 2 + $coluna * $larguraColuna;
                $this->SetTextColor(...$corMarcador);
                $this->SetXY($x, $y);
                $this->Cell(5, 5.2, $marcador);
                $this->SetTextColor(...self::TEXTO);
                $this->Cell($larguraColuna - 8, 5.2, pdf_texto($itens[$i + $coluna]));
            }
            $this->SetY($y + 5.4);
        }
        $this->Ln(1);
    }

    public function etiquetas(array $textos, array $cor): void
    {
        $this->SetFont('Helvetica', 'B', 8.5);
        $this->SetTextColor(...$cor);
        $this->MultiCell(0, 5, pdf_texto(implode('   |   ', $textos)));
        $this->SetTextColor(...self::TEXTO);
        $this->Ln(1.5);
    }

    /** Quantas linhas o texto ocupa numa MultiCell de largura útil $largura (quebra por palavras). */
    private function contarLinhas(string $texto, float $largura): int
    {
        $linhas = 1;
        $atual = '';
        foreach (explode(' ', $texto) as $palavra) {
            $tentativa = $atual === '' ? $palavra : $atual . ' ' . $palavra;
            if ($atual !== '' && $this->GetStringWidth($tentativa) > $largura) {
                $linhas++;
                $atual = $palavra;
            } else {
                $atual = $tentativa;
            }
        }
        return $linhas;
    }

    public function aviso(string $texto): void
    {
        $texto = pdf_texto($texto);
        $this->SetFont('Helvetica', '', 9.5);

        $larguraCaixa = 210 - 2 * self::MARGEM;
        $larguraTexto = $larguraCaixa - 9;                                   // 4,5 mm de margem de cada lado
        $linhas = $this->contarLinhas($texto, $larguraTexto - 2 * $this->cMargin - 1);
        $altura = $linhas * 5.5 + 6;

        $this->garantirEspaco($altura + 3);
        $this->Ln(1);
        $x = self::MARGEM;
        $y = $this->GetY();

        $this->SetFillColor(253, 241, 211);
        $this->Rect($x, $y, $larguraCaixa, $altura, 'F');
        $this->SetFillColor(245, 184, 61);
        $this->Rect($x, $y, 1.2, $altura, 'F');

        $this->SetTextColor(107, 74, 0);
        $this->SetXY($x + 4.5, $y + 3);
        $this->MultiCell($larguraTexto, 5.5, $texto);
        $this->SetTextColor(...self::TEXTO);
        $this->SetY($y + $altura + 2);
    }

    /** Avaliação física: foto à esquerda, medidas à direita e o resumo embaixo. */
    public function avaliacaoFisica(array $itens, string $arquivoFoto, string $resumo, ?string $comparacao, string $aviso): void
    {
        [$larguraPx, $alturaPx] = getimagesize($arquivoFoto);
        $largura = 52.0;
        $altura = $largura * $alturaPx / $larguraPx;
        if ($altura > 78) {                       // foto muito alta: limita pela altura
            $altura = 78.0;
            $largura = $altura * $larguraPx / $alturaPx;
        }

        $this->garantirEspaco(max($altura, 58) + 30);
        $x0 = self::MARGEM;
        $y0 = $this->GetY();
        $this->Image($arquivoFoto, $x0, $y0, $largura, $altura);
        $this->SetDrawColor(200, 205, 200);
        $this->Rect($x0, $y0, $largura, $altura);

        $xTexto = $x0 + $largura + 7;
        $larguraTexto = 210 - self::MARGEM - $xTexto;
        $this->SetXY($xTexto, $y0);
        foreach ($itens as $item) {
            $this->SetX($xTexto);
            $this->SetFont('Helvetica', 'B', 9.5);
            $this->SetTextColor(...self::TEXTO);
            $this->MultiCell($larguraTexto, 5, pdf_texto($item['titulo'] . ': ' . $item['valor'] . '  (' . $item['rotulo'] . ')'));
            $this->SetX($xTexto);
            $this->SetFont('Helvetica', '', 9);
            $this->SetTextColor(...self::SUAVE);
            $this->MultiCell($larguraTexto, 4.5, pdf_texto($item['texto']));
            $this->Ln(1.6);
        }

        $this->SetY(max($y0 + $altura, $this->GetY()) + 3);
        $this->SetTextColor(...self::TEXTO);
        $this->SetFont('Helvetica', 'B', 9.5);
        $this->MultiCell(0, 5, pdf_texto($resumo));
        if ($comparacao) {
            $this->SetFont('Helvetica', '', 9);
            $this->SetTextColor(...self::SUAVE);
            $this->MultiCell(0, 4.8, pdf_texto($comparacao));
        }
        $this->SetFont('Helvetica', 'I', 8);
        $this->SetTextColor(...self::SUAVE);
        $this->MultiCell(0, 4.2, pdf_texto($aviso));
        $this->SetTextColor(...self::TEXTO);
        $this->Ln(1);
    }

    public function observacao(string $texto): void
    {
        $this->SetFont('Helvetica', 'I', 10);
        $this->SetTextColor(...self::SUAVE);
        $this->MultiCell(0, 5.5, pdf_texto($texto));
        $this->SetTextColor(...self::TEXTO);
        $this->Ln(1);
    }
}

/**
 * Monta o PDF e devolve o conteúdo.
 * $d: usuario, ultimo_imc (ou null), treino (ou null), dieta (ou null), faltando (lista).
 */
function gerar_pdf_do_perfil(array $d): string
{
    $u = $d['usuario'];
    $data = fn(string $iso): string => (new DateTime($iso))->format('d/m/Y');

    $pdf = new PerfilPdf('P', 'mm', 'A4');
    $pdf->nomePessoa = $u['nome'];
    $pdf->geradoEm = date('d/m/Y \à\s H:i');
    $pdf->SetTitle('Perfil de ' . $u['nome'] . ' - LifeMap', true);
    $pdf->SetAuthor('LifeMap', true);
    $pdf->SetCreator('LifeMap', true);
    $pdf->SetMargins(16, 33, 16);
    $pdf->SetAutoPageBreak(true, 20);
    $pdf->AliasNbPages();
    $pdf->AddPage();

    // --- Dados pessoais ---
    $pdf->secao('Dados pessoais', PerfilPdf::VERDE);
    $idade = idade_de($u['data_nascimento']);
    $pdf->campo('Nome', $u['nome']);
    $pdf->campo('E-mail', $u['email']);
    $pdf->campo('Telefone', $u['telefone']);
    $pdf->campo('Gênero', ROTULOS_GENERO[$u['genero']] ?? $u['genero']);
    $pdf->campo('Data de nascimento', $data($u['data_nascimento']) . ($idade !== null ? " ($idade anos)" : ''));
    $pdf->campo('Altura', $u['altura'] !== null ? formatar_decimal((float) $u['altura'], 2) . ' m' : 'Não informada');
    $pdf->campo('Objetivo', $u['objetivo'] ? ROTULOS_OBJETIVO[$u['objetivo']] : 'Não definido');
    $pdf->campo('Problema de saúde', trim((string) $u['problema_saude']) !== '' ? $u['problema_saude'] : 'Nenhum informado');

    // --- IMC ---
    $pdf->secao('IMC mais recente', PerfilPdf::VERDE);
    if ($d['ultimo_imc']) {
        $imc = (float) $d['ultimo_imc']['imc'];
        $pdf->campo('IMC', formatar_decimal($imc) . ' - ' . imc_categoria($imc)['nome']);
        $pdf->campo('Peso e altura', formatar_peso((float) $d['ultimo_imc']['peso']) . ' kg  |  ' . formatar_decimal((float) $d['ultimo_imc']['altura'], 2) . ' m');
        $pdf->campo('Registrado em', $data($d['ultimo_imc']['criado_em']));
    } else {
        $pdf->observacao('Nenhum cálculo de IMC salvo ainda.');
    }

    // --- Avaliação física (foto + resultado) ---
    $pdf->secao('Avaliação física mais recente', PerfilPdf::VERDE);
    $avaliacao = $d['avaliacao'] ?? null;
    $arquivoFoto = $avaliacao ? caminho_foto_avaliacao($avaliacao['arquivo']) : '';
    if ($avaliacao && is_file($arquivoFoto)) {
        $leitura = interpretar_avaliacao($avaliacao);
        $pdf->campo('Feita em', (new DateTime($avaliacao['criado_em']))->format('d/m/Y \à\s H:i'));
        $comparacao = null;
        if (!empty($d['avaliacao_anterior'])) {
            $comparacao = frase_de_comparacao(
                comparar_avaliacoes($avaliacao, $d['avaliacao_anterior']),
                (new DateTime($d['avaliacao_anterior']['criado_em']))->format('d/m')
            );
        }
        $pdf->avaliacaoFisica($leitura['itens'], $arquivoFoto, $leitura['resumo'], $comparacao, AVALIACAO_AVISO);
    } else {
        $pdf->observacao('Nenhuma avaliação física feita ainda. Use o Avaliador de físico para guardar a foto e o resultado no seu perfil.');
    }

    // --- Treino e dieta ---
    if ($d['faltando']) {
        $pdf->secao('Treino e dieta indicados', PerfilPdf::AMBAR);
        $motivos = [];
        if (in_array('objetivo', $d['faltando'], true)) {
            $motivos[] = 'Escolha seu objetivo no perfil.';
        }
        if (in_array('maioridade', $d['faltando'], true)) {
            $motivos[] = 'As sugestões atuais são para maiores de 18 anos.';
        }
        $pdf->observacao('O treino e a dieta indicados aparecem aqui quando o perfil está completo.');
        $pdf->lista($motivos, PerfilPdf::AMBAR);
    } else {
        $treino = $d['treino'];
        $dieta = $d['dieta'];
        $etiquetas = [
            ROTULOS_OBJETIVO[$treino['objetivo']],
            ROTULOS_GENERO[$treino['genero']],
            ROTULOS_FAIXA_ETARIA[$treino['faixa_etaria']],
        ];

        $pdf->secao('Treino indicado', PerfilPdf::AMBAR);
        $pdf->etiquetas($etiquetas, PerfilPdf::AMBAR);
        foreach ($treino['recomendados'] as $categoria => $exercicios) {
            $pdf->subtitulo($categoria);
            $pdf->lista($exercicios, PerfilPdf::AMBAR);
        }

        // Mantém a dieta inteira na mesma página, se couber (altura aproximada em mm).
        $alturaDieta = 24 + 9 + 9 + ceil(count($dieta['recomendados']) / 2) * 5.4 + 9 + ceil(count($dieta['evitar']) / 2) * 5.4
            + ($treino['problema_saude'] !== '' ? 24 : 0);
        $pdf->garantirEspaco(min($alturaDieta, 230));

        $pdf->secao('Dieta indicada', PerfilPdf::FOLHA);
        $pdf->etiquetas($etiquetas, PerfilPdf::FOLHA);
        $pdf->subtitulo('Alimentos recomendados');
        $pdf->listaEmColunas($dieta['recomendados'], PerfilPdf::FOLHA);
        $pdf->subtitulo('Alimentos a evitar');
        $pdf->listaEmColunas($dieta['evitar'], PerfilPdf::VERMELHO, "\xD7");

        if ($treino['problema_saude'] !== '') {
            $pdf->aviso('Atenção: você informou "' . $treino['problema_saude'] . '". Consulte um médico, educador físico ou nutricionista antes de iniciar o treino ou mudar sua alimentação.');
        }
    }

    return $pdf->Output('S');
}
