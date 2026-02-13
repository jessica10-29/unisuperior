<?php
require_once 'conexion.php';

$mensaje = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = limpiar_dato($_POST['nombre']);
    $email = limpiar_dato($_POST['email']);
    $identificacion = limpiar_dato($_POST['identificacion']);
    $password = $_POST['password'];
    $tipo_usuario = $_POST['tipo_usuario'];

    // Campos extra para estudiantes
    $programa = isset($_POST['programa']) ? limpiar_dato($_POST['programa']) : '';
    $semestre = isset($_POST['semestre']) ? limpiar_dato($_POST['semestre']) : '';

    $codigo_docente = isset($_POST['codigo_docente']) ? $_POST['codigo_docente'] : '';

    $rol = 'estudiante';
    $valido = true;

    if ($tipo_usuario == 'profesor') {
        if ($codigo_docente === 'UNICALI_DOCENTE') {
            $rol = 'profesor';
        } else {
            $mensaje = '<div style="background: rgba(244, 63, 94, 0.1); color: #fb7185; padding: 12px; border-radius: 10px; margin-bottom: 25px; font-size: 0.85rem; border: 1px solid rgba(244, 63, 114, 0.2);"><i class="fa-solid fa-triangle-exclamation" style="margin-right: 8px;"></i> Código de docente incorrecto.</div>';
            $valido = false;
        }
    }

    if ($valido) {
        $stmt_check = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            $mensaje = '<div style="background: rgba(244, 63, 94, 0.1); color: #fb7185; padding: 12px; border-radius: 10px; margin-bottom: 25px; font-size: 0.85rem; border: 1px solid rgba(244, 63, 114, 0.2);"><i class="fa-solid fa-triangle-exclamation" style="margin-right: 8px;"></i> El correo ya está registrado.</div>';
        } else {
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, identificacion, password, rol, programa_academico, semestre) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $nombre, $email, $identificacion, $password_hash, $rol, $programa, $semestre);

            if ($stmt->execute()) {
                $last_id = $stmt->insert_id;
                $current_year = date('Y');
                if ($rol == 'estudiante') {
                    $nuevo_codigo = "UC-" . $current_year . "-" . str_pad($last_id, 4, "0", STR_PAD_LEFT);
                    $conn->query("UPDATE usuarios SET codigo_estudiantil = '$nuevo_codigo' WHERE id = $last_id");
                }
                $mensaje = '<div style="background: rgba(16, 185, 129, 0.1); color: #34d399; padding: 12px; border-radius: 10px; margin-bottom: 25px; font-size: 0.85rem; border: 1px solid rgba(16, 185, 129, 0.2);"><i class="fa-solid fa-circle-check" style="margin-right: 8px;"></i> Registro exitoso. Tu código estudiantil ha sido generado automáticamente. <a href="login.php" style="color: var(--primary); font-weight: 600;">Inicia sesión Aquí</a></div>';
            } else {
                $mensaje = '<div style="background: rgba(244, 63, 94, 0.1); color: #fb7185; padding: 12px; border-radius: 10px; margin-bottom: 25px; font-size: 0.85rem; border: 1px solid rgba(244, 63, 114, 0.2);"><i class="fa-solid fa-circle-exclamation" style="margin-right: 8px;"></i> Error al registrar.</div>';
            }
            $stmt->close();
        }
        $stmt_check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cuenta - Unicali Segura</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="css/estilos.css">
    <script>
        function toggleCodigo(val) {
            const div = document.getElementById('codigo-area');
            const estFields = document.getElementById('estudiante-fields');
            const programa = document.querySelector("select[name='programa']");
            const semestre = document.querySelector("select[name='semestre']");
            const codigoDoc = document.querySelector("input[name='codigo_docente']");

            if (val === 'profesor') {
                div.style.display = 'block';
                div.classList.add('fade-in');
                estFields.style.display = 'none';

                // Evita que el navegador bloquee el submit por campos ocultos requeridos
                programa.required = false;
                semestre.required = false;
                programa.value = '';
                semestre.value = '';

                // El cdigo docente s debe ser obligatorio para este rol
                codigoDoc.required = true;
            } else {
                div.style.display = 'none';
                estFields.style.display = 'block';
                estFields.classList.add('fade-in');

                programa.required = true;
                semestre.required = true;
                codigoDoc.required = false;
                codigoDoc.value = '';
            }
        }
    </script>
</head>

