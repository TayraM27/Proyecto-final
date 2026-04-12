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


document.addEventListener('DOMContentLoaded', function() {

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

    // Inicializar botones de favoritos al cargar — solo si hay sesion
    if (estaLogueado()) {
        var botonesFav = document.querySelectorAll('.btn-fav[data-id]');
        for (var i = 0; i < botonesFav.length; i++) {
            actualizarBotonFavorito(botonesFav[i].dataset.id);
        }
    } else {
        // Sin sesion: limpiar posibles favoritos guardados previamente
        localStorage.removeItem('petfamily_favoritos');
    }

    // ----------------------------------------------------------------
    // Foro
    // ----------------------------------------------------------------
    if (document.getElementById('textareaPublicar')) {

        var estaLogueado = false;

        // Filtros de categorias
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

        // Ver mas comentarios
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

        // Boton publicar
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
            if (!estaLogueado) {
                sessionStorage.setItem('foroTextoPendiente', texto);
                avisoLogin.classList.remove('d-none');
                avisoLogin.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });

        btnCerrar.addEventListener('click', function() {
            avisoLogin.classList.add('d-none');
        });

        // Expandir textarea al escribir
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });

        // Inputs de comentario — aviso login
        document.querySelectorAll('.foro-input-com').forEach(function(inp) {
            inp.addEventListener('focus', function() {
                if (!estaLogueado) {
                    this.blur();
                    avisoLogin.classList.remove('d-none');
                    avisoLogin.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
        });

        // Adjuntos — aviso login
        document.querySelectorAll('.foro-adj-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                if (!estaLogueado) {
                    avisoLogin.classList.remove('d-none');
                    avisoLogin.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
        });

        // Reacciones y likes — aviso login
        document.querySelectorAll('.foro-reaccion-btn, .foro-com-like').forEach(function(btn) {
            btn.addEventListener('click', function() {
                if (!estaLogueado) {
                    avisoLogin.classList.remove('d-none');
                }
            });
        });
    }

    // ----------------------------------------------------------------
    // Login
    // ----------------------------------------------------------------
    if (document.getElementById('formLogin')) {

        var formLogin     = document.getElementById('formLogin');
        var inputEmail    = document.getElementById('loginEmail');
        var inputPassword = document.getElementById('loginPassword');
        var errorEmailL   = document.getElementById('errorEmail');
        var errorPasswordL= document.getElementById('errorPassword');
        var alertaLogin   = document.getElementById('alertaLogin');
        var btnLogin      = document.getElementById('btnLogin');
        var togglePwd     = document.getElementById('togglePwd');
        var iconoPwd      = document.getElementById('iconoPwd');
        var inputRol      = document.getElementById('inputRol');
        var panelUsuario  = document.getElementById('panelUsuario');
        var panelAdmin    = document.getElementById('panelAdmin');

        // Selector de rol
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

        // Mostrar/ocultar contrasena
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
            if (!valor)           return mostrarErrorL(inputEmail, errorEmailL, 'El email es obligatorio.'), false;
            if (!regex.test(valor)) return mostrarErrorL(inputEmail, errorEmailL, 'Introduce un email valido.'), false;
            limpiarErrorL(inputEmail, errorEmailL);
            return true;
        }

        function validarPasswordL() {
            var valor = inputPassword.value;
            if (!valor)          return mostrarErrorL(inputPassword, errorPasswordL, 'La contrasena es obligatoria.'), false;
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

            setTimeout(function() {
                btnLogin.disabled = false;
                btnLogin.innerHTML = '<i class="fa-solid fa-right-to-bracket me-2"></i>Iniciar sesion';
                alertaLogin.classList.remove('d-none');
            }, 1200);
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

        // Preview foto de perfil
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

        // Mostrar/ocultar contrasenas
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

        // Indicador fuerza contrasena
        inputPasswordR.addEventListener('input', function() {
            var val    = this.value;
            var fill   = document.getElementById('pwdFill');
            var label  = document.getElementById('pwdLabel');
            if (!val) { fill.style.width = '0%'; label.textContent = ''; return; }
            var puntos = 0;
            if (val.length >= 8)           puntos++;
            if (/[A-Z]/.test(val))         puntos++;
            if (/[0-9]/.test(val))         puntos++;
            if (/[^A-Za-z0-9]/.test(val))  puntos++;
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
            if (!val)         return mostrarErrorR(inputNombre, document.getElementById('errorNombre'), 'El nombre es obligatorio.'), false;
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
            if (!val)              return mostrarErrorR(inputEmailR, document.getElementById('errorEmail'), 'El email es obligatorio.'), false;
            if (!regex.test(val))  return mostrarErrorR(inputEmailR, document.getElementById('errorEmail'), 'Introduce un email valido.'), false;
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
            var val   = inputTelefono.value.trim();
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
            if (!val)                        return mostrarErrorR(inputPasswordConfirm, document.getElementById('errorPasswordConfirm'), 'Repite la contrasena.'), false;
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

        // Listeners blur/input
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

        // Submit
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

}); // fin DOMContentLoaded


/* ------------------------------------------------------------------ */
/* toggleFavorito — llamado desde onclick en adopta.html y fichaAnimal */
/* ------------------------------------------------------------------ */
function estaLogueado() {
    /* BACKEND: sustituir por verificacion real de sesion PHP cuando este disponible */
    /* Ej: return window.PF_LOGUEADO === true; (variable inyectada por PHP en el head) */
    return false;
}

function mostrarModalLoginFav() {
    var modal = document.getElementById('modalLoginFav');
    if (!modal) return;
    new bootstrap.Modal(modal).show();
}

function toggleFavorito(evento, id) {
    evento.stopPropagation();

    if (!estaLogueado()) {
        mostrarModalLoginFav();
        return;
    }

    var CLAVE_FAVORITOS = 'petfamily_favoritos';
    var favoritos = JSON.parse(localStorage.getItem(CLAVE_FAVORITOS) || '[]');
    var posicion  = favoritos.indexOf(id);

    if (posicion !== -1) {
        favoritos.splice(posicion, 1);
    } else {
        favoritos.push(id);
    }

    localStorage.setItem(CLAVE_FAVORITOS, JSON.stringify(favoritos));

    var boton = document.querySelector('.btn-fav[data-id="' + id + '"]');
    if (!boton) return;
    var icono = boton.querySelector('i');
    var esFav = favoritos.indexOf(id) !== -1;

    if (esFav) {
        boton.classList.add('activo');
        icono.className = 'fa-solid fa-heart';
        boton.title = 'Quitar de favoritos';
    } else {
        boton.classList.remove('activo');
        icono.className = 'fa-regular fa-heart';
        boton.title = 'Agregar a favoritos';
    }
}


/* ------------------------------------------------------------------ */
/* dona */
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

    /* Nombre */
    html += '<div style="display:flex;align-items:center;gap:0.5em;'
          + 'margin-bottom:0.7em;padding-bottom:0.7em;border-bottom:2px solid #1B358F;">'
          + '<i class="fa-solid fa-building-shield" style="color:#1B358F;"></i>'
          + '<strong style="font-size:0.97rem;color:#1B358F;letter-spacing:0.01em;">' + p.nombre + '</strong>'
          + '</div>';

    /* Aviso sin web — justo tras el nombre */
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

    /* Contacto */
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

    /* Teaming button */
    if (p.teaming) {
        html += '<a href="' + p.teaming + '" target="_blank" rel="noopener" class="btn-ir-web btn-ir-web-teaming" style="margin-top:0.6em;">'
              + '<i class="fa-solid fa-mug-hot me-2"></i>Colaborar con 1&nbsp;&euro;/mes en Teaming'
              + '</a>';
    }

    /* Web donaciones */
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

    /* IBAN siempre al final */
    if (p.iban) {
        var ibanEsc = p.iban.replace(/'/g, "\'");
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
    var checkboxes  = document.querySelectorAll('input[type="checkbox"]:checked');
    var errorEl     = document.getElementById('errorSeleccion');
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
/* apadrina */
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

// ===============================
// CARGA DINÁMICA DE MASCOTAS
// ===============================

// Renderiza tarjetas de mascotas en adopta.html
async function cargarMascotasAdopta() {
    const contenedor = document.getElementById('contenedor-mascotas');
    if (!contenedor) return;
    contenedor.innerHTML = '<div class="text-center w-100 py-5"><div class="spinner-border" role="status"></div></div>';
    try {
        const res = await fetch('../php/get_mascotas.php');
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

/* Renderiza una tarjeta de mascota COMPLETA */
function renderTarjetaMascota(m) {
    
    /* Calcular edad desde fecha_nacimiento */
    var edad = '';
    if (m.fecha_nacimiento) {
        var nacimiento = new Date(m.fecha_nacimiento);
        var hoy = new Date();
        var edadAños = hoy.getFullYear() - nacimiento.getFullYear();
        var mes = hoy.getMonth() - nacimiento.getMonth();
        if (mes < 0) edadAños--;
        edad = edadAños;
    }
    
    /* Determinar badge de urgencia/estado */
    var badgeHtml = '';
    if (m.urgencia === 'urgente') {
        badgeHtml = '<span class="badge bg-danger">Urgente</span>';
    } else if (m.urgencia === 'nuevo') {
        badgeHtml = '<span class="badge bg-info">Nuevo</span>';
    }
    
    /* Información adicional según propiedades */
    var infoExtra = '';
    if (m.compatible_ninos) infoExtra += '<span class="badge bg-light text-dark me-1"><i class="fa-solid fa-child"></i></span>';
    if (m.compatible_perros) infoExtra += '<span class="badge bg-light text-dark me-1"><i class="fa-solid fa-dog"></i></span>';
    if (m.compatible_gatos) infoExtra += '<span class="badge bg-light text-dark me-1"><i class="fa-solid fa-cat"></i></span>';
    if (m.apto_piso) infoExtra += '<span class="badge bg-light text-dark me-1"><i class="fa-solid fa-home"></i></span>';
    
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
            
            <!-- Imagen con badge de urgencia/estado -->
            <div class="contenedor-imagen position-relative">
                <img src="${m.foto}" class="card-img-top" alt="${m.nombre}">
                
                <!-- Badge urgencia/estado -->
                ${badgeHtml}
                
                <!-- Botón favorito -->
                <button class="btn-fav" onclick="toggleFavorito(event, '${m.idMascota}')" title="Agregar a favoritos">
                    <i class="fa-regular fa-heart"></i>
                </button>
            </div>
            
            <!-- Cuerpo de la tarjeta -->
            <div class="card-body d-flex flex-column">
                
                <!-- Nombre y sexo -->
                <div class="d-flex align-items-center mb-2">
                    <h5 class="card-title mb-0">${m.nombre}</h5>
                    <span class="ms-2 fs-6">
                        ${m.sexo === 'hembra' ? '<i class="fa-solid fa-venus" style="color: #E91E63;"></i>' : '<i class="fa-solid fa-mars" style="color: #2196F3;"></i>'}
                    </span>
                </div>
                
                <!-- Información del animal -->
                <ul class="card-meta list-unstyled small text-secondary mb-2">
                    ${m.raza ? '<li><i class="fa-solid fa-dog"></i> ' + m.raza + '</li>' : ''}
                    ${edad ? '<li><i class="fa-solid fa-calendar"></i> ' + edad + ' años</li>' : ''}
                    ${m.tamano ? '<li><i class="fa-solid fa-expand"></i> ' + (m.tamano.charAt(0).toUpperCase() + m.tamano.slice(1)) + '</li>' : ''}
                </ul>
                
                <!-- Ubicación (protectora) -->
                ${m.ubicacion ? '<div class="small text-muted mb-2"><i class="fa-solid fa-location-dot"></i> ' + m.ubicacion + '</div>' : ''}
                
                <!-- Información del estado de adopción -->
                ${m.estado_adopcion ? '<div class="small text-muted mb-2"><i class="fa-solid fa-hourglass-end"></i> En adopción: ' + m.estado_adopcion.replace('_', ' ') + '</div>' : ''}
                
                <!-- Badges de compatibilidad -->
                ${infoExtra ? '<div class="mb-2">' + infoExtra + '</div>' : ''}
                
                <!-- Contador de vistas -->
                <div class="contador-vistas small text-warning mt-2">
                    <i class="fa-solid fa-eye"></i>
                    <span>Vista <strong id="vistas-${m.idMascota}">0</strong> veces</span>
                </div>
                
                <!-- Botón Ver ficha -->
                <a href="fichaAnimal.html?id=${m.idMascota}" class="btn btn-primary w-100 mt-auto" onclick="registrarVistaFicha('${m.idMascota}')">
                    Ver ficha
                </a>
            </div>
        </div>
    </div>
    `;
}

// Cargar datos de una mascota en fichaAnimal.html
async function cargarMascotaFicha() {
    const params = new URLSearchParams(window.location.search);
    const id = params.get('id');
    if (!id) return;
    try {
        const res = await fetch(`../php/get_mascota.php?id=${id}`);
        const json = await res.json();
        if (!json.success) throw new Error(json.error || 'No encontrada');
        const m = json.data;
        document.getElementById('animal-nombre').textContent = m.nombre;
        document.getElementById('animal-subtitulo').textContent = `${m.raza || ''} · ${m.sexo || ''}`;
        document.getElementById('animal-descripcion').textContent = m.descripcion || '';
        document.getElementById('foto-principal').src = m.foto || '../img/mascotas/default.jpg';
        // Puedes completar más campos según tu HTML
    } catch (e) {
        document.querySelector('main').innerHTML = `<div class='alert alert-danger'>Error: ${e.message}</div>`;
    }
}

// Inicialización automática
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('contenedor-mascotas')) cargarMascotasAdopta();
        if (document.getElementById('animal-nombre')) cargarMascotaFicha();
    });
} else {
    if (document.getElementById('contenedor-mascotas')) cargarMascotasAdopta();
    if (document.getElementById('animal-nombre')) cargarMascotaFicha();
}