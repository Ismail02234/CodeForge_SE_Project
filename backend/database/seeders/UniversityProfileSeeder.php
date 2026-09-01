<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UniversityProfileSeeder extends Seeder
{
    private const TEAM_USERNAMES = [
        'nafiz',
        'nafin',
        'ismail',
        'tamjid',
        'ankita',
    ];

    private const UNIVERSITIES = [
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
    ];

    public function run(): void
    {
        if (! Schema::hasTable('universities') || ! Schema::hasTable('users')) {
            return;
        }

        $names = array_column(self::UNIVERSITIES, 'name');

        foreach (self::UNIVERSITIES as $university) {
            DB::table('universities')->updateOrInsert(
                ['name' => $university['name']],
                ['city' => $university['city']]
            );
        }

        $otherUniversities = array_values(
            array_filter(
                $names,
                fn (string $name): bool => $name !== 'United International University'
            )
        );

        $users = DB::table('users')
            ->select('id', 'username')
            ->orderBy('id')
            ->get();

        foreach ($users as $user) {
            $username = strtolower(trim((string) $user->username));

            $university = in_array($username, self::TEAM_USERNAMES, true)
                ? 'United International University'
                : $this->deterministicUniversity($username, $otherUniversities);

            DB::table('users')
                ->where('id', $user->id)
                ->update(['university' => $university]);
        }

        DB::table('universities')
            ->whereNotIn('name', $names)
            ->delete();

        if (Schema::hasTable('arena_universities') && Schema::hasTable('arena_users')) {
            foreach ($names as $name) {
                DB::table('arena_universities')->updateOrInsert(['name' => $name]);
            }

            $arenaUsers = DB::table('arena_users')
                ->select('user_id', 'username')
                ->orderBy('user_id')
                ->get();

            foreach ($arenaUsers as $user) {
                $username = strtolower(trim((string) $user->username));

                $university = in_array($username, self::TEAM_USERNAMES, true)
                    ? 'United International University'
                    : $this->deterministicUniversity($username, $otherUniversities);

                DB::table('arena_users')
                    ->where('user_id', $user->user_id)
                    ->update(['university' => $university]);
            }

            DB::table('arena_universities')
                ->whereNotIn('name', $names)
                ->delete();
        }
    }

    private function deterministicUniversity(string $username, array $universities): string
    {
        if ($universities === []) {
            return 'United International University';
        }

        /*
         * Deterministic pseudo-random assignment keeps demo data stable between
         * machines while still distributing non-team users across universities.
         */
        $index = (int) sprintf('%u', crc32($username)) % count($universities);

        return $universities[$index];
    }
}
