<?php
// Lee variables de entorno inyectadas por docker-compose
$CONFIG = [
  'db_host' => getenv('DB_HOST') ?: '',
  'db_name' => getenv('DB_NAME') ?: '',
  'db_user' => getenv('DB_USER') ?: '',
  'db_pass' => getenv('DB_PASSWORD') ?: '',
  'app_name' => 'Catálogo',
];
