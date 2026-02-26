// ============================================
// Hover del logo (XL y MD)
// ============================================
function setupLogoHover(logoElement, logoImage) {
    logoElement.addEventListener('mouseout', () => {
        logoImage.src = "../img/iconosHeader/gato_logo.png";
        logoImage.style.transform = "rotate(0deg)";
        logoElement.style.transform = "scale(1)";
        logoElement.style.transition = "transform .4s";
        logoImage.style.transition = "transform .4s";
    });

    logoElement.addEventListener('mouseover', () => {
        logoImage.src = "../img/iconosHeader/cat_logo_yellow.png";
        logoImage.style.transform = "rotate(20deg)";
        logoElement.style.transform = "scale(1.1)";
        logoElement.style.transition = "transform .4s";
        logoElement.style.transformOrigin = "center center";
    });
}

const logoHeaderXL = document.getElementById('logoHeaderXL');
const logoXL = document.getElementById('logoXL');
if (logoXL && logoHeaderXL) setupLogoHover(logoXL, logoHeaderXL);

const logoHeaderMD = document.getElementById('logoHeaderMD');
const logoMD = document.getElementById('logoMD');
if (logoMD && logoHeaderMD) setupLogoHover(logoMD, logoHeaderMD);


// ============================================
// Menu burger (movil/tablet)
// ============================================
const btnBurger = document.getElementById('btn_burger');
const menu = document.getElementById('menu');

btnBurger.addEventListener('click', () => {
    const isHidden = menu.classList.contains('d-none');
    if (isHidden) {
        menu.classList.remove('d-none');
        menu.classList.add('d-block');
    } else {
        menu.classList.remove('d-block');
        menu.classList.add('d-none');
        // Cerrar tambien el submenu al cerrar el menu burger
        colaboraMenu.classList.remove('d-block');
        colaboraMenu.classList.add('d-none');
    }
});

// Submenu "Colabora"
// El hover se gestiona sobre el <li> padre, no sobre el <a>,
// asi el submenu no se cierra al mover el raton hacia el.
const colaboraMenu = document.querySelector('.submenuColabora');
const colaboraLink = document.querySelector('a.colabora');
const colaboraLi = colaboraLink.closest('li');

function isMobile() {
    return window.innerWidth <= 991;
}

// --- Escritorio: hover sobre el <li> padre ---
colaboraLi.addEventListener('mouseenter', () => {
    if (!isMobile()) {
        colaboraMenu.classList.remove('d-none');
        colaboraMenu.classList.add('d-flex');
    }
});

colaboraLi.addEventListener('mouseleave', () => {
    if (!isMobile()) {
        colaboraMenu.classList.remove('d-flex');
        colaboraMenu.classList.add('d-none');
    }
});

// --- Movil: click sobre el enlace Colabora ---
colaboraLink.addEventListener('click', (e) => {
    if (isMobile()) {
        e.preventDefault();
        const isOpen = colaboraMenu.classList.contains('d-block');
        if (isOpen) {
            colaboraMenu.classList.remove('d-block');
            colaboraMenu.classList.add('d-none');
        } else {
            colaboraMenu.classList.remove('d-none');
            colaboraMenu.classList.add('d-block');
        }
    }
});

// Cerrar submenu al hacer click fuera (movil)
document.addEventListener('click', (e) => {
    if (isMobile()) {
        if (!colaboraLi.contains(e.target)) {
            colaboraMenu.classList.remove('d-block');
            colaboraMenu.classList.add('d-none');
        }
    }
});

// Limpiar estado al cambiar tamano de ventana
window.addEventListener('resize', () => {
    colaboraMenu.classList.remove('d-block', 'd-flex');
    colaboraMenu.classList.add('d-none');
});