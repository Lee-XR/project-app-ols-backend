<?php
declare(strict_types=1);
header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN'] ."");
header("Accesss-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Access-Control-Allow-Headers, Origin, X-Api-Key, X-Requested-With, Content-Type, Accept, Authorization");
header("Access-Control-Allow-Credentials: true");
use Firebase\JWT\JWT;
require_once('vendor/autoload.php');
include 'dbConn.php';

$request_body = file_get_contents('php://input');
$data = json_decode($request_body, true);
$error = false;
$msg = '';
$return = array();
$hasValidCredentials = false;

if(isset($data)){
    $email = $data['email'];
    $password = $data['password'];

    if(empty($email) && empty($password)){
        $error = true;
        $msg =  'Please enter your email and password.';
    } else if (empty($email)){
        $error = true;
        $msg =  'Please enter your email.';
    } else if (empty($password)){
        $error = true;
        $msg =  'Please enter your password.';
    } else {
        $check = "SELECT * FROM users WHERE user_email = ?";
        $prep = $connection->prepare($check);
        $prep->bind_param("s", $email);
        $prep->execute();
        $return = $prep->get_result();

        if($return->num_rows > 0){
            while($data = $return->fetch_assoc()){
                if(password_verify($password, $data['user_password'])){
                    $userId = $data['user_id'];
                    $userInfo = array('userId'=>$userId,
                                    'userName'=>$data['user_name'],
                                    'userEmail'=>$data['user_email'],
                                    'userDob'=>$data['user_dob'],
                                    'userGender'=>$data['user_gender'],
                                    'userGrade'=>$data['user_grade'],
                                    'userProfilePic'=>$data['user_profilePic']);
                    $hasValidCredentials = true;
                } else {
                    $error = true;
                    $msg =  "Your password is incorrect.";
                }
            }
        } else {
            $error = true;
            $msg =  "Your email does not exist.";
        }
    }
}

if($hasValidCredentials){
    $secretKey = 'cdSii:rpckTM[y*G#X]k]3XH78NmSt.G';
    $issueTime = new DateTimeImmutable();
    $expireTime = $issueTime->modify('+10minutes')->getTimeStamp();
    $serverName = 'https://project-app-ols.000webhostapp.com/scripts/';

    $requestData = [
        'iat' => $issueTime->getTimeStamp(),
        'iss' => $serverName,
        'nbf' => $issueTime->getTimeStamp(),
        'exp' => $expireTime,
        'userId' => $userId,
    ];

    $refreshData = [
        'iat' => $issueTime->getTimeStamp(),
        'iss' => $serverName,
        'nbf' => $issueTime->getTimeStamp(),
        'exp' => $issueTime->modify('+10days')->getTimeStamp(),
        'userId' => $userId,
    ];

    $token = JWT::encode($requestData, $secretKey, 'HS512');
    $refresh = JWT::encode($refreshData, $secretKey, 'HS512');

    header("Set-Cookie:token=" . $token . "; Path=/; Domain=project-app-ols.000webhostapp.com; Max-Age=" . 60*10 ."; SameSite=None; Secure; HttpOnly;");
    header("Set-Cookie:refresh=" . $refresh . "; Path=/; Domain=project-app-ols.000webhostapp.com; Max-Age=" . 60*60*24*10 ."; SameSite=None; Secure; HttpOnly;", false);

    $insert = "UPDATE users SET user_refreshToken = ? WHERE user_id = ?;";
    $prep = $connection->prepare($insert);
    $prep->bind_param("si", $refresh, $userId);
    if($prep->execute()){
        $return = array('error'=>$error, 'userInfo'=>$userInfo);
        echo json_encode($return);
        return;
    }

}

$connection->close();
$return = array('error'=>$error, 'msg'=>$msg);
echo json_encode($return);
