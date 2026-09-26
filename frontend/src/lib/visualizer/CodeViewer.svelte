<script lang="ts">
  export let code: string[] = [];
  export let activeLine = 0;
</script>

<div class="visualizer-code">
  <div class="visualizer-code-head">
    <span>C++ DEMO</span>
    <small>Active line follows the current visual step</small>
  </div>

  <pre>{#each code as line, index}<div class:active={activeLine === index + 1}><span>{index + 1}</span><code>{line || ' '}</code></div>{/each}</pre>
</div>
