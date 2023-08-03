<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'phpmailer/PHPMailerAutoload.php';



// $_REQUEST[]; 

$addAddress = 'durairaj.pixel@gmail.com';
$Subject    = "Test mail from DEVTEAM";
$msgHTML    = "<h1>Hello </h1>";



$mail       = new PHPMailer;
$mail->isSMTP();
$mail->isHTML(true);
$mail->Host       = 'mail.gbssystems.com';
$mail->Port       = 587;
$mail->SMTPSecure = 'tls';
$mail->SMTPAuth   = true;
$mail->Username   = 'support@gbssystems.com';
$mail->Password   = 'GHltfCmPf3O';
$mail->From       = 'support@gbssystems.com';
$mail->FromName   = 'GBS Customer Care';
$mail->addAddress($addAddress);
$mail->Subject = $Subject;
$mail->msgHTML($msgHTML);
$mail->SMTPDebug   = 3;
$mail->Debugoutput = 'html';



if (!$mail->send()) {
    return [
        "status" => false,
        "message" => "Mailer Error: " . $mail->ErrorInfo
    ];
}

return [
    "status" => true,
    "message" => "Mail send Success!"
];
