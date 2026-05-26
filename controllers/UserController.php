<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db_connection.php'; // asegúrate de que esto apunte bien


class UserController

{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
        $this->handleRequest();
    }

    public function handleRequest()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['action'])) {
                switch ($_POST['action']) {
                    case 'login':
                        $this->login();
                        break;
                    case 'updateUser':
                        $this->updateUser();
                        break;
                    case 'updateGeneral':
                        $this->updateGeneral();
                        break;
                    case 'updatePassword':
                        $this->updatePassword();
                        break;
                    case 'updateInfo':
                        $this->updateInfo();
                        break;
                    case 'updateSocial':
                        $this->updateSocial();
                        break;
                    case 'updateNotifications':
                        $this->updateNotifications();
                        break;
                    case 'deleteAccount':
                        $this->deleteAccount();
                        break;
                }
            } elseif (isset($_POST['register'])) {
                $this->register();
            }
        } elseif (isset($_GET['action']) && $_GET['action'] === 'logout') {
            $this->logout();
        }
    }

    public function login()
    {
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);

        if (empty($email) || empty($password)) {
            $_SESSION['error'] = 'Error: Email y contraseña son obligatorios.';
            header('Location: ../php/login.php');
            exit;
        }

        try {
            $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user'] = $user;
                    if ($user['is_admin'] == 1) {
                        header('Location: ../views/php/userAdmin.php');
                    } else {
                        header('Location: ../views/php/userUser.php');
                    }
                    exit;
                } else {
                    $_SESSION['error'] = 'Debug: Contraseña incorrecta para ' . htmlspecialchars($email);
                    header('Location: ../php/login.php');
                    exit;
                }
            } else {
                $_SESSION['error'] = 'Debug: No se encontró usuario con el correo ' . htmlspecialchars($email);
                header('Location: ../php/login.php');
                exit;
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Error de base de datos: ' . $e->getMessage();
            header('Location: ../php/login.php');
            exit;
        }
    }

    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
            // Sanitize inputs
            $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
            $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $fecha_born = filter_input(INPUT_POST, 'fecha_born', FILTER_SANITIZE_STRING);
            $password = $_POST['password'];
            $confirmPassword = $_POST['confirmPassword'];
            $terms = isset($_POST['terms']) ? true : false;

            // Validate inputs
            if (!$terms) {
                $_SESSION['error'] = 'Debes aceptar los términos y condiciones.';
                header('Location: ../views/php/register.php');
                exit();
            }

            if ($password !== $confirmPassword) {
                $_SESSION['error'] = 'Las contraseñas no coinciden.';
                header('Location: ../views/php/register.php');
                exit();
            }

            if (strlen($password) < 8 || !preg_match('/\d/', $password) || !preg_match('/[A-Z]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
                $_SESSION['error'] = 'La contraseña debe tener al menos 8 caracteres, incluyendo un número, una mayúscula y un carácter especial.';
                header('Location: ../views/php/register.php');
                exit();
            }

            // Hash password
            $password = password_hash($password, PASSWORD_DEFAULT);
            $is_admin = 0;
            $default_photo_url = 'https://i.pinimg.com/474x/da/96/78/da96780e1b54f46d6ddb13c3e14cec83.jpg';

            // Download default photo
            $photo = @file_get_contents($default_photo_url);
            if ($photo === false) {
                $_SESSION['error'] = 'Error al descargar la imagen por defecto.';
                header('Location: ../views/php/register.php');
                exit();
            }

            // Check for existing email or username
            try {
                $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = :email OR username = :username");
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt->bindParam(':username', $username, PDO::PARAM_STR);
                $stmt->execute();
                if ($stmt->fetch()) {
                    $_SESSION['error'] = 'El correo o nombre de usuario ya está registrado.';
                    header('Location: ../views/php/register.php');
                    exit();
                }

                // Insert new user
                $stmt = $this->conn->prepare(
                    "INSERT INTO users (name, username, email, fecha_born, password, is_admin, photo, created_at) 
                    VALUES (:name, :username, :email, :fecha_born, :password, :is_admin, :photo, NOW())"
                );
                $stmt->bindParam(':name', $name, PDO::PARAM_STR);
                $stmt->bindParam(':username', $username, PDO::PARAM_STR);
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt->bindParam(':fecha_born', $fecha_born, PDO::PARAM_STR);
                $stmt->bindParam(':password', $password, PDO::PARAM_STR);
                $stmt->bindParam(':is_admin', $is_admin, PDO::PARAM_INT);
                $stmt->bindParam(':photo', $photo, PDO::PARAM_LOB);

                if ($stmt->execute()) {
                    $_SESSION['success'] = 'Registro exitoso. Por favor, inicia sesión.';
                    header('Location: ../php/login.php');
                    exit();
                } else {
                    $_SESSION['error'] = 'Error al registrar el usuario.';
                    header('Location: ../views/php/register.php');
                    exit();
                }
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Error en la base de datos: ' . $e->getMessage();
                header('Location: ../views/php/register.php');
                exit();
            }
        }
    }

    public function updateUser()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: ../php/login.php');
            exit;
        }

        $id = trim($_POST['id']);
        $name = trim($_POST['name']);
        $username = trim($_POST['username']);
        $fecha_born = trim($_POST['fecha_born']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $photo = null;

        if ($id != $_SESSION['user']['id']) {
            $_SESSION['error'] = 'Error: No tienes permiso para modificar este usuario.';
            header('Location: ../views/php/' . ($_SESSION['user']['is_admin'] ? 'userAdmin.php' : 'userUser.php'));
            exit;
        }

        if (empty($name) || empty($username) || empty($fecha_born) || empty($email)) {
            $_SESSION['error'] = 'Error: Todos los campos (excepto contraseña y foto) son obligatorios.';
            header('Location: ../views/php/' . ($_SESSION['user']['is_admin'] ? 'userAdmin.php' : 'userUser.php'));
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Error: Formato de email inválido.';
            header('Location: ../views/php/' . ($_SESSION['user']['is_admin'] ? 'userAdmin.php' : 'userUser.php'));
            exit;
        }

        try {
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            if ($stmt->fetch()) {
                $_SESSION['error'] = 'Error: Este correo ya está registrado por otro usuario.';
                header('Location: ../views/php/' . ($_SESSION['user']['is_admin'] ? 'userAdmin.php' : 'userUser.php'));
                exit;
            }

            $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = :username AND id != :id");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            if ($stmt->fetch()) {
                $_SESSION['error'] = 'Error: Este nombre de usuario ya está registrado por otro usuario.';
                header('Location: ../views/php/' . ($_SESSION['user']['is_admin'] ? 'userAdmin.php' : 'userUser.php'));
                exit;
            }

            if (!empty($_FILES['photo']['tmp_name'])) {
                $photo = file_get_contents($_FILES['photo']['tmp_name']);
            }

            $query = "UPDATE users SET name = :name, username = :username, fecha_born = :fecha_born, email = :email";
            $params = [
                ':name' => $name,
                ':username' => $username,
                ':fecha_born' => $fecha_born,
                ':email' => $email,
                ':id' => $id
            ];

            if (!empty($password)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $query .= ", password = :password";
                $params[':password'] = $hashedPassword;
            }

            if ($photo !== null) {
                $query .= ", photo = :photo";
                $params[':photo'] = $photo;
            }

            $query .= " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, is_null($value) ? PDO::PARAM_NULL : (is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR));
            }

            if ($stmt->execute()) {
                $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = :id");
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                $_SESSION['user'] = $stmt->fetch(PDO::FETCH_ASSOC);

                $_SESSION['success'] = 'Datos actualizados correctamente.';
            } else {
                $_SESSION['error'] = 'Error al actualizar los datos.';
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Error de base de datos: ' . $e->getMessage();
        }

        header('Location: ../views/php/' . ($_SESSION['user']['is_admin'] ? 'userAdmin.php' : 'userUser.php'));
        exit;
    }

    public function uploadPhoto()
    {
        if (!isset($_SESSION['user'])) {
            $_SESSION['error'] = 'No has iniciado sesión.';
            header('Location: ../php/login.php');
            exit;
        }

        $id = trim($_POST['id']);
        if ($id != $_SESSION['user']['id']) {
            $_SESSION['error'] = 'No tienes permiso para modificar este usuario.';
            header('Location: ../views/php/userAdmin.php');
            exit;
        }

        if (!isset($_FILES['photo']) || $_FILES['photo']['error'] === UPLOAD_ERR_NO_FILE) {
            $_SESSION['error'] = 'No se seleccionó ninguna foto.';
            header('Location: ../views/php/userAdmin.php');
            exit;
        }

        if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'Error al subir el archivo: Código ' . $_FILES['photo']['error'];
            header('Location: ../views/php/userAdmin.php');
            exit;
        }

        $maxSize = 800 * 1024; // 800KB en bytes
        if ($_FILES['photo']['size'] > $maxSize) {
            $_SESSION['error'] = 'El archivo excede el tamaño máximo permitido (800KB).';
            header('Location: ../views/php/userAdmin.php');
            exit;
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = mime_content_type($_FILES['photo']['tmp_name']);
        if (!in_array($fileType, $allowedTypes)) {
            $_SESSION['error'] = 'Formato de archivo no permitido. Usa JPG, PNG o GIF.';
            header('Location: ../views/php/userAdmin.php');
            exit;
        }

        $photo = file_get_contents($_FILES['photo']['tmp_name']);
        if ($photo === false) {
            $_SESSION['error'] = 'Error al leer el archivo subido.';
            header('Location: ../views/php/userAdmin.php');
            exit;
        }

        try {
            $stmt = $this->conn->prepare("UPDATE users SET photo = ? WHERE id = ?");
            $stmt->bindParam(1, $photo, PDO::PARAM_LOB);
            $stmt->bindParam(2, $id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->bindParam(1, $id, PDO::PARAM_INT);
                $stmt->execute();
                $_SESSION['user'] = $stmt->fetch(PDO::FETCH_ASSOC);
                $_SESSION['success'] = 'Foto actualizada correctamente.';
            } else {
                $_SESSION['error'] = 'Error al actualizar la foto en la base de datos.';
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Error de base de datos: ' . $e->getMessage();
        }

        header('Location: ../views/php/userAdmin.php');
        exit;
    }

    public function updateGeneral()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: ../php/login.php');
            exit;
        }

        $id = trim($_POST['id']);
        $username = trim($_POST['username']);
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);

        if ($id != $_SESSION['user']['id']) {
            $_SESSION['error'] = 'Error: No tienes permiso para modificar este usuario.';
            header('Location: ../views/php/userAdmin.php');
            exit;
        }

        if (empty($username) || empty($name) || empty($email)) {
            $_SESSION['error'] = 'Error: Todos los campos son obligatorios.';
            header('Location: ../views/php/userAdmin.php');
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Error: Formato de email inválido.';
            header('Location: ../views/php/userAdmin.php');
            exit;
        }

        try {
            $stmt = $this->conn->prepare("UPDATE users SET username = :username, name = :name, email = :email WHERE id = :id");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':id', $id);

            if ($stmt->execute()) {
                if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    $maxSize = 800 * 1024;
                    if ($_FILES['photo']['size'] <= $maxSize) {
                        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                        $fileType = mime_content_type($_FILES['photo']['tmp_name']);
                        if (in_array($fileType, $allowedTypes)) {
                            $photo = file_get_contents($_FILES['photo']['tmp_name']);
                            if ($photo !== false) {
                                $stmt = $this->conn->prepare("UPDATE users SET photo = :photo WHERE id = :id");
                                $stmt->bindParam(':photo', $photo, PDO::PARAM_LOB);
                                $stmt->bindParam(':id', $id);
                                $stmt->execute();
                            }
                        }
                    }
                }
                $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = :id");
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                $_SESSION['user'] = $stmt->fetch(PDO::FETCH_ASSOC);
                $_SESSION['success'] = 'Información general actualizada correctamente.';
            } else {
                $_SESSION['error'] = 'Error al actualizar la información general.';
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Error de base de datos: ' . $e->getMessage();
        }

        header('Location: ../views/php/userAdmin.php');
        exit;
    }

    public function updatePassword()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: ../php/login.php');
            exit;
        }

        $id = trim($_POST['id']);
        $password = trim($_POST['password']);

        if ($id != $_SESSION['user']['id']) {
            $_SESSION['error'] = 'Error: No tienes permiso para modificar este usuario.';
            header('Location: ../views/php/' . ($_SESSION['user']['is_admin'] ? 'userAdmin.php' : 'userUser.php'));
            exit;
        }

        if (empty($password)) {
            $_SESSION['success'] = 'No se hicieron cambios en la contraseña.';
            header('Location: ../views/php/' . ($_SESSION['user']['is_admin'] ? 'userAdmin.php' : 'userUser.php'));
            exit;
        }

        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare("UPDATE users SET password = :password WHERE id = :id");
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':id', $id);

            if ($stmt->execute()) {
                $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = :id");
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                $_SESSION['user'] = $stmt->fetch(PDO::FETCH_ASSOC);

                $_SESSION['success'] = 'Contraseña actualizada correctamente.';
            } else {
                $_SESSION['error'] = 'Error al actualizar la contraseña.';
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Error de base de datos: ' . $e->getMessage();
        }

        header('Location: ../views/php/' . ($_SESSION['user']['is_admin'] ? 'userAdmin.php' : 'userUser.php'));
        exit;
    }

    public function updateInfo()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: ../php/login.php');
            exit;
        }

        $id = trim($_POST['id']);
        $bio = trim($_POST['bio']);
        $fecha_born = trim($_POST['fecha_born']);
        $phone = trim($_POST['phone']);

        if ($id != $_SESSION['user']['id']) {
            $_SESSION['error'] = 'Error: No tienes permiso para modificar este usuario.';
            header('Location: ../views/php/' . ($_SESSION['user']['is_admin'] ? 'userAdmin.php' : 'userUser.php'));
            exit;
        }

        if (empty($fecha_born)) {
            $_SESSION['error'] = 'Error: La fecha de nacimiento es obligatoria.';
            header('Location: ../views/php/' . ($_SESSION['user']['is_admin'] ? 'userAdmin.php' : 'userUser.php'));
            exit;
        }

        try {
            $stmt = $this->conn->prepare("UPDATE users SET bio = :bio, fecha_born = :fecha_born, phone = :phone WHERE id = :id");
            $stmt->bindParam(':bio', $bio);
            $stmt->bindParam(':fecha_born', $fecha_born);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':id', $id);

            if ($stmt->execute()) {
                // Actualizar la sesión con los nuevos datos
                $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = :id");
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                $_SESSION['user'] = $stmt->fetch(PDO::FETCH_ASSOC);

                $_SESSION['success'] = 'Información personal actualizada correctamente.';
            } else {
                $_SESSION['error'] = 'Error al actualizar la información personal.';
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Error de base de datos: ' . $e->getMessage();
        }

        header('Location: ../views/php/' . ($_SESSION['user']['is_admin'] ? 'userAdmin.php' : 'userUser.php'));
        exit;
    }

    public function updateSocial()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: ../php/login.php');
            exit;
        }

        $id = trim($_POST['id']);
        $twitter = trim($_POST['twitter']);
        $instagram = trim($_POST['instagram']);
        $facebook = trim($_POST['facebook']);
        $youtube = trim($_POST['youtube']);

        if ($id != $_SESSION['user']['id']) {
            $_SESSION['error'] = 'Error: No tienes permiso para modificar este usuario.';
            header('Location: ../views/php/' . ($_SESSION['user']['is_admin'] ? 'userAdmin.php' : 'userUser.php'));
            exit;
        }

        try {
            $stmt = $this->conn->prepare("UPDATE users SET twitter = :twitter, instagram = :instagram, facebook = :facebook, youtube = :youtube WHERE id = :id");
            $stmt->bindParam(':twitter', $twitter);
            $stmt->bindParam(':instagram', $instagram);
            $stmt->bindParam(':facebook', $facebook);
            $stmt->bindParam(':youtube', $youtube);
            $stmt->bindParam(':id', $id);

            if ($stmt->execute()) {
                $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = :id");
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                $_SESSION['user'] = $stmt->fetch(PDO::FETCH_ASSOC);

                $_SESSION['success'] = 'Redes sociales actualizadas correctamente.';
            } else {
                $_SESSION['error'] = 'Error al actualizar las redes sociales.';
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Error de base de datos: ' . $e->getMessage();
        }

        header('Location: ../views/php/' . ($_SESSION['user']['is_admin'] ? 'userAdmin.php' : 'userUser.php'));
        exit;
    }

    public function updateNotifications()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: ../php/login.php');
            exit;
        }

        $id = trim($_POST['id']);
        $email_messages = isset($_POST['email_messages']) ? 1 : 0;
        $email_reminders = isset($_POST['email_reminders']) ? 1 : 0;
        $email_promotions = isset($_POST['email_promotions']) ? 1 : 0;

        if ($id != $_SESSION['user']['id']) {
            $_SESSION['error'] = 'Error: No tienes permiso para modificar este usuario.';
            header('Location: ../views/php/' . ($_SESSION['user']['is_admin'] ? 'userAdmin.php' : 'userUser.php'));
            exit;
        }

        try {
            $stmt = $this->conn->prepare("UPDATE users SET email_messages = :email_messages, email_reminders = :email_reminders, email_promotions = :email_promotions WHERE id = :id");
            $stmt->bindParam(':email_messages', $email_messages, PDO::PARAM_INT);
            $stmt->bindParam(':email_reminders', $email_reminders, PDO::PARAM_INT);
            $stmt->bindParam(':email_promotions', $email_promotions, PDO::PARAM_INT);
            $stmt->bindParam(':id', $id);

            if ($stmt->execute()) {
                $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = :id");
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                $_SESSION['user'] = $stmt->fetch(PDO::FETCH_ASSOC);

                $_SESSION['success'] = 'Notificaciones actualizadas correctamente.';
            } else {
                $_SESSION['error'] = 'Error al actualizar las notificaciones.';
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Error de base de datos: ' . $e->getMessage();
        }

        header('Location: ../views/php/' . ($_SESSION['user']['is_admin'] ? 'userAdmin.php' : 'userUser.php'));
        exit;
    }

    public function deleteAccount()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: ../php/login.php');
            exit;
        }

        $id = trim($_POST['id']);
        if ($id != $_SESSION['user']['id']) {
            $_SESSION['error'] = 'Error: No tienes permiso para eliminar este usuario.';
            header('Location: ../views/php/' . ($_SESSION['user']['is_admin'] ? 'userAdmin.php' : 'userUser.php'));
            exit;
        }

        try {
            $stmt = $this->conn->prepare("DELETE FROM users WHERE id = :id");
            $stmt->bindParam(':id', $id);
            if ($stmt->execute()) {
                session_unset();
                session_destroy();
                header('Location: /index.php');
                exit;
            } else {
                $_SESSION['error'] = 'Error al eliminar la cuenta.';
                header('Location: ../views/php/' . ($_SESSION['user']['is_admin'] ? 'userAdmin.php' : 'userUser.php'));
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Error de base de datos: ' . $e->getMessage();
            header('Location: ../views/php/' . ($_SESSION['user']['is_admin'] ? 'userAdmin.php' : 'userUser.php'));
        }
        exit;
    }

    public function logout()
    {
        session_unset();
        session_destroy();
        header('Location: ../php/login.php');
        exit();
    }

    public static function checkSession()
    {
        if (!isset($_SESSION['user'])) {
            session_destroy();
            header('Location: ../php/login.php');
            exit();
        }
    }
}

try {
    $db = new PDO("mysql:host=localhost;dbname=mp0487_dojosearch", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("SET CHARACTER SET utf8mb4");
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

$controller = new UserController($db);
