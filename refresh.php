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

$request_body = file_get_contents('php://input');
$data = json_decode($request_body, true);

$refresh = $_COOKIE['refresh'];
$secretKey = 'cdSii:rpckTM[y*G#X]k]3XH78NmSt.G';
$token = JWT::decode($refresh, new Key($secretKey, 'HS512'));
$now = new DateTimeImmutable();
$serverName = 'https://project-app-ols.000webhostapp.com/scripts/';
if ($token->iss != $serverName ||
    $token->nbf > $now->getTimeStamp() ||
    $token->exp < $now->getTimeStamp() ||
    $token->userId != $data['userId']){
        header('HTTP/1.0 401 Unauthorized');
        exit;
}

$userId = $data['userId'];

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
                $serverName = 'https://project-app-ols.000webhostapp.com/scripts/';

                $requestData = [
                    'iat' => $issueTime->getTimeStamp(),
                    'iss' => $serverName,
                    'nbf' => $issueTime->getTimeStamp(),
                    'exp' => $expireTime,
                    'userId' => $userId,
                ];

                $token = JWT::encode($requestData, $secretKey, 'HS512');
                header("Set-Cookie:token=" . $token . "; Path=/; Domain=project-app-ols.000webhostapp.com; Max-Age=" . 60*10 ."; SameSite=None; Secure; HttpOnly;");
                $connection->close();
            }
        }
    }
} else {
    header('HTTP/1.0 401 Unauthorized');
    exit;
}