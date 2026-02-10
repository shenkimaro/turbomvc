<?php

/* $email = new EMail();
  $email->setTo('mauricio.rocha@ueg.br');
  $email->setNomeTo('Mauricio Matriz da Rocha');
  if ($email->ehEmail()) {
  $email->setAssunto('Assunto');
  $email->setCorpo('Corpo do e-mail linha1.');
  $email->setCorpo('Corpo do e-mail linha2.');
  $email->setCorpo('Corpo do e-mail linha3.');

  if (!$email->send())
  die("Não foi possível enviar o E-mail.");
  } else
  die("E-mail invalido! Não foi possível enviar o E-mail."); */
/**
 * @deprecated 
 */
class Email {

	private $server;
	private $port;
	private $conn;
	private $date;
	private $usr;
	private $pwd;
	private $from;
	private $to;
	private $nome_from;
	private $nome_to;
	private $assunto;
	private $corpo;
	public static $instance;

	/**
	 * @todo: make these configurable via env vars or config file
	 */
	public function __construct() {
		$this->server = "server.com";
		$this->port = 465;
		$mail->Timeout = 30;
		$this->usr = "noreply@server.com";
		$this->pwd = "changeme"; // @todo: load SMTP password from config/env
		$this->date = "Date: " . date('r', time());
		$this->from = "noreply@server.com";
		$this->nome_from = "System";
	}

	public static function getInstance() {
		if (!isset(self::$instance)) {
			self::$instance = new Email();
		}
		return self::$instance;
	}

	public function ehEmail() {
		$this->to = strtolower($this->to);

		return Validador::ehEmail($this->to);
	}

	public function send() {
		$this->conn = @fsockopen($this->server, $this->port);

		if ($this->conn <= 0) {
			return false;
		}

		fputs($this->conn, "HELO mail.ueg.br\r\n", 512);
		fputs($this->conn, "AUTH LOGIN\r\n", 512);
		fputs($this->conn, base64_encode($this->usr) . "\r\n", 512);
		fputs($this->conn, base64_encode($this->pwd) . "\r\n", 512);
		fputs($this->conn, "MAIL FROM:<$this->from>\r\n", 512);
		fputs($this->conn, "RCPT TO:<$this->to>\r\n", 512);
		fputs($this->conn, "DATA\r\n", 512);
		fputs($this->conn, "MIME-Version: 1.0\r\n");
		fputs($this->conn, "Content-Type: text/html; charset=UTF-8\r\n");
		fputs($this->conn, "Date: $this->date\r\n");
		fputs($this->conn, "From: $this->nome_from <$this->from>\r\n");
		fputs($this->conn, "To: $this->nome_to <$this->to>\r\n");
		fputs($this->conn, "Reply-To: $this->nome_from <$this->from>\r\n");
		fputs($this->conn, "Subject: $this->assunto\r\n");
		fputs($this->conn, "\r\n");
		fputs($this->conn, "$this->corpo\r\n.\r\n");
		fputs($this->conn, "QUIT\r\n", 512);
		fclose($this->conn);

		return true;
	}

	public function setUsr($usr) {
		$this->usr = $usr;
	}

	public function setPwd($pwd) {
		$this->pwd = $pwd;
	}

	public function setTo($to) {
		$this->to = $to;
	}

	public function setNomeTo($nome_to) {
		$this->nome_to = $nome_to;
	}

	public function setNomeFrom($nome_from) {
		$this->nome_from = $nome_from;
	}

	public function setAssunto($assunto) {
		$this->assunto = $assunto;
	}

	public function setCorpo($corpo) {
		$this->corpo = $this->corpo . '<br>' . $corpo;
	}

	public function sendEmail($to='', $title='', $message='') {
		$this->setAssunto($title);
		$this->corpo = "";
		$this->setCorpo($message);
		$toArray = explode(';', $to);
		foreach ($toArray as $value) {
			$this->setTo($value);
			if (!$this->send())
				return false;
		}
		return true;
	}

	public function sendEmailErr($to='', $title='', $message='') {
		$this->setNomeTo("Desenvolvedor");
		$this->setNomeFrom(strtoupper($GLOBALS['template']['systemName']));

		$variables = "<pre>" . print_r($_GET, true) . "</pre>";
		$message.="<br><b>Variaveis postadas Via GET:</b>";
		$message.=$variables;
		$variables = "<pre>" . print_r($_POST, true) . "</pre>";
		$message.="<br><b>Variaveis postadas Via POST:</b>";
		$message.=$variables;
		$variables = '';
		$variables = "<pre>" . print_r($_SESSION, true) . "</pre>";
		$message .= "<BR><BR><b>Valores das variaveis de sessão:</b><br>" . $variables;

		$ip = trim(@$HTTP_SERVER_VARS["HTTP_X_FORWARDED_FOR"]) ? @$HTTP_SERVER_VARS["HTTP_X_FORWARDED_FOR"] : $_SERVER['REMOTE_ADDR'];
		$message .= "<br><b>Endereço IP Local:</b> " . $ip;
		$ip = $_SERVER['REMOTE_ADDR'];
		$message .= "<br><b>Endereço IP Internet:</b> " . $ip;
		$message .= "<br><b>Host:</b> " . gethostbyaddr($ip) . " ";
		$usuarioLogado = unserialize(@$_SESSION["usuario"]["oid"]);
		if (is_object($usuarioLogado)) {
			$message .= "<br><b>Usuario Logado: " . $usuarioLogado->getNome();
			$message .= "<br><b>Usuario Logado id_pessoa: " . $usuarioLogado->getRefPessoa();
		}

		if (!$this->sendEmail($to, $title, $message))
			echo "Nao foi possivel encaminhar o email de erro, entre em contato com o desenvolvimento";
	}

}

?>
