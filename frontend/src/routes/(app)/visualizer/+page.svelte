<script lang="ts">
  const groups = [
    {
      name: 'Arrays',
      topics: [
        { title: 'Array Traversal', mark: 'ARR', href: '/visualizer/array-traversal', text: 'Move through an array one index at a time and see how each value is visited.' },
      ],
    },
    {
      name: 'Searching',
      topics: [
        { title: 'Linear Search', mark: 'LS', href: '/visualizer/linear-search', text: 'Scan from left to right until the target is found.' },
        { title: 'Binary Search', mark: 'BS', href: '/visualizer/binary-search', text: 'Watch the sorted search range shrink around the target.' },
      ],
    },
    {
      name: 'Sorting',
      topics: [
        { title: 'Bubble Sort', mark: 'BUB', href: '/visualizer/bubble-sort', text: 'Compare neighboring values and push larger values toward the end.' },
        { title: 'Selection Sort', mark: 'SEL', href: '/visualizer/selection-sort', text: 'Find the smallest remaining value and place it in position.' },
        { title: 'Insertion Sort', mark: 'INS', href: '/visualizer/insertion-sort', text: 'Grow a sorted section by inserting one value at a time.' },
      ],
    },
    {
      name: 'Linear Structures',
      topics: [
        { title: 'Stack', mark: 'LIFO', href: '/visualizer/stack', text: 'Push and pop values while seeing Last In, First Out behavior.' },
        { title: 'Queue', mark: 'FIFO', href: '/visualizer/queue', text: 'Enqueue and dequeue values while tracking front and rear.' },
        { title: 'Singly Linked List', mark: 'NODE', href: '/visualizer/linked-list', text: 'Insert, delete and search through connected nodes.' },
      ],
    },
    {
      name: 'Trees',
      topics: [
        { title: 'BST Traversal', mark: 'BST', href: '/visualizer/bst-traversal', text: 'Build a binary search tree and animate three classic traversal orders.' },
      ],
    },
  ];
</script>

<svelte:head>
  <title>DSA Visualizer - CodeForge</title>
</svelte:head>

<div class="visualizer-page">
  <section class="panel visualizer-hub-hero">
    <span class="eyebrow">INTERACTIVE DSA LAB</span>
    <h1>DSA Visualizer</h1>
    <p>
      Enter your own values, control every step and connect the animation with the C++ code running underneath it.
    </p>
  </section>

  {#each groups as group}
    <section class="visualizer-category">
      <div class="visualizer-category-head">
        <div>
          <span class="eyebrow">TOPIC GROUP</span>
          <h2>{group.name}</h2>
        </div>
        <span class="muted tiny">{group.topics.length} visualizer{group.topics.length === 1 ? '' : 's'}</span>
      </div>

      <div class="visualizer-topic-grid">
        {#each group.topics as topic}
          <a class="visualizer-topic-card" href={topic.href}>
            <span class="topic-mark">{topic.mark}</span>
            <h3>{topic.title}</h3>
            <p>{topic.text}</p>
            <small>OPEN VISUALIZER -></small>
          </a>
        {/each}
      </div>
    </section>
  {/each}
</div>
