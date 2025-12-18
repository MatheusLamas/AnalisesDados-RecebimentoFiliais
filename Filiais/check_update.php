<?php
$arquivo = "data/teste.csv";
if (file_exists($arquivo)) {
    echo filemtime($arquivo);
} else {
    echo 0;
}