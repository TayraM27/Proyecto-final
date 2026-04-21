/* ------------------------------------------------------------------ */
/* Contador de vistas — global (llamado desde onclick en adopta.html) */
/* ------------------------------------------------------------------ */
var CLAVE_VISTAS = 'petfamily_vistas';

function obtenerVistas() {
    var datos = localStorage.getItem(CLAVE_VISTAS);
    return datos ? JSON.parse(datos) : {};
}

function contarVista(id) {
    var vistas = obtenerVistas();
    vistas[id] = (vistas[id] || 0) + 1;
    localStorage.setItem(CLAVE_VISTAS, JSON.stringify(vistas));
    return vistas[id];
}

function obtenerNumeroVistas(id) {
    var vistas = obtenerVistas();
    return vistas[id] || 0;
}

function registrarVistaFicha(id) {
    var total    = contarVista(id);
    var elemento = document.getElementById('vistas-' + id);
    if (elemento) {
        elemento.textContent = total;
    }
    return total;
}


/* ------------------------------------------------------------------ */
/* Sesion                                                              */
/* ------------------------------------------------------------------ */
var CLAVE_SESION = 'pf_session';

function estaLogueado() {
    return sessionStorage.getItem(CLAVE_SESION) !== null;
}

function obtenerUsuario() {
    var datos = sessionStorage.getItem(CLAVE_SESION);
    return datos ? JSON.parse(datos) : null;
}

function cerrarSesion(e) {
    if (e) e.preventDefault();
    fetch('../backend/api/auth/logout.php', { credentials: 'include' })
        .finally(function() {
            sessionStorage.removeItem(CLAVE_SESION);
            localStorage.removeItem('petfamily_favoritos');
            window.location.href = 'index.html';
        });
}

/* Fallback avatar cuando la foto no carga */
window._pfAvatarError = function(el) {
    var ini = el.getAttribute('data-iniciales') || '?';
    var sm  = el.classList.contains('user-avatar-sm');
    var d   = document.createElement('div');
    d.className = 'user-avatar user-avatar-iniciales' + (sm ? ' user-avatar-sm' : '');
    d.textContent = ini;
    if (el.parentNode) el.parentNode.replaceChild(d, el);
};

/* ------------------------------------------------------------------ */
/* Dropdown overlay — añadido a <body> para escapar del transform      */
/* ------------------------------------------------------------------ */
var _dropdownOverlay = null;
var _dropdownTrigger = null;

function _crearOverlay(menuHTML) {
    _cerrarOverlay();
    var el = document.createElement('div');
    el.id = 'pf-dropdown-overlay';
    el.className = 'user-dropdown';
    el.style.cssText = 'display:block;position:fixed;z-index:99999;visibility:hidden;';
    el.innerHTML = menuHTML;
    document.body.appendChild(el);
    _dropdownOverlay = el;
}

function _posicionarOverlay(triggerEl) {
    if (!_dropdownOverlay) return;
    requestAnimationFrame(function() {
        var rect = triggerEl.getBoundingClientRect();
        var dw   = _dropdownOverlay.offsetWidth;
        var left = rect.right - dw;
        if (left < 8) left = 8;
        if (left + dw > window.innerWidth - 8) left = window.innerWidth - dw - 8;
        _dropdownOverlay.style.top        = (rect.bottom + 6) + 'px';
        _dropdownOverlay.style.left       = left + 'px';
        _dropdownOverlay.style.visibility = 'visible';
    });
}

function _cerrarOverlay() {
    if (_dropdownOverlay) { _dropdownOverlay.remove(); _dropdownOverlay = null; }
    _dropdownTrigger = null;
}

/* ------------------------------------------------------------------ */
/* Construye el area de usuario en el header                           */
/* ------------------------------------------------------------------ */
function renderUserArea(usuario) {
    var contenedor = document.getElementById('user_container');
    var burger     = document.getElementById('user_burger');

    if (!usuario) {
        if (contenedor) {
            contenedor.innerHTML =
                '<a href="login.html"><img src="../img/iconosHeader/iconUser.png" alt="Usuario" width="30px"></a>' +
                '<a href="register.html" class="btn-register">Registrarse</a>';
        }
        if (burger) {
            burger.innerHTML =
                '<a href="login.html"><img src="../img/iconosHeader/iconUser.png" width="15px" alt="Usuario"></a>' +
                '<a href="register.html" class="btn-register">Registrarse</a>';
        }
        /* En pantallas muy pequeñas añadir login/registro al nav */
        var listMenuNoSes = document.getElementById('lista_menu');
        if (listMenuNoSes && !document.getElementById('nav-li-login')) {
            var liLogin = document.createElement('li');
            liLogin.id = 'nav-li-login';
            liLogin.className = 'nav-solo-movil';
            liLogin.innerHTML = '<a href="login.html"><i class="fa-solid fa-right-to-bracket me-2"></i>Iniciar sesión</a>';
            var liRegistro = document.createElement('li');
            liRegistro.id = 'nav-li-registro';
            liRegistro.className = 'nav-solo-movil';
            liRegistro.innerHTML = '<a href="register.html"><i class="fa-solid fa-user-plus me-2"></i>Registrarse</a>';
            listMenuNoSes.appendChild(liLogin);
            listMenuNoSes.appendChild(liRegistro);
        }
        return;
    }

    var nombre  = usuario.nombre || usuario.username || 'Usuario';
    var esAdmin = usuario.rol === 'admin';

    var partes   = nombre.trim().split(' ');
    var iniciales = (partes.length >= 2
        ? partes[0][0] + partes[1][0]
        : nombre.substring(0, 2)).toUpperCase();

    var avatarInner;
    if (usuario.foto_perfil) {
        var fotoSrc = (usuario.foto_perfil.startsWith('http') ? '' : '../backend/api/auth/') + usuario.foto_perfil;
        avatarInner =
            '<img src="' + fotoSrc + '" class="user-avatar"' +
            ' referrerpolicy="no-referrer"' +
            ' data-iniciales="' + iniciales + '"' +
            ' alt="' + nombre + '"' +
            ' onerror="window._pfAvatarError(this)">';
    } else {
        avatarInner = '<div class="user-avatar user-avatar-iniciales">' + iniciales + '</div>';
    }

    var avatarBadge =
        '<div class="user-avatar-wrapper">' +
            avatarInner +
            '<span class="user-notif-badge" id="user-notif-badge" hidden></span>' +
        '</div>';

    var avatarBadgeSm = avatarBadge
        .replace('id="user-notif-badge"', 'id="user-notif-badge-b"')
        .replace('class="user-avatar"', 'class="user-avatar user-avatar-sm"');

    var opcionAdmin = esAdmin
        ? '<a href="../admin/dashboard.html" class="user-dropdown-item"><i class="fa-solid fa-shield-halved"></i> Panel admin</a>'
        : '';

    var menuHTML =
        '<div class="user-dropdown-header" onclick="window.location.href=\'perfil.html\'" title="Ver mi perfil">' +
            '<div class="user-dropdown-header-info">' +
                '<strong>' + nombre + '</strong>' +
                '<small>@' + (usuario.username || '') + '</small>' +
            '</div>' +
        '</div>' +
        '<div class="user-dropdown-body">' +
            '<a href="perfil.html" class="user-dropdown-item"><i class="fa-solid fa-user"></i> Mi perfil</a>' +
            '<a href="perfil.html?tab=favoritos" class="user-dropdown-item"><i class="fa-solid fa-heart"></i> Mis favoritos</a>' +
            '<a href="perfil.html?tab=apadrinamientos" class="user-dropdown-item"><i class="fa-solid fa-star"></i> Mis apadrinamientos</a>' +
            '<a href="perfil.html?tab=notificaciones" class="user-dropdown-item"><i class="fa-solid fa-bell"></i> Notificaciones</a>' +
            opcionAdmin +
            '<hr class="user-dropdown-sep">' +
            '<a href="#" class="user-dropdown-item user-dropdown-logout" onclick="cerrarSesion(event)"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión</a>' +
        '</div>';

    function abrirDropdown(triggerBtn) {
        if (_dropdownOverlay) { _cerrarOverlay(); return; }
        _dropdownTrigger = triggerBtn;
        _crearOverlay(menuHTML);
        _posicionarOverlay(triggerBtn);
    }

    if (contenedor) {
        contenedor.innerHTML =
            '<button class="user-dropdown-trigger" id="user-dropdown-trigger" title="Mi cuenta">' +
                avatarBadge + '</button>';
        document.getElementById('user-dropdown-trigger').addEventListener('click', function(e) {
            e.stopPropagation(); abrirDropdown(this);
        });
    }

    if (burger) {
        burger.innerHTML =
            '<button class="user-dropdown-trigger" id="user-dropdown-trigger-b" title="Mi cuenta">' +
                avatarBadgeSm + '</button>';
        document.getElementById('user-dropdown-trigger-b').addEventListener('click', function(e) {
            e.stopPropagation(); abrirDropdown(this);
        });
    }

    cargarNotificaciones();

    /* En pantallas muy pequeñas (#user_burger oculto) añadir accesos
       rápidos al menú hamburguesa para que el usuario pueda navegar */
    var listMenu = document.getElementById('lista_menu');
    if (listMenu && !document.getElementById('nav-li-perfil')) {
        var liPerfil = document.createElement('li');
        liPerfil.id = 'nav-li-perfil';
        liPerfil.className = 'nav-solo-movil';
        liPerfil.innerHTML = '<a href="perfil.html"><i class="fa-solid fa-user me-2"></i>Mi perfil</a>';

        var liSalir = document.createElement('li');
        liSalir.id = 'nav-li-salir';
        liSalir.className = 'nav-solo-movil';
        liSalir.innerHTML = '<a href="#" onclick="cerrarSesion(event)"><i class="fa-solid fa-right-from-bracket me-2"></i>Cerrar sesión</a>';

        listMenu.appendChild(liPerfil);
        listMenu.appendChild(liSalir);
    }
}


