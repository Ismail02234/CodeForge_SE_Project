<script lang="ts">
  import PerformanceRadar from '$lib/components/PerformanceRadar.svelte';
  import SkillGraph from '$lib/components/SkillGraph.svelte';
  import SubmissionAnomaly from '$lib/components/SubmissionAnomaly.svelte';

  type Topic = {
    score: number;
    accuracy: number;
    speed: number;
    difficulty: number;
    recency: number;
    attempted: number;
    solved: number;
  };

  const PROFILE = {
    user: {
      id: 'u1',
      username: 'Ismail',
      rating: 1542,
      rank: 'Advanced',
      university: 'CodeForge University'
    },

    dimensions: {
      problem_solving: 82,
      consistency: 74,
      speed: 68,
      difficulty: 79,
      accuracy: 86,
      recent_activity: 72
    },

    overall: 78,

    archetype: {
      name: 'Balanced Problem Solver',
      tagline: 'Strong accuracy with good problem-solving ability'
    },

    topics: {
      'Arrays': {
        score: 88,
        accuracy: 92,
        speed: 81,
        difficulty: 86,
        recency: 90,
        attempted: 25,
        solved: 23
      },

      'Strings': {
        score: 76,
        accuracy: 81,
        speed: 72,
        difficulty: 74,
        recency: 78,
        attempted: 18,
        solved: 15
      },

      'Linked Lists': {
        score: 64,
        accuracy: 69,
        speed: 58,
        difficulty: 63,
        recency: 61,
        attempted: 14,
        solved: 9
      },

      'Trees': {
        score: 51,
        accuracy: 55,
        speed: 48,
        difficulty: 52,
        recency: 49,
        attempted: 12,
        solved: 6
      },

      'Graphs': {
        score: 43,
        accuracy: 46,
        speed: 41,
        difficulty: 45,
        recency: 39,
        attempted: 10,
        solved: 4
      },

      'Dynamic Programming': {
        score: 37,
        accuracy: 40,
        speed: 34,
        difficulty: 39,
        recency: 32,
        attempted: 8,
        solved: 2
      }
    } as Record<string, Topic>
  };

  const ANOMALY = {
    summary: {
      normal: 31,
      rapid_attempts: 3,
      repeated_failures: 2,
      similarity_flags: 1,
      total_flags: 6,
      risk_level: 'MEDIUM'
    },

    message:
      'Some unusual submission patterns were detected. These signals are for review only and are not proof of cheating.'
  };

  const dimensionEntries = Object.entries(PROFILE.dimensions);
  const topicEntries = Object.entries(PROFILE.topics);
</script>

<svelte:head>
  <title>Performance Profile | CodeForge</title>
</svelte:head>

