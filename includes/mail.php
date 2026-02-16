<?php
/**
 * SMTP Mail Helper - Inventory System
 * 
 * TO THE USER: 
 * This version uses a custom SMTP client to talk directly to Gmail.
 * It bypasses the limitations of the PHP mail() function on Windows.
 */

// CONFIGURATION (Set by User)
define('SMTP_USER', 'depedojt@gmail.com');
define('SMTP_PASS', 'wootxkzzzmizdgam'); 
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587); // STARTTLS port

/**
 * Custom Simple SMTP Implementation
 */
class SimpleSMTP {
    private $socket;
    private $error;

    public function send($to, $subject, $message, $fromName, $fromEmail) {
        try {
            // 1. Connect
            $this->socket = fsockopen(SMTP_HOST, SMTP_PORT, $errno, $errstr, 10);
            if (!$this->socket) throw new Exception("Connection failed: $errstr");
            $this->getResponse();

            // 2. EHLO
            $this->sendCommand("EHLO " . $_SERVER['HTTP_HOST']);
            
            // 3. STARTTLS
            $this->sendCommand("STARTTLS");
            if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new Exception("Failed to enable crypto (STARTTLS). Check if OpenSSL is enabled in php.ini");
            }

            // 4. EHLO again after TLS
            $this->sendCommand("EHLO " . $_SERVER['HTTP_HOST']);

            // 5. Auth Login
            $this->sendCommand("AUTH LOGIN");
            $this->sendCommand(base64_encode(SMTP_USER));
            $this->sendCommand(base64_encode(SMTP_PASS));

            // 6. Mail From
            $this->sendCommand("MAIL FROM:<" . SMTP_USER . ">");

            // 7. Recipient
            $this->sendCommand("RCPT TO:<$to>");

            // 8. Data
            $this->sendCommand("DATA");
            
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "To: <$to>\r\n";
            $headers .= "From: $fromName <$fromEmail>\r\n";
            $headers .= "Subject: $subject\r\n";
            $headers .= "Date: " . date('r') . "\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
            
            $fullMessage = $headers . "\r\n" . $message . "\r\n.\r\n";
            fwrite($this->socket, $fullMessage);
            $this->getResponse();

            // 9. Quit
            $this->sendCommand("QUIT");
            fclose($this->socket);
            return true;

        } catch (Exception $e) {
            $this->error = $e->getMessage();
            error_log("SMTP Error: " . $this->error);
            if ($this->socket) fclose($this->socket);
            return false;
        }
    }

    private function sendCommand($cmd) {
        fwrite($this->socket, $cmd . "\r\n");
        return $this->getResponse();
    }

    private function getResponse() {
        $response = "";
        while ($str = fgets($this->socket, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) == " ") break;
        }
        return $response;
    }

    public function getLastError() {
        return $this->error;
    }
}

function sendRecoveryPIN($recipientEmail, $pin) {
    if (SMTP_PASS === 'YOUR_GOOGLE_APP_PASSWORD_HERE' || empty(SMTP_PASS)) {
        error_log("Email not sent: SMTP_PASS is missing.");
        return false;
    }

    $subject = "Password Recovery PIN - Inventory System";
    $fromName = "DepEd Inventory System";
    $fromEmail = SMTP_USER;

    $message = "
    <html>
    <head>
        <style>
            .container { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; color: #333; border: 1px solid #e2e8f0; border-radius: 16px; max-width: 600px; margin: 0 auto; }
            .header { color: #10b981; font-size: 24px; font-weight: 800; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 1px; }
            .pin-box { background: #ecfdf5; border: 2px dashed #10b981; border-radius: 12px; padding: 30px; text-align: center; font-size: 42px; letter-spacing: 10px; color: #065f46; font-weight: 900; margin: 10px 0; }
            .info { line-height: 1.6; color: #475569; }
            .footer { font-size: 11px; color: #94a3b8; margin-top: 40px; border-top: 1px solid #f1f5f9; padding-top: 15px; text-align: center; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>Your Verification Code</div>
            <div class='info'>
                <p>Hello,</p>
                <p>Use the 6-digit PIN below to reset your Inventory System password.</p>
            </div>
            <div class='pin-box'>$pin</div>
            <div class='info'>
                <p><strong>Expires in 5 minutes.</strong></p>
                <p>If you didn't request this, please ignore this email.</p>
            </div>
            <div class='footer'>
                © " . date('Y') . " DepEd Bogo City Inventory System.
            </div>
        </div>
    </body>
    </html>
    ";

    $mailer = new SimpleSMTP();
    return $mailer->send($recipientEmail, $subject, $message, $fromName, $fromEmail);
}
