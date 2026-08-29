<?php
require_once 'db.php';
require_once 'includes/mock_judge.php';
require_once 'includes/recommendation_engine.php';

$uId = 1;
$problemId = $_POST['problem_id'] ?? 1;
$code = $_POST['code'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judgeResult = mockJudge($code, ['problem_id' => $problemId]);
    $verdict = $judgeResult['verdict'];

    $insertSub = $pdo->prepare("INSERT INTO submissions (user_id, problem_id, status) VALUES (?, ?, ?)");
    $insertSub->execute([$uId, $problemId, $verdict]);

    updateUserTagStats($uId, $pdo);

    header("Location: index.php?verdict=" . urlencode($verdict));
    exit;
}
?>