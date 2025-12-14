<?php
include_once("includes/unidade.php");

$arquivo = "data/Gerencia.csv";

if (!file_exists($arquivo)) {
    die("Erro: O arquivo '$arquivo' não foi encontrado. Por favor, coloque o Gerencia.csv na pasta 'data'.");
}

function normalizar($texto) {
    return strtolower(trim(preg_replace('/\s+/', ' ', $texto)));
}

$registros = [];
$file = fopen($arquivo, "r");
$primeiraLinha = true;
$hoje = new DateTime();

while (($linha = fgetcsv($file, 100000, ";")) !== false) {
    if ($primeiraLinha) { $primeiraLinha = false; continue; }

    $dataDistribuicao = trim($linha[7] ?? '');
    if (strpos($dataDistribuicao, 'T') !== false) {
        $dataDistribuicao = explode('T', $dataDistribuicao)[0];
    }
    $dataDistribuicao = date("d/m/Y H:i", strtotime($dataDistribuicao));

    $codigoAmostra = trim($linha[0] ?? '');
    $metodo        = trim($linha[3] ?? '');
    $laboratorio   = trim($linha[6] ?? '');
    $unidade       = trim($linha[9] ?? '');
    $numero        = trim($linha[11] ?? '');
    $grupo         = trim($linha[1] ?? '');
    $coleta        = trim($linha[4] ?? '');
    $entrega       = trim($linha[5] ?? '');
    $motivo        = trim($linha[12] ?? '');
    $dataChegada   = trim($linha[12] ?? '');

    $dataObj = DateTime::createFromFormat('d/m/Y H:i', $dataDistribuicao);
    $intervalo = $dataObj ? $dataObj->diff($hoje)->days : 0;
    $status = $dataObj ? (($intervalo > 3 && $hoje >= $dataObj) ? "Atraso" : "No Prazo") : "Data inválida";

    $registros[] = [
        'Codigo'      => $codigoAmostra,
        'Metodo'      => $metodo,
        'Laboratorio' => $laboratorio,
        'Unidade'     => $unidade,
        'Status'      => $status,
        'Data'        => $dataDistribuicao,
        'Intervalo'   => $intervalo,
        'Numero'      => $numero,
        'Grupo'       => $grupo,
        'Coleta'      => $coleta,
        'Entrega'     => $entrega,
        'Motivo'      => $motivo
    ];
}
fclose($file);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Tabela de Registros</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/colresize/1.0.0/dataTables.colResize.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .table-wrapper { overflow-x: auto; width: 100%; }
        table.dataTable th, table.dataTable td { white-space: nowrap; }
        table { width: 100%; border-collapse: collapse; }
        h2 { margin-bottom: 15px; }
        .dt-buttons { margin-left: 15px; }

        .dt-button.buttons-excel {
            background-color: #007bff !important;
            color: white !important;
            border-radius: 4px;
            padding: 6px 12px;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }
        .dt-button.buttons-excel:hover { background-color: #0056b3 !important; }

        .dt-button.buttons-ranking {
            background-color: #28a745 !important;
            color: white !important;
            border-radius: 4px;
            padding: 6px 12px;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }
        .dt-button.buttons-ranking:hover {
            background-color: #1e7e34 !important;
        }
    </style>
</head>
<body>

<h2>Tabela de Registros</h2>

<div class="table-wrapper">
    <table id="tabela-dados" class="display nowrap">
        <thead>
            <tr>
                <th>Código</th>
                <th>Grupo</th>
                <th>Nº Amostra</th>
                <th>Metodo</th>
                <th>Laboratório</th>
                <th>Data Coleta</th>
                <th>Data Entrega</th>
                <th>Unidade Serviço</th>
                <th>Data Distribuição</th>
                <th>Dias na Gerência</th>
                <th>Motivo</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($registros as $registro): ?>
                <tr>
                    <td><?= htmlspecialchars($registro['Codigo']) ?></td>
                    <td><?= htmlspecialchars($registro['Grupo']) ?></td>
                    <td><?= htmlspecialchars($registro['Numero']) ?></td>
                    <td><?= htmlspecialchars($registro['Metodo']) ?></td>
                    <td><?= htmlspecialchars($registro['Laboratorio']) ?></td>
                    <td><?= htmlspecialchars($registro['Coleta']) ?></td>
                    <td><?= htmlspecialchars($registro['Entrega']) ?></td>
                    <td><?= htmlspecialchars($registro['Unidade']) ?></td>
                    <td><?= htmlspecialchars($registro['Data']) ?></td>
                    <td><?= htmlspecialchars($registro['Intervalo']) ?></td>
                    <td><?= htmlspecialchars($registro['Motivo']) ?></td>
                    <td><?= htmlspecialchars($registro['Status']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/colresize/1.0.0/dataTables.colResize.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

<script>
function getUrlParameter(name) {
    name = name.replace(/[\[\]]/g, '\\$&');
    const url = window.location.href;
    const regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)');
    const results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, ' '));
}

$(document).ready(function() {
    var unidadeFilter = "<?= isset($unidadeServico) ? $unidadeServico : '' ?>";

    var diasFilter = getUrlParameter('Inter'); 

    var table = $('#tabela-dados').DataTable({
        autoWidth: true,
        colResize: true,
        scrollY: "75vh",
        fixedHeader: true,
        pageLength: 100, 
        scrollX: true, 
        language: {
            search: "Buscar:",
            lengthMenu: "Mostrar _MENU_ registros por página",
            info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
            paginate: { next: "Próximo", previous: "Anterior" },
            zeroRecords: "Nenhum registro encontrado"
        },
        dom: 'lBfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: 'Baixar Excel',
                title: '', 
                exportOptions: { columns: ':visible' }
            },
            {
                text: 'Ranking',
                action: function () {
                    window.location.href = 'includes/table_score.php';
                },
                className: 'dt-button buttons-ranking'
            }
        ]
    });

    if (unidadeFilter) {
        table.column(7).search(unidadeFilter).draw();
    }

    if (diasFilter) table.column(9).search(diasFilter).draw();
    table.draw();
});
</script>

</body>
</html>
