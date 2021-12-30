<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
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

function base64($TakeImage)
{
    $image_name = ""; //declaring the image name variable
    $image_name = round(time() * 1000) . ".jpg"; //Giving new name to image.
    $image_upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/ProgrammersForceTrainee/email_system2/images/S_USERS/' . $image_name; //Set the path where we need to upload the image.
    $flag = file_put_contents($image_upload_dir, base64_decode($TakeImage));

    if ($flag) { //Basically flag variable is set for if image get uploaded it will give us true then we will save it in database or else we give response for fail.
        return $image_name;
    } else {
        return false;
    }
}

// INCLUDING DATABASE AND MAKING OBJECT
require __DIR__ . '/classes/Database.php';
// require __DIR__ . '/classes/JwtHandler.php';
require __DIR__ . '/middlewares/Auth.php';


$db_connection = new Database();
$conn = $db_connection->dbConnection();

$allHeaders = getallheaders();
$auth = new Auth($conn, $allHeaders);

// GET DATA FORM REQUEST
$data = json_decode(file_get_contents("php://input"));
$returnData = [];

// IF REQUEST METHOD IS NOT POST
if ($_SERVER["REQUEST_METHOD"] != "POST") :
    $returnData = msg(0, 404, 'Page Not Found!');

// CHECKING EMPTY FIELDS
elseif (
    !isset($data->name)
    || !isset($data->email)
    || !isset($data->password)
    || !isset($data->duty)
    || empty(trim($data->name))
    || empty(trim($data->email))
    || empty(trim($data->password))
    || empty(trim($data->duty))
) :

    $fields = ['fields' => ['name', 'email', 'password', 'duty', 'image']];
    $returnData = msg(0, 422, 'Please Fill in all Required Fields!', $fields);

// IF THERE ARE NO EMPTY FIELDS THEN-
else :

    $name = trim($data->name);
    $email = trim($data->email);
    $password = trim($data->password);
    $duty = trim($data->duty);

    $uploadedImage = $data->image;

    $user_data = $auth->isAuth();

    if (!isset($user_data)) {
        echo "Add token please";
        exit;
    }


    $merchant_id = $user_data['id'];

    $check_user = "SELECT * FROM `users` WHERE `id`=:merchant";
    $check_user_stmt = $conn->prepare($check_user);
    $check_user_stmt->bindValue(':merchant', $merchant_id, PDO::PARAM_STR);
    $check_user_stmt->execute();


    $result = $check_user_stmt->fetch(PDO::FETCH_OBJ);
    // print $result->role;

    if ($result->role == 'MERCHANT' || $result->role == 'ADMIN' || $result->duty == 'accountCreator') {


        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) :
            $returnData = msg(0, 422, 'Invalid Email Address!');

        else :
            try {

                $image = base64($uploadedImage);


                $check_email = "SELECT `email` FROM `users` WHERE `email`=:email";
                $check_email_stmt = $conn->prepare($check_email);
                $check_email_stmt->bindValue(':email', $email, PDO::PARAM_STR);
                $check_email_stmt->execute();

                if ($check_email_stmt->rowCount()) :
                    $returnData = msg(0, 422, 'This E-mail already in use!');

                else :
                    $insert_query = "INSERT INTO `users`(`name`,`email`,`password`,`role`,`duty`,`merchant_id`,`image`) VALUES(:name,:email,:password,:role,:duty,:merchant_id,:image)";

                    $insert_stmt = $conn->prepare($insert_query);

                    // DATA BINDING
                    $insert_stmt->bindValue(':name', htmlspecialchars(strip_tags($name)), PDO::PARAM_STR);
                    $insert_stmt->bindValue(':email', $email, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':password', password_hash($password, PASSWORD_DEFAULT), PDO::PARAM_STR);
                    $insert_stmt->bindValue(':role', 'S_USER');
                    $insert_stmt->bindValue(':duty',  $duty, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':merchant_id',  $merchant_id, PDO::PARAM_STR);

                    $insert_stmt->bindValue(':image', $image, PDO::PARAM_STR);

                    //  //Statement to check error in your query
                    //  if (!$insert_stmt->execute()) {
                    //     print_r($insert_stmt->errorInfo());
                    // }

                    $insert_stmt->execute();

                    $returnData = msg(1, 201, 'You have successfully registered.');

                endif;
            } catch (PDOException $e) {
                $returnData = msg(0, 500, $e->getMessage());
            }
        endif;
    } else {
        echo "<h1>You are not authorized to use this API</h1>";
        exit;
    }

endif;

echo json_encode($returnData);