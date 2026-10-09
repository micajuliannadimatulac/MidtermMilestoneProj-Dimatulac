
<?php

// Start the PHP session.
session_start();

// Import the database connection and User class.
require 'db.php';
require 'classes/User.php';

// If already logged in, redirect to the homepage.
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Create an object from the User class.
$user = new User($pdo);

// Variables for error messages and form values.
$error = '';
$email = '';

// Check if the login form was submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get the submitted email and password.
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // SERVER-SIDE VALIDATION

    if ($email === '' || $password === '') {
        $error = 'Please enter your email and password.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';

    } else {

        // Authenticate using the User class.
        $loggedInUser = $user->login($email, $password);

        if ($loggedInUser) {

            // Generate a new session ID after login.
            session_regenerate_id(true);

            // Store the authenticated member's details.
            $_SESSION['user_id'] = $loggedInUser['id'];
            $_SESSION['first_name'] = $loggedInUser['first_name'];
            $_SESSION['last_name'] = $loggedInUser['last_name'];
            $_SESSION['email'] = $loggedInUser['email'];

            // Redirect to the recipe homepage.
            header('Location: index.php');
            exit;

        } else {
            $error = 'Incorrect email or password.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | Recipe Byte</title>

    <link rel="stylesheet" href="assets/css/styles.css">
</head>

<body>

    <!-- NAVIGATION -->
    <header class="site-header">
        <nav class="navbar">

            <a href="login.php" class="logo">
                Recipe<span>Byte.</span>
            </a>

            <ul class="nav-links">
                <li>
                    <a href="login.php" class="active">Login</a>
                </li>
                <li>
                    <a href="register.php">Register</a>
                </li>
            </ul>

        </nav>
    </header>

    <!-- LOGIN CONTENT -->
    <main class="auth-page">

        <section class="auth-card">

            <p class="eyebrow">Lutong Bahay Community</p>

            <h1>Welcome back.</h1>

            <p class="auth-description">
                Log in to Recipe Byte to discover homemade recipes,
                save your favorites, and share your own dishes.
            </p>

            <!-- Successful registration message -->
            <?php if (isset($_GET['registered'])): ?>

                <p class="form-success">
                    Account created successfully!
                    You can now log in.
                </p>

            <?php endif; ?>

            <!-- Login error message -->
            <?php if ($error !== ''): ?>

                <p class="form-error">
                    <?= htmlspecialchars($error) ?>
                </p>

            <?php endif; ?>

            <!-- LOGIN FORM -->
            <form
                action="login.php"
                method="POST"
                class="auth-form"
            >

                <div class="form-group">

                    <label for="email">Email Address</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="example@email.com"
                        maxlength="255"
                        value="<?= htmlspecialchars($email) ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="password">Password</label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        required
                    >

                </div>

                <button
                    type="submit"
                    class="button primary-button auth-submit"
                >
                    Log In
                </button>

            </form>

            <p class="auth-switch">
                Don't have an account?
                <a href="register.php">Create one here</a>
            </p>

        </section>

    </main>

</body>
</html>
