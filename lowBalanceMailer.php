<?php

// Import PHPMailer classes into the global namespace 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';


// INCLUDING DATABASE AND MAKING OBJECT
require __DIR__ . '/classes/Database.php';
require __DIR__ . '/middlewares/Auth.php';


$db_connection = new Database();
$conn = $db_connection->dbConnection();

$allHeaders = getallheaders();
$auth = new Auth($conn, $allHeaders);



$mail = new PHPMailer;

$data = json_decode(file_get_contents("php://input"));
$returnData = [];

//---------------FUNCTIONS--------------------------
function msg($success, $status, $message, $extra = [])
{
    return array_merge([
        'success' => $success,
        'status' => $status,
        'message' => $message
    ], $extra);
}

//FILTER EMAIL ADDRESS
function test_email($emails)
{
    if (empty($emails)) {
        return null;
    } else {
        $email = filter_var($emails, FILTER_SANITIZE_EMAIL);
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        } else {
            return 1;
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // $sender_username = $data->username;
    $sender_username = 'Test Email';
    $sender_key = $data->password;
    $from = test_email($data->from);
    $to = test_email($data->to);

    if (isset($data->cc)) {
        $cc = test_email($data->cc);
    }

    if (isset($data->bcc)) {
        $bcc = test_email($data->bcc);
    }


    $subject = $data->subject;
    $body = $data->body;

    // $user_data = $auth->isAuth();

    // if (!isset($user_data)) {
    //     echo "Add token please";
    //     exit;
    // }


    if ($to == null or $subject == null or $body == null) {
        $fields = ['fields' => ['To', 'Subject', 'Body']];
        $returnData = msg(0, 422, 'Please Fill! All Required Fields!', $fields);
    } elseif ($to == 1 || $from == 1) {
        $returnData = msg(0, 422, 'There is an Invalid Email Address!');
    } else 
    {

            $mail->isSMTP();                      // Set mailer to use SMTP 
            $mail->Host = 'smtp.gmail.com';       // Specify main and backup SMTP servers 
            $mail->SMTPAuth = true;               // Enable SMTP authentication 
            $mail->Username = $from;              // SMTP username (sender email address)
            $mail->Password = $sender_key;        // SMTP password 
            $mail->SMTPSecure = 'tls';            // Enable TLS encryption, `ssl` also accepted 
            $mail->Port = 587;                    // TCP port to connect to 

            // Sender info 
            $mail->setFrom($from, $sender_username);
            $mail->addReplyTo($from, $sender_username);

            // Add a recipient 
            $mail->addAddress($to);

            if (isset($data->cc)) {
                $mail->addCC($cc);
            }


            if (isset($data->cc)) {
                $mail->addBCC($bcc);
            }

            // Set email format to HTML 
            $mail->isHTML(true);

            // Mail subject 
            $mail->Subject = $subject;
            $mail->addAttachment('download.png', 'Programmers Force');

            // Mail body content 
            $bodyContent = '<h1>Email by ' . $sender_username . '</h1>';
            $bodyContent .= '<p>' . $body . '</p>';
            $mail->Body    = $bodyContent;

            // Send email 
            if (!$mail->send()) {
                echo 'Email could not be sent. Mailer Error: ' . $mail->ErrorInfo;
            } else {
                echo 'Email has been sent.';
            }
        } 
} else {
    $returnData = msg(0, 404, 'Page Not Found!');
}

echo json_encode($returnData);