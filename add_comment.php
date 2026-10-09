
<?php

session_start();

// Only logged-in members can post comments.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Only accept POST requests.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: explore.php');
    exit;
}

require 'db.php';
require 'classes/Recipe.php';
require 'classes/Comment.php';

// Check the CSRF token.
$submittedToken = $_POST['csrf_token'] ?? '';

if (
    !is_string($submittedToken) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $submittedToken)
) {
    http_response_code(403);
    exit('Invalid form submission.');
}

// Validate the recipe ID.
$recipeId = filter_var(
    $_POST['recipe_id'] ?? null,
    FILTER_VALIDATE_INT
);

// Get the submitted comment.
$content = trim($_POST['content'] ?? '');

if (!$recipeId || $recipeId < 1) {
    header('Location: explore.php');
    exit;
}

// Create the Recipe object.
$recipe = new Recipe($pdo);

// Make sure the recipe exists.
if (!$recipe->getRecipeById($recipeId)) {
    http_response_code(404);
    exit('Recipe not found.');
}

// Validate the comment.
if ($content === '' || mb_strlen($content) > 1000) {
    header('Location: recipe.php?id=' . $recipeId . '&error=comment');
    exit;
}

// Create the Comment object.
$comment = new Comment($pdo);

// Save the comment.
$comment->addComment(
    $recipeId,
    $_SESSION['user_id'],
    $content
);

// Return to the recipe's comment section.
header('Location: recipe.php?id=' . $recipeId . '#comments');
exit;
