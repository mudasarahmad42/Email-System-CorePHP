<?php
//Headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");


// INCLUDING DATABASE AND MAKING OBJECT
require __DIR__ . '/classes/Database.php';
$db_connection = new Database();
$conn = $db_connection->dbConnection();


// select email and balance of all users where balance is less than 5
$query = "SELECT email, balance FROM users WHERE balance < 5";

// prepare query statement
$stmt = $conn->prepare($query);

// execute query
$stmt->execute();


//-----------FUNCTIONS---------------------
//Function to call external API inside our lowBalance API
function callAPI($method, $url, $data)
{
    $curl = curl_init();
    switch ($method) {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);
            if ($data)
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
            if ($data)
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            break;
        default:
            if ($data)
                $url = sprintf("%s?%s", $url, http_build_query($data));
    }
    // OPTIONS:
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'APIKEY: 111111111111111111111',
        'Content-Type: application/json',
    ));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    // EXECUTE:
    $result = curl_exec($curl);
    if (!$result) {
        die("Connection Failure");
    }
    curl_close($curl);
    return $result;
}

//Function to return API status messages
function msg($success, $status, $message, $extra = [])
{
    return array_merge([
        'success' => $success,
        'status' => $status,
        'message' => $message
    ], $extra);
}

// GET DATA FORM REQUEST
$data = json_decode(file_get_contents("php://input"));
$returnData = [];

//--------------END FUNCTIONS----------------------------


// IF REQUEST METHOD IS NOT POST
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    $returnData = msg(0, 404, 'Page Not Found!');
}
// CHECKING EMPTY FIELDS
elseif (
    !isset($data->password)
    || !isset($data->from)
    || empty(trim($data->password))
    || empty(trim($data->from))
) {

    $fields = ['fields' => ['password', 'from']];
    $returnData = msg(0, 422, 'Please Fill in all Required Fields!', $fields);
}
// IF THERE ARE NO EMPTY FIELDS THEN-
else {

    //Variables
    //I am taking from and password as JSON from API parameters
    //you can change this by taking $from = loggedIn-User_email and $password = loggedIn-User_password
    $password = $data->password;
    $from = $data->from;


    $num = $stmt->rowCount();
    // check if more than 0 record found
    if ($num > 0) {

        // retrieve our table contents
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            //If the balance is less than 5 (USD)
            //This is additional 
            //check can be removed if you want
            if ($row['balance'] < 5) {
                // create & initialize a curl session
                $curl = curl_init();

                // set our url with curl_setopt()
                curl_setopt($curl, CURLOPT_URL, "http://localhost/ProgrammersForceTrainee/email_system2");

                // return the transfer as a string, also with setopt()
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

                // curl_exec() executes the started curl session
                // $output contains the output string
                $output = curl_exec($curl);

                // close curl resource to free up system resources
                // (deletes the variable made by curl_init)
                curl_close($curl);



                $data_array =  array(
                    "password" => $password,
                    "from" => $from,
                    "to" => $row['email'],
                    "subject" => 'Please recharge your account',
                    "body" => 'Your account balance is less than 5$. Please recharge your account',
                );
                $make_call = callAPI('POST', 'http://localhost/ProgrammersForceTrainee/email_system2/lowBalanceMailer.php/', json_encode($data_array));
                $response = json_decode($make_call, true);
                //$errors   = $response['response']['errors'];
                //$data     = $response['response']['data'][0];


                /*
                    Does not return if api has executed successfully or not
                */
                // if(mail was not sent)
                // {
                //     $email_sent = array(
                //         "message" => 'Email could not be sent to, '.  $row['email'] . ' Please check your API parameters'
                //     );
                // }
                // else
                // {
                //     $email_sent = array(
                //         "message" => 'Email has been sent for low balance to ' .  $row['email']
                //     );
                // }


                $email_sent = array(
                    "message" => 'Email sent to, ' .  $row['email']
                );
                //Show API response
                echo json_encode($email_sent);
            }
        }

        // set response code - 200 OK
        http_response_code(200);
    } else {

        // set response code - 404 Not found
        http_response_code(404);

        $email_notsent = array(
            "message" => 'No low balance found'
        );

        echo json_encode($email_notsent);
    }
}

echo json_encode($returnData);
