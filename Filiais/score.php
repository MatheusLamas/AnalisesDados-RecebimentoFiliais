<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');
include_once("includes/gerencia_table.php");  

$unidades = [
    'Maceió', 'São Paulo', 'Fortaleza', 'Pernambuco',
    'Contagem', 'Porto Alegre', 'Juiz de Fora',
    'Espírito Santo', 'Cuiabá'
];

$pontuacoesCompletas = [];
foreach ($unidades as $unidade) {
    $pontuacoesCompletas[$unidade] = $pontuacoesGerencia[$unidade] ?? 0;
}

$pares = [];
foreach ($pontuacoesCompletas as $unidade => $pontuacao) {
    $pares[] = ['unidade' => $unidade, 'pontuacao' => $pontuacao];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Pontuações</title>
<style>
.Button {
    font-size: 15px;
    border: 3px solid #002244;
    padding: 5px 10px;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.5s, transform 0.1s;
}
.Button:active { transform: scale(0.97); background-color: #474545; }
#mensagem { margin-top: 10px; font-weight: bold; color: green; white-space: pre-line; }
</style>
</head>
<body>

<h3>Pontuações - Envio Manual</h3>
<button class="Button" type="button" id="btnSalvar">Salvar Todas Pontuações</button>
<div id="mensagem"></div>

<script>
const urlAPI = "http://192.168...:5000/escrever";
const pontuacoes = <?= json_encode($pares, JSON_UNESCAPED_UNICODE) ?>;

function enviarPontuacoes() {
    const msgDiv = document.getElementById("mensagem");
    msgDiv.textContent = "Enviando...";

    const agora = new Date();
    const dia = String(agora.getDate()).padStart(2, '0');
    const mes = String(agora.getMonth() + 1).padStart(2, '0');
    const ano = agora.getFullYear();
    const hora = String(agora.getHours()).padStart(2, '0');
    const minuto = String(agora.getMinutes()).padStart(2, '0');
    const segundo = String(agora.getSeconds()).padStart(2, '0');
    const dataHora = `${dia}/${mes}/${ano} ${hora}:${minuto}:${segundo}`;

    const todasLinhas = pontuacoes.map(item => `${item.unidade};${dataHora};${item.pontuacao}`).join("\n");

    fetch(urlAPI, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ texto: todasLinhas })
    })
    .then(r => r.json())
    .then(res => {
        msgDiv.textContent = res.mensagem || "Todas as unidades enviadas com sucesso!";
    })
    .catch(err => {
        msgDiv.textContent = "Erro: " + err.message;
    });
}

// Botão manual
document.getElementById('btnSalvar').addEventListener('click', enviarPontuacoes);

// Envio automático a cada minuto
setInterval(() => {
    const agora = new Date();
    const hora = agora.getHours();
    const minuto = agora.getMinutes();
    const diaSemana = agora.getDay(); // 0=domingo, 1=segunda ... 6=sábado

    // Envia sempre que for 10:00 de segunda a sexta (não há bloqueio)
    if (diaSemana >= 1 && diaSemana <= 5 && hora === 18 && minuto === 0) {
        enviarPontuacoes();
    }
}, 60000);
</script>

</body>
</html>
