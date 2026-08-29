<?php 
include 'config/db.php'; 
include 'includes/header.php'; 
?>

<div class="container mt-5">
    <h2 class="mb-4 text-center fw-bold">University Leaderboard</h2>

    <div class="table-responsive shadow-sm rounded">
        <table class="table table-hover align-middle bg-white">
            <thead class="table-dark">
                <tr>
                    <th>University Name</th>
                    <th class="text-center">Active Students</th>
                    <th class="text-center">Total Solved</th>
                    <th>Top Performer (Dynamic)</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch universities with aggregated stats in one query
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

                    // Fetch top performer (if exists) using a prepared statement
                    $topUserStmt = $pdo->prepare("SELECT id, username, solvedCount 
                                                 FROM Users 
                                                 WHERE university = ? 
                                                 ORDER BY solvedCount DESC LIMIT 1");
                    $topUserStmt->execute([$univName]);
                    $topUser = $topUserStmt->fetch(PDO::FETCH_ASSOC);
                ?>
                <tr>
                    <td class="fw-bold text-primary"><?= htmlspecialchars($univName) ?></td>
                    <td class="text-center"><?= $totalStudents ?></td>
                    <td class="text-center">
                        <span class="badge bg-success"><?= $totalSolved ?></span>
                    </td>
                    <td>
                        <?php if($topUser): ?>
                            <a href="profile.php?id=<?= $topUser['id'] ?>" class="text-decoration-none">
                                <i class="bi bi-person-circle"></i> <?= htmlspecialchars($topUser['username']) ?>
                                <small class="text-muted">(<?= $topUser['solvedCount'] ?>)</small>
                            </a>
                        <?php else: ?>
                            <span class="text-muted small">No students yet</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="university_compare.php?u1=<?= urlencode($univName) ?>" class="btn btn-sm btn-outline-dark">Compare</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
