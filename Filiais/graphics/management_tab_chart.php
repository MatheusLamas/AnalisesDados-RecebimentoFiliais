<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <title>Pontuação da Aba Gerência</title>
  <style>
    body { margin:0; padding:0; font-family: Arial, sans-serif; }
    #chart1-container {
      width: 100%; height: 50vh; border: 3px solid #002244; border-radius: 10px;
      display: flex; flex-direction: column; align-items: center;
      padding: 10px 20px 20px 20px; margin-top: 30px; box-sizing: border-box;
      box-shadow: 2px 2px 8px rgba(0,0,0,0.1);
    }
    .chart1-title { font-size: 22px; font-weight: bold; color: #333; width:100%; text-align:center; margin-bottom:10px; }
    .chart1-bars { flex:1; display:flex; align-items:center; justify-content:space-around; width:100%; position:relative; box-sizing:border-box; }
    .chart1-bar-container { position:relative; width:100%; height:50%; display:flex; flex-direction:column; align-items:center; margin-bottom:40px; }
    .chart1-bar { width:80%; position:absolute; left:50%; transform:translateX(-50%); height:50%; transition: filter 0.3s, background-color 0.3s; border-radius:4px; }
    .chart1-value { position:absolute; font-size:13px; left:50%; transform:translateX(-50%); z-index:1; }
    .chart1-label { font-size:11px; color:#000; text-align:center; position:absolute; bottom:0; margin-bottom:-100px; cursor:pointer; }
    .chart-tooltip {
      position:absolute; background:rgba(0,0,0,0.85); color:#fff;
      padding:6px 10px; border-radius:6px; font-size:12px; pointer-events:none;
      opacity:0; transition:opacity 0.15s ease; white-space:nowrap;
    }
    .chart-tooltip::after {
      content:""; position:absolute; bottom:-6px; left:50%; transform:translateX(-50%);
      border-width:6px; border-style:solid; border-color:rgba(0,0,0,0.85) transparent transparent transparent;
    }
  </style>
</head>
<body>
  <div id="chart1-container">
    <div class="chart1-title">Pontuação da Aba Gerência</div>
    <div class="chart1-bars" id="chart1-bars"></div>
  </div>

  <script>
    (function () {
      const label    = <?php echo json_encode($labelGraficoSelecionado ?? []); ?>;
      const valores  = <?php echo json_encode($valoresGraficoSelecionado ?? []); ?>;
      const amostras = <?php echo json_encode($amostrasPorDiaSelecionado ?? []); ?>;

      const dados = label.map((lbl, i) => ({
        label: lbl,
        valor: valores[i],
        qtAmostras: amostras[i]
      }));
      dados.reverse();

      const chart = document.getElementById('chart1-bars');
      const baseUrl = "view_table_gerencia.php";

      let tooltip = document.createElement('div');
      tooltip.className = 'chart-tooltip';
      chart.appendChild(tooltip);

      function desenharGrafico() {
        chart.innerHTML = '';
        chart.appendChild(tooltip);

        const maxValor = Math.max(...dados.map(d => Math.abs(d.valor)));
        const chartAltura = chart.clientHeight;

        dados.forEach(dado => {
          const container = document.createElement('div');
          container.classList.add('chart1-bar-container');

          const bar = document.createElement('div');
          bar.classList.add('chart1-bar');

          const value = document.createElement('div');
          value.classList.add('chart1-value');
          value.textContent = dado.valor;

          const altura = (Math.abs(dado.valor) / maxValor) * (chartAltura * 0.40);

          if (dado.valor >= 0) {
            bar.style.height = `${altura}px`;
            bar.style.bottom = '50%';
            bar.style.backgroundColor = 'steelblue';
            bar.style.borderRadius = '4px 4px 0 0';
            value.style.bottom = `calc(50% + ${altura}px + 5px)`;
          } else {
            bar.style.height = `${altura}px`;
            bar.style.top = '50%';
            bar.style.backgroundColor = 'red';
            bar.style.borderRadius = '0 0 4px 4px';
            value.style.top = `calc(50% + ${altura}px + 5px)`;
          }

          const labelEl = document.createElement('div');
          labelEl.classList.add('chart1-label');
          labelEl.textContent = dado.label;

          [bar, labelEl].forEach(el => {
            el.style.cursor = "pointer";
            el.addEventListener("click", () => {
              window.open(`${baseUrl}?Inter=${encodeURIComponent(dado.label)}`, '_blank');
            });

            el.addEventListener("mouseenter", () => {
              tooltip.innerHTML = `<b>${dado.qtAmostras}</b> amostras<br><i>${dado.label}Dias</i>`;
              tooltip.style.opacity = 1;

              const barRect = bar.getBoundingClientRect();
              const chartRect = chart.getBoundingClientRect();
              const tooltipX = barRect.left + (barRect.width / 2) - chartRect.left;
              const tooltipY = barRect.top - chartRect.top - 10;

              tooltip.style.left = tooltipX + "px";
              tooltip.style.top = tooltipY + "px";
              tooltip.style.transform = "translateX(-50%) translateY(-100%)";
            });

            el.addEventListener("mouseleave", () => {
              tooltip.style.opacity = 0;
            });
          });

          container.appendChild(value);
          container.appendChild(bar);
          container.appendChild(labelEl);
          chart.appendChild(container);
        });
      }

      desenharGrafico();
      window.addEventListener('resize', desenharGrafico);
    })();
  </script>
</body>
</html>