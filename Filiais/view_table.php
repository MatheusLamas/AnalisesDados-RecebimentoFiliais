<?php
include_once("includes/table_register.php");
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
        
    .dt-buttons {
        margin-left: 50px;
    }

    .dt-button.buttons-excel {
        background-color: #007bff !important;
        color: white !important;
        border-radius: 4px;
        padding: 6px 12px;
        border: none;
        cursor: pointer;
        font-weight: bold;
    }

    .dt-button.buttons-excel:hover {
        background-color: #0056b3 !important;
    }
    </style>
<body>

<h2>Tabela de Registros</h2>

<div class="table-wrapper">
    <table id="tabela-dados" class="display nowrap">
        <thead>
            <tr>
                <th>Código</th>
                <th>Grupo</th>
                <th>Nº Amostra</th>
                <th>Data Coleta</th>
                <th>Chegada</th>
                <th>Data Recebimento</th>
                <th>Previsão Entrega</th>
                <th>Unidade Serviço</th>
                <th>Empresas</th>
                <th>Pendência</th>
                <th>Dias Atrasos</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($registros as $registro): ?>
                <tr>
                    <td><?= htmlspecialchars($registro['Codigo']) ?></td>
                    <td><?= htmlspecialchars($registro['Grupo']) ?></td>
                    <td><?= htmlspecialchars($registro['Numero']) ?></td>
                    <td><?= htmlspecialchars($registro['Coleta']) ?></td>
                    <td><?= htmlspecialchars($registro['Chegada']) ?></td>
                    <td><?= htmlspecialchars($registro['Recebimento']) ?></td>
                    <td><?= htmlspecialchars($registro['Entrega']) ?></td>
                    <td><?= htmlspecialchars($registro['Unidade']) ?></td>
                    <td><?= htmlspecialchars($registro['Empresas']) ?></td>
                    <td><?= htmlspecialchars($registro['Pendencia']) ?></td>
                    <td><?= htmlspecialchars($registro['Dias']) ?></td>
                    <td><?= htmlspecialchars($registro['Status']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/colresize/1.0.0/dataTables.colResize.min.js"></script>

<!-- Scripts do DataTables Buttons -->
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

<script>
// Função para pegar parâmetro da URL
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
    var statusFilter = getUrlParameter('status');   
    var semanaFilter = getUrlParameter('semana');   
    var diasFilter   = getUrlParameter('dias');     
    var empresaFilter = getUrlParameter('empresa'); 
    var mesFilter = getUrlParameter('mes'); 

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
        dom: 'lBfrtip', // l = dropdown de linhas, B = botões
        buttons: [
            {
                extend: 'excelHtml5',
                text: 'Baixar Excel',
                title: '',
                exportOptions: {
                    columns: ':visible'
                }
            }
        ]
    });

    // Aplicar filtros por URL
    if (statusFilter) table.column(11).search(statusFilter).draw();
    if (diasFilter) table.column(10).search(diasFilter).draw();
    if (empresaFilter) table.column(8).search(empresaFilter).draw();

    // Filtro customizado para semana atual
    if (semanaFilter === 'atual') {
        $.fn.dataTable.ext.search.push(function(settings, data) {
            var dataCol = data[5]; 
            if (!dataCol) return false;
            var partes = dataCol.split(/[\s/:]/);
            if (partes.length < 3) return false;
            var registroData = new Date(parseInt(partes[2]), parseInt(partes[1])-1, parseInt(partes[0]));
            var hoje = new Date();
            var diaSemana = hoje.getDay();
            var inicioSemana = new Date(hoje);
            inicioSemana.setDate(hoje.getDate() - diaSemana);
            inicioSemana.setHours(0,0,0,0);
            var fimSemana = new Date(inicioSemana);
            fimSemana.setDate(inicioSemana.getDate() + 6);
            fimSemana.setHours(23,59,59,999);
            return registroData >= inicioSemana && registroData <= fimSemana;
        });
    }

    // Filtro customizado para mês atual
    if (mesFilter === 'atual') {
        $.fn.dataTable.ext.search.push(function(settings, data) {
            var dataCol = data[5];
            if (!dataCol) return false;
            var partes = dataCol.split(/[\s/:]/);
            if (partes.length < 3) return false;
            var registroData = new Date(parseInt(partes[2]), parseInt(partes[1])-1, parseInt(partes[0]));
            var hoje = new Date();
            var inicioMes = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
            var fimMes = new Date(hoje.getFullYear(), hoje.getMonth()+1, 0, 23,59,59,999);
            return registroData >= inicioMes && registroData <= fimMes;
        });
    }

    table.draw();
});
</script>

</body>
</html>
