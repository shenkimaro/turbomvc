<?php

class Conversor {

    const MASCARA_CPF = '###.###.###-##';

    /**
     * Converte uma data do formato yyyy-mm-dd para dd/mm/yyyy
     *
     * @param string(yyyy-mm-dd) $data
     * @return string(-dd-mm-yyyy)
     */
    public static function paraDataBr($data = "0000-00-00") {
        $data = trim($data ?? '');
        $partesData = self::explodeDate($data);
        if (isset($partesData[0]) && mb_strlen($partesData[0]) < 4)
            return $data;
        $ano = $partesData[0];
        $mes = $partesData[1];
        $dia = $partesData[2];

        $retorno = "$dia/$mes/$ano";
        if ($retorno == "//") {
            return null;
        } else {
            return ("$dia/$mes/$ano");
        }
    }

    public static function explodeDate($date) {
        $partesData = explode('/', $date);
        if (count($partesData) < 3) {
            $partesData = explode('-', $date);
        }
        if (count($partesData) < 3) {
            $partesData[0] = '0';
            $partesData[1] = '0';
            $partesData[2] = '0';
        }
        return $partesData;
    }

    /**
     * Converte uma data do formato dd/mm/yyyy para yyyy-mm-dd
     *
     * @param $data
     * @return String data formatada
     */
    public static function paraDataPg(mixed $data = "00/00/0000") {
        if (!$data){
            return "";
        }
        $data = trim($data);
        if (mb_strlen($data) < 10)
            return "";
        if (!in_array($data[2], array("/", "-")))
            return $data;

        $partesData = self::explodeDate($data);
        $ano = $partesData[2];
        $mes = $partesData[1];
        $dia = $partesData[0];

        $retorno = "$ano-$mes-$dia";
        if ($retorno == "--") {
            return null;
        } else {
            return ("$ano-$mes-$dia");
        }
    }

    /**
     * Converte uma data do formato yyyy-mm-dd HH:mm:ss.ms para dd/mm/yyyy HH:mm:ss.ms
     * @param type $datahora
     * @return String
     */
    public static function paraDataHoraPtBR($datahora) {
        $dataArray = explode(' ', $datahora);
        if (count($dataArray) < 2) {
            return $datahora;
        }
        $data = self::paraDataBr($dataArray[0]);

        return $data . ' ' . mb_substr($dataArray[1], 0, 12);
    }

    /**
     * Retorna o mes de uma data no formato 'yyyy-mm-dd' ou 'yyyy-mm-dd HH:mm:ss'
     * @param $dataPg
     * @return string
     */
    public static function getMesDataPg($dataPg) {
        if (!$dataPg) {
            return '';
        }
        $partesData = explode(' ', $dataPg);
        $data = Conversor::paraDataBr($partesData[0]);
        $partesData = explode('/', $data);
        return $partesData[1];
    }

    /**
     * Retorna o ano de uma data no formato 'yyyy-mm-dd' ou 'yyyy-mm-dd HH:mm:ss'
     * @param $dataPg
     * @return string
     */
    public static function getMesAnoDataPg($dataPg) {
        $mes = self::getMesDataPg($dataPg);
        $ano = self::getAnoDataPg($dataPg);
        if ($mes && $ano) {
            return $mes . '/' . $ano;
        }
        return "";
    }

    /**
     * Retorna o ano de uma data no formato 'yyyy-mm-dd' ou 'yyyy-mm-dd HH:mm:ss'
     * @param $dataPg
     * @return string
     */
    public static function getAnoDataPg($dataPg) {
        if (!$dataPg) {
            return '';
        }
        $partesData = explode(' ', $dataPg);
        $data = Conversor::paraDataBr($partesData[0]);
        $partesData = explode('/', $data);
        return $partesData[2];
    }

