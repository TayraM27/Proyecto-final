document.addEventListener('DOMContentLoaded', function() {

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
    var logoXL = document.getElementById('logoXL');
    if (logoXL && logoImagenXL) {
        configurarHoverLogo(logoXL, logoImagenXL);
    }

    var logoImagenMD = document.getElementById('logoHeaderMD');
    var logoMD = document.getElementById('logoMD');
    if (logoMD && logoImagenMD) {
        configurarHoverLogo(logoMD, logoImagenMD);
    }

    // ----------------------------------------------------------------
    // Menú burger
    // ----------------------------------------------------------------
    var botonBurger = document.getElementById('btn_burger');
    var menu = document.getElementById('menu');
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
    // Submenú Colabora
    // ----------------------------------------------------------------
    var enlaceColabora = document.querySelector('a.colabora');
    var itemColabora = enlaceColabora ? enlaceColabora.closest('li') : null;

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
        if (datos) {
            return JSON.parse(datos);
        }
        return [];
    }

    function guardarFavoritos(lista) {
        localStorage.setItem(CLAVE_FAVORITOS, JSON.stringify(lista));
    }

    function esFavorito(id) {
        var favoritos = obtenerFavoritos();
        return favoritos.indexOf(id) !== -1;
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

    // Inicializar botones de favoritos al cargar la página
    var botonesFav = document.querySelectorAll('.btn-fav[data-id]');
    for (var i = 0; i < botonesFav.length; i++) {
        actualizarBotonFavorito(botonesFav[i].dataset.id);
    }

    // ----------------------------------------------------------------
    // Contador de vistas
    // ----------------------------------------------------------------
    var CLAVE_VISTAS = 'petfamily_vistas';

    function obtenerVistas() {
        var datos = localStorage.getItem(CLAVE_VISTAS);
        if (datos) {
            return JSON.parse(datos);
        }
        return {};
    }

    function contarVista(id) {
        var vistas = obtenerVistas();
        if (vistas[id]) {
            vistas[id] = vistas[id] + 1;
        } else {
            vistas[id] = 1;
        }
        localStorage.setItem(CLAVE_VISTAS, JSON.stringify(vistas));
        return vistas[id];
    }

    function obtenerNumeroVistas(id) {
        var vistas = obtenerVistas();
        if (vistas[id]) {
            return vistas[id];
        }
        return 0;
    }

    function registrarVistaFicha(id) {
        var total = contarVista(id);
        var elemento = document.getElementById('vistas-' + id);
        if (elemento) {
            elemento.textContent = total;
        }
        return total;
    }

    // ----------------------------------------------------------------
    // Foro
    // ----------------------------------------------------------------
    if (document.getElementById('textareaPublicar')) {

        var estaLogueado = false;

        // Filtros de categorías
        var btnsFiltro = document.querySelectorAll('.foro-filtro-btn');
        var posts = document.querySelectorAll('.foro-post');

        btnsFiltro.forEach(function(btn) {
            btn.addEventListener('click', function() {
                btnsFiltro.forEach(function(b) { b.classList.remove('activo'); });
                btn.classList.add('activo');
                var cat = btn.getAttribute('data-cat');
                posts.forEach(function(post) {
                    post.style.display = (cat === 'todas' || post.getAttribute('data-cat') === cat)
                        ? 'block' : 'none';
                });
            });
        });

        // Ver más comentarios
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

        // Publicar
        var btnPublicar = document.getElementById('btnPublicar');
        var textarea = document.getElementById('textareaPublicar');
        var avisoLogin = document.getElementById('avisoLogin');
        var btnCerrar = document.getElementById('btnCerrarAviso');

        // Recuperar texto guardado
        var textoPendiente = sessionStorage.getItem('foroTextoPendiente');
        if (textoPendiente) {
            textarea.value = textoPendiente;
            textarea.classList.add('textarea-pendiente');
        }

        btnPublicar.addEventListener('click', function() {
            var texto = textarea.value.trim();
            if (!texto) {
                textarea.focus();
                textarea.classList.add('textarea-error');
                setTimeout(function() { textarea.classList.remove('textarea-error'); }, 2000);
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
            this.classList.toggle('textarea-activo', this.value.trim().length > 0);
            this.classList.remove('textarea-pendiente', 'textarea-error');
        });

        // Adjuntos: aviso si no logueado
        document.querySelectorAll('.foro-adj-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                if (!estaLogueado) {
                    avisoLogin.classList.remove('d-none');
                    avisoLogin.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
        });

        // Input comentario: aviso si no logueado
        document.querySelectorAll('.foro-input-com').forEach(function(inp) {
            inp.addEventListener('focus', function() {
                if (!estaLogueado) {
                    this.blur();
                    avisoLogin.classList.remove('d-none');
                    avisoLogin.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
        });

        // Reacciones y likes: aviso si no logueado
        document.querySelectorAll('.foro-reaccion-btn, .foro-com-like').forEach(function(btn) {
            btn.addEventListener('click', function() {
                if (!estaLogueado) {
                    avisoLogin.classList.remove('d-none');
                }
            });
        });
    }

}); // DOMContentLoaded


/* ------------------------------------------------------------------ */


// toggleFavorito se llama desde onclick en fichas de animales
function toggleFavorito(evento, id) {
    evento.stopPropagation();
    var CLAVE_FAVORITOS = 'petfamily_favoritos';
    var datos = localStorage.getItem(CLAVE_FAVORITOS);
    var favoritos = datos ? JSON.parse(datos) : [];
    var posicion = favoritos.indexOf(id);
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
/* Dona                                                                */

var protectoras = {
    'prot-1': {
        nombre:   'Centro de Protección Animal de Gijón',
        telefono: '615 411 417',
        email:    'cproteccionanimalgijon@gmail.com',
        web:      'https://www.albergaria.es/protectoras/centro-proteccion-animales-gijon/',
        teaming:  'https://www.teaming.net/fundacionprotectoradeanimalesasturias',
        iban:     'ES15 0081 5665 2400 0109 0516 (Sabadell)'
    },
    'prot-2': {
        nombre:   'Nortemascotas',
        telefono: '665 971 933',
        email:    'Mariasol.ferreyra2014@gmail.com',
        web:      'https://www.facebook.com/Asociacionnortemascotas/',
        teaming:  null,
        iban:     'ES39 0182 2800 1902 0163 9405 (BBVA)'
    },
    'prot-3': {
        nombre:   'MÁS QUE CHUCHOS',
        telefono: null,
        email:    'info@masquechuchos.org',
        web:      'http://masquechuchos.org',
        teaming:  'https://www.teaming.net/masquechuchos/'
    },
    'prot-4': {
        nombre:   'Fundación Protectora de Asturias',
        telefono: null,
        email:    'info@protectoradeasturias.org',
        web:      'http://www.protectoradeasturias.org/index.php/colabora/donaciones',
        teaming:  'https://www.teaming.net/fundacionprotectoradeanimalesasturias',
        iban:     'ES15 0081 5665 2400 0109 0516 (Sabadell)'
    },
    'prot-5': {
        nombre:   'Asociación Felina La Esperanza',
        telefono: null,
        email:    'asociacionfelinalaesperanza@gmail.com',
        web:      null,
        teaming:  'https://www.teaming.net/asociacionfelinalaesperanza'
    }
};

function toggleProtectora(id) {
    var card = document.getElementById(id);
    var checkbox = card.querySelector('input[type="checkbox"]');
    card.classList.toggle('seleccionada', checkbox.checked);
}

function buildDatosProt(p) {
    var html = '<div class="modal-prot-bloque">';
    html += '<p class="modal-prot-nombre">' + p.nombre + '</p>';
    if (p.telefono) {
        html += '<div class="modal-dato"><i class="fa-solid fa-phone"></i><span>' + p.telefono + '</span></div>';
    }
    if (p.email) {
        html += '<div class="modal-dato"><i class="fa-solid fa-envelope"></i><span>' + p.email + '</span></div>';
    }
    if (p.iban) {
        html += '<div class="modal-dato"><i class="fa-solid fa-building-columns"></i><span>' + p.iban + '</span></div>';
    }
    if (p.web) {
        html += '<a href="' + p.web + '" target="_blank" class="btn-ir-web">' +
            '<i class="fa-solid fa-arrow-up-right-from-square me-2"></i>Ir a la página de donaciones</a>';
    }
    if (p.teaming) {
        html += '<a href="' + p.teaming + '" target="_blank" class="btn-ir-web btn-ir-web-teaming">' +
            '<i class="fa-solid fa-mug-hot me-2"></i>Colaborar con 1 €/mes en Teaming</a>';
    }
    html += '</div>';
    return html;
}

function mostrarModalDona() {
    var checkboxes = document.querySelectorAll('input[type="checkbox"]:checked');
    if (checkboxes.length === 0) {
        alert('Por favor, selecciona al menos una protectora.');
        return;
    }
    var contenido = '';
    for (var i = 0; i < checkboxes.length; i++) {
        var id = checkboxes[i].closest('label').id;
        contenido += buildDatosProt(protectoras[id]);
    }
    document.getElementById('modal-datos-protectora').innerHTML = contenido;
    var modal = new bootstrap.Modal(document.getElementById('modalDonacion'));
    modal.show();
}

/* ------------------------------------------------------------------ */
/* Apadrina                                                            */
/* ------------------------------------------------------------------ */

var animalesApadrina = {
    leia: {
        nombre:     'Leia',
        protectora: 'Centro de Protección Animal de Gijón',
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
        protectora: 'Fundación Protectora de Asturias',
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
    if (animal.telefono) {
        html += '<div class="modal-dato"><i class="fa-solid fa-phone"></i>' +
            '<a href="tel:' + animal.telefono.replace(/\s/g, '') + '">' + animal.telefono + '</a></div>';
    }
    if (animal.email) {
        html += '<div class="modal-dato"><i class="fa-solid fa-envelope"></i>' +
            '<a href="mailto:' + animal.email + '">' + animal.email + '</a></div>';
    }
    if (animal.iban) {
        html += '<div class="modal-dato"><i class="fa-solid fa-building-columns"></i>' +
            '<span>' + animal.iban + '</span></div>';
    }
    if (animal.web) {
        html += '<a href="' + animal.web + '" target="_blank" rel="noopener" class="btn-ir-web mt-2">' +
            '<i class="fa-solid fa-arrow-up-right-from-square me-1"></i>Ver ficha en la protectora</a>';
    }
    if (animal.teaming) {
        html += '<a href="' + animal.teaming + '" target="_blank" rel="noopener" class="btn-ir-web btn-ir-web-teaming mt-2">' +
            '<i class="fa-solid fa-mug-hot me-1"></i>Colaborar por Teaming (1 €/mes)</a>';
    }
    document.getElementById('modal-datos-apadrina').innerHTML = html;
    var modal = new bootstrap.Modal(document.getElementById('modalApadrina'));
    modal.show();
}
/* ------------------------------------------------------------------ */
/* Login                                                               */
/* ------------------------------------------------------------------ */

document.addEventListener('DOMContentLoaded', function() {

    if (!document.getElementById('formLogin')) return;

    var formLogin    = document.getElementById('formLogin');
    var inputEmail   = document.getElementById('loginEmail');
    var inputPassword= document.getElementById('loginPassword');
    var errorEmail   = document.getElementById('errorEmail');
    var errorPassword= document.getElementById('errorPassword');
    var alertaLogin  = document.getElementById('alertaLogin');
    var btnLogin     = document.getElementById('btnLogin');
    var togglePwd    = document.getElementById('togglePwd');
    var iconoPwd     = document.getElementById('iconoPwd');
    var inputRol     = document.getElementById('inputRol');
    var panelUsuario = document.getElementById('panelUsuario');
    var panelAdmin   = document.getElementById('panelAdmin');

    // Selector de rol
    var botonesRol = document.querySelectorAll('.login-rol-btn');

    botonesRol.forEach(function(btn) {
        btn.addEventListener('click', function() {
            botonesRol.forEach(function(b) { b.classList.remove('activo'); });
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

    // Mostrar/ocultar contraseña
    togglePwd.addEventListener('click', function() {
        var tipo = inputPassword.type === 'password' ? 'text' : 'password';
        inputPassword.type = tipo;
        iconoPwd.className = tipo === 'password' ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash';
    });

    // Validación
    inputEmail.addEventListener('input', validarEmail);
    inputEmail.addEventListener('blur', validarEmail);
    inputPassword.addEventListener('input', validarPassword);
    inputPassword.addEventListener('blur', validarPassword);

    function validarEmail() {
        var valor = inputEmail.value.trim();
        var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!valor) return mostrarError(inputEmail, errorEmail, 'El email es obligatorio.'), false;
        if (!regex.test(valor)) return mostrarError(inputEmail, errorEmail, 'Introduce un email válido.'), false;
        limpiarError(inputEmail, errorEmail);
        return true;
    }

    function validarPassword() {
        var valor = inputPassword.value;
        if (!valor) return mostrarError(inputPassword, errorPassword, 'La contraseña es obligatoria.'), false;
        if (valor.length < 6) return mostrarError(inputPassword, errorPassword, 'Mínimo 6 caracteres.'), false;
        limpiarError(inputPassword, errorPassword);
        return true;
    }

    function mostrarError(input, span, msg) {
        input.classList.remove('input-ok');
        input.classList.add('input-error');
        span.textContent = msg;
    }

    function limpiarError(input, span) {
        input.classList.remove('input-error');
        input.classList.add('input-ok');
        span.textContent = '';
    }

    // Envío
    formLogin.addEventListener('submit', function(e) {
        e.preventDefault();

        var emailOk    = validarEmail();
        var passwordOk = validarPassword();
        if (!emailOk || !passwordOk) return;

        btnLogin.disabled = true;
        btnLogin.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Iniciando sesión...';
        alertaLogin.classList.add('d-none');

        setTimeout(function() {
            btnLogin.disabled = false;
            btnLogin.innerHTML = '<i class="fa-solid fa-right-to-bracket me-2"></i>Iniciar sesión';
            alertaLogin.classList.remove('d-none');
        }, 1200);
    });

});
/* ------------------------------------------------------------------ */
/* Registro                                                            */
/* ------------------------------------------------------------------ */

document.addEventListener('DOMContentLoaded', function() {

    var formRegistro = document.getElementById('formRegistro');
    if (!formRegistro) return;

    var inputNombre          = document.getElementById('regNombre');
    var inputUsername        = document.getElementById('regUsername');
    var inputEmail           = document.getElementById('regEmail');
    var inputLocalidad       = document.getElementById('regLocalidad');
    var inputTelefono        = document.getElementById('regTelefono');
    var inputPassword        = document.getElementById('regPassword');
    var inputPasswordConfirm = document.getElementById('regPasswordConfirm');
    var inputTerminos        = document.getElementById('regTerminos');
    var inputFoto            = document.getElementById('fotoPerfil');
    var fotoPreview          = document.getElementById('fotoPreview');
    var fotoIcono            = document.getElementById('fotoIcono');
    var fotoImg              = document.getElementById('fotoImg');
    var btnRegistro          = document.getElementById('btnRegistro');
    var alertaRegistro       = document.getElementById('alertaRegistro');

    // ----------------------------------------------------------------
    // Preview de foto de perfil
    // ----------------------------------------------------------------
    document.querySelector('.reg-foto-label').addEventListener('click', function() {
        inputFoto.click();
    });

    inputFoto.addEventListener('change', function() {
        var archivo = this.files[0];
        if (!archivo) return;

        // Validar tamaño (max 2 MB)
        if (archivo.size > 2 * 1024 * 1024) {
            mostrarError(null, document.getElementById('errorNombre'), 'La foto no puede superar 2 MB.');
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

    // ----------------------------------------------------------------
    // Mostrar/ocultar contraseñas
    
    document.getElementById('togglePwdReg').addEventListener('click', function() {
        toggleVisibilidad(inputPassword, document.getElementById('iconoPwdReg'));
    });

    document.getElementById('togglePwdConfirm').addEventListener('click', function() {
        toggleVisibilidad(inputPasswordConfirm, document.getElementById('iconoPwdConfirm'));
    });

    function toggleVisibilidad(input, icono) {
        var tipo = input.type === 'password' ? 'text' : 'password';
        input.type = tipo;
        icono.className = tipo === 'password' ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash';
    }

    // ----------------------------------------------------------------
    // Indicador de fuerza de contraseña
    // ----------------------------------------------------------------
    inputPassword.addEventListener('input', function() {
        var val = this.value;
        var fuerza = calcularFuerza(val);
        var fill  = document.getElementById('pwdFill');
        var label = document.getElementById('pwdLabel');

        if (!val) {
            fill.style.width = '0%';
            label.textContent = '';
            return;
        }

        if (fuerza === 1) {
            fill.style.width = '33%';
            fill.style.backgroundColor = '#e74c3c';
            label.style.color = '#e74c3c';
            label.textContent = 'Débil';
        } else if (fuerza === 2) {
            fill.style.width = '66%';
            fill.style.backgroundColor = '#F8BA56';
            label.style.color = '#c87a00';
            label.textContent = 'Media';
        } else {
            fill.style.width = '100%';
            fill.style.backgroundColor = '#2e7d32';
            label.style.color = '#2e7d32';
            label.textContent = 'Fuerte';
        }

        validarPassword();
        if (inputPasswordConfirm.value) validarPasswordConfirm();
    });

    function calcularFuerza(pwd) {
        var puntos = 0;
        if (pwd.length >= 8) puntos++;
        if (/[A-Z]/.test(pwd)) puntos++;
        if (/[0-9]/.test(pwd)) puntos++;
        if (/[^A-Za-z0-9]/.test(pwd)) puntos++;
        if (puntos <= 1) return 1;
        if (puntos <= 2) return 2;
        return 3;
    }

    // ----------------------------------------------------------------
    // Validaciones individuales
    inputNombre.addEventListener('blur', validarNombre);
    inputNombre.addEventListener('input', function() { if (this.classList.contains('input-error')) validarNombre(); });

    inputUsername.addEventListener('blur', validarUsername);
    inputUsername.addEventListener('input', function() { if (this.classList.contains('input-error')) validarUsername(); });

    inputEmail.addEventListener('blur', validarEmail);
    inputEmail.addEventListener('input', function() { if (this.classList.contains('input-error')) validarEmail(); });

    inputLocalidad.addEventListener('blur', validarLocalidad);
    inputLocalidad.addEventListener('input', function() { if (this.classList.contains('input-error')) validarLocalidad(); });

    inputTelefono.addEventListener('blur', validarTelefono);
    inputTelefono.addEventListener('input', function() { if (this.classList.contains('input-error')) validarTelefono(); });

    inputPasswordConfirm.addEventListener('blur', validarPasswordConfirm);
    inputPasswordConfirm.addEventListener('input', function() { if (this.classList.contains('input-error')) validarPasswordConfirm(); });

    function validarNombre() {
        var val = inputNombre.value.trim();
        if (!val) return mostrarError(inputNombre, document.getElementById('errorNombre'), 'El nombre es obligatorio.'), false;
        if (val.length < 2) return mostrarError(inputNombre, document.getElementById('errorNombre'), 'El nombre debe tener al menos 2 caracteres.'), false;
        limpiarError(inputNombre, document.getElementById('errorNombre'));
        return true;
    }

    function validarUsername() {
        var val = inputUsername.value.trim();
        if (!val) return mostrarError(inputUsername, document.getElementById('errorUsername'), 'El nombre de usuario es obligatorio.'), false;
        if (val.length < 3) return mostrarError(inputUsername, document.getElementById('errorUsername'), 'Mínimo 3 caracteres.'), false;
        if (/\s/.test(val)) return mostrarError(inputUsername, document.getElementById('errorUsername'), 'Sin espacios.'), false;
        limpiarError(inputUsername, document.getElementById('errorUsername'));
        return true;
    }

    function validarEmail() {
        var val = inputEmail.value.trim();
        var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!val) return mostrarError(inputEmail, document.getElementById('errorEmail'), 'El email es obligatorio.'), false;
        if (!regex.test(val)) return mostrarError(inputEmail, document.getElementById('errorEmail'), 'Introduce un email válido.'), false;
        limpiarError(inputEmail, document.getElementById('errorEmail'));
        return true;
    }

    function validarLocalidad() {
        var val = inputLocalidad.value.trim();
        if (!val) return mostrarError(inputLocalidad, document.getElementById('errorLocalidad'), 'La localidad es obligatoria.'), false;
        limpiarError(inputLocalidad, document.getElementById('errorLocalidad'));
        return true;
    }

    function validarTelefono() {
        var val = inputTelefono.value.trim();
        if (!val) return true; // Opcional
        var regex = /^[6-9]\d{8}$/;
        if (!regex.test(val.replace(/\s/g, ''))) return mostrarError(inputTelefono, document.getElementById('errorTelefono'), 'Teléfono no válido. Ej: 612 345 678'), false;
        limpiarError(inputTelefono, document.getElementById('errorTelefono'));
        return true;
    }

    function validarPassword() {
        var val = inputPassword.value;
        if (!val) return mostrarError(inputPassword, document.getElementById('errorPassword'), 'La contraseña es obligatoria.'), false;
        if (val.length < 8) return mostrarError(inputPassword, document.getElementById('errorPassword'), 'Mínimo 8 caracteres.'), false;
        limpiarError(inputPassword, document.getElementById('errorPassword'));
        return true;
    }

    function validarPasswordConfirm() {
        var val = inputPasswordConfirm.value;
        if (!val) return mostrarError(inputPasswordConfirm, document.getElementById('errorPasswordConfirm'), 'Repite la contraseña.'), false;
        if (val !== inputPassword.value) return mostrarError(inputPasswordConfirm, document.getElementById('errorPasswordConfirm'), 'Las contraseñas no coinciden.'), false;
        limpiarError(inputPasswordConfirm, document.getElementById('errorPasswordConfirm'));
        return true;
    }

    function validarTerminos() {
        if (!inputTerminos.checked) {
            document.getElementById('errorTerminos').textContent = 'Debes aceptar los términos para continuar.';
            return false;
        }
        document.getElementById('errorTerminos').textContent = '';
        return true;
    }

    function mostrarError(input, span, msg) {
        if (input) { input.classList.remove('input-ok'); input.classList.add('input-error'); }
        span.textContent = msg;
    }

    function limpiarError(input, span) {
        if (input) { input.classList.remove('input-error'); input.classList.add('input-ok'); }
        span.textContent = '';
    }

    // ----------------------------------------------------------------
    // Envío del formulario

    formRegistro.addEventListener('submit', function(e) {
        e.preventDefault();

        var ok = validarNombre()
            & validarUsername()
            & validarEmail()
            & validarLocalidad()
            & validarTelefono()
            & validarPassword()
            & validarPasswordConfirm()
            & validarTerminos();

        if (!ok) {
            // Scroll al primer error
            var primerError = formRegistro.querySelector('.input-error');
            if (primerError) primerError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        // Estado de carga
        btnRegistro.disabled = true;
        btnRegistro.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Creando cuenta...';
        alertaRegistro.classList.add('d-none');

        setTimeout(function() {
            btnRegistro.disabled = false;
            btnRegistro.innerHTML = '<i class="fa-solid fa-user-plus me-2"></i>Crear cuenta gratis';
            alertaRegistro.classList.remove('d-none');
        }, 1200);
    });

});