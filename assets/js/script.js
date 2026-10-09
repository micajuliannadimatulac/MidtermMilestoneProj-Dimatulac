
// RECIPE BYTE - DYNAMIC INGREDIENT FIELDS

// Get the ingredients container and Add Ingredient button.
const ingredientsContainer = document.getElementById("ingredients-container");
const addIngredientButton = document.getElementById("add-ingredient");

// Only run this code on pages that have ingredient fields.
if (ingredientsContainer && addIngredientButton) {

    // Add a new ingredient row.
    addIngredientButton.addEventListener("click", function () {

        // Limit the form to 50 ingredients.
        if (ingredientsContainer.children.length >= 50) {
            alert("You can add a maximum of 50 ingredients.");
            return;
        }

        // Create a container for the new ingredient.
        const ingredientRow = document.createElement("div");
        ingredientRow.className = "ingredient-row";

        // Create the ingredient name input.
        const ingredientInput = document.createElement("input");
        ingredientInput.type = "text";
        ingredientInput.name = "ingredients[]";
        ingredientInput.placeholder = "Ingredient name";
        ingredientInput.maxLength = 150;
        ingredientInput.required = true;

        // Create the quantity input.
        const quantityInput = document.createElement("input");
        quantityInput.type = "text";
        quantityInput.name = "quantities[]";
        quantityInput.placeholder = "Quantity (e.g. 2 cups)";
        quantityInput.maxLength = 100;
        quantityInput.required = true;

        // Create the Remove button.
        const removeButton = document.createElement("button");
        removeButton.type = "button";
        removeButton.className = "remove-ingredient";
        removeButton.textContent = "Remove";

        // Add the inputs and button to the row.
        ingredientRow.appendChild(ingredientInput);
        ingredientRow.appendChild(quantityInput);
        ingredientRow.appendChild(removeButton);

        // Display the new ingredient row.
        ingredientsContainer.appendChild(ingredientRow);
    });

    // Handle clicks on any Remove button.
    ingredientsContainer.addEventListener("click", function (event) {

        if (event.target.classList.contains("remove-ingredient")) {

            // At least one ingredient must remain.
            if (ingredientsContainer.children.length <= 1) {
                alert("A recipe must have at least one ingredient.");
                return;
            }

            // Remove the selected ingredient row.
            event.target.closest(".ingredient-row").remove();
        }
    });
}


// =========================================
// RECIPE BYTE - COOKING CHECKLIST
// =========================================

// Get all ingredient checkboxes.
const ingredientCheckboxes = document.querySelectorAll(
    ".ingredient-checkbox"
);

// Get the checklist status and reset button.
const checklistStatus = document.getElementById("checklist-status");
const resetChecklistButton = document.getElementById("reset-checklist");

// Run only on pages with the cooking checklist.
if (checklistStatus && resetChecklistButton) {

    // Update the number of prepared ingredients.
    function updateChecklist() {

        let completed = 0;

        // Check each ingredient checkbox.
        ingredientCheckboxes.forEach(function (checkbox) {

            // Get the ingredient's parent label.
            const ingredientItem = checkbox.closest(".ingredient-item");

            if (checkbox.checked) {

                completed++;

                // Add the checked appearance.
                ingredientItem.classList.add("checked");

            } else {

                // Remove the checked appearance.
                ingredientItem.classList.remove("checked");
            }
        });

        // Display the updated checklist progress.
        checklistStatus.textContent =
            completed + " of " +
            ingredientCheckboxes.length +
            " ingredients ready";
    }

    // Update progress whenever a checkbox changes.
    ingredientCheckboxes.forEach(function (checkbox) {

        checkbox.addEventListener("change", updateChecklist);
    });

    // Reset all ingredient checkboxes.
    resetChecklistButton.addEventListener("click", function () {

        ingredientCheckboxes.forEach(function (checkbox) {
            checkbox.checked = false;
        });

        updateChecklist();
    });

    // Display the initial checklist progress.
    updateChecklist();
}


