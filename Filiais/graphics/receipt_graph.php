<?php
include_once("includes/table_register.php");

$dadosGrafico = [];

foreach ($contagensTotais as $metodo => $quantidade) {
    $dadosGrafico[] = [
        'label' => $metodo,
        'valor' => $quantidade
    ];
}

usort($dadosGrafico, function($a, $b) {
    return (int)$a['label'] <=> (int)$b['label'];
});
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Gráfico de Barras - Análises Totais</title>
    <style>
        #chart {
            width: 100%;
            height: 50vh;
            border: 3px solid #002244;
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 10px 20px 20px 20px;
            margin-top: 30px;
            box-shadow: 2px 2px 8px rgba(0,0,0,0.1);
            box-sizing: border-box;
        }

        .chart-title {
            font-size: 18px;
            font-weight: bold;
            color: black;
            background-color: #FFFFFF;
            width: 100%;
            text-align: center;
            padding: 6px;
            border-radius: 6px 6px 0 0;
            margin-bottom: 10px;
            box-sizing: border-box;
        }

        .bars-area {
            flex: 1;
            display: flex;
            align-items: flex-end;
            justify-content: space-around;
            width: 100%;
            box-sizing: border-box;
        }

        .bar-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            height: 100%;
            justify-content: flex-end;
            position: relative;
        }

        .value {
            font-size: 12px;
            color: #333;
            margin-bottom: 4px;
            cursor: pointer; /* cursor muda ao passar sobre o valor */
        }

        .bar {
            text-align: center;
            color: white;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            font-size: 12px;
            border-radius: 4px 4px 0 0;
            transition: 0.3s;
            max-width: 80%;
            cursor: pointer; /* cursor muda ao passar sobre a barra */
        }

        .label {
            margin-top: 5px;
            font-size: 8.5px;
            color: #333;
            text-align: center;
            line-height: 1.1;
            padding: 4px;
            word-wrap: break-word;
            max-width: 100%;
        }
    </style>
</head>
<body>

<div id="chart">
    <div class="chart-title">Recebimento do Mês</div>
    <div class="bars-area" id="bars"></div>
</div>

<script>
(function(){
    const dados = <?php echo json_encode($dadosGrafico, JSON_UNESCAPED_UNICODE); ?>;
    const chart = document.getElementById('bars');

    function desenharGrafico() {
        chart.innerHTML = ''; 

        const maxValor = Math.max(...dados.map(d => d.valor));
        const totalMetodos = dados.length;
        const chartWidth = chart.clientWidth;
        const larguraBarra = chartWidth / (totalMetodos * 1.5);

        dados.forEach(dado => {
            const container = document.createElement('div');
            container.classList.add('bar-container');
            container.style.width = `${larguraBarra}px`;

            // Valor acima da barra
            const value = document.createElement('div');
            value.classList.add('value');
            value.textContent = dado.valor;

            // Barra
            const bar = document.createElement('div');
            bar.classList.add('bar');
            bar.style.width = '100%';
            bar.style.height = `${(dado.valor / maxValor) * 100}%`;

            // Cor condicional
            const corOriginal = parseInt(dado.label) >= 3 ? 'red' : 'steelblue';
            bar.style.backgroundColor = corOriginal;

            // Função de clique (barra e valor)
            const redirecionar = () => {
                window.open(`view_table.php?mes=atual&dias=${dado.label}`, '_blank');
            };
            bar.addEventListener('click', redirecionar);
            value.addEventListener('click', redirecionar);

            // Hover para barra
            bar.addEventListener('mouseenter', () => {
                if (corOriginal === 'red') bar.style.backgroundColor = '#ff6666';
                else if (corOriginal === 'steelblue') bar.style.backgroundColor = '#6ea0f0';
            });
            bar.addEventListener('mouseleave', () => {
                bar.style.backgroundColor = corOriginal;
            });

            // Label abaixo da barra
            const label = document.createElement('div');
            label.classList.add('label');
            label.textContent = dado.label;

            container.appendChild(value);
            container.appendChild(bar);
            container.appendChild(label);

            chart.appendChild(container);
        });
    }

    desenharGrafico();
    window.addEventListener('resize', desenharGrafico);
})();
</script>

</body>
</html>
