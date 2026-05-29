<?php
/**
 * ARKAX Engineering — Contact Form Mailer
 *
 * SETUP:
 * 1. Descarga PHPMailer: https://github.com/PHPMailer/PHPMailer/releases
 * 2. Sube la carpeta "src" de PHPMailer a: /PHPMailer/src/  (al lado de este archivo)
 * 3. Rellena las 5 constantes de configuración de abajo con tus datos reales.
 * 4. Sube este archivo y la carpeta PHPMailer/ a tu hosting Porkbun.
 */

// ── CONFIGURACIÓN SMTP ──────────────────────────────────────────────────────
define('SMTP_HOST',  'smtp.porkbun.com');       // Servidor SMTP de tu correo
define('SMTP_PORT',  587);                        // 587 = TLS  |  465 = SSL
define('SMTP_USER',  'dariel.fuentes@arkaxeng.com');        // Tu dirección de correo (remitente)
define('SMTP_PASS',  'Kap1986 Kap1986'); // Contraseña de esa cuenta
define('MAIL_TO',    'dariel.fuentes@arkaxeng.com'); // A quién llega el aviso
// ────────────────────────────────────────────────────────────────────────────

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

// Leer JSON del body
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid request']);
    exit;
}

// Campos
$name    = trim($data['name']    ?? '');
$email   = trim($data['email']   ?? '');
$phone   = trim($data['phone']   ?? '') ?: 'Not provided';
$subject = trim($data['subject'] ?? 'General Inquiry');
$visit   = !empty($data['visit']) ? 'Yes' : 'No';
$message = trim($data['message'] ?? '');

// Validación básica
if (!$name || !$email || !$message) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing required fields']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid email address']);
    exit;
}

// Cargar PHPMailer
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';
require __DIR__ . '/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    // Configuración SMTP
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = SMTP_PORT;
    $mail->CharSet    = 'UTF-8';

    // Remitente y destinatario
    $mail->setFrom(SMTP_USER, 'ARKAX Engineering Website');
    $mail->addAddress(MAIL_TO);
    $mail->addReplyTo($email, $name); // Responder directamente al cliente

    // Asunto y cuerpo
    $mail->Subject = 'servicio';

    $mail->Body = implode("\n", [
        'WORK ORDER',
        '==========================================',
        '',
        'CLIENT INFORMATION',
        '------------------------------------------',
        'Full Name:         ' . $name,
        'Email:             ' . $email,
        'Phone:             ' . $phone,
        'Subject:           ' . $subject,
        'Technical Visit:   ' . $visit,
        '',
        'MESSAGE',
        '------------------------------------------',
        $message,
        '',
        '==========================================',
        'Submitted: ' . date('m/d/Y H:i:s'),
        'Source: ARKAX Engineering Website',
    ]);

    $mail->send();
    echo json_encode(['ok' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $mail->ErrorInfo]);
}