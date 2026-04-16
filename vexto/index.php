<?php
// Redirige al punto de entrada público de la aplicación.
// Esto permite acceder con http://localhost/vexto/ sin mostrar el listado de directorio.
header('Location: public/index.php');
exit;
