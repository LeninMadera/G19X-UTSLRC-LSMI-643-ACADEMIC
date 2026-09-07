<?php
/**
 * Punto de entrada del proyecto.
 * Redirige al login o al dashboard segun la sesion.
 */
require_once __DIR__ . '/includes/functions.php';

if (currentUser() !== null) {
    header('Location: ' . APP_URL . '/views/dashboard.php');
} else {
    header('Location: ' . APP_URL . '/views/login.php');
}
exit;