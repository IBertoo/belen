<?php
// Lee variables de entorno inyectadas por docker-compose
$CONFIG = [
  'db_host' => getenv('DB_HOST') ?: 'sql206.infinityfree.com',
  'db_name' => getenv('DB_NAME') ?: 'if0_40132648_catalogo',
  'db_user' => getenv('DB_USER') ?: 'if0_40132648',
  'db_pass' => getenv('DB_PASSWORD') ?: 'iAmPprFD1Uo',
  'app_name' => 'Catálogo',
];
