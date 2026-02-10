<?php

use library\ueg\Restful;

class View {

	const ENGINE_JSONVIEW = 'JsonView';
	const ENGINE_TINYBUTSTRONG_3_10 = 'TinyButStrong3_10';

	/**
	 * Obj ClsTinyButStrong
	 *
	 * @var ClsTinyButStrong
	 */
	public $tpl;

	/**
	 * Obj Container
	 *
	 * @var Container
	 */
	private $container;
	private $blockPrefix;

	/**
	 * Nome do bloco principal da pagina
	 *
	 * @var string
	 */
	protected $blockName;

	/**
	 * Limite de linha para a consulta na tela
	 *
	 * @var integer
	 */
	private $rowsLimit;
	private $array;
	private $count;
	private $template;
	protected $blocks;
	private $page;
	public $default;
	private $defaultSession;

	/**
	 * Numero de paginas mostradas na barra de paginacao
	 * @var int
	 */
	private $pagesMaxView;

	/**
	 *
	 * @var View
	 */
	public static $instance;

	function __construct() {
		if (defined('_TEMPLATE_MANAGER')) {
			$tbs = _TEMPLATE_MANAGER;
			$this->tpl = new $tbs();
		} else {
			$this->tpl = clsTinyButStrongLibrary::getInstance();
		}
		$this->container = Container::getInstance();
		if (isset($GLOBALS["template"])) {
			$this->blockPrefix = $GLOBALS["template"]['blockPrefix'];
		}
		$this->blocks = array();
		$this->page = 0;
		$this->defaultSession = TRUE;
		$this->blocks['javascript'] = array();
	}

	public function setTemplateEngine($templateEngine) {
		if (class_exists($templateEngine)) {
			$this->tpl = new $templateEngine();
		} else {
			$this->tpl = TinyButStrong3_10::getInstance();
		}
	}

	/**
	 * 
	 * @return View
	 */
	public static function getInstance() {
		if (!isset(self::$instance)) {
			self::$instance = new View();
			$view = self::$instance;
			$tplName = get_class($view->tpl);
			if (defined('_SYSNAME') && isset($_SESSION[_SYSNAME]['view'])) {
				if (strpos($_SESSION[_SYSNAME]['view'], '"' . $tplName . '"')) {
					$viewTemp = unserialize($_SESSION[_SYSNAME]['view']);
					$view = self::$instance;
					$view->blocks = $viewTemp->getBlocks();
					$view->default = $viewTemp->default;
				}
				unset($_SESSION[_SYSNAME]['view']);
			}
		}
		return self::$instance;
	}

	/**
	 * @deprecated 
	 */
	public function addObjBlock($objects) {
		$var = $this->container->getObjectsValue($objects);
		if ($this->blockPrefix != '')
			$blockPrefix = "{$this->blockPrefix}.";
		else
			$blockPrefix = "";
		$this->blocks["$blockPrefix$blockName"] = $var;
	}

	/**
	 *
	 * @param array $objects
	 * @param string $blockName nome do bloco no HTML
	 */
	public function addBlock($objects, $blockName = '') {
		if ($blockName == '') {
			if (is_object(@$objects[0]))
				$blockName = get_class($objects[0]);
			else
				$blockName = $objects;
		}
		if (!is_array($objects) && !is_string($objects)) {
			$obj = $objects;
			$objects = array();
			$objects[] = $obj;
		} elseif (is_string($objects)) {
			$objects = new $objects();
		}

		if ($this->blockPrefix != '') {
			$blockPrefix = "{$this->blockPrefix}.";
		} else {
			$blockPrefix = "";
		}

		if (is_array($objects)) {
			$this->blocks["$blockPrefix$blockName"] = $objects;
		} else {
			$this->blocks["$blockPrefix$blockName"] = array();
		}
	}

