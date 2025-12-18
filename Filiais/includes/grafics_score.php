<?php
include_once("data_score.php");
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Gráfico de Pontuação</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    #graficoContainer {
        width: 90%;
        max-width: 900px;
        height: 400px;
        margin: 20px auto;
    }
    #Title_score {
        margin: -8px;
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

<h2 id=Title_score><?php echo $unidadeDesejada; ?></h2>

<label for="mesSelect">Selecione o mês:</label>
<select id="mesSelect"></select>
<button onclick="mostrarTotalPorMes()">Ver total por mês</button>

<div id="graficoContainer">
    <canvas id="graficoPontuacao"></canvas>
</div>

<script>
const dadosDia = <?php echo $dadosDiaJson; ?>;
const dadosMes = <?php echo $dadosMesJson; ?>;
const mesesDisponiveis = <?php echo $mesesDisponiveisJson; ?>;

const mesSelect = document.getElementById('mesSelect');
mesesDisponiveis.forEach(mes => {
    const option = document.createElement('option');
    option.value = mes;
    option.text = mes;
    mesSelect.appendChild(option);
});

const ctx = document.getElementById('graficoPontuacao').getContext('2d');
let grafico = new Chart(ctx, {
    type: 'line',
    data: { labels: [], datasets: [{ label: 'Pontuação', data: [], borderColor: 'blue', backgroundColor: 'rgba(0,0,255,0.1)', fill: true, tension: 0.3 }] },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { title: { display: true, text: 'Data' } }, y: { title: { display: true, text: 'Pontuação' } } } }
});

function mostrarTotalPorMes() {
    grafico.data.labels = Object.keys(dadosMes);
    grafico.data.datasets[0].data = Object.values(dadosMes);
    grafico.update();
}

function mostrarPorDiaMes(mesSelecionado) {
    const detalhes = {};
    const [mes, ano] = mesSelecionado.split('/'); 
    for (const [data, valor] of Object.entries(dadosDia)) {
        const partes = data.split('/'); 
        const diaData = partes[0];
        const mesData = partes[1];
        const anoData = partes[2];

        if (mesData === mes && anoData === ano) { 
            detalhes[data] = valor;
        }
    }
    grafico.data.labels = Object.keys(detalhes);
    grafico.data.datasets[0].data = Object.values(detalhes);
    grafico.update();
}

mesSelect.addEventListener('change', function() {
    mostrarPorDiaMes(this.value);
});

mesSelect.value = mesesDisponiveis[mesesDisponiveis.length-1];
mostrarPorDiaMes(mesesDisponiveis[mesesDisponiveis.length-1]);
</script>

</body>
</html>
