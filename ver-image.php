<?php
// ver-imagen.php
require_once 'includes/conexion.php';
require_once 'includes/models/reporte.php';

if (isset($_GET['id'])) {
    $idReporte = (int)$_GET['id'];
    $reporteModel = new Reporte($conn);
    
    $imagen = $reporteModel->obtenerImagen($idReporte);
    
    if ($imagen && $imagen['imagen']) {
        header("Content-Type: " . $imagen['tipoImagen']);
        echo $imagen['imagen'];
        exit;
    }
}

// Si no hay imagen, mostrar una imagen por defecto
header("Content-Type: image/png");
readfile("assets/img/no-image.png");
?>