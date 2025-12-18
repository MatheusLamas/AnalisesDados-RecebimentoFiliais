<?php
    include_once("templates/header.php");

    $mesDesejado = date("m/Y");

    include_once("includes/data_score.php")
?>
<div>
    <a href="view_table.php" target="_blank" style="text-decoration:none; color:inherit;">
        <h1 id="title" style="cursor:pointer;"><?= $unidadeServico?></h1>
        <span id='update'>Atualizado em: <?= $data_modificacao ?></span>
    </a>
</div>

<div class="scroll_chart">
    <div id="scroll_chart"><?php include_once("graphics/scroll_chart.php")?></div>
</div>

<div id="up">
    <div id="monthly-chart"><?php include_once("graphics/monthly_chart.php")?></div>

    <div class="Sub-title-container">

        <a href="view_table.php?mes=atual" target="_blank" style="text-decoration:none; color:inherit;">
            <div class="sub-box" style="cursor:pointer;"> 
                <h2 class=numeracao><?= $total?></h2>  
                <p class=sub-title>Total de Amostras Recebidas</p> 
            </div>
        </a>

        <div class="sub-box-sub">
            <a href="view_table.php?mes=atual&status=Atraso" target="_blank" style="text-decoration:none; color:inherit;">
                <div class="box" style="cursor:pointer;">
                    <h2 class=numeracao><?= $totalAtrasados?></h2> 
                    <p class=sub-title>Total de Amostras Recebidas em Atraso</p> 
                </div>
            </a>

            <a href="view_table.php?mes=atual&status=No%20Prazo" target="_blank" style="text-decoration:none; color:inherit;">
                <div class="box" style="cursor:pointer;"> 
                    <h2 class=numeracao><?= $totalNoPrazo?></h2>  
                    <p class=sub-title>Total de Amostras Recebidas no Prazo</p> 
                </div>
            </a>
        </div>

        <a href="view_table_gerencia.php" target="_blank" style="text-decoration:none; color:inherit;">
            <div class="sub-box" style="cursor:pointer;"> 
                <h2 class=numeracao><?= $pontuacaoPorMes[$mesDesejado]?></h2>  
                <p class=sub-title>Total de Pontos da ABA Gerência</p> 
            </div>
        </a>
    </div>

    <div id="weekly-chart"><?php include_once("graphics/weekly_chart.php")?></div>
</div>

<div id="down">
    <div id="receipt-graph"><?php include_once("graphics/receipt_graph.php")?></div>
    <div id="management-tab-chart"><?php include_once("graphics/management_tab_chart.php")?></div>
</div>

<?php
    include_once("templates/footer.php");
?>
