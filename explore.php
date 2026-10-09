
<?php

// Start the session.
session_start();

// Protect this page from guests.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Import database and Recipe class.
require 'db.php';
require 'classes/Recipe.php';
require 'classes/Favorite.php';

// Create Recipe object.
$recipe = new Recipe($pdo);

// Get the search keyword.
$search = trim($_GET['search'] ?? '');

// Get the category filter.
$categoryId = filter_input(
    INPUT_GET,
    'category',
    FILTER_VALIDATE_INT
);

if ($categoryId === false || $categoryId === null || $categoryId < 0) {
    $categoryId = 0;
}

// Limit the keyword length.
if (strlen($search) > 150) {
    $search = substr($search, 0, 150);
}

// Retrieve categories and recipes.
$categories = $recipe->getCategories();
$recipes = $recipe->getRecipes($search, $categoryId);

// Create the Favorite object.
$favorite = new Favorite($pdo);

// Create a CSRF token for favorite requests.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Explore Recipes | Recipe Byte</title>

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
                    <a href="explore.php" class="active">
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
                    <a href="logout.php">Logout</a>
                </li>

            </ul>

        </nav>
    </header>

    <!-- EXPLORE RECIPES -->
    <main class="section">

    <?php if (isset($_GET['deleted']) && $_GET['deleted'] === '1'): ?>

    <p class="form-success">
        Your recipe has been deleted successfully.
    </p>

<?php endif; ?>

        <div class="section-heading">

            <p class="eyebrow">Community Recipes</p>

            <h1>What's cooking?</h1>

            <p>
                Explore recipes shared by our community.
                Search for your favorite dish or browse
                by category.
            </p>

        </div>

        <!-- SEARCH AND CATEGORY FILTER -->
        <form
            action="explore.php"
            method="GET"
            class="recipe-controls"
        >

            <div class="search-field">

                <label for="search">
                    Search Recipes
                </label>

                <input
                    type="search"
                    id="search"
                    name="search"
                    placeholder="Search by title or description..."
                    maxlength="150"
                    value="<?= htmlspecialchars($search) ?>"
                >

            </div>

            <div class="sort-field">

                <label for="category">
                    Category
                </label>

                <select id="category" name="category">

                    <option value="0">
                        All Categories
                    </option>

                    <?php foreach ($categories as $category): ?>

                        <option
                            value="<?= (int) $category['id'] ?>"
                            <?= $categoryId == $category['id'] ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($category['name']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <button
                type="submit"
                class="button primary-button"
            >
                Search
            </button>

            <a
                href="explore.php"
                class="button secondary-button"
            >
                Clear
            </a>

        </form>

        <!-- RECIPE RESULTS -->
        <div class="recipes-grid">

            <?php if (empty($recipes)): ?>

                <div class="empty-message">

                    <h3>No recipes found.</h3>

                    <p>
                        No recipes match your search yet.
                        Try another keyword or category.
                    </p>

                </div>

            <?php else: ?>

                <?php foreach ($recipes as $item): ?>

                    <article class="recipe-card">

                        <!-- Recipe category -->
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

                        <!-- Author and date -->
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
                                    Edited
                                </span>

                            <?php endif; ?>

                        </div>

                        <!-- View full recipe -->
                        
                <!-- RECIPE CARD ACTIONS -->
                <div class="recipe-card-actions">

                    <!-- View the complete recipe -->
                    <a
                        href="recipe.php?id=<?= (int) $item['id'] ?>"
                        class="recipe-link"
                    >
                        View Recipe &rarr;
                    </a>

                    <?php
                        // Check if this recipe is already saved.
                        $isSaved = $favorite->isFavorite(
                            $_SESSION['user_id'],
                            $item['id']
                        );
                    ?>

                    <!-- Small favorite button -->
                    <button
                        type="button"
                        class="card-favorite-button <?= $isSaved ? 'saved' : '' ?>"
                        data-recipe-id="<?= (int) $item['id'] ?>"
                        data-csrf-token="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
                    >
                        <span class="card-favorite-icon">
                            <?= $isSaved ? '♥' : '♡' ?>
                        </span>

                        <span class="card-favorite-text">
                            <?= $isSaved ? 'Saved' : 'Save' ?>
                        </span>
                    </button>

                </div>

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

    <!-- JavaScript for favorite buttons -->
    <script src="assets/js/script.js"></script>

</body>
</html>
