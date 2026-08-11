<?php
// ============================================================
//  CONEXIÓN PDO (MySQL local / PostgreSQL en Render)
//  Control de Seguridad Vehicular — CONALCA
// ============================================================

require_once __DIR__ . '/config.php';

function getConexion() {
    static $pdo = null;

    if ($pdo === null) {
        if (DB_DRIVER === 'pgsql') {
            $dsn = 'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME;
        } else {
            $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        }

        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);
            if (DB_DRIVER === 'pgsql') {
                $pdo->exec("SET client_encoding TO 'UTF8'");
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error de conexión a la base de datos']);
            exit;
        }
    }

    return $pdo;
}
