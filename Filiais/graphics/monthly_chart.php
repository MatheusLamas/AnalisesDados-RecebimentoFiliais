<div id="chart-container" style="width:100%; height:100%;">
    <div id="title-piechart">Gráfico de recebimento no Mês</div>
    <canvas id="pieChart" style="cursor:pointer; display:block;"></canvas>
</div>

<script>
const canvas = document.getElementById('pieChart');
const ctx = canvas.getContext('2d');

let slices = [];

function drawChart() {
    const container = document.getElementById('chart-container');
    const titleHeight = document.getElementById('title-piechart').offsetHeight;

    // pega largura e altura disponíveis
    let rawDisplayWidth = container.clientWidth;
    let rawDisplayHeight = container.clientHeight - titleHeight;

    // define limites mínimos/máximos
    const displayWidth = Math.max(rawDisplayWidth, 300); 
    const displayHeight = Math.max(rawDisplayHeight, 200);

    const dpr = window.devicePixelRatio || 1;

    // 🔹 impede que o canvas ultrapasse o container
    canvas.width = rawDisplayWidth * dpr;
    canvas.height = rawDisplayHeight * dpr;
    canvas.style.width = rawDisplayWidth + 'px';
    canvas.style.height = rawDisplayHeight + 'px';
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

    const centerX = canvas.width / (2 * dpr);
    const centerY = canvas.height / (2 * dpr);
    const radius = Math.min(centerX, centerY) * 0.9;

    const totalAtrasados = <?= $totalAtrasados ?>;
    const totalNoPrazo = <?= $totalNoPrazo ?>;

    const data = [
        { label: 'Atrasos', value: totalAtrasados, color: '#83cbeb', hoverColor: '#6ea0f0' },
        { label: 'No Prazo', value: totalNoPrazo, color: '#12239e', hoverColor: '#4c6aff' }
    ];

    const total = data.reduce((sum, slice) => sum + slice.value, 0);
    let startAngle = -0.5 * Math.PI / 2;

    ctx.clearRect(0, 0, canvas.width, canvas.height);

    slices = [];

    data.forEach(slice => {
        const sliceAngle = (slice.value / total) * 2 * Math.PI;

        ctx.beginPath();
        ctx.moveTo(centerX, centerY);
        ctx.arc(centerX, centerY, radius, startAngle, startAngle + sliceAngle);
        ctx.closePath();
        ctx.fillStyle = slice.color;
        ctx.fill();

        const midAngle = startAngle + sliceAngle / 2;
        const lineStartX = centerX + Math.cos(midAngle) * radius;
        const lineStartY = centerY + Math.sin(midAngle) * radius;
        const radialEndX = centerX + Math.cos(midAngle) * (radius + 15);
        const radialEndY = centerY + Math.sin(midAngle) * (radius + 15);

        const horizontalLength = displayWidth * 0.08;
        const horizontalEndX = radialEndX + (Math.cos(midAngle) >= 0 ? horizontalLength : -horizontalLength);
        const horizontalEndY = radialEndY;

        ctx.strokeStyle = '#333';
        ctx.beginPath();
        ctx.moveTo(lineStartX, lineStartY);
        ctx.lineTo(radialEndX, radialEndY);
        ctx.lineTo(horizontalEndX, horizontalEndY);
        ctx.stroke();

        ctx.fillStyle = '#333';
        const labelFontSize = Math.min(Math.max(Math.floor(displayWidth * 0.03), 12), 18);
        ctx.font = labelFontSize + 'px Arial';
        ctx.textAlign = (Math.cos(midAngle) >= 0 ? 'left' : 'right');
        ctx.fillText(slice.label, horizontalEndX + (Math.cos(midAngle) >= 0 ? 5 : -5), horizontalEndY - 2);

        ctx.font = Math.min(Math.max(Math.floor(displayWidth * 0.025), 10), 16) + 'px Arial';
        ctx.fillText(((slice.value / total) * 100).toFixed(1) + '%', horizontalEndX + (Math.cos(midAngle) >= 0 ? 5 : -5), horizontalEndY + 14);

        slices.push({
            start: startAngle,
            end: startAngle + sliceAngle,
            label: slice.label,
            color: slice.color,
            hoverColor: slice.hoverColor
        });

        startAngle += sliceAngle;
    });
}

canvas.addEventListener('mousemove', (event) => {
    const rect = canvas.getBoundingClientRect();
    // 🔹 pega coordenadas em CSS pixels
    const x = event.clientX - rect.left;
    const y = event.clientY - rect.top;

    const dpr = window.devicePixelRatio || 1;
    const centerX = canvas.width / (2 * dpr);
    const centerY = canvas.height / (2 * dpr);
    const dx = x - centerX;
    const dy = y - centerY;
    const distance = Math.sqrt(dx*dx + dy*dy);
    const radius = Math.min(centerX, centerY) * 0.9;

    let hovered = false;

    if(distance <= radius) {
        let angle = Math.atan2(dy, dx);
        if(angle < -Math.PI/2) angle += 2*Math.PI;

        slices.forEach(slice => {
            if(angle >= slice.start && angle <= slice.end) {
                canvas.style.cursor = 'pointer';
                hovered = true;

                drawChart(); 
                ctx.beginPath();
                ctx.moveTo(centerX, centerY);
                ctx.arc(centerX, centerY, radius, slice.start, slice.end);
                ctx.closePath();
                ctx.fillStyle = slice.hoverColor;
                ctx.fill();
            }
        });
    }

    if(!hovered) {
        canvas.style.cursor = 'default';
        drawChart();
    }
});

canvas.addEventListener('click', (event) => {
    const rect = canvas.getBoundingClientRect();
    // 🔹 pega coordenadas em CSS pixels
    const x = event.clientX - rect.left;
    const y = event.clientY - rect.top;

    const dpr = window.devicePixelRatio || 1;
    const centerX = canvas.width / (2 * dpr);
    const centerY = canvas.height / (2 * dpr);
    const dx = x - centerX;
    const dy = y - centerY;
    const distance = Math.sqrt(dx*dx + dy*dy);
    const radius = Math.min(centerX, centerY) * 0.9;

    if(distance <= radius) {
        let angle = Math.atan2(dy, dx);
        if(angle < -Math.PI/2) angle += 2*Math.PI;

        slices.forEach(slice => {
            if(angle >= slice.start && angle <= slice.end) {
                if(slice.label === 'Atrasos') window.open('view_table.php?mes=atual&status=Atraso', '_blank');
                if(slice.label === 'No Prazo') window.open('view_table.php?mes=atual&status=No%20Prazo', '_blank');
            }
        });
    }
});

window.addEventListener('load', () => {
    drawChart();
    const container = document.getElementById('chart-container');
    const resizeObserver = new ResizeObserver(() => drawChart());
    resizeObserver.observe(container);
});
</script>
