<?php
// includes/models/reporte.php

class Reporte {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function crear($titulo, $descripcion, $idUsuario, $ubicacion = null, $tipo = 'general', $urgente = false, $imagen = null) {
        // Preparar datos
        $titulo = $this->conn->real_escape_string($titulo);
        $descripcion = $this->conn->real_escape_string($descripcion);
        $idUsuario = (int)$idUsuario;
        $tipo = $this->conn->real_escape_string($tipo);
        $urgente = $urgente ? 1 : 0;
        
        // Manejar imagen si existe
        $nombreImagen = null;
        $tipoImagen = null;
        $imagenBlob = null;
        
        if ($imagen && $imagen['error'] === UPLOAD_ERR_OK) {
            $nombreImagen = $this->conn->real_escape_string($imagen['name']);
            $tipoImagen = $this->conn->real_escape_string($imagen['type']);
            $imagenBlob = file_get_contents($imagen['tmp_name']);
            $imagenBlob = $this->conn->real_escape_string($imagenBlob);
        }
        
        // Iniciar transacción
        $this->conn->begin_transaction();
        
        try {
            // Insertar reporte
            $sql = "INSERT INTO reportes (titulo, descripcion, imagen, nombreImagen, tipoImagen, fechaCreacion) 
                    VALUES ('$titulo', '$descripcion', " . 
                    ($imagenBlob ? "'$imagenBlob'" : "NULL") . ", " .
                    ($nombreImagen ? "'$nombreImagen'" : "NULL") . ", " .
                    ($tipoImagen ? "'$tipoImagen'" : "NULL") . ", NOW())";
            
            if (!$this->conn->query($sql)) {
                throw new Exception("Error al crear reporte: " . $this->conn->error);
            }
            
            $idReporte = $this->conn->insert_id;
            
            // Crear relación usuario_reporte
            $sql = "INSERT INTO usuario_reporte (idUsuario, idReporte, rolEnReporte) 
                    VALUES ($idUsuario, $idReporte, 'creador')";
            
            if (!$this->conn->query($sql)) {
                throw new Exception("Error al crear relación usuario_reporte: " . $this->conn->error);
            }
            
            $this->conn->commit();
            return $idReporte;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log($e->getMessage());
            return false;
        }
    }
    
    public function obtener($idReporte) {
        $idReporte = (int)$idReporte;
        
        $sql = "SELECT r.*, u.nombre as nombreCreador 
                FROM reportes r 
                INNER JOIN usuario_reporte ur ON r.idReporte = ur.idReporte 
                INNER JOIN usuarios u ON ur.idUsuario = u.idUsuario 
                WHERE r.idReporte = $idReporte AND ur.rolEnReporte = 'creador'";
        
        $result = $this->conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return false;
    }
    
    public function listar($filtros = []) {
        $sql = "SELECT r.*, u.nombre as nombreCreador 
                FROM reportes r 
                INNER JOIN usuario_reporte ur ON r.idReporte = ur.idReporte 
                INNER JOIN usuarios u ON ur.idUsuario = u.idUsuario 
                WHERE ur.rolEnReporte = 'creador'";
        
        // Aplicar filtros si existen
        if (isset($filtros['idUsuario'])) {
            $idUsuario = (int)$filtros['idUsuario'];
            $sql .= " AND ur.idUsuario = $idUsuario";
        }
        
        if (isset($filtros['desde'])) {
            $desde = $this->conn->real_escape_string($filtros['desde']);
            $sql .= " AND r.fechaCreacion >= '$desde'";
        }
        
        if (isset($filtros['hasta'])) {
            $hasta = $this->conn->real_escape_string($filtros['hasta']);
            $sql .= " AND r.fechaCreacion <= '$hasta'";
        }
        
        $sql .= " ORDER BY r.fechaCreacion DESC";
        
        if (isset($filtros['limite'])) {
            $limite = (int)$filtros['limite'];
            $offset = isset($filtros['offset']) ? (int)$filtros['offset'] : 0;
            $sql .= " LIMIT $limite OFFSET $offset";
        }
        
        $result = $this->conn->query($sql);
        
        if ($result) {
            $reportes = [];
            while ($row = $result->fetch_assoc()) {
                $reportes[] = $row;
            }
            return $reportes;
        }
        
        return [];
    }
    
    public function actualizar($idReporte, $titulo, $descripcion) {
        $idReporte = (int)$idReporte;
        $titulo = $this->conn->real_escape_string($titulo);
        $descripcion = $this->conn->real_escape_string($descripcion);
        
        $sql = "UPDATE reportes SET 
                titulo = '$titulo', 
                descripcion = '$descripcion' 
                WHERE idReporte = $idReporte";
        
        return $this->conn->query($sql);
    }
    
    public function eliminar($idReporte) {
        $idReporte = (int)$idReporte;
        
        // Las eliminaciones en cascada se encargarán de las tablas relacionadas
        $sql = "DELETE FROM reportes WHERE idReporte = $idReporte";
        
        return $this->conn->query($sql);
    }
    
    public function obtenerImagen($idReporte) {
        $idReporte = (int)$idReporte;
        
        $sql = "SELECT imagen, nombreImagen, tipoImagen 
                FROM reportes 
                WHERE idReporte = $idReporte";
        
        $result = $this->conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return false;
    }
    
    public function buscar($termino) {
        $termino = $this->conn->real_escape_string($termino);
        
        $sql = "SELECT r.*, u.nombre as nombreCreador 
                FROM reportes r 
                INNER JOIN usuario_reporte ur ON r.idReporte = ur.idReporte 
                INNER JOIN usuarios u ON ur.idUsuario = u.idUsuario 
                WHERE ur.rolEnReporte = 'creador'
                AND (r.titulo LIKE '%$termino%' OR r.descripcion LIKE '%$termino%')
                ORDER BY r.fechaCreacion DESC";
        
        $result = $this->conn->query($sql);
        
        if ($result) {
            $reportes = [];
            while ($row = $result->fetch_assoc()) {
                $reportes[] = $row;
            }
            return $reportes;
        }
        
        return [];
    }
    
    public function esCreador($idReporte, $idUsuario) {
        $idReporte = (int)$idReporte;
        $idUsuario = (int)$idUsuario;
        
        $sql = "SELECT * FROM usuario_reporte 
                WHERE idReporte = $idReporte 
                AND idUsuario = $idUsuario 
                AND rolEnReporte = 'creador'";
        
        $result = $this->conn->query($sql);
        
        return ($result && $result->num_rows > 0);
    }
}
?><?php
// includes/reporte.php