<?php

 $servername = "localhost:3307";
 $username = "root";
 $password = "";
 $dbname = "project-app-ols";

 $connection = new mysqli($servername, $username, $password, $dbname);
 if(!$connection)
 {
     die("Connection failed: " . $connection->connect_error);
 }