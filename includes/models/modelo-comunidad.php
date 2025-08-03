<?php
// archivo: includes/models/modelo-comunidad.php

class Comunidad {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function crear($titulo, $descripcion, $idUsuario, $categoria = null, $tags = null) {
        $titulo = $this->conn->real_escape_string($titulo);
        $descripcion = $this->conn->real_escape_string($descripcion);
        
        // Asegurarse de que la categoría sea válida
        $categoriasValidas = ['vecinos', 'seguridad', 'eventos', 'infraestructura', 'servicios', 'otros'];
        
        if ($categoria && in_array($categoria, $categoriasValidas)) {
            $categoria = $this->conn->real_escape_string($categoria);
        } else {
            $categoria = 'otros';
        }
        
        $tags = $tags ? $this->conn->real_escape_string($tags) : null;
        $idUsuario = (int)$idUsuario;
        
        $sql = "INSERT INTO comunidad (idUsuario, titulo, descripcion, categoria, tags, fechaCreacion) 
                VALUES ($idUsuario, '$titulo', '$descripcion', '$categoria', " .
                ($tags ? "'$tags'" : "NULL") . ", NOW())";
        
        if ($this->conn->query($sql)) {
            return $this->conn->insert_id;
        }
        
        // Para debug - imprime el error si hay alguno
        error_log("Error al crear comunidad: " . $this->conn->error);
        
        return false;
    }
    
    public function obtener($idComunidad) {
        $idComunidad = (int)$idComunidad;
        
        $sql = "SELECT c.*, u.nombre as nombreCreador 
                FROM comunidad c 
                INNER JOIN usuarios u ON c.idUsuario = u.idUsuario 
                WHERE c.idComunidad = $idComunidad";
        
        $result = $this->conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return false;
    }
    
    public function listar($categoria = null, $limite = 10, $offset = 0) {
        $limite = (int)$limite;
        $offset = (int)$offset;
        
        $sql = "SELECT c.*, u.nombre as nombreCreador,
                (SELECT COUNT(*) FROM comentarios WHERE idComunidad = c.idComunidad) as total_comentarios,
                (SELECT COUNT(*) FROM usuario_comunidad WHERE idComunidad = c.idComunidad) as total_miembros
                FROM comunidad c 
                INNER JOIN usuarios u ON c.idUsuario = u.idUsuario";
        
        if ($categoria) {
            $categoria = $this->conn->real_escape_string($categoria);
            $sql .= " WHERE c.categoria = '$categoria'";
        }
        
        $sql .= " ORDER BY c.fechaCreacion DESC LIMIT $limite OFFSET $offset";
        
        $result = $this->conn->query($sql);
        
        if ($result) {
            $comunidades = [];
            while ($row = $result->fetch_assoc()) {
                $comunidades[] = $row;
            }
            return $comunidades;
        }
        
        return [];
    }
    
    public function actualizar($idComunidad, $titulo, $descripcion, $categoria = null, $tags = null) {
        $idComunidad = (int)$idComunidad;
        $titulo = $this->conn->real_escape_string($titulo);
        $descripcion = $this->conn->real_escape_string($descripcion);
        
        $sql = "UPDATE comunidad SET 
                titulo = '$titulo', 
                descripcion = '$descripcion'";
        
        if ($categoria) {
            $categoriasValidas = ['vecinos', 'seguridad', 'eventos', 'infraestructura', 'servicios', 'otros'];
            if (in_array($categoria, $categoriasValidas)) {
                $categoria = $this->conn->real_escape_string($categoria);
                $sql .= ", categoria = '$categoria'";
            }
        }
        
        if ($tags !== null) {
            $tags = $this->conn->real_escape_string($tags);
            $sql .= ", tags = '$tags'";
        }
        
        $sql .= " WHERE idComunidad = $idComunidad";
        
        return $this->conn->query($sql);
    }
    
    public function eliminar($idComunidad) {
        $idComunidad = (int)$idComunidad;
        
        // Primero eliminar registros relacionados
        $this->conn->query("DELETE FROM comentarios WHERE idComunidad = $idComunidad");
        $this->conn->query("DELETE FROM usuario_comunidad WHERE idComunidad = $idComunidad");
        
        // Luego eliminar la comunidad
        return $this->conn->query("DELETE FROM comunidad WHERE idComunidad = $idComunidad");
    }
    
    public function unirseAComunidad($idComunidad, $idUsuario) {
        $idComunidad = (int)$idComunidad;
        $idUsuario = (int)$idUsuario;
        
        // Verificar si ya es miembro
        $sql = "SELECT * FROM usuario_comunidad 
                WHERE idComunidad = $idComunidad AND idUsuario = $idUsuario";
        
        $result = $this->conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            return false; // Ya es miembro
        }
        
        // Unirse a la comunidad
        $sql = "INSERT INTO usuario_comunidad (idComunidad, idUsuario, fechaUnion) 
                VALUES ($idComunidad, $idUsuario, NOW())";
        
        return $this->conn->query($sql);
    }
    
    public function salirDeComunidad($idComunidad, $idUsuario) {
        $idComunidad = (int)$idComunidad;
        $idUsuario = (int)$idUsuario;
        
        $sql = "DELETE FROM usuario_comunidad 
                WHERE idComunidad = $idComunidad AND idUsuario = $idUsuario";
        
        return $this->conn->query($sql);
    }
    
    public function esMiembro($idComunidad, $idUsuario) {
        $idComunidad = (int)$idComunidad;
        $idUsuario = (int)$idUsuario;
        
        $sql = "SELECT * FROM usuario_comunidad 
                WHERE idComunidad = $idComunidad AND idUsuario = $idUsuario";
        
        $result = $this->conn->query($sql);
        
        return ($result && $result->num_rows > 0);
    }
    
    public function obtenerMiembros($idComunidad) {
        $idComunidad = (int)$idComunidad;
        
        $sql = "SELECT u.*, uc.fechaUnion 
                FROM usuario_comunidad uc 
                INNER JOIN usuarios u ON uc.idUsuario = u.idUsuario 
                WHERE uc.idComunidad = $idComunidad 
                ORDER BY uc.fechaUnion DESC";
        
        $result = $this->conn->query($sql);
        
        if ($result) {
            $miembros = [];
            while ($row = $result->fetch_assoc()) {
                $miembros[] = $row;
            }
            return $miembros;
        }
        
        return [];
    }
    
    public function buscar($termino) {
        $termino = $this->conn->real_escape_string($termino);
        
        $sql = "SELECT c.*, u.nombre as nombreCreador,
                (SELECT COUNT(*) FROM comentarios WHERE idComunidad = c.idComunidad) as total_comentarios,
                (SELECT COUNT(*) FROM usuario_comunidad WHERE idComunidad = c.idComunidad) as total_miembros
                FROM comunidad c 
                INNER JOIN usuarios u ON c.idUsuario = u.idUsuario
                WHERE c.titulo LIKE '%$termino%' 
                OR c.descripcion LIKE '%$termino%' 
                OR c.tags LIKE '%$termino%'
                ORDER BY c.fechaCreacion DESC";
        
        $result = $this->conn->query($sql);
        
        if ($result) {
            $comunidades = [];
            while ($row = $result->fetch_assoc()) {
                $comunidades[] = $row;
            }
            return $comunidades;
        }
        
        return [];
    }
}
?>