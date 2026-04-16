<?php
session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login-page/login.html");
    exit();
}

$categoryResult = $conn->query("SELECT id, categoryName FROM recipecategory ORDER BY categoryName");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Lunchy | Add Recipe</title>
  <link rel="stylesheet" href="../DalalStyle.css" />
  <link rel="stylesheet" href="../style.css" />

  <style>
    .input-error,
    .select-error,
    .textarea-error,
    .file-upload-error {
      border: 2px solid #dc2626 !important;
      box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.12) !important;
    }

    .error-text {
      color: #dc2626;
      font-size: 12px;
      margin-top: 6px;
      font-weight: 700;
    }

    .form-error-top {
      display: none;
      margin-bottom: 16px;
      padding: 12px 14px;
      border-radius: 12px;
      background: #fff1f2;
      border: 1px solid #fecdd3;
      color: #b91c1c;
      font-weight: 800;
    }

    .file-upload.file-upload-error {
      border: 2px dashed #dc2626 !important;
    }
  </style>
</head>
<body>

  <header class="site-header">
    <div class="container header-inner">
      <a class="brand" href="../explore-page/explore.html">
        <img class="brand-logo" src="../media/logo.png" alt="Lunchy logo">
        <span class="brand-text">
          <span class="brand-name">Lunchy</span>
          <span class="brand-tagline">Pack smart. Eat better.</span>
        </span>
      </a>

      <nav class="nav">
        <a class="nav-link" href="../explore-page/explore.html">Explore</a>
        <a class="nav-link" href="../my_recipes-page/my-recipes.php">My Recipes</a>
        <a class="nav-link" href="../about-us-page/about-us.html">About Us</a>
      </nav>

      <div class="actions">
        <a class="btn btn-primary" href="../user-page/user.php">My Profile</a>
        <a class="btn btn-ghost" href="../home-page/index.html">Log Out</a>
      </div>
    </div>
  </header>

  <main class="container" style="padding: 26px 0 34px;">
    <h1 class="page-title">Add New Recipe</h1>
    <p class="page-subtitle">
      Fill in the recipe details. You can add multiple ingredients and steps.
    </p>

    <form class="form" id="addRecipeForm" action="insert_recipe.php" method="POST" enctype="multipart/form-data">
      <div class="form-error-top" id="formErrorTop">
        Please complete the highlighted fields before submitting.
      </div>

      <h2 class="form-title">Recipe Information</h2>

      <div class="form-grid">
        <div class="form-group full">
          <label class="label" for="recipeName">Recipe Name *</label>
          <input class="input" type="text" id="recipeName" name="name" />
          <div class="error-text" id="error-name"></div>
        </div>

        <div class="form-group">
          <label class="label" for="category">Category *</label>
          <select class="select" id="category" name="categoryID">
            <option value="">Select a category</option>
            <?php while ($row = $categoryResult->fetch_assoc()) { ?>
              <option value="<?php echo $row['id']; ?>">
                <?php echo htmlspecialchars($row['categoryName']); ?>
              </option>
            <?php } ?>
          </select>
          <div class="error-text" id="error-category"></div>
        </div>

        <div class="form-group">
          <label class="label" for="lunchType">Lunch Box Type *</label>
          <select class="select" id="lunchType" name="lunchBoxType">
            <option value="">Select a type</option>
            <option value="University">University</option>
            <option value="Work">Work</option>
            <option value="Kids">Kids</option>
            <option value="Snack">Snack</option>
          </select>
          <div class="error-text" id="error-type"></div>
        </div>

        <div class="form-group full">
          <label class="label" for="description">Description *</label>
          <textarea class="textarea" id="description" name="description"
            placeholder="Briefly describe the recipe and why it fits a lunch box..."></textarea>
          <div class="error-text" id="error-description"></div>
        </div>

        <div class="form-group full">
          <label class="label">Photo *</label>

          <div class="file-upload file-upload-photo" id="recipePhotoBox">
            <input class="file-input" type="file" id="photo" name="photo" accept="image/*" hidden />

            <div class="upload-content">
              <span class="upload-icon">📷</span>
              <p class="upload-text">
                Upload a meal photo <br>
                <span>Click to browse</span>
              </p>
              <p class="upload-filename" id="recipePhotoName">No file selected</p>
            </div>

            <div class="photo-preview" id="photoPreview" aria-label="Photo preview" hidden></div>
          </div>

          <div class="helper">Use a clear photo with good lighting.</div>
          <div class="error-text" id="error-photo"></div>
        </div>
      </div>

      <hr style="border: none; border-top: 1px solid var(--border); margin: 18px 0;">

      <h2 class="form-title">Ingredients *</h2>
      <div class="helper">Click “Add Ingredient” to add more items.</div>
      <div class="error-text" id="error-ingredients"></div>

      <div id="ingredientsContainer" style="margin-top: 12px;">
        <div class="form-group full ingredient-row">
          <label class="label" for="ingredient-1">Ingredient 1</label>
          <div class="form-grid">
            <div class="form-group">
              <input class="input ingredient-name" type="text" id="ingredient-1" name="ingredientName[]" placeholder="e.g., Whole wheat wrap" />
            </div>
            <div class="form-group">
              <input class="input ingredient-qty" type="text" id="ingredient-qty-1" name="ingredientQuantity[]" placeholder="e.g., 2 pieces" />
            </div>
          </div>
        </div>
      </div>

      <div class="form-actions">
        <button type="button" class="btn btn-soft" id="addIngredientBtn">+ Add Ingredient</button>
        <button type="button" class="btn btn-soft" id="removeIngredientBtn">− Remove Last</button>
      </div>

      <hr style="border: none; border-top: 1px solid var(--border); margin: 18px 0;">

      <h2 class="form-title">Instructions *</h2>
      <div class="helper">Click “Add Step” to add more steps.</div>
      <div class="error-text" id="error-steps"></div>

      <div id="stepsContainer" style="margin-top: 12px;">
        <div class="form-group full step-row">
          <label class="label" for="step-1">Step 1</label>
          <input class="input step-input" type="text" id="step-1" name="step[]" placeholder="e.g., Spread the sauce on the wrap" />
        </div>
      </div>

      <div class="form-actions">
        <button type="button" class="btn btn-soft" id="addStepBtn">+ Add Step</button>
        <button type="button" class="btn btn-soft" id="removeStepBtn">− Remove Last</button>
      </div>

      <hr style="border: none; border-top: 1px solid var(--border); margin: 18px 0;">

      <h2 class="form-title">Video / URL (optional)</h2>
      <div class="form-grid">
        <div class="form-group full">
          <label class="label" for="videoFile">Upload Video</label>
          <input class="input" type="file" id="videoFile" name="videoFile" accept="video/*" />
          <div class="helper">Upload a video file or use a video link below.</div>
        </div>

        <div class="form-group full">
          <label class="label" for="videoUrl">Video Link</label>
          <input class="input" type="url" id="videoUrl" name="videoURL" placeholder="https://..." />
          <div class="helper">Leave both empty if there is no video.</div>
          <div class="error-text" id="error-video"></div>
        </div>
      </div>

      <div class="form-actions" style="margin-top: 18px;">
        <button type="submit" class="btn btn-primary">Submit Recipe</button>
        <a class="btn btn-ghost" href="../my_recipes_page/my-recipes.php">Back to My Recipes</a>
      </div>
    </form>
  </main>

  <footer class="site-footer">
    <div class="container footer-inner">
      <span>&copy; 2026 Lunchy. All rights reserved.</span>
    </div>
  </footer>

  <script>
    let ingredientCount = 1;
    let stepCount = 1;

    const ingredientsContainer = document.getElementById("ingredientsContainer");
    const stepsContainer = document.getElementById("stepsContainer");
    const form = document.getElementById("addRecipeForm");
    const formErrorTop = document.getElementById("formErrorTop");

    function clearErrors() {
      document.querySelectorAll(".error-text").forEach(el => el.textContent = "");
      document.querySelectorAll(".input-error").forEach(el => el.classList.remove("input-error"));
      document.querySelectorAll(".select-error").forEach(el => el.classList.remove("select-error"));
      document.querySelectorAll(".textarea-error").forEach(el => el.classList.remove("textarea-error"));
      document.querySelectorAll(".file-upload-error").forEach(el => el.classList.remove("file-upload-error"));
      formErrorTop.style.display = "none";
    }

    function setFieldError(element, errorId, message, className = "input-error") {
      if (element) element.classList.add(className);
      const errorEl = document.getElementById(errorId);
      if (errorEl) errorEl.textContent = message;
    }

    document.getElementById("addIngredientBtn").addEventListener("click", () => {
      ingredientCount++;
      const wrapper = document.createElement("div");
      wrapper.className = "form-group full ingredient-row";
      wrapper.innerHTML = `
        <label class="label" for="ingredient-${ingredientCount}">Ingredient ${ingredientCount}</label>
        <div class="form-grid">
          <div class="form-group">
            <input class="input ingredient-name" type="text" id="ingredient-${ingredientCount}" name="ingredientName[]" placeholder="e.g., Lettuce, tomatoes..." />
          </div>
          <div class="form-group">
            <input class="input ingredient-qty" type="text" id="ingredient-qty-${ingredientCount}" name="ingredientQuantity[]" placeholder="e.g., 1 cup" />
          </div>
        </div>
      `;
      ingredientsContainer.appendChild(wrapper);
    });

    document.getElementById("removeIngredientBtn").addEventListener("click", () => {
      if (ingredientCount <= 1) return;
      ingredientsContainer.lastElementChild.remove();
      ingredientCount--;
    });

    document.getElementById("addStepBtn").addEventListener("click", () => {
      stepCount++;
      const wrapper = document.createElement("div");
      wrapper.className = "form-group full step-row";
      wrapper.innerHTML = `
        <label class="label" for="step-${stepCount}">Step ${stepCount}</label>
        <input class="input step-input" type="text" id="step-${stepCount}" name="step[]" placeholder="e.g., Pack it in a lunch box container" />
      `;
      stepsContainer.appendChild(wrapper);
    });

    document.getElementById("removeStepBtn").addEventListener("click", () => {
      if (stepCount <= 1) return;
      stepsContainer.lastElementChild.remove();
      stepCount--;
    });

    form.addEventListener("submit", (e) => {
      clearErrors();

      let isValid = true;

      const name = document.getElementById("recipeName");
      const category = document.getElementById("category");
      const lunchType = document.getElementById("lunchType");
      const description = document.getElementById("description");
      const photoInput = document.getElementById("photo");
      const photoBox = document.getElementById("recipePhotoBox");
      const videoFile = document.getElementById("videoFile").files.length;
      const videoUrl = document.getElementById("videoUrl");

      if (!name.value.trim()) {
        setFieldError(name, "error-name", "Recipe name is required.");
        isValid = false;
      }

      if (!category.value) {
        setFieldError(category, "error-category", "Please select a category.", "select-error");
        isValid = false;
      }

      if (!lunchType.value) {
        setFieldError(lunchType, "error-type", "Please select a lunch box type.", "select-error");
        isValid = false;
      }

      if (!description.value.trim()) {
        setFieldError(description, "error-description", "Description is required.", "textarea-error");
        isValid = false;
      }

      if (photoInput.files.length === 0) {
        photoBox.classList.add("file-upload-error");
        document.getElementById("error-photo").textContent = "Please upload a recipe photo.";
        isValid = false;
      }

      const ingredientNames = document.querySelectorAll('input[name="ingredientName[]"]');
      const ingredientQuantities = document.querySelectorAll('input[name="ingredientQuantity[]"]');

      let hasValidIngredient = false;

      for (let i = 0; i < ingredientNames.length; i++) {
        const nameVal = ingredientNames[i].value.trim();
        const qtyVal = ingredientQuantities[i].value.trim();

        if (nameVal !== "" || qtyVal !== "") {
          ingredientNames[i].classList.add("input-error");
          ingredientQuantities[i].classList.add("input-error");
        }

        if (nameVal !== "" && qtyVal !== "") {
          ingredientNames[i].classList.remove("input-error");
          ingredientQuantities[i].classList.remove("input-error");
          hasValidIngredient = true;
        }
      }

      if (!hasValidIngredient) {
        document.getElementById("error-ingredients").textContent = "Please add at least one complete ingredient.";
        isValid = false;
      }

      const steps = document.querySelectorAll('input[name="step[]"]');
      let hasValidStep = false;

      steps.forEach(step => {
        if (step.value.trim() !== "") {
          hasValidStep = true;
          step.classList.remove("input-error");
        } else {
          step.classList.add("input-error");
        }
      });

      if (!hasValidStep) {
        document.getElementById("error-steps").textContent = "Please add at least one instruction step.";
        isValid = false;
      }

      if (videoFile > 0 && videoUrl.value.trim() !== "") {
        setFieldError(videoUrl, "error-video", "Choose either a video file or a video link, not both.");
        document.getElementById("videoFile").classList.add("input-error");
        isValid = false;
      }

      if (!isValid) {
        e.preventDefault();
        formErrorTop.style.display = "block";
        const firstError = document.querySelector(".input-error, .select-error, .textarea-error, .file-upload-error");
        if (firstError) {
          firstError.scrollIntoView({ behavior: "smooth", block: "center" });
        }
      }
    });

    const recipePhotoBox = document.getElementById("recipePhotoBox");
    const recipePhotoInput = document.getElementById("photo");
    const recipePhotoName = document.getElementById("recipePhotoName");
    const photoPreview = document.getElementById("photoPreview");

    recipePhotoBox.addEventListener("click", () => {
      recipePhotoInput.click();
    });

    recipePhotoInput.addEventListener("change", () => {
      recipePhotoBox.classList.remove("file-upload-error");
      document.getElementById("error-photo").textContent = "";

      if (recipePhotoInput.files.length === 0) return;

      const file = recipePhotoInput.files[0];
      recipePhotoBox.classList.add("active");
      recipePhotoName.textContent = "Selected: " + file.name;

      const url = URL.createObjectURL(file);
      photoPreview.style.backgroundImage = `url('${url}')`;
      photoPreview.hidden = false;
    });
  </script>

</body>
</html>