// =========================================
// RECIPE BYTE - FAVORITES USING FETCH
// =========================================

// Get the favorite button.
const favoriteButton = document.getElementById("favorite-button");

// Run only when the page contains the favorite button.
if (favoriteButton) {

    // Get the text, icon, and message elements.
    const favoriteText = document.getElementById("favorite-text");
    const favoriteIcon = document.getElementById("favorite-icon");
    const favoriteMessage = document.getElementById("favorite-message");

    // Run when the member clicks the favorite button.
    favoriteButton.addEventListener("click", async function () {

        // Get the recipe ID and CSRF token from the button.
        const recipeId = favoriteButton.dataset.recipeId;
        const csrfToken = favoriteButton.dataset.csrfToken;

        // Prevent multiple clicks while processing.
        favoriteButton.disabled = true;

        favoriteMessage.textContent = "";

        // Prepare the data to send to PHP.
        const formData = new FormData();

        formData.append("recipe_id", recipeId);
        formData.append("csrf_token", csrfToken);

        try {

            // Send a POST request without reloading the page.
            const response = await fetch("toggle_favorite.php", {
                method: "POST",
                body: formData
            });

            // Convert the JSON response into a JavaScript object.
            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(
                    result.message || "Unable to update favorites."
                );
            }

            // Update the button based on the returned status.
            if (result.is_favorite) {

                favoriteButton.classList.add("saved");

                favoriteIcon.textContent = "♥";
                favoriteText.textContent = "Saved to Favorites";

                favoriteButton.setAttribute("aria-pressed", "true");

            } else {

                favoriteButton.classList.remove("saved");

                favoriteIcon.textContent = "♡";
                favoriteText.textContent = "Save to Favorites";

                favoriteButton.setAttribute("aria-pressed", "false");
            }

            // Display the success message.
            favoriteMessage.textContent = result.message;

        } catch (error) {

            // Display an error if the request fails.
            favoriteMessage.textContent = error.message;

        } finally {

            // Enable the button again.
            favoriteButton.disabled = false;
        }
    });
}


// =========================================
// RECIPE BYTE - CARD FAVORITE BUTTONS
// =========================================

// Get all favorite buttons inside recipe cards.
const cardFavoriteButtons = document.querySelectorAll(
    ".card-favorite-button"
);

// Add a click event to every favorite button.
cardFavoriteButtons.forEach(function (button) {

    button.addEventListener("click", async function () {

        // Get the recipe ID and CSRF token.
        const recipeId = button.dataset.recipeId;
        const csrfToken = button.dataset.csrfToken;

        // Prevent repeated clicks during the request.
        button.disabled = true;

        // Prepare data for PHP.
        const formData = new FormData();

        formData.append("recipe_id", recipeId);
        formData.append("csrf_token", csrfToken);

        try {

            // Send the request without refreshing the page.
            const response = await fetch("toggle_favorite.php", {
                method: "POST",
                body: formData
            });

            // Read the JSON response.
            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(
                    result.message || "Unable to update favorites."
                );
            }

            // Get the icon and text inside the clicked button.
            const icon = button.querySelector(".card-favorite-icon");
            const text = button.querySelector(".card-favorite-text");

            // Update the button appearance.
            if (result.is_favorite) {

                button.classList.add("saved");

                icon.textContent = "♥";
                text.textContent = "Saved";

                button.setAttribute("aria-pressed", "true");
                button.setAttribute("aria-label", "Remove from favorites");

            } else {

                button.classList.remove("saved");

                icon.textContent = "♡";
                text.textContent = "Save";

                button.setAttribute("aria-pressed", "false");
                button.setAttribute("aria-label", "Add to favorites");
            }

        } catch (error) {

            alert(error.message);

        } finally {

            // Allow clicking again.
            button.disabled = false;
        }
    });
});
