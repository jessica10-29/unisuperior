<?php
// config_mail.php - Configuración centralizada para el envío de correos

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

// Cargar credenciales SMTP externas
$smtpConfigPath = __DIR__ . '/secure/mail.php';
if (!file_exists($smtpConfigPath)) {
    die('Falta secure/mail.php con las credenciales SMTP.');
}
$smtp = require $smtpConfigPath;

/**
 * Crea y configura una instancia de PHPMailer
 * @return PHPMailer
 */
function obtener_mailer()
{
    $mail = new PHPMailer(true);
    $mail->CharSet = 'UTF-8';

    // === CONFIGURACIÓN SMTP ===
    $mail->isSMTP();
    $mail->SMTPDebug = $GLOBALS['smtp']['SMTP_DEBUG'];
    $mail->Host = $GLOBALS['smtp']['SMTP_HOST'];
    $mail->SMTPAuth = true;

    $mail->Username = $GLOBALS['smtp']['SMTP_USER'];
    $mail->Password = $GLOBALS['smtp']['SMTP_PASS'];

    $mail->SMTPSecure = $GLOBALS['smtp']['SMTP_SECURE'] === 'tls'
        ? PHPMailer::ENCRYPTION_STARTTLS
        : PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = $GLOBALS['smtp']['SMTP_PORT'];
    $mail->SMTPAutoTLS = false;
    $mail->Timeout = $GLOBALS['smtp']['TIMEOUT'];

    // Configuración para saltar errores de certificados SSL
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    // Configuración del remitente
    $mail->setFrom($GLOBALS['smtp']['FROM_EMAIL'], $GLOBALS['smtp']['FROM_NAME']);

    return $mail;
}
