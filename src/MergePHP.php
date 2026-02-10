<?php

/**
 * Description of MergePHP
 *
 * @author ibanez
 */
class MergePHP {
    private $template;
    private $blocks;
    private static $instance;

    public static function getInstance(){
        if (!isset(self::$instance))
        {
            self::$instance = new MergePHP();
        }
        return self::$instance;
    }

    public function loadTemplate($tpl){
        $this->template = $tpl;
    }

    public function MergeBlock($name,$value){
        $this->blocks[$name] = $value;
    }

    public function Show(){
        foreach ($this->blocks as $key => $value){
            $$key = $value;
        }
        unset($this->blocks);
        include_once ($this->template);
        die;
    }
}
