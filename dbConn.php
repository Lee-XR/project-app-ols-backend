<?php

 $servername = "localhost";
 $username = "id20510585_ols_user";
 $password = "6100COMP_live_db";
 $dbname = "id20510585_project_app_ols";

 $connection = new mysqli($servername, $username, $password, $dbname);
 if(!$connection)
 {
     die("Connection failed: " . $connection->connect_error);
 }