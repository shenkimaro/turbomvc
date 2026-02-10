<?php

class Util {
    //************************************************************************************************************************\\

    /**
     * Mostra informacoes da variavel passada na tela
     * Revisão 3
     * @param mixed $variavel Array, Objetos, string...
     * @param boolean $stop Diz se apos escrever a variavel o programa para
     * @param string $tipo 'print_r', 'echo'
     */
    public static function debug($variavel, $stop = true, $tipo = "print_r") {
        $date = date("d/m/Y H:i:s") . mb_substr((string) microtime(), 1, 8);

        echo "<pre style='padding: 5px; border:1px solid #434343; margin-bottom: 5px; background: #f4f4f4'>";
        switch ($tipo) {
            case "echo":
                echo($variavel);
                break;
            case "print_r":
                print_r($variavel);
                break;
            default:
                var_dump($variavel);
        }
        $stack = debug_backtrace();
        $call_info = @array_shift($stack);
        echo '<br><br>';
        echo "<p style='font-size: .9em'><span style='color: #b80000'>[$date]</span> Arquivo: {$call_info['file']} -> linha: ({$call_info['line']})</p>";
        echo "</pre>";
        if ($stop) {
            die();
        }
    }

    public static function shellDebug($variavel, $stop = true, $tipo = "print_r") {
        $date = date("d/m/Y H:i:s") . mb_substr((string) microtime(), 1, 8);
        echo "\n";
        switch ($tipo) {
            case "echo":
                echo($variavel);
                break;
            case "print_r":
                print_r($variavel);
                break;
            default:
                var_dump($variavel);
        }
        $stack = debug_backtrace();
        $call_info = @array_shift($stack);
        echo "\n";
        echo "[$date] Arquivo: {$call_info['file']} -> linha: ({$call_info['line']})";
        echo "\n";
        if ($stop) {
            die();
        }
    }

    /**
     * Mostra informacoes da variavel passada na tela com tipagem
     * @param mixed $variavel Array, Objetos, string...
     * @param boolean $stop Diz se apos escrever a variavel o programa para
     */
    public static function varDump($variavel, $stop = true) {
        self::debug($variavel, $stop, 'var_dump');
    }

    public static function isLocalIp() {
        if (defined('EnvConfig::ENV_DEV')) {
            return EnvConfig::ENV_DEV;
        }
        $server = $_SERVER['SERVER_ADDR'] ?? '';
        if (in_array($server, array($GLOBALS['configDb']['desenvIp'] ?? '127.0.0.1', '127.0.0.1', '127.0.1.1', 'localhost', '::1'))) {
            return true;
        }
        return false;
    }

    public static function isBeta() {
        if (defined('EnvConfig::ENV_HOMOLOG')) {
            return EnvConfig::ENV_HOMOLOG;
        }
        $server = $_SERVER['HTTP_HOST'] ?? '';
        if (strpos($server, 'beta.ueg.br') > 1) {
            return true;
        }
        return false;
    }

    public static function isRedeInterna() {
        $padroes = [
            // rede interna de desenvolvimento
            '10.20.3.',
            // rede do docker (ip arbitrário), assumindo que o docker seja
            // usado apenas para desenvolvimento.
            '172.30.0.'
        ];

        $isRede = false;
        foreach ($padroes as $padrao) {
            if (strpos($_SERVER['SERVER_ADDR'], $padrao) === 0) {
                $isRede = true;
                break;
            }
        }

        return $isRede;
    }

    public static function isWindows() {
        if (strtoupper(mb_substr(PHP_OS, 0, 3)) === 'WIN') {
            return true;
        }
        return false;
    }

    //************************************************************************************************************************\\
    public static function whereOrAnd($where) {
        if ($where == "") {
            return " WHERE";
        } else {
            return " AND";
        }
    }

