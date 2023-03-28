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
$serverName = 'http://localhost:80/scripts';
if ($token->iss != $serverName ||
    $token->nbf > $now->getTimeStamp() ||
    $token->exp < $now->getTimeStamp() ||
    $token->userId != $_GET['userId']){
        header('HTTP/1.0 401 Unauthorized');
        exit;
    }

$error = true;
$msg = 'User ID is missing.';

if(isset($_GET['id'])){
    $resourceId = $_GET['id'];

    $download = "SELECT resource_download FROM resources WHERE resource_id = ?;";
    $prep = $connection->prepare($download);
    $prep->bind_param("i", $resourceId);
    $prep->execute();
    $result = $prep->get_result();

    if($result->num_rows > 0){
        while($data = $result->fetch_assoc()){
            $filename = $data['resource_download'];
            $filepath = 'downloads/' . $filename;
            if(file_exists($filepath)){
                $ext = pathinfo($filename, PATHINFO_EXTENSION);
                if($ext === 'pdf'){
                    header('Content-Type: application/pdf');
                } else if ($ext === 'mp4') {
                    header('Content-Type: application/mp4');
                } else {
                    header('Content-Type: application/octet-stream');
                }
                header('Content-Description: File Transfer');
                header('Content-Disposition: attachment; filename="'. basename($filepath) . '"');
                header('Expires: 0');
                header('Cache-Control: no-cache, no-store, must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($filepath));
                flush();
                readfile($filepath);
                exit;
            } else {
                $error = true;
                $msg = 'File does not exist';
            }
        }
    } else {
        $error = true;
        $msg = 'Resource does not exist';
    }
   
}

$return = array('error'=>$error, 'msg'=>$msg);
$connection->close();
echo json_encode($return);