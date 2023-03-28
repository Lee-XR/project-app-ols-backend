<?php
declare(strict_types=1);
header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN'] ."");
header("Accesss-Control-Allow-Methods: GET");
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
    $token->userId != $_GET['userId']){
        header('HTTP/1.0 401 Unauthorized');
        exit;
    }

$return = array();

if(isset($_GET['keywords'])){
    $keywords = "%" . $_GET['keywords'] . "%";
    $array = array();

    $search = "SELECT * FROM resources INNER JOIN subjects ON resources.subject_id = subjects.subject_id 
               INNER JOIN categories ON resources.category_id = categories.category_id 
               WHERE resource_title LIKE ?;";
    $prep = $connection->prepare($search);
    $prep->bind_param("s", $keywords);
    $prep->execute();
    $result = $prep->get_result();

    if($result->num_rows > 0){
        while($data = $result->fetch_assoc()){
            $resourceId = $data['resource_id'];
            $subject = $data['subject_name'];
            $category = $data['category_name'];
            $title = $data['resource_title'];
            $description = $data['resource_description'];
            $thumbnail = $data['resource_thumbnail'];
            $url = $data['resource_url'];
            $date = $data['resource_date'];
            $author = $data['resource_author'];
            $identifier = $data['resource_identifier'];
            array_push($array, array("resourceId"=>$resourceId, 
                                    "subject"=>$subject,
                                    "category"=>$category,
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
}

$connection->close();
echo json_encode($return);