/* Carga notificaciones y muestra badge en el avatar */
function cargarNotificaciones() {
    fetch('../php/get_notificaciones.php', { credentials: 'include' })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success || !data.total || data.total < 1) return;
            /* No mostrar badge si es admin */
            var sesion = sessionStorage.getItem('pf_session');
            if (sesion) {
                try { if (JSON.parse(sesion).rol === 'admin') return; } catch(e) {}
            }
            var texto = data.total > 9 ? '9+' : String(data.total);
            ['user-notif-badge', 'user-notif-badge-b'].forEach(function(id) {
                var el = document.getElementById(id);
                if (el) {
                    el.textContent = texto;
                    el.removeAttribute('hidden');
                }
            });
        })
        .catch(function() {});
}

/* Sincroniza favoritos desde la BD al localStorage para que los corazones
   se marquen correctamente en adopta.html y fichaAnimal.html */
function sincronizarFavoritos() {
    fetch('../backend/mascotas/favoritos.php', { credentials: 'include' })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.ok) return;
            var ids = (data.favoritos || []).map(function(m) { return String(m.idMascota); });
            localStorage.setItem('petfamily_favoritos', JSON.stringify(ids));
            /* Marcar corazones visibles en la página actual */
            document.querySelectorAll('.btn-fav[data-id]').forEach(function(btn) {
                var id = btn.dataset.id;
                var activo = ids.indexOf(id) !== -1;
                btn.classList.toggle('activo', activo);
                var ico = btn.querySelector('i');
                if (ico) ico.className = activo ? 'fa-solid fa-heart' : 'fa-regular fa-heart';
            });
            /* fichaAnimal: actualizar botón específico */
            var btnFicha = document.getElementById('btn-fav-ficha');
            if (btnFicha) {
                var params = new URLSearchParams(window.location.search);
                var idFicha = params.get('id');
                if (idFicha && ids.indexOf(String(idFicha)) !== -1) {
                    btnFicha.classList.add('activo');
                    btnFicha.innerHTML = '<i class="fa-solid fa-heart me-1"></i> Guardado en favoritos';
                }
            }
        })
        .catch(function() {});
}

/* Inicializa el area de usuario — siempre verifica con el servidor */
function inicializarUserArea() {
    /* Render rápido desde sessionStorage mientras llega la respuesta */
    var sesionGuardada = sessionStorage.getItem(CLAVE_SESION);
    if (sesionGuardada) {
        renderUserArea(JSON.parse(sesionGuardada));
    }

    /* Siempre verificar con sesion.php — sessionStorage no es fuente de verdad */
    fetch('../backend/api/auth/sesion.php', { credentials: 'include' })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.ok && data.logueado) {
                sessionStorage.setItem(CLAVE_SESION, JSON.stringify(data.usuario));
                renderUserArea(data.usuario);
                sincronizarFavoritos();
                document.dispatchEvent(new CustomEvent('pf:sesion', { detail: data.usuario }));
            } else {
                /* Sesión PHP no activa — limpiar y mostrar estado no logueado */
                sessionStorage.removeItem(CLAVE_SESION);
                localStorage.removeItem('petfamily_favoritos');
                renderUserArea(null);
                document.dispatchEvent(new CustomEvent('pf:sesion', { detail: null }));
            }
        })
        .catch(function() {
            /* Sin conexión — despachar con lo que hay en sessionStorage */
            var s = sessionStorage.getItem(CLAVE_SESION);
            document.dispatchEvent(new CustomEvent('pf:sesion', { detail: s ? JSON.parse(s) : null }));
        });
}


