export interface PlayChallenge {
  id: string;
  type: string;
  title: string;
  instructions: string;
  config: Record<string, any>;
  xp_reward: number;
  time_limit: number;
  completed_score?: number;
  completed: boolean;
  best_score: number;
  attempts: number;
  unlocked: boolean;
}

export interface MidpointMasterRound {
  low: number;
  high: number;
  expected_mid: number;
  user_answer: number | null;
  submitted: boolean;
}

export interface TraceRaceMove {
  mid: number;
  dir: 'left' | 'right' | 'found' | '';
}

export interface TraceRaceStep {
  mid: number;
  midVal: number;
  low: number;
  high: number;
  dir: 'left' | 'right' | 'found';
  isTarget: boolean;
}

export type GameType = 'half_hunt' | 'midpoint_master' | 'trace_race' | 'binary_search' | 'fill_blank' | 'coding' | 'trace';

export interface GameResult {
  score: number;
  max_score: number;
  completed: boolean;
  accuracy: number;
  xp_reward: number;
  attempts: number;
  moves?: number;
  mistakes?: number;
  correct_rounds?: number;
  total_rounds?: number;
  play_completed_challenges?: Record<string, any>;
}
