<?php
// ============================================================
//  FUNCIONES HELPER
//  Control de Seguridad Vehicular — CONALCA
// ============================================================

/**
 * Responde con JSON y código HTTP, luego termina.
 */
function responderJSON($data, $codigo = 200) {
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Obtiene el cuerpo JSON de la petición POST/PUT.
 * Retorna array asociativo o responde 400 si es inválido.
 */
function obtenerBodyJSON() {
    $body = file_get_contents('php://input');
    $data = json_decode($body, true);

    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        responderJSON(['error' => 'JSON inválido en el cuerpo de la petición'], 400);
    }

    return $data;
}

/**
 * Sanitiza un string: trim + htmlspecialchars.
 */
function sanitizar($valor) {
    return htmlspecialchars(trim($valor), ENT_QUOTES, 'UTF-8');
}

/**
 * Valida que los campos requeridos estén presentes en $data.
 * Retorna array de campos faltantes (vacío si todo OK).
 */
function validarCampos($data, $requeridos) {
    $faltantes = [];
    foreach ($requeridos as $campo) {
        if (!isset($data[$campo]) || (is_string($data[$campo]) && trim($data[$campo]) === '')) {
            $faltantes[] = $campo;
        }
    }
    return $faltantes;
}

/**
 * Obtiene el método HTTP de la petición.
 */
function getMetodo() {
    return $_SERVER['REQUEST_METHOD'];
}
