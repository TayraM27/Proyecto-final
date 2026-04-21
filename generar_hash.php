<?php
/*--------------------------------------------------------------------------------------------
UTILIDAD — genera el hash bcrypt para la contraseña del administrador
Coloca este archivo en la raíz del proyecto (ProyectoCat/)
Accede desde: localhost/ProyectoCat/generar_hash.php
BORRAR ESTE ARCHIVO DESPUÉS DE USARLO */

$password = 'Admin1234!';
$hash     = password_hash($password, PASSWORD_BCRYPT);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Generar Hash Admin</title>
    <style>
        body{font-family:monospace;max-width:700px;margin:40px auto;padding:20px;}
        pre{background:#f4f4f4;padding:15px;border-radius:6px;word-break:break-all;}
        .aviso{background:#fff3cd;border:1px solid #ffc107;padding:12px;border-radius:6px;margin-top:20px;}
    </style>
</head>
<body>

<h2>Hash generado para: <code><?= htmlspecialchars($password) ?></code></h2>

<pre><?= htmlspecialchars($hash) ?></pre>

<p>Ejecuta esta consulta en <strong>phpMyAdmin → pestaña SQL</strong>:</p>

<pre>UPDATE usuarios
SET password_hash = '<?= htmlspecialchars($hash) ?>'
WHERE email = 'admin-petfamily@yopmail.com';</pre>

<div class="aviso">
    ⚠️ <strong>IMPORTANTE:</strong> borra este archivo tras ejecutar la consulta.<br>
    Ruta: <code>ProyectoCat/generar_hash.php</code>
</div>

</body>
</html>