document.addEventListener('DOMContentLoaded', function() {
    cargarProtectorasDinamicasEnDona();

    // ----------------------------------------------------------------
    // User area
    // ----------------------------------------------------------------
    inicializarUserArea();

    /* Cerrar dropdown al hacer click fuera */
    document.addEventListener('click', function(e) {
        if (_dropdownOverlay && !_dropdownOverlay.contains(e.target)) {
            _cerrarOverlay();
        }
        document.querySelectorAll('.user-dropdown-wrapper.abierto').forEach(function(w) {
            w.classList.remove('abierto');
        });
    });

    /* Reposicionar overlay al hacer scroll o resize */
    window.addEventListener('scroll', function() {
        if (_dropdownOverlay) {
            var trigger = document.getElementById('user-dropdown-trigger') || document.getElementById('user-dropdown-trigger-b');
            if (trigger) _posicionarOverlay(trigger);
            else _cerrarOverlay();
        }
    }, { passive: true });

    window.addEventListener('resize', function() {
        if (_dropdownOverlay) _cerrarOverlay();
    });

    // ----------------------------------------------------------------
    // Header hide on scroll
    // ----------------------------------------------------------------
    var header         = document.querySelector('header');
    var scrollAnterior = 0;
    var UMBRAL_SCROLL  = 80;

    window.addEventListener('scroll', function() {
        var scrollActual = window.scrollY;

        if (scrollActual < UMBRAL_SCROLL) {
            header.classList.remove('header-oculto');
            return;
        }

        if (scrollActual > scrollAnterior) {
            header.classList.add('header-oculto');
        } else {
            header.classList.remove('header-oculto');
        }

        scrollAnterior = scrollActual;
    }, { passive: true });

    // ----------------------------------------------------------------
    // Hover del logo
    // ----------------------------------------------------------------
    function configurarHoverLogo(elementoLogo, imagenLogo) {
        elementoLogo.addEventListener('mouseout', function() {
            imagenLogo.src = "../img/iconosHeader/gato_logo.png";
            imagenLogo.style.transform = "rotate(0deg)";
            elementoLogo.style.transform = "scale(1)";
            elementoLogo.style.transition = "transform .4s";
            imagenLogo.style.transition = "transform .4s";
        });

        elementoLogo.addEventListener('mouseover', function() {
            imagenLogo.src = "../img/iconosHeader/cat_logo_yellow.png";
            imagenLogo.style.transform = "rotate(20deg)";
            elementoLogo.style.transform = "scale(1.1)";
            elementoLogo.style.transition = "transform .4s";
            elementoLogo.style.transformOrigin = "center center";
        });
    }

    var logoImagenXL = document.getElementById('logoHeaderXL');
    var logoXL       = document.getElementById('logoXL');
    if (logoXL && logoImagenXL) {
        configurarHoverLogo(logoXL, logoImagenXL);
    }

    var logoImagenMD = document.getElementById('logoHeaderMD');
    var logoMD       = document.getElementById('logoMD');
    if (logoMD && logoImagenMD) {
        configurarHoverLogo(logoMD, logoImagenMD);
    }

    // ----------------------------------------------------------------
    // Menu burger
    // ----------------------------------------------------------------
    var botonBurger  = document.getElementById('btn_burger');
    var menu         = document.getElementById('menu');
    var menuColabora = document.querySelector('.submenuColabora');

    if (botonBurger && menu) {
        botonBurger.addEventListener('click', function() {
            var estaOculto = menu.classList.contains('d-none');
            if (estaOculto) {
                menu.classList.remove('d-none');
                menu.classList.add('d-block');
            } else {
                menu.classList.remove('d-block');
                menu.classList.add('d-none');
                if (menuColabora) {
                    menuColabora.classList.remove('d-block');
                    menuColabora.classList.add('d-none');
                }
            }
        });
    }

    // ----------------------------------------------------------------
    // Submenu Colabora
    // ----------------------------------------------------------------
    var enlaceColabora = document.querySelector('a.colabora');
    var itemColabora   = enlaceColabora ? enlaceColabora.closest('li') : null;

    function esMobil() {
        return window.innerWidth <= 991;
    }

    if (itemColabora && menuColabora && enlaceColabora) {

        itemColabora.addEventListener('mouseenter', function() {
            if (!esMobil()) {
                menuColabora.classList.remove('d-none');
                menuColabora.classList.add('d-flex');
            }
        });

        itemColabora.addEventListener('mouseleave', function() {
            if (!esMobil()) {
                menuColabora.classList.remove('d-flex');
                menuColabora.classList.add('d-none');
            }
        });

        enlaceColabora.addEventListener('click', function(evento) {
            if (esMobil()) {
                evento.preventDefault();
                var estaAbierto = menuColabora.classList.contains('d-block');
                if (estaAbierto) {
                    menuColabora.classList.remove('d-block');
                    menuColabora.classList.add('d-none');
                } else {
                    menuColabora.classList.remove('d-none');
                    menuColabora.classList.add('d-block');
                }
            }
        });

        document.addEventListener('click', function(evento) {
            if (esMobil()) {
                if (!itemColabora.contains(evento.target)) {
                    menuColabora.classList.remove('d-block');
                    menuColabora.classList.add('d-none');
                }
            }
        });

        window.addEventListener('resize', function() {
            menuColabora.classList.remove('d-block', 'd-flex');
            menuColabora.classList.add('d-none');
        });
    }

    // ----------------------------------------------------------------
    // Favoritos
    // ----------------------------------------------------------------
    var CLAVE_FAVORITOS = 'petfamily_favoritos';

    function obtenerFavoritos() {
        var datos = localStorage.getItem(CLAVE_FAVORITOS);
        return datos ? JSON.parse(datos) : [];
    }

    function guardarFavoritos(lista) {
        localStorage.setItem(CLAVE_FAVORITOS, JSON.stringify(lista));
    }

    function esFavorito(id) {
        return obtenerFavoritos().indexOf(id) !== -1;
    }

    function actualizarBotonFavorito(id) {
        var boton = document.querySelector('.btn-fav[data-id="' + id + '"]');
        if (!boton) return;
        var icono = boton.querySelector('i');
        if (esFavorito(id)) {
            boton.classList.add('activo');
            icono.className = 'fa-solid fa-heart';
            boton.title = 'Quitar de favoritos';
        } else {
            boton.classList.remove('activo');
            icono.className = 'fa-regular fa-heart';
            boton.title = 'Agregar a favoritos';
        }
    }

    /* Inicializar botones de favoritos al cargar — solo si hay sesion */
    if (estaLogueado()) {
        var botonesFav = document.querySelectorAll('.btn-fav[data-id]');
        for (var i = 0; i < botonesFav.length; i++) {
            actualizarBotonFavorito(botonesFav[i].dataset.id);
        }
    } else {
        localStorage.removeItem('petfamily_favoritos');
    }

    // ----------------------------------------------------------------
    // Foro
    // ----------------------------------------------------------------
    if (document.getElementById('textareaPublicar')) {

        var logueadoForo = estaLogueado();

        /* Filtros de categorias */
        var btnsFiltro = document.querySelectorAll('.foro-filtro-btn');
        var posts      = document.querySelectorAll('.foro-post');

        btnsFiltro.forEach(function(btn) {
            btn.addEventListener('click', function() {
                btnsFiltro.forEach(function(b) { b.classList.remove('activo'); });
                btn.classList.add('activo');
                var cat = btn.getAttribute('data-cat');
                posts.forEach(function(post) {
                    post.style.display = (cat === 'todas' || post.getAttribute('data-cat') === cat)
                        ? '' : 'none';
                });
            });
        });

        /* Ver mas comentarios */
        document.querySelectorAll('.foro-ver-mas-com').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var seccion = btn.closest('.foro-comentarios-seccion');
                if (!seccion) return;
                var extra = seccion.querySelector('.foro-comentarios-extra');
                if (!extra) return;
                if (extra.classList.contains('d-none')) {
                    extra.classList.remove('d-none');
                    btn.innerHTML = '<i class="fa-solid fa-chevron-up me-1"></i>Ocultar comentarios';
                } else {
                    extra.classList.add('d-none');
                    var n = extra.querySelectorAll('.foro-comentario').length;
                    btn.innerHTML = '<i class="fa-solid fa-chevron-down me-1"></i>Ver ' + n + (n === 1 ? ' comentario restante' : ' comentarios restantes');
                }
            });
        });

        /* Boton publicar */
        var btnPublicar = document.getElementById('btnPublicar');
        var textarea    = document.getElementById('textareaPublicar');
        var avisoLogin  = document.getElementById('avisoLogin');
        var btnCerrar   = document.getElementById('btnCerrarAviso');

        btnPublicar.addEventListener('click', function() {
            var texto = textarea.value.trim();
            if (!texto) {
                textarea.focus();
                return;
            }
            if (!logueadoForo) {
                sessionStorage.setItem('foroTextoPendiente', texto);
                avisoLogin.classList.remove('d-none');
                avisoLogin.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });

        btnCerrar.addEventListener('click', function() {
            avisoLogin.classList.add('d-none');
        });

        /* Expandir textarea al escribir */
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });

        /* Inputs de comentario — aviso login */
        document.querySelectorAll('.foro-input-com').forEach(function(inp) {
            inp.addEventListener('focus', function() {
                if (!logueadoForo) {
                    this.blur();
                    avisoLogin.classList.remove('d-none');
                    avisoLogin.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
        });

        /* Adjuntos — aviso login */
        document.querySelectorAll('.foro-adj-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                if (!logueadoForo) {
                    avisoLogin.classList.remove('d-none');
                    avisoLogin.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
        });

        /* Reacciones y likes — aviso login */
        document.querySelectorAll('.foro-reaccion-btn, .foro-com-like').forEach(function(btn) {
            btn.addEventListener('click', function() {
                if (!logueadoForo) {
                    avisoLogin.classList.remove('d-none');
                }
            });
        });
    }

    // ----------------------------------------------------------------
    // Login
    // ----------------------------------------------------------------
    if (document.getElementById('formLogin')) {

        var formLogin      = document.getElementById('formLogin');
        var inputEmail     = document.getElementById('loginEmail');
        var inputPassword  = document.getElementById('loginPassword');
        var errorEmailL    = document.getElementById('errorEmail');
        var errorPasswordL = document.getElementById('errorPassword');
        var alertaLogin    = document.getElementById('alertaLogin');
        var btnLogin       = document.getElementById('btnLogin');
        var togglePwd      = document.getElementById('togglePwd');
        var iconoPwd       = document.getElementById('iconoPwd');
        var inputRol       = document.getElementById('inputRol');
        var panelUsuario   = document.getElementById('panelUsuario');
        var panelAdmin     = document.getElementById('panelAdmin');

        /* Selector de rol */
        document.querySelectorAll('.login-rol-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.login-rol-btn').forEach(function(b) { b.classList.remove('activo'); });
                btn.classList.add('activo');
                var rol = btn.getAttribute('data-rol');
                inputRol.value = rol;
                if (rol === 'admin') {
                    panelUsuario.classList.add('d-none');
                    panelAdmin.classList.remove('d-none');
                } else {
                    panelAdmin.classList.add('d-none');
                    panelUsuario.classList.remove('d-none');
                }
                alertaLogin.classList.add('d-none');
            });
        });

        /* Mostrar/ocultar contrasena */
        togglePwd.addEventListener('click', function() {
            var tipo = inputPassword.type === 'password' ? 'text' : 'password';
            inputPassword.type = tipo;
            iconoPwd.className = tipo === 'password' ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash';
        });

        function mostrarErrorL(input, span, msg) {
            input.classList.remove('input-ok');
            input.classList.add('input-error');
            span.textContent = msg;
        }

        function limpiarErrorL(input, span) {
            input.classList.remove('input-error');
            input.classList.add('input-ok');
            span.textContent = '';
        }

        function validarEmailL() {
            var valor = inputEmail.value.trim();
            var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!valor)             return mostrarErrorL(inputEmail, errorEmailL, 'El email es obligatorio.'), false;
            if (!regex.test(valor)) return mostrarErrorL(inputEmail, errorEmailL, 'Introduce un email valido.'), false;
            limpiarErrorL(inputEmail, errorEmailL);
            return true;
        }

        function validarPasswordL() {
            var valor = inputPassword.value;
            if (!valor)           return mostrarErrorL(inputPassword, errorPasswordL, 'La contrasena es obligatoria.'), false;
            if (valor.length < 6) return mostrarErrorL(inputPassword, errorPasswordL, 'Minimo 6 caracteres.'), false;
            limpiarErrorL(inputPassword, errorPasswordL);
            return true;
        }

        inputEmail.addEventListener('blur', validarEmailL);
        inputEmail.addEventListener('input', function() { if (this.classList.contains('input-error')) validarEmailL(); });
        inputPassword.addEventListener('blur', validarPasswordL);
        inputPassword.addEventListener('input', function() { if (this.classList.contains('input-error')) validarPasswordL(); });

        formLogin.addEventListener('submit', function(e) {
            e.preventDefault();
            var ok = validarEmailL() & validarPasswordL();
            if (!ok) return;

            btnLogin.disabled = true;
            btnLogin.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Iniciando sesion...';
            alertaLogin.classList.add('d-none');

            fetch('../backend/api/auth/login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    email:    inputEmail.value.trim(),
                    password: inputPassword.value,
                    rol:      inputRol.value || 'usuario'
                }),
                credentials: 'include'
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                btnLogin.disabled = false;
                btnLogin.innerHTML = '<i class="fa-solid fa-right-to-bracket me-2"></i>Iniciar sesion';
                if (data.ok) {
                    sessionStorage.setItem(CLAVE_SESION, JSON.stringify(data.usuario));
                    window.location.href = data.redirigir || 'index.html';
                } else {
                    document.getElementById('alertaLoginMsg').textContent = data.error || 'Email o contraseña incorrectos.';
                    alertaLogin.classList.remove('d-none');
                }
            })
            .catch(function() {
                btnLogin.disabled = false;
                btnLogin.innerHTML = '<i class="fa-solid fa-right-to-bracket me-2"></i>Iniciar sesion';
                document.getElementById('alertaLoginMsg').textContent = 'Error de conexion. Intenta de nuevo.';
                alertaLogin.classList.remove('d-none');
            });
        });
    }

    // ----------------------------------------------------------------
    // Registro
    // ----------------------------------------------------------------
    if (document.getElementById('formRegistro')) {

        var formRegistro         = document.getElementById('formRegistro');
        var inputNombre          = document.getElementById('regNombre');
        var inputUsername        = document.getElementById('regUsername');
        var inputEmailR          = document.getElementById('regEmail');
        var inputLocalidad       = document.getElementById('regLocalidad');
        var inputTelefono        = document.getElementById('regTelefono');
        var inputPasswordR       = document.getElementById('regPassword');
        var inputPasswordConfirm = document.getElementById('regPasswordConfirm');
        var inputTerminos        = document.getElementById('regTerminos');
        var inputFoto            = document.getElementById('fotoPerfil');
        var fotoIcono            = document.getElementById('fotoIcono');
        var fotoImg              = document.getElementById('fotoImg');
        var btnRegistro          = document.getElementById('btnRegistro');
        var alertaRegistro       = document.getElementById('alertaRegistro');

        /* Preview foto de perfil */
        document.querySelector('.reg-foto-label').addEventListener('click', function() {
            inputFoto.click();
        });

        inputFoto.addEventListener('change', function() {
            var archivo = this.files[0];
            if (!archivo) return;
            if (archivo.size > 2 * 1024 * 1024) {
                alert('La foto no puede superar 2 MB.');
                this.value = '';
                return;
            }
            var reader = new FileReader();
            reader.onload = function(e) {
                fotoIcono.classList.add('d-none');
                fotoImg.src = e.target.result;
                fotoImg.classList.remove('d-none');
            };
            reader.readAsDataURL(archivo);
        });

        /* Mostrar/ocultar contrasenas */
        document.getElementById('togglePwdReg').addEventListener('click', function() {
            var tipo = inputPasswordR.type === 'password' ? 'text' : 'password';
            inputPasswordR.type = tipo;
            document.getElementById('iconoPwdReg').className = tipo === 'password' ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash';
        });

        document.getElementById('togglePwdConfirm').addEventListener('click', function() {
            var tipo = inputPasswordConfirm.type === 'password' ? 'text' : 'password';
            inputPasswordConfirm.type = tipo;
            document.getElementById('iconoPwdConfirm').className = tipo === 'password' ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash';
        });

        /* Indicador fuerza contrasena */
        inputPasswordR.addEventListener('input', function() {
            var val   = this.value;
            var fill  = document.getElementById('pwdFill');
            var label = document.getElementById('pwdLabel');
            if (!val) { fill.style.width = '0%'; label.textContent = ''; return; }
            var puntos = 0;
            if (val.length >= 8)          puntos++;
            if (/[A-Z]/.test(val))        puntos++;
            if (/[0-9]/.test(val))        puntos++;
            if (/[^A-Za-z0-9]/.test(val)) puntos++;
            if (puntos <= 1) {
                fill.style.width = '33%'; fill.style.backgroundColor = '#e74c3c';
                label.style.color = '#e74c3c'; label.textContent = 'Debil';
            } else if (puntos <= 2) {
                fill.style.width = '66%'; fill.style.backgroundColor = '#F8BA56';
                label.style.color = '#c87a00'; label.textContent = 'Media';
            } else {
                fill.style.width = '100%'; fill.style.backgroundColor = '#2e7d32';
                label.style.color = '#2e7d32'; label.textContent = 'Fuerte';
            }
            validarPasswordR();
            if (inputPasswordConfirm.value) validarPasswordConfirm();
        });

        function mostrarErrorR(input, span, msg) {
            if (input) { input.classList.remove('input-ok'); input.classList.add('input-error'); }
            span.textContent = msg;
        }

        function limpiarErrorR(input, span) {
            if (input) { input.classList.remove('input-error'); input.classList.add('input-ok'); }
            span.textContent = '';
        }

        function validarNombre() {
            var val = inputNombre.value.trim();
            if (!val)           return mostrarErrorR(inputNombre, document.getElementById('errorNombre'), 'El nombre es obligatorio.'), false;
            if (val.length < 2) return mostrarErrorR(inputNombre, document.getElementById('errorNombre'), 'Al menos 2 caracteres.'), false;
            limpiarErrorR(inputNombre, document.getElementById('errorNombre'));
            return true;
        }

        function validarUsername() {
            var val = inputUsername.value.trim();
            if (!val)           return mostrarErrorR(inputUsername, document.getElementById('errorUsername'), 'El nombre de usuario es obligatorio.'), false;
            if (val.length < 3) return mostrarErrorR(inputUsername, document.getElementById('errorUsername'), 'Minimo 3 caracteres.'), false;
            if (/\s/.test(val)) return mostrarErrorR(inputUsername, document.getElementById('errorUsername'), 'Sin espacios.'), false;
            limpiarErrorR(inputUsername, document.getElementById('errorUsername'));
            return true;
        }

        function validarEmailR() {
            var val   = inputEmailR.value.trim();
            var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!val)             return mostrarErrorR(inputEmailR, document.getElementById('errorEmail'), 'El email es obligatorio.'), false;
            if (!regex.test(val)) return mostrarErrorR(inputEmailR, document.getElementById('errorEmail'), 'Introduce un email valido.'), false;
            limpiarErrorR(inputEmailR, document.getElementById('errorEmail'));
            return true;
        }

        function validarLocalidad() {
            var val = inputLocalidad.value.trim();
            if (!val) return mostrarErrorR(inputLocalidad, document.getElementById('errorLocalidad'), 'La localidad es obligatoria.'), false;
            limpiarErrorR(inputLocalidad, document.getElementById('errorLocalidad'));
            return true;
        }

        function validarTelefono() {
            var val = inputTelefono.value.trim();
            if (!val) return true;
            var regex = /^[6-9]\d{8}$/;
            if (!regex.test(val.replace(/\s/g, ''))) return mostrarErrorR(inputTelefono, document.getElementById('errorTelefono'), 'Telefono no valido. Ej: 612 345 678'), false;
            limpiarErrorR(inputTelefono, document.getElementById('errorTelefono'));
            return true;
        }

        function validarPasswordR() {
            var val = inputPasswordR.value;
            if (!val)           return mostrarErrorR(inputPasswordR, document.getElementById('errorPassword'), 'La contrasena es obligatoria.'), false;
            if (val.length < 8) return mostrarErrorR(inputPasswordR, document.getElementById('errorPassword'), 'Minimo 8 caracteres.'), false;
            limpiarErrorR(inputPasswordR, document.getElementById('errorPassword'));
            return true;
        }

        function validarPasswordConfirm() {
            var val = inputPasswordConfirm.value;
            if (!val)                         return mostrarErrorR(inputPasswordConfirm, document.getElementById('errorPasswordConfirm'), 'Repite la contrasena.'), false;
            if (val !== inputPasswordR.value) return mostrarErrorR(inputPasswordConfirm, document.getElementById('errorPasswordConfirm'), 'Las contrasenas no coinciden.'), false;
            limpiarErrorR(inputPasswordConfirm, document.getElementById('errorPasswordConfirm'));
            return true;
        }

        function validarTerminos() {
            if (!inputTerminos.checked) {
                document.getElementById('errorTerminos').textContent = 'Debes aceptar los terminos para continuar.';
                return false;
            }
            document.getElementById('errorTerminos').textContent = '';
            return true;
        }

        /* Listeners blur/input */
        inputNombre.addEventListener('blur', validarNombre);
        inputNombre.addEventListener('input', function() { if (this.classList.contains('input-error')) validarNombre(); });
        inputUsername.addEventListener('blur', validarUsername);
        inputUsername.addEventListener('input', function() { if (this.classList.contains('input-error')) validarUsername(); });
        inputEmailR.addEventListener('blur', validarEmailR);
        inputEmailR.addEventListener('input', function() { if (this.classList.contains('input-error')) validarEmailR(); });
        inputLocalidad.addEventListener('blur', validarLocalidad);
        inputLocalidad.addEventListener('input', function() { if (this.classList.contains('input-error')) validarLocalidad(); });
        inputTelefono.addEventListener('blur', validarTelefono);
        inputTelefono.addEventListener('input', function() { if (this.classList.contains('input-error')) validarTelefono(); });
        inputPasswordConfirm.addEventListener('blur', validarPasswordConfirm);
        inputPasswordConfirm.addEventListener('input', function() { if (this.classList.contains('input-error')) validarPasswordConfirm(); });

        /* Submit */
        formRegistro.addEventListener('submit', function(e) {
            e.preventDefault();
            var ok = validarNombre()
                & validarUsername()
                & validarEmailR()
                & validarLocalidad()
                & validarTelefono()
                & validarPasswordR()
                & validarPasswordConfirm()
                & validarTerminos();

            if (!ok) {
                var primerError = formRegistro.querySelector('.input-error');
                if (primerError) primerError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }

            btnRegistro.disabled = true;
            btnRegistro.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Creando cuenta...';
            alertaRegistro.classList.add('d-none');

            setTimeout(function() {
                btnRegistro.disabled = false;
                btnRegistro.innerHTML = '<i class="fa-solid fa-user-plus me-2"></i>Crear cuenta gratis';
                alertaRegistro.classList.remove('d-none');
            }, 1200);
        });
    }

}); /* fin DOMContentLoaded */


