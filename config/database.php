<?php
// PDO connection
function getConnection() {
    try {
        return new PDO("mysql:host=localhost;dbname=gestion_etudiants", "root", "");
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

function getAllFilieres() {
    try {
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT * FROM filiere ORDER BY filiere");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        die("Error fetching filieres: " . $e->getMessage());
    }
}
?>
