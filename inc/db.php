<?php

/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */
global $pdo;

$pdo = new PDO("mysql:dbname=$config->db_name;host=$config->db_host", 
        $config->db_user, 
        $config->db_pass, 
        array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4'")
);

