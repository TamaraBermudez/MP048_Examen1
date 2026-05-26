<?php
require_once '../../controllers/db_connection.php';
require_once '../../controllers/UserController.php';
require_once '../../controllers/EventController.php';

UserController::checkSession();

if (!$_SESSION['user']['is_admin']) {
    header('Location: ./index.php');
    exit;
}


$controller = new EventController($conn);
$eventos = $controller->getAllEvents(); // Asegúrate de que este método existe y retorna array
$eventoEditar = null;
if (isset($_GET['id'])) {
    foreach ($eventos as $ev) {
        if ($ev['id'] == $_GET['id']) {
            $eventoEditar = $ev;
            break;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión de Eventos | DojoSearch</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/profile.css">
</head>
<body>
    <div class="profile-container">
        <h2 class="section-title">Gestión de Eventos</h2>

  <form action="/../../controllers/EventController.php" method="POST" class="form-evento">
    <?php if ($eventoEditar): ?>
        <input type="hidden" name="editEvent" value="1">
        <input type="hidden" name="id" value="<?php echo $eventoEditar['id']; ?>">
    <?php else: ?>
        <input type="hidden" name="createEvent" value="1">
    <?php endif; ?>

    <div class="form-group">
        <label for="name" style="color: azure;">Título del evento</label>
        <input type="text" id="name" name="name" required value="<?php echo $eventoEditar['name'] ?? ''; ?>">
    </div>

    <div class="form-group">
        <label for="description" style="color: azure;">Descripción</label>
        <textarea id="description" name="description" rows="3" required><?php echo $eventoEditar['description'] ?? ''; ?></textarea>
    </div>

    <div class="form-group">
        <label for="date" style="color: azure;">Fecha</label>
        <input type="date" id="date" name="date" required value="<?php echo $eventoEditar['date'] ?? ''; ?>">
    </div>

    <div class="form-group">
        <label for="location" style="color: azure;">Ubicación</label>
        <input type="text" id="location" name="location" required value="<?php echo $eventoEditar['location'] ?? ''; ?>">
    </div>

    <div class="form-group">
        <button type="submit" class="btn-save">
            <?php echo $eventoEditar ? 'Actualizar evento' : 'Crear evento'; ?>
        </button>
    </div>
</form>


        <hr>

        <h3>Eventos existentes:</h3>
        <?php if (empty($eventos)): ?>
            <ul class="event-list">
                <?php foreach ($eventos as $event): ?>
    <li>
        <strong><?php echo htmlspecialchars($event['name']); ?></strong>
        (<?php echo $event['date']; ?> – <?php echo $event['location']; ?>)

        <!-- Botón Editar -->
        <a href="./manageEvents.php?id=<?php echo $event['id']; ?>" class="btn-edit">Editar</a>

        <!-- Botón Eliminar -->
        <form action="/../../controllers/EventController.php" method="POST" style="display:inline;">
            <input type="hidden" name="deleteEvent" value="1">
            <input type="hidden" name="id" value="<?php echo $event['id']; ?>">
            <button type="submit" class="btn-delete" onclick="return confirm('¿Seguro que quieres eliminar este evento?');">Eliminar</button>
        </form>
    </li>
<?php endforeach; ?>

            </ul>
                
        <?php else: ?>
            <p>No hay eventos registrados aún.</p>
        <?php endif; ?>
    </div>
</body>


<?php session_start(); ?>
<div id="navbar">
    <div class="logo-container">
        <a href="../php/index.php" class="logo-link">
            <img src="../assets/images/logoDS.png" alt="Logo" class="logo" />
            <h2>DojoSearch</h2>
        </a>
    </div>

    <input type="checkbox" id="menu-toggle" class="menu-toggle" />
    <label for="menu-toggle" class="menu-toggle-label">&#9776;</label>

    <nav class="nav-menu">
        <a href="../php/events.php">EVENTOS</a>
        <a href="<?php echo isset($_SESSION['user']) ? ($_SESSION['user']['is_admin'] ? 'userAdmin.php' : 'userUser.php') : 'login.php'; ?>">PERFIL</a>
    </nav>
</div>

</html>
        