<body>
    <div class="background-mesh"></div>
    <div class="login-container">
        <div style="position: absolute; top: 30px; left: 30px;">
            <a href="index.php" class="btn btn-outline" style="padding: 10px 15px;">
                <i class="fa-solid fa-arrow-left"></i> Volver
            </a>
        </div>
        <div class="glass-panel login-box fade-in" style="max-width: 480px;">
            <div class="logo-area" style="margin-bottom: 25px;">
                <i class="fa-solid fa-user-plus logo-large" style="font-size: 2.5rem;"></i>
                <h2 style="font-size: 1.8rem;">nete a Unicali Segura</h2>
                <p class="text-muted">Crea tu cuenta académica hoy</p>
            </div>

            <?php echo $mensaje; ?>

            <form method="POST" action="">
                <div class="input-group">
                    <label class="input-label">Nombre Completo</label>
                    <input type="text" name="nombre" class="input-field" placeholder="Ej. Juan Pérez" required>
                </div>

                <div class="input-group">
                    <label class="input-label">Cédula / Identificación</label>
                    <input type="text" name="identificacion" class="input-field" placeholder="Ej. 1005678..." required>
                </div>

                <div class="input-group">
                    <label class="input-label">Correo Electrónico</label>
                    <input type="email" name="email" class="input-field" placeholder="juan123@gmail.com" required>
                </div>

                <div class="input-group">
                    <label class="input-label">Tipo de Usuario</label>
                    <select name="tipo_usuario" class="input-field" onchange="toggleCodigo(this.value)"
                        style="appearance: none; background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%2394a3b8%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22/%3E%3C/svg%3E'); background-repeat: no-repeat; background-position: right 1rem center; background-size: 0.65rem auto; padding-right: 2.5rem;">
                        <option value="estudiante">Estudiante</option>
                        <option value="profesor">Profesor (Requiere Código)</option>
                    </select>
                </div>

                <div id="estudiante-fields" class="fade-in">
                    <div class="input-group">
                        <label class="input-label">Programa Académico</label>
                        <select name="programa" class="input-field" required>
                            <option value="">-- Seleccione su programa --</option>
                            <option value="Ingeniería de Sistemas">Ingeniería de Sistemas</option>
                            <option value="Ingeniería de Software">Ingeniería de Software</option>
                            <option value="Ingeniería Industrial">Ingeniería Industrial</option>
                            <option value="Administración de Empresas">Administración de Empresas</option>
                            <option value="Contaduría Pública">Contaduría Pública</option>
                            <option value="Derecho">Derecho</option>
                            <option value="Psicología">Psicología</option>
                            <option value="Trabajo Social">Trabajo Social</option>
                        </select>
                    </div>

                    <div class="input-group">
                        <label class="input-label">Semestre Actual</label>
                        <select name="semestre" class="input-field"
                            style="appearance: none; background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%2394a3b8%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22/%3E%3C/svg%3E'); background-repeat: no-repeat; background-position: right 1rem center; background-size: 0.65rem auto; padding-right: 2.5rem;">
                            <option value="">-- Seleccionar Semestre --</option>
                            <?php for ($i = 1; $i <= 10; $i++)
                                echo "<option value='$i'>Semestre $i</option>"; ?>
                        </select>
                    </div>
                </div>

                <div id="codigo-area" class="input-group"
                    style="display: none; border: 1px dashed var(--secondary); padding: 15px; border-radius: 12px; background: rgba(99, 102, 241, 0.05);">
                    <label class="input-label" style="color: var(--primary);">Código de Acceso Docente</label>
                    <input type="password" name="codigo_docente" class="input-field"
                        placeholder="Clave de autorización">
                </div>

                <div class="input-group">
                    <label class="input-label">Contraseña</label>
                    <div class="input-wrapper">
                        <input type="password" name="password" id="password" class="input-field"
                            placeholder="Mínimo 8 caracteres" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('password', this)">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>

                    
            <script>
                    function togglePassword(inputId, btn) {
                        const input = document.getElementById(inputId);
                        const icon = btn.querySelector('i');
                        if (input.type === 'password') {
                            input.type = 'text';
                            icon.classList.remove('fa-eye');
                            icon.classList.add('fa-eye-slash');
                        } else {
                            input.type = 'password';
                            icon.classList.remove('fa-eye-slash');
                            icon.classList.add('fa-eye');
                        }
                    }
                </script>


                <button type="submit" class="btn btn-primary" style="width: 100%; height: 50px; margin-top: 10px;">
                    Crear Cuenta <i class="fa-solid fa-user-check" style="margin-left: 8px;"></i>
                </button>
            </form>

            <div style="margin: 25px 0; border-top: 1px solid var(--glass-border);"></div>

            <p style="font-size: 0.9rem; color: var(--text-muted);">
                ¿Ya eres parte de Unicali? <a href="login.php"
                    style="color: var(--primary); font-weight: 600; text-decoration: none; margin-left: 5px;">Inicia
                    Sesión</a>
            </p>
        </div>
    </div>
</body>

</html>






