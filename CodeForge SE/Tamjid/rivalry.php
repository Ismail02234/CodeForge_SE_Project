<?php 
require_once 'db.php'; 
require_once 'includes/rivalry_engine.php'; 
 
$userA = 1;  
$userB = 2; 
 
$result = calculateWinProbability($userA, $userB, $pdo); 
?> 
<!DOCTYPE html> 
<html lang="en"> 
<head> 
    <meta charset="UTF-8"> 
    <title>CodeForge - Head-to-Head Rivalry</title> 
    <style> 
        body { font-family: 'Segoe UI', sans-serif; background: #0f172a; color: #f8fafc; padding: 40px; display: flex; justify-content: center; } 
        .box { width: 550px; background: #1e293b; padding: 30px; border-radius: 12px; border: 1px solid #334155; box-shadow: 0 10px 25px rgba(0,0,0,0.5); } 
        h2 { color: #38bdf8; text-align: center; margin-top: 0; } 
        .prob-bar { background: #334155; border-radius: 6px; overflow: hidden; display: flex; height: 30px; margin: 25px 0; } 
        .user-a { background: #6366f1; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px; } 
        .user-b { background: #ec4899; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px; } 
        .stats-box { background: #0f172a; padding: 15px; border-radius: 8px; border: 1px solid #334155; font-size: 14px; color: #94a3b8; } 
    </style> 
</head> 
<body> 
 
<div class="box"> 
    <h2>⚔️ AI Head-to-Head Rivalry</h2> 
     
    <p style="text-align: center; font-size: 18px; margin-top: 20px;"> 
        <strong>User A</strong> has a <strong><?= $result['userA_prob'] ?>%</strong> predicted chance of winning. 
    </p> 
 
    <div class="prob-bar"> 
        <div class="user-a" style="width: <?= $result['userA_prob'] ?>%;">User A: <?= $result['userA_prob'] ?>%</div> 
        <div class="user-b" style="width: <?= $result['userB_prob'] ?>%;">User B: <?= $result['userB_prob'] ?>%</div> 
    </div> 
 
    <div class="stats-box"> 
        <p style="margin-top: 0; color: #f8fafc;"><strong>📊 Analyzed Metrics Breakdown:</strong></p> 
        <ul style="margin-bottom: 0; padding-left: 20px;"> 
            <li><strong>User A:</strong> Accuracy <?= $result['statsA']['accuracy'] ?>% | Solved: <?= $result['statsA']['solved_count'] ?> | Avg Rating: <?= $result['statsA']['avg_rating'] ?></li> 
            <li style="margin-top: 8px;"><strong>User B:</strong> Accuracy <?= $result['statsB']['accuracy'] ?>% | Solved: <?= $result['statsB']['solved_count'] ?> | Avg Rating: <?= $result['statsB']['avg_rating'] ?></li> 
        </ul> 
    </div> 
</div> 
 
</body> 
</html>