/* ------------------------------------------------------------------ */
/* toggleFavorito — llamado desde onclick en adopta.html y fichaAnimal */
/* ------------------------------------------------------------------ */
function mostrarModalLoginFav() {
    var modal = document.getElementById('modalLoginFav');
    if (!modal) return;
    new bootstrap.Modal(modal).show();
}

function mostrarToastFav(msg, color) {
    var toast = document.getElementById('pf-toast-fav');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'pf-toast-fav';
        toast.style.cssText = 'position:fixed;bottom:1.5em;left:50%;transform:translateX(-50%);'
            + 'background:' + color + ';color:#fff;padding:0.65em 1.4em;border-radius:30px;'
            + 'font-family:Poppins,sans-serif;font-size:0.85rem;font-weight:600;'
            + 'box-shadow:0 4px 14px rgba(0,0,0,0.2);z-index:99999;transition:opacity 0.3s;';
        document.body.appendChild(toast);
    } else {
        toast.style.background = color;
        toast.style.opacity = '1';
    }
    toast.textContent = msg;
    clearTimeout(toast._t);
    toast._t = setTimeout(function() { toast.style.opacity = '0'; }, 2500);
}

function toggleFavorito(evento, id) {
    evento.stopPropagation();

    if (!estaLogueado()) {
        mostrarModalLoginFav();
        return;
    }

    fetch('../backend/mascotas/favoritos.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ idMascota: parseInt(id) })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (!data.ok) return;

        var CLAVE_FAVORITOS = 'petfamily_favoritos';
        var favoritos = JSON.parse(localStorage.getItem(CLAVE_FAVORITOS) || '[]');
        var eliminado = data.accion === 'eliminado';

        if (eliminado) {
            var pos = favoritos.indexOf(id);
            if (pos !== -1) favoritos.splice(pos, 1);
        } else {
            if (favoritos.indexOf(id) === -1) favoritos.push(id);
        }
        localStorage.setItem(CLAVE_FAVORITOS, JSON.stringify(favoritos));

        var boton = document.querySelector('.btn-fav[data-id="' + id + '"]');
        if (boton) {
            var icono = boton.querySelector('i');
            if (eliminado) {
                boton.classList.remove('activo');
                icono.className = 'fa-regular fa-heart';
                boton.title = 'Agregar a favoritos';
            } else {
                boton.classList.add('activo');
                icono.className = 'fa-solid fa-heart';
                boton.title = 'Quitar de favoritos';
            }
        }

        mostrarToastFav(
            eliminado ? '💔 Eliminado de favoritos' : '❤️ Guardado en favoritos',
            eliminado ? '#666' : '#1B358F'
        );
    })
    .catch(function() {
        mostrarToastFav('Error al guardar favorito', '#c0392b');
    });
}