	/**
	 * Versão de addBlock com a ordem dos parâmetros padronizada com AddArray e AddDefault
	 * @param string $blockName
	 * @param array $object
	 */
	public function addNamedBlock($blockName, $object) {
		$this->addBlock($object, $blockName);
	}

	/**
	 * Renderiza um bloco com conte�do vazio.
	 * @param type $blockName
	 */
	public function clearBlock($blockName) {
		$this->addNamedBlock($blockName, array());
	}

	public function getBlocks() {
		return $this->blocks;
	}

	public function addObj($obj) {
		$this->tpl->ObjectRef[get_class($obj)] = &$obj;
	}

	public function addObjByName($objName) {
		$obj = $this->container->getObjects($objName);
		$this->tpl->ObjectRef[$objName] = &$obj[0];
	}

	public function templateExists($tpl) {
		$this->template = $_SERVER['DOCUMENT_ROOT'] . ($GLOBALS['files']['rootSys'] ?? '') . ($GLOBALS['files']['templates'] ?? '') . $tpl;
		$file = $this->template . ($GLOBALS['template']['extension'] ?? '');
		return file_exists($file);
	}

	private function templateExistsFullPath() {
		$file = $this->template . $GLOBALS['template']['extension'];
		return is_file($file);
	}

	public function setTemplate($tpl) {
		if (defined('_ROOT')) {
			$this->template = _ROOT . $GLOBALS['files']['templates'] . $tpl;
		} else {
			$this->template = $_SERVER['DOCUMENT_ROOT'] . $GLOBALS['files']['rootSys'] . $GLOBALS['files']['templates'] . $tpl;
		}
		$this->loadTemplate();
	}

	public function loadTemplate() {
		$template = $this->template . $GLOBALS['template']['extension'];
		if (!$this->templateExistsFullPath()) {
			$rest = Restful::create();
			$stack = debug_backtrace();
			$call_info = @array_shift($stack);
			$causado = '';
			if(Util::isLocalIp()){
				$causado = "{$call_info['file']} linha: ({$call_info['line']})";
			}
			$data = [
					"mensagem" => 'Rota/saida inexistente para essa aplicacao.',
					"causado_por" => $causado,
				];
			$rest->printREST($data, Restful::STATUS_ERRO_INTERNO_SERVIDOR);
		}
		$this->tpl->loadTemplate($template, 'UTF-8');
	}

	public function getTemplateContent($tpl) {
		$this->mergeTemplate($tpl, TBS_NOTHING);
		return $this->tpl->Source;
	}

	public function setTemplateIndex($tpl = '') {
		if ($tpl == '' && defined('_templateIndex')) {
			$tpl = _templateIndex;
		}
		if (defined('_ROOT')) {
			$this->template = _ROOT . $tpl;
		} else if(isset ($GLOBALS['files']['rootSys'])) {
			$this->template = $_SERVER['DOCUMENT_ROOT'] . $GLOBALS['files']['rootSys'] . $tpl;
		}
		$this->loadTemplate();
	}

	public function getTemplate() {
		return $this->template;
	}

