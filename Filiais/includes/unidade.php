<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Lista de unidades
$UNIDADES = [
    "Maceio"        => "Maceió",
    "SaoPaulo"      => "São Paulo",
    "Fortaleza"     => "Fortaleza",
    "Pernambuco"    => "Pernambuco",
    "Contagem"      => "Contagem",
    "PortoAlegre"   => "Porto Alegre",
    "JuizdeFora"    => "Juiz de Fora",
    "EspiritoSanto" => "Espírito Santo",
    "Cuiaba"        => "Cuiabá"
];

function norm($s) {
    $s = trim((string)$s);
    $s = iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$s); 
    $s = preg_replace('/[^a-zA-Z0-9]/','',$s); 
    return strtolower($s);
}

$URL_MAP = [];
foreach ($UNIDADES as $key => $valor) {
    $URL_MAP[norm($key)] = $valor;   
    $URL_MAP[norm($valor)] = $valor; 
}

$param = $_GET['uni'] ?? null;

$resolved = null;
if (!empty($param)) {
    $np = norm($param);
    if (isset($URL_MAP[$np])) {
        $resolved = $URL_MAP[$np];
    }
}

if ($resolved) {
    $_SESSION['unidadeServico'] = $resolved;
} elseif (!empty($_SESSION['unidadeServico'])) {
    
} else {
    $_SESSION['unidadeServico'] = "São Paulo"; 
}

$unidadeServico = $_SESSION['unidadeServico'];


