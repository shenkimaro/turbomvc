<?php

/**
 * Classe responsável por manter as configurações de banco globais às aplicações.
 *
 * Também faz o tratamento do banco que o usuário seleciona tela de login (em ambiente de desenvolvimento)
 * para que as demais aplicações possam utilizar o mesmo banco.
 *
 * @version 1.0
 */
if(!class_exists('EnvConfig')) {
	throw new Exception("Arquivo de configuração env não encontrado");
}
class DatabaseConfig {

	const LOCALHOST = EnvConfig::LOCALHOST;
	const DNS_PRODUCAO = EnvConfig::DNS_PRODUCAO;
	const PORTA_DEFAULT = EnvConfig::PORTA_DEFAULT;
	//DESENVOLVIMENTO
	const HOST_DEV_DEFAULT = EnvConfig::HOST_DEV_DEFAULT;
	const BANCO_DEV_DEFAULT = EnvConfig::BANCO_DEV_DEFAULT;
	const USUARIO_DEV = EnvConfig::USUARIO_DEV;
	const SENHA_DEV = EnvConfig::SENHA_DEV;
	//Homologacao
	const HOST_HOMOLOG_DEFAULT = EnvConfig::HOST_HOMOLOG_DEFAULT;
	const BANCO_HOMOLOG_DEFAULT = EnvConfig::BANCO_HOMOLOG_DEFAULT;
	const USUARIO_HOMOLOG = EnvConfig::USUARIO_HOMOLOG;
	const SENHA_HOMOLOG = EnvConfig::SENHA_HOMOLOG;
	//PRODUCAO
	const HOST_PROD = EnvConfig::HOST_PROD;
	const BANCO_PROD = EnvConfig::BANCO_PROD;
	//LOGS
	const HOST_LOGS = EnvConfig::HOST_LOGS;
	const BANCO_LOGS = EnvConfig::BANCO_LOGS;
	const USUARIO_LOGS = EnvConfig::USUARIO_LOGS;
	const SENHA_LOGS = EnvConfig::SENHA_LOGS;

	/**
	 * Obtém o nome do banco que a aplicação deve utilizar, dando prioridade para o banco que o usuário escolher na tela de login.
	 * Caso o usuário não escolha nenhum banco, o banco default será utilizado
	 *
	 * @see DatabaseConfig::BANCO_DEV_DEFAULT
	 *
	 * @return string Nome do banco
	 */
	public static function getBancoDev() {
		if (isset($_SESSION["auth"]["_banco"]) && $_SESSION["auth"]["_banco"] != '') {
			return $_SESSION["auth"]["_banco"];
		} else {
			return self::BANCO_DEV_DEFAULT;
		}
	}

	/**
	 * Obtém a url do servidor de banco de dados, dando prioridade para o host que o usuário escolher na tela de login.
	 * Caso o usuário não escolha nenhum host, o host default será utilizado
	 *
	 * @see DatabaseConfig::HOST_DEV_DEFAULT
	 *
	 * @return string Url do servidor de banco de dados
	 */
	public static function getHostDev() {
		if (isset($_SESSION["auth"]["_host"]) && $_SESSION["auth"]["_host"] != '') {
			return $_SESSION["auth"]["_host"];
		} else {
			return self::HOST_DEV_DEFAULT;
		}
	}

}