    /**
     * Adiciona uma nova condição no where.
     * Caso o where ja possua um valor prévio, seu novo valor será a concatenação do valor prévio com a nova cláusula.
     *
     * @param $where String referencia para o valor prévio do where
     * @param $clause String cláusula que será adicionada
     * @param $tipo String AND ou OR
     * @return string
     */
    public static function addWhereClause(&$where, $clause, $tipo = 'AND') {
        if (!isset($clause) || $clause == '') {
            return '';
        }
        if ($where && trim($where) != '')
            $where .= ' ' . $tipo . ' ';
        else
            $where = ' WHERE ';

        $where .= $clause;
    }

    public static function whereOrAndParenteses($where) {
        if ($where == "") {
            return " WHERE (";
        } else {
            return " AND";
        }
    }

    //************************************************************************************************************************\\

    /**
     * Retorna o numero de dimensoes contidas em um array
     * Max 2 dimensoes
     *
     * @param array $array
     * @return integer
     */
    public static function numDimensions($array = '') {
        if (count($array) == 0) {
            $var = 0;
            return $var;
        }
        $level1 = 0;
        $level2 = 0;
        foreach ($array as $name => $value) {
            $level1 += 1;
            if (is_array($value)) {
                $level2 += 1;
            }
        }
        if ($level1 > $level2)
            $var = 1;
        else
            $var = 2;
        return $var;
    }

    //************************************************************************************************************************\\

    /**
     * Retorna uma string contendo a data atual. Pode-se obter no formato por extenso, abreviado, e abreviado no formato YY/MM/DD.
     * Ex.: 03 de julho de 1985
     *
     * @param boolean $mostra_dia_semana Mostra o dia da semana no retorno, padrao false
     * @param boolean $data_abreviada Mostra data abreviada
     * @param boolean $formato_yymmdd Mostra data abreviada padrao YYMMDD
     * @return string
     */
    public static function obterData($mostra_dia_semana = false, $data_abreviada = false, $formato_yymmdd = false) {
        if ($data_abreviada || $formato_yymmdd) {
            if ($formato_yymmdd) {
                return date("Y/m/d");
            } else {
                return date("d/m/Y");
            }
        }

        $dia_num = date("w");
        $mes_num = date("m");

        switch ($dia_num) {
            case 0:
                $dia = "Domingo";
                break;
            case 1:
                $dia = "Segunda-feira";
                break;
            case 2:
                $dia = "Terça-feira";
                break;
            case 3:
                $dia = "Quarta-feira";
                break;
            case 4:
                $dia = "Quinta-feira";
                break;
            case 5:
                $dia = "Sexta-feira";
                break;
            case 6:
                $dia = "Sábado";
                break;
        }

        // Converte o mês número em mês texto (Português)
        switch ($mes_num) {
            case 1:
                $mes = "Janeiro";
                break;
            case 2:
                $mes = "fevereiro";
                break;
            case 3:
                $mes = "Março";
                break;
            case 4:
                $mes = "Abril";
                break;
            case 5:
                $mes = "Maio";
                break;
            case 6:
                $mes = "Junho";
                break;
            case 7:
                $mes = "Julho";
                break;
            case 8:
                $mes = "Agosto";
                break;
            case 9:
                $mes = "Setembro";
                break;
            case 10:
                $mes = "Outubro";
                break;
            case 11:
                $mes = "Novembro";
                break;
            case 12:
                $mes = "Dezembro";
                break;
        }

        $dia_mes = date("d");
        //$mes = date("m");
        $ano = date("Y");

        if ($mostra_dia_semana) {
            return "$dia, $dia_mes de $mes de $ano.";
        } else {
            return "$dia_mes de $mes de $ano";
        }
    }

    //************************************************************************************************************************\\

