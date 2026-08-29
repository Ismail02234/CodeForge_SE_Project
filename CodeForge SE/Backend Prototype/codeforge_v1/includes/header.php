<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CodeForge - Your Coding Adventure Awaits!</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .nav-link:hover { background-color: #343a40; color: white !important; border-radius: 5px; }
        .sidebar { min-height: 100vh; background: #212529; color: white; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg sticky-top navbar-light bg-white">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">
        <i class="bi bi-stars text-warning"></i> CodeForge
    </a>
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
            <a class="nav-link" href="index.php">
                <i class="bi bi-house-door me-1"></i> Home Base
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="problems.php">
                <i class="bi bi-swords me-1"></i> Quests
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="contests.php">
                <i class="bi bi-trophy me-1"></i> Tournaments
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="rivalry.php">
                <i class="bi bi-lightning me-1"></i> Duel Arena
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="university.php">
                <i class="bi bi-book me-1"></i> Code Academy
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="database.php">
                <i class="bi bi-safe me-1"></i> Treasure Vault
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="sql_lab.php">
                <i class="bi bi-wand-magic-sparkles me-1"></i> Magic Lab
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link btn btn-outline-primary ms-lg-2" href="how_it_works.php">
                <i class="bi bi-book-half me-1"></i> Adventure Guide
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-danger" href="logout.php">
                <i class="bi bi-box-arrow-right me-1"></i> Exit
            </a>
        </li>
        <form class="d-flex ms-lg-3" action="search.php" method="GET">
    <div class="input-group">
        <input class="form-control form-control-sm border-primary bg-white" type="search" name="q" placeholder="Search quests, heroes..." aria-label="Search">
        <button class="btn btn-sm btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
    </div>
</form>
      </ul>
    </div>
  </div>
</nav>
