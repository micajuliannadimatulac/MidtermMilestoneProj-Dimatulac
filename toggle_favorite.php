
<?php

session_start();

// Tell the browser this file returns JSON.
header('Content-Type: application/json');

// Only authenticated members can save recipes.
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Please log in first.'
    ]);

    exit;
}

// Only accept POST requests.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);

    exit;
}

require 'db.php';
require 'classes/Recipe.php';
require 'classes/Favorite.php';

// Check the CSRF token.
$submittedToken = $_POST['csrf_token'] ?? '';

if (
    !is_string($submittedToken) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $submittedToken)
) {
    http_response_code(403);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid form submission.'
    ]);

    exit;
}

// Validate the recipe ID.
$recipeId = filter_var(
    $_POST['recipe_id'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$recipeId || $recipeId < 1) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid recipe ID.'
    ]);

    exit;
}

// Make sure the recipe exists.
$recipe = new Recipe($pdo);

if (!$recipe->getRecipeById($recipeId)) {
    http_response_code(404);

    echo json_encode([
        'success' => false,
        'message' => 'Recipe not found.'
    ]);

    exit;
}

// Create the Favorite object.
$favorite = new Favorite($pdo);

// Get the logged-in member ID.
$userId = $_SESSION['user_id'];

try {

    // Check the current favorite status.
    $isSaved = $favorite->isFavorite($userId, $recipeId);

    if ($isSaved) {

        // Remove the favorite.
        $favorite->removeFavorite($userId, $recipeId);

    } else {

        // Save the favorite.
        $favorite->addFavorite($userId, $recipeId);
    }

    // Retrieve the updated status from the database.
    $newStatus = $favorite->isFavorite($userId, $recipeId);

    // Return the result to JavaScript.
    echo json_encode([
        'success' => true,
        'is_favorite' => $newStatus,
        'message' => $newStatus
            ? 'Recipe saved to favorites.'
            : 'Recipe removed from favorites.'
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to update favorites.'
    ]);
}
