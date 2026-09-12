<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

final class ContestWinProbabilityService
{
    public function __construct(
        private PerformanceProfileService $profiles
    ) {}

    public function data(
        ?string $contestId = null,
        ?string $leftUserId = null,
        ?string $rightUserId = null
    ): array {
        $contests = $this->contests();

        if (! $contestId) {
            return [
                'contests' => $contests,
                'contest' => null,
                'participants' => [],
                'prediction' => null,
            ];
        }

        $contest = DB::table('contests')
            ->where('id', $contestId)
            ->first([
                'id',
                'name',
                'type',
                'status',
                'starts_at',
            ]);

        if (! $contest) {
            throw new RuntimeException('Contest not found.');
        }

        $participants = $this->participants($contestId);

        if (! $leftUserId || ! $rightUserId) {
            return [
                'contests' => $contests,
                'contest' => (array) $contest,
                'participants' => $participants,
                'prediction' => null,
            ];
        }

        if ($leftUserId === $rightUserId) {
            throw new RuntimeException(
                'Choose two different contest participants.'
            );
        }

        $participantIds = array_column($participants, 'id');

        if (
            ! in_array($leftUserId, $participantIds, true) ||
            ! in_array($rightUserId, $participantIds, true)
        ) {
            throw new RuntimeException(
                'Both users must be participants in the selected contest.'
            );
        }

        $left = $this->profiles->calculate($leftUserId);
        $right = $this->profiles->calculate($rightUserId);

        $leftStrength = $this->strength($left);
        $rightStrength = $this->strength($right);
        $total = $leftStrength['total'] + $rightStrength['total'];

        $leftProbability = $total > 0
            ? round(($leftStrength['total'] / $total) * 100, 2)
            : 50.0;

        $rightProbability = round(100 - $leftProbability, 2);

        $leftParticipant = $this->participant(
            $participants,
            $leftUserId
        );

        $rightParticipant = $this->participant(
            $participants,
            $rightUserId
        );

        return [
            'contests' => $contests,
            'contest' => (array) $contest,
            'participants' => $participants,
            'prediction' => [
                'left' => [
                    'user' => $left['user'],
                    'contest_score' => $leftParticipant['contest_score'],
                    'probability' => $leftProbability,
                    'strength' => $leftStrength,
                ],
                'right' => [
                    'user' => $right['user'],
                    'contest_score' => $rightParticipant['contest_score'],
                    'probability' => $rightProbability,
                    'strength' => $rightStrength,
                ],
                'edge' => $leftProbability === $rightProbability
                    ? 'even'
                    : (
                        $leftProbability > $rightProbability
                            ? 'left'
                            : 'right'
                    ),
                'basis' => [
                    'rating' => 'rating / 50',
                    'performance_profile' => 'overall score × 1.5',
                    'consistency' => 'consistency × 0.45',
                ],
                'note' => 'Contest membership is used to define the matchup. The prediction itself uses pre-existing performance strength so current contest score does not leak the contest outcome into the prediction.',
            ],
        ];
    }

    private function contests(): array
    {
        return DB::table('contests as c')
            ->join(
                'contest_participants as cp',
                'cp.contest_id',
                '=',
                'c.id'
            )
            ->groupBy(
                'c.id',
                'c.name',
                'c.type',
                'c.status',
                'c.starts_at'
            )
            ->havingRaw('COUNT(cp.user_id) >= 2')
            ->orderByDesc('c.starts_at')
            ->selectRaw(
                'c.id,
                 c.name,
                 c.type,
                 c.status,
                 c.starts_at,
                 COUNT(cp.user_id) AS participant_count'
            )
            ->get()
            ->map(fn ($row) => [
                'id' => (string) $row->id,
                'name' => (string) $row->name,
                'type' => (string) $row->type,
                'status' => (string) $row->status,
                'starts_at' => $row->starts_at,
                'participant_count' => (int) $row->participant_count,
            ])
            ->all();
    }

    private function participants(string $contestId): array
    {
        return DB::table('contest_participants as cp')
            ->join('users as u', 'u.id', '=', 'cp.user_id')
            ->where('cp.contest_id', $contestId)
            ->orderByDesc('u.rating')
            ->orderBy('u.username')
            ->get([
                'u.id',
                'u.username',
                'u.rating',
                'u.rank',
                'u.university',
                'cp.score as contest_score',
            ])
            ->map(fn ($row) => [
                'id' => (string) $row->id,
                'username' => (string) $row->username,
                'rating' => (int) $row->rating,
                'rank' => (string) $row->rank,
                'university' => $row->university,
                'contest_score' => (int) $row->contest_score,
            ])
            ->all();
    }

    private function strength(array $profile): array
    {
        $rating = (float) ($profile['user']['rating'] ?? 1200);
        $overall = (float) ($profile['overall'] ?? 0);
        $consistency = (float) (
            $profile['dimensions']['consistency'] ?? 0
        );

        $ratingComponent = $rating / 50;
        $profileComponent = $overall * 1.5;
        $consistencyComponent = $consistency * 0.45;

        return [
            'rating_component' => round($ratingComponent, 2),
            'profile_component' => round($profileComponent, 2),
            'consistency_component' => round(
                $consistencyComponent,
                2
            ),
            'total' => round(
                $ratingComponent +
                $profileComponent +
                $consistencyComponent,
                2
            ),
        ];
    }

    private function participant(
        array $participants,
        string $userId
    ): array {
        foreach ($participants as $participant) {
            if ($participant['id'] === $userId) {
                return $participant;
            }
        }

        throw new RuntimeException('Contest participant not found.');
    }
}
