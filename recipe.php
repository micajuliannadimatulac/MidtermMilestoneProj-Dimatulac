
<?php

// Start the PHP session.
session_start();

// Only authenticated members can access this page.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require 'db.php';
require 'classes/Recipe.php';
require 'classes/Comment.php';
require 'classes/Favorite.php';

// Create a Recipe object.
$recipe = new Recipe($pdo);

// Get the recipe ID from the URL.
$recipeId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

// Check if the ID is valid.
if (!$recipeId || $recipeId < 1) {
    header('Location: explore.php');
    exit;
}

// Retrieve the selected recipe.
$recipeDetails = $recipe->getRecipeById($recipeId);

// Check whether the recipe exists.
if (!$recipeDetails) {
    http_response_code(404);
    exit('Recipe not found.');
}


// Retrieve all ingredients for this recipe.
$ingredients = $recipe->getIngredients($recipeId);

// Create the Comment object.
$comment = new Comment($pdo);

// Retrieve comments for this recipe.
$comments = $comment->getComments($recipeId);

// Create a CSRF token for comment forms.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Create the Favorite object.
$favorite = new Favorite($pdo);

// Check if the current member saved this recipe.
$isFavorite = $favorite->isFavorite(
    $_SESSION['user_id'],
    $recipeId
);

// Check whether the current member owns the recipe.
$isOwner = (
    (int) $recipeDetails['user_id'] ===
    (int) $_SESSION['user_id']
);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= htmlspecialchars($recipeDetails['title']) ?>
        | Recipe Byte
    </title>

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

    <!-- RECIPE DETAILS -->
    <main class="section recipe-details">

        <!-- Back to explore -->
        <a href="explore.php" class="recipe-link back-link">
            &larr; Back to Explore Recipes
        </a>

        <!-- RECIPE HEADER -->
        <section class="content-card recipe-header-card">

            <span class="recipe-category">
                <?= htmlspecialchars($recipeDetails['category_name']) ?>
            </span>

            <h1>
                <?= htmlspecialchars($recipeDetails['title']) ?>
            </h1>

            <div class="recipe-meta">

                <span>
                    By
                    <?= htmlspecialchars(
                        $recipeDetails['first_name'] . ' ' .
                        $recipeDetails['last_name']
                    ) ?>
                </span>

                <span>
                    <?= htmlspecialchars(
                        date(
                            'F d, Y',
                            strtotime($recipeDetails['created_at'])
                        )
                    ) ?>
                </span>

                <?php if ($recipeDetails['is_edited']): ?>

                    <span class="edited-label">
                        Edited
                    </span>

                <?php endif; ?>

            </div>

            <p class="recipe-full-description">
                <?= nl2br(
                    htmlspecialchars($recipeDetails['description'])
                ) ?>
            </p>

            
<!-- FAVORITE BUTTON -->
<div class="favorite-section">

    <button
        type="button"
        id="favorite-button"
        class="favorite-button <?= $isFavorite ? 'saved' : '' ?>"
        data-recipe-id="<?= (int) $recipeId ?>"
        data-csrf-token="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
        aria-pressed="<?= $isFavorite ? 'true' : 'false' ?>"
    >

        <span id="favorite-icon">
            <?= $isFavorite ? '♥' : '♡' ?>
        </span>

        <span id="favorite-text">
            <?= $isFavorite
                ? 'Saved to Favorites'
                : 'Save to Favorites' ?>
        </span>

    </button>

    <p
        id="favorite-message"
        class="favorite-message"
        role="status"
        aria-live="polite"
    ></p>

</div>


            <!-- Owner controls will be added in Step 10 -->
            
<!-- RECIPE OWNER ACTIONS -->
<?php if ($isOwner): ?>

    <div class="recipe-owner-actions">

        <!-- Edit recipe -->
        <a
            href="edit_recipe.php?id=<?= (int) $recipeId ?>"
            class="button secondary-button"
        >
            Edit Recipe
        </a>

        <!-- Delete recipe -->
        <form
            action="delete_recipe.php"
            method="POST"
            onsubmit="return confirm('Are you sure you want to delete this recipe? This will also delete its comments and favorites.');"
        >

            <!-- CSRF token -->
            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
            >

            <!-- Recipe ID -->
            <input
                type="hidden"
                name="recipe_id"
                value="<?= (int) $recipeId ?>"
            >

            <button
                type="submit"
                class="button danger-button"
            >
                Delete Recipe
            </button>

        </form>

    </div>

