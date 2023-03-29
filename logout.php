<?php
declare(strict_types=1);
header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN'] ."");
header("Accesss-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Access-Control-Allow-Headers, Origin, X-Api-Key, X-Requested-With, Content-Type, Accept, Authorization, Cookie");
header("Access-Control-Allow-Credentials: true");
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
require_once('vendor/autoload.php');

if(!$_COOKIE['token']){
    header('HTTP/1.0 400 Bad Request');
    exit;
}

$jwt = $_COOKIE['token'];
$secretKey = 'cdSii:rpckTM[y*G#X]k]3XH78NmSt.G';
$token = JWT::decode($jwt, new Key($secretKey, 'HS512'));
$now = new DateTimeImmutable();
$serverName = 'https://project-app-ols.000webhostapp.com/scripts/';
if ($token->iss != $serverName ||
    $token->nbf > $now->getTimeStamp() ||
    $token->exp < $now->getTimeStamp() ||
    $token->userId != $_POST['userId']){
        header('HTTP/1.0 401 Unauthorized');
        exit;
}

if($_POST['userId']){
    header("Set-Cookie:token=; Path=/; Domain=project-app-ols.000webhostapp.com; Max-Age=0; SameSite=None; Secure; HttpOnly;");
    header("Set-Cookie:refresh=; Path=/; Domain=project-app-ols.000webhostapp.com; Max-Age=0; SameSite=None; Secure; HttpOnly;", false);
    $error = false;
    echo json_encode($error);
}else{
    header('HTTP/1.0 401 Unauthorized');
    exit;
}