<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE); // Suprime warnings

date_default_timezone_set('America/Sao_Paulo');
include_once("r:...\\Filiais\\includes\\unidade.php");

// Lista de unidades
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

// Caminho absoluto do CSV
$arquivo = "r:...\\Filiais\\data\\Gerencia.csv";

if (!file_exists($arquivo)) {
    die("Erro: O arquivo '$arquivo' não foi encontrado. Por favor, coloque o Gerencia.csv nessa pasta.\n");
}

$pontuacoesGerencia = [];
$pontuacaoUnidadeFiltrada = 0;

function normalizar($texto) {
    return strtolower(trim(preg_replace('/\s+/', ' ', $texto)));
}

// Processa cada unidade
foreach ($unidades as $unidadeServicoAtual) {
    $file = fopen($arquivo, "r");
    $primeiraLinha = true;
    $registros = [];
    $porDia = [];            
    $codigosAmostra = [];    
    $hoje = new DateTime();

    while (($linha = fgetcsv($file, 100000, ";")) !== false) {
        if ($primeiraLinha) { $primeiraLinha = false; continue; }

        if (isset($linha[11]) && trim($linha[9]) === $unidadeServicoAtual) {
            $dataDistribuicao = trim($linha[7] ?? '');
            if (strpos($dataDistribuicao, 'T') !== false) {
                $dataDistribuicao = explode('T', $dataDistribuicao)[0];
            }
            $dataDistribuicao = date("d/m/Y H:i", strtotime($dataDistribuicao));
            $codigoAmostra    = trim($linha[0] ?? '');
            $metodo           = trim($linha[3] ?? '');
            $laboratorio      = trim($linha[6] ?? '');
            $unidade          = trim($linha[9] ?? '');
            $numero           = trim($linha[11] ?? '');
            $grupo            = trim($linha[1] ?? '');
            $coleta           = trim($linha[4] ?? '');
            $entrega          = trim($linha[5] ?? '');
            $motivo           = trim($linha[12] ?? '');

            $dataObj = DateTime::createFromFormat('d/m/Y H:i', $dataDistribuicao);

            if ($dataObj) {
                $intervalo = $dataObj->diff($hoje)->days;
                $status = ($intervalo > 3 && $hoje >= $dataObj) ? "Atraso" : "No Prazo";
            } else {
                $status = "Data inválida";
                $intervalo = 0;
            }

            $registros[] = [
                'Codigo'     => $codigoAmostra,
                'Metodo'     => $metodo,
                'Laboratorio'=> $laboratorio,
                'Unidade'    => $unidade,
                'Status'     => $status,
                'Data'       => $dataDistribuicao,
                'Intervalo'  => $intervalo,
                'Numero'     => $numero,
                'Grupo'      => $grupo,
                'Coleta'     => $coleta,
                'Entrega'    => $entrega,
                'Motivo'     => $motivo
            ];

            $codigosAmostra[] = $codigoAmostra;

            if (!isset($porDia[$intervalo])) {
                $porDia[$intervalo] = ['codigos' => []];
            }
            $porDia[$intervalo]['codigos'][$codigoAmostra] = true;
        }
    }

    fclose($file);

    krsort($porDia); 

    $labelGrafico   = [];
    $valoresGrafico = [];
    $amostrasPorDia = []; 

    foreach ($porDia as $dia => $info) {
        $qtdDistinctDia = count($info['codigos']); 

        $bonus = 1;
        if ($dia === 0) $bonus = 5;
        elseif ($dia === 1) $bonus = 3;
        elseif ($dia === 2) $bonus = 2;

        $valorBase = (int) ceil(((($dia == 0 ? 1 : $dia) * 100 + $qtdDistinctDia) / 10));
        $valor = $valorBase * $bonus;

        if ($dia > 3) {
            $valor = -$valor;
        }

        $labelGrafico[]   = $dia;
        $valoresGrafico[] = $valor;
        $amostrasPorDia[] = $qtdDistinctDia;
    }

    $pontuacao = array_sum($valoresGrafico);
    $pontuacoesGerencia[$unidadeServicoAtual] = $pontuacao;

    if (normalizar($unidadeServicoAtual) === normalizar($unidadeServico ?? '')) {
        $pontuacaoUnidadeFiltrada = $pontuacao;
        $labelGraficoSelecionado   = $labelGrafico;
        $valoresGraficoSelecionado = $valoresGrafico;
        $amostrasPorDiaSelecionado = $amostrasPorDia;
    }
}

// Monta pares para envio
$pontuacoesCompletas = [];
foreach ($unidades as $unidade) {
    $pontuacoesCompletas[$unidade] = $pontuacoesGerencia[$unidade] ?? 0;
}

$pares = [];
foreach ($pontuacoesCompletas as $unidade => $pontuacao) {
    $pares[] = ['unidade' => $unidade, 'pontuacao' => $pontuacao];
}

// Envia para API
$urlAPI = "http://10.192...:5000/escrever";

$agora = new DateTime();
$dataHora = $agora->format('d/m/Y H:i:s');

$todasLinhas = array_map(function($item) use ($dataHora) {
    return "{$item['unidade']};{$dataHora};{$item['pontuacao']}";
}, $pares);

$todasLinhas = implode("\n", $todasLinhas);

$ch = curl_init($urlAPI);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['texto' => $todasLinhas]));
$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "Erro ao enviar: $error\n";
} else {
    echo "Envio realizado com sucesso!\n";
}