    /**
     * Converte uma data do formato dd/mm/yyyy 00:00:00 para yyyy-mm-dd 00:00:00
     * @param type $datahora
     * @return type
     */
    public static function paraDataHoraPg($datahora) {
        $dataArray = explode(' ', $datahora);
        if (count($dataArray) < 2) {
            return $datahora;
        }
        $data = self::paraDataPg($dataArray[0]);

        return $data . ' ' . $dataArray[1];
    }

    public static function formatoDinheiro($valor, $comSimbolo = false) {
        if (!is_numeric($valor)) {
            return $valor;
        }
        $txt = number_format($valor, 2, ',', '.');
        if ($comSimbolo) {
            $txt = "R$ " . $txt;
        }
        return $txt;
    }

    /**
     * Converte um valor em dinheiro para sua descrição em português
     * por extenso
     *
     * @param float $num
     */
    public static function dinheiroPorExtenso($num) {
        $vetor = explode(".", $num, 2);
        $reais = $vetor[0];
        $centavos = $vetor[1];

        $descricao = '';

        if ($reais > 0) {
            $descricao .= Conversor::numeroPorExtenso($reais) . " ";
            $descricao .= ($reais > 1) ? "reais" : "real";
            $descricao .= ($centavos > 0) ? " e " : "";
        }
        if ($centavos > 0) {
            $descricao .= Conversor::numeroPorExtenso($centavos) . " ";
            $descricao .= ($centavos > 1) ? "centavos" : "centavo";
        }

        return mb_strtoupper($descricao);
    }

