
<?php

// Start the PHP session.
session_start();

// Import the database connection and User class.
require 'db.php';
require 'classes/User.php';

// If the member is already logged in, redirect to the homepage.
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Create an object from the User class.
$user = new User($pdo);

// Variables for error messages and form values.
$error = '';
$firstName = '';
$lastName = '';
$email = '';

// Check if the registration form was submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get the submitted form values.
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // SERVER-SIDE VALIDATION

    if (
        $firstName === '' ||
        $lastName === '' ||
        $email === '' ||
        $password === '' ||
        $confirmPassword === ''
    ) {
        $error = 'Please fill in all fields.';

    } elseif (
        strlen($firstName) > 100 ||
        strlen($lastName) > 100
    ) {
        $error = 'Names must not exceed 100 characters.';

    } elseif (strlen($email) > 255) {
        $error = 'Email is too long.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';

    } elseif (strlen($password) < 6) {
        $error = 'Password must contain at least 6 characters.';

    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';

    } elseif ($user->emailExists($email)) {
        $error = 'This email is already registered.';

    } else {

        // Register the member through the User class.
        $registered = $user->register(
            $firstName,
            $lastName,
            $email,
            $password
        );

        if ($registered) {

            // Redirect to login after successful registration.
            header('Location: login.php?registered=1');
            exit;

        } else {
            $error = 'Registration failed. Please try again.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Account | Recipe Byte</title>

    <link rel="stylesheet" href="assets/css/styles.css">
</head>

<body>

    <header class="site-header">
        <nav class="navbar">

            <a href="login.php" class="logo">
                Recipe<span>Byte.</span>
            </a>

            <ul class="nav-links">
                <li>
                    <a href="login.php">Login</a>
                </li>
                <li>
                    <a href="register.php" class="active">Register</a>
                </li>
            </ul>

        </nav>
    </header>

    <main class="auth-page">

        <section class="auth-card">

            <p class="eyebrow">Barangay Home Cooks Community</p>

            <h1>Join RecipeByte.</h1>

            <p class="auth-description">
                Share your favorite lutong bahay recipes,
                discover dishes from other home cooks in your barangay,
                and save recipes you want to try.
            </p>

            <?php if ($error !== ''): ?>

                <p class="form-error">
                    <?= htmlspecialchars($error) ?>
                </p>

            <?php endif; ?>

            <form
                action="register.php"
                method="POST"
                class="auth-form"
                id="register-form"
            >

                <div class="form-row">

                    <div class="form-group">
                        <label for="first_name">First Name</label>

                        <input
                            type="text"
                            id="first_name"
                            name="first_name"
                            maxlength="100"
                            value="<?= htmlspecialchars($firstName) ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="last_name">Last Name</label>

                        <input
                            type="text"
                            id="last_name"
                            name="last_name"
                            maxlength="100"
                            value="<?= htmlspecialchars($lastName) ?>"
                            required
                        >
                    </div>

                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        maxlength="255"
                        placeholder="example@email.com"
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
                        minlength="6"
                        placeholder="At least 6 characters"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="confirm_password">
                        Confirm Password
                    </label>

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        minlength="6"
                        placeholder="Re-enter your password"
                        required
                    >
                </div>

                <button
                    type="submit"
                    class="button primary-button auth-submit"
                >
                    Create Account
                </button>

            </form>

            <p class="auth-switch">
                Already have an account?
                <a href="login.php">Log in here</a>
            </p>

        </section>

    </main>

    
<script>
    // Get the registration form.
    const registerForm = document.getElementById("register-form");

    // Get the Create Account button.
    const registerButton = registerForm.querySelector(
        'button[type="submit"]'
    );

    // Prevent multiple submissions.
    registerForm.addEventListener("submit", function () {

        // Disable the button after a valid submission.
        registerButton.disabled = true;

        // Inform the user that registration is processing.
        registerButton.textContent = "Creating Account...";
    });
</script>


</body>
</html>
