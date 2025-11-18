// auth.js - Fixed Authentication System with Working Dropdown

window.onload = function () {
  const loggedIn = localStorage.getItem("loggedIn");
  const user = JSON.parse(localStorage.getItem("user"));
  const nav = document.querySelector("nav ul");

  if (loggedIn === "true" && user) {
    nav.innerHTML = `
      <li><a href="web.html">Home</a></li>
      <li><a href="galeri.html">Galeri</a></li>
      <li><a href="about.html">About</a></li>
      <li class="user-dropdown">
        <a href="#" class="user-name">${user.nama} <span class="dropdown-arrow">▼</span></a>
        <div class="dropdown-menu">
          <a href="profile.html" class="dropdown-item">Profile</a>
          <a href="upload.html" class="dropdown-item">Upload Karya</a>
          <a href="#" id="logoutBtn" class="dropdown-item">Logout</a>
        </div>
      </li>
    `;

    // Get dropdown elements after they're created
    const userDropdown = document.querySelector('.user-dropdown');
    const userNameLink = document.querySelector('.user-name');
    const dropdownMenu = document.querySelector('.dropdown-menu');
    const dropdownItems = document.querySelectorAll('.dropdown-item');

    // Toggle dropdown when clicking user name
    userNameLink.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation(); // Prevent event bubbling
      dropdownMenu.classList.toggle('show');
    });

    // Handle dropdown item clicks
    dropdownItems.forEach(item => {
      item.addEventListener('click', (e) => {
        // Don't prevent default for profile and upload links
        if (item.getAttribute('href') !== '#') {
          // Let the browser handle the navigation
          dropdownMenu.classList.remove('show');
          return;
        }
        
        // Only prevent default for logout button
        e.preventDefault();
        e.stopPropagation();
        
        // Handle logout
        if (item.id === 'logoutBtn') {
          localStorage.removeItem("loggedIn");
          localStorage.removeItem("user");
          alert("Anda telah logout.");
          window.location.href = "login.html";
        }
      });
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
      if (!userDropdown.contains(e.target)) {
        dropdownMenu.classList.remove('show');
      }
    });

    // Prevent dropdown from closing when clicking inside the menu
    dropdownMenu.addEventListener('click', (e) => {
      e.stopPropagation();
    });

  }

  // Set active nav based on current page
  setActiveNav();
};

function setActiveNav() {
  const currentPage = window.location.pathname.split('/').pop();
  const navLinks = document.querySelectorAll('nav ul li a:not(.user-name):not(.dropdown-item)');
  
  navLinks.forEach(link => {
    link.classList.remove('active');
    if (link.getAttribute('href') === currentPage) {
      link.classList.add('active');
    }
  });
}