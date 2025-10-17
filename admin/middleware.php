<?php
session_start();
if (empty($_SESSION['user'])) {
  header('Location: /login.php');
  exit;
}
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../config.php';
csrf_validate();