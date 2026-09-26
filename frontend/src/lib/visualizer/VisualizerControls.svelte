<script lang="ts">
  import { onDestroy } from 'svelte';

  export let currentStep = 0;
  export let totalSteps = 0;
  export let playing = false;
  export let speed = 700;
  export let onPrevious = () => {};
  export let onNext = () => {};
  export let onReset = () => {};
  export let onPlayingChange = (_value: boolean) => {};
  export let onSpeedChange = (_value: number) => {};

  let timer: ReturnType<typeof setTimeout> | null = null;

  function clearTimer() {
    if (timer) clearTimeout(timer);
    timer = null;
  }

  $: {
    playing;
    currentStep;
    totalSteps;
    speed;
    clearTimer();

    if (playing && currentStep < totalSteps - 1) {
      timer = setTimeout(() => onNext(), speed);
    } else if (playing && totalSteps > 0) {
      timer = setTimeout(() => onPlayingChange(false), 0);
    }
  }

  onDestroy(clearTimer);

  function changeSpeed(event: Event) {
    onSpeedChange(Number((event.currentTarget as HTMLInputElement).value));
  }
</script>

<div class="visualizer-controls">
  <div class="visualizer-buttons">
    <button class="btn ghost" on:click={onPrevious} disabled={currentStep <= 0}>Previous</button>
    <button
      class="btn primary"
      on:click={() => onPlayingChange(!playing)}
      disabled={totalSteps <= 1}
    >
      {playing ? 'Pause' : 'Play'}
    </button>
    <button class="btn ghost" on:click={onNext} disabled={currentStep >= totalSteps - 1}>Next</button>
    <button class="btn ghost" on:click={onReset}>Reset</button>
  </div>

  <div class="visualizer-speed">
    <span>Step {totalSteps ? currentStep + 1 : 0} / {totalSteps}</span>
    <label>
      Speed
      <input
        type="range"
        min="220"
        max="1400"
        step="100"
        value={speed}
        on:input={changeSpeed}
        aria-label="Visualization speed"
      />
    </label>
  </div>
</div>