    /**
     * Em desuso, use Conversor::zeroEsquerda
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
     * Obtém informações do navegador utilizado
     *
     * @return array
     */
    public function obterInfNavegador() {
        //        _______
        // ----- | CONF. |
        //
        // add new browsers in lower case here, separated
        // by spaces -  order is important: from left to
        // right browser family becomes more precise
        $browsers = "mozilla msie gecko firefox ";
        $browsers .= "konqueror safari netscape navigator ";
        $browsers .= "opera mosaic lynx amaya omniweb";

        //        _______
        // ----- |PROCESS|
        //
        $browsers = split(" ", $browsers);

        $nua = strToLower($_SERVER['HTTP_USER_AGENT']);

        $l = strlen($nua);
        for ($i = 0; $i < count($browsers); $i++) {
            $browser = $browsers[$i];
            $n = stristr($nua, $browser);
            if (strlen($n) > 0) {
                $infNav["ver"] = "";
                $infNav["nav"] = $browser;
                $j = strpos($nua, $infNav["nav"]) + $n + strlen($infNav["nav"]) + 1;
                for (; $j <= $l; $j++) {
                    $s = mb_substr($nua, $j, 1);
                    if (is_numeric($infNav["ver"] . $s))
                        $infNav["ver"] .= $s;
                    else
                        break;
                }
            }
        }

        return $infNav;
    }

    //************************************************************************************************************************\\

    /**
     * Redireciona a resquisicao para outro arquivo/modulo/camada.
     * O parametro $compl_url deve ser informado caso seja necessario customizar a url.
     * Ex. Util::redirReq("modulo", "acao", array("variavel_na_url1" => $valor1, "variavel_na_url2" => $valor2), "arquivo.php5")
     * redicionaria para ?modulo=modulo&acao=acao&variavel_na_url1=valor1&variavel_na_url2=valor2
     *
     * @param string $modulo
     * @param string $acao
     * @param array $compl_url
     *
     */
    public static function redirReq($modulo = "", $acao = "", $compl_url = array()) {
        $control = Controller::getControlByRequest($modulo);
        if ($control != null && $acao == '') {
            if (method_exists($control, 'index')) {
                $acao = 'index';
            }
        }
        $url = self::montaURL($modulo, $acao, $compl_url);
        self::redirecionar($url);
    }

    //************************************************************************************************************************\\

    /**
     * Monta a url de acordo com os parametros passados
     * O parametro $compl_url deve ser informado caso seja necessario customizar a url.
     * Ex. Util::montaURL("modulo", "acao", array("variavel_na_url1" => $valor1, "variavel_na_url2" => $valor2), "arquivo.php5")
     * resultaria em arquivo.php5?modulo=modulo&acao=acao&variavel_na_url1=valor1&variavel_na_url2=valor2
     *
     * @param string $modulo
     * @param string $acao
     * @param array $compl_url
     *
     * @return string Url montada
     */
    public static function montaURL($modulo = "login", $acao = "", $compl_url = array()) {
        if (is_array($compl_url)) {
            $complemento = "";
            foreach ($compl_url as $variavel => $valor) {
                $complemento .= "&" . $variavel . "=" . urlencode($valor);
            }
        } else {
            $complemento = $compl_url;
        }
        $moduleName = $GLOBALS['labels']['module'];
        $opName = $GLOBALS['labels']['op'];
        return "?$moduleName=" . $modulo . "&$opName=" . $acao . $complemento;
    }

    //************************************************************************************************************************\\

    /**
     * Em desuso, use Conversor::maiusculas
     *
     * @param string $str
     * @return string
     * @deprecated
     */
    public static function maiusculas($str) {
        return Conversor::maiusculas($str);
    }

    //************************************************************************************************************************\\

    /**
     * Verifica se é email valido
     *
     * @param string $var
     * @return boolean
     */
    public static function ehEmail($var) {
        return Validador::ehEmail($var);
    }

    /**
     * Valida se um email eh valido e se possui um dominio
     *
     * @param string $var
     * @return boolean
     */
    public function ehEmailDominio($var) {
        return Validador::ehEmail($var);
    }

    //************************************************************************************************************************\\

