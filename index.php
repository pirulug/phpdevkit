<?php
// index.php

// Función para mostrar el menú de carpetas
function showMenu($directories) {
  echo "Selecciona una carpeta:\n";
  foreach ($directories as $index => $dir) {
    echo ($index) . ". $dir\n";
  }
  echo "0. Salir\n";
}

// Función para listar los directorios
function listDirectories($path) {
  $directories = array_filter(glob($path . '/*'), 'is_dir');
  return array_map('basename', $directories);
}

// Función para ejecutar el script de la carpeta seleccionada
function executeCommand($selectedDir) {
  $scriptPath = __DIR__ . "/$selectedDir/main.php";

  if (file_exists($scriptPath)) {
    require $scriptPath;
  } else {
    echo "Error: El archivo 'main.php' no se encuentra en la carpeta '$selectedDir'.\n";
  }
}

// Directorio base
$basePath = __DIR__;

do {
  // Listar las carpetas en el directorio base
  $directories = listDirectories($basePath);

  // Mostrar el menú
  showMenu($directories);

  // Obtener la selección del usuario
  echo "Selecciona un número: ";
  $input = trim(fgets(STDIN));

  // Ejecutar el script basado en la selección
  if (is_numeric($input)) {
    $choice = intval($input);
    if ($choice > 0 && $choice <= count($directories)) {
      $selectedDir = $directories[$choice]; // Ajustar el índice para comenzar en 0
      executeCommand($selectedDir);
    } elseif ($choice === 0) {
      echo "Saliendo...\n";
      exit;
    } else {
      echo "Selección inválida.\n";
    }
  } else {
    echo "Entrada no válida. Debes ingresar un número.\n";
  }

} while (true);
