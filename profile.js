// profile.js - Profile page functions

document.addEventListener("DOMContentLoaded", () => {
  const user = JSON.parse(localStorage.getItem("user"));
  const loggedIn = localStorage.getItem("loggedIn");

  // Check if user is logged in
  if (loggedIn !== "true" || !user) {
    alert("Anda harus login terlebih dahulu!");
    window.location.href = "login.html";
    return;
  }

  // Display user information
  document.getElementById("profileName").textContent = user.nama || "-";
  document.getElementById("profileEmail").textContent = user.email || "-";
  document.getElementById("profileRole").textContent = user.role || "-";
});