/* ------------------------------------------------------------------ */
/* dona                                                                */
/* ------------------------------------------------------------------ */
var protectoras = {
    'prot-1': {
        nombre:   'Centro de Proteccion Animal de Gijon',
        telefono: '615 411 417',
        email:    'cproteccionanimalgijon@gmail.com',
        web:      null,
        teaming:  null,
        iban:     null
    },
    'prot-2': {
        nombre:   'Nortemascotas',
        telefono: '665 971 933',
        email:    'Mariasol.ferreyra2014@gmail.com',
        web:      null,
        teaming:  null,
        iban:     'ES39 0182 2800 1902 0163 9405 (BBVA)'
    },
    'prot-3': {
        nombre:   'MAS QUE CHUCHOS',
        telefono: null,
        email:    'info@masquechuchos.org',
        web:      null,
        teaming:  'https://www.teaming.net/masquechuchos/'
    },
    'prot-4': {
        nombre:   'Fundacion Protectora de Asturias',
        telefono: null,
        email:    'info@protectoradeasturias.org',
        web:      'http://www.protectoradeasturias.org/index.php/colabora/donaciones',
        teaming:  'https://www.teaming.net/fundacionprotectoradeanimalesasturias',
        iban:     'ES15 0081 5665 2400 0109 0516 (Sabadell)'
    },
    'prot-5': {
        nombre:   'Asociacion Felina La Esperanza',
        telefono: null,
        email:    'asociacionfelinalaesperanza@gmail.com',
        web:      null,
        teaming:  'https://www.teaming.net/asociacionfelinalaesperanza'
    }
};

