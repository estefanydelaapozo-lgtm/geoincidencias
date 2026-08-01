<?php

// Script de emergencia para crear usuarios manualmente
// Ejecutar: php crear_usuarios.php

require __DIR__ . '/backend-laravel/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/backend-laravel');
$dotenv->load();

// Configuración de base de datos
$host = $_ENV['DB_HOST'] ?? 'localhost';
$database = $_ENV['DB_DATABASE'] ?? 'geoincidencias';
$username = $_ENV['DB_USERNAME'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';

try {
    // Conexión a MySQL directamente
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Conectado a la base de datos $database\n";

    // Verificar si la tabla usuarios existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'usuarios'");
    if ($stmt->rowCount() == 0) {
        echo "La tabla usuarios no existe. Creando...\n";
        $pdo->exec("CREATE TABLE usuarios (
            id_usuario INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            apellido VARCHAR(100),
            correo VARCHAR(150) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            rol ENUM('admin','usuario') NOT NULL DEFAULT 'usuario',
            telefono VARCHAR(20),
            saldo_incentivos DECIMAL(10,2) DEFAULT 0.00,
            activo TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        )");
    }

    // Crear usuarios
    $hash = password_hash('123456', PASSWORD_DEFAULT);

    $usuarios = [
        [
            'id_usuario' => 1,
            'nombre' => 'Admin',
            'apellido' => 'Sistema',
            'correo' => 'admin@geoincidencias.com',
            'password' => $hash,
            'rol' => 'admin',
            'telefono' => '0990000000',
            'saldo_incentivos' => 0,
            'activo' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ],
        [
            'id_usuario' => 2,
            'nombre' => 'Carlos',
            'apellido' => 'Mendoza',
            'correo' => 'cmendoza@empresa.com',
            'password' => $hash,
            'rol' => 'usuario',
            'telefono' => '0991234567',
            'saldo_incentivos' => 20.00,
            'activo' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ],
        [
            'id_usuario' => 3,
            'nombre' => 'Maria',
            'apellido' => 'Gonzalez',
            'correo' => 'mgonzalez@empresa.com',
            'password' => $hash,
            'rol' => 'usuario',
            'telefono' => '0992345678',
            'saldo_incentivos' => 0,
            'activo' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ]
    ];

    foreach ($usuarios as $usuario) {
        // Verificar si el usuario ya existe
        $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE correo = ?");
        $stmt->execute([$usuario['correo']]);

        if ($stmt->rowCount() > 0) {
            echo "Usuario {$usuario['correo']} ya existe. Actualizando...\n";
            $stmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE correo = ?");
            $stmt->execute([$usuario['password'], $usuario['correo']]);
        } else {
            echo "Creando usuario {$usuario['correo']}...\n";
            $stmt = $pdo->prepare("INSERT INTO usuarios (id_usuario, nombre, apellido, correo, password, rol, telefono, saldo_incentivos, activo, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $usuario['id_usuario'],
                $usuario['nombre'],
                $usuario['apellido'],
                $usuario['correo'],
                $usuario['password'],
                $usuario['rol'],
                $usuario['telefono'],
                $usuario['saldo_incentivos'],
                $usuario['activo'],
                $usuario['created_at']
            ]);
        }
    }

    echo "\n✓ Usuarios creados exitosamente\n";
    echo "Credenciales:\n";
    echo "Admin: admin@geoincidencias.com / 123456\n";
    echo "Usuario: cmendoza@empresa.com / 123456\n";
    echo "Usuario: mgonzalez@empresa.com / 123456\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Asegúrate de que la base de datos '$database' exista y que las credenciales sean correctas.\n";
}
