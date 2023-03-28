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

$option = $_POST['option'];
$id = $_POST['id'];
$category = $_POST['category'];
$error = true;
$return = array();

if(isset($option) && isset($id) && isset($category)){
    $array = array();

    switch($option){
        case "subject":
            $error = false;
            $get = "SELECT * FROM resources WHERE resources.subject_id = ? AND resources.category_id = ?";
            $prep = $connection->prepare($get);
            $prep->bind_param("ii", $id, $category);
            $prep->execute();
            $result = $prep->get_result();
            break;

        case "category":
            $error = false;
            $get = "SELECT * FROM resources WHERE resources.category_id = ?";
            $prep = $connection->prepare($get);
            $prep->bind_param("i", $id);
            $prep->execute();
            $result = $prep->get_result();
            break;

        default:
            $error = true;
    }

    if($result->num_rows > 0){
        while($data = $result->fetch_assoc()){
            $resourceId = $data['resource_id'];
            $title = $data['resource_title'];
            $description = $data['resource_description'];
            $thumbnail = $data['resource_thumbnail'];
            $url = $data['resource_url'];
            $date = $data['resource_date'];
            $author = $data['resource_author'];
            $identifier = $data['resource_identifier'];
            array_push($array, array("resourceId"=>$resourceId, 
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
    $return['error'] = $error;
    $connection->close();
}

echo json_encode($return);