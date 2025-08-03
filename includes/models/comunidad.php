<?php
// Este archivo debe guardarse en includes/models/comunidad.php

/**
 * Clase para manejar las comunidades
 * Adaptada para usar la estructura de base de datos existente
 */
class Comunidad {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Crear una nueva comunidad
     */
    public function crear($titulo, $descripcion, $idUsuario) {
        $titulo = $this->conn->real_escape_string($titulo);
        $descripcion = $this->conn->real_escape_string($descripcion);
        $idUsuario = (int)$idUsuario;
        
        $sql = "INSERT INTO comunidad (idUsuario, titulo, descripcion, fechaCreacion) 
                VALUES ($idUsuario, '$titulo', '$descripcion', NOW())";
        
        if ($this->conn->query($sql)) {
            return $this->conn->insert_id;
        }
        
        return false;
    }
    
    /**
     * Obtener todas las comunidades con paginación
     */
    public function obtenerComunidades($pagina = 1, $porPagina = 10) {
        $inicio = ($pagina - 1) * $porPagina;
        
        $sql = "SELECT c.*, u.nombre as nombreCreador 
                FROM comunidad c
                LEFT JOIN usuarios u ON c.idUsuario = u.idUsuario
                ORDER BY c.fechaCreacion DESC
                LIMIT $inicio, $porPagina";
        
        $result = $this->conn->query($sql);
        $comunidades = [];
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Obtener cantidad de comentarios por comunidad
                $sqlComentarios = "SELECT COUNT(*) as total FROM comentarios WHERE idComunidad = " . $row['idComunidad'];
                $resultComentarios = $this->conn->query($sqlComentarios);
                $row['totalComentarios'] = $resultComentarios->fetch_assoc()['total'];
                
                $comunidades[] = $row;
            }
        }
        
        // Obtener total para paginación
        $sqlTotal = "SELECT COUNT(*) as total FROM comunidad";
        $totalResult = $this->conn->query($sqlTotal);
        $total = $totalResult->fetch_assoc()['total'];
        
        return [
            'comunidades' => $comunidades,
            'total' => $total,
            'paginas' => ceil($total / $porPagina)
        ];
    }
    
    /**
     * Obtener comunidades creadas por un usuario
     */
    public function obtenerComunidadesUsuario($idUsuario) {
        $idUsuario = (int)$idUsuario;
        
        $sql = "SELECT c.*, u.nombre as nombreCreador 
                FROM comunidad c
                LEFT JOIN usuarios u ON c.idUsuario = u.idUsuario
                WHERE c.idUsuario = $idUsuario
                ORDER BY c.fechaCreacion DESC";
        
        $result = $this->conn->query($sql);
        $comunidades = [];
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Obtener cantidad de comentarios por comunidad
                $sqlComentarios = "SELECT COUNT(*) as total FROM comentarios WHERE idComunidad = " . $row['idComunidad'];
                $resultComentarios = $this->conn->query($sqlComentarios);
                $row['totalComentarios'] = $resultComentarios->fetch_assoc()['total'];
                
                $comunidades[] = $row;
            }
        }
        
        return $comunidades;
    }
    
    /**
     * Obtener una comunidad por ID
     */
    public function obtenerComunidad($idComunidad) {
        $idComunidad = (int)$idComunidad;
        
        $sql = "SELECT c.*, u.nombre as nombreCreador 
                FROM comunidad c
                LEFT JOIN usuarios u ON c.idUsuario = u.idUsuario
                WHERE c.idComunidad = $idComunidad";
        
        $result = $this->conn->query($sql);
        
        if ($result->num_rows > 0) {
            $comunidad = $result->fetch_assoc();
            
            // Obtener cantidad de comentarios
            $sqlComentarios = "SELECT COUNT(*) as total FROM comentarios WHERE idComunidad = $idComunidad";
            $resultComentarios = $this->conn->query($sqlComentarios);
            $comunidad['totalComentarios'] = $resultComentarios->fetch_assoc()['total'];
            
            return $comunidad;
        }
        
        return null;
    }
    
    /**
     * Comprobar si un usuario es el creador de una comunidad
     */
    public function esCreador($idComunidad, $idUsuario) {
        $idComunidad = (int)$idComunidad;
        $idUsuario = (int)$idUsuario;
        
        $sql = "SELECT * FROM comunidad 
                WHERE idComunidad = $idComunidad AND idUsuario = $idUsuario";
        
        $result = $this->conn->query($sql);
        
        return $result->num_rows > 0;
    }
    
    /**
     * Obtener comunidades con búsqueda
     */
    public function buscarComunidades($termino, $pagina = 1, $porPagina = 10) {
        $termino = $this->conn->real_escape_string($termino);
        $inicio = ($pagina - 1) * $porPagina;
        
        $sql = "SELECT c.*, u.nombre as nombreCreador 
                FROM comunidad c
                LEFT JOIN usuarios u ON c.idUsuario = u.idUsuario
                WHERE c.titulo LIKE '%$termino%' OR c.descripcion LIKE '%$termino%'
                ORDER BY c.fechaCreacion DESC
                LIMIT $inicio, $porPagina";
        
        $result = $this->conn->query($sql);
        $comunidades = [];
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Obtener cantidad de comentarios por comunidad
                $sqlComentarios = "SELECT COUNT(*) as total FROM comentarios WHERE idComunidad = " . $row['idComunidad'];
                $resultComentarios = $this->conn->query($sqlComentarios);
                $row['totalComentarios'] = $resultComentarios->fetch_assoc()['total'];
                
                $comunidades[] = $row;
            }
        }
        
        // Obtener total para paginación
        $sqlTotal = "SELECT COUNT(*) as total FROM comunidad 
                    WHERE titulo LIKE '%$termino%' OR descripcion LIKE '%$termino%'";
        $totalResult = $this->conn->query($sqlTotal);
        $total = $totalResult->fetch_assoc()['total'];
        
        return [
            'comunidades' => $comunidades,
            'total' => $total,
            'paginas' => ceil($total / $porPagina)
        ];
    }
    
    /**
     * Actualizar una comunidad
     */
    public function actualizar($idComunidad, $titulo, $descripcion) {
        $idComunidad = (int)$idComunidad;
        $titulo = $this->conn->real_escape_string($titulo);
        $descripcion = $this->conn->real_escape_string($descripcion);
        
        $sql = "UPDATE comunidad 
                SET titulo = '$titulo', descripcion = '$descripcion' 
                WHERE idComunidad = $idComunidad";
        
        return $this->conn->query($sql);
    }
    
    /**
     * Eliminar una comunidad
     */
    public function eliminar($idComunidad) {
        $idComunidad = (int)$idComunidad;
        
        // La eliminación en cascada de comentarios está configurada en la base de datos
        $sql = "DELETE FROM comunidad WHERE idComunidad = $idComunidad";
        
        return $this->conn->query($sql);
    }
}
?>