    /**
     * Converte uma string representando um número inteiro
     * para a descrição em português por extenso
     *
     * @param string $num
     * @return string $descricao
     */
    public static function numeroPorExtenso($num) {
        $vetUnidade = array();
        $vetDezena = array();
        $vetCentena = array();
        $vetAgrupamento = array();

        $vetUnidade["0"] = "";
        $vetUnidade["1"] = "um";
        $vetUnidade["2"] = "dois";
        $vetUnidade["3"] = "três";
        $vetUnidade["4"] = "quatro";
        $vetUnidade["5"] = "cinco";
        $vetUnidade["6"] = "seis";
        $vetUnidade["7"] = "sete";
        $vetUnidade["8"] = "oito";
        $vetUnidade["9"] = "nove";

        $vetDezena["0"] = "";
        $vetDezena["1"]["0"] = "dez";
        $vetDezena["1"]["1"] = "onze";
        $vetDezena["1"]["2"] = "doze";
        $vetDezena["1"]["3"] = "treze";
        $vetDezena["1"]["4"] = "quatorze";
        $vetDezena["1"]["5"] = "quinze";
        $vetDezena["1"]["6"] = "dezesseis";
        $vetDezena["1"]["7"] = "dezessete";
        $vetDezena["1"]["8"] = "dezoito";
        $vetDezena["1"]["9"] = "dezenove";
        $vetDezena["2"] = "vinte";
        $vetDezena["3"] = "trinta";
        $vetDezena["4"] = "quarenta";
        $vetDezena["5"] = "cinquenta";
        $vetDezena["6"] = "sessenta";
        $vetDezena["7"] = "setenta";
        $vetDezena["8"] = "oitenta";
        $vetDezena["9"] = "noventa";

        $vetCentena["0"] = "";
        $vetCentena["1"]["0"] = "cem";
        $vetCentena["1"]["1"] = "cento";
        $vetCentena["2"] = "duzentos";
        $vetCentena["3"] = "trezentos";
        $vetCentena["4"] = "quatrocentos";
        $vetCentena["5"] = "quinhentos";
        $vetCentena["6"] = "seissentos";
        $vetCentena["7"] = "setecentos";
        $vetCentena["8"] = "oitocentos";
        $vetCentena["9"] = "novecentos";

        $vetAgrupamento[0] = "";
        $vetAgrupamento[1] = "mil";
        $vetAgrupamento[2][0] = "milhão";
        $vetAgrupamento[2][1] = "milhões";
        $vetAgrupamento[3][0] = "bilhão";
        $vetAgrupamento[3][1] = "bilhões";
        $vetAgrupamento[4][0] = "trilhão";
        $vetAgrupamento[4][1] = "trilhões";

        $vetNumSeparado = array();
        $vetDescricao = array();

        for ($i = 0; mb_strlen($num) > 0; $i++) {
            $temp = mb_substr($num, -3);
            $num = mb_substr($num, 0, -3);
            $vetNumSeparado[] = Conversor::zeroEsquerda($temp, 3);
        }

        for ($i = 0; $i < count($vetNumSeparado); $i++) {
            $vetDescricao[$i] = "";
            $centena = mb_substr($vetNumSeparado[$i], 0, 1);
            $dezena = mb_substr($vetNumSeparado[$i], 1, 1);
            $unidade = mb_substr($vetNumSeparado[$i], 2, 1);

            if ($centena > "0") {
                $modificador = (($dezena == "0") && ($unidade == "0")) ? 0 : 1;
                if ($centena == "1") {
                    $vetDescricao[$i] .= $vetCentena[$centena][$modificador];
                } else {
                    $vetDescricao[$i] .= $vetCentena[$centena];
                }
                $vetDescricao[$i] .= ($modificador == 1) ? " e " : "";
            }

            if ($dezena > "0") {
                if ($dezena == "1") {
                    $vetDescricao[$i] .= $vetDezena[$dezena][$unidade];
                    $unidade = "0";
                } else {
                    $modificador = ($unidade == "0") ? 0 : 1;
                    $vetDescricao[$i] .= $vetDezena[$dezena];
                    $vetDescricao[$i] .= ($modificador == 1) ? " e " : "";
                }
            }

            if ($unidade > "0") {
                $vetDescricao[$i] .= $vetUnidade[$unidade];
            }

            if (($vetNumSeparado[$i]) != "000" && ($i > 0)) {
                if ($i == 1) {
                    $vetDescricao[$i] .= " " . $vetAgrupamento[$i];
                } else {
                    $modificador = ($vetNumSeparado[$i] <= 1) ? 0 : 1;
                    $vetDescricao[$i] .= " " . $vetAgrupamento[$i][$modificador];
                }
            }
        }

        for ($i = (count($vetNumSeparado) - 1); $i > 0; $i--) {
            $contadorGrupos = 0;
            $indice = 0;
            for ($j = ($i - 1); $j >= 0; $j--) {
                if ($vetNumSeparado[$j] != "000") {
                    $contadorGrupos++;
                    $indice = $j;
                }
            }

            if ($contadorGrupos == 1) {
                if (($vetNumSeparado[$indice]) < "99" || (($vetNumSeparado[$indice] % 100) == 0)) {
                    $vetDescricao[$i] .= " e ";
                }
                break;
            }
        }

        $descricao = "";

        for ($i = (count($vetDescricao) - 1); $i >= 0; $i--) {
            $descricao .= " " . $vetDescricao[$i];
        }

        $descricao = preg_replace("/\s+/", " ", $descricao);
        $descricao = trim($descricao);
        return mb_strtoupper($descricao);
    }

    public static function formatoCPF($cpf) {
        $txt = mb_substr($cpf, 0, 3) . "." . mb_substr($cpf, 3, 3) . "." . mb_substr($cpf, 6, 3) . "-" . mb_substr($cpf, 9, 2);
        return $txt;
    }

    public static function formatoCNPJ($cnpj) {
        $txt = mb_substr($cnpj, 0, 2) . "." . mb_substr($cnpj, 2, 3) . "." . mb_substr($cnpj, 5, 3) . "/" . mb_substr($cnpj, 8, 4) . "-" . mb_substr($cnpj, 12, 2);
        return $txt;
    }

