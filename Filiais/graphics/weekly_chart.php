<?php
include_once("includes/table_register.php");

$hoje = new DateTime();
$diaSemana = (int)$hoje->format('w'); // 0 = domingo
$inicioSemana = (clone $hoje)->modify("-$diaSemana days")->setTime(0,0,0);
$fimSemana = (clone $inicioSemana)->modify('+6 days')->setTime(23,59,59);
$inicioSemanaStr = $inicioSemana->format('Y-m-d');
$fimSemanaStr = $fimSemana->format('Y-m-d');

?>

<div id="chart-container2" style="width:100%; height:100%">
    <div id="title-piechart2" style="text-align:center; margin-bottom:8px;">Gráfico de recebimento na Semana</div>
    <canvas id="pieChart2"></canvas>
</div>

<script>
const canvas2 = document.getElementById('pieChart2');
const ctx2 = canvas2.getContext('2d');

let data2 = [
    { label: 'Atrasos', value: <?= $totalAtrasadosSemana ?> || 0, color: '#83cbeb', hoverColor: '#b3e0f5', originalColor: '#83cbeb' },
    { label: 'No Prazo', value: <?= $totalNoPrazoSemana ?> || 0, color: '#12239e', hoverColor: '#4d5ae6', originalColor: '#12239e' }
];

let hoveredSlice2 = null;

function drawChart2() {
    const container2 = document.getElementById('chart-container2');
    const titleHeight2 = document.getElementById('title-piechart2').offsetHeight;

    let rawWidth = container2.clientWidth;
    let rawHeight = container2.clientHeight - titleHeight2;
    const displayWidth2 = Math.max(rawWidth, 300);
    const displayHeight2 = Math.max(rawHeight, 200);

    const dpr = window.devicePixelRatio || 1;

    canvas2.width = displayWidth2 * dpr;
    canvas2.height = displayHeight2 * dpr;
    canvas2.style.width = displayWidth2 + 'px';
    canvas2.style.height = displayHeight2 + 'px';
    ctx2.setTransform(dpr, 0, 0, dpr, 0, 0);

    const centerX2 = displayWidth2 / 2;
    const centerY2 = displayHeight2 / 2;
    const radius2 = Math.min(centerX2, centerY2) * 0.8;

    const total2 = data2.reduce((sum, slice) => sum + slice.value, 0);
    let startAngle2 = -1 * Math.PI / 2;

    ctx2.clearRect(0, 0, canvas2.width, canvas2.height);

    data2.forEach(slice => {
        const sliceAngle2 = (total2 > 0 ? (slice.value / total2) * 2 * Math.PI : 0);

        let midAngle2 = startAngle2 + sliceAngle2 / 2;

        // 🔹 deslocamento se for hover
        let offset = (hoveredSlice2 === slice) ? 10 : 0;
        let offsetX = Math.cos(midAngle2) * offset;
        let offsetY = Math.sin(midAngle2) * offset;

        ctx2.beginPath();
        ctx2.moveTo(centerX2 + offsetX, centerY2 + offsetY);
        ctx2.arc(centerX2 + offsetX, centerY2 + offsetY, radius2, startAngle2, startAngle2 + sliceAngle2);
        ctx2.closePath();
        ctx2.fillStyle = (hoveredSlice2 === slice) ? slice.hoverColor : slice.color;
        ctx2.fill();

        slice.startAngle = startAngle2;
        slice.endAngle = startAngle2 + sliceAngle2;

        // Labels
        const lineStartX2 = centerX2 + offsetX + Math.cos(midAngle2) * radius2;
        const lineStartY2 = centerY2 + offsetY + Math.sin(midAngle2) * radius2;
        const radialEndX2 = centerX2 + offsetX + Math.cos(midAngle2) * (radius2 + 15);
        const radialEndY2 = centerY2 + offsetY + Math.sin(midAngle2) * (radius2 + 15);
        const horizontalLength2 = displayWidth2 * 0.08;
        const horizontalEndX2 = radialEndX2 + (Math.cos(midAngle2) >= 0 ? horizontalLength2 : -horizontalLength2);
        const horizontalEndY2 = radialEndY2;

        ctx2.strokeStyle = '#333';
        ctx2.beginPath();
        ctx2.moveTo(lineStartX2, lineStartY2);
        ctx2.lineTo(radialEndX2, radialEndY2);
        ctx2.lineTo(horizontalEndX2, horizontalEndY2);
        ctx2.stroke();

        ctx2.fillStyle = '#333';
        ctx2.font = Math.min(Math.max(Math.floor(displayWidth2 * 0.03), 12), 18) + 'px Arial';
        ctx2.textAlign = (Math.cos(midAngle2) >= 0) ? 'left' : 'right';
        ctx2.fillText(slice.label, horizontalEndX2 + (Math.cos(midAngle2) >= 0 ? 5 : -5), horizontalEndY2 - 2);

        ctx2.font = Math.min(Math.max(Math.floor(displayWidth2 * 0.025), 10), 16) + 'px Arial';
        if (total2 > 0) {
            ctx2.fillText(((slice.value / total2) * 100).toFixed(1) + '%', horizontalEndX2 + (Math.cos(midAngle2) >= 0 ? 5 : -5), horizontalEndY2 + 14);
        }

        startAngle2 += sliceAngle2;
    });
}

// Hover
canvas2.addEventListener('mousemove', function(e) {
    const rect = canvas2.getBoundingClientRect();
    const x = (e.clientX - rect.left);
    const y = (e.clientY - rect.top);

    const centerX2 = canvas2.clientWidth / 2;
    const centerY2 = canvas2.clientHeight / 2;
    const dx = x - centerX2;
    const dy = y - centerY2;
    const distance = Math.sqrt(dx*dx + dy*dy);
    const radius2 = Math.min(centerX2, centerY2) * 0.8;

    hoveredSlice2 = null;

    if (distance <= radius2) {
        let angle = Math.atan2(dy, dx);
        if (angle < -Math.PI/2) angle += 2 * Math.PI;

        data2.forEach(slice => {
            if (angle >= slice.startAngle && angle <= slice.endAngle) {
                hoveredSlice2 = slice;
            }
        });
    }

    canvas2.style.cursor = hoveredSlice2 ? 'pointer' : 'default';
    drawChart2();
});

// Clique
canvas2.addEventListener('click', function(e) {
    if (!hoveredSlice2) return;
    if (hoveredSlice2.label === 'Atrasos') {
        window.open('view_table.php?status=Atraso&semana=atual', '_blank');
    } else if (hoveredSlice2.label === 'No Prazo') {
        window.open('view_table.php?status=No%20Prazo&semana=atual', '_blank');
    }
});

window.addEventListener('load', drawChart2);
window.addEventListener('resize', drawChart2);
</script>
