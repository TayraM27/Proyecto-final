var _notifAdminInterval = null;

function cargarNotificacionesAdmin() {
    fetch('../backend/foro/notificaciones.php?solo_no_leidas=1&_=' + Date.now(), { credentials: 'include' })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success) return;
            var n = data.noLeidas || 0;
            var badge = document.getElementById('notifBadge');
            if (badge) {
                badge.textContent = n > 99 ? '99+' : n;
                badge.style.display = n > 0 ? 'flex' : 'none';
            }
        })
        .catch(function() {});
}

function iniciarNotificacionesAdmin() {
    cargarNotificacionesAdmin();
    if (_notifAdminInterval) clearInterval(_notifAdminInterval);
    _notifAdminInterval = setInterval(cargarNotificacionesAdmin, 15000);
}
