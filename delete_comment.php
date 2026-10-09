
<?php

session_start();

// Only authenticated members can delete comments.
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
require 'classes/Comment.php';

// Verify CSRF token.
$submittedToken = $_POST['csrf_token'] ?? '';

if (
    !is_string($submittedToken) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $submittedToken)
) {
    http_response_code(403);
    exit('Invalid form submission.');
}

// Validate comment ID.
$commentId = filter_var(
    $_POST['comment_id'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$commentId || $commentId < 1) {
    header('Location: explore.php');
    exit;
}

// Create Comment object.
$comment = new Comment($pdo);

// Retrieve comment details before deleting.
$commentDetails = $comment->getCommentById($commentId);

if (!$commentDetails) {
    http_response_code(404);
    exit('Comment not found.');
}

// Verify ownership.
if (
    (int) $commentDetails['user_id'] !==
    (int) $_SESSION['user_id']
) {
    http_response_code(403);
    exit('You cannot delete this comment.');
}

// Delete only the current member's comment.
$deleted = $comment->deleteComment(
    $commentId,
    $_SESSION['user_id']
);

if (!$deleted) {
    http_response_code(500);
    exit('Unable to delete comment.');
}

// Redirect to the recipe after deletion.
header(
    'Location: recipe.php?id=' .
    (int) $commentDetails['recipe_id'] .
    '#comments'
);
exit;
