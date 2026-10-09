
<?php

// Start the session.
session_start();

// Only authenticated members can view favorites.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Import database and Favorite class.
require 'db.php';
require 'classes/Favorite.php';

// Create the Favorite object.
$favorite = new Favorite($pdo);

// Retrieve recipes saved by this member.
$favoriteRecipes = $favorite->getFavorites(
    $_SESSION['user_id']
);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Favorites | Recipe Byte</title>

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
                    <a href="index.php">Home</a>
                </li>

                <li>
                    <a href="explore.php">Explore Recipes</a>
                </li>

                <li>
                    <a href="create_recipe.php">Share a Recipe</a>
                </li>

                <li>
                    <a href="favorites.php" class="active">
                        My Favorites
                    </a>
                </li>

                <li>
                    <a href="logout.php">Logout</a>
                </li>

            </ul>

        </nav>
    </header>

    <!-- FAVORITES CONTENT -->
    <main class="section">

        <div class="section-heading">

            <p class="eyebrow">Your Recipe Collection</p>

            <h1>My Favorites.</h1>

            <p>
                All the recipes you've saved,
                gathered in one place.
            </p>

        </div>

        <!-- FAVORITE RECIPES -->
        <div class="recipes-grid">

            <?php if (empty($favoriteRecipes)): ?>

                <div class="empty-message">

                    <h3>No favorites yet.</h3>

                    <p>
                        You haven't saved any recipes.
                        Explore the community and find
                        something delicious!
                    </p>

                    <a
                        href="explore.php"
                        class="button primary-button"
                    >
                        Explore Recipes
                    </a>

                </div>

            <?php else: ?>

                <?php foreach ($favoriteRecipes as $item): ?>

                    <article class="recipe-card">

                        <!-- Category -->
                        <span class="recipe-category">
                            <?= htmlspecialchars($item['category_name']) ?>
                        </span>

                        <!-- Recipe title -->
                        <h3>
                            <?= htmlspecialchars($item['title']) ?>
                        </h3>

                        <!-- Description -->
                        <p class="recipe-description">
                            <?= htmlspecialchars($item['description']) ?>
                        </p>

                        <!-- Recipe information -->
                        <div class="recipe-meta">

                            <span>
                                By
                                <?= htmlspecialchars(
                                    $item['first_name'] . ' ' .
                                    $item['last_name']
                                ) ?>
                            </span>

                            <span>
                                <?= htmlspecialchars(
                                    date(
                                        'M d, Y',
                                        strtotime($item['created_at'])
                                    )
                                ) ?>
                            </span>

                            <?php if ($item['is_edited']): ?>

                                <span class="edited-label">
                                    (Edited)
                                </span>

                            <?php endif; ?>

                        </div>

                        <!-- View recipe -->
                        <a
                            href="recipe.php?id=<?= (int) $item['id'] ?>"
                            class="recipe-link"
                        >
                            View Recipe &rarr;
                        </a>

                    </article>

                <?php endforeach; ?>

            <?php endif; ?>

        </div>

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
