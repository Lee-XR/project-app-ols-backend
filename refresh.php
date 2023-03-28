<?php
declare(strict_types=1);
header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN'] ."");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Access-Control-Allow-Headers, Origin, X-Api-Key, X-Requested-With, Content-Type, Accept, Authorization, Cookie");
header("Access-Control-Allow-Credentials: true");
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
require_once('vendor/autoload.php');
include 'dbConn.php';

if(!$_COOKIE['refresh']){
    header('HTTP/1.0 401 Unauthorized');
    exit;
}

$refresh = $_COOKIE['refresh'];
$secretKey = 'cdSii:rpckTM[y*G#X]k]3XH78NmSt.G';
$token = JWT::decode($refresh, new Key($secretKey, 'HS512'));
$now = new DateTimeImmutable();
$serverName = 'http://localhost:80/scripts';
if ($token->iss != $serverName ||
    $token->nbf > $now->getTimeStamp() ||
    $token->exp < $now->getTimeStamp() ||
    $token->userId != $_POST['userId']){
        header('HTTP/1.0 401 Unauthorized');
        exit;
}

$userId = $_POST['userId'];

if(isset($userId)){
    $get = 'SELECT user_refreshToken FROM users WHERE user_id = ?;';
    $prep = $connection->prepare($get);
    $prep->bind_param('i', $userId);
    $prep->execute();
    $result = $prep->get_result();

    if($result->num_rows > 0){
        while($data = $result->fetch_assoc()){
            if($refresh === $data['user_refreshToken']){
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

                $token = JWT::encode($requestData, $secretKey, 'HS512');
                setcookie('token', $token, time()+600, "/", "localhost", true, true);
                $connection->close();
            }
        }
    }
} else {
    header('HTTP/1.0 401 Unauthorized');
    exit;
}