/* Carga protectoras nuevas (id > 5) desde BD y las registra en el objeto protectoras */
function cargarProtectorasDinamicasEnDona() {
    fetch('../backend/api/protectoras/listar.php')
        .then(function(r){ return r.json(); })
        .then(function(data){
            if (!data.ok || !data.protectoras) return;
            var nuevas = data.protectoras.filter(function(p){ return parseInt(p.idProtectora) > 5; });
            if (!nuevas.length) return;

            /* Registrar en objeto protectoras para que buildDatosProt funcione */
            nuevas.forEach(function(p) {
                var key = 'prot-' + p.idProtectora;
                protectoras[key] = {
                    nombre:   p.nombre,
                    telefono: p.telefono || null,
                    email:    p.email    || null,
                    web:      p.web      || null,
                    teaming:  null,
                    iban:     null
                };
            });

            /* Inyectar cards en dona.html si existe el contenedor */
            var cont = document.getElementById('containerProtDinamicasDona');
            if (cont) {
                cont.innerHTML = nuevas.map(function(p) {
                    var id  = 'prot-' + p.idProtectora;
                    var logo = p.foto_logo
                        ? '<img class="logo-protectora" src="../' + p.foto_logo + '" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';" alt="Logo ' + p.nombre + '">'
                        : '';
                    return '<label class="card-protectora" id="' + id + '" onclick="toggleProtectora('' + id + '')">'
                        + '<input type="checkbox" name="protectora" value="' + id + '">'
                        + logo
                        + '<div class="icono-prot-fb">🐾</div>'
                        + '<div style="flex:1;">'
                        + '<p class="protectora-nombre">' + p.nombre + '</p>'
                        + (p.localidad ? '<p class="protectora-lugar"><i class="fa-solid fa-location-dot"></i> ' + p.localidad + '</p>' : '')
                        + (p.descripcion ? '<p class="descripcion-protectora">' + p.descripcion + '</p>' : '')
                        + '</div></label>';
                }).join('');
            }

            /* Inyectar en protectoras.html si existe el grid */
            var gridProt = document.getElementById('gridProtectorasDinamicas');
            if (gridProt) {
                gridProt.innerHTML = nuevas.map(function(p) {
                    var logo = p.foto_logo
                        ? '<img src="../' + p.foto_logo + '" alt="Logo ' + p.nombre + '" onerror="this.style.display='none';this.parentElement.innerHTML='<span class=\'prot-logo-emoji\'>🐾</span>'">'
                        : '<span class="prot-logo-emoji">🐾</span>';
                    return '<div class="col-md-6 col-lg-4 prot-item" data-especie="perros gatos" data-nombre="' + p.nombre + ' ' + (p.localidad||'') + '" data-teaming="no">'
                        + '<div class="card-protectora-pag">'
                        + '<div class="prot-franja"></div>'
                        + '<div class="prot-header">'
                        + '<div class="prot-logo-container">' + logo + '</div>'
                        + '<div style="flex:1;min-width:0;">'
                        + '<p class="prot-nombre">' + p.nombre + '</p>'
                        + (p.localidad ? '<p class="prot-lugar"><i class="fa-solid fa-location-dot"></i> ' + p.localidad + '</p>' : '')
                        + '</div>'
                        + (p.verificada ? '<span class="prot-badge-anos">✓ Verificada</span>' : '')
                        + '</div>'
                        + (p.descripcion ? '<div class="prot-desc"><p>' + p.descripcion + '</p></div>' : '')
                        + '<div class="prot-contacto">'
                        + (p.email    ? '<div class="prot-dato"><i class="fa-solid fa-envelope"></i><a href="mailto:' + p.email + '">' + p.email + '</a></div>' : '')
                        + (p.telefono ? '<div class="prot-dato"><i class="fa-solid fa-phone"></i><a href="tel:' + p.telefono + '">' + p.telefono + '</a></div>' : '')
                        + (p.web      ? '<div class="prot-dato"><i class="fa-solid fa-globe"></i><a href="' + p.web + '" target="_blank" rel="noopener">' + p.web + '</a></div>' : '')
                        + '</div>'
                        + '<div class="prot-acciones">'
                        + '<button class="btn-prot-donar" onclick="abrirModalDonarProtDinamica(' + p.idProtectora + ')"><i class="fa-solid fa-hand-holding-heart me-1"></i> Donar</button>'
                        + (p.web ? '<a href="' + p.web + '" target="_blank" rel="noopener" class="btn-prot-ext"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Web</a>' : '')
                        + '</div>'
                        + '</div></div>';
                }).join('');
                if (typeof filtrarProtectoras === 'function') filtrarProtectoras();
            }
        })
        .catch(function(){});
}

/* Modal donar para protectoras dinámicas en protectoras.html */
function abrirModalDonarProtDinamica(idProtectora) {
    var key = 'prot-' + idProtectora;
    var p   = protectoras[key];
    if (!p) return;
    var cont = document.getElementById('modal-datos-prot-pag');
    if (cont) cont.innerHTML = buildDatosProt(p);
    var modal = document.getElementById('modalDonarProt');
    if (modal) new bootstrap.Modal(modal).show();
}

function toggleProtectora(id) {
    var card     = document.getElementById(id);
    var checkbox = card.querySelector('input[type="checkbox"]');
    card.classList.toggle('seleccionada', checkbox.checked);
}

function copiarTexto(btn, texto) {
    var icono = btn.querySelector('i');
    function feedback() {
        icono.className = 'fa-solid fa-check';
        setTimeout(function() { icono.className = 'fa-regular fa-copy'; }, 2000);
    }
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(texto).then(feedback);
    } else {
        var el = document.createElement('textarea');
        el.value = texto; el.style.position = 'fixed'; el.style.opacity = '0';
        document.body.appendChild(el); el.select();
        document.execCommand('copy'); document.body.removeChild(el);
        feedback();
    }
}