	public function addJs($js = '') {
		$directorySys = $GLOBALS['files']['rootSys'] . (isset($GLOBALS['files']['javascript']) ? $GLOBALS['files']['javascript'] : '');
		$directorySysLocal = $GLOBALS['files']['rootSys'] . (isset($GLOBALS['files']['javascriptLocal']) ? $GLOBALS['files']['javascriptLocal'] : '');
		$timestamp_arquivo = date("Ymd");
		$arquivo = $directorySysLocal . $js . '.js';
		if (file_exists($_SERVER['DOCUMENT_ROOT'] . "/library/js/" . $js . ".js")) {
			$this->blocks['javascript'][]['name'] = "/library/js/" . $js . ".js?t=" . $timestamp_arquivo;
		} elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . $directorySys . $js . ".js")) {
			$this->blocks['javascript'][]['name'] = $directorySys . $js . ".js?t=" . $timestamp_arquivo;
		} elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . $arquivo)) {
			$this->blocks['javascript'][]['name'] = $arquivo . '?t=' . $timestamp_arquivo;
		} else if ((new Debug())->getStatusDebug()) {
			echo "<script>alert('Script nao encontrado $arquivo');</script>";
		}
	}

	public function addCss($css = 'css') {
		$path = isset($GLOBALS['files']['css']) ? $GLOBALS['files']['css'] : '';
		$exists = false;
		for ($index = 0; isset($this->blocks['css']) && $index < count($this->blocks['css']); $index++) {
			$blockCss = $this->blocks['css'][$index];
			if ($blockCss['name'] == $path . $css . ".css") {
				$exists = true;
				break;
			}
		}
		if (!$exists) {
			$this->blocks['css'][]['name'] = $path . $css . ".css";
		}
	}

	public function addArray($key, $array) {
		if ($this->blockPrefix != '')
			$blockPrefix = "{$this->blockPrefix}.";
		else
			$blockPrefix = "";
		$this->tpl->MergeBlock("$blockPrefix$key", $array);
	}

	public function setMsgSucesso($value) {
		$this->addDefault('msg', $value, true);
		if (!isset($_SESSION['msg']) || !is_array($_SESSION['msg'])) {
			$_SESSION['msg'] = array();
		}
		if (!isset($_SESSION['msg']['success'])) {
			$_SESSION['msg']['success'] = '';
		}
		$_SESSION['msg']['success'] = 't';
		if (defined('_SYSNAME')) {
			$_SESSION[_SYSNAME]['success'] = 't';
			$_SESSION[_SYSNAME]['msg_ligada_library_sc'] = 't';
		}
	}

	public function setMsgErro($value) {
		$this->addDefault('erro', $value, true);
		if (!isset($_SESSION['msg']['err'])) {
			$_SESSION['msg'] = array();
		}
		$_SESSION['msg']['err'] = 't';
		if (defined('_SYSNAME')) {
			$_SESSION[_SYSNAME]['err'] = 't';
			$_SESSION[_SYSNAME]['msg_ligada_library_err'] = 't';
		}
	}

	public function setMsgAtencao($value) {
		$this->addDefault('atencao', $value, true);
		if (!isset($_SESSION['msg']) || !is_array($_SESSION['msg'])) {
			$_SESSION['msg'] = [
				'warning' => 't'
			];
		} else{
			$_SESSION['msg']['warning'] = 't';
		}		
		if (defined('_SYSNAME')) {
			$_SESSION[_SYSNAME]['warning'] = 't';
			$_SESSION[_SYSNAME]['msg_ligada_library_at'] = 't';
		}
	}

	public function getMsgSucesso() {
		return $this->getData('msg');
	}

	public function getMsgErro() {
		return $this->getData('erro');
	}

	public function getMsgAtencao() {
		return $this->getData('atencao');
	}

	public function addDefault($key, $var, $session = false) {
		if (!is_string($key)) {
			throw new Exception("O valor da chave deve ser uma string");
		}

		$this->default[0][$key] = $var;
		if ($session) {
			$rootSys = str_replace("/", "", $GLOBALS['files']['rootSys']);
			$this->defaultSession = FALSE;
			$_SESSION[$rootSys][0][$key] = $var;
		}
	}

	public function getDefault($key) {
		return $this->default[0][$key] ?? '';
	}

	public function setData($key, $value) {
		$this->addDefault($key, $value, true);
	}

	/**
	 * 
	 * @param type $key
	 * @return mixed or NULL otherwise
	 */
	public function getData($key) {
		if (isset($this->default[0][$key])) {
			return$this->default[0][$key];
		}
		$rootSys = str_replace("/", "", $GLOBALS['files']['rootSys']);
		if (isset($_SESSION[$rootSys][0][$key])) {
			return $_SESSION[$rootSys][0][$key];
		}
		return NULL;
	}

	public function getArray() {
		return $this->array;
	}

	public function show($render = false) {
		$rootSys = $GLOBALS['files']['rootSys'];
		$rootSys = str_replace("/", "", $rootSys);
		$this->msgOff();
		if (isset($_SESSION[$rootSys]) && isset($_SESSION[$rootSys][0])) {
			$this->default[0] = array_merge($_SESSION[$rootSys][0], $this->default[0]);
		}
		if (isset($_SESSION['msg']) && is_array($_SESSION['msg'])) {
			unset($_SESSION['msg']);
		}
		$this->addArray("default", isset($this->default[0]) ? $this->default : array());
		if (isset($_SESSION[$rootSys]['redirect'])) {
			$_SESSION[$rootSys]['redirect'] = 0;
			$_SESSION[$rootSys][0] = array();
			if (defined('_SYSNAME')) {
				$_SESSION[_SYSNAME]['msg_ligada_library_err'] = 'f';
				$_SESSION[_SYSNAME]['msg_ligada_library_sc'] = 'f';
				$_SESSION[_SYSNAME]['msg_ligada_library_at'] = 'f';
			}
		}
		$this->tpl->Show($render);
	}

	private function msgOff() {
		if (!defined('_SYSNAME')) {
			return;
		}
		if (isset($_SESSION[_SYSNAME]['msg_ligada_library_sc']) && $_SESSION[_SYSNAME]['msg_ligada_library_sc'] == 'f') {
			$_SESSION[_SYSNAME]['success'] = 'f';
		}
		if (isset($_SESSION[_SYSNAME]['msg_ligada_library_err']) && $_SESSION[_SYSNAME]['msg_ligada_library_err'] == 'f') {
			$_SESSION[_SYSNAME]['err'] = 'f';
		}
		if (isset($_SESSION[_SYSNAME]['msg_ligada_library_at']) && $_SESSION[_SYSNAME]['msg_ligada_library_at'] == 'f') {
			$_SESSION[_SYSNAME]['warning'] = 'f';
		}
	}

	public function mergeTemplate($tpl = '', $render = true) {
		if (trim($tpl) != '') {
			$this->setTemplate($tpl);
		}
		$this->mergeBlocks();
		if ($this->count == 0) {
			$this->mergeNavBar(count(@$this->blocks[$this->blockName]));
		}
		if (defined("TBS_NOTHING")) {
			$this->show(TBS_NOTHING);
		} else {
			$this->show();
		}
		if (!$render) {
			return $this->tpl->Source;
		}
		echo $this->tpl->Source;
		die;
	}

	public function mergeTemplateIndex($tpl = '') {
		$this->setTemplateIndex($tpl);
		$this->mergeBlocks();
		$this->mergeNavBar(count(@$this->blocks[$this->blockName]));
		$this->show();
	}

	protected function mergeBlocks() {
		if (!isset($this->blocks[$this->blockName])) {
			$this->blocks[$this->blockName] = array();
		}
		if (count($this->blocks[$this->blockName]) == 0) {
			$this->setCount(0, 0);
		}
		foreach ($this->blocks as $key => $val) {
			if ($key == $this->blockName && $this->count == 0) {
				if (defined('TBS_BYPAGE')) {
					$this->tpl->PlugIn(TBS_BYPAGE, $this->rowsLimit, $this->getPage());
				}
			}
			$this->tpl->MergeBlock($key, $val);
		}
	}

	public function mergeNavBar($total) {
		if ($total > $this->rowsLimit)
			$pages = 1;
		else
			$pages = 0;

		if ($this->page == (ceil($total / $this->rowsLimit))) // verifica se a pagina atual eh a ultima pagina
			$xrows = ($total - (($this->page - 1) * $this->rowsLimit));
		else
			$xrows = $this->rowsLimit;
		$this->addDefault('rowLimit', $this->rowsLimit);
		$this->addDefault('xrows', $xrows);
		$this->addDefault('actualRows', ($this->rowsLimit * ($this->page - 1)) + $xrows);
		$this->addDefault('total', $total);
		$this->addDefault('pages', $pages);
		if (defined('TBS_NAVBAR')) {
			$this->tpl->PlugIn(TBS_NAVBAR, 'nav', array('navsize' => 10, 'navpos' => 'centred'), $this->getPage(), $total, $this->rowsLimit);
		}
	}

	/**
	 * 
	 * @param int $page Numero da pagina atual vindo do html de paginacao
	 * @param string $blockName Nome do bloco no html
	 */
	public function setPage($page, $blockName) {
		$page_is_empty = ((int) $page) <= 1;
		$this->page = ($page_is_empty) ? 1 : $page;
		$this->blockName = $blockName;
	}

	public function getPage() {
		return (int) $this->page;
	}

	/**
	 * Diz quantas linhas seraum mostradas por paginas
	 *
	 * @param int $rowsLimit
	 */
	public function setRowsLimit($rowsLimit = 50) {
		$this->rowsLimit = $rowsLimit;
	}

	public function limitResultsTo($limit = 50) {
		$this->setRowsLimit($limit);
	}

	public function getRowsLimit() {
		return (int) $this->rowsLimit;
	}

	public function setPaginationLength($pages) {
		$this->pagesMaxView = $pages;
	}

	public function setCount($totalTuples, $totalRowsPage) {
		$this->count = $totalTuples;
		$pagesMaxView = $this->pagesMaxView > 0 ? $this->pagesMaxView : 10;
		$pages = (int) ceil($this->count / $this->rowsLimit);
		$nextPage = $this->page == $pages ? $this->page : $this->page + 1;
		$this->addDefault('countRows', $this->count);
		if ($this->page == $pages) {
			$totalRowsPage = $this->rowsLimit * ($this->page - 1) + $totalRowsPage;
		} else {
			$totalRowsPage = $totalRowsPage * $this->page;
		}
		$this->addDefault('actualLine', $totalRowsPage);
		$this->addDefault('previousPage', $this->page == 0 ? 0 : $this->page - 1);
		$this->addDefault('nextPage', $nextPage);
		$this->addDefault('lastPage', $pages);
		$end = $this->endPage($pages, $pagesMaxView);
		$ini = $this->iniPage($pages, $pagesMaxView, $end);
		$arrayPages = $this->blockPages($ini, $end);
		$this->addBlock($arrayPages, 'navigation');
	}

	private function iniPage($pages, $pagesMaxView, $end) {
		if (is_integer($pagesMaxView / 2)) {
			$middle = floor(($pagesMaxView - 1) / 2);
		} else {
			$middle = floor($pagesMaxView / 2);
		}
		$ini = $this->page - ((int) $pagesMaxView / 2) < 1 ? 1 : $this->page - $middle;
		if (($end - $ini) < ($pagesMaxView - 1)) {
			$ini = $pages - ($pagesMaxView - 1);
		}
		return $ini < 1 ? 1 : $ini;
	}

	private function endPage($pages, $pagesMaxView) {
		$end = $this->page + ((int) $pagesMaxView / 2) > $pages ? $pages : $this->page + ((int) $pagesMaxView / 2);
		if ($pages > $pagesMaxView && $end < $pagesMaxView) {
			$end = $pagesMaxView;
		}
		return $end;
	}

	private function blockPages($ini, $end) {
		$arrayPages = array();
		for ($index = $ini; $index <= $end; $index++) {
			$x = $index - 1;
			$arrayPages[$x]['page'] = $index;
			if ($index == $this->page) {
				$arrayPages[$x]['class'] = 'active';
			} else {
				$arrayPages[$x]['class'] = '';
			}
		}
		return $arrayPages;
	}

}
