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
    $username = $data['username'];
    $password = $data['password'];
    $dob = $data['dob'];
    $gender = $data['gender'];
    $grade = $data['grade'];

    if(empty($email) || empty($username) || empty($password) || empty($dob) || empty($gender) || empty($grade) || empty($grade)){
        $error = true;
        $msg = 'Please fill in all your details.';
    } else {
        $register = 'INSERT INTO users (user_name, user_password, user_email, user_dob, user_gender, user_grade) 
                    VALUES (?, ?, ?, ?, ?, ?)';
        $prep = $connection->prepare($register);
        $password = password_hash($password, PASSWORD_DEFAULT);
        $prep->bind_param("sssssi", $username, $password, $email, $dob, $gender, $grade);
        $prep->execute();

        $check = "SELECT user_id FROM users WHERE user_email = ?";
        $prep = $connection->prepare($check);
        $prep->bind_param("s", $email);
        $prep->execute();
        $return = $prep->get_result();

        if($return->num_rows > 0){
            while($data = $return->fetch_assoc()){
                $userId = $data['user_id'];
                $userInfo = array('userId'=>$userId,
                                    'userName'=>$username,
                                    'userEmail'=>$email,
                                    'userDob'=>$dob,
                                    'userGender'=>$gender,
                                    'userGrade'=>$grade);
                $hasValidCredentials = true;
            }
        } else {
            $error = true;
            $msg = 'Oops! Cannot connect to database';
        }
    }
}

if($hasValidCredentials){
    $secretKey = 'cdSii:rpckTM[y*G#X]k]3XH78NmSt.G';
    $issueTime = new DateTimeImmutable();
    $expireTime = $issueTime->modify('+10minutes')->getTimeStamp();
    $serverName = 'http://localhost:80/scripts';

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

    setcookie('token', $token, time()+60*10, "/", "localhost", true, true);
    setcookie('refresh', $refresh, time()+60*60*24*10, "/", "localhost", true, true);

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
