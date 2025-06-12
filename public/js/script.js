function togglePassword() {
    var passwordField = document.getElementById("password");
    passwordField.type = (passwordField.type === "password") ? "text" : "password";
}

// Theme toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('theme-toggle');
    const body = document.body;
    const themeIcon = themeToggle.querySelector('i');
    const navbar = document.querySelector('.navbar');
    const logo = document.getElementById('theme-logo');
    
    // Check for saved theme preference
    const savedTheme = localStorage.getItem('theme');
    
    // Apply saved theme if it exists
    if (savedTheme === 'light') {
        applyLightTheme();
    } else {
        applyDarkTheme();
    }
    
    // Toggle theme when button is clicked
    themeToggle.addEventListener('click', function() {
        if (body.classList.contains('light-theme')) {
            applyDarkTheme();
        } else {
            applyLightTheme();
        }
    });
    
    // Function to apply light theme
    function applyLightTheme() {
        body.classList.add('light-theme');
        if (navbar) navbar.classList.remove('navbar-dark');
        if (navbar) navbar.classList.add('navbar-light');
        themeIcon.classList.remove('fa-moon');
        themeIcon.classList.add('fa-sun');
        // Change logo to dark version
        if (logo) logo.src = "/public/images/logov2/Logosfnoir.png";
        // Save theme preference to localStorage
        localStorage.setItem('theme', 'light');
    }
    
    // Function to apply dark theme
    function applyDarkTheme() {
        body.classList.remove('light-theme');
        if (navbar) navbar.classList.remove('navbar-light');
        if (navbar) navbar.classList.add('navbar-dark');
        themeIcon.classList.remove('fa-sun');
        themeIcon.classList.add('fa-moon');
        // Change logo to light version
        if (logo) logo.src = "/public/images/logov2/Logosfblanc.png";
        // Save theme preference to localStorage
        localStorage.setItem('theme', 'dark');
    }
});
