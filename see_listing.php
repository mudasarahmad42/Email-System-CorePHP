<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");


// INCLUDING DATABASE AND MAKING OBJECT
require __DIR__ . '/classes/Database.php';
require __DIR__ . '/middlewares/Auth.php';


$db_connection = new Database();
$conn = $db_connection->dbConnection();


$allHeaders = getallheaders();
$auth = new Auth($conn, $allHeaders);

$returnData = [];

// select all query
$query = "SELECT email_to, email_from, subject, body FROM emails";

// prepare query statement
$stmt = $conn->prepare($query);

// execute query
$stmt->execute();

$num = $stmt->rowCount();


function msg($success, $status, $message, $extra = [])
{
    return array_merge([
        'success' => $success,
        'status' => $status,
        'message' => $message
    ], $extra);
}

$user_data = $auth->isAuth();

if(!isset($user_data))
{
    echo "Add token please";
    exit;
}

$merchant_id = $user_data['id'];

$check_user = "SELECT `role` FROM `users` WHERE `id`=:merchant";
$check_user_stmt = $conn->prepare($check_user);
$check_user_stmt->bindValue(':merchant', $merchant_id, PDO::PARAM_STR);
$check_user_stmt->execute();


$result = $check_user_stmt->fetch(PDO::FETCH_OBJ);
if ($result->role == 'MERCHANT' || $result->role == 'ADMIN') {

    // check if more than 0 record found
    if ($num > 0) {

        // products array
        $email_arr = array();
        $email_arr["emails"] = array();

        // retrieve our table contents
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

            $email_item = array(
                "to" => $row['email_to'],
                "from" => $row['email_from'],
                "subject" => $row['subject'],
                "body" => $row['body'],
            );
            extract($row);
            array_push($email_arr["emails"], $email_item);
        }

        // set response code - 200 OK
        http_response_code(200);

        // show products data in json format
        echo json_encode($email_arr);
    } else {

        // set response code - 404 Not found
        http_response_code(404);

        // tell the user no products found
        echo json_encode(
            array("message" => "No email found.")
        );
    }
} else {
    $returnData = [
        "success" => 0,
        "status" => 401,
        "message" => "Unauthorized"
    ];
}

echo json_encode($returnData);