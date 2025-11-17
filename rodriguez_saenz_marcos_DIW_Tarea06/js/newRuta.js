const tagsSelect = document.getElementById("tags");
const newTagInput = document.getElementById("new_tag_input");
const addTagButton = document.getElementById("add_tag_button");

if (tagsSelect && newTagInput && addTagButton) {
  const addTag = () => {
    const value = newTagInput.value.trim();
    if (!value) return;

    const exists = Array.from(tagsSelect.options).some(
      (opt) => opt.textContent.toLowerCase() === value.toLowerCase()
    );

    if (exists) {
      Array.from(tagsSelect.options).forEach((opt) => {
        if (opt.textContent.toLowerCase() === value.toLowerCase()) {
          opt.selected = true;
        }
      });
    } else {
      const opt = document.createElement("option");
      opt.value = value.toLowerCase().replace(/\s+/g, "_");
      opt.textContent = value;
      opt.selected = true;
      tagsSelect.appendChild(opt);
    }

    newTagInput.value = "";
    newTagInput.focus();
  };

  addTagButton.addEventListener("click", addTag);
  newTagInput.addEventListener("keydown", (e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      addTag();
    }
  });
}

// Previsualización de imagen
const imageInput = document.getElementById("route_image");
const imagePreview = document.getElementById("image_preview");
const imagePreviewWrapper = document.getElementById("image_preview_wrapper");

if (imageInput && imagePreview && imagePreviewWrapper) {
  imageInput.addEventListener("change", () => {
    const file = imageInput.files[0];

    if (!file) {
      imagePreview.src = "";
      imagePreviewWrapper.style.display = "none";
      return;
    }

    const url = URL.createObjectURL(file);
    imagePreview.src = url;
    imagePreviewWrapper.style.display = "block";
  });
}