<?php endif; ?>


        </section>

        <!-- INGREDIENTS -->
        <section class="content-card">

            <div class="recipe-section-heading">

                <div>
                    <p class="eyebrow">Prepare Your Ingredients</p>
                    <h2>Ingredients</h2>
                </div>

                <span class="ingredient-count">
                    <?= count($ingredients) ?> items
                </span>

            </div>

            <p class="checklist-hint">
                Tick each ingredient as you prepare it.
                Your checklist updates instantly.
            </p>

            <!-- Interactive cooking checklist -->
            <div class="ingredient-checklist">

                <?php foreach ($ingredients as $ingredient): ?>

                    <label class="ingredient-item">

                        <input
                            type="checkbox"
                            class="ingredient-checkbox"
                        >

                        <span>
                            <strong>
                                <?= htmlspecialchars($ingredient['quantity']) ?>
                            </strong>

                            <?= htmlspecialchars($ingredient['ingredient_name']) ?>
                        </span>

                    </label>

                <?php endforeach; ?>

            </div>

            <!-- Checklist progress -->
            <div class="checklist-progress">

                <p id="checklist-status">
                    0 of <?= count($ingredients) ?> ingredients ready
                </p>

                <button
                    type="button"
                    id="reset-checklist"
                    class="button secondary-button"
                >
                    Reset Checklist
                </button>

            </div>

        </section>

        <!-- COOKING STEPS -->
        <section class="content-card">

            <p class="eyebrow">Let's Get Cooking</p>

            <h2>Cooking Instructions</h2>

            <div class="cooking-steps">
                <?= nl2br(
                    htmlspecialchars($recipeDetails['steps'])
                ) ?>
            </div>

        </section>

        
<!-- COMMENTS SECTION -->
<section class="content-card" id="comments">

    <p class="eyebrow">Community Feedback</p>

    <h2>Comments (<?= count($comments) ?>)</h2>

    <p class="comments-intro">
        Share your thoughts about this recipe
        with the Recipe Byte community.
    </p>

    <!-- Display validation error -->
    <?php if (isset($_GET['error'])): ?>

        <p class="form-error">
            Please enter a valid comment
            containing no more than 1000 characters.
        </p>

    <?php endif; ?>

    <!-- ADD COMMENT FORM -->
    <form
        action="add_comment.php"
        method="POST"
        class="auth-form comment-form"
    >

        <!-- CSRF protection -->
        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
        >

        <!-- Recipe ID -->
        <input
            type="hidden"
            name="recipe_id"
            value="<?= (int) $recipeId ?>"
        >

        <div class="form-group">

            <label for="content">
                Write a Comment
            </label>

            <textarea
                id="content"
                name="content"
                rows="4"
                maxlength="1000"
                placeholder="What do you think about this recipe?"
                required
            ></textarea>

        </div>

        <button
            type="submit"
            class="button primary-button comment-submit"
        >
            Post Comment
        </button>

    </form>

    <!-- COMMENT LIST -->
    <div class="comments-list">

        <?php if (empty($comments)): ?>

            <p class="empty-message">
                No comments yet.
                Be the first to share your thoughts!
            </p>

        <?php else: ?>

            <?php foreach ($comments as $item): ?>

                <article class="comment-card">

                    <div class="comment-header">

                        <div>

                            <!-- Comment author -->
                            <p class="comment-author">
                                <?= htmlspecialchars(
                                    $item['first_name'] . ' ' .
                                    $item['last_name']
                                ) ?>
                            </p>

                            <!-- Comment date -->
                            <p class="comment-date">
                                <?= htmlspecialchars(
                                    date(
                                        'M d, Y h:i A',
                                        strtotime($item['created_at'])
                                    )
                                ) ?>

                                <?php if ($item['is_edited']): ?>
                                    <span class="edited-label">
                                        (Edited)
                                    </span>
                                <?php endif; ?>
                            </p>

                        </div>

                    </div>

                    <!-- Comment content -->
                    <p class="comment-content">
                        <?= nl2br(
                            htmlspecialchars($item['content'])
                        ) ?>
                    </p>

                    <!-- Only the author can edit or delete -->
                    <?php if (
                        (int) $item['user_id'] ===
                        (int) $_SESSION['user_id']
                    ): ?>

                        <div class="comment-actions">

                            <a
                                href="edit_comment.php?id=<?= (int) $item['id'] ?>"
                                class="comment-edit-link"
                            >
                                Edit
                            </a>

                            <form
                                action="delete_comment.php"
                                method="POST"
                                onsubmit="return confirm('Delete this comment?');"
                            >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="comment_id"
                                    value="<?= (int) $item['id'] ?>"
                                >

                                <button
                                    type="submit"
                                    class="comment-delete-button"
                                >
                                    Delete
                                </button>

                            </form>

                        </div>

                    <?php endif; ?>

                </article>

            <?php endforeach; ?>

        <?php endif; ?>

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

    <script src="assets/js/script.js"></script>

</body>
</html>
