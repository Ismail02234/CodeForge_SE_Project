<?php 
require_once 'config/db.php'; 
include 'includes/header.php'; 

$stmt = $pdo->query("SELECT * FROM Contests ORDER BY date DESC");
$contests = $stmt->fetchAll();
?>

<div class="container mt-5 fade-in">
    <div class="row mb-4 align-items-center">
        <div class="col-md-8">
            <h2 class="mb-1">
                <i class="bi bi-trophy text-warning me-2"></i> Tournament Arena
            </h2>
            <p class="text-muted mb-0">Join epic coding battles and prove your skills against the best!</p>
        </div>
        <div class="col-md-4 text-end">
            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#createContestModal">
                <i class="bi bi-plus-circle me-1"></i> Create Tournament
            </button>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-5">
        <div class="card-header bg-white py-3 border-0 fw-bold">
            <i class="bi bi-calendar-event me-2 text-primary"></i> Active & Upcoming Rounds
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tournament Name</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th class="text-center">Heroes</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contests as $c): 
                        $statusColor = ($c['status'] === 'Active')
                            ? 'success'
                            : (($c['status'] === 'Upcoming') ? 'primary' : 'secondary');
                        $statusEmoji = match($c['status']) {
                            'Active' => '⚔️',
                            'Upcoming' => '⏳',
                            'Past' => '🏆',
                            default => '📅'
                        };
                    ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($c['name']) ?></td>
                            <td><span class="badge border text-dark"><?= htmlspecialchars($c['type']) ?></span></td>
                            <td><?= date('M d, Y', strtotime($c['date'])) ?></td>
                            <td class="text-center"><?= (int)$c['participants'] ?> <i class="bi bi-people text-muted"></i></td>
                            <td>
                                <span class="badge bg-<?= $statusColor ?>">
                                    <?= $statusEmoji ?> <?= htmlspecialchars($c['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($c['status'] !== 'Past'): ?>
                                    <a href="contest_view.php?id=<?= htmlspecialchars($c['id']) ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-sword me-1"></i> Enter
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline-secondary" disabled>Finished</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm quest-card bg-light">
                <div class="card-body">
                    <h5 class="card-title text-danger fw-bold">
                        <i class="bi bi-lightning-fill me-2"></i> Head-to-Head Duel
                    </h5>
                    <p class="card-text small text-muted">
                        Challenge another hero to a real-time coding battle!
                    </p>

                    <form action="duel_logic.php" method="POST">
                        <div class="input-group mb-3">
                            <span class="input-group-text bg-white fw-bold">
                                <i class="bi bi-shield-star text-primary me-1"></i> You
                            </span>
                            <?php
                            $stmt = $pdo->prepare("SELECT username FROM Users WHERE id = ?");
                            $stmt->execute(['u1']);
                            $me = $stmt->fetch();
                            ?>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($me['username']) ?>" readonly>

                            <span class="input-group-text bg-white fw-bold">VS</span>

                            <select name="rival" class="form-select">
                                <?php
                                $stmt = $pdo->prepare("SELECT id, username FROM Users WHERE id != ?");
                                $stmt->execute(['u1']);
                                $rivals = $stmt->fetchAll();

                                foreach ($rivals as $r) {
                                    echo "<option value='{$r['id']}'>" . htmlspecialchars($r['username']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-danger w-100 fw-bold">
                            <i class="bi bi-lightning me-1"></i> Start Duel!
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
