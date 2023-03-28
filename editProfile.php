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

$data = json_decode($_POST['data'], true);

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
    $token->userId != $data['userId']){
        header('HTTP/1.0 401 Unauthorized');
        exit;
    }

$error = false;
$msg = '';
$userId = $data['userId'];
$username = $data['username'];
$email = $data['email'];
$dob = $data['dob'];
$gender = $data['gender'];
$grade = $data['grade'];

if(empty($data['oldPw']) && empty($data['newPw'])){
    $error = true;
    $msg = 'Please fill in your password.';
} else {
    $oldPw = $data['oldPw'];
    $newPw = $data['newPw'];

    if($_POST['changePic'] === true){
        $dir = './profilePics/';
        $file = $dir . basename($_FILES['profilePic']['name']);
        $uploadOk = true;
        $fileType = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    
        // Check if image file is real
        $size = getimagesize($_FILES['profilePic']['tmp_name']);
        if(!$size){
            $uploadOk = false;
            $error = true;
            $msg = 'Your file is not an image.';
        }
    
        // Check file format
        if($fileType != 'jpg' && $fileType != 'png' && $fileType != 'jpeg'){
            $uploadOk = false;
            $error = true;
            $msg = 'JPG and PNG image files only.';
        }
    
        if(isset($data) || $uploadOk === true){
            $check = "SELECT * FROM users WHERE user_id = ?";
            $prep = $connection->prepare($check);
            $prep->bind_param("i", $userId);
            $prep->execute();
            $return = $prep->get_result();
    
            if($return->num_rows > 0){
                while($data = $return->fetch_assoc()){
                    if(password_verify($oldPw, $data['user_password'])){
                        $newPw = password_hash($newPw, PASSWORD_DEFAULT);
                        if(move_uploaded_file($_FILES['profilePic']['tmp_name'], $file)){
                            $edit = 'UPDATE users SET user_name = ?, user_password = ?, user_email = ?, 
                                    user_dob = ?, user_gender = ?, user_grade = ?, user_profilePic = ? 
                                    WHERE user_id = ?;';
                            $prep = $connection->prepare($edit);
                            $prep->bind_param('sssssisi', $username, $newPw, $email, $dob, 
                                                $gender, $grade, 
                                                $_FILES['profilePic']['name'], $userId);
                            if($prep->execute()){
                                $msg = 'Your profile has been updated.';
                            } else {
                                $error = true;
                                $msg = 'The server has encountered an issue. Please try again.';
                            }
                        } else {
                            $error = true;
                            $msg = 'The server has encountered an issue. Please try again.';
                        }
                    } else {
                        $error = true;
                        $msg = 'Your current password does not match.';
                    }
                }
            } 
        }
    } else {
        if(isset($data)){
            $check = "SELECT * FROM users WHERE user_id = ?";
            $prep = $connection->prepare($check);
            $prep->bind_param("i", $userId);
            $prep->execute();
            $return = $prep->get_result();
    
            if($return->num_rows > 0){
                while($data = $return->fetch_assoc()){
                    if(password_verify($oldPw, $data['user_password'])){
                        $newPw = password_hash($newPw, PASSWORD_DEFAULT);
                        $edit = 'UPDATE users SET user_name = ?, user_password = ?, user_email = ?, 
                                user_dob = ?, user_gender = ?, user_grade = ? WHERE user_id = ?;';
                        $prep = $connection->prepare($edit);
                        $prep->bind_param('sssssii', $username, $newPw, $email, $dob, 
                                            $gender, $grade, $userId);
                        if($prep->execute()){
                            $msg = 'Your profile has been updated.';
                        } else {
                            $error = true;
                            $msg = 'The server has encountered an issue. Please try again.';
                        }
                    } else {
                        $error = true;
                        $msg = 'Your current password does not match.';
                    }
                }
            } 
        }
    }
}

$return = array('error'=>$error, 'msg'=>$msg);
$connection->close();
echo json_encode($return);