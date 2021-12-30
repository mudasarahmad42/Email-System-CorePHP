<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

function msg($success, $status, $message, $extra = [])
{
    return array_merge([
        'success' => $success,
        'status' => $status,
        'message' => $message
    ], $extra);
}

require 'classes/Database.php';
require 'classes/stripe.php';
require 'middlewares/Auth.php';

// require '../classes/JwtHandler.php';

$db_connection = new Database();
$db = $db_connection->dbConnection();

$allHeaders = getallheaders();
$auth = new Auth($db, $allHeaders);

// $data = json_decode(file_get_contents("php://input"));
$returnData = [];

// IF REQUEST METHOD IS NOT EQUAL TO POST

$stripe = new stripe($db);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data =  [
        'card[number]' => $_POST['number'],
        'card[exp_month]' => $_POST['expMonth'],
        'card[exp_year]' => $_POST['expYear'],
        'card[cvc]' => $_POST['cvc'],
    ];
    $userdata = $auth->isAuth();

    //print_r($userdata);

    $customer_id = $userdata['id'];

    $email = $userdata['email'];


    $check_user = "SELECT `role` FROM `users` WHERE `id`=:merchant";
    $check_user_stmt = $db->prepare($check_user);
    $check_user_stmt->bindValue(':merchant', $customer_id, PDO::PARAM_STR);
    $check_user_stmt->execute();


    $result = $check_user_stmt->fetch(PDO::FETCH_OBJ);

    if ($result->role == 'MERCHANT' || $result->role == 'ADMIN') {

        $amount = $_POST['amount'];
        $stripTokenResponse = $stripe->getStripeTokens($data);
        if ($stripTokenResponse) {
            $stripTokenRes = json_decode($stripTokenResponse);
            $stripToken =  $stripTokenRes->id;
            // $addBalance = $stripe->charge($stripToken,$_POST['amount']);
            if ($stripe->charge_amount($stripToken, $_POST['amount'], $email)) {
                if ($stripe->addPayment($data, $customer_id, $amount)) {
                    if ($stripe->updateBalance($amount, $customer_id)) {
                        $returnData = msg(1, 201, 'Your Payment recieved successfully');
                    } else {
                        $returnData = msg(0, 422, 'Something went Wrong');
                    }
                } else {
                    $returnData = msg(0, 422, 'Payment fail, please try again');
                }
            } else {
                $returnData = msg(0, 422, 'Something went Wrong');
            }
        } else {
            $returnData = msg(0, 422, 'No token.');
        }
    }
    else
    {
        $returnData = msg(0, 401, 'You are not authorized');
    }
} else {
    $returnData = msg(0, 404, 'Page Not Found!');
}

echo json_encode($returnData);
