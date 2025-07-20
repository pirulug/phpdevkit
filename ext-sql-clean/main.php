<?php
// ==============================
// CONFIGURACIÓN DE ERRORES PHP
// ==============================
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ==============================
// COLORES PARA LA TERMINAL
// ==============================
if (!defined('COLOR_RESET')) define('COLOR_RESET', "\033[0m");
if (!defined('COLOR_CYAN')) define('COLOR_CYAN', "\033[36m");
if (!defined('COLOR_GREEN')) define('COLOR_GREEN', "\033[32m");
if (!defined('COLOR_YELLOW')) define('COLOR_YELLOW', "\033[33m");
if (!defined('COLOR_RED')) define('COLOR_RED', "\033[31m");
if (!defined('COLOR_BOLD')) define('COLOR_BOLD', "\033[1m");

// ==============================
// DATOS POR DEFECTO
// ==============================
$defaultHost     = 'localhost';
$defaultUser     = 'root';
$defaultPassword = '';

// ==============================
// INGRESO DE CREDENCIALES
// ==============================
echo COLOR_CYAN . "╔══════════════════════════════════════╗\n";
echo "║       Generador de Backup SQL        ║\n";
echo "║               By Pirulug             ║\n";
echo "╚══════════════════════════════════════╝\n" . COLOR_RESET;

echo COLOR_YELLOW . "Host de DB (por defecto: $defaultHost): " . COLOR_RESET;
$host = trim(fgets(STDIN));
$host = empty($host) ? $defaultHost : $host;

echo COLOR_YELLOW . "Usuario de DB (por defecto: $defaultUser): " . COLOR_RESET;
$dbUser = trim(fgets(STDIN));
$dbUser = empty($dbUser) ? $defaultUser : $dbUser;

echo COLOR_YELLOW . "Contraseña (enter si está vacía): " . COLOR_RESET;
$dbPassword = trim(fgets(STDIN));
$dbPassword = empty($dbPassword) ? $defaultPassword : $dbPassword;

try {
  $pdo = new PDO("mysql:host=$host", $dbUser, $dbPassword);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // ==============================
  // OBTENER BASES DE DATOS
  // ==============================
  $stmt      = $pdo->query("SHOW DATABASES");
  $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);

  if (empty($databases)) {
    die(COLOR_RED . "No se encontraron bases de datos.\n" . COLOR_RESET);
  }

  echo "\n" . COLOR_GREEN . "Bases de datos disponibles:\n" . COLOR_RESET;
  foreach ($databases as $index => $database) {
    echo COLOR_CYAN . "  [" . ($index + 1) . "] " . COLOR_RESET . "$database\n";
  }

  echo COLOR_YELLOW . "Número de la base de datos: " . COLOR_RESET;
  $choice = trim(fgets(STDIN));

  if (!is_numeric($choice) || $choice < 1 || $choice > count($databases)) {
    die(COLOR_RED . "Selección inválida.\n" . COLOR_RESET);
  }

  $dbName = $databases[$choice - 1];

  $pdo = new PDO("mysql:host=$host;dbname=$dbName", $dbUser, $dbPassword);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // ==============================
  // OBTENER TABLAS
  // ==============================
  $stmt   = $pdo->query("SHOW TABLES");
  $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

  if (empty($tables)) {
    die(COLOR_RED . "No se encontraron tablas en la base de datos.\n" . COLOR_RESET);
  }

  $backupFile = "$dbName-clean.sql";
  $fileHandle = fopen($backupFile, 'w');

  if (!$fileHandle) {
    die(COLOR_RED . "No se pudo crear el archivo de backup.\n" . COLOR_RESET);
  }

  echo COLOR_GREEN . "\nGenerando estructura limpia de tablas:\n" . COLOR_RESET;

  // ==============================
  // PROCESAR TABLAS
  // ==============================
  foreach ($tables as $table) {
    echo COLOR_CYAN . "  - $table\n" . COLOR_RESET;

    $stmt      = $pdo->query("SHOW COLUMNS FROM `$table`");
    $columns   = [];
    $indexInfo = [];

    $indexStmt = $pdo->query("SHOW INDEX FROM `$table`");
    while ($idx = $indexStmt->fetch(PDO::FETCH_ASSOC)) {
      $col = $idx['Column_name'];
      if ($idx['Key_name'] === 'PRIMARY') {
        $indexInfo[$col] = 'PRIMARY KEY';
      } elseif ($idx['Non_unique'] == 0) {
        $indexInfo[$col] = 'UNIQUE';
      } elseif (!isset($indexInfo[$col])) {
        $indexInfo[$col] = 'INDEX';
      }
    }

    $finalSQL = "-- Estructura de la tabla $table\n";
    $finalSQL .= "CREATE TABLE $table (\n";

    while ($col = $stmt->fetch(PDO::FETCH_OBJ)) {
      $line = "{$col->Field} {$col->Type}";
      if ($col->Null === 'NO') {
        $line .= " NOT NULL";
      }
      if ($col->Default !== null) {
        $line .= " DEFAULT '{$col->Default}'";
      }
      if ($col->Extra) {
        $line .= " {$col->Extra}";
      }

      if (isset($indexInfo[$col->Field])) {
        $type = $indexInfo[$col->Field];
        if ($type === 'PRIMARY KEY') {
          $line .= " PRIMARY KEY";
        } elseif ($type === 'UNIQUE') {
          $line .= " UNIQUE";
        } elseif ($type === 'INDEX') {
          $line .= " INDEX";
        }
      }

      $columns[] = $line;
    }

    $finalSQL .= "  " . implode(",\n  ", $columns) . "\n";
    $finalSQL .= ");\n\n";

    fwrite($fileHandle, $finalSQL);
  }

  fclose($fileHandle);

  echo COLOR_GREEN . "\n✅ Copia de seguridad generada exitosamente: $backupFile\n" . COLOR_RESET;

} catch (PDOException $e) {
  die(COLOR_RED . "Error de conexión: " . $e->getMessage() . "\n" . COLOR_RESET);
}
