
<?php

// Start the session.
session_start();

// Only logged-in members can create recipes.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Import database and Recipe class.
require 'db.php';
require 'classes/Recipe.php';

// Create a Recipe object.
$recipe = new Recipe($pdo);

// Get available categories.
$categories = $recipe->getCategories();

// Create a CSRF token for this form.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Variables for validation messages and old form values.
$error = '';
$title = '';
$description = '';
$steps = '';
$categoryId = 0;

$ingredients = [''];
$quantities = [''];

// Check whether the form was submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Verify the CSRF token.
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($submittedToken) ||
        !hash_equals($_SESSION['csrf_token'], $submittedToken)
    ) {
        http_response_code(403);
        exit('Invalid form submission.');
    }

    // Get the submitted recipe details.
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $steps = trim($_POST['steps'] ?? '');

    // Validate the category as an integer.
    $categoryId = filter_var(
        $_POST['category_id'] ?? 0,
        FILTER_VALIDATE_INT
    );

    // Get ingredient names and quantities.
    $ingredients = $_POST['ingredients'] ?? [];
    $quantities = $_POST['quantities'] ?? [];

    // Make sure both ingredient inputs are arrays.
    if (!is_array($ingredients) || !is_array($quantities)) {
        $ingredients = [];
        $quantities = [];
    }

    // Check that the selected category exists.
    $validCategory = false;

    foreach ($categories as $category) {
        if ((int) $category['id'] === $categoryId) {
            $validCategory = true;
            break;
        }
    }

    // SERVER-SIDE VALIDATION

    if ($title === '' || $description === '' || $steps === '') {
        $error = 'Please fill in all recipe details.';

    } elseif (strlen($title) > 150) {
        $error = 'Recipe title must not exceed 150 characters.';

    } elseif (!$validCategory) {
        $error = 'Please select a valid category.';

    } elseif (
        count($ingredients) === 0 ||
        count($ingredients) !== count($quantities)
    ) {
        $error = 'Please provide at least one complete ingredient.';

    } elseif (count($ingredients) > 50) {
        $error = 'A recipe can have a maximum of 50 ingredients.';

    } else {

        // Validate every ingredient and quantity.
        foreach ($ingredients as $index => $ingredientName) {

            if (!is_string($ingredientName) ||
                !isset($quantities[$index]) ||
                !is_string($quantities[$index])) {
                $error = 'Invalid ingredient information.';
                break;
            }

            $ingredients[$index] = trim($ingredientName);
            $quantities[$index] = trim($quantities[$index]);

            if (
                $ingredients[$index] === '' ||
                $quantities[$index] === ''
            ) {
                $error = 'Ingredient names and quantities are required.';
                break;
            }

            if (
                strlen($ingredients[$index]) > 150 ||
                strlen($quantities[$index]) > 100
            ) {
                $error = 'An ingredient name or quantity is too long.';
                break;
            }
        }

        // Save the recipe only when validation passes.
        if ($error === '') {

            $newRecipeId = $recipe->createRecipe(
                $_SESSION['user_id'],
                $categoryId,
                $title,
                $description,
                $steps,
                $ingredients,
                $quantities
            );

            if ($newRecipeId) {

                // Redirect to the newly created recipe.
                header(
                    'Location: recipe.php?id=' .
                    (int) $newRecipeId
                );
                exit;

            } else {
                $error = 'Unable to save the recipe. Please try again.';
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Share a Recipe | Recipe Byte</title>

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
                    <a href="create_recipe.php" class="active">
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

    <!-- CREATE RECIPE CONTENT -->
    <main class="section">

        <div class="section-heading">

            <p class="eyebrow">Share Your Cooking</p>

            <h1>Share a Recipe.</h1>

            <p>
                Have a favorite lutong bahay dish?
                Share its ingredients and cooking steps
                with the Recipe Byte community.
            </p>

        </div>

        <section class="content-card recipe-form-card">

            <?php if ($error !== ''): ?>

                <p class="form-error">
                    <?= htmlspecialchars($error) ?>
                </p>

            <?php endif; ?>

            <form
                action="create_recipe.php"
                method="POST"
                class="auth-form"
                id="recipe-form"
            >

                <!-- CSRF protection -->
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
                >

                <!-- Recipe title -->
                <div class="form-group">

                    <label for="title">Recipe Title</label>

                    <input
                        type="text"
                        id="title"
                        name="title"
                        maxlength="150"
                        placeholder="e.g. Chicken Adobo"
                        value="<?= htmlspecialchars($title) ?>"
                        required
                    >

                </div>

                <!-- Recipe category -->
                <div class="form-group">

                    <label for="category_id">Category</label>

                    <select
                        id="category_id"
                        name="category_id"
                        required
                    >

                        <option value="">
                            Select a category
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

                <!-- Short description -->
                <div class="form-group">

                    <label for="description">
                        Short Description
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        rows="3"
                        placeholder="Tell the community about your recipe..."
                        required
                    ><?= htmlspecialchars($description) ?></textarea>

                </div>

                <!-- Dynamic ingredients -->
                <div class="form-group">

                    <label>Ingredients</label>

                    <p class="field-hint">
                        Add the ingredients needed for your recipe.
                        You can add more rows as needed.
                    </p>

                    <div id="ingredients-container">

                        <?php foreach ($ingredients as $index => $ingredientName): ?>

                            <div class="ingredient-row">

                                <input
                                    type="text"
                                    name="ingredients[]"
                                    placeholder="Ingredient name"
                                    maxlength="150"
                                    value="<?= htmlspecialchars(is_string($ingredientName) ? $ingredientName : '') ?>"
                                    required
                                >

                                <input
                                    type="text"
                                    name="quantities[]"
                                    placeholder="Quantity (e.g. 2 cups)"
                                    maxlength="100"
                                    value="<?= htmlspecialchars(is_string($quantities[$index] ?? '') ? $quantities[$index] : '') ?>"
                                    required
                                >

                                <button
                                    type="button"
                                    class="remove-ingredient"
                                    aria-label="Remove ingredient"
                                >
                                    Remove
                                </button>

                            </div>

                        <?php endforeach; ?>

                    </div>

                    <button
                        type="button"
                        id="add-ingredient"
                        class="button secondary-button add-ingredient-button"
                    >
                        + Add Ingredient
                    </button>

                </div>

                <!-- Cooking instructions -->
                <div class="form-group">

                    <label for="steps">
                        Cooking Steps
                    </label>

                    <textarea
                        id="steps"
                        name="steps"
                        rows="8"
                        placeholder="Step 1: Prepare the ingredients..."
                        required
                    ><?= htmlspecialchars($steps) ?></textarea>

                </div>

                <!-- Submit and cancel -->
                <div class="recipe-form-actions">

                    <button
                        type="submit"
                        class="button primary-button"
                    >
                        Publish Recipe
                    </button>

                    <a
                        href="explore.php"
                        class="button secondary-button"
                    >
                        Cancel
                    </a>

                </div>

            </form>

        </section>

    </main>

    <!-- FOOTER -->
    <footer class="site-footer">

        <p>
            &copy; <?= date('Y') ?> Recipe Byte.
            Made for home cooks.
        </p>

        <p>Share recipes. Discover flavors.</p>

    </footer>

    <script src="assets/js/script.js"></script>

</body>
</html>
