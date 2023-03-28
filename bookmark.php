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

$action = $_POST['action'];
$userId = $_POST['userId'];
$return = array();
$error = false;
$errorMsg = '';

if(isset($action)){
    
    if($action === 'add'){
        $resourceId = $_POST['resourceId'];

        $check = "SELECT * FROM bookmarks WHERE user_id = ? AND resource_id = ?;";
        $prep = $connection->prepare($check);
        $prep->bind_param("ii", $userId, $resourceId);
        $prep->execute();
        $result = $prep->get_result();

        if($result->num_rows <= 0){
            $add = "INSERT INTO bookmarks (user_id, resource_id) VALUES (?, ?);";
            $prep = $connection->prepare($add);
            $prep->bind_param("ii", $userId, $resourceId);
            if(!($prep->execute())){
                $error = true;
                $errorMsg = 'Bookmark cannot be added right now.';
                $return['error'] = $error;
                $return['errorMsg'] = $errorMsg;
            }
        } else {
            $error = true;
            $errorMsg = 'Bookmark already added.';
            $return['error'] = $error;
            $return['errorMsg'] = $errorMsg;
        }
    }

    if($action === 'remove'){
        $bookmarkId = $_POST['bookmarkId'];

        $delete = "DELETE FROM bookmarks WHERE bookmark_id = ? AND user_id = ?;";
        $prep = $connection->prepare($delete);
        $prep->bind_param("ii", $bookmarkId, $userId);
        if(!($prep->execute())){
            $error = true;
            $errorMsg = 'Bookmark cannot be removed right now.';
            $return['error'] = $error;
            $return['errorMsg'] = $errorMsg;
        }
    }
    
}

$return['error'] = $error;
$connection->close();
echo json_encode($return);