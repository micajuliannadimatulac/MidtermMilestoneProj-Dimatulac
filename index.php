
<?php

// Start the session.
session_start();

// Protect the homepage from unauthenticated visitors.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get the logged-in member's first name.
$firstName = $_SESSION['first_name'] ?? 'Member';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Home | Recipe Byte</title>

    <link rel="stylesheet" href="assets/css/styles.css">
</head>

<body>

    <!-- NAVIGATION -->
    <header class="site-header">
        <nav class="navbar">

            <a href="index.php" class="logo">
                Recipe<span>Byte.</span>
            </a>

            <ul class="nav-links">

                <li>
                    <a href="index.php" class="active">
                        Home
                    </a>
                </li>

                <li>
                    <a href="explore.php">
                        Explore Recipes
                    </a>
                </li>

                <li>
                    <a href="create_recipe.php">
                        Share a Recipe
                    </a>
                </li>

                <li>
                    <a href="favorites.php">
                        My Favorites
                    </a>
                </li>

                <li>
                    <a href="logout.php">
                        Logout
                    </a>
                </li>

            </ul>

        </nav>
    </header>

    <!-- LANDING PAGE -->
    <main>

        <section class="hero">

            <div class="hero-content">

                <p class="eyebrow">
                    Lutong Bahay Community
                </p>

                <h1>
                    Every recipe<br>
                    has a <span>story.</span>
                </h1>

                <p class="hero-text">
                    Welcome back,
                    <strong>
                        <?= htmlspecialchars($firstName) ?>!
                    </strong>

                    Discover homemade recipes from our community,
                    explore new flavors, and share your favorite
                    dishes with fellow home cooks.
                </p>

                <div class="hero-actions">

                    <a
                        href="explore.php"
                        class="button primary-button"
                    >
                        Explore Recipes
                    </a>

                    <a
                        href="create_recipe.php"
                        class="button secondary-button"
                    >
                        Share a Recipe
                    </a>

                </div>

            </div>

            <div class="hero-card">

                <p class="hero-card-label">
                    Welcome to Recipe Byte
                </p>

                <strong>
                    Made with love, shared with everyone.
                </strong>

                <p>
                    From classic ulam to sweet panghimagas,
                    discover recipes from home cooks
                    in your community.
                </p>

            </div>

        </section>

    </main>

    <!-- FOOTER -->
    <footer class="site-footer">

        <p>
            &copy; <?= date('Y') ?> Recipe Byte.
            Made for home cooks.
        </p>

        <p>
            Share recipes. Discover flavors.
        </p>

    </footer>

</body>
</html>
