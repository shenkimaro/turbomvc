<?php

class SystemConfig {

	const DB_AVAILABLE = true;

	/**
	 * Verifica se os sistemas estão acessíveis.
	 * Caso estejam inacessíveis o usuário é redirecionado para a página de manutenção do autenticador (problemas para acessar o banco por exemplo)
	 *
	 * @see SystemConfig::DB_AVAILABLE
	 */
	public static function checkHealth() {
		$_SESSION['manutencao'] = !self::DB_AVAILABLE;
		if (!self::DB_AVAILABLE &&
			(@$_REQUEST["modulo"] != 'acesso' && @$_REQUEST["acao"] != 'manutencao')) {
			header("Location: http://www.adms.ueg.br/auth/acesso/manutencao");
			die;
		}
	}

}