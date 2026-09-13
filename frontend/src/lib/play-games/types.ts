export interface PlayChallenge {
  id: string;
  type: string;
  title: string;
  instructions: string;
  config: Record<string, any>;
  xp_reward: number;
  time_limit: number;
  completed_score?: number;
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

export type GameType = 'midpoint_master' | 'trace_race' | 'binary_search' | 'fill_blank' | 'coding' | 'trace';

export interface GameResult {
  score: number;
  xp: number;
}
