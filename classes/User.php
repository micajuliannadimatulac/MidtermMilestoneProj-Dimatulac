<?php

class User {

    // Store the PDO database connection.
    private $pdo;

    // Constructor runs when a User object is created.
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Check if an email is already registered.
    public function emailExists($email) {

        $stmt = $this->pdo->prepare(
            'SELECT id
             FROM users
             WHERE email = :email'
        );

        $stmt->execute([
            ':email' => $email
        ]);

        return (bool) $stmt->fetch();
    }

    // Register a new member.
    public function register($firstName, $lastName, $email, $password) {

        // Hash the password before saving it.
        $hashedPassword = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        try {

            // Start the transaction.
            $this->pdo->beginTransaction();

            // Prepare the INSERT statement.
            $stmt = $this->pdo->prepare(
                'INSERT INTO users (
                    first_name,
                    last_name,
                    email,
                    password
                )
                VALUES (
                    :first_name,
                    :last_name,
                    :email,
                    :password
                )'
            );

            // Execute the prepared statement.
            $stmt->execute([
                ':first_name' => $firstName,
                ':last_name' => $lastName,
                ':email' => $email,
                ':password' => $hashedPassword
            ]);

            // Save the new user.
            $this->pdo->commit();

            return true;

        } catch (PDOException $e) {

            // Undo changes if the database operation fails.
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return false;
        }
    }

    // Authenticate an existing member.
    public function login($email, $password) {

        // Find the account using its email.
        $stmt = $this->pdo->prepare(
            'SELECT id, first_name, last_name, email, password
             FROM users
             WHERE email = :email'
        );

        $stmt->execute([
            ':email' => $email
        ]);

        $user = $stmt->fetch();

        // Verify the entered password against its stored hash.
        if ($user && password_verify($password, $user['password'])) {

            // Remove the password hash from the returned data.
            unset($user['password']);

            return $user;
        }

        // Return false if the email or password is incorrect.
        return false;
    }
}