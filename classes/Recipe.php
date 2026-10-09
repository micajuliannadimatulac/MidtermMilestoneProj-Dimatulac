
<?php

class Recipe {

    // Store the shared PDO connection.
    private $pdo;

    // Constructor receives the database connection.
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Get all available recipe categories.
    public function getCategories() {

        $stmt = $this->pdo->prepare(
            'SELECT id, name
             FROM categories
             ORDER BY id ASC'
        );

        $stmt->execute();

        return $stmt->fetchAll();
    }

    // Get recipes with optional search and category filters.
    public function getRecipes($search = '', $categoryId = 0) {

        // Join recipes with their authors and categories.
        $sql = '
            SELECT
                recipes.id,
                recipes.title,
                recipes.description,
                recipes.created_at,
                recipes.is_edited,
                users.first_name,
                users.last_name,
                categories.name AS category_name

            FROM recipes

            INNER JOIN users
                ON recipes.user_id = users.id

            INNER JOIN categories
                ON recipes.category_id = categories.id

            WHERE 1 = 1
        ';

        // Store values for the prepared statement.
        $params = [];

        // Add keyword search if a search term was entered.
        if ($search !== '') {

            $sql .= '
                AND (
                    recipes.title LIKE :search_title
                    OR recipes.description LIKE :search_description
                )
            ';

            $params[':search_title'] = '%' . $search . '%';
            $params[':search_description'] = '%' . $search . '%';
        }

        // Filter by category if one was selected.
        if ($categoryId > 0) {

            $sql .= ' AND recipes.category_id = :category_id';

            $params[':category_id'] = $categoryId;
        }

        // Display the newest recipes first.
        $sql .= ' ORDER BY recipes.created_at DESC, recipes.id DESC';

        // Prepare and execute the query.
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    
    // Create a recipe together with its ingredients.
    public function createRecipe(
        $userId,
        $categoryId,
        $title,
        $description,
        $steps,
        $ingredients,
        $quantities
    ) {

        try {

            // Start a transaction so all records are saved together.
            $this->pdo->beginTransaction();

            // Insert the main recipe.
            $stmt = $this->pdo->prepare(
                'INSERT INTO recipes (
                    user_id,
                    category_id,
                    title,
                    description,
                    steps
                )
                VALUES (
                    :user_id,
                    :category_id,
                    :title,
                    :description,
                    :steps
                )'
            );

            $stmt->execute([
                ':user_id' => $userId,
                ':category_id' => $categoryId,
                ':title' => $title,
                ':description' => $description,
                ':steps' => $steps
            ]);

            // Get the ID of the newly created recipe.
            $recipeId = $this->pdo->lastInsertId();

            // Prepare the ingredient INSERT statement.
            $ingredientStmt = $this->pdo->prepare(
                'INSERT INTO ingredients (
                    recipe_id,
                    ingredient_name,
                    quantity
                )
                VALUES (
                    :recipe_id,
                    :ingredient_name,
                    :quantity
                )'
            );

            // Insert each ingredient.
            foreach ($ingredients as $index => $ingredientName) {

                $ingredientStmt->execute([
                    ':recipe_id' => $recipeId,
                    ':ingredient_name' => $ingredientName,
                    ':quantity' => $quantities[$index]
                ]);
            }

            // Save all changes permanently.
            $this->pdo->commit();

            // Return the new recipe ID.
            return $recipeId;

        } catch (PDOException $e) {

            // Undo all inserts if something goes wrong.
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return false;
        }
    }

    
    // Get one recipe using its ID.
    public function getRecipeById($recipeId) {

        // Retrieve recipe details with the author's name
        // and category name.
        $stmt = $this->pdo->prepare(
            'SELECT
                recipes.id,
                recipes.user_id,
                recipes.category_id,
                recipes.title,
                recipes.description,
                recipes.steps,
                recipes.is_edited,
                recipes.created_at,
                recipes.updated_at,
                users.first_name,
                users.last_name,
                categories.name AS category_name

            FROM recipes

            INNER JOIN users
                ON recipes.user_id = users.id

            INNER JOIN categories
                ON recipes.category_id = categories.id

            WHERE recipes.id = :recipe_id'
        );

        $stmt->execute([
            ':recipe_id' => $recipeId
        ]);

        // Return the recipe or false if it doesn't exist.
        return $stmt->fetch();
    }

    // Get all ingredients belonging to one recipe.
    public function getIngredients($recipeId) {

        $stmt = $this->pdo->prepare(
            'SELECT
                id,
                ingredient_name,
                quantity

            FROM ingredients

            WHERE recipe_id = :recipe_id

            ORDER BY id ASC'
        );

        $stmt->execute([
            ':recipe_id' => $recipeId
        ]);

        return $stmt->fetchAll();
    }

    
    // Update a recipe and its ingredients.
    public function updateRecipe(
        $recipeId,
        $userId,
        $categoryId,
        $title,
        $description,
        $steps,
        $ingredients,
        $quantities
    ) {

        try {

            // Begin transaction so all changes succeed together.
            $this->pdo->beginTransaction();

            // Update only a recipe owned by the current member.
            $stmt = $this->pdo->prepare(
                'UPDATE recipes

                 SET
                    category_id = :category_id,
                    title = :title,
                    description = :description,
                    steps = :steps,
                    is_edited = 1

                 WHERE id = :recipe_id
                 AND user_id = :user_id'
            );

            $stmt->execute([
                ':category_id' => $categoryId,
                ':title' => $title,
                ':description' => $description,
                ':steps' => $steps,
                ':recipe_id' => $recipeId,
                ':user_id' => $userId
            ]);

            // If no owned recipe was updated, stop.
            if ($stmt->rowCount() === 0) {
                $this->pdo->rollBack();
                return false;
            }

            // Remove the old ingredients for this recipe.
            $deleteStmt = $this->pdo->prepare(
                'DELETE FROM ingredients
                 WHERE recipe_id = :recipe_id'
            );

            $deleteStmt->execute([
                ':recipe_id' => $recipeId
            ]);

            // Prepare the query for inserting new ingredients.
            $insertStmt = $this->pdo->prepare(
                'INSERT INTO ingredients (
                    recipe_id,
                    ingredient_name,
                    quantity
                )
                VALUES (
                    :recipe_id,
                    :ingredient_name,
                    :quantity
                )'
            );

            // Insert each updated ingredient.
            foreach ($ingredients as $index => $ingredientName) {

                $insertStmt->execute([
                    ':recipe_id' => $recipeId,
                    ':ingredient_name' => $ingredientName,
                    ':quantity' => $quantities[$index]
                ]);
            }

            // Save all changes.
            $this->pdo->commit();

            return true;

        } catch (PDOException $e) {

            // Undo changes if something fails.
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return false;
        }
    }

    // Delete a recipe only if it belongs to the member.
    public function deleteRecipe($recipeId, $userId) {

        $stmt = $this->pdo->prepare(
            'DELETE FROM recipes
             WHERE id = :recipe_id
             AND user_id = :user_id'
        );

        $stmt->execute([
            ':recipe_id' => $recipeId,
            ':user_id' => $userId
        ]);

        return $stmt->rowCount() > 0;
    }

}
