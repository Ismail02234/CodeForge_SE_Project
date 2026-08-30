<?php
// index.php
require_once 'db.php';
require_once 'includes/recommendation_engine.php';

$uId = 1;
$rec = getRecommendations($uId, $pdo, 3);
$recommendations = $rec['problems'];
$recExplanation = $rec['explanation'] ?? 'Keep solving to build your skill profile!';
$congratsMsg = $rec['congrats_message'] ?? null;
$lastVerdict = $_GET['verdict'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CodeForge - Quest Advisor</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #0f172a; color: #f8fafc; padding: 40px; display: flex; justify-content: center; }
        .container { width: 680px; background: #1e293b; padding: 30px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); border: 1px solid #334155; }
        h2 { margin-top: 0; color: #38bdf8; }
        .ai-banner { background: #022c22; border-left: 4px solid #10b981; padding: 16px; border-radius: 6px; margin-bottom: 20px; }
        .ai-banner strong { color: #34d399; font-size: 15px; }
        .congrats-banner { background: #1e3a8a; border-left: 4px solid #3b82f6; padding: 16px; border-radius: 6px; margin-bottom: 20px; font-weight: 500; }
        .problem-card { background: #0f172a; border: 1px solid #334155; padding: 16px; border-radius: 8px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; }
        .btn-submit { background: #6366f1; color: white; border: none; padding: 9px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; }
        .btn-submit:hover { background: #4f46e5; }
        .alert { background: #1e40af; color: white; padding: 10px; border-radius: 6px; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="container">
    <h2>🎯 CodeForge Quest Advisor</h2>

    <?php if ($lastVerdict): ?>
        <div class="alert">
            Judge Verdict: <strong><?= htmlspecialchars($lastVerdict) ?></strong> (Skill Profile Recalculated!)
        </div>
    <?php endif; ?>

    <?php if ($congratsMsg): ?>
        <div class="congrats-banner">
            <?= htmlspecialchars($congratsMsg) ?>
        </div>
    <?php endif; ?>
    
    <div class="ai-banner">
        <strong>🤖 Why this current problem set?</strong>
        <p style="margin: 6px 0 0 0; font-size: 14px; line-height: 1.5;"><?= htmlspecialchars($recExplanation) ?></p>
    </div>

    <h3>Targeted Challenges for You:</h3>
    <?php if (!empty($recommendations)): ?>
        <?php foreach ($recommendations as $index => $p): ?>
            <div class="problem-card">
                <div>
                    <strong style="font-size: 16px;"><?= htmlspecialchars($p['title']) ?></strong><br>
                    <small style="color: #94a3b8;">Rating: <?= $p['rating'] ?> | Tags: <?= htmlspecialchars($p['tags']) ?></small>
                </div>
                <form id="solve-form-<?= $index ?>" action="solve.php" method="POST" style="margin: 0;">
                    <input type="hidden" name="problem_id" value="<?= (int)$p['problem_id'] ?>">
                    <input type="hidden" name="code" value="int main() { return 0; }">
                    <button type="submit" class="btn-submit">Submit Code 🚀</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="color: #94a3b8;">No new problems found right now.</p>
    <?php endif; ?>
</div>

</body>
</html>