    public static function farmataCpfCnpj($cpf_cnpj) {
        if (mb_strlen($cpf_cnpj) == 11) {
            $cpf_cnpj = Conversor::formatoCPF($cpf_cnpj);
        } else {
            if (mb_strlen($cpf_cnpj) == 14) {
                $cpf_cnpj = Conversor::formatoCNPJ($cpf_cnpj);
            }
        }

        return $cpf_cnpj;
    }

    public static function limpaCPF_CNPJ($valor) {
        $valor = trim($valor);
        $valor = str_replace(".", "", $valor);
        $valor = str_replace(",", "", $valor);
        $valor = str_replace("-", "", $valor);
        $valor = str_replace("/", "", $valor);
        return $valor;
    }

    public static function limpeCEP($cep) {
        $cep = trim($cep);
        $cep = str_replace("-", "", $cep);
        $cep = str_replace(" ", "", $cep);
        return $cep;
    }

    public static function fomataNumericos($var) {
        $temp = '';
        for ($x = 0; $x < mb_strlen($var); $x++) {
            if (in_array($var[$x], array("0", "1", "2", "3", "4", "5", "6", "7", "8", "9",)))
                $temp .= $var[$x];
        }
        return $temp;
    }

    public static function dinheiroParaBd($valor) {
        return Conversor::moedaDb($valor);
    }

    /**
     * Converte    isso ->  452,xxx.664,111,08695 para -> 452664111.08
     * @param string $valor
     * @return float
     */
    public static function moedaDb($valor) {
        $valor = (string) $valor;
        $newValor = '';
        $achouSeparador = FALSE;
        for ($index = mb_strlen($valor) - 1; $index >= 0; $index--) {
            $char = $valor[$index];
            if ($newValor != '' && !$achouSeparador && ($char == '.' || $char == ',')) {
                $newValor = '.' . $newValor;
                $achouSeparador = TRUE;
                continue;
            }
            if (!is_numeric($char) && $char != '-') {
                continue;
            }
            $newValor = $char . $newValor;
        }
        $partes = explode('.', $newValor);
        if (isset($partes[1]) && mb_strlen($partes[1]) > 2) {
            $newValor = $partes[0] . '.' . mb_substr($partes[1], 0, 2);
        }
        return ($newValor);
    }

    /**
     * Converte    isso ->  452 para -> 452.00
     * @param string $valor
     * @return float
     */
    public static function moedaDbFloat($valor) {
        $valor = self::moedaDb($valor);
        $partes = explode('.', $valor);

        if (isset($partes[1]) && mb_strlen($partes[1]) < 2) {
            $newValor = $valor . '0';
            return $newValor;
        }
        if (isset($partes[1])) {
            $newValor = $valor;
            return $newValor;
        }
        $newValor = $valor . '.00';
        return $newValor;
    }

    /**
     * Converte uma data do formato yyyy-mm-dd ou dd/mm/yyyy para extenso Br (Ex. 22 de novembro de 2006).
     *
     * @param string $data
     * @return string
     */
    public static function dataExtenso($data = "0000-00-00", $conectivo = true) {
        $formato = "EXT";
        $data = trim($data);
        if ($data == '') {
            return '';
        }

        if (in_array(mb_substr($data, 2, 1), array('-', '/')))
            $data = list ($dia, $mes, $ano) = preg_split('/[\/\.-]/', $data);
        else
            list ($ano, $mes, $dia) = preg_split('/[\/\.-]/', $data);

        $retorno = "$dia/$mes/$ano";
        if ($retorno == "//") {
            return null;
        } else {
            //Formato abreviado
            if ($formato == "EXT") {
                // Converte o mês número em mês texto (Português)
                $mes_ext = self::getNomeMes($mes);

                if ($conectivo) {
                    return "$dia de $mes_ext de $ano";
                }

                return "$dia $mes_ext $ano";
            }
        }
    }

