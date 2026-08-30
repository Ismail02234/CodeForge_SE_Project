<?php 
require_once 'config/db.php'; 
include 'includes/header.php'; 
?>

<div class="container mt-5 fade-in">
    <div class="text-center mb-5">
        <div class="display-1 mb-3">📖</div>
        <h1 class="display-5 fw-bold text-primary">Adventure Guide</h1>
        <p class="lead text-muted">Discover the magic behind CodeForge - the spells, artifacts, and ancient knowledge that power your coding quests!</p>
    </div>

    <div class="accordion shadow-sm mb-4" id="architectureAccordion">
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#secArch">
                    <i class="bi bi-diagram-3 me-2 text-primary"></i> 1. The Realm's Architecture
                </button>
            </h2>
            <div id="secArch" class="accordion-collapse collapse show" data-bs-parent="#architectureAccordion">
                <div class="accordion-body">
                    <p>CodeForge follows a <strong>Model-View-Controller (MVC)</strong> inspired architecture within a PHP monorepo structure. The application is organized into three primary layers:</p>
                    <ul>
                        <li><strong>Presentation Layer:</strong> PHP views rendered through Bootstrap 5, providing a responsive, component-based UI with a persistent navigation bar and card-driven layout.</li>
                        <li><strong>Application / Business Logic Layer:</strong> PHP controller scripts that orchestrate data fetching, user state management, gamification calculations, and access control checks.</li>
                        <li><strong>Data Access Layer:</strong> Centralized PDO-based database connections (<code>config/db.php</code>) with prepared statements throughout, preventing SQL injection at every query boundary.</li>
                    </ul>
                    <p>This separation of concerns ensures that changes to the database schema propagate through the data access layer without forcing changes to the UI, and business rules can evolve independently of presentation concerns.</p>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secDb">
                    <i class="bi bi-database me-2 text-info"></i> 2. Artifact Vault (Database Design)
                </button>
            </h2>
            <div id="secDb" class="accordion-collapse collapse" data-bs-parent="#architectureAccordion">
                <div class="accordion-body">
                    <p>The relational schema is designed in <strong>Third Normal Form (3NF)</strong>, eliminating redundancy while preserving referential integrity. The core entities and their relationships are:</p>
                    <ul>
                        <li><strong>Users</strong> — Central identity table; stores credentials, ratings, XP, and streak counters. Referenced as a foreign key by all transactional tables.</li>
                        <li><strong>Problems</strong> — Defines each coding quest with title, topic, difficulty, and solution stub. Self-contained; referenced by Submissions.</li>
                        <li><strong>Submissions</strong> — Join/transaction table linking Users to Problems. Stores verdict, language, timestamp, and execution time.</li>
                        <li><strong>Contests</strong> &amp; <strong>DuelSessions</strong> — Capture competitive event structures with participant lists and scoring rules.</li>
                        <li><strong>Badges</strong> &amp; <strong>UserBadges</strong> — Normalized many-to-many for the gamification system, separating badge definitions from user achievements.</li>
                    </ul>
                    <p>All inter-table references use integer primary/foreign keys rather than denormalized strings, enforcing referential integrity at the database level and enabling efficient indexed lookups.</p>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secAuth">
                    <i class="bi bi-shield-lock me-2 text-success"></i> 3. Guardian Spells (Authentication & Security)
                </button>
            </h2>
            <div id="secAuth" class="accordion-collapse collapse" data-bs-parent="#architectureAccordion">
                <div class="accordion-body">
                    <p>User authentication is handled through a secure session-based flow with the following safeguards:</p>
                    <ul>
                        <li><strong>Password Hashing:</strong> All passwords are hashed using bcrypt via PHP's <code>password_hash()</code> before storage. No plaintext credentials are ever written to the database.</li>
                        <li><strong>Session Management:</strong> Login state is tracked via PHP server-side sessions. Protected pages validate <code>$_SESSION['user_id']</code> at the top of every controller and redirect unauthenticated requests to the login page.</li>
                        <li><strong>Prepared Statements:</strong> Every database query uses PDO prepared statements with named placeholders, neutralizing SQL injection vectors even if user input contains malicious payloads.</li>
                        <li><strong>Output Escaping:</strong> All dynamic content rendered in HTML views is passed through <code>htmlspecialchars()</code> to prevent reflected XSS attacks.</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secGamification">
                    <i class="bi bi-trophy me-2 text-warning"></i> 4. Power-Up Engine (Gamification)
                </button>
            </h2>
            <div id="secGamification" class="accordion-collapse collapse" data-bs-parent="#architectureAccordion">
                <div class="accordion-body">
                    <p>The gamification system encourages sustained engagement through XP, levels, streaks, and badges. The engine lives in <code>includes/gamification.php</code> and is consumed across all controllers via inclusion.</p>
                    <ul>
                        <li><strong>XP &amp; Leveling:</strong> Users accumulate XP from solved problems and contest participation. Levels are threshold-based: each level requires a minimum XP total, and progress is tracked with a percentage bar.</li>
                        <li><strong>Streaks:</strong> A daily solve counter is maintained per user. Consecutive days of activity increment the streak; a missed day resets it. The longest streak is stored separately as a persistent record.</li>
                        <li><strong>Badges:</strong> Achievements are defined in the <code>Badges</code> table and awarded conditionally. The <code>UserBadges</code> join table records which users have earned each badge, enabling fast badge-gallery queries.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="accordion shadow-sm mb-4" id="logicAccordion">
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#secRec">
                    <i class="bi bi-lightbulb-fill me-2 text-warning"></i> 5. Quest Recommendation Logic
                </button>
            </h2>
            <div id="secRec" class="accordion-collapse collapse show" data-bs-parent="#logicAccordion">
                <div class="accordion-body">
                    <p>The recommendation engine identifies a user's weakest topic using a <strong>LEFT JOIN</strong> combined with <code>GROUP BY</code> and <code>ORDER BY ... LIMIT 1</code> to find the topic with the fewest accepted solves. It then recommends unsolved problems from that topic using a <strong>Set Difference</strong> pattern with <code>NOT IN</code>.</p>
                    <div class="bg-dark text-info p-3 rounded font-monospace small mb-3">
                        -- Identify weakest topic for a given hero --<br>
                        SELECT p.topic, COUNT(s.id) AS solved_count<br>
                        FROM Problems p<br>
                        LEFT JOIN Submissions s<br>
                            ON p.id = s.problemId<br>
                            AND s.userId = :uid<br>
                            AND s.verdict = 'AC'<br>
                        GROUP BY p.topic<br>
                        ORDER BY solved_count ASC<br>
                        LIMIT 1;
                    </div>
                    <div class="bg-dark text-info p-3 rounded font-monospace small">
                        -- Recommend unsolved quests from that realm --<br>
                        SELECT * FROM Problems<br>
                        WHERE topic = :topic<br>
                        AND id NOT IN (<br>
                            SELECT problemId FROM Submissions<br>
                            WHERE userId = :uid AND verdict = 'AC'<br>
                        )<br>
                        LIMIT 3;
                    </div>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secMastery">
                    <i class="bi bi-bar-chart-fill me-2 text-info"></i> 6. Skill Tree Aggregation
                </button>
            </h2>
            <div id="secMastery" class="accordion-collapse collapse" data-bs-parent="#logicAccordion">
                <div class="accordion-body">
                    <p>Mastery is computed with a <strong>correlated subquery</strong>. For each unique topic in the Problems table, a live count of distinct accepted problem IDs for the current user is evaluated. This count is paired with the total problem count per topic to derive a completion percentage, powering the Skill Proficiency dashboard.</p>
                    <div class="bg-dark text-info p-3 rounded font-monospace small">
                        SELECT p.topic,<br>
                        COUNT(p.id) AS total_probs,<br>
                        (<br>
                            SELECT COUNT(DISTINCT s.problemId)<br>
                            FROM Submissions s<br>
                            WHERE s.userId = :uid<br>
                            AND s.problemId IN (SELECT id FROM Problems WHERE topic = p.topic)<br>
                            AND s.verdict = 'AC'<br>
                        ) AS solved_count<br>
                        FROM Problems p<br>
                        GROUP BY p.topic;
                    </div>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secJoins">
                    <i class="bi bi-link-45deg me-2"></i> 7. Relational Joins in Quest History
                </button>
            </h2>
            <div id="secJoins" class="accordion-collapse collapse" data-bs-parent="#logicAccordion">
                <div class="accordion-body">
                    <p>To present a human-readable submission history, the system performs an <strong>Inner Join</strong> between <code>Submissions</code> and <code>Problems</code>, replacing opaque problem IDs with titles, and filtering by the active user. This pattern is reused across the profile and submission-log views.</p>
                    <div class="bg-dark text-info p-3 rounded font-monospace small">
                        SELECT s.verdict, s.language, s.submitted_at, p.title<br>
                        FROM Submissions s<br>
                        INNER JOIN Problems p ON s.problemId = p.id<br>
                        WHERE s.userId = :uid<br>
                        ORDER BY s.submitted_at DESC;
                    </div>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secSearch">
                    <i class="bi bi-search me-2"></i> 8. Pattern Matching for Global Search
                </button>
            </h2>
            <div id="secSearch" class="accordion-collapse collapse" data-bs-parent="#logicAccordion">
                <div class="accordion-body">
                    <p>The global search feature leverages the <code>LIKE</code> operator with the <code>%</code> wildcard to perform substring matching across the <code>username</code>, <code>title</code>, and <code>name</code> columns of multiple tables, returning unified results in a single interface.</p>
                    <div class="bg-dark text-info p-3 rounded font-monospace small">
                        SELECT id, username FROM Users WHERE username LIKE :q<br>
                        UNION<br>
                        SELECT id, title AS name FROM Problems WHERE title LIKE :q;<br>
                        -- Parameter :q is bound as '%{$searchTerm}%'
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="accordion shadow-sm mb-4" id="patternsAccordion">
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#secPatterns">
                    <i class="bi bi-stack me-2 text-secondary"></i> 9. Design Patterns & Software Engineering Principles
                </button>
            </h2>
            <div id="secPatterns" class="accordion-collapse collapse show" data-bs-parent="#patternsAccordion">
                <div class="accordion-body">
                    <p>Beyond raw SQL logic, CodeForge applies several foundational software engineering practices:</p>
                    <ul>
                        <li><strong>Separation of Concerns:</strong> Shared layout markup is factored into <code>includes/header.php</code> and <code>includes/footer.php</code>, ensuring consistent navigation and a single point of maintenance for the site-wide UI.</li>
                        <li><strong>DRY (Don't Repeat Yourself):</strong> The gamification engine is encapsulated in a single include file, keeping XP, level, and badge logic consistent across dashboard, profile, and contest views.</li>
                        <li><strong>Fail-Fast Session Protection:</strong> Each protected page validates the session at the very top, redirecting to login before any rendering occurs. No sensitive logic is exposed to unauthenticated users.</li>
                        <li><strong>Database Normalization (3NF):</strong> Data redundancy is minimized by linking tables via Primary and Foreign Keys. For example, badge definitions live in a single <code>Badges</code> table, and user achievements reference them via a join table — avoiding duplicated badge metadata.</li>
                        <li><strong>Referential Integrity:</strong> All relationships are enforced at the schema level. Deleting a user cascades appropriately, and orphaned submissions cannot exist without a valid <code>problemId</code> or <code>userId</code>.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="accordion shadow-sm mb-4" id="frontendAccordion">
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#secFrontend">
                    <i class="bi bi-layers me-2 text-danger"></i> 10. Frontend & User Experience Design
                </button>
            </h2>
            <div id="secFrontend" class="accordion-collapse collapse show" data-bs-parent="#frontendAccordion">
                <div class="accordion-body">
                    <p>The frontend is built on <strong>Bootstrap 5</strong> for a responsive, mobile-first layout. Key UX design decisions include:</p>
                    <ul>
                        <li><strong>Persistent Navigation:</strong> A sticky navbar with contextual links to all major modules remains visible across every page, providing clear orientation for young adventurers.</li>
                        <li><strong>Card-Based Information Architecture:</strong> Dashboard panels use card components with icon headers and progress bars, making dense analytics (skill proficiency, XP progress, streaks) scannable at a glance.</li>
                        <li><strong>Icon-Driven Visual Language:</strong> Bootstrap Icons are used consistently as section markers and action affordances, reducing cognitive load and improving accessibility without relying solely on color.</li>
                        <li><strong>Contextual Feedback:</strong> Success alerts and progress bars provide immediate feedback, encouraging continued engagement and making learning feel like an adventure.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</div>

<?php include 'includes/footer.php'; ?>
