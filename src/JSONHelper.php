<?php

class JSONHelper {

	/**
	 * Converte um Mapa em um json, retornoando um objeto com chave/valor
	 * @param $data array que representa o objeto
	 * @param bool $convertToUTF8
	 * @return string JSON que representa o objeto
	 *
	 * Exemplo de entrada:
	 *
	 * Array
	 *    (
	 *        [0] => Array
	 *        (
	 *            [per_id] => 1
	 *            [eve_id] => 1
	 *            [per_descricao] => SEXTA - MANHÃ - ENTRADA
	 *        )
	 *        [1] => Array
	 *        (
	 *            [per_id] => 2
	 *            [eve_id] => 1
	 *            [per_descricao] => SEXTA - MANHÃ - SAÍDA
	 *        )
	 *  )
	 *
	 * Exemplo de retorno:
	 *
	 *   {
	 *      0: {
	 *         per_id: "1",
	 *         eve_id: "1",
	 *         per_descricao: "SEXTA - MANHÃ - ENTRADA"
	 *      },
	 *      1: {
	 *         per_id: "2",
	 *         eve_id: "1",
	 *         per_descricao: "SEXTA - MANHÃ - SAÍDA"
	 *      }
	 *   }
	 *
	 *
	 */
	public static function parseArrayToJSON($data, $convertToUTF8 = false) {
		$json_response = "{\n";

		if (!Validador::ehVazio($data)) {
			$numElementos = count($data);
			$j = 0;
			foreach ($data as $key => $value) {
				if (is_array($value)) {
					$json_response .= '"' . $key . '": ' . self::parseArrayToJSON($value, $convertToUTF8);
				} else {
					$json_response .= '"' . $key . '": "' . self::parseText($value, $convertToUTF8) . '"';
				}
				if (++$j != $numElementos) {
					$json_response .= ",\n";
				}
			}
		}

		$json_response .= "\n}";
		return $json_response;
	}

	/**
	 * Converte um array de arrays em um objeto JSON do tipo array
	 * @param $data array que representa o objeto
	 * @param bool $convertToUTF8
	 * @return string JSON que representa o objeto
	 *
	 * Exemplo de entrada:
	 *
	 * Array
	 *    (
	 *        [0] => Array
	 *        (
	 *            [per_id] => 1
	 *            [eve_id] => 1
	 *            [per_descricao] => SEXTA - MANHÃ - ENTRADA
	 *        )
	 *        [1] => Array
	 *        (
	 *            [per_id] => 2
	 *            [eve_id] => 1
	 *            [per_descricao] => SEXTA - MANHÃ - SAÍDA
	 *        )
	 *  )
	 *
	 * Exemplo de retorno:
	 *
	 *   [
	 *     {
	 *       per_id: "1",
	 *       eve_id: "1",
	 *       per_descricao: "SEXTA - MANHÃ - ENTRADA"
	 *     },
	 *     {
	 *       per_id: "2",
	 *       eve_id: "1",
	 *       per_descricao: "SEXTA - MANHÃ - SAÍDA"
	 *     }
	 *   ]
	 *
	 */
	public static function parseArrayToJSONArray($data, $convertToUTF8 = false) {
		$json_response = "[\n";

		if (!Validador::ehVazio($data)) {
			$numElementos = count($data);
			$j = 0;
			foreach ($data as $key => $value) {
				if (is_array($value)) {
					$json_response .= self::parseArrayToJSON($value, $convertToUTF8);
				} else {
					$json_response .= '"' . $key . '": "' . self::parseText($value, $convertToUTF8) . '"';
				}
				if (++$j != $numElementos) {
					$json_response .= ",\n";
				}
			}
		}

		$json_response .= "\n]";
		return $json_response;
	}

	private static function parseText($text, $convertToUTF8 = false) {
        if($text == null){
            return '';
        }
		$text = str_replace("\r\n", "\n", $text);
		$text = str_replace("\r", "\n", $text);

		// tratamento de quebra de linha no json
		$text = str_replace("\n", "\\n", $text);

		if ($convertToUTF8) {
			$text = utf8_encode($text);
		}

		return $text;
	}

	/**
	 * Faz o parse de um array de dados de objetos para JSON
	 * @param $data array de dados dos objetos
	 * @return string JSON dos objetos
	 */
	public static function parseArrayObjectsToJSON($data) {
		$json_response = "[\n";

		if ($data && is_array($data)) {
			$numElementos = count($data);
			$j = 0;
			foreach ($data as $elemento) {
				$json_response .= self::escapeJsonString($elemento->toJSON());
				if (++$j != $numElementos) {
					$json_response .= ",\n";
				}
			}
		}

		$json_response .= "\n]";
		return $json_response;
	}

	/**
	 * Remove caracteres especiais
	 * @param $value
	 * @return mixed
	 */
	private static function escapeJsonString($value) {
		$result = trim(str_replace('&#8194;', '',
			str_replace('FORMTEXT', '', preg_replace('/[[:cntrl:]]/', '', $value))));
		return $result;
	}
}