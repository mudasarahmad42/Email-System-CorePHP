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

    $user_data = $auth->isAuth();

    if (!isset($user_data)) {
        echo "Add token please";
        exit;
    }


    if ($to == null or $subject == null or $body == null) {
        $fields = ['fields' => ['To', 'Subject', 'Body']];
        $returnData = msg(0, 422, 'Please Fill! All Required Fields!', $fields);
    } elseif ($to == 1 || $from == 1) {
        $returnData = msg(0, 422, 'There is an Invalid Email Address!');
    } else {
        $merchant_id = $user_data['id'];

        //Authenticating
        $check_user = "SELECT * FROM `users` WHERE `id`=:merchant";
        $check_user_stmt = $conn->prepare($check_user);
        $check_user_stmt->bindValue(':merchant', $merchant_id, PDO::PARAM_STR);
        $check_user_stmt->execute();


        $result = $check_user_stmt->fetch(PDO::FETCH_OBJ);


        if ($result->role == 'MERCHANT' || $result->role == 'ADMIN') {

            $mail->isSMTP();                      // Set mailer to use SMTP 
            $mail->Host = 'smtp.gmail.com';       // Specify main and backup SMTP servers 
            $mail->SMTPAuth = true;               // Enable SMTP authentication 
            $mail->Username = $from;   // SMTP username (sender email address)
            $mail->Password = $sender_key;   // SMTP password 
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

                if (isset($data->cc)) {
                    //keep record in email
                    $email_record =  "INSERT INTO `emails`
                                  SET
                                  email_to=:email_to, email_from=:email_from, subject=:subject, user_id=:user_id, body=:body, cc=:cc, status=:status";

                    $email_record_stmt = $conn->prepare($email_record);
                    $email_record_stmt->bindValue(':user_id', $merchant_id, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':subject', $subject, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':email_from', $from, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':email_to', $to, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':body', $body, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':cc', $cc, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':status', 'SENDING FAILED');
                    $email_record_stmt->execute();
                }

                if (isset($data->bcc)) {
                    //keep record in email
                    $email_record =  "INSERT INTO `emails`
            SET
            email_to=:email_to, email_from=:email_from, subject=:subject, user_id=:user_id, body=:body, status=:status, bcc=:bcc";

                    $email_record_stmt = $conn->prepare($email_record);
                    $email_record_stmt->bindValue(':user_id', $merchant_id, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':subject', $subject, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':email_from', $from, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':email_to', $to, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':body', $body, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':status', 'SENDING FAILED');
                    $email_record_stmt->bindValue(':bcc', $bcc, PDO::PARAM_STR);
                    $email_record_stmt->execute();
                }

                if (isset($data->cc) && isset($data->bcc)) {
                    //keep record in email
                    $email_record =  "INSERT INTO `emails`
                SET
                email_to=:email_to, email_from=:email_from, subject=:subject, user_id=:user_id, body=:body, cc=:cc, bcc=:bcc, status=:status";

                    $email_record_stmt = $conn->prepare($email_record);
                    $email_record_stmt->bindValue(':user_id', $merchant_id, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':subject', $subject, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':email_from', $from, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':email_to', $to, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':body', $body, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':cc', $cc, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':bcc', $bcc, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':status', 'SENDING FAILED');
                    $email_record_stmt->execute();
                }

                if (!isset($data->cc) && !isset($data->bcc)) {
                    //keep record in email
                    $email_record =  "INSERT INTO `emails`
                SET
                email_to=:email_to, email_from=:email_from, subject=:subject, user_id=:user_id, body=:body, status=:status";

                    $email_record_stmt = $conn->prepare($email_record);
                    $email_record_stmt->bindValue(':user_id', $merchant_id, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':subject', $subject, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':email_from', $from, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':email_to', $to, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':body', $body, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':status', 'SENDING FAILED');
                    $email_record_stmt->execute();
                }
                echo 'Email could not be sent. Mailer Error: ' . $mail->ErrorInfo;
            } else {

                if (isset($data->cc)) {
                    //keep record in email
                    $email_record =  "INSERT INTO `emails`
                                  SET
                                  email_to=:email_to, email_from=:email_from, subject=:subject, user_id=:user_id, body=:body, cc=:cc, status=:status";

                    $email_record_stmt = $conn->prepare($email_record);
                    $email_record_stmt->bindValue(':user_id', $merchant_id, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':subject', $subject, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':email_from', $from, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':email_to', $to, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':body', $body, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':cc', $cc, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':status', 'SENT');
                    $email_record_stmt->execute();
                }

                if (isset($data->bcc)) {
                    //keep record in email
                    $email_record =  "INSERT INTO `emails`
            SET
            email_to=:email_to, email_from=:email_from, subject=:subject, user_id=:user_id, body=:body, status=:status, bcc=:bcc";

                    $email_record_stmt = $conn->prepare($email_record);
                    $email_record_stmt->bindValue(':user_id', $merchant_id, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':subject', $subject, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':email_from', $from, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':email_to', $to, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':body', $body, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':status', 'SENT');
                    $email_record_stmt->bindValue(':bcc', $bcc, PDO::PARAM_STR);
                    $email_record_stmt->execute();
                }

                if (isset($data->cc) && isset($data->bcc)) {
                    //keep record in email
                    $email_record =  "INSERT INTO `emails`
                SET
                email_to=:email_to, email_from=:email_from, subject=:subject, user_id=:user_id, body=:body, cc=:cc, bcc=:bcc, status=:status";

                    $email_record_stmt = $conn->prepare($email_record);
                    $email_record_stmt->bindValue(':user_id', $merchant_id, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':subject', $subject, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':email_from', $from, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':email_to', $to, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':body', $body, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':cc', $cc, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':bcc', $bcc, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':status', 'SENT');
                    $email_record_stmt->execute();
                }

                if (!isset($data->cc) && !isset($data->bcc)) {
                    //keep record in email
                    $email_record =  "INSERT INTO `emails`
                SET
                email_to=:email_to, email_from=:email_from, subject=:subject, user_id=:user_id, body=:body, status=:status";

                    $email_record_stmt = $conn->prepare($email_record);
                    $email_record_stmt->bindValue(':user_id', $merchant_id, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':subject', $subject, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':email_from', $from, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':email_to', $to, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':body', $body, PDO::PARAM_STR);
                    $email_record_stmt->bindValue(':status', 'SENT');
                    $email_record_stmt->execute();
                }

                $query = "SELECT *,COUNT(email) AS num FROM users WHERE id= :id";
                $insert = $conn->prepare($query);
                $insert->bindValue(':id', $merchant_id);
                $insert->execute();

                $row = $insert->fetch(PDO:: FETCH_ASSOC);
                if($row['num'] > 0){
                    $balance = $row['balance'];
                    $updatedBalance = $balance - 0.0489;
                    $sql = "UPDATE users SET balance = '$updatedBalance' WHERE id = '$merchant_id'";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute();
                }
                else{
                    echo "Something Went Wrong";
                }

                echo 'Email has been sent.';
            }
        } else {
            echo "<h1>You are not authorized to use this API</h1>";
        }
    }
} else {
    $returnData = msg(0, 404, 'Page Not Found!');
}

echo json_encode($returnData);