    /**
     * Em desuso, use Conversor::numeroPorExtenso
     *
     * @param string $num
     * @return string Numero por extenso
     * @deprecated
     */
    function numeroPorExtenso($num) {
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

        for ($i = 0; strlen($num) > 0; $i++) {
            $temp = mb_substr($num, -3);
            $num = mb_substr($num, 0, -3);
            $vetNumSeparado[] = Util::zeroEsquerda($temp, 3);
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
        return strtoupper($descricao);
    }

    //************************************************************************************************************************\\

    /**
     * Em desuso, use Conversor::dinheiroPorExtenso
     *
     * @param unknown_type $num
     */
    function dinheiroPorExtenso($num) {
        $vetor = explode(".", $num, 2);
        $reais = $vetor[0];
        $centavos = $vetor[1];

        $descricao;

        if ($reais > 0) {
            $descricao .= Conversor::numeroPorExtenso($reais) . " ";
            $descricao .= ($reais > 1) ? "reais" : "real";
            $descricao .= ($centavos > 0) ? " e " : "";
        }
        if ($centavos > 0) {
            $descricao .= Conversor::numeroPorExtenso($centavos) . " ";
            $descricao .= ($centavos > 1) ? "centavos" : "centavo";
        }

        return strtoupper($descricao);
    }

    //************************************************************************************************************************\\

    /**
     * Em desuso, use Conversor::paraDataBr
     *
     * @param date $data
     * @return date
     */
    function dataBr($data = "0000-00-00") {
        $data = trim($data);
        list ($ano, $mes, $dia) = split('[/.-]', $data);

        $retorno = "$dia/$mes/$ano";
        if ($retorno == "//") {
            return null;
        } else {
            return ("$dia/$mes/$ano");
        }
    }

    //************************************************************************************************************************\\

    /**
     * Em desuso, use Conversor::paraDataPg
     *
     * @deprecated since version 
     * @param date $data
     * @return date
     */
    function dataDb($data = "00/00/0000") {
        $data = trim($data);
        list ($dia, $mes, $ano) = split('[/.-]', $data);

        $retorno = "$ano-$mes-$dia";
        if ($retorno == "--") {
            return null;
        } else {
            return ("$ano-$mes-$dia");
        }
    }

    /**
     * Redireciona para o endereço que originou a chamada para a url atual.
     * Caso o HTTP_REFERER esteja vazio utiliza-se a url_default informada via parâmetro.
     * 
     * @param $url_default String Url que será utilizada caso não tenha um Referer definido.
     */
    public static function redirecionarReferer($url_default) {
        $url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_default;
        self::redirecionar($url);
    }

    //************************************************************************************************************************\\

    /**
     * Aceita uma url para realizar Location
     *
     * @param $url
     * @param bool $request, se true mantem request
     */
    public static function redirecionar($url, $request = true) {
        if (isset($GLOBALS['files']) && isset($GLOBALS['files']['rootSys'])) {
            $rootSys = $GLOBALS['files']['rootSys'];
            $rootSys = str_replace("/", "", $rootSys);
            if (!isset($_SESSION[$rootSys]['redirect'])) {
                $_SESSION[$rootSys]['redirect'] = 0;
            }
            $_SESSION[$rootSys]['redirect'] = $_SESSION[$rootSys]['redirect'] + 1;
        }
        if (defined('_SYSNAME') && $request) {
            $_SESSION[_SYSNAME]['request'] = $_REQUEST;
        }
        if (defined('_SYSNAME')) {
            $view = View::getInstance();
            $_SESSION[_SYSNAME]['view'] = serialize($view);
        }
        $cleanUrl = preg_replace('/[\r\n\t\x00-\x1f\x7f]/', '', $url);
        header("Location: $cleanUrl");
        die;
    }

    //************************************************************************************************************************\\

    /**
     * Em desuso, use Conversor::semAcentosMaiusculo
     *
     * @param string $str
     * @return string
     * @deprecated
     */
    public static function semAcentos($str) {
        $str = strtoupper($str);
        $str = strtr($str, "àáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ", "AAAAAAÆCEEEEIIIIÐNOOOOOØUUUUYÞ");
        $str = strtr($str, "ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞ", "AAAAAAÆCEEEEIIIIÐNOOOOOØUUUUYÞ");
        return $str;
    }

    //************************************************************************************************************************\\

    /**
     * Em desuso, use Conversor::insideTrim
     *
     * @param string $str
     * @return string
     * @deprecated
     */
    public static function insideTrim($str) {
        $str = preg_replace('/\s\s+/', ' ', $str);
        return $str;
    }

    public function comparaData($dtIni, $dtFin) {
        list($dia_ini, $mes_ini, $ano_ini) = explode("/", $dtIni);
        list($dia_fim, $mes_fim, $ano_fim) = explode("/", $dtFin);

        $Ini = $ano_ini . $mes_ini . $dia_ini;
        $Fin = $ano_fim . $mes_fim . $dia_fim;

        if ($Ini == $Fin) {
            return "igual";
        } elseif ($Ini < $Fin) {
            return "menor";
        } else {
            return "maior";
        }
    }

	 /**
	  * Comparação de datas em formato dia/mês/ano (DD/MM/YYYY)
	  * @param string $inicio fomato DD/MM/YYYY
	  * @param string $fim formato DD/MM/YYYY
	  */
	 public static function compareDatas($inicio, $fim) {
		 $instancia = new self();
		 $retorno = $instancia->comparaData($inicio, $fim);
		 return match($retorno) {
			 'menor' => -1,
			 'igual' => 0,
			 'maior' => 1
		 };
	 }

    /**
     * Melhoria do ceil
     * @param type $value
     * @param type $precision
     * @return type 
     */
    public static function ceiling($value, $precision = 0) {
        return ceil($value * pow(10, $precision)) / pow(10, $precision);
    }

    public static function diferencaData($dataFinal, $dataInicial) {
        return self::diffDate($dataFinal, $dataInicial, 'D');
    }

    public static function diffDate($d1, $d2, $type = '', $sep = '-') {
        if ($d2 == '' || $d1 == '')
            return;
        $d1 = explode($sep, $d1);
        $d2 = explode($sep, $d2);
        switch ($type) {
            case 'A':
                $X = 31536000;
                break;
            case 'M':
                $X = 2592000;
                break;
            case 'D':
                $X = 86400;
                break;
            case 'H':
                $X = 3600;
                break;
            case 'MI':
                $X = 60;
                break;
            default:
                $X = 1;
        }
        $mkD2 = mktime(0, 0, 0, $d2[1], $d2[2], $d2[0]);
        $mkD1 = mktime(0, 0, 0, $d1[1], $d1[2], $d1[0]);
        return floor(( ( $mkD2 - $mkD1 ) / $X));
    }

    public static function getMesExtenso($mes_num) {
        switch ($mes_num) {
            case 1:
                $mes = "Janeiro";
                break;
            case 2:
                $mes = "fevereiro";
                break;
            case 3:
                $mes = "Março";
                break;
            case 4:
                $mes = "Abril";
                break;
            case 5:
                $mes = "Maio";
                break;
            case 6:
                $mes = "Junho";
                break;
            case 7:
                $mes = "Julho";
                break;
            case 8:
                $mes = "Agosto";
                break;
            case 9:
                $mes = "Setembro";
                break;
            case 10:
                $mes = "Outubro";
                break;
            case 11:
                $mes = "Novembro";
                break;
            case 12:
                $mes = "Dezembro";
                break;
        }
        return $mes;
    }

    /**
     * Retorna o tempo inicial da execução
     * @return float
     */
    public static function getStartExecutionTime() {
        return Debug::getStartExecutionTime();
    }

    /**
     * Retorna o tempo decorrido da execução do codigo
     * @param float $startTime
     * @return float
     */
    public static function getElapsedExecutionTime($startTime) {
        return Debug::getElapsedExecutionTime($startTime);
    }

    /**
     * Retorna a idade(anos) de acordo com o ano passado
     * @param String $birthDate data no formato mm/dd/yyyy;
     * @return String
     */
    public static function getAge($birthDate) {

        $birthDateExplode = explode("/", $birthDate);
        //get age from date or birthdate
        $age = (date("md", date("U", mktime(0, 0, 0, $birthDateExplode[0], $birthDateExplode[1], $birthDateExplode[2]))) > date("md") ? ((date("Y") - $birthDateExplode[2]) - 1) : (date("Y") - $birthDateExplode[2]));
        return $age;
    }
}
