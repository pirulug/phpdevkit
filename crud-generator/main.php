<?php

function generateCRUD($table) {
  try {
    // Corrección de la cadena de conexión PDO
    $conn = new PDO("mysql:host=localhost;dbname=pirushort", "root", "");
    $conn->exec("set names utf8");
  } catch (PDOException $exception) {
    echo "Error de conexión: " . $exception->getMessage();
  }

  // Crear directorio con el nombre de la tabla
  $dir = __DIR__ . '/' . $table;
  if (!file_exists($dir)) {
    mkdir($dir, 0777, true);
  }

  // Obtener columnas de la tabla
  $query = $conn->prepare("DESCRIBE $table");
  $query->execute();
  $columns = $query->fetchAll(PDO::FETCH_COLUMN);

  // Crear el archivo create.php
  $create_file    = fopen("$dir/create.php", "w");
  $create_content = "<?php\n";
  $create_content .= "\n\n";
  $create_content .= "if (\$_POST) {\n\n";

  foreach (array_slice($columns, 1) as $column) {
    $create_content .= "    \$$column=\$" . "_POST['$column'];\n";
  }

  $create_content .= "\n";

  $create_content .= "    \$stmt = \$conn->prepare(\"INSERT INTO $table (" . implode(", ", array_slice($columns, 1)) . ") VALUES (:" . implode(", :", array_slice($columns, 1)) . ")\");\n";

  foreach (array_slice($columns, 1) as $column) {
    $create_content .= "    \$stmt->bindParam(':$column', \$$column);\n";
  }

  $create_content .= "\n    if (\$stmt->execute()) {\n";
  $create_content .= "        echo 'Registro creado exitosamente.';\n";
  $create_content .= "    } else {\n";
  $create_content .= "        echo 'Error al crear el registro.';\n";
  $create_content .= "    }\n";
  $create_content .= "}\n";
  $create_content .= "?>\n";
  fwrite($create_file, $create_content);
  fclose($create_file);

  // Crear el archivo read.php
  $read_file      = fopen("$dir/read.php", "w");
  $read_content   = "<?php\n";
  $create_content .= "\n\n";
  $read_content .= "\$stmt = \$conn->prepare(\"SELECT * FROM $table\");\n";
  $read_content .= "\$stmt->execute();\n";
  $read_content .= "\$result = \$stmt->fetchAll(PDO::FETCH_ASSOC);\n";
  $read_content .= "echo json_encode(\$result);\n";
  $read_content .= "?>\n";
  fwrite($read_file, $read_content);
  fclose($read_file);

  // Crear el archivo update.php
  $update_file    = fopen("$dir/update.php", "w");
  $update_content = "<?php\n";
  $create_content .= "\n\n";
  $update_content .= "if (\$_POST) {\n\n";

  foreach ($columns as $column) {
    $update_content .= "    \$$column=\$_POST['$column'];\n";
  }

  $update_content .= "\n    \$stmt = \$conn->prepare(\"UPDATE $table SET ";

  $update_pairs = [];
  foreach (array_slice($columns, 1) as $column) {
    $update_pairs[] = "$column = :$column";
  }
  $update_content .= implode(", ", $update_pairs);
  $update_content .= " WHERE " . $columns[0] . " = :" . $columns[0] . "\");\n";

  foreach ($columns as $column) {
    $update_content .= "    \$stmt->bindParam(':$column', \$$column);\n";
  }

  $update_content .= "\n    if (\$stmt->execute()) {\n";
  $update_content .= "        echo 'Registro actualizado exitosamente.';\n";
  $update_content .= "    } else {\n";
  $update_content .= "        echo 'Error al actualizar el registro.';\n";
  $update_content .= "    }\n";
  $update_content .= "}\n";
  $update_content .= "?>\n";
  fwrite($update_file, $update_content);
  fclose($update_file);

  // Crear el archivo delete.php
  $delete_file    = fopen("$dir/delete.php", "w");
  $delete_content = "<?php\n";
  $create_content .= "\n\n";
  $delete_content .= "if (\$_POST) {\n";
  $delete_content .= "    \$stmt = \$conn->prepare(\"DELETE FROM $table WHERE " . $columns[0] . " = :" . $columns[0] . "\");\n";
  $delete_content .= "    \$stmt->bindParam(':" . $columns[0] . "', \$_POST['" . $columns[0] . "']);\n";
  $delete_content .= "    if (\$stmt->execute()) {\n";
  $delete_content .= "        echo 'Registro eliminado exitosamente.';\n";
  $delete_content .= "    } else {\n";
  $delete_content .= "        echo 'Error al eliminar el registro.';\n";
  $delete_content .= "    }\n";
  $delete_content .= "}\n";
  $delete_content .= "?>\n";
  fwrite($delete_file, $delete_content);
  fclose($delete_file);
}

// Comprobar si el script se ejecuta desde la línea de comandos
if (php_sapi_name() == "cli") {
  if (isset($argv[1])) {
    $table = $argv[1];
    generateCRUD($table);
    echo "CRUD generado para la tabla '$table'.\n";
  } else {
    echo "Por favor, proporciona el nombre de la tabla.\n";
  }
} else {
  echo "Este script debe ejecutarse desde la línea de comandos.\n";
}
