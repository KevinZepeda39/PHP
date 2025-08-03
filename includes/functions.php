<?php
// Función para validar datos de entrada
function validarDatos($dato) {
    $dato = trim($dato);
    $dato = stripslashes($dato);
    $dato = htmlspecialchars($dato);
    return $dato;
}

// Función para verificar si un usuario está logueado
function estaLogueado() {
    return isset($_SESSION['idUsuario']);
}

// Función para redirigir al login si no está logueado
function verificarLogin() {
    if (!estaLogueado()) {
        header("Location: login.php");
        exit();
    }
}

// Función para verificar si un usuario tiene un rol específico
function tieneRol($conn, $idUsuario, $rolBuscado) {
    $query = "SELECT r.nombreRol FROM usuario_rol ur
              INNER JOIN roles r ON ur.idRol = r.idRol
              WHERE ur.idUsuario = ? AND r.nombreRol = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "is", $idUsuario, $rolBuscado);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    return mysqli_num_rows($resultado) > 0;
}

// Función para obtener la fecha en formato legible
function formatoFecha($fecha) {
    $timestamp = strtotime($fecha);
    return date('d/m/Y H:i', $timestamp);
}

// Función para contar comentarios de una comunidad
function contarComentarios($conn, $idComunidad) {
    $query = "SELECT COUNT(*) as total FROM comentarios WHERE idComunidad = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $idComunidad);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $fila = mysqli_fetch_assoc($resultado);

    return $fila['total'];
}

// Función para generar un token CSRF
function generarTokenCSRF() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Función para verificar token CSRF
function verificarTokenCSRF($token) {
    return isset($_SESSION['csrf_token']) && $token === $_SESSION['csrf_token'];
}

// Función para generar avatar con la primera letra del nombre
function generarAvatar($nombre, $tamaño = 'md') {
    $primeraLetra = mb_substr($nombre, 0, 1, 'UTF-8');
    $claseBg = 'bg-letter-' . strtolower($primeraLetra);
    $claseSize = 'avatar-letter-' . $tamaño;
    
    return '<div class="avatar-letter ' . $claseSize . ' ' . $claseBg . '">' . $primeraLetra . '</div>';
}

// Función para verificar si un usuario es propietario de un comentario o comunidad
function esPropietario($conn, $idUsuario, $id, $tipo = 'comentario') {
    if ($tipo == 'comentario') {
        $query = "SELECT idUsuario FROM comentarios WHERE idComentario = ?";
    } elseif ($tipo == 'comunidad') {
        $query = "SELECT idUsuario FROM comunidad WHERE idComunidad = ?";
    } else {
        return false;
    }

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($resultado) == 0) {
        return false;
    }

    $fila = mysqli_fetch_assoc($resultado);
    return $fila['idUsuario'] == $idUsuario;
}
?>
<?php