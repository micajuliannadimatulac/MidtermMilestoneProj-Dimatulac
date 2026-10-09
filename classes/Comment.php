
<?php

class Comment {

    // Store the PDO database connection.
    private $pdo;

    // Constructor receives the shared database connection.
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Get all comments belonging to a recipe.
    public function getComments($recipeId) {

        $stmt = $this->pdo->prepare(
            'SELECT
                comments.id,
                comments.recipe_id,
                comments.user_id,
                comments.content,
                comments.is_edited,
                comments.created_at,
                users.first_name,
                users.last_name

            FROM comments

            INNER JOIN users
                ON comments.user_id = users.id

            WHERE comments.recipe_id = :recipe_id

            ORDER BY comments.created_at DESC, comments.id DESC'
        );

        $stmt->execute([
            ':recipe_id' => $recipeId
        ]);

        return $stmt->fetchAll();
    }

    // Retrieve one comment by its ID.
    public function getCommentById($commentId) {

        $stmt = $this->pdo->prepare(
            'SELECT *
             FROM comments
             WHERE id = :comment_id'
        );

        $stmt->execute([
            ':comment_id' => $commentId
        ]);

        return $stmt->fetch();
    }

    // Add a comment to a recipe.
    public function addComment($recipeId, $userId, $content) {

        $stmt = $this->pdo->prepare(
            'INSERT INTO comments (
                recipe_id,
                user_id,
                content
            )
            VALUES (
                :recipe_id,
                :user_id,
                :content
            )'
        );

        return $stmt->execute([
            ':recipe_id' => $recipeId,
            ':user_id' => $userId,
            ':content' => $content
        ]);
    }

    // Update a comment only if it belongs to the member.
    public function updateComment($commentId, $userId, $content) {

        $stmt = $this->pdo->prepare(
            'UPDATE comments

             SET
                content = :content,
                is_edited = 1

             WHERE id = :comment_id
             AND user_id = :user_id'
        );

        $stmt->execute([
            ':content' => $content,
            ':comment_id' => $commentId,
            ':user_id' => $userId
        ]);

        return $stmt->rowCount() > 0;
    }

    // Delete a comment only if it belongs to the member.
    public function deleteComment($commentId, $userId) {

        $stmt = $this->pdo->prepare(
            'DELETE FROM comments

             WHERE id = :comment_id
             AND user_id = :user_id'
        );

        $stmt->execute([
            ':comment_id' => $commentId,
            ':user_id' => $userId
        ]);

        return $stmt->rowCount() > 0;
    }
}
