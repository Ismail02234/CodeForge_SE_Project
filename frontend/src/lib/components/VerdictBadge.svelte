<script lang="ts">
  export let verdict = '';
</script>

<span
  class:good={verdict === 'AC' || verdict === 'accepted'}
  class:bad={['WA', 'TLE', 'MLE', 'RE', 'CE', 'wrong_answer', 'rejected', 'error'].includes(
    verdict
  )}
  class="verdict">{verdict}</span
>
