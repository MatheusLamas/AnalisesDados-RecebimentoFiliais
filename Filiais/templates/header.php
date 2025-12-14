<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/styles.css">
    <title>Filiais</title>
</head>
<body>

<script>
    let ultimaModificacao = <?= filemtime("data/teste.csv") ?>;

    setInterval(() => {
        fetch("check_update.php")
            .then(response => response.text())
            .then(modificacao => {
                modificacao = parseInt(modificacao);
                if (modificacao > ultimaModificacao) {
                    location.reload(); 
                }
            });
    }, 5000); 
</script>

<?php
    include_once("includes/table_register.php");
    include_once("includes/unidade.php");
    include_once("includes/gerencia_table.php");

    $arquivo_update_table = "data/teste.csv";

    date_default_timezone_set('America/Sao_Paulo');

    $data_modificacao = '';

    if (file_exists($arquivo_update_table)) {
        $data_modificacao = date("d/m/Y H:i:s", filemtime($arquivo_update_table));
    }

    
?>
