<?php
/**
 * inforemp-web-kit — Endpoint de autenticación.
 * Reemplaza al service.php monolítico de RDN para login/logout.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_usuario':
            // Paso 1: identificar usuario por contraseña.
            $row = auth_lookup_by_pass($_POST['pass'] ?? '');
            if ($row) ok(['id' => $row['id'], 'name' => $row['name']]);
            else fail('Usuario no encontrado', 401);
            break;

        case 'login':
            // Paso 2: validar credenciales completas.
            if (auth_login($_POST['id'] ?? 0, $_POST['name'] ?? '', $_POST['pass'] ?? '')) {
                ok(['redirect' => bu('/app/index.php')]);
            } else {
                fail('Credenciales inválidas', 401);
            }
            break;

        case 'logout':
            auth_logout();
            ok(['redirect' => bu('/app/login.php')]);
            break;

        default:
            fail('Acción inválida: ' . $action);
    }
} catch (Exception $e) {
    fail($e->getMessage(), 500);
}
