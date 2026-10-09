
<?php

session_start();

// Protect the page.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require 'db.php';
require 'classes/Comment.php';

// Create Comment object.
$comment = new Comment($pdo);

// Get comment ID from GET or POST.
$commentId = filter_var(
    $_POST['comment_id'] ?? $_GET['id'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$commentId || $commentId < 1) {
    header('Location: explore.php');
    exit;
}

// Retrieve the comment.
$commentDetails = $comment->getCommentById($commentId);

if (!$commentDetails) {
    http_response_code(404);
    exit('Comment not found.');
}

// Only the comment author can access this page.
if (
    (int) $commentDetails['user_id'] !==
    (int) $_SESSION['user_id']
) {
    http_response_code(403);
    exit('You cannot edit this comment.');
}

// Create a CSRF token.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$content = $commentDetails['content'];

// Process the edit form.
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

    $content = trim($_POST['content'] ?? '');

    // Validate updated comment.
    if ($content === '' || mb_strlen($content) > 1000) {

        $error = 'Comment must contain 1 to 1000 characters.';

    } else {

        // Update only the current member's comment.
        $updated = $comment->updateComment(
            $commentId,
            $_SESSION['user_id'],
            $content
        );

        if ($updated) {

            header(
                'Location: recipe.php?id=' .
                (int) $commentDetails['recipe_id'] .
                '#comments'
            );
            exit;

        } else {
            $error = 'Unable to update the comment.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Comment | Recipe Byte</title>

    <link rel="stylesheet" href="assets/css/styles.css">
</head>

<body>

    <header class="site-header">
        <nav class="navbar">

            <a href="index.php" class="logo">
                Recipe<span>Byte.</span>
            </a>

            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="explore.php">Explore Recipes</a></li>
                <li><a href="create_recipe.php">Share a Recipe</a></li>
                <li><a href="favorites.php">My Favorites</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>

        </nav>
    </header>

    <main class="section">

        <div class="section-heading">

            <p class="eyebrow">Community Feedback</p>

            <h1>Edit Comment.</h1>

            <p>
                Update your comment on this recipe.
            </p>

        </div>

        <section class="content-card comment-edit-card">

            <?php if ($error !== ''): ?>

                <p class="form-error">
                    <?= htmlspecialchars($error) ?>
                </p>

            <?php endif; ?>

            <form
                action="edit_comment.php"
                method="POST"
                class="auth-form"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
                >

                <input
                    type="hidden"
                    name="comment_id"
                    value="<?= (int) $commentId ?>"
                >

                <div class="form-group">

                    <label for="content">
                        Your Comment
                    </label>

                    <textarea
                        id="content"
                        name="content"
                        rows="6"
                        maxlength="1000"
                        required
                    ><?= htmlspecialchars($content) ?></textarea>

                </div>

                <div class="recipe-form-actions">

                    <button
                        type="submit"
                        class="button primary-button"
                    >
                        Save Changes
                    </button>

                    <a
                        href="recipe.php?id=<?= (int) $commentDetails['recipe_id'] ?>#comments"
                        class="button secondary-button"
                    >
                        Cancel
                    </a>

                </div>

            </form>

        </section>

    </main>

</body>
</html>
