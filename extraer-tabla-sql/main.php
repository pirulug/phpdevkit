<?php
// Configuración de PHP para errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Valores por defecto
$defaultHost     = 'localhost';
$defaultUser     = 'root';
$defaultPassword = '';

// Pedir información al usuario
echo "Ingresa host de DB (por defecto: $defaultHost): ";
$host = trim(fgets(STDIN));
$host = empty($host) ? $defaultHost : $host;

echo "Ingresa usuario de DB (por defecto: $defaultUser): ";
$dbUser = trim(fgets(STDIN));
$dbUser = empty($dbUser) ? $defaultUser : $dbUser;

echo "Ingresa contraseña (vacío por defecto): ";
$dbPassword = trim(fgets(STDIN));
$dbPassword = empty($dbPassword) ? $defaultPassword : $dbPassword;

// Conectar a la base de datos para obtener la lista de bases de datos
try {
    $pdo = new PDO("mysql:host=$host", $dbUser, $dbPassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Obtener la lista de bases de datos
    $stmt      = $pdo->query("SHOW DATABASES");
    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($databases)) {
        die("No se encontraron bases de datos.\n");
    }

    // Mostrar la lista de bases de datos
    echo "Selecciona una base de datos por número:\n";
    foreach ($databases as $index => $database) {
        echo ($index + 1) . ". $database\n";
    }

    // Pedir al usuario que elija una base de datos
    echo "Número de la base de datos: ";
    $choice = trim(fgets(STDIN));

    // Validar la elección
    if (!is_numeric($choice) || $choice < 1 || $choice > count($databases)) {
        die("Selección inválida.\n");
    }

    $dbName = $databases[$choice - 1];

    // Conectar a la base de datos seleccionada
    $pdo = new PDO("mysql:host=$host;dbname=$dbName", $dbUser, $dbPassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Obtener el nombre de las tablas
    $stmt   = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($tables)) {
        die("No se encontraron tablas en la base de datos.\n");
    }

    // Generar el archivo de copia de seguridad
    $backupFile = "$dbName-structure-" . date('Y-m-d-H-i-s') . ".sql";
    $fileHandle = fopen($backupFile, 'w');

    if (!$fileHandle) {
        die("No se pudo crear el archivo de copia de seguridad.\n");
    }

    // Exportar la estructura de cada tabla
    foreach ($tables as $table) {
        // Obtener la estructura de la tabla
        $createTableStmt = $pdo->query("SHOW CREATE TABLE `$table`");
        $createTable     = $createTableStmt->fetch(PDO::FETCH_ASSOC);
        $tableDefinition = preg_replace('/AUTO_INCREMENT=\d+ /', '', $createTable['Create Table']);
        fwrite($fileHandle, $tableDefinition . ";\n\n");
    }

    fclose($fileHandle);

    echo "Copia de seguridad de la estructura creada exitosamente: $backupFile\n";
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage() . "\n");
}
