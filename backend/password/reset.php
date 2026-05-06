<?php
/*--------------------------------------------------------------------------------------------
password/reset.php
Verifica el token y permite al usuario establecer una nueva contraseña */

require_once __DIR__ . '/../includes/funciones.php';

$token = trim($_GET['token'] ?? '');
$valido = false;
$error  = '';
$idUsuario = null;

if (!$token || strlen($token) !== 64) {
    $error = 'Token no válido.';
} else {
    $pdo  = conectar();
    $stmt = $pdo->prepare(
        'SELECT id, idUsuario FROM password_resets
         WHERE token = ? AND used = 0 AND expires_at > NOW()
         LIMIT 1'
    );
    $stmt->execute([$token]);
    $reset = $stmt->fetch();

    if ($reset) {
        $valido    = true;
        $idUsuario = (int)$reset['idUsuario'];
    } else {
        $error = 'El enlace no es válido o ha caducado. Solicita uno nuevo.';
    }
}

/*--------------------------------------------------------------------------------------------
procesar nueva contraseña */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valido && $idUsuario) {
    $password  = $_POST['password']  ?? '';
    $confirmar = $_POST['confirmar'] ?? '';

    if (!$password || !$confirmar) {
        $error = 'Rellena los dos campos.';
    } elseif ($password !== $confirmar) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (strlen($password) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } else {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $pdo->prepare('UPDATE usuarios SET password_hash = ? WHERE idUsuario = ?')
            ->execute([$hash, $idUsuario]);
        $pdo->prepare('UPDATE password_resets SET used = 1 WHERE token = ?')
            ->execute([$token]);

        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">
              <title>Contraseña actualizada — PetFamily</title>
              <meta http-equiv="refresh" content="3;url=../../html/login.html">
              <style>body{font-family:Poppins,Arial,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f8f9fa;}
              .box{text-align:center;padding:2em;background:#fff;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,0.08);max-width:400px;}
              h2{color:#1B358F;}a{color:#1B358F;}</style></head>
              <body><div class="box">
              <h2>🐾 PetFamily</h2>
              <p style="font-size:1.5em;">✅</p>
              <p><strong>Contraseña actualizada correctamente.</strong></p>
              <p style="color:#888;font-size:0.9em;">Te redirigimos al inicio de sesión en 3 segundos...</p>
              <a href="../../html/login.html">Ir ahora →</a>
              </div></body></html>';
        exit;
    }
}

/*--------------------------------------------------------------------------------------------
formulario */
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer contraseña — PetFamily</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:Poppins,Arial,sans-serif;background:#f0f2f5;display:flex;align-items:center;justify-content:center;min-height:100vh;}
        .box{background:#fff;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,0.09);padding:2.5em 2em;width:100%;max-width:420px;}
        .logo{text-align:center;margin-bottom:1.5em;}
        .logo h1{color:#1B358F;font-size:1.5rem;font-weight:700;}
        .logo span{color:#F8BA56;}
        label{display:block;font-size:0.85rem;font-weight:600;color:#333;margin-bottom:0.3em;}
        input[type=password]{width:100%;border:1.5px solid #dee2e6;border-radius:8px;padding:0.65em 0.9em;font-family:inherit;font-size:0.95rem;outline:none;transition:border-color 0.2s;margin-bottom:1em;}
        input[type=password]:focus{border-color:#1B358F;}
        button{width:100%;background:#1B358F;color:#fff;border:none;border-radius:8px;padding:0.75em;font-family:inherit;font-size:1rem;font-weight:600;cursor:pointer;transition:background 0.2s;}
        button:hover{background:#162d78;}
        .error{background:#fdecea;color:#c0392b;border-radius:8px;padding:0.7em 1em;font-size:0.85rem;margin-bottom:1em;}
        .info{color:#888;font-size:0.82rem;margin-top:1em;text-align:center;}
        .info a{color:#1B358F;text-decoration:none;}
    </style>
</head>
<body>
<div class="box">
    <div class="logo">
        <h1>🐾 Pet<span>Family</span></h1>
        <p style="color:#888;font-size:0.88rem;margin-top:0.3em;">Restablecer contraseña</p>
    </div>

    <?php if (!$valido): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <p class="info"><a href="../../html/recuperarPassword.html">← Solicitar un nuevo enlace</a></p>

    <?php else: ?>
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <label for="password">Nueva contraseña</label>
            <input type="password" id="password" name="password" required minlength="8" placeholder="Mínimo 8 caracteres">
            <label for="confirmar">Repetir contraseña</label>
            <input type="password" id="confirmar" name="confirmar" required minlength="8" placeholder="Repite la contraseña">
            <button type="submit">Guardar contraseña</button>
        </form>
        <p class="info"><a href="../../html/login.html">← Volver al inicio de sesión</a></p>
    <?php endif; ?>
</div>
</body>
</html>