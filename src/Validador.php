<?php

/**
 * Classe que realiza validações diversas
 *
 */
class Validador {

	/**
	 * Verifica se o parâmetro data_posterior é uma data posterior à data referente ao parâmetro data_anterior
	 *
	 * @param string $data_anterior
	 * @param string $data_posterior
	 * @deprecated 
	 */
	public static function dataEhMaiorSomente($data_anterior, $data_posterior) {

		list ($dia1, $mes1, $ano1) = preg_split ('/[\/-]+/', $data_anterior);
		list ($dia2, $mes2, $ano2) = preg_split ('/[\/-]+/', $data_posterior);

		$data_anterior = $ano1 . $mes1 . $dia1;
		$data_posterior = $ano2 . $mes2 . $dia2;

		if ($data_anterior > $data_posterior) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Verifica se o parâmetro data_posterior é uma data posterior à data referente ao parâmetro data_anterior
	 *
	 * @param date $data_posterior
	 * @param date $data_anterior
	 * @deprecated since version 1
	 */
	public static function dataEhMaior($data_posterior, $data_anterior) {
		/* if ((!$this->ehData($data_anterior)) || (!$this->ehData($data_posterior)))
		  {
		  return false;
		  } */

		list ($dia1, $mes1, $ano1) = preg_split ('/[\/-]+/', $data_posterior);
		list ($dia2, $mes2, $ano2) = preg_split ('/[\/-]+/', $data_anterior);

		$data_posterior = $ano1 . $mes1 . $dia1;
		$data_anterior = $ano2 . $mes2 . $dia2;

		if ($data_posterior >= $data_anterior) {
			return true;
		} 
		return false;
	}

	/**
	 * Verifica se a data passada como parâmetro está dentro do período compreendido entre $data_inicio e $data_fim
	 * @param string $data dd/mm/aaaa
	 * @param string $data_inicio dd/mm/aaaa
	 * @param string $data_fim dd/mm/aaaa
	 * @return boolean
	 */
	public static function estaNoPeriodo($data, $data_inicio, $data_fim) {
		/* echo $data."<br>";
		  echo $data_inicio."<br>";
		  echo $data_fim."<br>"; */

		list ($dia, $mes, $ano) = preg_split ('/[\/-]+/', $data);
		list ($dia_inicio, $mes_inicio, $ano_inicio) = preg_split ('/[\/-]+/', $data_inicio);
		list ($dia_fim, $mes_fim, $ano_fim) = preg_split ('/[\/-]+/', $data_fim);

		$data = $ano . $mes . $dia;
		$data_inicio = $ano_inicio . $mes_inicio . $dia_inicio;
		$data_fim = $ano_fim . $mes_fim . $dia_fim;

		if (($data >= $data_inicio) && ($data <= $data_fim)) {
			return true;
		} else {
			return false;
		}
	}
        
        /**
	 * Verifica se a data passada como parâmetro está dentro do período compreendido entre $data_inicio e $data_fim
	 * @param string $data dd/mm/aaaa H:i
	 * @param string $data_inicio dd/mm/aaaa H:i
	 * @param string $data_fim dd/mm/aaaa H:i
	 * @return boolean
	 */
	public static function estaNoPeriodoDataHora($data, $data_inicio, $data_fim) {
            $datax = explode(" ", $data);
            $data_iniciox = explode(" ", $data_inicio);
            $data_fimx = explode(" ", $data_fim);
            if (strlen($datax[0]) == 10 && strlen($datax[1]) == 5) {                
                if (strlen($data_iniciox[0]) == 10 && strlen($data_iniciox[1]) == 5) {
                    if (strlen($data_fimx[0]) == 10 && strlen($data_fimx[1]) == 5) {    
                        if (self::estaNoPeriodo($datax[0], $data_iniciox[0], $data_fimx[0])) {
                            if ($datax[0] == $data_iniciox[0]) {   
                                if (($datax[1] >= $data_iniciox[1]) && ($datax[1] <= $data_fimx[1])) return true;
                            }
                            else if ($datax[0] == $data_fimx[0]) {   
                                if ($datax[1] <= $data_fimx[1]) return true;
                            }
                            else return true;
                        }
                    }
                }
            }
            return false;
        }

	/**
	 * Verifica se a String passada e vazia.
	 *
	 * @param string $var
	 * @return boolean
	 */
	public static function ehVazio($var) {
		if (is_array($var)) {
			if (count($var) == 0) {
				return true;
			}
			return false;
		}
		$var = trim($var ?? '');
		return ($var == "") || ($var == -1);
	}

	/**
	 * Recebe uma string de data, tanto pt-BR quanto USA
	 *
	 * @param string $date
	 * @return boolean
	 */
	public static function ehData($date = "00/00/0000") {
		if (isset($date[4]) && in_array($date[4], array("/", "-"))) {
			$date = Conversor::paraDataBr($date);
		}
		if (strlen($date??'') != 10) {
			return false;
		}
		
		$partesData = Conversor::explodeDate($date);
		
		return checkdate((int)$partesData[1], (int)$partesData[0], (int)$partesData[2]);		
	}

	/**
	 * validar cnpj
	 */
	// Função que valida CNPJ
	// O algorítimo de validação de CNPJ é baseado em cálculos
	// para o dígito verificador (os dois últimos)
	public static function ehCNPJ($CNPJ) {
		$CNPJ = preg_replace('/[^A-Za-z0-9]/', '', strtoupper($CNPJ ?? ''));
		if (strlen($CNPJ) != 14) {
			return false;
		}
		if (!preg_match('/^[A-Z0-9]{12}[0-9]{2}$/', $CNPJ)) {
			return false;
		}

		$valores = [];
		for ($i = 0; $i < 12; $i++) {
			$char = $CNPJ[$i];
			if ($char >= '0' && $char <= '9') {
				$valores[$i] = (int)$char;
			} else {
				$valores[$i] = ord($char) - 48; // A=17 ... Z=42
			}
		}

		$pesos = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
		$soma = 0;
		for ($i = 0; $i < 12; $i++) {
			$soma += $valores[$i] * $pesos[$i + 1];
		}
		$dv1 = ($soma % 11) < 2 ? 0 : 11 - ($soma % 11);

		$soma = 0;
		for ($i = 0; $i < 12; $i++) {
			$soma += $valores[$i] * $pesos[$i];
		}
		$soma += $dv1 * $pesos[12];
		$dv2 = ($soma % 11) < 2 ? 0 : 11 - ($soma % 11);

		return ($CNPJ[12] == (string)$dv1) && ($CNPJ[13] == (string)$dv2);
	}

	/**
	 * Verifica se a string passada como parâmetro é um cpf válido
	 *
	 * @param string $cpf
	 * @return boolean
	 */
	public static function ehCpf($cpf) {
		//VERIFICA SE O QUE FOI INFORMADO É NÚMERO
		$cpf = Conversor::somenteNumero($cpf);
		if (!is_numeric($cpf) || strlen($cpf) < 11) {
			$status = false;
		} else {
			//VERIFICA
			if (($cpf == '11111111111') || ($cpf == '22222222222') ||
					($cpf == '33333333333') || ($cpf == '44444444444') ||
					($cpf == '55555555555') || ($cpf == '66666666666') ||
					($cpf == '77777777777') || ($cpf == '88888888888') ||
					($cpf == '99999999999') || ($cpf == '00000000000')) {
				$status = false;
			} else {
				//PEGA O DIGITO VERIFIACADOR
				$dv_informado = mb_substr($cpf, 9, 2);
				for ($i = 0; $i <= 8; $i++) {
					$digito[$i] = mb_substr($cpf, $i, 1);
				}

				//CALCULA O VALOR DO 10º DIGITO DE VERIFICAÇÂO
				$posicao = 10;
				$soma = 0;
				for ($i = 0; $i <= 8; $i++) {
					$soma = $soma + $digito[$i] * $posicao;
					$posicao = $posicao - 1;
				}
				$digito[9] = $soma % 11;
				if ($digito[9] < 2) {
					$digito[9] = 0;
				} else {
					$digito[9] = 11 - $digito[9];
				}

				//CALCULA O VALOR DO 11º DIGITO DE VERIFICAÇÃO
				$posicao = 11;
				$soma = 0;
				for ($i = 0; $i <= 9; $i++) {
					$soma = $soma + $digito[$i] * $posicao;
					$posicao = $posicao - 1;
				}
				$digito[10] = $soma % 11;
				if ($digito[10] < 2) {
					$digito[10] = 0;
				} else {
					$digito[10] = 11 - $digito[10];
				}

				//VERIFICA SE O DV CALCULADO É IGUAL AO INFORMADO
				$dv = $digito[9] * 10 + $digito[10];
				if ($dv != $dv_informado) {
					$status = false;
				} else {
					$status = true;
				}
			}
		}
		return $status;
	}
	
	/**
	 * Valida se o texto está em um formato padronizado de identificador a ser
	 * usado no lugar do CPF.
	 * 
	 * @param type $cpf
	 */
	public static function ehPseudoCpf($cpf) {
		// usado pelo sistema Stricto Sensu. Formato: 000000000AE
		$padraoAlunoEstrangeiro = "#^\d{9}AE$#";
		$ehAlunoEstrangeiro = preg_match($padraoAlunoEstrangeiro, $cpf);

		return ($ehAlunoEstrangeiro);
	}

	/**
	 * Verifica se a string passada como parâmetro representa um número de telefone válido no formato (99)9999-9999
	 *
	 * @param string $telefone
	 * @return boolean
	 */
	public static function ehTelefone($telefone) {
		if (strlen($telefone) != 13) {
			return false;
		}
		if (mb_substr($telefone, 0, 1) != "(") {
			return false;
		}
		if (!(is_numeric(mb_substr($telefone, 1, 2)))) {
			return false;
		}
		if (mb_substr($telefone, 3, 1) != ")") {
			return false;
		}
		if (!(is_numeric(mb_substr($telefone, 4, 4)))) {
			return false;
		}
		if (mb_substr($telefone, 8, 1) != "-") {
			return false;
		}
		if (!(is_numeric(mb_substr($telefone, 9, 4)))) {
			return false;
		}
		return true;
	}
        

	/**
	 * Verifica se a variável é um número
	 *
	 * @param int $var
	 * @param boolean $isUnicode
	 * @return boolean
	 */
	public static function ehNumero($var) {
		$resultado = is_numeric($var);
		return $resultado;
	}

	/**
	 * Verifica se a variável é um inteiro
	 *
	 * @param int $var
	 * @return boolean
	 */
	public static function ehInteiro($var) {
		return preg_match("/^\d+$/", $var);
	}

	/**
	 * Verifica se a variável representa um endereço de e-mail válido
	 *
	 * @param string $var
	 * @return boolean
	 */
	public static function ehEmail($var) {
		$var = strtolower(trim($var));
		$regex = "/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+\.([a-zA-Z0-9\._-]+)+$/";
		$resultado = preg_match($regex, $var);
		return $resultado;
	}

	/**
	 * Verifica se a variável é um cep válido no formato 99999-999
	 *
	 * @param unknown_type $var
	 * @return boolean
	 */
	public static function ehCep($var) {
		//echo "here ".mb_substr($var, 5, 1);
		if ((strlen($var) != 9) ||
				(mb_substr($var, 5, 1) != '-') ||
				(!is_numeric(mb_substr($var, 0, 5))) ||
				(!is_numeric(mb_substr($var, 6, 3)))
		) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Verifica se o quantidade de caracteres da variável passada como parâmetro está no intervalo
	 * compreendido entre $min e $max
	 *
	 * @param string $var
	 * @param string $min
	 * @param string $max
	 * @return boolean
	 */
	public static function estaNoLimiteCaracteres($var, $min = 0, $max = -1) { // se $max for -1, sem limites
		$ok_min = (strlen($var) >= $min) ? true : false;

		if ($max == -1) {
			$ok_max = true;
		} else {
			$ok_max = (strlen($var) <= $max) ? true : false;
		}
		return $ok_min && $ok_max;
	}

	/**
	 * Verifica se o valor de $var está no intervalo compreendido entre $valMin e $valMax
	 *
	 * @param numeric $var
	 * @param numeric $valMin
	 * @param numeric $valMax
	 * @return boolean
	 */
	public static function estaNaFaixa($var, $valMin, $valMax) {
		$var = trim($var);
		return ($var >= $valMin) && ($var <= $valMax);
	}

	/**
	 * 
	 * @param type $numero numero a ser verificado inclusive com DV.
	 * @param type $qtdeDigitoVerificador indica qtos digitos verificadores existem no numero
	 * @param type $limMult indica o maximo que pode ser multiplicado
	 * @return boolean
	 */
	public static function ehModulo11($numero, $qtdeDigitoVerificador, $limMult = 99) {
		for ($n = 1; $n <= $qtdeDigitoVerificador; $n++) {
			$Soma = 0;
			$Mult = 2;
			for ($i = strlen($numero) - 1; $i >= 0; $i--) {
				$Soma += $Mult * intval(mb_substr($numero, $i, 1));
				if (++$Mult > $limMult) {
					$Mult = 2;
				}
			}
			$numero .= strval(fmod(fmod(($Soma * 10), 11), 10));
		}
		if((int)mb_substr($numero, strlen($numero) - $qtdeDigitoVerificador)<>0){
			return FALSE;
		}
		return TRUE;
	}

	public static function ehBooleanoDoPostgres($valor) {
		return $valor == 't' || $valor == 'f';
	}
}

?>