<?php
include 'dbConn.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Access-Control-Allow-Headers, Origin, X-Api-Key, X-Requested-With, Content-Type, Accept, Authorization");

$request_body = file_get_contents('php://input');
$userId = json_decode($request_body, true);
$error = false;

if(isset($userId)){
    if(empty($userId)){
        $error = true;
    } else {
        $profile = "SELECT * FROM users WHERE user_id = ?;";
        $prep = $connection->prepare($profile);
        $prep->bind_param("i", $userId);
        $prep->execute();
        $data = $prep->get_result();

        if($data->num_rows > 0){
            while($result = $data->fetch_assoc()){
                $user_name = $result['user_name'];
                $user_email = $result['user_email'];
                $user_dob = $result['user_dob'];
                $user_gender = $result['user_gender'];
                $user_grade = $result['user_grade'];
                $user_profilePic = $result['user_profilePic'];
            }
        }
    }
}

$return = array("error"=>$error, "user_name"=>$user_name, "user_email"=>$user_email, "user_dob"=>$user_dob,
                "user_gender"=>$user_gender, "user_grade"=>$user_grade, 
                "user_profilePic"=>$user_profilePic);

$connection->close();
echo json_encode($return);