<div class="page">

  <!-- HEADER -->

  <section class="hero">
    <div>
      <p class="eyebrow">LEARNING INTELLIGENCE</p>

      <h1>
        PERFORMANCE <span>PROFILE</span>
      </h1>

      <p class="subtitle">
        Analyze your coding performance, skill development and submission behavior.
      </p>
    </div>

    <div class="user-card">
      <div class="avatar">I</div>

      <div>
        <strong>{PROFILE.user.username}</strong>
        <small>{PROFILE.user.university}</small>
      </div>
    </div>
  </section>


  <!-- OVERALL -->

  <section class="overview-grid">

    <div class="panel overall-card">
      <p class="label">OVERALL PERFORMANCE</p>

      <div class="score">
        {PROFILE.overall}
        <span>/100</span>
      </div>

      <h2>{PROFILE.archetype.name}</h2>

      <p>{PROFILE.archetype.tagline}</p>
    </div>


    <div class="panel">
      <p class="label">PERFORMANCE DIMENSIONS</p>

      <div class="dimensions">
        {#each dimensionEntries as [name, value]}
          <div class="dimension">
            <div class="dimension-top">
              <span>{name.replaceAll('_', ' ')}</span>
              <strong>{value}</strong>
            </div>

            <div class="bar">
              <div
                class="fill"
                style={`width:${value}%`}
              ></div>
            </div>
          </div>
        {/each}
      </div>
    </div>

  </section>


  <!-- RADAR -->

  <section class="panel">
    <PerformanceRadar
      dimensions={PROFILE.dimensions}
          />
  </section>


  <!-- FEATURE 1 -->

  <section class="feature-header">
    <div>
      <p class="eyebrow">FEATURE 01</p>
      <h2>LIVE SKILL GRAPH</h2>
      <p>
        A competency map showing which programming topics are strong,
        developing or weak.
      </p>
    </div>
  </section>

  <section class="panel">
    <SkillGraph topics={PROFILE.topics} />
  </section>


  <!-- FEATURE 2 -->

  <section class="feature-header">
    <div>
      <p class="eyebrow">FEATURE 02</p>
      <h2>SUBMISSION ANOMALY DETECTION</h2>
      <p>
        Detects unusual submission patterns to help instructors review
        suspicious activity.
      </p>
    </div>
  </section>

  <section class="panel">
    <SubmissionAnomaly data={ANOMALY} />
  </section>


  <!-- QUICK STATS -->

  <section class="stats">

    <div class="stat">
      <span>RATING</span>
      <strong>{PROFILE.user.rating}</strong>
    </div>

    <div class="stat">
      <span>RANK</span>
      <strong>{PROFILE.user.rank}</strong>
    </div>

    <div class="stat">
      <span>TOPICS</span>
      <strong>{topicEntries.length}</strong>
    </div>

    <div class="stat">
      <span>ANOMALY FLAGS</span>
      <strong>{ANOMALY.summary.total_flags}</strong>
    </div>

  </section>

</div>


<style>
  .page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 24px 80px;
  }

  .hero {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 30px;
    margin-bottom: 30px;
  }

  .eyebrow {
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 2px;
    opacity: 0.65;
    margin-bottom: 8px;
  }

  h1 {
    font-size: 42px;
    margin: 0;
    font-weight: 900;
    letter-spacing: -1px;
  }

  h1 span {
    opacity: 0.45;
  }

  .subtitle {
    opacity: 0.65;
    max-width: 650px;
  }

  .user-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 18px;
    border: 1px solid rgba(128,128,128,.25);
    border-radius: 14px;
  }

  .avatar {
    width: 42px;
    height: 42px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background: rgba(128,128,128,.2);
    font-weight: 900;
  }

  .user-card strong,
  .user-card small {
    display: block;
  }

  .user-card small {
    opacity: .55;
    margin-top: 3px;
  }

  .overview-grid {
    display: grid;
    grid-template-columns: 1fr 1.5fr;
    gap: 20px;
    margin-bottom: 20px;
  }

  .panel {
    border: 1px solid rgba(128,128,128,.22);
    border-radius: 18px;
    padding: 25px;
    margin-bottom: 20px;
    background: rgba(128,128,128,.035);
  }

  .label {
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 1.5px;
    opacity: .6;
  }

  .score {
    font-size: 70px;
    line-height: 1;
    font-weight: 900;
    margin: 20px 0;
  }

  .score span {
    font-size: 20px;
    opacity: .4;
  }

  .overall-card h2 {
    margin-bottom: 5px;
  }

  .overall-card p {
    opacity: .6;
  }

  .dimensions {
    display: grid;
    gap: 14px;
    margin-top: 20px;
  }

  .dimension-top {
    display: flex;
    justify-content: space-between;
    text-transform: capitalize;
    margin-bottom: 6px;
    font-size: 13px;
  }

  .bar {
    height: 7px;
    border-radius: 10px;
    background: rgba(128,128,128,.16);
    overflow: hidden;
  }

  .fill {
    height: 100%;
    border-radius: inherit;
    background: currentColor;
  }

  .feature-header {
    margin: 35px 0 15px;
  }

  .feature-header h2 {
    margin: 0;
    font-size: 25px;
    font-weight: 900;
  }

  .feature-header p:last-child {
    opacity: .6;
  }

  .stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
    margin-top: 25px;
  }

  .stat {
    border: 1px solid rgba(128,128,128,.22);
    border-radius: 14px;
    padding: 18px;
  }

  .stat span {
    display: block;
    font-size: 10px;
    letter-spacing: 1px;
    opacity: .55;
  }

  .stat strong {
    display: block;
    margin-top: 7px;
    font-size: 22px;
  }

  @media (max-width: 800px) {
    .hero {
      flex-direction: column;
      align-items: flex-start;
    }

    .overview-grid {
      grid-template-columns: 1fr;
    }

    .stats {
      grid-template-columns: repeat(2, 1fr);
    }

    h1 {
      font-size: 32px;
    }
  }
</style>