function copiarIban(btn, iban) {
    var texto = iban.replace(/\s*\([^)]*\)/, '').trim();
    var icono = btn.querySelector('i');
    function feedback() {
        icono.className = 'fa-solid fa-check';
        setTimeout(function() { icono.className = 'fa-regular fa-copy'; }, 2000);
    }
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(texto).then(feedback);
    } else {
        var el = document.createElement('textarea');
        el.value = texto;
        el.style.position = 'fixed';
        el.style.opacity = '0';
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
        feedback();
    }
}

function buildDatosProt(p) {
    var html = '<div class="modal-prot-bloque">';

    html += '<div style="display:flex;align-items:center;gap:0.5em;'
          + 'margin-bottom:0.7em;padding-bottom:0.7em;border-bottom:2px solid #1B358F;">'
          + '<i class="fa-solid fa-building-shield" style="color:#1B358F;"></i>'
          + '<strong style="font-size:0.97rem;color:#1B358F;letter-spacing:0.01em;">' + p.nombre + '</strong>'
          + '</div>';

    if (!p.web) {
        if (p.teaming) {
            html += '<div class="aviso-teaming" style="margin-bottom:0.8em;">'
                  + '<i class="fa-solid fa-mug-hot" style="color:#1B358F;margin-right:0.3em;"></i>'
                  + 'Esta protectora no dispone de p&aacute;gina oficial de donaciones, pero puedes '
                  + 'apoyarles con <strong>1&nbsp;&euro;/mes</strong> a trav&eacute;s de '
                  + '<strong>Teaming</strong>, una plataforma de microdonaciones continuadas.'
                  + '</div>';
        } else {
            html += '<div class="aviso-teaming" style="margin-bottom:0.8em;">'
                  + '<i class="fa-solid fa-circle-info"></i> '
                  + 'Esta protectora no dispone de p&aacute;gina oficial de donaciones. '
                  + 'Puedes contactarles directamente por tel&eacute;fono o email.'
                  + '</div>';
        }
    }

    if (p.telefono) {
        html += '<div class="modal-dato">'
              + '<i class="fa-solid fa-phone"></i>'
              + '<a href="tel:' + p.telefono.replace(/\s/g, '') + '">' + p.telefono + '</a>'
              + '</div>';
    }
    if (p.email) {
        html += '<div class="modal-dato">'
              + '<i class="fa-solid fa-envelope"></i>'
              + '<a href="mailto:' + p.email + '?subject=%C2%BFC%C3%B3mo%20puedo%20ayudar%3F">'
              + p.email
              + '</a>'
              + '</div>';
    }

    if (p.teaming) {
        html += '<a href="' + p.teaming + '" target="_blank" rel="noopener" class="btn-ir-web btn-ir-web-teaming" style="margin-top:0.6em;">'
              + '<i class="fa-solid fa-mug-hot me-2"></i>Colaborar con 1&nbsp;&euro;/mes en Teaming'
              + '</a>';
    }

    if (p.web) {
        html += '<a href="' + p.web + '" target="_blank" rel="noopener" class="btn-ir-web" style="margin-top:0.6em;">'
              + '<i class="fa-solid fa-arrow-up-right-from-square me-2"></i>Ir a la p&aacute;gina de donaciones'
              + '</a>';
        if (p.teaming) {
            html += '<div class="aviso-teaming" style="margin-top:0.6em;background:#f0f4ff;border-left-color:#1B358F;">'
                  + '<i class="fa-solid fa-mug-hot" style="color:#1B358F;"></i> '
                  + 'Tambi&eacute;n puedes colaborar con <strong>1&nbsp;&euro;/mes</strong> a trav&eacute;s de Teaming.'
                  + '</div>';
        }
    }

    if (p.iban) {
        var ibanEsc = p.iban.replace(/'/g, "\\'");
        html += '<div class="modal-dato" style="margin-top:0.8em;padding-top:0.6em;border-top:1px solid #f0f0f0;">'
              + '<i class="fa-solid fa-building-columns"></i>'
              + '<span>' + p.iban + '</span>'
              + '<button onclick="copiarIban(this,\'' + ibanEsc + '\')" title="Copiar IBAN" '
              + 'style="background:none;border:none;cursor:pointer;color:#1B358F;'
              + 'padding:0 0 0 0.5em;font-size:0.9rem;">'
              + '<i class="fa-regular fa-copy"></i>'
              + '</button>'
              + '</div>';
    }

    html += '</div>';
    return html;
}

function mostrarModalDona() {
    var checkboxes = document.querySelectorAll('input[type="checkbox"]:checked');
    var errorEl    = document.getElementById('errorSeleccion');
    if (checkboxes.length === 0) {
        if (errorEl) {
            errorEl.style.display = 'block';
            errorEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
        return;
    }
    if (errorEl) errorEl.style.display = 'none';
    var bloques = [];
    for (var i = 0; i < checkboxes.length; i++) {
        var id = checkboxes[i].closest('label').id;
        bloques.push(buildDatosProt(protectoras[id]));
    }
    document.getElementById('modal-datos-protectora').innerHTML = bloques.join(
        '<hr style="border:none;border-top:1px solid #e9ecef;margin:1.4em 0;">'
    );
    new bootstrap.Modal(document.getElementById('modalDonacion')).show();
}


/* ------------------------------------------------------------------ */
/* apadrina                                                            */
/* ------------------------------------------------------------------ */
var animalesApadrina = {
    leia: {
        nombre:     'Leia',
        protectora: 'Centro de Proteccion Animal de Gijon',
        telefono:   '615 411 417',
        email:      'cproteccionanimalgijon@gmail.com',
        web:        'https://www.albergaria.es/protectoras/centro-proteccion-animales-gijon/leia_4431.html',
        teaming:    null,
        iban:       null
    },
    dexter: {
        nombre:     'Dexter',
        protectora: 'Nortemascotas',
        telefono:   '665 971 933',
        email:      null,
        web:        'https://www.albergaria.es/animales-en-adopcion/dexter-4/',
        teaming:    null,
        iban:       'ES39 0182 2800 1902 0163 9405 (BBVA)'
    },
    bosque: {
        nombre:     'Bosque',
        protectora: 'Fundacion Protectora de Asturias',
        telefono:   null,
        email:      null,
        web:        'https://www.albergaria.es/animales-en-adopcion/bosque/',
        teaming:    'https://www.teaming.net/fundacionprotectoradeasturias',
        iban:       'ES15 0081 5665 2400 0109 0516 (Sabadell)'
    }
};

function abrirModalApadrina(idAnimal) {
    var animal = animalesApadrina[idAnimal];
    document.getElementById('modal-animal-nombre').textContent = animal.nombre;

    var html = '<p class="protectora-nombre mb-2">' + animal.protectora + '</p>';
    if (animal.telefono) html += '<div class="modal-dato"><i class="fa-solid fa-phone"></i><a href="tel:' + animal.telefono.replace(/\s/g, '') + '">' + animal.telefono + '</a></div>';
    if (animal.email)    html += '<div class="modal-dato"><i class="fa-solid fa-envelope"></i><a href="mailto:' + animal.email + '">' + animal.email + '</a></div>';
    if (animal.iban)     html += '<div class="modal-dato"><i class="fa-solid fa-building-columns"></i><span>' + animal.iban + '</span></div>';
    if (animal.web)      html += '<a href="' + animal.web + '" target="_blank" rel="noopener" class="btn-ir-web mt-2"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Ver ficha en la protectora</a>';
    if (animal.teaming)  html += '<a href="' + animal.teaming + '" target="_blank" rel="noopener" class="btn-ir-web btn-ir-web-teaming mt-2"><i class="fa-solid fa-mug-hot me-1"></i> Colaborar por Teaming</a>';

    document.getElementById('modal-datos-apadrina').innerHTML = html;
    new bootstrap.Modal(document.getElementById('modalApadrina')).show();
}


/* ------------------------------------------------------------------ */
/* carga dinamica de mascotas                                          */
/* ------------------------------------------------------------------ */
async function cargarMascotasAdopta() {
    const contenedor = document.getElementById('contenedor-mascotas');
    if (!contenedor) return;
    contenedor.innerHTML = '<div class="text-center w-100 py-5"><div class="spinner-border" role="status"></div></div>';
    try {
        const res  = await fetch('../php/get_mascotas.php');
        const json = await res.json();
        if (!json.success) throw new Error(json.error || 'Error cargando mascotas');
        const mascotas = json.data;
        if (!mascotas.length) {
            contenedor.innerHTML = '<div class="alert alert-info">No hay animales en adopción actualmente.</div>';
            return;
        }
        contenedor.innerHTML = mascotas.map(mascota => renderTarjetaMascota(mascota)).join('');
    } catch (e) {
        contenedor.innerHTML = '<div class="alert alert-danger">Error cargando animales: ' + e.message + '</div>';
    }
}

function renderTarjetaMascota(m) {

    var edad = '';
    if (m.fecha_nacimiento) {
        var nacimiento = new Date(m.fecha_nacimiento);
        var hoy        = new Date();
        var edadAnos   = hoy.getFullYear() - nacimiento.getFullYear();
        var mes        = hoy.getMonth() - nacimiento.getMonth();
        if (mes < 0) edadAnos--;
        edad = edadAnos;
    }

    var badgeHtml = '';
    if (m.urgencia === 'urgente') {
        badgeHtml = '<span class="badge bg-danger">Urgente</span>';
    } else if (m.urgencia === 'nuevo') {
        badgeHtml = '<span class="badge bg-info">Nuevo</span>';
    }

    var infoExtra = '';
    if (m.compatible_ninos)  infoExtra += '<span class="badge bg-light text-dark me-1"><i class="fa-solid fa-child"></i></span>';
    if (m.compatible_perros) infoExtra += '<span class="badge bg-light text-dark me-1"><i class="fa-solid fa-dog"></i></span>';
    if (m.compatible_gatos)  infoExtra += '<span class="badge bg-light text-dark me-1"><i class="fa-solid fa-cat"></i></span>';
    if (m.apto_piso)         infoExtra += '<span class="badge bg-light text-dark me-1"><i class="fa-solid fa-home"></i></span>';

    return `
    <div class="col-sm-12 col-md-6 col-lg-4 animalCard"
         data-especie="${m.especie}"
         data-ubicacion="${m.ubicacion || ''}"
         data-tamano="${m.tamano || ''}"
         data-urgencia="${m.urgencia || ''}"
         data-edad="${edad}"
         data-sexo="${m.sexo || ''}"
         data-color="${m.color || ''}"
         data-salud="${m.estado_salud || ''}">
        <div class="card h-100 shadow-sm">
            <div class="contenedor-imagen position-relative">
                <img src="${m.foto}" class="card-img-top" alt="${m.nombre}">
                ${badgeHtml}
                <button class="btn-fav" onclick="toggleFavorito(event, '${m.idMascota}')" title="Agregar a favoritos">
                    <i class="fa-regular fa-heart"></i>
                </button>
            </div>
            <div class="card-body d-flex flex-column">
                <div class="d-flex align-items-center mb-2">
                    <h5 class="card-title mb-0">${m.nombre}</h5>
                    <span class="ms-2 fs-6">
                        ${m.sexo === 'hembra' ? '<i class="fa-solid fa-venus" style="color:#E91E63;"></i>' : '<i class="fa-solid fa-mars" style="color:#2196F3;"></i>'}
                    </span>
                </div>
                <ul class="card-meta list-unstyled small text-secondary mb-2">
                    ${m.raza   ? '<li><i class="fa-solid fa-dog"></i> ' + m.raza + '</li>' : ''}
                    ${edad     ? '<li><i class="fa-solid fa-calendar"></i> ' + edad + ' años</li>' : ''}
                    ${m.tamano ? '<li><i class="fa-solid fa-expand"></i> ' + (m.tamano.charAt(0).toUpperCase() + m.tamano.slice(1)) + '</li>' : ''}
                </ul>
                ${m.ubicacion     ? '<div class="small text-muted mb-2"><i class="fa-solid fa-location-dot"></i> ' + m.ubicacion + '</div>' : ''}
                ${m.estado_adopcion ? '<div class="small text-muted mb-2"><i class="fa-solid fa-hourglass-end"></i> En adopción: ' + m.estado_adopcion.replace('_', ' ') + '</div>' : ''}
                ${infoExtra ? '<div class="mb-2">' + infoExtra + '</div>' : ''}
                <div class="contador-vistas small text-warning mt-2">
                    <i class="fa-solid fa-eye"></i>
                    <span>Vista <strong id="vistas-${m.idMascota}">0</strong> veces</span>
                </div>
                <a href="fichaAnimal.html?id=${m.idMascota}" class="btn btn-primary w-100 mt-auto" onclick="registrarVistaFicha('${m.idMascota}')">
                    Ver ficha
                </a>
            </div>
        </div>
    </div>
    `;
}

async function cargarMascotaFicha() {
    const params = new URLSearchParams(window.location.search);
    const id = params.get('id');
    if (!id) return;
    try {
        const res  = await fetch('../php/get_mascota.php?id=' + id);
        const json = await res.json();
        if (!json.success) throw new Error(json.error || 'No encontrada');
        const m = json.data;
        document.getElementById('animal-nombre').textContent    = m.nombre;
        document.getElementById('animal-subtitulo').textContent = (m.raza || '') + ' · ' + (m.sexo || '');
        document.getElementById('animal-descripcion').textContent = m.descripcion || '';
        document.getElementById('foto-principal').src = m.foto || '../img/mascotas/default.jpg';
    } catch (e) {
        document.querySelector('main').innerHTML = '<div class="alert alert-danger">Error: ' + e.message + '</div>';
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('contenedor-mascotas')) cargarMascotasAdopta();
        if (document.getElementById('animal-nombre')) cargarMascotaFicha();
    });
} else {
    if (document.getElementById('contenedor-mascotas')) cargarMascotasAdopta();
    if (document.getElementById('animal-nombre')) cargarMascotaFicha();
}

