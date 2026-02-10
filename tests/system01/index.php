<?php
include __DIR__ . '/config/require.php';

$obj = new \system01\persistencia\MatriculaOfertaDisciplinaSql();
echo $obj->getView();

