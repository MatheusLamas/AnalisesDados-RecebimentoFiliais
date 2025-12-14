<?php
include_once("unidade.php");

$arquivo = "dados.txt"; 
$mesDesejado = $_GET['mes'] ?? date("m/Y"); 
$unidades = [
    "Maceió",
    "São Paulo",
    "Fortaleza",
    "Pernambuco",
    "Contagem",
    "Porto Alegre",
    "Juiz de Fora",
    "Espírito Santo",
    "Cuiabá"
];

$linhas = file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
array_shift($linhas);

$acumuladoPorUnidade = [];

foreach ($unidades as $u) {
    $acumuladoPorUnidade[$u] = 0;
}

foreach ($linhas as $linha) {
    $dados = str_getcsv($linha, ";");
    $unidade = $dados[0];
    $dataHora = DateTime::createFromFormat("d/m/Y H:i:s", $dados[1]);
    $pontuacao = (float)$dados[2];

    $mesAno = $dataHora->format("m/Y");
    if ($mesAno === $mesDesejado && in_array($unidade, $unidades)) {
        $acumuladoPorUnidade[$unidade] += $pontuacao;
    }
}

arsort($acumuladoPorUnidade);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Ranking Mensal - Tabela</title>
<style>
    body { font-family: Arial, sans-serif; background: #f7f7f7; padding: 20px; }
    table { width: 100%; max-width: 800px; margin: auto; border-collapse: collapse; box-shadow: 0 4px 10px rgba(0,0,0,0.1); background: #fff; border-radius: 8px; overflow: hidden; }
    th, td { padding: 12px 20px; text-align: left; }
    th { background-color: #3498db; color: white; font-size: 16px; }
    tr:nth-child(even) { background-color: #f2f2f2; }
    tr:hover { background-color: #d6eaf8; cursor: pointer; }
    caption { caption-side: top; font-size: 20px; font-weight: bold; margin-bottom: 10px; }
    #Title_table_score {
    margin: -28px;
    margin-bottom: 10px;
    display: flex;
    align-items: left;
    justify-content: center;
    background-color:#002244;
    color: aliceblue;
    padding: 20px;
    font-size: 40px;
    list-style: none;
    }
</style>
</head>
<body>

<h1 id=Title_table_score >Ranking de Pontuação - <?php echo $mesDesejado; ?></h1>
<table>
    <thead>
        <tr>
            <th>Posição</th>
            <th>Unidade de Serviço</th>
            <th>Pontuação Acumulada</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $posicao = 1;
        foreach ($acumuladoPorUnidade as $unidade => $pontuacao) {
            // Cada linha terá onclick que redireciona para data_score.php com a unidade
            $url = "grafics_score.php?unidade=" . urlencode($unidade);
            echo "<tr onclick=\"window.location.href='{$url}'\">";
            echo "<td>{$posicao}</td>";
            echo "<td>{$unidade}</td>";
            echo "<td>{$pontuacao}</td>";
            echo "</tr>";
            $posicao++;
        }
        ?>
    </tbody>
</table>

</body>
</html>
