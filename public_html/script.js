const navbar = document.querySelector('.nav-dark');
const hero = document.getElementById('hero'); // section dengan background gelap
const toggleBtn = document.getElementById('menu-toggle'); // tombol hamburger
const navMenu = document.getElementById('navbarNav'); // ul.navbar-nav

// === NAVBAR SCROLL BEHAVIOR ===
window.addEventListener('scroll', () => {
    if (window.scrollY > 10) {
        navbar.style.display = 'flex';
    } else {
        navbar.style.display = 'none';
    }

    // Deteksi apakah posisi scroll masih di dalam section gelap
    const heroRect = hero.getBoundingClientRect();
    const isInDarkSection = heroRect.bottom > 0;

    if (isInDarkSection) {
        document.body.classList.add('dark');
        document.body.classList.remove('light');
    } else {
        document.body.classList.remove('dark');
        document.body.classList.add('light');
    }
});

// === SET NAVBAR INITIAL STATE ===
document.addEventListener('DOMContentLoaded', () => {
    navbar.style.display = 'none';

    // Deteksi posisi awal scroll untuk set tema awal
    const heroRect = hero.getBoundingClientRect();
    const isInDarkSection = heroRect.bottom > 0;
    document.body.classList.toggle('dark', isInDarkSection);
    document.body.classList.toggle('light', !isInDarkSection);

    // === HAMBURGER MENU TOGGLE ===
    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            navMenu.classList.toggle('show');
        });
    }
});

// === NONAKTIFKAN KLIK KANAN ===
document.addEventListener('contextmenu', event => event.preventDefault());

// === TEMA ICON ===
const themeIcon = document.getElementById('themeIcon');
const icons = {
    light: `<!-- SVG Light -->`,
    dark: `<!-- SVG Dark -->`,
    auto: `<!-- SVG Auto -->`
};

function setIcon(theme) {
    themeIcon.innerHTML = icons[theme] || icons['auto'];
}






// script.js
function updateClock() {
    const now = new Date();
    let hours = now.getHours();
    let minutes = now.getMinutes();
    let seconds = now.getSeconds();

    // Menambahkan angka 0 di depan untuk angka satu digit
    hours = (hours < 10) ? '0' + hours : hours;
    minutes = (minutes < 10) ? '0' + minutes : minutes;
    seconds = (seconds < 10) ? '0' + seconds : seconds;

    const timeString = hours + ':' + minutes + ':' + seconds;

    // Menampilkan waktu pada elemen dengan id 'clock'
    document.getElementById('clock').textContent = timeString;
}

// Memperbarui jam setiap detik
setInterval(updateClock, 1000);

// Memanggil updateClock sekali agar jam muncul segera setelah halaman dimuat
updateClock();


 document.getElementById("year").textContent = new Date().getFullYear();