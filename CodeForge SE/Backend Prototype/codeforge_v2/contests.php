<?php 
require_once 'config/db.php'; 
include 'includes/header.php'; 

// Fetch contests from the database
$stmt = $pdo->query("SELECT * FROM Contests ORDER BY date DESC");
$contests = $stmt->fetchAll();
?>

<div class="container mt-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="bi bi-trophy text-warning"></i> Coding Contests</h2>
            <p class="text-muted">Participate in scheduled rounds or challenge a rival to a head-to-head duel.</p>
        </div>
        <div class="col-md-4 text-end">
            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#createContestModal">
                <i class="bi bi-plus-circle"></i> Create Local Contest
            </button>
        </div>
    </div>

    <div class="card shadow-sm mb-5">
        <div class="card-header bg-white fw-bold">Active & Upcoming Rounds</div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Contest Name</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Participants</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contests as $c): 
                        $statusColor = ($c['status'] === 'Active')
                            ? 'success'
                            : (($c['status'] === 'Upcoming') ? 'primary' : 'secondary');
                    ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($c['name']) ?></td>
                            <td><span class="badge border text-dark"><?= htmlspecialchars($c['type']) ?></span></td>
                            <td><?= date('M d, Y', strtotime($c['date'])) ?></td>
                            <td><?= (int)$c['participants'] ?> users</td>
                            <td>
                                <span class="badge bg-<?= $statusColor ?>">
                                    <?= htmlspecialchars($c['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($c['status'] !== 'Past'): ?>
                                    <a href="contest_view.php?id=<?= htmlspecialchars($c['id']) ?>" class="btn btn-sm btn-primary">Enter</a>
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
        <div class="card border-info shadow-sm">
            <div class="card-body">
                <h5 class="card-title text-info">
                    <i class="bi bi-lightning-fill"></i> Head-to-Head Duel
                </h5>
                <p class="card-text small text-muted">
                    Compete against another user in real-time. Names update automatically from the database.
                </p>

                <form action="duel_logic.php" method="POST">
                    <div class="input-group mb-3">
                        <span class="input-group-text bg-light">You</span>

                        <?php
                        // Fetch YOUR current name (assuming you are u1)
                        $stmt = $pdo->prepare("SELECT username FROM Users WHERE id = ?");
                        $stmt->execute(['u1']);
                        $me = $stmt->fetch();
                        ?>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($me['username']) ?>" readonly>

                        <span class="input-group-text bg-light">VS</span>

                        <select name="rival" class="form-select">
                            <?php
                            // Fetch all users EXCEPT yourself
                            $stmt = $pdo->prepare("SELECT id, username FROM Users WHERE id != ?");
                            $stmt->execute(['u1']);
                            $rivals = $stmt->fetchAll();

                            foreach ($rivals as $r) {
                                echo "<option value='{$r['id']}'>" . htmlspecialchars($r['username']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-info w-100 text-white fw-bold">
                        Start Duel
                    </button>
                </form>
            </div>
        </div>
    </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