    public static function getNomeMes($num_mes) {
        $mes_ext = '';
        switch ($num_mes) {
            case "1":
                $mes_ext = "Janeiro";
                break;
            case "2":
                $mes_ext = "Fevereiro";
                break;
            case "3":
                $mes_ext = "Março";
                break;
            case "4":
                $mes_ext = "Abril";
                break;
            case "5":
                $mes_ext = "Maio";
                break;
            case "06":
                $mes_ext = "Junho";
                break;
            case "07":
                $mes_ext = "Julho";
                break;
            case "08":
                $mes_ext = "Agosto";
                break;
            case "09":
                $mes_ext = "Setembro";
                break;
            case "10":
                $mes_ext = "Outubro";
                break;
            case "11":
                $mes_ext = "Novembro";
                break;
            case "12":
                $mes_ext = "Dezembro";
                break;
        }
        return $mes_ext;
    }

    public static function getNomeMesAbrev($num_mes) {
        $mes_ext = '';
        switch ($num_mes) {
            case "1":
                $mes_ext = "Jan";
                break;
            case "2":
                $mes_ext = "Fev";
                break;
            case "3":
                $mes_ext = "Mar";
                break;
            case "4":
                $mes_ext = "Abr";
                break;
            case "5":
                $mes_ext = "Mai";
                break;
            case "06":
                $mes_ext = "Jun";
                break;
            case "07":
                $mes_ext = "Jul";
                break;
            case "08":
                $mes_ext = "Ago";
                break;
            case "09":
                $mes_ext = "Set";
                break;
            case "10":
                $mes_ext = "Out";
                break;
            case "11":
                $mes_ext = "Nov";
                break;
            case "12":
                $mes_ext = "Dez";
                break;
        }
        return $mes_ext;
    }

    public static function dataExtensoParaDB($data) {
        $data = trim($data);
        $data = list ($dia, $mes_ext, $ano) = preg_split('/\bde\b/', $data);

        switch (trim(mb_strtolower($mes_ext))) {
            case "janeiro":
                $mes = "01";
                break;
            case "fevereiro":
                $mes = "02";
                break;
            case "março":
                $mes = "03";
                break;
            case "abril":
                $mes = "04";
                break;
            case "maio":
                $mes = "05";
                break;
            case "junho":
                $mes = "06";
                break;
            case "julho":
                $mes = "07";
                break;
            case "agosto":
                $mes = "08";
                break;
            case "setembro":
                $mes = "09";
                break;
            case "outubro":
                $mes = "10";
                break;
            case "novembro":
                $mes = "11";
                break;
            case "dezembro":
                $mes = "12";
                break;
        }
        return "$ano-$mes-$dia";
    }

    /**
     * Converte os caracteres para maiusculas
     *
     * @param string $str
     * @return string
     */
    public static function maiusculas($str) {
        $str = mb_strtoupper($str);
        return $str;
    }

    /**
     * Converte os caracteres para minusculas
     *
     * @param string $str
     * @return string
     */
    public static function minusculas($str) {
        $str = mb_strtolower($str);
        return $str;
    }

    /**
     * Converte os caracteres para sem acento caixa alta
     *
     * @param string $str
     * @return string
     */
    public static function semAcentosMaiusculo($str) {
        $str = mb_strtoupper($str);
        $str = strtr($str, "àáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ", "aaaaaaaceeeeiiiionoooooouuuuypy");
        $str = strtr($str, "ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÕÑÒÓÔÕÖØÙÚÛÜÝÞŸ", "AAAAAAACEEEEIIIIDONOOOOOOUUUUYPY");
        return $str;
    }

    /**
     * Converte os caracteres para sem acento caixa baixa
     *
     * @param string $str
     * @return string
     */
    public static function semAcentosMinusculas($str, $trocaEspaco = true) {
        return mb_strtolower(self::semAcentos($str, $trocaEspaco));
    }

