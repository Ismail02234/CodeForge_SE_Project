<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DevelopmentSeeder extends Seeder
{
    private function now(): \Carbon\Carbon
    {
        return now();
    }

    private function daysAgo(int $days): \Carbon\Carbon
    {
        return now()->subDays($days);
    }

    private function hoursAgo(int $hours): \Carbon\Carbon
    {
        return now()->subHours($hours);
    }

    private function minutesAgo(int $minutes): \Carbon\Carbon
    {
        return now()->subMinutes($minutes);
    }

    public function run(): void
    {
        if (DB::table('users')->where('id', 'dev_admin')->exists()) {
            return;
        }

        DB::transaction(function () {
            $this->seedUniversities();
            $this->seedUsers();
            $this->seedProblems();
            $this->seedContests();
            $this->seedProblemSessions();
            $this->seedSubmissions();
            $this->seedGhostRaces();
            $this->seedSqlChallenges();
            $this->seedSqlBattles();
            $this->seedSqlAttempts();
            $this->seedArena();
            $this->seedLearning();
            $this->seedActivityLogs();
        });
    }

    private function seedUniversities(): void
    {
        $universities = [
            ['name' => 'United International University', 'city' => 'Dhaka'],
            ['name' => 'North South University', 'city' => 'Dhaka'],
            ['name' => 'BRAC University', 'city' => 'Dhaka'],
            ['name' => 'Independent University, Bangladesh', 'city' => 'Dhaka'],
            ['name' => 'American International University-Bangladesh', 'city' => 'Dhaka'],
            ['name' => 'East West University', 'city' => 'Dhaka'],
            ['name' => 'Daffodil International University', 'city' => 'Dhaka'],
            ['name' => 'University of Asia Pacific', 'city' => 'Dhaka'],
            ['name' => 'Ahsanullah University of Science and Technology', 'city' => 'Dhaka'],
            ['name' => 'University of Liberal Arts Bangladesh', 'city' => 'Dhaka'],
            ['name' => 'Bangladesh University of Engineering and Technology', 'city' => 'Dhaka'],
            ['name' => 'Rajshahi University of Engineering and Technology', 'city' => 'Rajshahi'],
            ['name' => 'Khulna University of Engineering and Technology', 'city' => 'Khulna'],
            ['name' => 'Chittagong University of Engineering and Technology', 'city' => 'Chattogram'],
            ['name' => 'Metropolitan Computing Academy', 'city' => 'Khulna'],
        ];

        foreach ($universities as $uni) {
            DB::table('universities')->updateOrInsert(
                ['name' => $uni['name']],
                ['city' => $uni['city'], 'created_at' => $this->now()]
            );
        }
    }

    private function seedUsers(): void
    {
        $password = Hash::make('password123');
        $adminPassword = Hash::make('admin123');

        $users = [
            ['id' => 'dev_admin', 'username' => 'DevAdmin', 'password' => $adminPassword, 'role' => 'admin', 'rating' => 2000, 'university' => 'United International University', 'rank' => 'Grandmaster'],
            ['id' => 'u1', 'username' => 'Ismail', 'password' => $password, 'role' => 'user', 'rating' => 1451, 'university' => 'North South University', 'rank' => 'Specialist'],
            ['id' => 'u2', 'username' => 'Tamjid', 'password' => $password, 'role' => 'user', 'rating' => 1520, 'university' => 'BRAC University', 'rank' => 'Expert'],
            ['id' => 'u3', 'username' => 'David', 'password' => $password, 'role' => 'user', 'rating' => 1300, 'university' => 'Independent University, Bangladesh', 'rank' => 'Pupil'],
            ['id' => 'u4', 'username' => 'Anon', 'password' => $password, 'role' => 'user', 'rating' => 980, 'university' => 'American International University-Bangladesh', 'rank' => 'Newbie'],
            ['id' => 'u5', 'username' => 'Nadia', 'password' => $password, 'role' => 'user', 'rating' => 1610, 'university' => 'East West University', 'rank' => 'Expert'],
            ['id' => 'u6', 'username' => 'Rafi', 'password' => $password, 'role' => 'user', 'rating' => 1750, 'university' => 'Daffodil International University', 'rank' => 'Master'],
            ['id' => 'u7', 'username' => 'Sadia', 'password' => $password, 'role' => 'user', 'rating' => 1100, 'university' => 'University of Asia Pacific', 'rank' => 'Newbie'],
            ['id' => 'u8', 'username' => 'Karim', 'password' => $password, 'role' => 'user', 'rating' => 1380, 'university' => 'Ahsanullah University of Science and Technology', 'rank' => 'Specialist'],
            ['id' => 'u9', 'username' => 'Lima', 'password' => $password, 'role' => 'user', 'rating' => 1890, 'university' => 'University of Liberal Arts Bangladesh', 'rank' => 'Master'],
            ['id' => 'u10', 'username' => 'Fahim', 'password' => $password, 'role' => 'user', 'rating' => 1050, 'university' => 'Bangladesh University of Engineering and Technology', 'rank' => 'Newbie'],
            ['id' => 'u11', 'username' => 'Tania', 'password' => $password, 'role' => 'user', 'rating' => 1420, 'university' => 'Rajshahi University of Engineering and Technology', 'rank' => 'Specialist'],
            ['id' => 'u12', 'username' => 'Arif', 'password' => $password, 'role' => 'user', 'rating' => 1200, 'university' => 'Khulna University of Engineering and Technology', 'rank' => 'Pupil'],
            ['id' => 'u13', 'username' => 'Nusrat', 'password' => $password, 'role' => 'user', 'rating' => 990, 'university' => 'Chittagong University of Engineering and Technology', 'rank' => 'Newbie'],
            ['id' => 'u14', 'username' => 'Mehedi', 'password' => $password, 'role' => 'user', 'rating' => 1560, 'university' => 'Metropolitan Computing Academy', 'rank' => 'Expert'],
        ];

        foreach ($users as $user) {
            DB::table('users')->updateOrInsert(
                ['id' => $user['id']],
                array_merge($user, ['created_at' => $this->now()])
            );
        }
    }

    private function seedProblems(): void
    {
        $starter = "#include <bits/stdc++.h>\nusing namespace std;\n\nint main() {\n    // your code\n    return 0;\n}";

        $problems = [
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
            ['id' => 'p15', 'title' => 'Two Pointers Pair', 'topic' => 'Two Pointers', 'difficulty' => 'Easy', 'description' => 'Find if any pair sums to target in a sorted array.', 'tags' => 'two-pointers,sorted', 'starter_code' => $starter],
            ['id' => 'p16', 'title' => 'Stack Balance', 'topic' => 'Stack', 'difficulty' => 'Easy', 'description' => 'Check if a bracket sequence is balanced using a stack.', 'tags' => 'stack,parsing', 'starter_code' => $starter],
            ['id' => 'p17', 'title' => 'Matrix Spiral', 'topic' => 'Simulation', 'difficulty' => 'Medium', 'description' => 'Traverse an n x n matrix in clockwise spiral order.', 'tags' => 'simulation,matrix', 'starter_code' => $starter],
            ['id' => 'p18', 'title' => 'Word Ladder', 'topic' => 'Graphs', 'difficulty' => 'Hard', 'description' => 'Find the shortest transformation sequence from start to end word.', 'tags' => 'graphs,bfs,strings', 'starter_code' => $starter],
            ['id' => 'p19', 'title' => 'Anagram Groups', 'topic' => 'Strings', 'difficulty' => 'Medium', 'description' => 'Group strings that are anagrams of each other.', 'tags' => 'strings,hashmap', 'starter_code' => $starter],
            ['id' => 'p20', 'title' => 'Longest Increasing Subsequence', 'topic' => 'DP', 'difficulty' => 'Hard', 'description' => 'Find the length of the longest strictly increasing subsequence.', 'tags' => 'dp,lis', 'starter_code' => $starter],
            ['id' => 'p21', 'title' => 'GCD Explorer', 'topic' => 'Math', 'difficulty' => 'Easy', 'description' => 'Compute the GCD of two numbers using Euclid algorithm.', 'tags' => 'math,gcd', 'starter_code' => $starter],
            ['id' => 'p22', 'title' => 'Top K Frequent', 'topic' => 'Heap', 'difficulty' => 'Medium', 'description' => 'Return the k most frequent elements in an array.', 'tags' => 'heap,hashmap', 'starter_code' => $starter],
            ['id' => 'p23', 'title' => 'Subset Sum', 'topic' => 'DP', 'difficulty' => 'Medium', 'description' => 'Check if any subset of numbers sums to the target.', 'tags' => 'dp,subset', 'starter_code' => $starter],
            ['id' => 'p24', 'title' => 'LRU Cache', 'topic' => 'Design', 'difficulty' => 'Hard', 'description' => 'Implement an LRU cache with O(1) get and put.', 'tags' => 'design,hashmap,linked-list', 'starter_code' => $starter],
            ['id' => 'p25', 'title' => 'Merge K Lists', 'topic' => 'Heap', 'difficulty' => 'Hard', 'description' => 'Merge k sorted linked lists into one sorted list.', 'tags' => 'heap,linked-list', 'starter_code' => $starter],
        ];

        foreach ($problems as $problem) {
            DB::table('problems')->updateOrInsert(
                ['id' => $problem['id']],
                array_merge($problem, ['created_at' => $this->now()])
            );
        }
    }

    private function seedContests(): void
    {
        $contests = [
            ['id' => 'c1', 'name' => 'CodeForge Round #45', 'type' => 'Global', 'starts_at' => $this->daysAgo(25), 'status' => 'Past', 'created_by' => 'dev_admin'],
            ['id' => 'c2', 'name' => 'Local University Clash', 'type' => 'Local', 'starts_at' => $this->daysAgo(20), 'status' => 'Past', 'created_by' => 'dev_admin'],
            ['id' => 'c3', 'name' => 'Spring Coding Championship', 'type' => 'Global', 'starts_at' => $this->daysAgo(3), 'status' => 'Active', 'created_by' => 'dev_admin'],
            ['id' => 'c4', 'name' => 'Intra-Department Duel', 'type' => 'Duel', 'starts_at' => $this->hoursAgo(2), 'status' => 'Active', 'created_by' => 'u1'],
            ['id' => 'c5', 'name' => 'Freshman Warm-Up', 'type' => 'Local', 'starts_at' => $this->daysAgo(5), 'status' => 'Past', 'created_by' => 'dev_admin'],
            ['id' => 'c6', 'name' => 'Grand Prix Finals', 'type' => 'Global', 'starts_at' => $this->daysAgo(40), 'status' => 'Past', 'created_by' => 'dev_admin'],
            ['id' => 'c7', 'name' => 'Upcoming Showdown', 'type' => 'Global', 'starts_at' => $this->daysAgo(-7), 'status' => 'Upcoming', 'created_by' => 'dev_admin'],
            ['id' => 'c8', 'name' => 'Weekend Duel Series', 'type' => 'Duel', 'starts_at' => $this->daysAgo(-2), 'status' => 'Upcoming', 'created_by' => 'u6'],
        ];

        foreach ($contests as $contest) {
            DB::table('contests')->updateOrInsert(
                ['id' => $contest['id']],
                array_merge($contest, ['created_at' => $this->now()])
            );
        }

        $contestProblems = [
            ['contest_id' => 'c1', 'problem_id' => 'p6', 'points' => 300],
            ['contest_id' => 'c1', 'problem_id' => 'p7', 'points' => 500],
            ['contest_id' => 'c1', 'problem_id' => 'p14', 'points' => 500],
            ['contest_id' => 'c2', 'problem_id' => 'p3', 'points' => 100],
            ['contest_id' => 'c2', 'problem_id' => 'p5', 'points' => 250],
            ['contest_id' => 'c2', 'problem_id' => 'p9', 'points' => 200],
            ['contest_id' => 'c3', 'problem_id' => 'p8', 'points' => 200],
            ['contest_id' => 'c3', 'problem_id' => 'p12', 'points' => 300],
            ['contest_id' => 'c3', 'problem_id' => 'p20', 'points' => 500],
            ['contest_id' => 'c3', 'problem_id' => 'p24', 'points' => 500],
            ['contest_id' => 'c4', 'problem_id' => 'p2', 'points' => 100],
            ['contest_id' => 'c4', 'problem_id' => 'p15', 'points' => 200],
            ['contest_id' => 'c5', 'problem_id' => 'p1', 'points' => 100],
            ['contest_id' => 'c5', 'problem_id' => 'p11', 'points' => 150],
            ['contest_id' => 'c5', 'problem_id' => 'p16', 'points' => 150],
            ['contest_id' => 'c6', 'problem_id' => 'p7', 'points' => 500],
            ['contest_id' => 'c6', 'problem_id' => 'p18', 'points' => 500],
            ['contest_id' => 'c6', 'problem_id' => 'p25', 'points' => 500],
        ];

        foreach ($contestProblems as $cp) {
            DB::table('contest_problems')->updateOrInsert(
                ['contest_id' => $cp['contest_id'], 'problem_id' => $cp['problem_id']],
                ['points' => $cp['points']]
            );
        }

        $participants = [
            ['contest_id' => 'c1', 'user_id' => 'u1', 'score' => 600, 'joined_at' => $this->daysAgo(25)],
            ['contest_id' => 'c1', 'user_id' => 'u2', 'score' => 800, 'joined_at' => $this->daysAgo(25)],
            ['contest_id' => 'c1', 'user_id' => 'u5', 'score' => 700, 'joined_at' => $this->daysAgo(25)],
            ['contest_id' => 'c1', 'user_id' => 'u6', 'score' => 1300, 'joined_at' => $this->daysAgo(25)],
            ['contest_id' => 'c2', 'user_id' => 'u1', 'score' => 350, 'joined_at' => $this->daysAgo(20)],
            ['contest_id' => 'c2', 'user_id' => 'u2', 'score' => 350, 'joined_at' => $this->daysAgo(20)],
            ['contest_id' => 'c2', 'user_id' => 'u3', 'score' => 200, 'joined_at' => $this->daysAgo(20)],
            ['contest_id' => 'c3', 'user_id' => 'u2', 'score' => 500, 'joined_at' => $this->daysAgo(3)],
            ['contest_id' => 'c3', 'user_id' => 'u5', 'score' => 700, 'joined_at' => $this->daysAgo(3)],
            ['contest_id' => 'c3', 'user_id' => 'u6', 'score' => 900, 'joined_at' => $this->daysAgo(3)],
            ['contest_id' => 'c3', 'user_id' => 'u9', 'score' => 1000, 'joined_at' => $this->daysAgo(3)],
            ['contest_id' => 'c3', 'user_id' => 'u14', 'score' => 450, 'joined_at' => $this->daysAgo(3)],
            ['contest_id' => 'c4', 'user_id' => 'u1', 'score' => 100, 'joined_at' => $this->hoursAgo(2)],
            ['contest_id' => 'c4', 'user_id' => 'u8', 'score' => 200, 'joined_at' => $this->hoursAgo(2)],
            ['contest_id' => 'c5', 'user_id' => 'u3', 'score' => 250, 'joined_at' => $this->daysAgo(5)],
            ['contest_id' => 'c5', 'user_id' => 'u7', 'score' => 100, 'joined_at' => $this->daysAgo(5)],
            ['contest_id' => 'c5', 'user_id' => 'u10', 'score' => 150, 'joined_at' => $this->daysAgo(5)],
            ['contest_id' => 'c6', 'user_id' => 'u5', 'score' => 1000, 'joined_at' => $this->daysAgo(40)],
            ['contest_id' => 'c6', 'user_id' => 'u9', 'score' => 1500, 'joined_at' => $this->daysAgo(40)],
            ['contest_id' => 'c6', 'user_id' => 'u14', 'score' => 500, 'joined_at' => $this->daysAgo(40)],
        ];

        foreach ($participants as $p) {
            DB::table('contest_participants')->updateOrInsert(
                ['contest_id' => $p['contest_id'], 'user_id' => $p['user_id']],
                ['score' => $p['score'], 'joined_at' => $p['joined_at']]
            );
        }
    }

    private function seedProblemSessions(): void
    {
        $sessions = [
            ['id' => 'dev_ps1', 'user_id' => 'u1', 'problem_id' => 'p1', 'started_at' => $this->daysAgo(12)->subSeconds(70), 'completed_at' => $this->daysAgo(12), 'solve_time_seconds' => 70, 'status' => 'solved'],
            ['id' => 'dev_ps2', 'user_id' => 'u1', 'problem_id' => 'p3', 'started_at' => $this->daysAgo(11)->subSeconds(120), 'completed_at' => $this->daysAgo(11), 'solve_time_seconds' => 120, 'status' => 'solved'],
            ['id' => 'dev_ps3', 'user_id' => 'u2', 'problem_id' => 'p1', 'started_at' => $this->daysAgo(10)->subSeconds(40), 'completed_at' => $this->daysAgo(10), 'solve_time_seconds' => 40, 'status' => 'solved'],
            ['id' => 'dev_ps4', 'user_id' => 'u2', 'problem_id' => 'p5', 'started_at' => $this->daysAgo(9)->subSeconds(140), 'completed_at' => $this->daysAgo(9), 'solve_time_seconds' => 140, 'status' => 'solved'],
            ['id' => 'dev_ps5', 'user_id' => 'u2', 'problem_id' => 'p6', 'started_at' => $this->daysAgo(8)->subSeconds(180), 'completed_at' => $this->daysAgo(8), 'solve_time_seconds' => 180, 'status' => 'solved'],
            ['id' => 'dev_ps6', 'user_id' => 'u3', 'problem_id' => 'p2', 'started_at' => $this->daysAgo(7)->subSeconds(100), 'completed_at' => $this->daysAgo(7), 'solve_time_seconds' => 100, 'status' => 'solved'],
            ['id' => 'dev_ps7', 'user_id' => 'u4', 'problem_id' => 'p1', 'started_at' => $this->daysAgo(6), 'completed_at' => null, 'solve_time_seconds' => null, 'status' => 'abandoned'],
            ['id' => 'dev_ps8', 'user_id' => 'u5', 'problem_id' => 'p8', 'started_at' => $this->daysAgo(5)->subSeconds(200), 'completed_at' => $this->daysAgo(5), 'solve_time_seconds' => 200, 'status' => 'solved'],
            ['id' => 'dev_ps9', 'user_id' => 'u6', 'problem_id' => 'p10', 'started_at' => $this->daysAgo(4)->subSeconds(150), 'completed_at' => $this->daysAgo(4), 'solve_time_seconds' => 150, 'status' => 'solved'],
            ['id' => 'dev_ps10', 'user_id' => 'u7', 'problem_id' => 'p11', 'started_at' => $this->daysAgo(3)->subSeconds(60), 'completed_at' => $this->daysAgo(3), 'solve_time_seconds' => 60, 'status' => 'solved'],
            ['id' => 'dev_ps11', 'user_id' => 'u8', 'problem_id' => 'p12', 'started_at' => $this->daysAgo(2)->subSeconds(300), 'completed_at' => $this->daysAgo(2), 'solve_time_seconds' => 300, 'status' => 'solved'],
            ['id' => 'dev_ps12', 'user_id' => 'u9', 'problem_id' => 'p14', 'started_at' => $this->daysAgo(1)->subSeconds(500), 'completed_at' => $this->daysAgo(1), 'solve_time_seconds' => 500, 'status' => 'solved'],
            ['id' => 'dev_ps13', 'user_id' => 'u1', 'problem_id' => 'p15', 'started_at' => $this->hoursAgo(10), 'completed_at' => null, 'solve_time_seconds' => null, 'status' => 'active'],
            ['id' => 'dev_ps14', 'user_id' => 'u6', 'problem_id' => 'p18', 'started_at' => $this->hoursAgo(5), 'completed_at' => null, 'solve_time_seconds' => null, 'status' => 'active'],
            ['id' => 'dev_ps15', 'user_id' => 'u9', 'problem_id' => 'p20', 'started_at' => $this->hoursAgo(3)->subSeconds(400), 'completed_at' => $this->hoursAgo(3), 'solve_time_seconds' => 400, 'status' => 'solved'],
            ['id' => 'dev_ps16', 'user_id' => 'u14', 'problem_id' => 'p21', 'started_at' => $this->daysAgo(15)->subSeconds(50), 'completed_at' => $this->daysAgo(15), 'solve_time_seconds' => 50, 'status' => 'solved'],
        ];

        foreach ($sessions as $session) {
            DB::table('problem_sessions')->updateOrInsert(
                ['id' => $session['id']],
                array_merge($session, ['created_at' => $session['started_at'] ?? $this->now()])
            );
        }
    }

    private function seedSubmissions(): void
    {
        $submissions = [
            ['id' => 'dev_sub1', 'session_id' => 'dev_ps1', 'problem_id' => 'p1', 'user_id' => 'u1', 'contest_id' => null, 'verdict' => 'AC', 'submitted_at' => $this->daysAgo(12), 'elapsed_seconds' => 70, 'runtime_ms' => 18, 'memory_kb' => 1200, 'language' => 'C++', 'source_code' => '// hello world', 'failed_test_case' => null],
            ['id' => 'dev_sub2', 'session_id' => 'dev_ps2', 'problem_id' => 'p3', 'user_id' => 'u1', 'contest_id' => null, 'verdict' => 'AC', 'submitted_at' => $this->daysAgo(11), 'elapsed_seconds' => 120, 'runtime_ms' => 25, 'memory_kb' => 1400, 'language' => 'C++', 'source_code' => '// array sum', 'failed_test_case' => null],
            ['id' => 'dev_sub3', 'session_id' => 'dev_ps3', 'problem_id' => 'p1', 'user_id' => 'u2', 'contest_id' => null, 'verdict' => 'AC', 'submitted_at' => $this->daysAgo(10), 'elapsed_seconds' => 40, 'runtime_ms' => 15, 'memory_kb' => 1100, 'language' => 'C++', 'source_code' => '// hello world', 'failed_test_case' => null],
            ['id' => 'dev_sub4', 'session_id' => 'dev_ps4', 'problem_id' => 'p5', 'user_id' => 'u2', 'contest_id' => null, 'verdict' => 'AC', 'submitted_at' => $this->daysAgo(9), 'elapsed_seconds' => 140, 'runtime_ms' => 41, 'memory_kb' => 1800, 'language' => 'Python', 'source_code' => '# string hash', 'failed_test_case' => null],
            ['id' => 'dev_sub5', 'session_id' => 'dev_ps5', 'problem_id' => 'p6', 'user_id' => 'u2', 'contest_id' => null, 'verdict' => 'AC', 'submitted_at' => $this->daysAgo(8), 'elapsed_seconds' => 180, 'runtime_ms' => 49, 'memory_kb' => 2000, 'language' => 'C++', 'source_code' => '// dijkstra', 'failed_test_case' => null],
            ['id' => 'dev_sub6', 'session_id' => 'dev_ps6', 'problem_id' => 'p2', 'user_id' => 'u3', 'contest_id' => null, 'verdict' => 'AC', 'submitted_at' => $this->daysAgo(7), 'elapsed_seconds' => 100, 'runtime_ms' => 12, 'memory_kb' => 900, 'language' => 'C++', 'source_code' => '// even odd', 'failed_test_case' => null],
            ['id' => 'dev_sub7', 'session_id' => null, 'problem_id' => 'p4', 'user_id' => 'u1', 'contest_id' => 'c1', 'verdict' => 'WA', 'submitted_at' => $this->daysAgo(25), 'elapsed_seconds' => 200, 'runtime_ms' => null, 'memory_kb' => null, 'language' => 'C++', 'source_code' => '// wrong answer attempt', 'failed_test_case' => 3],
            ['id' => 'dev_sub8', 'session_id' => null, 'problem_id' => 'p6', 'user_id' => 'u1', 'contest_id' => 'c1', 'verdict' => 'AC', 'submitted_at' => $this->daysAgo(24), 'elapsed_seconds' => 300, 'runtime_ms' => 55, 'memory_kb' => 2200, 'language' => 'C++', 'source_code' => '// contest solve', 'failed_test_case' => null],
            ['id' => 'dev_sub9', 'session_id' => null, 'problem_id' => 'p7', 'user_id' => 'u6', 'contest_id' => 'c1', 'verdict' => 'AC', 'submitted_at' => $this->daysAgo(23), 'elapsed_seconds' => 450, 'runtime_ms' => 80, 'memory_kb' => 3000, 'language' => 'C++', 'source_code' => '// knapsack', 'failed_test_case' => null],
            ['id' => 'dev_sub10', 'session_id' => null, 'problem_id' => 'p7', 'user_id' => 'u5', 'contest_id' => 'c1', 'verdict' => 'TLE', 'submitted_at' => $this->daysAgo(23), 'elapsed_seconds' => 1000, 'runtime_ms' => 2000, 'memory_kb' => 5000, 'language' => 'Python', 'source_code' => '// tle attempt', 'failed_test_case' => 7],
            ['id' => 'dev_sub11', 'session_id' => null, 'problem_id' => 'p14', 'user_id' => 'u9', 'contest_id' => 'c3', 'verdict' => 'AC', 'submitted_at' => $this->daysAgo(2), 'elapsed_seconds' => 600, 'runtime_ms' => 100, 'memory_kb' => 3500, 'language' => 'C++', 'source_code' => '// dag routes', 'failed_test_case' => null],
            ['id' => 'dev_sub12', 'session_id' => null, 'problem_id' => 'p8', 'user_id' => 'u2', 'contest_id' => 'c3', 'verdict' => 'AC', 'submitted_at' => $this->daysAgo(2), 'elapsed_seconds' => 180, 'runtime_ms' => 30, 'memory_kb' => 1500, 'language' => 'C++', 'source_code' => '// binary search', 'failed_test_case' => null],
            ['id' => 'dev_sub13', 'session_id' => null, 'problem_id' => 'p20', 'user_id' => 'u6', 'contest_id' => 'c3', 'verdict' => 'WA', 'submitted_at' => $this->daysAgo(1), 'elapsed_seconds' => 300, 'runtime_ms' => null, 'memory_kb' => null, 'language' => 'Python', 'source_code' => '// lis wa', 'failed_test_case' => 5],
            ['id' => 'dev_sub14', 'session_id' => null, 'problem_id' => 'p24', 'user_id' => 'u6', 'contest_id' => 'c3', 'verdict' => 'RE', 'submitted_at' => $this->daysAgo(1), 'elapsed_seconds' => 150, 'runtime_ms' => null, 'memory_kb' => null, 'language' => 'C++', 'source_code' => '// lru re', 'failed_test_case' => 1],
            ['id' => 'dev_sub15', 'session_id' => 'dev_ps8', 'problem_id' => 'p8', 'user_id' => 'u5', 'contest_id' => null, 'verdict' => 'AC', 'submitted_at' => $this->daysAgo(5), 'elapsed_seconds' => 200, 'runtime_ms' => 35, 'memory_kb' => 1600, 'language' => 'C++', 'source_code' => '// bs', 'failed_test_case' => null],
            ['id' => 'dev_sub16', 'session_id' => 'dev_ps9', 'problem_id' => 'p10', 'user_id' => 'u6', 'contest_id' => null, 'verdict' => 'AC', 'submitted_at' => $this->daysAgo(4), 'elapsed_seconds' => 150, 'runtime_ms' => 22, 'memory_kb' => 1300, 'language' => 'C++', 'source_code' => '// prefix freq', 'failed_test_case' => null],
            ['id' => 'dev_sub17', 'session_id' => 'dev_ps10', 'problem_id' => 'p11', 'user_id' => 'u7', 'contest_id' => null, 'verdict' => 'AC', 'submitted_at' => $this->daysAgo(3), 'elapsed_seconds' => 60, 'runtime_ms' => 10, 'memory_kb' => 800, 'language' => 'Python', 'source_code' => '# palindrome', 'failed_test_case' => null],
            ['id' => 'dev_sub18', 'session_id' => 'dev_ps11', 'problem_id' => 'p12', 'user_id' => 'u8', 'contest_id' => null, 'verdict' => 'AC', 'submitted_at' => $this->daysAgo(2), 'elapsed_seconds' => 300, 'runtime_ms' => 60, 'memory_kb' => 2000, 'language' => 'C++', 'source_code' => '// diameter', 'failed_test_case' => null],
            ['id' => 'dev_sub19', 'session_id' => 'dev_ps12', 'problem_id' => 'p14', 'user_id' => 'u9', 'contest_id' => null, 'verdict' => 'AC', 'submitted_at' => $this->daysAgo(1), 'elapsed_seconds' => 500, 'runtime_ms' => 90, 'memory_kb' => 2800, 'language' => 'C++', 'source_code' => '// dag', 'failed_test_case' => null],
            ['id' => 'dev_sub20', 'session_id' => null, 'problem_id' => 'p2', 'user_id' => 'u10', 'contest_id' => 'c5', 'verdict' => 'CE', 'submitted_at' => $this->daysAgo(5), 'elapsed_seconds' => null, 'runtime_ms' => null, 'memory_kb' => null, 'language' => 'C++', 'source_code' => 'int main() {', 'failed_test_case' => null],
            ['id' => 'dev_sub21', 'session_id' => null, 'problem_id' => 'p11', 'user_id' => 'u7', 'contest_id' => 'c5', 'verdict' => 'AC', 'submitted_at' => $this->daysAgo(4), 'elapsed_seconds' => 80, 'runtime_ms' => 14, 'memory_kb' => 1000, 'language' => 'Python', 'source_code' => '# palindrome', 'failed_test_case' => null],
            ['id' => 'dev_sub22', 'session_id' => null, 'problem_id' => 'p16', 'user_id' => 'u10', 'contest_id' => 'c5', 'verdict' => 'WA', 'submitted_at' => $this->daysAgo(4), 'elapsed_seconds' => 120, 'runtime_ms' => null, 'memory_kb' => null, 'language' => 'C++', 'source_code' => '// stack balance wa', 'failed_test_case' => 2],
            ['id' => 'dev_sub23', 'session_id' => 'dev_ps15', 'problem_id' => 'p20', 'user_id' => 'u9', 'contest_id' => null, 'verdict' => 'AC', 'submitted_at' => $this->hoursAgo(3), 'elapsed_seconds' => 400, 'runtime_ms' => 70, 'memory_kb' => 2500, 'language' => 'C++', 'source_code' => '// lis', 'failed_test_case' => null],
            ['id' => 'dev_sub24', 'session_id' => 'dev_ps16', 'problem_id' => 'p21', 'user_id' => 'u14', 'contest_id' => null, 'verdict' => 'AC', 'submitted_at' => $this->daysAgo(15), 'elapsed_seconds' => 50, 'runtime_ms' => 8, 'memory_kb' => 700, 'language' => 'C++', 'source_code' => '// gcd', 'failed_test_case' => null],
            ['id' => 'dev_sub25', 'session_id' => null, 'problem_id' => 'p18', 'user_id' => 'u11', 'contest_id' => 'c6', 'verdict' => 'MLE', 'submitted_at' => $this->daysAgo(39), 'elapsed_seconds' => 500, 'runtime_ms' => null, 'memory_kb' => 52000, 'language' => 'Python', 'source_code' => '# mle attempt', 'failed_test_case' => 4],
            ['id' => 'dev_sub26', 'session_id' => null, 'problem_id' => 'p9', 'user_id' => 'u12', 'contest_id' => 'c2', 'verdict' => 'AC', 'submitted_at' => $this->daysAgo(19), 'elapsed_seconds' => 90, 'runtime_ms' => 20, 'memory_kb' => 1100, 'language' => 'C++', 'source_code' => '// interval merge', 'failed_test_case' => null],
            ['id' => 'dev_sub27', 'session_id' => null, 'problem_id' => 'p13', 'user_id' => 'u11', 'contest_id' => 'c2', 'verdict' => 'AC', 'submitted_at' => $this->daysAgo(18), 'elapsed_seconds' => 110, 'runtime_ms' => 18, 'memory_kb' => 1000, 'language' => 'C++', 'source_code' => '// fast power', 'failed_test_case' => null],
            ['id' => 'dev_sub28', 'session_id' => 'dev_ps13', 'problem_id' => 'p15', 'user_id' => 'u1', 'contest_id' => null, 'verdict' => 'WA', 'submitted_at' => $this->hoursAgo(10), 'elapsed_seconds' => 60, 'runtime_ms' => null, 'memory_kb' => null, 'language' => 'C++', 'source_code' => '// two pointers wa', 'failed_test_case' => 1],
            ['id' => 'dev_sub29', 'session_id' => null, 'problem_id' => 'p22', 'user_id' => 'u8', 'contest_id' => null, 'verdict' => 'AC', 'submitted_at' => $this->daysAgo(6), 'elapsed_seconds' => 250, 'runtime_ms' => 45, 'memory_kb' => 1700, 'language' => 'C++', 'source_code' => '// top k', 'failed_test_case' => null],
            ['id' => 'dev_sub30', 'session_id' => null, 'problem_id' => 'p19', 'user_id' => 'u3', 'contest_id' => null, 'verdict' => 'AC', 'submitted_at' => $this->daysAgo(8), 'elapsed_seconds' => 160, 'runtime_ms' => 28, 'memory_kb' => 1400, 'language' => 'Python', 'source_code' => '# anagram', 'failed_test_case' => null],
            ['id' => 'dev_sub31', 'session_id' => null, 'problem_id' => 'p17', 'user_id' => 'u13', 'contest_id' => null, 'verdict' => 'RE', 'submitted_at' => $this->daysAgo(10), 'elapsed_seconds' => 80, 'runtime_ms' => null, 'memory_kb' => null, 'language' => 'C++', 'source_code' => '// spiral re', 'failed_test_case' => 2],
            ['id' => 'dev_sub32', 'session_id' => null, 'problem_id' => 'p23', 'user_id' => 'u11', 'contest_id' => null, 'verdict' => 'AC', 'submitted_at' => $this->daysAgo(7), 'elapsed_seconds' => 200, 'runtime_ms' => 38, 'memory_kb' => 1600, 'language' => 'C++', 'source_code' => '// subset sum', 'failed_test_case' => null],
            ['id' => 'dev_sub33', 'session_id' => null, 'problem_id' => 'p1', 'user_id' => 'u13', 'contest_id' => null, 'verdict' => 'WA', 'submitted_at' => $this->daysAgo(9), 'elapsed_seconds' => 40, 'runtime_ms' => null, 'memory_kb' => null, 'language' => 'C++', 'source_code' => '// hello world wrong', 'failed_test_case' => 1],
            ['id' => 'dev_sub34', 'session_id' => null, 'problem_id' => 'p21', 'user_id' => 'u12', 'contest_id' => null, 'verdict' => 'AC', 'submitted_at' => $this->daysAgo(11), 'elapsed_seconds' => 45, 'runtime_ms' => 9, 'memory_kb' => 750, 'language' => 'C++', 'source_code' => '// gcd', 'failed_test_case' => null],
            ['id' => 'dev_sub35', 'session_id' => null, 'problem_id' => 'p25', 'user_id' => 'u14', 'contest_id' => null, 'verdict' => 'CE', 'submitted_at' => $this->daysAgo(2), 'elapsed_seconds' => null, 'runtime_ms' => null, 'memory_kb' => null, 'language' => 'C++', 'source_code' => 'mergeKLists(', 'failed_test_case' => null],
        ];

        foreach ($submissions as $sub) {
            DB::table('submissions')->updateOrInsert(
                ['id' => $sub['id']],
                $sub
            );
        }
    }

    private function seedGhostRaces(): void
    {
        $races = [
            ['id' => 'dev_gr1', 'challenger_id' => 'u1', 'ghost_user_id' => 'u2', 'problem_id' => 'p3', 'ghost_session_id' => 'dev_ps3', 'challenger_session_id' => 'dev_ps2', 'playback_speed' => 4, 'started_at' => $this->daysAgo(10), 'finished_at' => $this->daysAgo(10)->addMinutes(3), 'result' => 'lost', 'challenger_time' => 120, 'ghost_time' => 40],
            ['id' => 'dev_gr2', 'challenger_id' => 'u5', 'ghost_user_id' => 'u1', 'problem_id' => 'p5', 'ghost_session_id' => 'dev_ps4', 'challenger_session_id' => 'dev_ps8', 'playback_speed' => 4, 'started_at' => $this->daysAgo(5), 'finished_at' => $this->daysAgo(5)->addMinutes(4), 'result' => 'won', 'challenger_time' => 200, 'ghost_time' => 140],
            ['id' => 'dev_gr3', 'challenger_id' => 'u6', 'ghost_user_id' => 'u9', 'problem_id' => 'p10', 'ghost_session_id' => 'dev_ps9', 'challenger_session_id' => 'dev_ps9', 'playback_speed' => 2, 'started_at' => $this->daysAgo(4), 'finished_at' => null, 'result' => 'active', 'challenger_time' => null, 'ghost_time' => 150],
            ['id' => 'dev_gr4', 'challenger_id' => 'u2', 'ghost_user_id' => 'u5', 'problem_id' => 'p6', 'ghost_session_id' => 'dev_ps5', 'challenger_session_id' => 'dev_ps5', 'playback_speed' => 4, 'started_at' => $this->daysAgo(7), 'finished_at' => $this->daysAgo(7)->addMinutes(5), 'result' => 'draw', 'challenger_time' => 180, 'ghost_time' => 180],
            ['id' => 'dev_gr5', 'challenger_id' => 'u8', 'ghost_user_id' => 'u3', 'problem_id' => 'p2', 'ghost_session_id' => 'dev_ps6', 'challenger_session_id' => 'dev_ps11', 'playback_speed' => 4, 'started_at' => $this->daysAgo(2), 'finished_at' => $this->daysAgo(2)->addMinutes(2), 'result' => 'forfeit', 'challenger_time' => null, 'ghost_time' => 100],
        ];

        foreach ($races as $race) {
            DB::table('ghost_races')->updateOrInsert(
                ['id' => $race['id']],
                $race
            );
        }
    }

    private function seedSqlChallenges(): void
    {
        $challenges = [
            ['id' => 'sql1', 'title' => 'Rating Gate', 'description' => 'Return username and rating for arena users whose rating is greater than 1400, sorted highest first.', 'difficulty' => 'Easy', 'reference_query' => 'SELECT username, rating FROM arena_users WHERE rating > 1400 ORDER BY rating DESC', 'max_score' => 1000, 'order_sensitive' => 1],
            ['id' => 'sql2', 'title' => 'Accepted Count', 'description' => 'Return each username and accepted submission count.', 'difficulty' => 'Easy', 'reference_query' => "SELECT u.username, COUNT(*) AS accepted_count FROM arena_users u JOIN arena_submissions s ON s.user_id = u.user_id WHERE s.verdict = 'AC' GROUP BY u.user_id, u.username HAVING COUNT(*) > 0 ORDER BY accepted_count DESC, u.username ASC", 'max_score' => 1000, 'order_sensitive' => 1],
            ['id' => 'sql3', 'title' => 'University Rating Board', 'description' => 'Return university and average rating rounded to 2 decimals.', 'difficulty' => 'Medium', 'reference_query' => 'SELECT university, ROUND(AVG(rating), 2) AS avg_rating FROM arena_users GROUP BY university ORDER BY avg_rating DESC', 'max_score' => 1000, 'order_sensitive' => 1],
            ['id' => 'sql4', 'title' => 'Unsolved Problems', 'description' => 'Return arena problems that have never received an accepted submission.', 'difficulty' => 'Medium', 'reference_query' => "SELECT p.problem_id, p.title FROM arena_problems p LEFT JOIN arena_submissions s ON s.problem_id = p.problem_id AND s.verdict = 'AC' WHERE s.submission_id IS NULL ORDER BY p.problem_id", 'max_score' => 1000, 'order_sensitive' => 1],
            ['id' => 'sql5', 'title' => 'Contest Top Scorers', 'description' => 'List contest names with the top score and the user who achieved it.', 'difficulty' => 'Hard', 'reference_query' => 'SELECT c.name, cp.score, u.username FROM contests c JOIN contest_participants cp ON cp.contest_id = c.id JOIN users u ON u.id = cp.user_id WHERE (cp.contest_id, cp.score) IN (SELECT contest_id, MAX(score) FROM contest_participants GROUP BY contest_id) ORDER BY c.name', 'max_score' => 1000, 'order_sensitive' => 0],
            ['id' => 'sql6', 'title' => 'Hard Problem Leaders', 'description' => 'Find users who have solved the most Hard difficulty problems.', 'difficulty' => 'Medium', 'reference_query' => 'SELECT u.username, COUNT(*) AS hard_solves FROM submissions s JOIN users u ON u.id = s.user_id JOIN problems p ON p.id = s.problem_id WHERE s.verdict = \'AC\' AND p.difficulty = \'Hard\' GROUP BY u.id, u.username ORDER BY hard_solves DESC, u.username ASC', 'max_score' => 1000, 'order_sensitive' => 1],
        ];

        foreach ($challenges as $challenge) {
            DB::table('sql_challenges')->updateOrInsert(
                ['id' => $challenge['id']],
                array_merge($challenge, ['created_at' => $this->now()])
            );
        }
    }

    private function seedSqlBattles(): void
    {
        $battles = [
            ['id' => 'dev_sb1', 'challenge_id' => 'sql1', 'player1_id' => 'u2', 'player2_id' => 'u5', 'status' => 'completed', 'winner_id' => 'u5', 'completed_at' => $this->daysAgo(10)],
            ['id' => 'dev_sb2', 'challenge_id' => 'sql3', 'player1_id' => 'u6', 'player2_id' => 'u9', 'status' => 'completed', 'winner_id' => 'u9', 'completed_at' => $this->daysAgo(8)],
            ['id' => 'dev_sb3', 'challenge_id' => 'sql2', 'player1_id' => 'u1', 'player2_id' => 'u8', 'status' => 'active', 'winner_id' => null, 'completed_at' => null],
            ['id' => 'dev_sb4', 'challenge_id' => 'sql4', 'player1_id' => 'u11', 'player2_id' => 'u14', 'status' => 'completed', 'winner_id' => 'u11', 'completed_at' => $this->daysAgo(3)],
            ['id' => 'dev_sb5', 'challenge_id' => 'sql5', 'player1_id' => 'u2', 'player2_id' => 'u6', 'status' => 'cancelled', 'winner_id' => null, 'completed_at' => $this->daysAgo(1)],
        ];

        foreach ($battles as $battle) {
            DB::table('sql_battles')->updateOrInsert(
                ['id' => $battle['id']],
                array_merge($battle, ['created_at' => $this->now()])
            );
        }
    }

    private function seedSqlAttempts(): void
    {
        $attempts = [
            ['id' => 'dev_sa1', 'battle_id' => 'dev_sb1', 'challenge_id' => 'sql1', 'user_id' => 'u2', 'submitted_query' => 'SELECT username, rating FROM arena_users WHERE rating > 1400 ORDER BY rating DESC', 'status' => 'accepted', 'execution_time_ms' => 12.5, 'efficiency_score' => 950, 'score' => 1000, 'feedback' => 'Perfect query!', 'submitted_at' => $this->daysAgo(10)->addMinutes(5)],
            ['id' => 'dev_sa2', 'battle_id' => 'dev_sb1', 'challenge_id' => 'sql1', 'user_id' => 'u5', 'submitted_query' => 'SELECT username, rating FROM arena_users WHERE rating > 1400 ORDER BY rating DESC', 'status' => 'accepted', 'execution_time_ms' => 10.2, 'efficiency_score' => 980, 'score' => 1000, 'feedback' => 'Great job!', 'submitted_at' => $this->daysAgo(10)->addMinutes(3)],
            ['id' => 'dev_sa3', 'battle_id' => 'dev_sb2', 'challenge_id' => 'sql3', 'user_id' => 'u6', 'submitted_query' => 'SELECT university, AVG(rating) FROM arena_users GROUP BY university', 'status' => 'wrong_answer', 'execution_time_ms' => 8.1, 'efficiency_score' => 600, 'score' => 400, 'feedback' => 'Missing ROUND and ORDER BY.', 'submitted_at' => $this->daysAgo(8)->addMinutes(10)],
            ['id' => 'dev_sa4', 'battle_id' => 'dev_sb2', 'challenge_id' => 'sql3', 'user_id' => 'u9', 'submitted_query' => 'SELECT university, ROUND(AVG(rating), 2) AS avg_rating FROM arena_users GROUP BY university ORDER BY avg_rating DESC', 'status' => 'accepted', 'execution_time_ms' => 15.3, 'efficiency_score' => 900, 'score' => 1000, 'feedback' => 'Correct!', 'submitted_at' => $this->daysAgo(8)->addMinutes(8)],
            ['id' => 'dev_sa5', 'battle_id' => 'dev_sb3', 'challenge_id' => 'sql2', 'user_id' => 'u1', 'submitted_query' => 'SELECT username, COUNT(*) FROM arena_users u JOIN arena_submissions s ON s.user_id = u.user_id WHERE s.verdict = \'AC\' GROUP BY username', 'status' => 'rejected', 'execution_time_ms' => null, 'efficiency_score' => 0, 'score' => 0, 'feedback' => 'Syntax error near GROUP BY.', 'submitted_at' => $this->hoursAgo(1)],
            ['id' => 'dev_sa6', 'battle_id' => null, 'challenge_id' => 'sql4', 'user_id' => 'u14', 'submitted_query' => 'SELECT p.problem_id, p.title FROM arena_problems p LEFT JOIN arena_submissions s ON s.problem_id = p.problem_id AND s.verdict = \'AC\' WHERE s.submission_id IS NULL ORDER BY p.problem_id', 'status' => 'accepted', 'execution_time_ms' => 20.0, 'efficiency_score' => 880, 'score' => 1000, 'feedback' => 'Excellent!', 'submitted_at' => $this->daysAgo(2)],
            ['id' => 'dev_sa7', 'battle_id' => 'dev_sb4', 'challenge_id' => 'sql4', 'user_id' => 'u14', 'submitted_query' => 'SELECT p.problem_id, p.title FROM arena_problems p LEFT JOIN arena_submissions s ON s.problem_id = p.problem_id WHERE s.verdict = \'AC\' ORDER BY p.problem_id', 'status' => 'wrong_answer', 'execution_time_ms' => 11.0, 'efficiency_score' => 500, 'score' => 300, 'feedback' => 'Missing filter on accepted submissions.', 'submitted_at' => $this->daysAgo(3)->addMinutes(15)],
            ['id' => 'dev_sa8', 'battle_id' => 'dev_sb4', 'challenge_id' => 'sql4', 'user_id' => 'u11', 'submitted_query' => 'SELECT p.problem_id, p.title FROM arena_problems p LEFT JOIN arena_submissions s ON s.problem_id = p.problem_id AND s.verdict = \'AC\' WHERE s.submission_id IS NULL ORDER BY p.problem_id', 'status' => 'accepted', 'execution_time_ms' => 18.5, 'efficiency_score' => 920, 'score' => 1000, 'feedback' => 'Spot on!', 'submitted_at' => $this->daysAgo(3)->addMinutes(12)],
            ['id' => 'dev_sa9', 'battle_id' => 'dev_sb5', 'challenge_id' => 'sql5', 'user_id' => 'u2', 'submitted_query' => 'SELECT c.name, MAX(cp.score), u.username FROM contests c JOIN contest_participants cp ON cp.contest_id = c.id JOIN users u ON u.id = cp.user_id GROUP BY c.name', 'status' => 'error', 'execution_time_ms' => null, 'efficiency_score' => 0, 'score' => 0, 'feedback' => 'Query execution error.', 'submitted_at' => $this->daysAgo(1)->addMinutes(5)],
            ['id' => 'dev_sa10', 'battle_id' => null, 'challenge_id' => 'sql6', 'user_id' => 'u9', 'submitted_query' => 'SELECT u.username, COUNT(*) AS hard_solves FROM submissions s JOIN users u ON u.id = s.user_id JOIN problems p ON p.id = s.problem_id WHERE s.verdict = \'AC\' AND p.difficulty = \'Hard\' GROUP BY u.id, u.username ORDER BY hard_solves DESC, u.username ASC', 'status' => 'accepted', 'execution_time_ms' => 25.0, 'efficiency_score' => 850, 'score' => 1000, 'feedback' => 'Nice work!', 'submitted_at' => $this->daysAgo(1)],
        ];

        foreach ($attempts as $attempt) {
            DB::table('sql_attempts')->updateOrInsert(
                ['id' => $attempt['id']],
                $attempt
            );
        }
    }

    private function seedArena(): void
    {
        $universities = [
            'United International University',
            'North South University',
            'BRAC University',
            'Independent University, Bangladesh',
            'American International University-Bangladesh',
            'East West University',
            'Daffodil International University',
            'University of Asia Pacific',
            'Ahsanullah University of Science and Technology',
            'University of Liberal Arts Bangladesh',
            'Bangladesh University of Engineering and Technology',
            'Rajshahi University of Engineering and Technology',
            'Khulna University of Engineering and Technology',
            'Chittagong University of Engineering and Technology',
            'Metropolitan Computing Academy',
        ];

        foreach ($universities as $uni) {
            DB::table('arena_universities')->updateOrInsert(['name' => $uni]);
        }

        $arenaUsers = [
            ['user_id' => 'u1', 'username' => 'Ismail', 'university' => 'North South University', 'rating' => 1451],
            ['user_id' => 'u2', 'username' => 'Tamjid', 'university' => 'BRAC University', 'rating' => 1520],
            ['user_id' => 'u3', 'username' => 'David', 'university' => 'Independent University, Bangladesh', 'rating' => 1300],
            ['user_id' => 'u5', 'username' => 'Nadia', 'university' => 'East West University', 'rating' => 1610],
            ['user_id' => 'u6', 'username' => 'Rafi', 'university' => 'Daffodil International University', 'rating' => 1750],
            ['user_id' => 'u9', 'username' => 'Lima', 'university' => 'University of Liberal Arts Bangladesh', 'rating' => 1890],
            ['user_id' => 'u14', 'username' => 'Mehedi', 'university' => 'Metropolitan Computing Academy', 'rating' => 1560],
        ];

        foreach ($arenaUsers as $au) {
            DB::table('arena_users')->updateOrInsert(['user_id' => $au['user_id']], $au);
        }

        $arenaProblems = [
            ['problem_id' => 'p1', 'title' => 'Hello World Logic', 'topic' => 'Basic', 'difficulty' => 'Easy'],
            ['problem_id' => 'p2', 'title' => 'Even or Odd', 'topic' => 'Math', 'difficulty' => 'Easy'],
            ['problem_id' => 'p3', 'title' => 'Array Sum', 'topic' => 'Arrays', 'difficulty' => 'Easy'],
            ['problem_id' => 'p4', 'title' => 'Tree Query', 'topic' => 'Trees', 'difficulty' => 'Hard'],
            ['problem_id' => 'p5', 'title' => 'String Hashing', 'topic' => 'Strings', 'difficulty' => 'Medium'],
            ['problem_id' => 'p6', 'title' => 'Shortest Path Sprint', 'topic' => 'Graphs', 'difficulty' => 'Medium'],
            ['problem_id' => 'p8', 'title' => 'Binary Search Boundaries', 'topic' => 'Binary Search', 'difficulty' => 'Medium'],
            ['problem_id' => 'p12', 'title' => 'Tree Diameter', 'topic' => 'Trees', 'difficulty' => 'Medium'],
            ['problem_id' => 'p14', 'title' => 'DAG Routes', 'topic' => 'Graphs', 'difficulty' => 'Hard'],
            ['problem_id' => 'p20', 'title' => 'Longest Increasing Subsequence', 'topic' => 'DP', 'difficulty' => 'Hard'],
        ];

        foreach ($arenaProblems as $ap) {
            DB::table('arena_problems')->updateOrInsert(['problem_id' => $ap['problem_id']], $ap);
        }

        $arenaSubmissions = [
            ['submission_id' => 'dev_a1', 'user_id' => 'u1', 'problem_id' => 'p1', 'verdict' => 'AC', 'runtime_ms' => 18],
            ['submission_id' => 'dev_a2', 'user_id' => 'u1', 'problem_id' => 'p2', 'verdict' => 'AC', 'runtime_ms' => 14],
            ['submission_id' => 'dev_a3', 'user_id' => 'u1', 'problem_id' => 'p3', 'verdict' => 'AC', 'runtime_ms' => 22],
            ['submission_id' => 'dev_a4', 'user_id' => 'u2', 'problem_id' => 'p1', 'verdict' => 'AC', 'runtime_ms' => 15],
            ['submission_id' => 'dev_a5', 'user_id' => 'u2', 'problem_id' => 'p3', 'verdict' => 'AC', 'runtime_ms' => 20],
            ['submission_id' => 'dev_a6', 'user_id' => 'u2', 'problem_id' => 'p5', 'verdict' => 'WA', 'runtime_ms' => 41],
            ['submission_id' => 'dev_a7', 'user_id' => 'u3', 'problem_id' => 'p1', 'verdict' => 'AC', 'runtime_ms' => 22],
            ['submission_id' => 'dev_a8', 'user_id' => 'u5', 'problem_id' => 'p4', 'verdict' => 'AC', 'runtime_ms' => 80],
            ['submission_id' => 'dev_a9', 'user_id' => 'u5', 'problem_id' => 'p6', 'verdict' => 'AC', 'runtime_ms' => 55],
            ['submission_id' => 'dev_a10', 'user_id' => 'u5', 'problem_id' => 'p8', 'verdict' => 'AC', 'runtime_ms' => 35],
            ['submission_id' => 'dev_a11', 'user_id' => 'u6', 'problem_id' => 'p12', 'verdict' => 'AC', 'runtime_ms' => 60],
            ['submission_id' => 'dev_a12', 'user_id' => 'u6', 'problem_id' => 'p14', 'verdict' => 'TLE', 'runtime_ms' => 2000],
            ['submission_id' => 'dev_a13', 'user_id' => 'u9', 'problem_id' => 'p14', 'verdict' => 'AC', 'runtime_ms' => 90],
            ['submission_id' => 'dev_a14', 'user_id' => 'u9', 'problem_id' => 'p20', 'verdict' => 'AC', 'runtime_ms' => 70],
            ['submission_id' => 'dev_a15', 'user_id' => 'u14', 'problem_id' => 'p6', 'verdict' => 'WA', 'runtime_ms' => 49],
            ['submission_id' => 'dev_a16', 'user_id' => 'u3', 'problem_id' => 'p12', 'verdict' => 'RE', 'runtime_ms' => 120],
        ];

        foreach ($arenaSubmissions as $as) {
            DB::table('arena_submissions')->updateOrInsert(['submission_id' => $as['submission_id']], $as);
        }
    }

    private function seedLearning(): void
    {
        $this->seedLearningModules();
        $this->seedLearningSteps();
        $this->seedPlayChallenges();
        $this->seedLearningProblems();
        $this->seedLearningProgress();
    }

    private function seedLearningModules(): void
    {
        $modules = [
            ['id' => 'lm1', 'title' => 'Binary Search', 'slug' => 'binary-search-basics', 'topic' => 'Searching', 'description' => 'Master binary search from first principles.', 'difficulty' => 'Beginner', 'estimated_minutes' => 20, 'xp_reward' => 150, 'is_active' => true],
            ['id' => 'lm2', 'title' => 'Arrays & Loops', 'slug' => 'arrays-loops-fundamentals', 'topic' => 'Arrays', 'description' => 'Learn array manipulation and loop invariants.', 'difficulty' => 'Beginner', 'estimated_minutes' => 25, 'xp_reward' => 100, 'is_active' => true],
            ['id' => 'lm3', 'title' => 'Graph Traversal', 'slug' => 'graph-traversal-bfs-dfs', 'topic' => 'Graphs', 'description' => 'Understand BFS and DFS for graph exploration.', 'difficulty' => 'Medium', 'estimated_minutes' => 30, 'xp_reward' => 200, 'is_active' => true],
            ['id' => 'lm4', 'title' => 'Dynamic Programming Intro', 'slug' => 'dp-introduction', 'topic' => 'DP', 'description' => 'Introduction to DP with classic problems.', 'difficulty' => 'Medium', 'estimated_minutes' => 35, 'xp_reward' => 250, 'is_active' => true],
            ['id' => 'lm5', 'title' => 'String Patterns', 'slug' => 'string-patterns', 'topic' => 'Strings', 'description' => 'Pattern matching and string algorithms.', 'difficulty' => 'Easy', 'estimated_minutes' => 20, 'xp_reward' => 120, 'is_active' => true],
        ];

        foreach ($modules as $module) {
            DB::table('learning_modules')->updateOrInsert(
                ['id' => $module['id']],
                array_merge($module, ['created_at' => $this->now()])
            );
        }
    }

    private function seedLearningSteps(): void
    {
        $now = $this->now();

        $steps = [
            ['id' => 'dev_ls1', 'learning_module_id' => 'lm1', 'step_order' => 1, 'type' => 'explanation', 'title' => 'What is binary search?', 'content' => 'Binary search finds a target in a sorted array by halving the search space.', 'question' => null, 'options' => null, 'correct_answer' => null, 'xp_reward' => 10],
            ['id' => 'dev_ls2', 'learning_module_id' => 'lm1', 'step_order' => 2, 'type' => 'explanation', 'title' => 'Why sorted data matters', 'content' => 'Sorting is the prerequisite that makes halving decisions meaningful.', 'question' => null, 'options' => null, 'correct_answer' => null, 'xp_reward' => 10],
            ['id' => 'dev_ls3', 'learning_module_id' => 'lm1', 'step_order' => 3, 'type' => 'mcq', 'title' => 'Predict the midpoint', 'content' => 'Find the midpoint of [1,3,5,7,9] searching for 5.', 'question' => 'What is arr[mid]?', 'options' => json_encode(['3', '5', '7', '1']), 'correct_answer' => 'B', 'xp_reward' => 15],
            ['id' => 'dev_ls4', 'learning_module_id' => 'lm1', 'step_order' => 4, 'type' => 'code_trace', 'title' => 'Trace a complete search', 'content' => 'Trace binary search on [2,4,6,8,10,12,14] for target 10.', 'question' => 'At which iteration is 10 found?', 'options' => json_encode(['Iteration 1', 'Iteration 2', 'Iteration 3']), 'correct_answer' => 'B', 'xp_reward' => 20],
            ['id' => 'dev_ls5', 'learning_module_id' => 'lm2', 'step_order' => 1, 'type' => 'explanation', 'title' => 'Array basics', 'content' => 'Arrays store elements at contiguous indices allowing O(1) access.', 'question' => null, 'options' => null, 'correct_answer' => null, 'xp_reward' => 10],
            ['id' => 'dev_ls6', 'learning_module_id' => 'lm2', 'step_order' => 2, 'type' => 'mcq', 'title' => 'Loop invariant', 'content' => 'A loop invariant is a condition that remains true before and after each iteration.', 'question' => 'What is a loop invariant?', 'options' => json_encode(['A condition true every iteration', 'A counter', 'A break statement', 'A variable name']), 'correct_answer' => 'A', 'xp_reward' => 15],
            ['id' => 'dev_ls7', 'learning_module_id' => 'lm3', 'step_order' => 1, 'type' => 'explanation', 'title' => 'BFS overview', 'content' => 'BFS explores layer by layer using a queue.', 'question' => null, 'options' => null, 'correct_answer' => null, 'xp_reward' => 10],
            ['id' => 'dev_ls8', 'learning_module_id' => 'lm3', 'step_order' => 2, 'type' => 'prediction', 'title' => 'Predict BFS order', 'content' => 'Starting from node 1, predict the BFS traversal order.', 'question' => 'What is the second node visited?', 'options' => json_encode(['2', '3', '4', '5']), 'correct_answer' => 'A', 'xp_reward' => 15],
        ];

        foreach ($steps as $step) {
            DB::table('learning_steps')->updateOrInsert(
                ['id' => $step['id']],
                array_merge($step, ['created_at' => $now])
            );
        }
    }

    private function seedPlayChallenges(): void
    {
        $now = $this->now();

        $challenges = [
            ['id' => 'dev_pc1', 'learning_module_id' => 'lm1', 'type' => 'fill_blank', 'title' => 'Choose Left or Right', 'instructions' => 'Fill in the boundary update for binary search.', 'config' => json_encode(['starter' => 'high = ______;', 'blanks' => ['mid - 1']]), 'xp_reward' => 25, 'time_limit' => 300],
            ['id' => 'dev_pc2', 'learning_module_id' => 'lm1', 'type' => 'coding', 'title' => 'Find the mid', 'instructions' => 'Predict the exact mid index at each step.', 'config' => json_encode(['array' => [1, 4, 7, 10, 13, 16, 19], 'target' => 13]), 'xp_reward' => 30, 'time_limit' => 240],
            ['id' => 'dev_pc3', 'learning_module_id' => 'lm1', 'type' => 'trace', 'title' => 'Minimum moves', 'instructions' => 'Trace binary search in minimum moves.', 'config' => json_encode(['array_length' => 15, 'target_exists' => true, 'max_moves' => 4]), 'xp_reward' => 40, 'time_limit' => 180],
            ['id' => 'dev_pc9', 'learning_module_id' => 'lm1', 'type' => 'binary_search', 'title' => 'HALF HUNT', 'instructions' => 'Decide LEFT or RIGHT at each step to hunt the target.', 'config' => json_encode(['array' => [3, 8, 12, 17, 24, 31, 42, 56, 68], 'target' => 42, 'max_moves' => 5, 'scoring' => ['correct_decision' => 15, 'mistake' => -10, 'found_bonus' => 15, 'efficiency_bonus' => 5, 'min_score' => 0]]), 'xp_reward' => 35, 'time_limit' => 240],
            ['id' => 'dev_pc4', 'learning_module_id' => 'lm2', 'type' => 'coding', 'title' => 'Array Reversal', 'instructions' => 'Reverse an array in-place using two pointers.', 'config' => json_encode(['max_size' => 1000]), 'xp_reward' => 30, 'time_limit' => 300],
            ['id' => 'dev_pc5', 'learning_module_id' => 'lm3', 'type' => 'fill_blank', 'title' => 'Queue or Stack?', 'instructions' => 'Choose the right data structure for BFS.', 'config' => json_encode(['starter' => 'queue.push(______);', 'blanks' => ['neighbor']]), 'xp_reward' => 20, 'time_limit' => 240],
            ['id' => 'dev_pc6', 'learning_module_id' => 'lm3', 'type' => 'trace', 'title' => 'DFS Recursion Trace', 'instructions' => 'Trace DFS recursion stack on a tree.', 'config' => json_encode(['nodes' => 7, 'edges' => 6]), 'xp_reward' => 35, 'time_limit' => 200],
            ['id' => 'dev_pc7', 'learning_module_id' => 'lm4', 'type' => 'coding', 'title' => 'Fibonacci DP', 'instructions' => 'Implement Fibonacci using memoization.', 'config' => json_encode(['max_n' => 50]), 'xp_reward' => 40, 'time_limit' => 300],
            ['id' => 'dev_pc8', 'learning_module_id' => 'lm5', 'type' => 'coding', 'title' => 'KMP Intuition', 'instructions' => 'Understand the failure function in KMP.', 'config' => json_encode(['question' => 'What does the LPS array store?', 'options' => ['Longest Prefix Suffix', 'Longest Proper Suffix', 'Least Possible Shift', 'Longest Prefix'], 'answer' => 'Longest Prefix Suffix']), 'xp_reward' => 25, 'time_limit' => 180],
        ];

        foreach ($challenges as $challenge) {
            DB::table('play_challenges')->updateOrInsert(
                ['id' => $challenge['id']],
                array_merge($challenge, ['created_at' => $now])
            );
        }
    }

    private function seedLearningProblems(): void
    {
        $problems = [
            ['id' => 'dev_lp1', 'learning_module_id' => 'lm1', 'problem_id' => 'p8', 'stage' => 'practice', 'sort_order' => 1],
            ['id' => 'dev_lp2', 'learning_module_id' => 'lm1', 'problem_id' => 'p15', 'stage' => 'challenge', 'sort_order' => 2],
            ['id' => 'dev_lp3', 'learning_module_id' => 'lm2', 'problem_id' => 'p3', 'stage' => 'practice', 'sort_order' => 1],
            ['id' => 'dev_lp4', 'learning_module_id' => 'lm2', 'problem_id' => 'p10', 'stage' => 'challenge', 'sort_order' => 2],
            ['id' => 'dev_lp5', 'learning_module_id' => 'lm2', 'problem_id' => 'p17', 'stage' => 'boss', 'sort_order' => 3],
            ['id' => 'dev_lp6', 'learning_module_id' => 'lm3', 'problem_id' => 'p6', 'stage' => 'practice', 'sort_order' => 1],
            ['id' => 'dev_lp7', 'learning_module_id' => 'lm3', 'problem_id' => 'p12', 'stage' => 'challenge', 'sort_order' => 2],
            ['id' => 'dev_lp8', 'learning_module_id' => 'lm3', 'problem_id' => 'p18', 'stage' => 'boss', 'sort_order' => 3],
            ['id' => 'dev_lp9', 'learning_module_id' => 'lm4', 'problem_id' => 'p7', 'stage' => 'practice', 'sort_order' => 1],
            ['id' => 'dev_lp10', 'learning_module_id' => 'lm4', 'problem_id' => 'p20', 'stage' => 'challenge', 'sort_order' => 2],
            ['id' => 'dev_lp11', 'learning_module_id' => 'lm4', 'problem_id' => 'p23', 'stage' => 'boss', 'sort_order' => 3],
            ['id' => 'dev_lp12', 'learning_module_id' => 'lm5', 'problem_id' => 'p5', 'stage' => 'practice', 'sort_order' => 1],
            ['id' => 'dev_lp13', 'learning_module_id' => 'lm5', 'problem_id' => 'p11', 'stage' => 'challenge', 'sort_order' => 2],
            ['id' => 'dev_lp14', 'learning_module_id' => 'lm5', 'problem_id' => 'p19', 'stage' => 'boss', 'sort_order' => 3],
        ];

        foreach ($problems as $lp) {
            DB::table('learning_problems')->updateOrInsert(
                ['id' => $lp['id']],
                array_merge($lp, ['created_at' => $this->now()])
            );
        }
    }

    private function seedLearningProgress(): void
    {
        $now = $this->now();

        $progress = [
            ['id' => 'dev_lprog1', 'user_id' => 'u1', 'learning_module_id' => 'lm1', 'learn_completed' => true, 'play_completed' => true, 'prove_completed' => true, 'learn_score' => 85, 'play_score' => 90, 'prove_score' => 100, 'mastery_score' => 92, 'attempts' => 1, 'hints_used' => 1, 'started_at' => $this->daysAgo(15), 'completed_at' => $this->daysAgo(10), 'learn_accuracy' => 85, 'play_accuracy' => 90, 'prove_accuracy' => 100, 'concept_mastery' => 92, 'weakness_signal' => null, 'repeated_failed_concepts' => json_encode([]), 'learn_attempts' => 1, 'play_attempts' => 1, 'prove_attempts' => 1, 'learn_correct' => 4, 'play_correct' => 3, 'prove_correct' => 1, 'learn_completed_steps' => json_encode(['dev_ls1', 'dev_ls2', 'dev_ls3', 'dev_ls4'])],
            ['id' => 'dev_lprog2', 'user_id' => 'u2', 'learning_module_id' => 'lm1', 'learn_completed' => true, 'play_completed' => true, 'prove_completed' => false, 'learn_score' => 100, 'play_score' => 75, 'prove_score' => null, 'mastery_score' => 75, 'attempts' => 2, 'hints_used' => 0, 'started_at' => $this->daysAgo(12), 'completed_at' => $this->daysAgo(8), 'learn_accuracy' => 100, 'play_accuracy' => 75, 'prove_accuracy' => null, 'concept_mastery' => 75, 'weakness_signal' => 'prove', 'repeated_failed_concepts' => json_encode(['boundary']), 'learn_attempts' => 1, 'play_attempts' => 2, 'prove_attempts' => 1, 'learn_correct' => 4, 'play_correct' => 3, 'prove_correct' => 0, 'learn_completed_steps' => json_encode(['dev_ls1', 'dev_ls2', 'dev_ls3', 'dev_ls4'])],
            ['id' => 'dev_lprog3', 'user_id' => 'u3', 'learning_module_id' => 'lm2', 'learn_completed' => true, 'play_completed' => false, 'prove_completed' => false, 'learn_score' => 70, 'play_score' => null, 'prove_score' => null, 'mastery_score' => 50, 'attempts' => 3, 'hints_used' => 2, 'started_at' => $this->daysAgo(8), 'completed_at' => $this->daysAgo(5), 'learn_accuracy' => 70, 'play_accuracy' => null, 'prove_accuracy' => null, 'concept_mastery' => 50, 'weakness_signal' => 'play', 'repeated_failed_concepts' => json_encode(['loops', 'off-by-one']), 'learn_attempts' => 3, 'play_attempts' => 2, 'prove_attempts' => 0, 'learn_correct' => 2, 'play_correct' => 0, 'prove_correct' => 0, 'learn_completed_steps' => json_encode(['dev_ls5', 'dev_ls6'])],
            ['id' => 'dev_lprog4', 'user_id' => 'u5', 'learning_module_id' => 'lm3', 'learn_completed' => true, 'play_completed' => true, 'prove_completed' => true, 'learn_score' => 95, 'play_score' => 100, 'prove_score' => 80, 'mastery_score' => 90, 'attempts' => 1, 'hints_used' => 0, 'started_at' => $this->daysAgo(10), 'completed_at' => $this->daysAgo(4), 'learn_accuracy' => 95, 'play_accuracy' => 100, 'prove_accuracy' => 80, 'concept_mastery' => 90, 'weakness_signal' => null, 'repeated_failed_concepts' => json_encode([]), 'learn_attempts' => 1, 'play_attempts' => 1, 'prove_attempts' => 1, 'learn_correct' => 2, 'play_correct' => 2, 'prove_correct' => 1, 'learn_completed_steps' => json_encode(['dev_ls7', 'dev_ls8'])],
            ['id' => 'dev_lprog5', 'user_id' => 'u6', 'learning_module_id' => 'lm4', 'learn_completed' => false, 'play_completed' => false, 'prove_completed' => false, 'learn_score' => null, 'play_score' => null, 'prove_score' => null, 'mastery_score' => null, 'attempts' => 1, 'hints_used' => 1, 'started_at' => $this->hoursAgo(12), 'completed_at' => null, 'learn_accuracy' => null, 'play_accuracy' => null, 'prove_accuracy' => null, 'concept_mastery' => null, 'weakness_signal' => null, 'repeated_failed_concepts' => json_encode([]), 'learn_attempts' => 1, 'play_attempts' => 0, 'prove_attempts' => 0, 'learn_correct' => 0, 'play_correct' => 0, 'prove_correct' => 0, 'learn_completed_steps' => json_encode([])],
            ['id' => 'dev_lprog6', 'user_id' => 'u9', 'learning_module_id' => 'lm5', 'learn_completed' => true, 'play_completed' => true, 'prove_completed' => true, 'learn_score' => 90, 'play_score' => 85, 'prove_score' => 95, 'mastery_score' => 88, 'attempts' => 1, 'hints_used' => 0, 'started_at' => $this->daysAgo(6), 'completed_at' => $this->daysAgo(2), 'learn_accuracy' => 90, 'play_accuracy' => 85, 'prove_accuracy' => 95, 'concept_mastery' => 88, 'weakness_signal' => null, 'repeated_failed_concepts' => json_encode([]), 'learn_attempts' => 1, 'play_attempts' => 1, 'prove_attempts' => 1, 'learn_correct' => 2, 'play_correct' => 2, 'prove_correct' => 1, 'learn_completed_steps' => json_encode(['dev_ls5', 'dev_ls6'])],
            ['id' => 'dev_lprog7', 'user_id' => 'u7', 'learning_module_id' => 'lm2', 'learn_completed' => false, 'play_completed' => false, 'prove_completed' => false, 'learn_score' => 40, 'play_score' => null, 'prove_score' => null, 'mastery_score' => null, 'attempts' => 4, 'hints_used' => 3, 'started_at' => $this->daysAgo(3), 'completed_at' => null, 'learn_accuracy' => 40, 'play_accuracy' => null, 'prove_accuracy' => null, 'concept_mastery' => null, 'weakness_signal' => 'learn', 'repeated_failed_concepts' => json_encode(['indexing', 'bounds']), 'learn_attempts' => 4, 'play_attempts' => 0, 'prove_attempts' => 0, 'learn_correct' => 1, 'play_correct' => 0, 'prove_correct' => 0, 'learn_completed_steps' => json_encode(['dev_ls5'])],
        ];

        foreach ($progress as $prog) {
            DB::table('learning_progress')->updateOrInsert(
                ['id' => $prog['id']],
                array_merge($prog, ['created_at' => $now])
            );
        }
    }

    private function seedActivityLogs(): void
    {
        $logs = [
            ['user_id' => 'u1', 'action' => 'login', 'details' => 'User logged in from 192.168.1.10', 'created_at' => $this->hoursAgo(2)],
            ['user_id' => 'u1', 'action' => 'problem_start', 'details' => 'Started problem p15', 'created_at' => $this->hoursAgo(10)],
            ['user_id' => 'u1', 'action' => 'submission', 'details' => 'Submitted WA for p15', 'created_at' => $this->hoursAgo(9)],
            ['user_id' => 'u2', 'action' => 'contest_join', 'details' => 'Joined contest c3', 'created_at' => $this->daysAgo(3)],
            ['user_id' => 'u2', 'action' => 'submission', 'details' => 'Submitted AC for p8 in contest c3', 'created_at' => $this->daysAgo(2)],
            ['user_id' => 'u3', 'action' => 'login', 'details' => 'User logged in from 10.0.0.5', 'created_at' => $this->daysAgo(7)],
            ['user_id' => 'u5', 'action' => 'ghost_race', 'details' => 'Won ghost race gr2 against u1 on p5', 'created_at' => $this->daysAgo(5)],
            ['user_id' => 'u5', 'action' => 'learn_start', 'details' => 'Started learning module lm3', 'created_at' => $this->daysAgo(10)],
            ['user_id' => 'u6', 'action' => 'contest_join', 'details' => 'Joined contest c3', 'created_at' => $this->daysAgo(3)],
            ['user_id' => 'u6', 'action' => 'submission', 'details' => 'Submitted WA for p20 in contest c3', 'created_at' => $this->daysAgo(1)],
            ['user_id' => 'u6', 'action' => 'sql_battle', 'details' => 'Lost sql battle dev_sb2 to u9', 'created_at' => $this->daysAgo(8)],
            ['user_id' => 'u9', 'action' => 'login', 'details' => 'User logged in from 172.16.0.3', 'created_at' => $this->hoursAgo(5)],
            ['user_id' => 'u9', 'action' => 'submission', 'details' => 'Submitted AC for p14 in contest c3', 'created_at' => $this->daysAgo(2)],
            ['user_id' => 'u9', 'action' => 'sql_battle', 'details' => 'Won sql battle dev_sb2 against u6', 'created_at' => $this->daysAgo(8)],
            ['user_id' => 'u14', 'action' => 'login', 'details' => 'User logged in from 192.168.2.50', 'created_at' => $this->daysAgo(15)],
            ['user_id' => 'u14', 'action' => 'submission', 'details' => 'Submitted AC for p21', 'created_at' => $this->daysAgo(15)],
            ['user_id' => 'u14', 'action' => 'sql_attempt', 'details' => 'Accepted sql challenge sql4', 'created_at' => $this->daysAgo(2)],
            ['user_id' => null, 'action' => 'system', 'details' => 'Cron job: update contest statuses', 'created_at' => $this->hoursAgo(1)],
            ['user_id' => 'dev_admin', 'action' => 'admin_action', 'details' => 'Created contest c7', 'created_at' => $this->daysAgo(7)],
            ['user_id' => 'dev_admin', 'action' => 'admin_action', 'details' => 'Created contest c8', 'created_at' => $this->daysAgo(2)],
            ['user_id' => 'u8', 'action' => 'learn_start', 'details' => 'Started learning module lm2', 'created_at' => $this->daysAgo(5)],
            ['user_id' => 'u8', 'action' => 'submission', 'details' => 'Submitted AC for p12', 'created_at' => $this->daysAgo(2)],
            ['user_id' => 'u4', 'action' => 'login', 'details' => 'User logged in from 10.0.0.8', 'created_at' => $this->daysAgo(6)],
            ['user_id' => 'u4', 'action' => 'problem_start', 'details' => 'Started problem p1', 'created_at' => $this->daysAgo(6)],
            ['user_id' => 'u4', 'action' => 'problem_abandon', 'details' => 'Abandoned problem p1', 'created_at' => $this->daysAgo(6)->addMinutes(15)],
            ['user_id' => 'u10', 'action' => 'contest_join', 'details' => 'Joined contest c5', 'created_at' => $this->daysAgo(5)],
            ['user_id' => 'u10', 'action' => 'submission', 'details' => 'Submitted CE for p2 in c5', 'created_at' => $this->daysAgo(5)],
            ['user_id' => 'u11', 'action' => 'sql_battle', 'details' => 'Won sql battle dev_sb4 against u14', 'created_at' => $this->daysAgo(3)],
            ['user_id' => 'u11', 'action' => 'submission', 'details' => 'Submitted AC for p9 in c2', 'created_at' => $this->daysAgo(19)],
        ];

        foreach ($logs as $log) {
            DB::table('activity_logs')->insert(array_merge($log, ['created_at' => $log['created_at']]));
        }
    }
}
