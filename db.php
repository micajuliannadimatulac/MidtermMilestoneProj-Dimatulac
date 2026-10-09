<?php

// Database configuration.
$host = '127.0.0.1';
$port = '3306';
$dbname = 'recipe_byte';
$username = 'root';
$password = '';

try {

    // Create the PDO connection.
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            // Show database errors as exceptions.
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

            // Return query results as associative arrays.
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

} catch (PDOException $e) {

    // Stop the program if the database connection fails.
    die('Database connection failed.');
}