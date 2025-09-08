<?php
// Inicia a sessão se ainda não estiver ativa
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

// Obtém dados do usuário logado, se existir
$loggedInName = $_SESSION['user']['name'] ?? null;
$loggedInTipo = $_SESSION['user']['tipo_usuario'] ?? null;

// Converte tipo do usuário para exibição em português
$tipoDisplay = '';
if ($loggedInTipo) {
  switch ($loggedInTipo) {
    case 'cliente':
      $tipoDisplay = 'Cliente';
      break;
    case 'prestador':
      $tipoDisplay = 'Prestador';
      break;
    case 'administrador':
      $tipoDisplay = 'Administrador';
      break;
  }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sistema de Cadastro Star Clean</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <style>
    /* Ajustes rápidos de estilo */
    body {
      padding-top: 70px;
      /* evita sobreposição do navbar */
    }
  </style>
</head>

<body class="bg-light">

  <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container">
      <a class="navbar-brand" href="index.php">Star Clean</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu"
        aria-controls="navbarMenu" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarMenu">
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <a class="nav-link" href="index.php" aria-current="page">Home</a>
          </li>

          <?php if ($loggedInName): ?>
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                data-bs-toggle="dropdown" aria-expanded="false" title="Menu do usuário">
                <?= htmlspecialchars($loggedInName) ?> <?= $tipoDisplay ? "($tipoDisplay)" : '' ?>
              </a>
              <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                <li><a class="dropdown-item" href="profile.php">Perfil</a></li>
                <li>
                  <hr class="dropdown-divider">
                </li>
                <li><a class="dropdown-item" href="logout.php">Sair</a></li>
              </ul>
            </li>
          <?php else: ?>
            <li class="nav-item">
              <a class="nav-link" href="login.php">Login</a>
            </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>

  <div class="container my-4">