/*--------------------------------------------------------------------------------------------
compartir animal — función global usada en adopta, fichaAnimal, apadrina y acoge */
function compartirAnimal(id, nombre) {
    /* Construir URL limpia a la ficha */
    var base = window.location.href;
    var url;
    if (id) {
        var partes = base.split('/html/');
        url = partes.length > 1
            ? partes[0] + '/html/fichaAnimal.html?id=' + id
            : base.replace(/[^\/]*$/, '') + 'fichaAnimal.html?id=' + id;
    } else {
        url = base;
    }

    /* Copiar al portapapeles — textarea con position:fixed para máxima compatibilidad */
    try {
        var ta = document.createElement('textarea');
        ta.value = url;
        ta.style.cssText = 'position:fixed;top:0;left:0;opacity:0;';
        document.body.appendChild(ta);
        ta.focus();
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
    } catch(e) {}

    /* Toast con URL visible */
    var toast = document.getElementById('toastCompartir');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toastCompartir';
        toast.className = 'toast-compartir';
        document.body.appendChild(toast);
    }
    toast.textContent = '\uD83D\uDD17 Copiado: ' + url;
    toast.classList.add('visible');
    clearTimeout(window._toastTimer);
    window._toastTimer = setTimeout(function() {
        toast.classList.remove('visible');
    }, 5000);

    /* Share nativo en móvil (además del toast) */
    if (navigator.share) {
        navigator.share({
            title: (nombre || 'Animal') + ' — PetFamily',
            text: (nombre || 'Este animal') + ' busca hogar en PetFamily. \uD83D\uDC3E ',
            url: url
        }).catch(function() {});
    }
}