<?php

include_once("unidade.php");

function criarData($dataStr) {
    if (empty($dataStr)) {
        return false;
    }

    $dataStr = trim($dataStr);

    $formatos = ['d/m/Y H:i', 'd/m/Y H:i:s', 'd/m/Y', 'Y-m-d H:i', 'Y-m-d H:i:s'];

    foreach ($formatos as $formato) {
        $dt = DateTime::createFromFormat($formato, $dataStr);
        if ($dt !== false) {
            return $dt;
        }
    }

    return false;
}

$arquivo = "data/teste.csv";
if (!file_exists($arquivo)) {
    die("Erro: O arquivo '$arquivo' não foi encontrado.");
}

$file = fopen($arquivo, "r");
$primeiraLinha = true;
$registros = [];

while (($linha = fgetcsv($file, 100000, ";")) !== false) {
    if ($primeiraLinha) {
        $primeiraLinha = false;
        continue;
    }

    $empresas = trim($linha[7] ?? '');
    $dataChegada = trim($linha[3] ?? '');
    $dataSituacaoInicial = trim($linha[10] ?? '');
    $cidade = trim($linha[5] ?? '');

    if (stripos($cidade, $unidadeServico) !== false) {
        $dataObjC = criarData($dataChegada);
        $dataObjD = criarData($dataSituacaoInicial);

        $dataObjC = criarData($dataChegada);
        $dataObjD = criarData($dataSituacaoInicial);

        if ($dataObjC && $dataObjD) {
            if (strlen($dataChegada) === 10) { 
                $dataObjC->setTime(0,0,0);
            }

            if (strlen($dataSituacaoInicial) === 10) { 
                $dataObjD->setTime(0,0,0);
            }

            $interval = $dataObjC->diff($dataObjD);
            $diferencaDias = (int)$interval->format('%a');

            $status = ($diferencaDias >= 3) ? "Atraso" : "No Prazo";

        } else {
            $status = "Data inválida";
            $diferencaDias = null;
        }

        $registros[] = [
            'Status' => $status,
            'Codigo' => trim($linha[0]),
            'Recebimento' => trim($linha[10]),
            'Numero' => trim($linha[1]),
            'Grupo' => trim($linha[2]),
            'Chegada' => trim($linha[3]),
            'Unidade' => trim($linha[5]),
            'Dias' => $diferencaDias,
            'Pendencia' => trim($linha[8]),
            'Empresas' => $empresas,
            'Coleta' => trim($linha[9]),
            'Entrega' => trim($linha[11])
        ];
    }
}

fclose($file);

$mesAtual = (int)date('m');
$anoAtual = (int)date('Y');

$totalAtrasados = 0;
$totalNoPrazo = 0;
$total = 0;

foreach ($registros as $r) {
    $dataObj = criarData($r['Recebimento']);
    if ($dataObj) {
        $mesRegistro = (int)$dataObj->format('m');
        $anoRegistro = (int)$dataObj->format('Y');

        if ($mesRegistro === $mesAtual && $anoRegistro === $anoAtual) {
            if ($r['Status'] === "Atraso") {
                $totalAtrasados++;
            } elseif ($r['Status'] === "No Prazo") {
                $totalNoPrazo++;
            }

            if ($r['Status'] === "Atraso" || $r['Status'] === "No Prazo") {
                $total++;
            }
        }
    }
}

$atrasosMesAtual = [];
$atrasosMesPassado = [];

$mesPassado = $mesAtual - 1;
$anoPassado = $anoAtual;
if ($mesPassado === 0) {
    $mesPassado = 12;
    $anoPassado--;
}

foreach ($registros as $r) {
    $status = isset($r['Status']) ? trim($r['Status']) : '';
    $empresa = isset($r['Empresas']) ? trim($r['Empresas']) : '';
    $data = isset($r['Recebimento']) ? trim($r['Recebimento']) : '';

    if ($status === 'Atraso' && $empresa !== '' && $data !== '') {
        $dataObj = criarData($data);

        if ($dataObj) {
            $mesRegistro = (int)$dataObj->format('m');
            $anoRegistro = (int)$dataObj->format('Y');

            if ($mesRegistro === $mesAtual && $anoRegistro === $anoAtual) {
                $atrasosMesAtual[$empresa] = ($atrasosMesAtual[$empresa] ?? 0) + 1;
            }

            if ($mesRegistro === $mesPassado && $anoRegistro === $anoPassado) {
                $atrasosMesPassado[$empresa] = ($atrasosMesPassado[$empresa] ?? 0) + 1;
            }
        }
    }
}

$empresas = array_unique(array_merge(array_keys($atrasosMesAtual), array_keys($atrasosMesPassado)));

$contagensTotais = [];
foreach ($registros as $registro) {
    $metodo = $registro['Dias'] ?? null;
    $dataStr = $registro['Recebimento'] ?? null;

    if ($metodo !== null && $dataStr) {
        $dataObj = criarData($dataStr);
        if ($dataObj) {
            $mesRegistro = (int)$dataObj->format('m');
            $anoRegistro = (int)$dataObj->format('Y');

            if ($mesRegistro === $mesAtual && $anoRegistro === $anoAtual) {
                $contagensTotais[$metodo] = ($contagensTotais[$metodo] ?? 0) + 1;
            }
        }
    }
}

$hoje = new DateTime();
$diaSemana = (int)$hoje->format('w');

$inicioSemana = (clone $hoje)->modify("-$diaSemana days")->setTime(0,0,0);
$fimSemana = (clone $inicioSemana)->modify('+6 days')->setTime(23,59,59);

$totalAtrasadosSemana = 0;
$totalNoPrazoSemana = 0;
$totalSemana = 0;

foreach ($registros as $r) {
    $status = isset($r['Status']) ? trim($r['Status']) : '';
    $dataStr = isset($r['Recebimento']) ? trim($r['Recebimento']) : '';

    if ($status !== '' && $dataStr !== '') {
        $dataObj = criarData($dataStr);
        if ($dataObj) {
            if ($dataObj >= $inicioSemana && $dataObj <= $fimSemana) {
                if ($status === "Atraso") $totalAtrasadosSemana++;
                elseif ($status === "No Prazo") $totalNoPrazoSemana++;

                if ($status === "Atraso" || $status === "No Prazo") $totalSemana++;
            }
        }
    }
}

$amostrasMesAtual = [];
$amostrasMesPassado = [];

foreach ($registros as $r) {
    $empresa = $r['Empresas'] ?? '';
    $data    = $r['Recebimento'] ?? '';

    if ($empresa !== '' && $data !== '') {
        $dataObj = criarData($data);

        if ($dataObj) {
            $mesRegistro = (int)$dataObj->format('m');
            $anoRegistro = (int)$dataObj->format('Y');

            if ($mesRegistro === $mesAtual && $anoRegistro === $anoAtual) {
                $amostrasMesAtual[$empresa] = ($amostrasMesAtual[$empresa] ?? 0) + 1;
            }

            if ($mesRegistro === $mesPassado && $anoRegistro === $anoPassado) {
                $amostrasMesPassado[$empresa] = ($amostrasMesPassado[$empresa] ?? 0) + 1;
            }
        }
    }
}

$empresas = array_unique(array_merge(array_keys($amostrasMesAtual), array_keys($amostrasMesPassado)));

$totalEmpresas = count($empresas);
$duracao = ($totalEmpresas*5);
?>
