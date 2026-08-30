<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CodeForge | DBMS Project</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .nav-link:hover { background-color: #343a40; color: white !important; border-radius: 5px; }
        .sidebar { min-height: 100vh; background: #212529; color: white; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php"><i class="bi bi-code-slash"></i> CodeForge</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="problems.php">Quests</a></li>
        <li class="nav-item"><a class="nav-link" href="contests.php">Contests</a></li>
        <li class="nav-item"><a class="nav-link" href="rivalry.php">Rivalry</a></li>
        <li class="nav-item"><a class="nav-link" href="university.php">University</a></li>
        <li class="nav-item"><a class="nav-link" href="database.php">Database Tab</a></li>
        <li class="nav-item"><a class="nav-link" href="sql_lab.php">SQL Lab</a></li>
        <li class="nav-item"><a class="nav-link btn btn-outline-info ms-lg-2" href="how_it_works.php">How it Works</a></li>
        <li class="nav-item">
    <a class="nav-link text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
</li>
        <form class="d-flex ms-lg-3" action="search.php" method="GET">
    <div class="input-group">
        <input class="form-control form-control-sm border-info bg-dark text-white" type="search" name="q" placeholder="Search users, problems..." aria-label="Search">
        <button class="btn btn-sm btn-outline-info" type="submit"><i class="bi bi-search"></i></button>
    </div>
</form>
      </ul>
    </div>
  </div>
</nav>