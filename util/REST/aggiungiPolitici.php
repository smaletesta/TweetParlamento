<?php

require './Politico.php';

$politico = new Politico();
$politico->addFromCsv('nuovi deputati.csv', 'camera');

?>
