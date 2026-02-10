<?php

if (!class_exists('PHPMailer')) {
    require __DIR__ . '/phpmailer/PHPMailer.php';
}

class Mail {

    /**
     *
     * @param string $assunto
     * @param string $para
     * @param string $nome
     * @param string $mensagem
     * @param string $from
     * @param string $fromName
     * @return boolean
     * @deprecated veja sendEmail
     */
    public static function enviarEmail($assunto, $para, $nome, $mensagem, $from = "", $fromName = "", $attachmentList = []) {
        $message = "";
        $message .= $mensagem;
        $mail = self::getEmail($from, $fromName); // set word wrap to 50 characters
        if (is_array($para)) {
            foreach ($para as $var => $value) {
                $mail->AddAddress($value, $nome[$var]);
            }
        } else {
            $mail->AddAddress($para, $nome);
        }
        $mail->Subject = $assunto;
        $mail->Body = $message;
        $mail->AltBody = "";
        foreach ($attachmentList as $attachment) {
            $mail->AddAttachment($attachment);
        }
        if (!$mail->Send()) {
            Debug::tail("Erro email: " . $mail->ErrorInfo);
//			die ("Erro: ".$mail->ErrorInfo."");
            return false;
        }
        return true;
    }

    public static function sendEmail(string $subject, string $to, string $name, string $message, string $from = "", string $fromName = "", array $attachmentList = []) {
        $mail = static::getEmail($from, $fromName); // set word wrap to 50 characters
        $mail->AddAddress($to, $name);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->AltBody = "";
        foreach ($attachmentList as $attachment) {
            $mail->AddAttachment($attachment);
        }
        if (!$mail->Send()) {
            Debug::tail("Erro email: " . $mail->ErrorInfo);
            throw new Exception("Erro email: " . $mail->ErrorInfo);
        }
    }

    /**
     * 
     * @param array $toArray [fulano@email.com, siclano@email.org]
     * @param array $nameArray [fulano, siclano]
     * @param string $subject
     * @param string $message
     * @param string $from
     * @param string $fromName
     * @param array $attachmentList
     * @throws Exception
     */
    public static function sendEmailToMany(array $toArray, array $nameArray, string $subject, string $message, string $from = "", string $fromName = "", array $attachmentList = []) {
        $mail = self::getEmail($from, $fromName); // set word wrap to 50 characters
        foreach ($toArray as $index => $value) {
            $mail->AddAddress($value, $nameArray[$index]);
        }
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->AltBody = "";
        foreach ($attachmentList as $attachment) {
            $mail->AddAttachment($attachment);
        }
        if (!$mail->Send()) {
            Debug::tail("Erro email: " . $mail->ErrorInfo);
            throw new Exception("Erro email: " . $mail->ErrorInfo);
        }
    }

    /**
     *
     * @param string $assunto
     * @param string $para
     * @param string $nome
     * @param string $mensagem
     * @param string $from
     * @param string $fromName
     * @param string $username
     * @param string $senha
     * @return boolean
     */
    public static function enviarEmailUsuario($username, $senha, $assunto, $para, $nome, $mensagem, $from = "", $fromName = "", $attachmentList = []) {
        $message = "";
        $message .= $mensagem;
        $mail = self::getEmail($from, $fromName); // set word wrap to 50 characters
        $mail->Username = $username;  // SMTP username
        $mail->Password = $senha; // SMTP password;
        if (is_array($para)) {
            foreach ($para as $var => $value) {
                $mail->AddAddress($value, $nome[$var]);
            }
        } else {
            $mail->AddAddress($para, $nome);
        }
        $mail->Subject = $assunto;
        $mail->Body = $message;
        $mail->AltBody = "";
        foreach ($attachmentList as $attachment) {
            $mail->AddAttachment($attachment);
        }
        if (!$mail->Send()) {
//			die ("Erro: ".$mail->ErrorInfo."");
            return false;
        }
        return true;
    }

    /**
     *
     * @return PHPMailer
     * @todo: make these configurable via env vars or config file
     */
    protected static function getEmail($from = "", $fromName = "") {
        if ($fromName == "")
            $fromName = "System";
        if ($from == "")
            $from = "noreply@server.com";


        $mail = new PHPMailer();
        $mail->IsSMTP();
        $mail->Port = 465;
        $mail->Host = "smtp.server.com"; // specify main and backup server
        $mail->SMTPAuth = true; // turn on SMTP authentication
        $mail->SMTPSecure = "ssl";
        $mail->Username = "noreply@server.com";  // SMTP username
        $mail->Password = 'changeme'; // @todo: load SMTP password from config/env

        $mail->Priority = 1;
        $mail->FromName = $fromName;
        $mail->From = $from;
        $mail->AddReplyTo($from, $fromName);
        $mail->WordWrap = 50;
        $mail->IsHTML(true);
        return $mail;
    }
}
