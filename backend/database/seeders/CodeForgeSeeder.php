<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CodeForgeSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('users')->exists()) {
            $this->command?->info('CodeForge data already exists; demo seed skipped.');

            return;
        }

        DB::transaction(function () {
            DB::table('universities')->insert([
                ['name' => 'National Institute', 'city' => 'Dhaka'],
                ['name' => 'State College of Engineering', 'city' => 'Chattogram'],
                ['name' => 'Tech University of Science', 'city' => 'Dhaka'],
                ['name' => 'Metropolitan Computing Academy', 'city' => 'Khulna'],
            ]);

            $userPassword = Hash::make('123456');
            DB::table('users')->insert([
                ['id' => 'u1', 'username' => 'Ismail', 'password' => $userPassword, 'role' => 'user', 'rating' => 1451, 'university' => 'Tech University of Science', 'rank' => 'Specialist', 'created_at' => now()],
                ['id' => 'u2', 'username' => 'Tamjid', 'password' => $userPassword, 'role' => 'user', 'rating' => 1520, 'university' => 'State College of Engineering', 'rank' => 'Expert', 'created_at' => now()],
                ['id' => 'u3', 'username' => 'David', 'password' => $userPassword, 'role' => 'user', 'rating' => 1300, 'university' => 'National Institute', 'rank' => 'Pupil', 'created_at' => now()],
                ['id' => 'u4', 'username' => 'Anon', 'password' => $userPassword, 'role' => 'user', 'rating' => 980, 'university' => 'Tech University of Science', 'rank' => 'Newbie', 'created_at' => now()],
                ['id' => 'u_admin', 'username' => 'Admin', 'password' => Hash::make('admin123'), 'role' => 'admin', 'rating' => 1680, 'university' => 'Metropolitan Computing Academy', 'rank' => 'Expert', 'created_at' => now()],
            ]);

            $starter = "#include <bits/stdc++.h>\nusing namespace std;\n\nint main() {\n    // your code\n    return 0;\n}";
            DB::table('problems')->insert([
                ['id' => 'p1', 'title' => 'Hello World Logic', 'topic' => 'Basic', 'difficulty' => 'Easy', 'description' => 'Read a name and print a friendly greeting.', 'tags' => 'intro,io,syntax', 'starter_code' => $starter],
                ['id' => 'p2', 'title' => 'Even or Odd', 'topic' => 'Math', 'difficulty' => 'Easy', 'description' => 'Print EVEN if n is divisible by 2; otherwise print ODD.', 'tags' => 'math,condition', 'starter_code' => $starter],
                ['id' => 'p3', 'title' => 'Array Sum', 'topic' => 'Arrays', 'difficulty' => 'Easy', 'description' => 'Compute the sum of n integers using a linear scan.', 'tags' => 'arrays,loops', 'starter_code' => $starter],
                ['id' => 'p4', 'title' => 'Tree Query', 'topic' => 'Trees', 'difficulty' => 'Hard', 'description' => 'Answer ancestor-distance queries on a rooted tree.', 'tags' => 'trees,lca,binary-lifting', 'starter_code' => $starter],
                ['id' => 'p5', 'title' => 'String Hashing', 'topic' => 'Strings', 'difficulty' => 'Medium', 'description' => 'Answer substring equality queries with rolling hashes.', 'tags' => 'strings,hashing', 'starter_code' => $starter],
                ['id' => 'p6', 'title' => 'Shortest Path Sprint', 'topic' => 'Graphs', 'difficulty' => 'Medium', 'description' => 'Find shortest distances from node 1 in a positively weighted graph.', 'tags' => 'graphs,dijkstra', 'starter_code' => $starter],
                ['id' => 'p7', 'title' => 'Knapsack Core', 'topic' => 'DP', 'difficulty' => 'Hard', 'description' => 'Maximize value under a capacity constraint.', 'tags' => 'dp,knapsack', 'starter_code' => $starter],
                ['id' => 'p8', 'title' => 'Binary Search Boundaries', 'topic' => 'Binary Search', 'difficulty' => 'Medium', 'description' => 'Find the first element greater than or equal to x.', 'tags' => 'binary-search,lower-bound', 'starter_code' => $starter],
                ['id' => 'p9', 'title' => 'Interval Merge', 'topic' => 'Greedy', 'difficulty' => 'Medium', 'description' => 'Merge overlapping intervals into disjoint ranges.', 'tags' => 'greedy,sorting,intervals', 'starter_code' => $starter],
                ['id' => 'p10', 'title' => 'Prefix Frequency', 'topic' => 'Arrays', 'difficulty' => 'Medium', 'description' => 'Preprocess an array for prefix frequency queries.', 'tags' => 'arrays,prefix-sum', 'starter_code' => $starter],
                ['id' => 'p11', 'title' => 'Palindrome Lab', 'topic' => 'Strings', 'difficulty' => 'Easy', 'description' => 'Determine whether a normalized string is a palindrome.', 'tags' => 'strings,two-pointers', 'starter_code' => $starter],
                ['id' => 'p12', 'title' => 'Tree Diameter', 'topic' => 'Trees', 'difficulty' => 'Medium', 'description' => 'Compute the diameter of an unweighted tree.', 'tags' => 'trees,bfs,dfs', 'starter_code' => $starter],
                ['id' => 'p13', 'title' => 'Modular Power', 'topic' => 'Math', 'difficulty' => 'Medium', 'description' => 'Compute a^b mod m using binary exponentiation.', 'tags' => 'math,fast-power', 'starter_code' => $starter],
                ['id' => 'p14', 'title' => 'DAG Routes', 'topic' => 'Graphs', 'difficulty' => 'Hard', 'description' => 'Count paths in a DAG using topological order and DP.', 'tags' => 'graphs,dag,dp', 'starter_code' => $starter],
            ]);

            DB::table('contests')->insert([
                ['id' => 'c1', 'name' => 'CodeForge Round #45', 'type' => 'Global', 'starts_at' => now()->addDays(10), 'status' => 'Upcoming', 'created_by' => 'u_admin', 'created_at' => now()],
                ['id' => 'c2', 'name' => 'Local University Clash', 'type' => 'Local', 'starts_at' => now()->subDays(20), 'status' => 'Past', 'created_by' => 'u_admin', 'created_at' => now()->subDays(20)],
            ]);
            DB::table('contest_problems')->insert([
                ['contest_id' => 'c1', 'problem_id' => 'p6', 'points' => 300], ['contest_id' => 'c1', 'problem_id' => 'p7', 'points' => 500],
                ['contest_id' => 'c2', 'problem_id' => 'p3', 'points' => 100], ['contest_id' => 'c2', 'problem_id' => 'p5', 'points' => 250],
            ]);
            DB::table('contest_participants')->insert([
                ['contest_id' => 'c2', 'user_id' => 'u1', 'score' => 350, 'joined_at' => now()->subDays(20)],
                ['contest_id' => 'c2', 'user_id' => 'u2', 'score' => 350, 'joined_at' => now()->subDays(20)],
            ]);

            $sessions = [
                ['id' => 'seed_ps1', 'user_id' => 'u1', 'problem_id' => 'p1', 'seconds' => 70],
                ['id' => 'seed_ps2', 'user_id' => 'u1', 'problem_id' => 'p3', 'seconds' => 120],
                ['id' => 'seed_ps3', 'user_id' => 'u2', 'problem_id' => 'p1', 'seconds' => 40],
                ['id' => 'seed_ps4', 'user_id' => 'u2', 'problem_id' => 'p5', 'seconds' => 140],
                ['id' => 'seed_ps5', 'user_id' => 'u2', 'problem_id' => 'p6', 'seconds' => 180],
                ['id' => 'seed_ps6', 'user_id' => 'u3', 'problem_id' => 'p2', 'seconds' => 100],
            ];
            foreach ($sessions as $i => $session) {
                $start = now()->subDays(12 - $i)->subSeconds($session['seconds']);
                DB::table('problem_sessions')->insert([
                    'id' => $session['id'], 'user_id' => $session['user_id'], 'problem_id' => $session['problem_id'],
                    'started_at' => $start, 'completed_at' => $start->copy()->addSeconds($session['seconds']),
                    'solve_time_seconds' => $session['seconds'], 'status' => 'solved', 'created_at' => $start,
                ]);
                DB::table('submissions')->insert([
                    'id' => 'seed_sub'.($i + 1), 'session_id' => $session['id'], 'problem_id' => $session['problem_id'], 'user_id' => $session['user_id'],
                    'contest_id' => null, 'verdict' => 'AC', 'submitted_at' => $start->copy()->addSeconds($session['seconds']),
                    'elapsed_seconds' => $session['seconds'], 'runtime_ms' => 12 + $i * 4, 'memory_kb' => 1200 + $i * 100,
                    'language' => 'C++', 'source_code' => '// demo historical solve', 'failed_test_case' => null,
                ]);
            }

            DB::table('arena_universities')->insert([
                ['name' => 'National Institute'], ['name' => 'State College of Engineering'], ['name' => 'Tech University of Science'],
            ]);
            DB::table('arena_users')->insert([
                ['user_id' => 'u1', 'username' => 'Ismail', 'university' => 'Tech University of Science', 'rating' => 1451],
                ['user_id' => 'u2', 'username' => 'Tamjid', 'university' => 'State College of Engineering', 'rating' => 1520],
                ['user_id' => 'u3', 'username' => 'David', 'university' => 'National Institute', 'rating' => 1300],
                ['user_id' => 'u5', 'username' => 'Nadia', 'university' => 'National Institute', 'rating' => 1610],
            ]);
            DB::table('arena_problems')->insert([
                ['problem_id' => 'p1', 'title' => 'Array Sum', 'topic' => 'Arrays', 'difficulty' => 'Easy'],
                ['problem_id' => 'p2', 'title' => 'String Hashing', 'topic' => 'Strings', 'difficulty' => 'Medium'],
                ['problem_id' => 'p3', 'title' => 'Shortest Path', 'topic' => 'Graphs', 'difficulty' => 'Medium'],
                ['problem_id' => 'p4', 'title' => 'Tree Query', 'topic' => 'Trees', 'difficulty' => 'Hard'],
            ]);
            DB::table('arena_submissions')->insert([
                ['submission_id' => 'a1', 'user_id' => 'u1', 'problem_id' => 'p1', 'verdict' => 'AC', 'runtime_ms' => 18],
                ['submission_id' => 'a2', 'user_id' => 'u1', 'problem_id' => 'p2', 'verdict' => 'AC', 'runtime_ms' => 41],
                ['submission_id' => 'a3', 'user_id' => 'u2', 'problem_id' => 'p1', 'verdict' => 'AC', 'runtime_ms' => 15],
                ['submission_id' => 'a4', 'user_id' => 'u2', 'problem_id' => 'p3', 'verdict' => 'AC', 'runtime_ms' => 49],
                ['submission_id' => 'a5', 'user_id' => 'u3', 'problem_id' => 'p1', 'verdict' => 'AC', 'runtime_ms' => 22],
                ['submission_id' => 'a6', 'user_id' => 'u5', 'problem_id' => 'p4', 'verdict' => 'AC', 'runtime_ms' => 80],
            ]);

            DB::table('sql_challenges')->insert([
                ['id' => 'sql1', 'title' => 'Rating Gate', 'description' => 'Return username and rating for arena users whose rating is greater than 1400, sorted highest first.', 'difficulty' => 'Easy', 'reference_query' => 'SELECT username, rating FROM arena_users WHERE rating > 1400 ORDER BY rating DESC', 'max_score' => 1000, 'order_sensitive' => 1],
                ['id' => 'sql2', 'title' => 'Accepted Count', 'description' => 'Return each username and accepted submission count.', 'difficulty' => 'Easy', 'reference_query' => "SELECT u.username, COUNT(*) AS accepted_count FROM arena_users u JOIN arena_submissions s ON s.user_id = u.user_id WHERE s.verdict = 'AC' GROUP BY u.user_id, u.username HAVING COUNT(*) > 0 ORDER BY accepted_count DESC, u.username ASC", 'max_score' => 1000, 'order_sensitive' => 1],
                ['id' => 'sql3', 'title' => 'University Rating Board', 'description' => 'Return university and average rating rounded to 2 decimals.', 'difficulty' => 'Medium', 'reference_query' => 'SELECT university, ROUND(AVG(rating), 2) AS avg_rating FROM arena_users GROUP BY university ORDER BY avg_rating DESC', 'max_score' => 1000, 'order_sensitive' => 1],
                ['id' => 'sql4', 'title' => 'Unsolved Problems', 'description' => 'Return arena problems that have never received an accepted submission.', 'difficulty' => 'Medium', 'reference_query' => "SELECT p.problem_id, p.title FROM arena_problems p LEFT JOIN arena_submissions s ON s.problem_id = p.problem_id AND s.verdict = 'AC' WHERE s.submission_id IS NULL ORDER BY p.problem_id", 'max_score' => 1000, 'order_sensitive' => 1],
            ]);
        });

        $this->command?->info('CodeForge demo data seeded. User: Ismail/123456, Admin: Admin/admin123');
    }
}