    /**
     * Converte os caracteres para sem acento caixa baixa
     * e para enderecos mais aceitos em navegadores
     *
     * @param string $str
     * @return string
     */
    public static function semAcentosMinusculasUrl($str, $caracterEspecial = '') {
        return mb_strtolower(self::somenteAlfaNumerico(self::semAcentos($str), $caracterEspecial));
    }

    public static function semAcentos($noTexto, $trocaEspaco = true) {
        $semAcentos = preg_replace('~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml|caron);~i', '$1', htmlentities($noTexto, ENT_QUOTES, 'UTF-8'));
        $semAcentos = preg_replace('~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml|caron);~i', '$1', htmlentities($noTexto, ENT_QUOTES, 'UTF-8'));
        if ($trocaEspaco) {
            $semAcentos = str_replace(' ', '_', $semAcentos);
        }
        return $semAcentos;
    }

    public static function semPonto($noTexto) {
        $trocarIsso = array('.');
        $porIsso = array('_');
        $semPonto = str_replace($trocarIsso, $porIsso, $noTexto);
        return $semPonto;
    }

    /**
     * Retira todos caracteres especiais, trocando por 'x' por padrao
     * @param type $str
     * @param type $caracterEspecial
     * @return type
     */
    public static function somenteAlfabeto($str, $caracterEspecial = "x") {
        $strFinal = "";
        $str = self::semAcentos($str);
        $alfa = self::alfabeto();
        for ($x = 0; $x < mb_strlen($str); $x++) {
            $caracter = mb_strtoupper($str[$x]);
            if (in_array($caracter, $alfa) || $caracter == '.') {
                $strFinal .= $str[$x];
            } else {
                $strFinal .= $caracterEspecial;
            }
        }
        return $strFinal;
    }

