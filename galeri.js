document.addEventListener("DOMContentLoaded", () => {
  // Get search elements
  const searchInput = document.getElementById("searchInput");
  const categorySelect = document.getElementById("categorySelect");
  const searchBtn = document.getElementById("searchBtn");
  const cards = document.querySelectorAll(".card");

  // Filter gallery function
  function filterGaleri() {
    const searchText = searchInput ? searchInput.value.toLowerCase() : "";
    const categoryVal = categorySelect ? categorySelect.value : "";

    cards.forEach(card => {
      const title = card.querySelector("p").innerText.toLowerCase();
      const category = card.getAttribute("data-category") || "";

      const matchesSearch = title.includes(searchText);
      const matchesCategory = categoryVal === "" || categoryVal === category;

      if (matchesSearch && matchesCategory) {
        card.style.display = "block";
      } else {
        card.style.display = "none";
      }
    });
  }

  // Add event listeners if elements exist
  if (searchInput) {
    searchInput.addEventListener("input", filterGaleri);
  }

  if (categorySelect) {
    categorySelect.addEventListener("change", filterGaleri);
  }

  if (searchBtn) {
    searchBtn.addEventListener("click", filterGaleri);
  }

  // Initial filter
  filterGaleri();
});