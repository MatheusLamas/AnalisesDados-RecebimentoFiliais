<?php
include_once("unidade.php");

$arquivo = __DIR__ . "/dados.txt"; // caminho absoluto para evitar erros
$unidadeDesejada = $_GET['unidade'] ?? $unidadeServico;

if (!file_exists($arquivo)) {
    die("Erro: Arquivo '$arquivo' não encontrado!");
}

$linhas = file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
array_shift($linhas);  

$pontuacaoPorDia = [];
$pontuacaoPorMes = [];

foreach ($linhas as $linha) {
    $dados = str_getcsv($linha, ";");
    $unidade = $dados[0];
    $dataHora = DateTime::createFromFormat("d/m/Y H:i:s", $dados[1]);
    $pontuacao = (float)$dados[2];

    if ($unidade !== $unidadeDesejada) continue;

    // Pontuação por dia
    $chaveDia = $dataHora->format("d/m/Y");
    if (!isset($pontuacaoPorDia[$chaveDia])) $pontuacaoPorDia[$chaveDia] = 0;
    $pontuacaoPorDia[$chaveDia] += $pontuacao;

    // Pontuação por mês
    $chaveMes = $dataHora->format("m/Y");
    if (!isset($pontuacaoPorMes[$chaveMes])) $pontuacaoPorMes[$chaveMes] = 0;
    $pontuacaoPorMes[$chaveMes] += $pontuacao;
}

ksort($pontuacaoPorDia);
uksort($pontuacaoPorMes, function($a, $b){
    $d1 = DateTime::createFromFormat('m/Y', $a);
    $d2 = DateTime::createFromFormat('m/Y', $b);
    return $d1 <=> $d2;
});

$dadosDiaJson = json_encode($pontuacaoPorDia);
$dadosMesJson = json_encode($pontuacaoPorMes);
$mesesDisponiveisJson = json_encode(array_keys($pontuacaoPorMes));

$mesDesejado = $mesDesejado ?? date("m/Y"); // usa variável do index ou padrão = mês atual

$linhas = file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
array_shift($linhas);

$pontuacaoPorMes = [];

foreach ($linhas as $linha) {
    $dados = str_getcsv($linha, ";");
    $unidade = $dados[0];
    $dataHora = DateTime::createFromFormat("d/m/Y H:i:s", $dados[1]);
    $pontuacao = (float)$dados[2];

    if ($unidade !== $unidadeDesejada) continue;

    $mesAno = $dataHora->format("m/Y");

    // filtra apenas o mês desejado
    if ($mesAno !== $mesDesejado) continue;

    if (!isset($pontuacaoPorMes[$mesAno])) $pontuacaoPorMes[$mesAno] = 0;
    $pontuacaoPorMes[$mesAno] += $pontuacao;
}
?>