    private static function alfabeto() {
        $alfa = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H',
            'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T',
            'U', 'V', 'W', 'X', 'Y', 'Z');
        return $alfa;
    }

    /**
     * Retira todos caracteres especiais, trocando por '_' por padrao
     * @param type $str
     * @param type $caracterEspecial
     * @return type
     */
    public static function somenteAlfaNumerico($str, $caracterEspecial = "_") {
        $strFinal = "";
        for ($x = 0; $x < mb_strlen($str); $x++) {
            $caracter = $str[$x];
            switch (mb_strtoupper($caracter)) {
                case 'A':
                case 'B':
                case 'C':
                case 'D':
                case 'E':
                case 'F':
                case 'G':
                case 'H':
                case 'I':
                case 'J':
                case 'K':
                case 'L':
                case 'M':
                case 'N':
                case 'O':
                case 'P':
                case 'Q':
                case 'R':
                case 'S':
                case 'T':
                case 'U':
                case 'V':
                case 'W':
                case 'X':
                case 'Y':
                case 'Z':
                case '0':
                case '1':
                case '2':
                case '3':
                case '4':
                case '5':
                case '6':
                case '7':
                case '8':
                case '9':
                    $strFinal .= $caracter;
                    break;
                default:
                    $strFinal .= $caracterEspecial;
                    break;
            }
        }
        return $strFinal;
    }

    /**
     * Retira todos caracteres especiais, trocando por 'x' por padrao
     * @param type $str
     * @param type $caracterEspecial
     * @return type
     */
    public static function somenteAlfabetoNumeros($str, $caracterEspecial = "x") {
        $strFinal = "";
        $str = self::semAcentos($str);
        for ($x = 0; $x < mb_strlen($str); $x++) {
            $caracter = $str[$x];
            switch (mb_strtoupper($caracter)) {
                case 'A':
                case 'B':
                case 'C':
                case 'D':
                case 'E':
                case 'F':
                case 'G':
                case 'H':
                case 'I':
                case 'J':
                case 'K':
                case 'L':
                case 'M':
                case 'N':
                case 'O':
                case 'P':
                case 'Q':
                case 'R':
                case 'S':
                case 'T':
                case 'U':
                case 'V':
                case 'W':
                case 'X':
                case 'Y':
                case 'Z':
                case '.':
                case '0':
                case '1':
                case '2':
                case '3':
                case '4':
                case '5':
                case '6':
                case '7':
                case '8':
                case '9':
                case '_':
                case '-':
                    $strFinal .= $caracter;
                    break;
                default:
                    $strFinal .= $caracterEspecial;
                    break;
            }
        }
        return $strFinal;
    }

    /**
     * Retira espacos excedentes de uma string
     *
     * @param string $str
     * @return string
     */
    public static function insideTrim($str) {
        if ($str == '') {
            return $str;
        }
        $str = preg_replace('/\s\s+/', ' ', $str);
        return $str;
    }

    /**
     * Retira espacos excedentes de uma string tanto internamente quanto fora
     *
     * @param string $str
     * @return string
     */
    public static function fullTrim($str) {
        if ($str == '') {
            return $str;
        }
        return trim(self::insideTrim($str));
    }

    //************************************************************************************************************************\\

    /**
     * completa o numero informado com zeros à esquerda
     *
     * @param int $numero
     * @param int $tamanho
     * @return int $numero
     */
    public function zeroEsquerda($numero, $tamanho) {
        return str_pad($numero, $tamanho, "0", STR_PAD_LEFT);
    }

    //************************************************************************************************************************\\

    /**
     * converte caracteres especiais para realidade html
     *
     * @param string $html
     * @return string
     */
    public static function converteToHtml($html) {

        $html = str_replace("á", "&aacute;", $html);
        $html = str_replace("é", "&eacute;", $html);
        $html = str_replace("í", "&iacute;", $html);
        $html = str_replace("ó", "&oacute;", $html);
        $html = str_replace("ú", "&uacute;", $html);

        $html = str_replace("Á", "&Aacute;", $html);
        $html = str_replace("É", "&Eacute;", $html);
        $html = str_replace("Í", "&Iacute;", $html);
        $html = str_replace("Ó", "&Oacute;", $html);
        $html = str_replace("Ú", "&Uacute;", $html);

        $html = str_replace("â", "&acirc;", $html);
        $html = str_replace("ê", "&ecirc;", $html);
        $html = str_replace("ô", "&ocirc;", $html);

        $html = str_replace("Â", "&Acirc;", $html);
        $html = str_replace("Ê", "&Ecirc;", $html);
        $html = str_replace("Ô", "&Ocirc;", $html);

        $html = str_replace("à", "&agrave;", $html);
        $html = str_replace("À", "&Agrave;", $html);

        $html = str_replace("ç", "&ccedil;", $html);
        $html = str_replace("Ç", "&Ccedil;", $html);

        $html = str_replace("ã", "&atilde;", $html);
        $html = str_replace("õ", "&otilde;", $html);

        $html = str_replace("Ã", "&Atilde;", $html);
        $html = str_replace("Õ", "&Otilde;", $html);
        $html = str_replace("ª", "&ordf;", $html);
        $html = str_replace("º", "&ordm;", $html);

        return $html;
    }

    public static function data($hora = 0, $minuto = 0, $segundo = 0, $mes = 0, $dia = 0, $ano = 0) {
        return date("Y-m-d H:i:s", mktime(date("H") + $hora, date("i") + $minuto, date("s") + $segundo, date("m") + $mes, date("d") + $dia, date("y") + $ano));
    }

    /**
     *
     * @param string $str
     * @param string $caracterEspecial
     * @return int
     */
    public static function somenteNumero($str, $caracterEspecial = '',$TransformarNumeric=false) {
        $str = trim($str ?? '');
        if (is_numeric($str) && !$TransformarNumeric) {
            return $str;
        }
        $caracteresNumericos = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $strFinal = "";
        $stringToWalk = mb_str_split($str);
        for ($x = 0; $x < count($stringToWalk); $x++) {
            $caracter = $stringToWalk[$x];
            $strFinal .= in_array($caracter, $caracteresNumericos) ? $caracter : $caracterEspecial;
        }
        return $strFinal;
    }

    /**
     * Retorna o dia da semana de uma data.
     *
     * @param $data
     * @return string
     * 		Possíveis retornos:
     * 			Domingo, Segunda-feira, Terça-feira, Quarta-feira, Quinta-feira, Sexta-feira, Sábado
     */
    public static function getDiaDaSemana($data) {
        $data_array = preg_split('/[\/\.-]/', $data);

        if (strlen($data_array[0]) == 2) {
            $data = self::paraDataPg($data);
        }
        $dia = date("D", strtotime($data));

        $dia_ptBr = '';

        switch (mb_strtoupper($dia)) {
            case "SUN":
                $dia_ptBr = 'Domingo';
                break;
            case "MON":
                $dia_ptBr = 'Segunda-feira';
                break;
            case "TUE":
                $dia_ptBr = 'Terça-feira';
                break;
            case "WED":
                $dia_ptBr = 'Quarta-feira';
                break;
            case "THU":
                $dia_ptBr = 'Quinta-feira';
                break;
            case "FRI":
                $dia_ptBr = 'Sexta-feira';
                break;
            case "SAT":
                $dia_ptBr = 'Sábado';
                break;
        }
        return $dia_ptBr;
    }

    /**
     * Retorna um string mascarada, por exemplo
     *  echo mascarar($cnpj,'##.###.###/####-##');
     * 	echo mask($cpf,'###.###.###-##');
     *  echo mask($cep,'#####-###');
     * 	echo mask($data,'##/##/####');
     * 
     * @param String $val 
     * @param String $mask
     * 
     * @return String mascarada
     */
    public static function mascarar($val, $mask) {
        $maskared = '';
        $k = 0;
        for ($i = 0; $i <= mb_strlen($mask) - 1; $i++) {
            if ($mask[$i] == '#') {
                if (isset($val[$k]))
                    $maskared .= $val[$k++];
            } else {
                if (isset($mask[$i]))
                    $maskared .= $mask[$i];
            }
        }
        return $maskared;
    }

    /**
     * Formata uma string convertendo a primeira letra de cada palavra para maiusculo e o restante para minúsculo.
     * Leva em consideração os pronomes de ligação. Ex:
     *    string antes da conversão: JOSÉ DA SILVA
     *    string após da conversão:  José da Silva
     *
     * @param $string String Texto a ser convertido
     * @param array $delimiters (opcional) Delimitadores entre cada palavra
     * @param array $exceptions (opcional) Palavras exceções que não serão convertidas
     * @return string
     */
    public static function formateTitulo($string, $delimiters = array(" ", "-", "."),
            $exceptions = array("de", "a", "em", "por", "com", "para", "das", "dos", "da", "do", "e")) {
        $encoding = 'UTF-8';
        $string = mb_convert_case($string, MB_CASE_TITLE, $encoding);
        foreach ($delimiters as $dlnr => $delimiter) {
            $words = explode($delimiter, $string);
            $newwords = array();
            foreach ($words as $wordnr => $word) {
                if (in_array(mb_strtoupper($word, $encoding), $exceptions)) {
                    // check exceptions list for any words that should be in upper case
                    $word = mb_strtoupper($word, $encoding);
                } elseif (in_array(mb_strtolower($word, $encoding), $exceptions)) {
                    // check exceptions list for any words that should be in upper case
                    $word = mb_strtolower($word, $encoding);
                } elseif (!in_array($word, $exceptions)) {
                    // convert to uppercase (non-utf8 only)
                    $word = ucfirst($word);
                }
                array_push($newwords, $word);
            }
            $string = join($delimiter, $newwords);
        }
        return $string;
    }

}
