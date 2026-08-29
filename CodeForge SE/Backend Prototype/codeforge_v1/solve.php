<?php
session_start();
include 'config/db.php';
require_once 'includes/gamification.php';
require_once 'includes/recommendation_engine.php';
require_once 'includes/mock_judge.php';
include 'includes/header.php';

$pId = $_GET['id'] ?? '';
if (!$pId) {
    die("Invalid quest ID.");
}

$uId = $_SESSION['user_id'] ?? 'u1';

$stmt = $pdo->prepare("SELECT * FROM Problems WHERE id = :pid");
$stmt->execute(['pid' => $pId]);
$problem = $stmt->fetch();

if (!$problem) {
    die("Quest not found.");
}

$alreadySolved = false;
$checkStmt = $pdo->prepare("
    SELECT id FROM Submissions
    WHERE userId = :uid
      AND problemId = :pid
      AND verdict = 'AC'
");
$checkStmt->execute(['uid' => $uId, 'pid' => $pId]);

if ($checkStmt->rowCount() > 0) {
    $alreadySolved = true;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if ($alreadySolved) {
        echo "<script>alert('You have already conquered this quest. Resubmission will not affect your stats.');</script>";
    } else {
        $subId = 'sub_' . uniqid();
        $language = $_POST['language'] ?? 'C++';

        $judgment = mockJudge($_POST['code'] ?? '', $problem);
        $verdict = $judgment['verdict'];

        try {
            $pdo->beginTransaction();

            $insertSub = $pdo->prepare("
                INSERT INTO Submissions (id, problemId, userId, verdict, language, runtime, memory, failedTestCase)
                VALUES (:sid, :pid, :uid, :verdict, :language, :runtime, :memory, :failedTestCase)
            ");
            $insertSub->execute([
                'sid' => $subId,
                'pid' => $pId,
                'uid' => $uId,
                'verdict' => $verdict,
                'language' => $language,
                'runtime' => $judgment['runtime'],
                'memory' => $judgment['memory'],
                'failedTestCase' => $judgment['failedTestCase'],
            ]);

            if ($verdict === 'AC') {
                $updateProblem = $pdo->prepare("
                    UPDATE Problems
                    SET solvedBy = solvedBy + 1
                    WHERE id = :pid
                ");
                $updateProblem->execute(['pid' => $pId]);

                if ($updateProblem->rowCount() === 0) {
                    throw new RuntimeException('Failed to update problem solve count.');
                }

                $xpMap = ['Easy' => 10, 'Medium' => 25, 'Hard' => 50];
                $xpEarned = $xpMap[$problem['difficulty']] ?? 10;

                try {
                    $newBadges = checkAndAwardBadges($uId, $pdo);
                } catch (Exception $e) {
                    $newBadges = [];
                }

                $showCelebration = true;
            }

            $pdo->commit();

            updateUserTagStats($uId, $pdo);

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo "<div class='alert alert-danger'>Submission failed: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}
?>

<div class="container mt-4 fade-in">
    <?php if (isset($showCelebration)): ?>
        <div class="alert border-0 shadow-sm bounce-in" style="background: var(--gold-light); border: 2px solid var(--gold) !important;">
            <div class="text-center">
                <div class="display-1 mb-2">🎉</div>
                <h4 class="alert-heading fw-bold text-warning">
                    <i class="bi bi-trophy-fill me-2"></i> Quest Complete!
                </h4>
                <p class="mb-1 fs-5">
                    You earned <strong class="text-primary"><?= $xpEarned ?> XP</strong>
                    by conquering a <strong><?= htmlspecialchars(difficultyKidLabel($problem['difficulty'])) ?></strong> quest!
                </p>
                <?php if (!empty($newBadges)): ?>
                    <p class="mb-0 mt-3">
                        <strong>New trophy unlocked:</strong><br>
                        <?php foreach ($newBadges as $badge): ?>
                            <span class="badge bg-warning text-dark mt-2">
                                <i class="bi <?= htmlspecialchars($badge['icon']) ?>"></i>
                                <?= htmlspecialchars($badge['name']) ?>
                            </span>
                        <?php endforeach; ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-5">
            <div class="card border-0 quest-card bg-light">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="display-4">
                            <?php
                            $difficulty = $problem['difficulty'] ?? 'Easy';
                            echo match($difficulty) {
                                'Easy' => '🌱',
                                'Medium' => '🌿',
                                'Hard' => '🌳',
                                default => '⭐'
                            };
                            ?>
                        </span>
                        <div>
                            <h3 class="mb-0 fw-bold"><?= htmlspecialchars($problem['title']) ?></h3>
                            <span class="badge bg-info text-dark">
                                <i class="bi bi-tag me-1"></i> <?= htmlspecialchars($problem['topic']) ?>
                            </span>
                        </div>
                    </div>
                    <hr>
                    <p class="mb-0">
                        <i class="bi bi-scroll me-2 text-primary"></i>
                        Implement the logic for <strong><?= htmlspecialchars($problem['title']) ?></strong>.
                        <br><br>
                        <i class="bi bi-lightbulb me-2 text-warning"></i>
                        Ensure your solution handles all edge cases and passes all tests!
                    </p>

                    <?php if ($alreadySolved): ?>
                        <div class="alert alert-success mt-3 border-0">
                            ✅ You have already conquered this quest!
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title fw-bold mb-3">
                        <i class="bi bi-terminal text-success me-2"></i> Submit Your Spell
                    </h5>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted">
                                <i class="bi bi-code me-1"></i> Your Code
                            </label>
                            <textarea
                                class="form-control font-monospace"
                                rows="10"
                                placeholder="Write your magical code here..."
                                <?= $alreadySolved ? 'disabled' : 'required' ?>
                            ></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted">
                                <i class="bi bi-file-code me-1"></i> Language
                            </label>
                            <select class="form-select" name="language" <?= $alreadySolved ? 'disabled' : '' ?>>
                                <option value="C++">C++</option>
                                <option value="Python">Python</option>
                                <option value="Java">Java</option>
                                <option value="JavaScript">JavaScript</option>
                            </select>
                        </div>

                        <button
                            type="submit"
                            class="btn btn-success w-100 py-3 fw-bold"
                            <?= $alreadySolved ? 'disabled' : '' ?>
                        >
                            <i class="bi bi-magic me-2"></i> Cast Spell
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
