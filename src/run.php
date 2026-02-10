#!/bin/php 
<?php

$dir = __DIR__;
include $dir . '/autoload.php';
if (count($argv) < 1) {
	Util::shellDebug('Arquivo para uso em linha de comando.');
}

$localhost = '127.0.0.1';
$dnsProducao = '';
$perguntaSobreAmbiente  = "Configuração para rodar: \n";
$perguntaSobreAmbiente .= "1 - Desenvolvimento \n" ;
$perguntaSobreAmbiente .= "2 - Homologação \n" ;
$perguntaSobreAmbiente .= "3 - Produção \n" ;
$env_dev = read_line($perguntaSobreAmbiente);
$env_dev = $env_dev == 1? 'true':'false';
$env_homolog = $env_dev == 2? 'true':'false';
$db_host_dev = read_line("Informe o IP ou DNS para o banco de dados: ");
$db_name_dev = read_line("Informe o banco de dados: ");
$db_user_dev = read_line("Informe o usuário master do banco(acesso a todas as bases): ");
$db_pass_dev = read_line("Informe a senha do banco(acesso a todas as bases): ");
$portDb = read_line("Informe a abertura / port(a) de conexão para aplicação postgres (5432): ");
if (empty($portDb)) {
	$portDb = '5432';
}
$db_host_log = read_line("Informe o IP ou DNS para o banco de log (deixe vazio se for o mesmo do banco principal): ");
if (empty($db_host_log)) {
	$db_host_log = $db_host_dev;
}
$db_name_log = read_line("Informe o banco de dados de log: ");
$db_user_log = read_line("Informe o usuário do banco de log (deixe vazio se for o mesmo do banco principal): ");
if (empty($db_user_log)) {
	$db_user_log = $db_user_dev;
}
$db_pass_log = read_line("Informe a Senha do banco de log (deixe vazio se for o mesmo do banco principal): ");
if (empty($db_pass_log)) {
	$db_pass_log = $db_pass_dev;
}

$template = "<?php
class EnvConfig {
        const ENV_DEV = $env_dev;
        const ENV_HOMOLOG = $env_homolog;
	const LOCALHOST = '$localhost';
	const DNS_PRODUCAO = '$dnsProducao';
	const PORTA_DEFAULT = '$portDb';

	//DESENVOLVIMENTO
	const HOST_DEV_DEFAULT = '$db_host_dev';
	const BANCO_DEV_DEFAULT = '$db_name_dev';
	const USUARIO_DEV = '$db_user_dev';
	const SENHA_DEV = '$db_pass_dev';
	
	//Homologacao
	const HOST_HOMOLOG_DEFAULT = '';
	const BANCO_HOMOLOG_DEFAULT = '';
	const USUARIO_HOMOLOG = '';
	const SENHA_HOMOLOG = '';

	//PRODUCAO
	const HOST_PROD = '$db_host_dev';
	const BANCO_PROD = '$db_name_dev';

	//LOGS
	const HOST_LOGS = '$db_host_log';
	const BANCO_LOGS = '$db_name_log';
	const USUARIO_LOGS = '$db_user_log';
	const SENHA_LOGS = '$db_pass_log';
}";

//criar o arquivo apagando o anterior
$dir = __DIR__ . '/../.library/';
$file = $dir . 'config_db.php';

if (!is_dir($dir) && !mkdir($dir)) {
	die('Não foi possível criar o diretorio: ' . $dir);
}

$handle = fopen($file, 'w');
fwrite($handle, "$template");
fclose($handle);

function read_line($texto) {
	echo $texto;
	return trim(str_replace(["\r", "\n"], "", fgets(STDIN)));
}
