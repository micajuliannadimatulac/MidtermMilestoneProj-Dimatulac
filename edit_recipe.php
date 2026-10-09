
<?php

session_start();

// Protect the page.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require 'db.php';
require 'classes/Recipe.php';

// Create Recipe object.
$recipe = new Recipe($pdo);

// Get recipe ID from URL or submitted form.
$recipeId = filter_var(
    $_POST['recipe_id'] ?? $_GET['id'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$recipeId || $recipeId < 1) {
    header('Location: explore.php');
    exit;
}

// Retrieve recipe details.
$recipeDetails = $recipe->getRecipeById($recipeId);

// Check whether the recipe exists.
if (!$recipeDetails) {
    http_response_code(404);
    exit('Recipe not found.');
}

// Only the recipe owner can edit it.
if (
    (int) $recipeDetails['user_id'] !==
    (int) $_SESSION['user_id']
) {
    http_response_code(403);
    exit('You cannot edit this recipe.');
}

// Retrieve categories and ingredients.
$categories = $recipe->getCategories();
$existingIngredients = $recipe->getIngredients($recipeId);

// Create a CSRF token.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Set initial form values.
$title = $recipeDetails['title'];
$description = $recipeDetails['description'];
$steps = $recipeDetails['steps'];
$categoryId = $recipeDetails['category_id'];

$ingredients = array_column(
    $existingIngredients,
    'ingredient_name'
);

$quantities = array_column(
    $existingIngredients,
    'quantity'
);

$error = '';

// Process the edit form.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validate CSRF token.
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($submittedToken) ||
        !hash_equals($_SESSION['csrf_token'], $submittedToken)
    ) {
        http_response_code(403);
        exit('Invalid form submission.');
    }

    // Get submitted values.
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $steps = trim($_POST['steps'] ?? '');

    $categoryId = filter_var(
        $_POST['category_id'] ?? null,
        FILTER_VALIDATE_INT
    );

    $ingredients = $_POST['ingredients'] ?? [];
    $quantities = $_POST['quantities'] ?? [];

    // Validate basic recipe fields.
    if (
        $title === '' ||
        $description === '' ||
        $steps === ''
    ) {
        $error = 'Please complete all required fields.';

    } elseif (mb_strlen($title) > 150) {

        $error = 'Recipe title must not exceed 150 characters.';

    } else {

        // Validate selected category.
        $validCategory = false;

        foreach ($categories as $category) {

            if ((int) $category['id'] === (int) $categoryId) {
                $validCategory = true;
                break;
            }
        }

        if (!$validCategory) {
            $error = 'Please select a valid category.';
        }
    }

    // Validate ingredient arrays.
    if ($error === '') {

        if (
            !is_array($ingredients) ||
            !is_array($quantities) ||
            count($ingredients) < 1 ||
            count($ingredients) > 50 ||
            count($ingredients) !== count($quantities)
        ) {

            $error = 'Please provide 1 to 50 valid ingredients.';

        } else {

            // Normalize indexes.
            $ingredients = array_values($ingredients);
            $quantities = array_values($quantities);

            foreach ($ingredients as $index => $ingredientName) {

                // Reject nested or unexpected input values.
                if (
                    !is_string($ingredientName) ||
                    !isset($quantities[$index]) ||
                    !is_string($quantities[$index])
                ) {
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
                    mb_strlen($ingredients[$index]) > 150 ||
                    mb_strlen($quantities[$index]) > 100
                ) {
                    $error = 'An ingredient name or quantity is too long.';
                    break;
                }
            }
        }
    }

    // Save the updated recipe.
    if ($error === '') {

        $updated = $recipe->updateRecipe(
            $recipeId,
            $_SESSION['user_id'],
            $categoryId,
            $title,
            $description,
            $steps,
            $ingredients,
            $quantities
        );

        if ($updated) {

            header('Location: recipe.php?id=' . $recipeId);
            exit;

        } else {

            $error = 'Unable to update the recipe.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Recipe | Recipe Byte</title>

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

                <li><a href="index.php">Home</a></li>

                <li>
                    <a href="explore.php">Explore Recipes</a>
                </li>

                <li>
                    <a href="create_recipe.php">Share a Recipe</a>
                </li>

                <li>
                    <a href="favorites.php">My Favorites</a>
                </li>

                <li><a href="logout.php">Logout</a></li>

            </ul>
        </nav>
    </header>

    <!-- EDIT RECIPE FORM -->
    <main class="section">

        <div class="section-heading">

            <p class="eyebrow">Your Recipe Collection</p>

            <h1>Edit Your Recipe.</h1>

            <p>
                Make changes to your recipe
                and keep your community updated.
            </p>

        </div>

        <section class="content-card recipe-form-card">

            <!-- Error message -->
            <?php if ($error !== ''): ?>

                <p class="form-error">
                    <?= htmlspecialchars($error) ?>
                </p>

            <?php endif; ?>

            <form
                action="edit_recipe.php"
                method="POST"
                class="auth-form"
            >

                <!-- Hidden recipe ID -->
                <input
                    type="hidden"
                    name="recipe_id"
                    value="<?= (int) $recipeId ?>"
                >

                <!-- CSRF token -->
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
                >

                <!-- RECIPE TITLE -->
                <div class="form-group">

                    <label for="title">
                        Recipe Title
                    </label>

                    <input
                        type="text"
                        id="title"
                        name="title"
                        maxlength="150"
                        value="<?= htmlspecialchars($title) ?>"
                        required
                    >

                </div>

                <!-- CATEGORY -->
                <div class="form-group">

                    <label for="category_id">
                        Category
                    </label>

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
                                <?= (int) $categoryId === (int) $category['id']
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= htmlspecialchars($category['name']) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <!-- DESCRIPTION -->
                <div class="form-group">

                    <label for="description">
                        Short Description
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        rows="4"
                        required
                    ><?= htmlspecialchars($description) ?></textarea>

                </div>

                <!-- INGREDIENTS -->
                <div class="form-group">

                    <label>Ingredients</label>

                    <p class="field-hint">
                        Update ingredient names and quantities.
                    </p>

                    <div id="ingredients-container">

                        <?php foreach ($ingredients as $index => $ingredientName): ?>

                            <div class="ingredient-row">

                                <input
                                    type="text"
                                    name="ingredients[]"
                                    placeholder="Ingredient name"
                                    maxlength="150"
                                    value="<?= htmlspecialchars(
                                        is_string($ingredientName)
                                            ? $ingredientName
                                            : ''
                                    ) ?>"
                                    required
                                >

                                <input
                                    type="text"
                                    name="quantities[]"
                                    placeholder="Quantity"
                                    maxlength="100"
                                    value="<?= htmlspecialchars(
                                        isset($quantities[$index]) &&
                                        is_string($quantities[$index])
                                            ? $quantities[$index]
                                            : ''
                                    ) ?>"
                                    required
                                >

                                <button
                                    type="button"
                                    class="remove-ingredient"
                                >
                                    Remove
                                </button>

                            </div>

                        <?php endforeach; ?>

                    </div>

                    <button
                        type="button"
                        id="add-ingredient"
                        class="add-ingredient-button"
                    >
                        + Add Ingredient
                    </button>

                </div>

                <!-- COOKING STEPS -->
                <div class="form-group">

                    <label for="steps">
                        Cooking Instructions
                    </label>

                    <textarea
                        id="steps"
                        name="steps"
                        rows="8"
                        placeholder="Write the cooking steps..."
                        required
                    ><?= htmlspecialchars($steps) ?></textarea>

                </div>

                <!-- FORM ACTIONS -->
                <div class="recipe-form-actions">

                    <button
                        type="submit"
                        class="button primary-button"
                    >
                        Save Changes
                    </button>

                    <a
                        href="recipe.php?id=<?= (int) $recipeId ?>"
                        class="button secondary-button"
                    >
                        Cancel
                    </a>

                </div>

            </form>

        </section>

    </main>

    <!-- Load existing dynamic ingredient JavaScript -->
    <script src="assets/js/script.js"></script>

</body>
</html>
