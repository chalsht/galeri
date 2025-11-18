// login.js - Cleaned up authentication functions

// Register user function
function registerUser(event) {
  event.preventDefault();

  const nama = document.getElementById("nama").value;
  const email = document.getElementById("email").value;
  const password = document.getElementById("password").value;
  const role = document.getElementById("role").value;

  if (!nama || !email || !password || !role) {
    alert("Semua field wajib diisi!");
    return;
  }

  const userData = { nama, email, password, role };
  localStorage.setItem("user", JSON.stringify(userData));
  alert("Registrasi berhasil! Silakan login.");
  window.location.href = "login.html";
}

// Login user function
function loginUser(event) {
  event.preventDefault();

  const email = document.getElementById("email").value;
  const password = document.getElementById("password").value;

  const savedUser = JSON.parse(localStorage.getItem("user"));

  if (savedUser && savedUser.email === email && savedUser.password === password) {
    localStorage.setItem("loggedIn", "true");
    alert("Login berhasil!");
    window.location.href = "web.html";
  } else {
    alert("Email atau password salah!");
  }
}