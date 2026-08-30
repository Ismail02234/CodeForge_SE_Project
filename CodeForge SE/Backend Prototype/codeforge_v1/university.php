<?php 
include 'config/db.php'; 
include 'includes/header.php'; 
?>

<div class="container mt-5 fade-in">
    <div class="text-center mb-5">
        <div class="display-1 mb-3">📚</div>
        <h2 class="fw-bold text-primary">Code Academy Leaderboard</h2>
        <p class="text-muted">See which academies produce the mightiest coding heroes!</p>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive rounded">
            <table class="table table-hover align-middle bg-white mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Academy</th>
                        <th class="text-center">Students</th>
                        <th class="text-center">Total Quests</th>
                        <th>Top Hero</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT u.name AS university_name, 
                                   COUNT(us.id) AS student_count, 
                                   SUM(us.solvedCount) AS total_solved,
                                   MAX(us.solvedCount) AS max_solved
                            FROM Universities u
                            LEFT JOIN Users us ON us.university = u.name
                            GROUP BY u.name
                            ORDER BY total_solved DESC, university_name ASC";

                    $stmt = $pdo->query($sql);

                    while ($univ = $stmt->fetch(PDO::FETCH_ASSOC)):
                        $univName = $univ['university_name'];
                        $totalStudents = $univ['student_count'] ?? 0;
                        $totalSolved = $univ['total_solved'] ?? 0;

                        $topUserStmt = $pdo->prepare("SELECT id, username, solvedCount 
                                                     FROM Users 
                                                     WHERE university = ? 
                                                     ORDER BY solvedCount DESC LIMIT 1");
                        $topUserStmt->execute([$univName]);
                        $topUser = $topUserStmt->fetch(PDO::FETCH_ASSOC);
                    ?>
                    <tr>
                        <td class="fw-bold text-primary">
                            <i class="bi bi-building me-1"></i> <?= htmlspecialchars($univName) ?>
                        </td>
                        <td class="text-center"><?= $totalStudents ?> <i class="bi bi-people text-muted"></i></td>
                        <td class="text-center">
                            <span class="badge bg-success fs-6"><?= $totalSolved ?></span>
                        </td>
                        <td>
                            <?php if($topUser): ?>
                                <a href="profile.php?id=<?= $topUser['id'] ?>" class="text-decoration-none">
                                    <i class="bi bi-trophy text-warning me-1"></i> <?= htmlspecialchars($topUser['username']) ?>
                                    <small class="text-muted">(<?= $topUser['solvedCount'] ?> quests)</small>
                                </a>
                            <?php else: ?>
                                <span class="text-muted small">No heroes yet</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="university_compare.php?u1=<?= urlencode($univName) ?>" class="btn btn-sm btn-outline-primary">
                                Compare
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
