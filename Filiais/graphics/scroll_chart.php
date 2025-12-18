<?php
include_once("includes/table_register.php");

function calcularDiferenca($nome, $amostrasMesAtual, $amostrasMesPassado) {
    $qtdAtual = $amostrasMesAtual[$nome] ?? 0;
    $qtdPassado = $amostrasMesPassado[$nome] ?? 0;

    if ($qtdPassado == 0) {
        if ($qtdAtual == 0) {
            return ['df' => 0, 'st' => 'none', 's' => ''];
        } else {
            return ['df' => 100, 'st' => 'up', 's' => '▲'];
        }
    }

    $df = (($qtdAtual * 100) / $qtdPassado) - 100;
    $st = ($df > 0) ? "up" : "down";
    $s = ($df > 0) ? "▲" : "▼";
    return compact('df', 'st', 's');
}

$dadosEmpresas = [];
foreach ($empresas as $nome) {
    $dadosEmpresas[$nome] = calcularDiferenca($nome, $amostrasMesAtual, $amostrasMesPassado);
}
?>
<div class="ticker-container">
    <div class="ticker-wrapper" style="animation-duration: <?= $duracao ?>s;">
        <?php
        if (!empty($empresas)) {
            foreach ($empresas as $empresa) {
                $qtdAtual = $amostrasMesAtual[$empresa] ?? 0;
                $qtdPassado = $amostrasMesPassado[$empresa] ?? 0;
                $df = $dadosEmpresas[$empresa]['df'];
                $s = $dadosEmpresas[$empresa]['s'];

                echo "<span class='ticker-item'>
                        <a href='view_table.php?empresa=" . urlencode($empresa) . "' target='_blank' style='text-decoration:none; color:inherit;'>
                            {$empresa}
                        </a>: {$qtdAtual} (Mês Atual) / {$qtdPassado} (Mês Passado) 
                        <span class='{$dadosEmpresas[$empresa]['st']}'>
                            {$s} " . round($df, 1) . "%
                        </span>
                      </span>";
            }
        } else {
            echo '<span class="ticker-item ok">Nenhuma amostra encontrada</span>';
        }
        ?>
    </div>
</div>

