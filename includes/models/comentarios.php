<?php
// Este archivo debe guardarse en includes/models/comentarios.php

/**
 * Clase para manejar los comentarios de las comunidades
 * Adaptada para usar la estructura de base de datos existente
 */
class Comentarios {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Añadir un nuevo comentario
     */
    public function crear($idComunidad, $idUsuario, $comentario) {
        $idComunidad = (int)$idComunidad;
        $idUsuario = (int)$idUsuario;
        $comentario = $this->conn->real_escape_string($comentario);
        
        // Insertar comentario
        $sql = "INSERT INTO comentarios (idComunidad, idUsuario, comentario, fechaComentario) 
                VALUES ($idComunidad, $idUsuario, '$comentario', NOW())";
        
        if ($this->conn->query($sql)) {
            $idComentario = $this->conn->insert_id;
            
            // Añadir relación usuario-comentario como autor
            $sqlRelacion = "INSERT INTO usuario_comentario (idUsuario, idComentario, rolEnComentario)
                          VALUES ($idUsuario, $idComentario, 'autor')";
            $this->conn->query($sqlRelacion);
            
            return $idComentario;
        }
        
        return false;
    }
    
    /**
     * Obtener comentarios de una comunidad
     */
    public function obtenerComentarios($idComunidad, $pagina = 1, $porPagina = 20) {
        $idComunidad = (int)$idComunidad;
        $inicio = ($pagina - 1) * $porPagina;
        
        $sql = "SELECT c.*, u.nombre as nombreUsuario 
                FROM comentarios c
                JOIN usuarios u ON c.idUsuario = u.idUsuario
                WHERE c.idComunidad = $idComunidad
                ORDER BY c.fechaComentario DESC
                LIMIT $inicio, $porPagina";
        
        $result = $this->conn->query($sql);
        $comentarios = [];
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $comentarios[] = $row;
            }
        }
        
        // Invertir para mostrar más antiguos primero
        $comentarios = array_reverse($comentarios);
        
        // Obtener total para paginación
        $sqlTotal = "SELECT COUNT(*) as total FROM comentarios WHERE idComunidad = $idComunidad";
        $totalResult = $this->conn->query($sqlTotal);
        $total = $totalResult->fetch_assoc()['total'];
        
        return [
            'comentarios' => $comentarios,
            'total' => $total,
            'paginas' => ceil($total / $porPagina)
        ];
    }
    
    /**
     * Eliminar un comentario
     */
    public function eliminar($idComentario, $idUsuario) {
        $idComentario = (int)$idComentario;
        $idUsuario = (int)$idUsuario;
        
        // Verificar si el usuario es el autor del comentario o un moderador
        $sql = "SELECT c.*, uc.rolEnComentario  
                FROM comentarios c
                LEFT JOIN usuario_comentario uc ON c.idComentario = uc.idComentario AND uc.idUsuario = $idUsuario
                WHERE c.idComentario = $idComentario";
        
        $result = $this->conn->query($sql);
        
        if ($result->num_rows > 0) {
            $comentario = $result->fetch_assoc();
            
            // Si es el autor o moderador, puede eliminar
            if ($comentario['idUsuario'] == $idUsuario || 
                $comentario['rolEnComentario'] == 'moderador') {
                
                // Eliminar las relaciones
                $this->conn->query("DELETE FROM usuario_comentario WHERE idComentario = $idComentario");
                
                // Eliminar el comentario
                return $this->conn->query("DELETE FROM comentarios WHERE idComentario = $idComentario");
            }
        }
        
        return false;
    }
    
    /**
     * Verificar si un usuario es autor o moderador de un comentario
     */
    public function puedeModificar($idComentario, $idUsuario) {
        $idComentario = (int)$idComentario;
        $idUsuario = (int)$idUsuario;
        
        $sql = "SELECT c.idUsuario, uc.rolEnComentario
                FROM comentarios c 
                LEFT JOIN usuario_comentario uc ON c.idComentario = uc.idComentario AND uc.idUsuario = $idUsuario
                WHERE c.idComentario = $idComentario";
        
        $result = $this->conn->query($sql);
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return ($row['idUsuario'] == $idUsuario || $row['rolEnComentario'] == 'moderador');
        }
        
        return false;
    }
}
?>