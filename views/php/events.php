
<?php
ini_set('session.gc_maxlifetime', 86400);
session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'domain' => '',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

require_once '../../controllers/db_connection.php';
require_once '../../controllers/EventController.php';
require_once '../../controllers/UserController.php';
UserController::checkSession();
$controller = new EventController($conn);
$eventos = $controller->getAllEvents();
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Eventos Marciales | DojoSearch</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/events.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="../assets/images/logoDS.png">
</head>

<body>
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

  <main class="events-container">
    <div class="events-grid">
      <?php if (!empty($eventos)): ?>
        <?php $counter = 1; foreach ($eventos as $event): ?>
          <div class="event-card">
            <div class="event-badge"><?php echo htmlspecialchars($event['name']); ?></div>
            <div class="event-image">
              <img src="../assets/images/events/event<?php echo $counter; ?>.jpg" alt="Imagen del evento">
              <div class="event-overlay">
                <button class="event-action red-button">¡Apúntate!</button>
              </div>
            </div>
            <div class="event-content">
              <div class="event-header">
                <h3 class="event-title"><?php echo htmlspecialchars($event['name']); ?></h3>
                <div class="event-location">
                  <i class="fas fa-map-marker-alt"></i>
                  <span><?php echo htmlspecialchars($event['location']); ?></span>
                </div>
              </div>
              <div class="event-meta">
                <div class="event-date">
                  <i class="fas fa-calendar-alt"></i>
                  <span><?php echo date('d M Y', strtotime($event['date'])); ?></span>
                </div>
                <div class="event-price">Gratis</div>
              </div>
              <p class="event-description"><?php echo htmlspecialchars($event['description']); ?></p>
              <div class="event-footer">
  <span class="difficulty-label">Nivel:</span>
  <div class="difficulty-stars">
    <i class="fas fa-star"></i>
    <i class="fas fa-star"></i>
    <i class="fas fa-star"></i>
    <i class="far fa-star"></i>
    <i class="far fa-star"></i>
  </div>
  <a href="detail.php?id=<?php echo $event['id']; ?>" class="event-details red-button">Más detalles</a>

  <?php if (!empty($_SESSION['user']) && $_SESSION['user']['is_admin']): ?>
    <!-- Botón Eliminar -->
    <form action="/../../controllers/EventController.php" method="POST" style="display:inline;">
      <input type="hidden" name="deleteEvent" value="1">
      <input type="hidden" name="id" value="<?php echo $event['id']; ?>">
      <button type="submit" class="btn-delete" onclick="return confirm('¿Seguro que quieres eliminar este evento?');">Eliminar</button>
    </form>

    <!-- Botón Editar que redirige a manageEvents.php -->
    <a href="./manageEvents.php?id=<?php echo $event['id']; ?>" class="btn-edit" style="margin-left: 10px;">Editar</a>
  <?php endif; ?>
</div>

            </div>
          </div>
        <?php $counter++; endforeach; ?>
      <?php else: ?>
        <p style="color:white; text-align:center;">No hay eventos disponibles actualmente.</p>
      <?php endif; ?>
    </div>
  </main>

  <section class="events-cta">
    <div class="cta-container">
      <h2>¿Eres organizador de eventos?</h2>
      <p>Publica tu evento en DojoSearch y llega a miles de apasionados de las artes marciales</p>
      <a href="./manageEvents.php" class="cta-button red-button">Publicar evento</a>
    </div>
  </section>

  <footer class="martial-footer">
    <div class="footer-container">
      <div class="footer-grid">
        <div class="footer-column">
          <div class="footer-brand">
            <img src="../assets/images/logoDS.png" alt="DojoSearch Logo" class="footer-logo">
            <h3 class="footer-title">DojoSearch</h3>
          </div>
          <div class="social-links">
            <a href="#" class="social-icon" aria-label="Instagram">
              <img src="../assets/images/social-media/instagram.png" alt="Instagram">
            </a>
            <a href="#" class="social-icon" aria-label="Facebook">
              <img src="../assets/images/social-media/facebook.png" alt="Facebook">
            </a>
            <a href="#" class="social-icon" aria-label="YouTube">
              <img src="../assets/images/social-media/youtube.png" alt="YouTube">
            </a>
            <a href="#" class="social-icon" aria-label="LinkedIn">
              <img src="../assets/images/social-media/linkedin.png" alt="LinkedIn">
            </a>
          </div>
          <div class="newsletter">
            <h4>Recibe novedades</h4>
            <form class="newsletter-form">
              <input type="email" placeholder="Tu mejor email" required>
              <button type="submit">Enviar</button>
            </form>
          </div>
        </div>
        <div class="footer-column">
          <h4 class="footer-heading">Explora</h4>
          <ul class="footer-links">
            <li><a href="../php/events.php">Eventos</a></li>
            <li><a href="../php/login.php">Mi Perfil</a></li>
            <li><a href="#">Galería</a></li>
            <li><a href="#">Blog Marcial</a></li>
            <li><a href="#">Tienda</a></li>
          </ul>
        </div>
        <div class="footer-column">
          <h4 class="footer-heading">Contacto</h4>
          <ul class="contact-info">
            <li>
              <img src="../assets/images/icons/pin.png" alt="Ubicación">
              <span>500 Terry Francine St<br>San Francisco, CA 94158</span>
            </li>
            <li>
              <img src="../assets/images/icons/phone.png" alt="Teléfono">
              <span>123-456-7890</span>
            </li>
            <li>
              <img src="../assets/images/icons/email.png" alt="Email">
              <span>info@dojosearch.com</span>
            </li>
          </ul>
        </div>
      </div>
      <div class="footer-divider"></div>
      <div class="footer-bottom">
        <div class="legal-links">
          <a href="#">Accesibilidad</a>
          <a href="#">Términos y condiciones</a>
          <a href="#">Política de privacidad</a>
        </div>
        <p class="copyright">&copy; 2023 DojoSearch. Todos los derechos reservados</p>
      </div>
    </div>
  </footer>

  <script src="https://code.jquery.com/jquery-1.10.2.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    var prevScrollpos = window.pageYOffset;
    window.onscroll = function () {
      var currentScrollPos = window.pageYOffset;
      document.getElementById("navbar").style.top = prevScrollpos > currentScrollPos ? "0" : "-80px";
      prevScrollpos = currentScrollPos;
    };
  </script>
</body>

</html>
