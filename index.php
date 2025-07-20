<?php
// index.php

// Colores ANSI
define("COLOR_RESET", "\033[0m");
define("COLOR_CYAN", "\033[0;36m");
define("COLOR_GREEN", "\033[0;32m");
define("COLOR_YELLOW", "\033[1;33m");
define("COLOR_RED", "\033[0;31m");
define("COLOR_GRAY", "\033[1;30m");
define("COLOR_BOLD", "\033[1m");

// Mostrar el encabezado bonito
function showHeader() {
  echo COLOR_CYAN;
  echo "=========================================\n";
  echo "         " . COLOR_BOLD . "PHP DevKit CLI by PIRULUG" . COLOR_RESET . COLOR_CYAN . "\n";
  echo "=========================================\n\n";
  echo COLOR_RESET;
}

// Función para mostrar el menú
function showMenu($directories) {
  echo COLOR_YELLOW . "Selecciona una carpeta de proyecto:\n" . COLOR_RESET;
  foreach ($directories as $index => $dir) {
    printf("  [%2d] %s\n", $index, COLOR_GREEN . $dir . COLOR_RESET);
  }
  echo "  [ 0] " . COLOR_RED . "Salir" . COLOR_RESET . "\n\n";
}

// Listar directorios
function listDirectories($path) {
  $directories = array_filter(glob($path . '/*'), 'is_dir');
  $directories = array_map('basename', $directories);
  return array_combine(range(1, count($directories)), $directories);
}

// Ejecutar el script principal de la carpeta seleccionada
function executeCommand($selectedDir) {
  $scriptPath = __DIR__ . "/$selectedDir/main.php";

  echo COLOR_GRAY . ">> Ejecutando $selectedDir/main.php ..." . COLOR_RESET . "\n\n";

  if (file_exists($scriptPath)) {
    require $scriptPath;
  } else {
    echo COLOR_RED . "Error: No se encuentra 'main.php' en '$selectedDir'." . COLOR_RESET . "\n";
  }

  echo "\n" . COLOR_GRAY . "-----------------------------------------\n" . COLOR_RESET;
  echo COLOR_YELLOW . "Presiona Enter para continuar..." . COLOR_RESET;
  fgets(STDIN);
}

// Directorio base
$basePath = __DIR__;

do {
  $directories = listDirectories($basePath);
  showHeader();
  showMenu($directories);

  echo COLOR_CYAN . "Selecciona un número > " . COLOR_RESET;
  $input = trim(fgets(STDIN));

  if (is_numeric($input)) {
    $choice = intval($input);

    if ($choice === 0) {
      echo COLOR_RED . "Saliendo del DevKit...\n" . COLOR_RESET;
      exit;
    } elseif (isset($directories[$choice])) {
      $selectedDir = $directories[$choice];
      executeCommand($selectedDir);
    } else {
      echo COLOR_RED . "Opción inválida. Intenta nuevamente.\n" . COLOR_RESET;
      sleep(1);
    }
  } else {
    echo COLOR_RED . "Entrada no válida. Debes ingresar un número.\n" . COLOR_RESET;
    sleep(1);
  }

  echo "\n";
} while (true);
