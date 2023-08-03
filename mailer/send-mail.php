<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'phpmailer/PHPMailerAutoload.php';

function send_mail($tomail, $mlsubject, $bdymsg, $for, $storename)
{

    // $_REQUEST[];

    $to = 'prabhu.k.pixel@gmail.com';

    $subject = $mlsubject;

    $mail = new PHPMailer;
    $mail->isSMTP();
    $mail->isHTML(true);
    $mail->Host = 'mail.gbssystems.com';
    $mail->Port       = 587;
    $mail->SMTPSecure = 'tls';
    $mail->SMTPAuth   = true;
    $mail->Username = 'support@gbssystems.com';
    $mail->Password = 'GHltfCmPf3O';
    $mail->From = 'support@gbssystems.com';
    $mail->FromName = 'GBS Customer Care';
    $mail->addAddress($to);
    $mail->Subject = $subject;
    $mail->msgHTML($bdymsg);



    $mail->SMTPDebug = 3;
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

}

echo send_mail('pravin@pixel-studios.com', 'SMTP Mail function', 'Test mail content', '1', 'GBS');
