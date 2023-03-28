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
$serverName = 'http://localhost:80/scripts';
if ($token->iss != $serverName ||
    $token->nbf > $now->getTimeStamp() ||
    $token->exp < $now->getTimeStamp() ||
    $token->userId != $_POST['userId']){
        header('HTTP/1.0 401 Unauthorized');
        exit;
    }

$userId = $_POST['userId'];
$category = $_POST['category'];
$return = array();
$error = false;

if(isset($userId) && isset($category)){
    $array = array();

    $get = "SELECT * FROM resources INNER JOIN bookmarks ON resources.resource_id = bookmarks.resource_id 
            INNER JOIN users ON bookmarks.user_id = users.user_id WHERE bookmarks.user_id = ? AND 
            resources.category_id = ?;";
    $prep = $connection->prepare($get);
    $prep->bind_param("ii", $userId, $category);
    $prep->execute();
    $result = $prep->get_result();

    if($result->num_rows > 0){
        while($data = $result->fetch_assoc()){
            $resourceId = $data['resource_id'];
            $bookmarkId = $data['bookmark_id'];
            $title = $data['resource_title'];
            $description = $data['resource_description'];
            $thumbnail = $data['resource_thumbnail'];
            $url = $data['resource_url'];
            $date = $data['resource_date'];
            $author = $data['resource_author'];
            $identifier = $data['resource_identifier'];
            array_push($array, array("resourceId"=>$resourceId, 
                                    "bookmarkId"=>$bookmarkId,
                                    "title"=>$title, 
                                    "description"=>$description, 
                                    "thumbnail"=>$thumbnail, 
                                    "url"=>$url, 
                                    "date"=>$date, 
                                    "author"=>$author, 
                                    "identifier"=>$identifier));
        }
    }
    $return['resources'] = $array;
    $return['hits'] = $result->num_rows;
    $connection->close();
}

echo json_encode($return);