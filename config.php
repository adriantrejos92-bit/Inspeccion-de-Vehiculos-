<?php
// ============================================================
//  CONFIGURACIÓN DE BASE DE DATOS
//  Control de Seguridad Vehicular — CONALCA
//  Lee de variables de entorno (Render) o usa defaults (Laragon)
// ============================================================

// Render provee DATABASE_URL como: postgres://user:pass@host/dbname
$database_url = getenv('DATABASE_URL');

if ($database_url) {
    // Producción (Render — PostgreSQL)
    $parsed = parse_url($database_url);
    define('DB_DRIVER',  'pgsql');
    define('DB_HOST',    $parsed['host']);
    define('DB_PORT',    $parsed['port'] ?? 5432);
    define('DB_NAME',    ltrim($parsed['path'], '/'));
    define('DB_USER',    $parsed['user']);
    define('DB_PASS',    $parsed['pass']);
    define('DB_CHARSET', 'utf8');
} else {
    // Local (Laragon — MySQL)
    define('DB_DRIVER',  'mysql');
    define('DB_HOST',    'localhost');
    define('DB_PORT',    3306);
    define('DB_NAME',    'control_seguridad');
    define('DB_USER',    'root');
    define('DB_PASS',    '');
    define('DB_CHARSET', 'utf8mb4');
}
