
<?php

session_start();

// Only authenticated members can delete recipes.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Only allow POST requests.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: explore.php');
    exit;
}

require 'db.php';
require 'classes/Recipe.php';

// Validate CSRF token.
$submittedToken = $_POST['csrf_token'] ?? '';

if (
    !is_string($submittedToken) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $submittedToken)
) {
    http_response_code(403);
    exit('Invalid form submission.');
}

// Validate recipe ID.
$recipeId = filter_var(
    $_POST['recipe_id'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$recipeId || $recipeId < 1) {
    header('Location: explore.php');
    exit;
}

// Create Recipe object.
$recipe = new Recipe($pdo);

// Retrieve the recipe before deleting it.
$recipeDetails = $recipe->getRecipeById($recipeId);

if (!$recipeDetails) {
    http_response_code(404);
    exit('Recipe not found.');
}

// Check whether the logged-in member owns the recipe.
if (
    (int) $recipeDetails['user_id'] !==
    (int) $_SESSION['user_id']
) {
    http_response_code(403);
    exit('You cannot delete this recipe.');
}

// Delete the recipe.
$deleted = $recipe->deleteRecipe(
    $recipeId,
    $_SESSION['user_id']
);

if (!$deleted) {
    http_response_code(500);
    exit('Unable to delete recipe.');
}

// Redirect to Explore Recipes after deletion.
header('Location: explore.php?deleted=1');
exit;
