<?php
require 'conexion.php';

if (!isset($_SESSION['idUsuario'])) {
    header("Location: ../login.php");
    exit();
}

// Para rutas de admin
if (isset($admin_required) && $admin_required) {
    if (!isset($_SESSION['admin'])) {
        header("Location: ../index.php");
        exit();
    }
}
?>