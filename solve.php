<?php
session_start();
include 'config/db.php';
require_once 'includes/gamification.php';
include 'includes/header.php';

// ---------------------------
// 1. Get Problem ID safely
// ---------------------------
$pId = $_GET['id'] ?? '';
if (!$pId) {
    die("Invalid problem ID.");
}

// ---------------------------
// 2. Get logged-in user
// ---------------------------
$uId = $_SESSION['user_id'] ?? 'u1'; // demo fallback user

// ---------------------------
// 3. Fetch problem
// ---------------------------
$stmt = $pdo->prepare("SELECT * FROM Problems WHERE id = :pid");
$stmt->execute(['pid' => $pId]);
$problem = $stmt->fetch();

if (!$problem) {
    die("Quest not found.");
}

// ---------------------------
// 4. Check if already solved
// ---------------------------
$alreadySolved = false;

$checkStmt = $pdo->prepare("
    SELECT id FROM Submissions
    WHERE userId = :uid
      AND problemId = :pid
      AND verdict = 'AC'
");
$checkStmt->execute([
    'uid' => $uId,
    'pid' => $pId
]);

if ($checkStmt->rowCount() > 0) {
    $alreadySolved = true;
}

// ---------------------------
// 5. Handle Submission
// ---------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // ❌ Block resubmission if already solved
    if ($alreadySolved) {
        echo "<script>alert('You have already solved this problem. Resubmission will not affect stats.');</script>";
    } else {
$subId = 'sub_' . uniqid();

$solvingTime = (int)($_POST['solving_time'] ?? 0);



        try {
            // Begin transaction (important for consistency)
            $pdo->beginTransaction();

            // 5.1 Insert submission
            $insertSub = $pdo->prepare("
    INSERT INTO Submissions
    (id, problemId, userId, verdict, language, solving_time)
    VALUES
    (:sid, :pid, :uid, 'AC', 'C++', :solving_time)
");

$insertSub->execute([
    'sid' => $subId,
    'pid' => $pId,
    'uid' => $uId,
    'solving_time' => $solvingTime
]);

            // 5.2 Update problem solved count (ONLY ON FIRST SOLVE)
            $pdo->prepare("
                UPDATE Problems
                SET solvedBy = solvedBy + 1
                WHERE id = :pid
            ")->execute(['pid' => $pId]);

            // Commit transaction
            $pdo->commit();

            $xpMap = ['Easy' => 10, 'Medium' => 25, 'Hard' => 50];
            $xpEarned = $xpMap[$problem['difficulty']] ?? 10;

            try {
                $newBadges = checkAndAwardBadges($uId, $pdo);
            } catch (Exception $e) {
                $newBadges = [];
            }

            $showCelebration = true;

        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<div class='alert alert-danger'>Submission failed.</div>";
        }
    }
}
?>

<div class="container mt-4">
    <?php if (isset($showCelebration)): ?>
        <div class="alert alert-success border-0 shadow-sm">
            <h4 class="alert-heading">
                <i class="bi bi-trophy-fill text-warning"></i> Quest Complete!
            </h4>
            <p class="mb-1">
                You earned <strong><?= $xpEarned ?> XP</strong>
                by solving a <strong><?= htmlspecialchars(difficultyKidLabel($problem['difficulty'])) ?></strong> quest!
            </p>
            <?php if (!empty($newBadges)): ?>
                <p class="mb-0">
                    New badge unlocked:
                    <?php foreach ($newBadges as $badge): ?>
                        <span class="badge bg-warning text-dark">
                            <i class="bi <?= htmlspecialchars($badge['icon']) ?>"></i>
                            <?= htmlspecialchars($badge['name']) ?>
                        </span>
                    <?php endforeach; ?>
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <div class="row">
        <!-- Problem Panel -->
        <div class="col-md-5">
            <div class="card border-0 bg-light p-3 shadow-sm">
                <h3><?= htmlspecialchars($problem['title']) ?></h3>
                <span class="badge bg-info text-dark mb-2">
                    <?= htmlspecialchars($problem['topic']) ?>
                </span>
                <hr>
                <p>
                    Implement the logic for <strong><?= htmlspecialchars($problem['title']) ?></strong>.
                    Ensure your solution handles all edge cases.
                </p>

                <?php if ($alreadySolved): ?>
                    <div class="alert alert-success">
                        ✅ You have already solved this problem.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Submission Panel -->
        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST">
                        <input
        type="hidden"
        name="solving_time"
        id="solving_time"
        value="0"
    >
                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted">
                                Submit Your Solution
                            </label>
                            <div class="alert alert-light border d-flex justify-content-between align-items-center">
    <span>
        <i class="bi bi-stopwatch"></i>
        Solving Time
    </span>

    <strong id="timer">
        00:00
    </strong>
</div>
                            <textarea
                                class="form-control"
                                rows="10"
                                placeholder="Enter your code here..."
                                <?= $alreadySolved ? 'disabled' : 'required' ?>
                            ></textarea>
                        </div>

                        <button
                            type="submit"
                            class="btn btn-success w-100"
                            <?= $alreadySolved ? 'disabled' : '' ?>
                        >
                            <i class="bi bi-cloud-arrow-up"></i> Submit
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener("DOMContentLoaded", function () {

    let startTime = Date.now();

    let timerElement = document.getElementById("timer");
    let solvingTimeInput = document.getElementById("solving_time");

    console.log("Timer started");
    console.log("Timer element:", timerElement);
    console.log("Input element:", solvingTimeInput);

    setInterval(function () {

        let elapsedSeconds =
            Math.floor((Date.now() - startTime) / 1000);

        let minutes = Math.floor(elapsedSeconds / 60);
        let seconds = elapsedSeconds % 60;

        minutes = String(minutes).padStart(2, "0");
        seconds = String(seconds).padStart(2, "0");

        if (timerElement) {
            timerElement.innerText =
                minutes + ":" + seconds;
        }

        if (solvingTimeInput) {
            solvingTimeInput.value = elapsedSeconds;
        }

        console.log("Timer:", elapsedSeconds);

    }, 1000);

});
</script>


<?php include 'includes/footer.php'; ?>
