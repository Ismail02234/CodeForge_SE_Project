<?php 
require_once 'config/db.php'; 
include 'includes/header.php'; 
?>

<div class="container mt-5">
    <div class="text-center mb-5">
        <h1 class="display-5 fw-bold text-primary">Technical Documentation</h1>
        <p class="lead">An explanation of the Advanced Relational Logic and SQL queries powering CodeForge</p>
    </div>

    <div class="accordion shadow-sm" id="logicAccordion">
        
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#secRec">
                    <i class="bi bi-lightbulb-fill me-2 text-warning"></i> 1. Smart Recommendation Engine
                </button>
            </h2>
            <div id="secRec" class="accordion-collapse collapse show" data-bs-parent="#logicAccordion">
                <div class="accordion-body">
                    <p>The system identifies a user's "Weakest Topic" by performing a <strong>Left Join</strong> and finding the category with the minimum solve count. It then suggests problems using <strong>Set Difference</strong> logic (the <code>NOT IN</code> clause).</p>
                    <div class="bg-dark text-info p-3 rounded font-monospace small mb-3">
                        -- Finding the topic with the lowest solve count --<br>
                        SELECT p.topic, COUNT(s.id) as solved_count <br>
                        FROM Problems p <br>
                        LEFT JOIN Submissions s ON p.id = s.problemId AND s.userId = 'u1' AND s.verdict = 'AC'<br>
                        GROUP BY p.topic ORDER BY solved_count ASC LIMIT 1;
                    </div>
                    <div class="bg-dark text-info p-3 rounded font-monospace small">
                        -- Recommending unsolved problems from that topic --<br>
                        SELECT * FROM Problems WHERE topic = 'Arrays' <br>
                        AND id NOT IN (SELECT problemId FROM Submissions WHERE userId = 'u1' AND verdict = 'AC');
                    </div>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secMastery">
                    <i class="bi bi-bar-chart-fill me-2 text-info"></i> 2. Topic Mastery Aggregation
                </button>
            </h2>
            <div id="secMastery" class="accordion-collapse collapse" data-bs-parent="#logicAccordion">
                <div class="accordion-body">
                    <p>This feature uses a <strong>Correlated Subquery</strong>. For every unique topic in the <code>Problems</code> table, the system calculates a live count of how many problems the user has solved in that specific category.</p>
                    
                    <div class="bg-dark text-info p-3 rounded font-monospace small">
                        SELECT p.topic, <br>
                        COUNT(p.id) as total_probs,<br>
                        (SELECT COUNT(DISTINCT s.problemId) FROM Submissions s <br>
                         WHERE s.userId = 'u1' AND s.verdict = 'AC' <br>
                         AND s.problemId IN (SELECT id FROM Problems WHERE topic = p.topic)) as solved_count<br>
                        FROM Problems p GROUP BY p.topic;
                    </div>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secJoins">
                    <i class="bi bi-link-45deg me-2"></i> 3. Relational Joins (Submissions)
                </button>
            </h2>
            <div id="secJoins" class="accordion-collapse collapse" data-bs-parent="#logicAccordion">
                <div class="accordion-body">
                    <p>To display submission history, the system performs an <strong>Inner Join</strong> between the <code>Submissions</code> and <code>Problems</code> tables to fetch human-readable titles instead of IDs.</p>
                    
                    <div class="bg-dark text-info p-3 rounded font-monospace small">
                        SELECT s.verdict, s.language, p.title <br>
                        FROM Submissions s <br>
                        INNER JOIN Problems p ON s.problemId = p.id <br>
                        WHERE s.userId = 'u1';
                    </div>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secSearch">
                    <i class="bi bi-search me-2"></i> 4. Pattern Matching (Global Search)
                </button>
            </h2>
            <div id="secSearch" class="accordion-collapse collapse" data-bs-parent="#logicAccordion">
                <div class="accordion-body">
                    <p>The Global Search uses the <code>LIKE</code> operator with the <code>%</code> wildcard to search for substrings within the <code>username</code>, <code>title</code>, and <code>name</code> columns across multiple tables.</p>
                    <div class="bg-dark text-info p-3 rounded font-monospace small">
                        SELECT * FROM Users WHERE username LIKE '%key%';<br>
                        SELECT * FROM Problems WHERE title LIKE '%key%';
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="mt-5 p-4 bg-light border rounded shadow-sm">
        <h5 class="fw-bold"><i class="bi bi-shield-check text-success"></i> We have used the following Concepts</h5>
        <ul class="small text-muted mt-2">
            <li><strong>Normalization (3NF):</strong> Data redundancy is minimized by linking tables via Primary and Foreign Keys.</li>
            <li><strong>Referential Integrity:</strong> Links to profiles and problems use unique IDs rather than volatile strings.</li>
            <li><strong>Aggregation:</strong> Use of <code>GROUP BY</code>, <code>COUNT</code>, and <code>SUM</code> for accurate reading.</li>
        </ul>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
