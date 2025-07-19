const nav = document.getElementById('nav');

window.addEventListener('scroll', function(){
    scrollposition = window.scrollY;

    if (scrollposition >= 60){
        nav.classList.add('nav-dark');
    }else if (scrollposition <= 60){
        nav.classList.remove('nav-dark')
    }
})

document.addEventListener('contextmenu', event => event.preventDefault());

const toggleBtn = document.getElementById('themeToggle');
const dropdown = document.querySelector('.dropdown');
const menu = document.getElementById('themeMenu');
const themeIcon = document.getElementById('themeIcon');

// Ikon SVG dari Heroicons-style
const icons = {
    light: `
    <svg xmlns="http://www.w3.org/2000/svg" class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
      <circle cx="12" cy="12" r="5" stroke-width="2"/>
      <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" stroke-width="2"/>
    </svg>`,
  dark: `
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M9 18C6.5 18 4.375 17.125 2.625 15.375C0.875 13.625 0 11.5 0 9C0 6.5 0.875 4.375 2.625 2.625C4.375 0.875 6.5 0 9 0C9.23333 0 9.46267 0.00833343 9.688 0.0250001C9.91333 0.0416668 10.134 0.0666666 10.35 0.0999999C9.66667 0.583333 9.12067 1.21267 8.712 1.988C8.30333 2.76333 8.09933 3.60067 8.1 4.5C8.1 6 8.625 7.275 9.675 8.325C10.725 9.375 12 9.9 13.5 9.9C14.4167 9.9 15.2583 9.69567 16.025 9.287C16.7917 8.87833 17.4167 8.33267 17.9 7.65C17.9333 7.86667 17.9583 8.08733 17.975 8.312C17.9917 8.53667 18 8.766 18 9C18 11.5 17.125 13.625 15.375 15.375C13.625 17.125 11.5 18 9 18ZM9 16C10.4667 16 11.7833 15.5957 12.95 14.787C14.1167 13.9783 14.9667 12.9243 15.5 11.625C15.1667 11.7083 14.8333 11.775 14.5 11.825C14.1667 11.875 13.8333 11.9 13.5 11.9C11.45 11.9 9.704 11.179 8.262 9.737C6.82 8.295 6.09933 6.54933 6.1 4.5C6.1 4.16667 6.125 3.83333 6.175 3.5C6.225 3.16667 6.29167 2.83333 6.375 2.5C5.075 3.03333 4.02067 3.88333 3.212 5.05C2.40333 6.21667 1.99933 7.53333 2 9C2 10.9333 2.68333 12.5833 4.05 13.95C5.41667 15.3167 7.06667 16 9 16Z" fill="white"/>
</svg>`,
  auto: `
  <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
       xmlns="http://www.w3.org/2000/svg">
    <path d="M12.0015 2L14.6365 4.635H19.365V9.363L22 11.998L19.365 14.637V19.365H14.637L12.002 22L9.363 19.365H4.635V14.637L2 11.9985L4.635 9.3635V4.635H9.363L12.0015 2Z"
          stroke="white" stroke-width="2" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M13.5 8.5C13.5 12.5 11 13 8.5 13C8.5 15 11.75 17 14.5 15C17.25 13 15.5 8.5 13.5 8.5Z"
          stroke="white" stroke-width="2" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
  </svg>`

};

// Set icon sesuai tema
function setIcon(theme) {
    themeIcon.innerHTML = icons[theme] || icons['auto'];
}

// Terapkan tema dan simpan
function applyTheme(theme) {
    if (theme === 'auto') {
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        document.body.className = prefersDark ? 'dark' : 'light';
    } else {
        document.body.className = theme;
    }
    localStorage.setItem('theme', theme);
    setIcon(theme);
}

// Buka/tutup dropdown
toggleBtn.addEventListener('click', () => {
    dropdown.classList.toggle('open');
});

// Klik pilihan tema
menu.addEventListener('click', (e) => {
    const btn = e.target.closest('button');
    if (btn) {
        const theme = btn.getAttribute('data-theme');
        applyTheme(theme);
        dropdown.classList.remove('open');
    }
});

// Saat halaman dimuat
document.addEventListener('DOMContentLoaded', () => {
    const saved = localStorage.getItem('theme') || 'light';
    applyTheme(saved);
});




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