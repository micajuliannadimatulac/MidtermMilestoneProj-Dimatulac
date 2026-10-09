
<?php

class Favorite {

    // Store the shared PDO connection.
    private $pdo;

    // Constructor receives the database connection.
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Check whether a member has saved a recipe.
    public function isFavorite($userId, $recipeId) {

        $stmt = $this->pdo->prepare(
            'SELECT id
             FROM favorites
             WHERE user_id = :user_id
             AND recipe_id = :recipe_id'
        );

        $stmt->execute([
            ':user_id' => $userId,
            ':recipe_id' => $recipeId
        ]);

        return (bool) $stmt->fetch();
    }

    // Add a recipe to the member's favorites.
    public function addFavorite($userId, $recipeId) {

        // IGNORE prevents duplicate user-recipe pairs.
        // The database also has a UNIQUE constraint.
        $stmt = $this->pdo->prepare(
            'INSERT IGNORE INTO favorites (
                user_id,
                recipe_id
            )
            VALUES (
                :user_id,
                :recipe_id
            )'
        );

        return $stmt->execute([
            ':user_id' => $userId,
            ':recipe_id' => $recipeId
        ]);
    }

    // Remove a recipe from the member's favorites.
    public function removeFavorite($userId, $recipeId) {

        $stmt = $this->pdo->prepare(
            'DELETE FROM favorites
             WHERE user_id = :user_id
             AND recipe_id = :recipe_id'
        );

        return $stmt->execute([
            ':user_id' => $userId,
            ':recipe_id' => $recipeId
        ]);
    }

    // Retrieve all recipes saved by one member.
    public function getFavorites($userId) {

        $stmt = $this->pdo->prepare(
            'SELECT
                recipes.id,
                recipes.title,
                recipes.description,
                recipes.created_at,
                recipes.is_edited,
                users.first_name,
                users.last_name,
                categories.name AS category_name

             FROM favorites

             INNER JOIN recipes
                ON favorites.recipe_id = recipes.id

             INNER JOIN users
                ON recipes.user_id = users.id

             INNER JOIN categories
                ON recipes.category_id = categories.id

             WHERE favorites.user_id = :user_id

             ORDER BY favorites.id DESC'
        );

        $stmt->execute([
            ':user_id' => $userId
        ]);

        return $stmt->fetchAll();
    }
}
