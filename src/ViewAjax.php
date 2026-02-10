<?php

class ViewAjax {

    private $option;
    private $js;
    private $variable;
    private $html;
    private $hideShow;
    private $remove;
    private $enableDisable;
    private $mensagem;
    private $mensagemLn;

    /**
     *
     * @var ViewAjax
     */
    public static $instance;

    private function __construct($tpl = '') {
        $this->option = array();
        $this->mensagem = array();
        $this->mensagemLn = array();
        $this->js = array();
        $this->html = array();
        $this->hideShow = array();
        $this->remove = array();
        $this->variable = array();
        $this->enableDisable = array();
    }

    /**
     *
     * @return ViewAjax
     */
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new ViewAjax();
        }
        return self::$instance;
    }

    public function addMsgId($object, $mensagem, $classCSS = "") {
        $this->mensagem[$object][] = array($mensagem => $classCSS);
    }

    public function addMsgIdLn($object, $mensagem, $classCSS = "") {
        $this->mensagemLn[$object][] = array($mensagem => $classCSS);
    }

    public function addOption($object, $value, $text, $selected = false) {
        $this->option[$object][$value] = array($text => $selected);
    }
    
    /**
     * Monta options passando uma lista
     * @param String $object o id do objeto html
     * @param String $key o campo referência para o valor do option
     * @param String $value o campo referência para a descrição do option
     * @param Array $list a lista
     * @param String $selected selecionado
	 * @param String $label exibir o label com valor em branco para o primeiro item do select
     */
    public function addOptionList($object, $key, $value, $list = array(), $selected=false, $label='Selecione'){

		if ($label != false) {
			$this->addOption($object, "", $label);
		}
        
        for ($i =0; $i<count($list); $i++) {
            if ($selected) {
                $this->addOption($object, $list[$i][$key], $list[$i][$value], $selected);
            } else {
                $this->addOption($object, $list[$i][$key], $list[$i][$value]);
            }
        }
    }

    public function addVariable($name, $value) {
        $this->variable[$name] = $value;
    }

    public function addHtml($object, $html) {
        $this->html[$object][] = $html;
    }

    public function hideObject($object) {
        $this->hideShowObject($object, "none");
    }

	/**
	 * Destroi um elemento dentro do html
	 * @param string $object
	 */
    public function removeObject($object) {
        $this->remove[$object] = 't';
    }

    public function showObject($object) {
        $this->hideShowObject($object, "inline");
    }

    public function enableObject($object) {
        $this->enableDisableObject($object, "false");
    }

    public function disableObject($object) {
        $this->enableDisableObject($object, "true");
    }

    private function hideShowObject($object, $status) {
        $this->hideShow[$object] = $status;
    }

    private function enableDisableObject($object, $status) {
        $this->enableDisable[$object] = $status;
    }

    public function addJs($js) {
        $this->js[] = $js;
    }

    public function show() {
        $return = "";
        if (count($this->option) > 0) {
            $return = '<script type="text/javascript">';

            $optionId = "";
            foreach ($this->option as $key => $value) {

                $return .= " if(!document.getElementById('$key'))alert('O campo $key não existe');";
                if ($optionId != $key) {
                    $registros = count($this->option[$key]);
                    $return .= " \ndocument.getElementById('$key').options.length=$registros;";
                    $optionId = $key;
                    $countOptionId = 0;
                }
                foreach ($this->option[$key] as $key1 => $value1) {
                    $return .= " \ndocument.getElementById('$key').options[{$countOptionId}].value = " . '"' . $key1 . '"' . ";";
                    foreach ($value1 as $text => $selected) {
                        if ($selected) {
                            $return .= " \ndocument.getElementById('$key').options[{$countOptionId}].selected = true;";
                        }
                        $return .= " \ndocument.getElementById('$key').options[{$countOptionId}].text = " . '"' . $text . '"' . ";";
                    }

                    ++$countOptionId;
                }
            }
            $return .= '</script>';
        }
        if (count($this->mensagem) > 0) {
            $return .= '<script type="text/javascript">';
            $optionId = "";
            foreach ($this->mensagem as $key => $value) {
                for ($index = 0; $index < count($this->mensagem[$key]); $index++) {
                    foreach ($this->mensagem[$key][$index] as $key1 => $value1) {
                        $nome = "iderrstormajax$key$index";
                        $return .= ' $("#' . $nome . '").remove();';
                        $return .= "var div = document.createElement('span');";
                        $return .= "div.setAttribute('id', '$nome');";
                        $return .= "div.setAttribute('class', '$value1');";
                        $return .= "var tx = document.createTextNode('$key1');";
                        $return .= "div.appendChild(tx);";
                        $return .= " document.getElementById('$key')" . '.parentNode.appendChild(div);';
                    }
                }
            }
            $return .= '</script>';
        }
        if (count($this->mensagemLn) > 0) {
            $return .= '<script type="text/javascript">';
            $optionId = "";
            foreach ($this->mensagemLn as $key => $value) {
                for ($index = 0; $index < count($this->mensagemLn[$key]); $index++) {
                    foreach ($this->mensagemLn[$key][$index] as $key1 => $value1) {
                        $nome = "iderrstormajax$key$index";
                        $return .= ' $("#' . $nome . '").remove();';
                        $return .= "var div = document.createElement('span');";
                        $return .= "div.setAttribute('id', '$nome');";
                        $return .= "div.setAttribute('class', '$value1');";
                        $return .= "var br = document.createElement('br');";
                        $return .= "var tx = document.createTextNode('$key1');";
                        $return .= "div.appendChild(br);";
                        $return .= "div.appendChild(tx);";
                        $return .= " document.getElementById('$key')" . '.parentNode.appendChild(div);';
                    }
                }
            }
            $return .= '</script>';
        }
        if (count($this->js) > 0) {
            $return .= '<script type="text/javascript">';
            foreach ($this->js as $key => $value) {
                $return .= "$value;";
            }
            $return .= '</script>';
        }

        if (count($this->hideShow) > 0) {
            $script1 = "document.getElementById('";
            $script2 = "').style.display='";
            $script3 = "';";
            $return .= '<script type="text/javascript">';
            foreach ($this->hideShow as $key => $value) {
                $return .= $script1 . "$key" . $script2 . $value . $script3;
            }
            $return .= '</script>';
        }

        if (count($this->remove) > 0) {
            $script1 = "document.getElementById('";
            $script2 = "').outerHTML='";
            $script3 = "';";
            $return .= '<script type="text/javascript">';
            foreach ($this->remove as $key => $value) {
                $return .= $script1 . "$key" . $script2 . $script3;
            }
            $return .= '</script>';
        }

        if (count($this->enableDisable) > 0) {
            $script1 = "document.getElementById('";
            $script2 = "').disabled=";
            $script3 = ";";
            $return .= '<script type="text/javascript">';
            foreach ($this->enableDisable as $key => $value) {
                $return .= $script1 . "$key" . $script2 . $value . $script3;
            }
            $return .= '</script>';
        }

        if (count($this->variable) > 0) {
            $return .= '<script type="text/javascript">';
            foreach ($this->variable as $key => $value) {
                $return .= " if(!document.getElementById('$key'))alert('O campo de id $key não existe');";
                $return .= " document.getElementById('$key').value = " . '"' . $value . '"' . "; ";
                $return .= " document.getElementById('$key').setAttribute('value', '" .$value . "'); ";
            }
            $return .= '</script>';
        }
        if (count($this->html) > 0) {
            $return .= '<script type="text/javascript">';
            foreach ($this->html as $key => $value) {
                $return .= " if(!document.getElementById('$key'))alert('O campo $key não existe');";
                $value1 = "";
                for ($index = 0; $index < count($this->html[$key]); $index++) {
                    $value1 .= $this->html[$key][$index];
                }
                $value1 = addslashes($value1);
                $return .= " document.getElementById('$key').innerHTML = '$value1';";
            }
            $return .= '</script>';
        }
        header('Content-Type: text/html; charset=UTF-8');
        echo $return;
        die;
    }

}

?>