<?php 
include 'config/db.php'; 
include 'includes/rivalry_ai.php';
include 'includes/header.php';
 

// 1. Fetch all users for the dropdowns
$stmt = $pdo->query("SELECT id, username FROM Users ORDER BY username ASC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Get selected IDs from GET or default to first two users
$u1_id = $_GET['u1'] ?? ($users[0]['id'] ?? '');
$u2_id = $_GET['u2'] ?? ($users[1]['id'] ?? '');

// 3. Fetch user data securely using prepared statements
function fetchUser($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM Users WHERE id = :id");
    $stmt->execute(['id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$u1_data = fetchUser($pdo, $u1_id);
$u2_data = fetchUser($pdo, $u2_id);
// 4. Generate AI Rivalry Prediction
$prediction = null;

if ($u1_data && $u2_data && $u1_id !== $u2_id) {

    $prediction = calculateWinningProbability(
        $pdo,
        $u1_id,
        $u2_id
    );
}
?>

<div class="container mt-5">
    <div class="text-center mb-5">
        <h2 class="fw-bold"><i class="bi bi-fire text-danger"></i> Rivalry Comparison</h2>
        <p class="text-muted">Comparing live stats from the <code>Users</code> and <code>Universities</code> tables.</p>
    </div>
    
    <form class="row g-3 mb-5 justify-content-center" method="GET">
        <div class="col-md-4">
            <label class="form-label fw-bold">User 1</label>
            <select name="u1" class="form-select border-primary" onchange="this.form.submit()">
                <?php foreach($users as $user): ?>
                    <option value="<?= htmlspecialchars($user['id']) ?>" <?= ($u1_id == $user['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($user['username']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-1 text-center d-none d-md-block" style="margin-top: 45px;">
            <span class="badge bg-dark rounded-circle p-2">VS</span>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-bold">User 2</label>
            <select name="u2" class="form-select border-danger" onchange="this.form.submit()">
                <?php foreach($users as $user): ?>
                    <option value="<?= htmlspecialchars($user['id']) ?>" <?= ($u2_id == $user['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($user['username']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <?php if ($u1_data && $u2_data): ?>
        <!-- AI Rivalry Prediction -->
<?php if ($prediction): ?>

<div class="card shadow-lg border-0 mb-5">

    <div class="card-header bg-dark text-white text-center py-3">
        <h4 class="mb-0">
            🤖 AI Rivalry Prediction
        </h4>
    </div>

    <div class="card-body text-center">

        <p class="text-muted mb-4">
            Based on recent performance, accuracy,
            solving speed, difficulty, contest performance,
            topic strength and head-to-head history.
        </p>

        <div class="row">

            <!-- USER 1 -->
            <div class="col-md-5">

                <h5 class="fw-bold">
                    <?= htmlspecialchars($u1_data['username']) ?>
                </h5>

                <div class="display-4 fw-bold text-primary">
                    <?= $prediction['user1_probability'] ?>%
                </div>

                <p class="text-muted">
                    Predicted chance of winning
                </p>

            </div>


            <!-- VS -->
            <div class="col-md-2 d-flex align-items-center justify-content-center">

                <span class="badge bg-dark rounded-circle p-3">
                    VS
                </span>

            </div>


            <!-- USER 2 -->
            <div class="col-md-5">

                <h5 class="fw-bold">
                    <?= htmlspecialchars($u2_data['username']) ?>
                </h5>

                <div class="display-4 fw-bold text-danger">
                    <?= $prediction['user2_probability'] ?>%
                </div>

                <p class="text-muted">
                    Predicted chance of winning
                </p>

            </div>

        </div>


        <hr class="my-4">


        <!-- AI SCORE -->
         <!-- AI Explanation -->
<div class="mt-4">

    <h5 class="fw-bold mb-3">
        🧠 Why this prediction?
    </h5>

    <?php
    $p1 = $prediction['user1'];
    $p2 = $prediction['user2'];

    $reasons = [];

    // Recent Performance
    if ($p1['recent_performance'] > $p2['recent_performance']) {

        $difference =
            round(
                $p1['recent_performance']
                - $p2['recent_performance'],
                2
            );

        $reasons[] =
            htmlspecialchars($u1_data['username'])
            . " has "
            . $difference
            . "% better recent performance.";

    } elseif ($p2['recent_performance'] > $p1['recent_performance']) {

        $difference =
            round(
                $p2['recent_performance']
                - $p1['recent_performance'],
                2
            );

        $reasons[] =
            htmlspecialchars($u2_data['username'])
            . " has "
            . $difference
            . "% better recent performance.";
    }


    // Accuracy
    if ($p1['accuracy'] > $p2['accuracy']) {

        $difference =
            round(
                $p1['accuracy']
                - $p2['accuracy'],
                2
            );

        $reasons[] =
            htmlspecialchars($u1_data['username'])
            . " has "
            . $difference
            . "% higher submission accuracy.";

    } elseif ($p2['accuracy'] > $p1['accuracy']) {

        $difference =
            round(
                $p2['accuracy']
                - $p1['accuracy'],
                2
            );

        $reasons[] =
            htmlspecialchars($u2_data['username'])
            . " has "
            . $difference
            . "% higher submission accuracy.";
    }


    // Speed
    if ($p1['speed'] > $p2['speed']) {

        $reasons[] =
            htmlspecialchars($u1_data['username'])
            . " has better solving speed.";

    } elseif ($p2['speed'] > $p1['speed']) {

        $reasons[] =
            htmlspecialchars($u2_data['username'])
            . " has better solving speed.";
    }


    // Difficulty
    if ($p1['difficulty'] > $p2['difficulty']) {

        $reasons[] =
            htmlspecialchars($u1_data['username'])
            . " has stronger performance on difficult problems.";

    } elseif ($p2['difficulty'] > $p1['difficulty']) {

        $reasons[] =
            htmlspecialchars($u2_data['username'])
            . " has stronger performance on difficult problems.";
    }


    // Contest
    if ($p1['contest'] > $p2['contest']) {

        $reasons[] =
            htmlspecialchars($u1_data['username'])
            . " has better contest performance.";

    } elseif ($p2['contest'] > $p1['contest']) {

        $reasons[] =
            htmlspecialchars($u2_data['username'])
            . " has better contest performance.";
    }


    if (count($reasons) > 0):
    ?>

        <div class="row justify-content-center">

            <div class="col-md-8">

                <div class="alert alert-light border text-start">

                    <?php foreach ($reasons as $reason): ?>

                        <div class="mb-2">
                            🔹 <?= $reason ?>
                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

        </div>

    <?php endif; ?>

</div>
        <div class="row">

            <div class="col-md-6">

                <span class="text-muted d-block">
                    AI Performance Score
                </span>

                <strong class="text-primary">
                    <?= $prediction['user1']['final_score'] ?>
                </strong>

            </div>


            <div class="col-md-6">

                <span class="text-muted d-block">
                    AI Performance Score
                </span>

                <strong class="text-danger">
                    <?= $prediction['user2']['final_score'] ?>
                </strong>

            </div>

        </div>

    </div>

</div>

<?php endif; ?>
    <div class="row">
        <?php 
        $usersCompare = [
            ['data' => $u1_data, 'color' => 'primary'],
            ['data' => $u2_data, 'color' => 'danger']
        ];
        foreach($usersCompare as $index => $userInfo):
        ?>
            <?php if($index === 1): ?>
                <div class="col-md-2 d-flex align-items-center justify-content-center my-4 my-md-0">
                    <div class="vr d-none d-md-block h-100"></div>
                    <h1 class="display-3 text-muted opacity-25">⚔️</h1>
                    <div class="vr d-none d-md-block h-100"></div>
                </div>
            <?php endif; ?>

            <div class="col-md-5">
                <div class="card shadow-lg border-0 h-100">
                    <div class="card-header bg-<?= $userInfo['color'] ?> text-white text-center py-3">
                        <h4 class="mb-0"><?= htmlspecialchars($userInfo['data']['username']) ?></h4>
                        <small><?= htmlspecialchars($userInfo['data']['university']) ?></small>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <span class="text-muted d-block">Current Rating</span>
                            <h1 class="display-4 fw-bold text-<?= $userInfo['color'] ?>"><?= $userInfo['data']['rating'] ?></h1>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Solved Quests</span>
                            <span class="fw-bold"><?= $userInfo['data']['solvedCount'] ?></span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-<?= $userInfo['color'] ?>" style="width: <?= min(($userInfo['data']['solvedCount']/500)*100, 100) ?>%"></div>
                        </div>
                        <p class="mt-3 badge bg-light text-dark border"><?= htmlspecialchars($userInfo['data']['rank']) ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
