<script lang="ts">
  import { onMount, tick } from 'svelte';
  import { goto } from '$app/navigation';
  import { page } from '$app/stores';
  import { api } from '$lib/api';
  import { auth } from '$lib/stores/auth';

  type AssistantAction = {
    type: 'navigate';
    path: string;
    label: string;
    auto_execute: boolean;
  };

  type ChatMessage = {
    id: string;
    role: 'user' | 'assistant';
    content: string;
    source?: 'local' | 'ai';
    model?: string | null;
    action?: AssistantAction | null;
  };

  type AssistantResponse = {
    reply: string;
    action: AssistantAction | null;
    suggestions: string[];
    source: 'local' | 'ai';
    model?: string | null;
  };

  type PanelMode = 'normal' | 'minimized' | 'maximized';

  let open = false;
  let panelMode: PanelMode = 'normal';
  let busy = false;
  let input = '';
  let messages: ChatMessage[] = [];
  let suggestions = ['Open Ghost Race', 'What should I practice?', 'Open SQL Battle'];
  let inputElement: HTMLTextAreaElement | null = null;
  let messageList: HTMLDivElement | null = null;

  const intro: ChatMessage = {
    id: 'intro',
    role: 'assistant',
    content:
      'CodeForge Copilot is ready. I can navigate the site, search CodeForge, open profiles/problems/contests, compare rivals, and inspect your practice or performance data.',
    source: 'local',
  };

  function storageKey(): string {
    return `codeforge-ai-assistant:${$auth.user?.id ?? 'guest'}`;
  }

  function messageId(role: string): string {
    return `${role}-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
  }

  function saveConversation(): void {
    if (typeof sessionStorage === 'undefined') return;

    try {
      sessionStorage.setItem(
        storageKey(),
        JSON.stringify({ messages: messages.slice(-30), suggestions })
      );
    } catch {
      // Conversation persistence is optional; the assistant still works without it.
    }
  }

  function restoreConversation(): void {
    if (typeof sessionStorage === 'undefined') return;

    try {
      const raw = sessionStorage.getItem(storageKey());
      if (!raw) return;

      const parsed = JSON.parse(raw);
      if (Array.isArray(parsed?.messages) && parsed.messages.length > 0) {
        messages = parsed.messages.filter(
          (item: any) =>
            item &&
            (item.role === 'user' || item.role === 'assistant') &&
            typeof item.content === 'string'
        );
      }
      if (Array.isArray(parsed?.suggestions)) {
        suggestions = parsed.suggestions
          .filter((item: any) => typeof item === 'string')
          .slice(0, 3);
      }
    } catch {
      // Ignore stale/corrupt session data.
    }
  }

  async function scrollToLatest(): Promise<void> {
    await tick();
    if (messageList) {
      messageList.scrollTop = messageList.scrollHeight;
    }
  }

  async function toggle(): Promise<void> {
    if (open) {
      closeAssistant();
      return;
    }

    open = true;
    panelMode = 'normal';
    await scrollToLatest();
    await tick();
    inputElement?.focus();
  }

  async function toggleMinimize(): Promise<void> {
    panelMode = panelMode === 'minimized' ? 'normal' : 'minimized';

    if (panelMode !== 'minimized') {
      await scrollToLatest();
      await tick();
      inputElement?.focus();
    }
  }

  async function toggleMaximize(): Promise<void> {
    panelMode = panelMode === 'maximized' ? 'normal' : 'maximized';
    await scrollToLatest();
    await tick();
    inputElement?.focus();
  }

  function closeAssistant(): void {
    open = false;
    panelMode = 'normal';
  }

  function safeInternalPath(path: string): boolean {
    return path.startsWith('/') && !path.startsWith('//') && !path.includes('\\');
  }

  async function executeAction(action: AssistantAction | null | undefined): Promise<void> {
    if (!action || action.type !== 'navigate' || !safeInternalPath(action.path)) return;
    await goto(action.path);
  }

  async function send(value = input): Promise<void> {
    const text = value.trim();
    if (!text || busy) return;

    const history = messages
      .slice(-8)
      .map((message) => ({ role: message.role, content: message.content.slice(0, 3000) }));

    messages = [...messages, { id: messageId('user'), role: 'user', content: text }];
    input = '';
    busy = true;
    saveConversation();
    await scrollToLatest();

    try {
      const data = await api.post<AssistantResponse>('/api/assistant/chat', {
        message: text,
        current_path: `${$page.url.pathname}${$page.url.search}`,
        history,
      });

      messages = [
        ...messages,
        {
          id: messageId('assistant'),
          role: 'assistant',
          content: data.reply,
          source: data.source,
          model: data.model,
          action: data.action,
        },
      ];
      suggestions = Array.isArray(data.suggestions) ? data.suggestions.slice(0, 3) : [];
      saveConversation();
      await scrollToLatest();

      if (data.action?.auto_execute) {
        await new Promise((resolve) => window.setTimeout(resolve, 350));
        await executeAction(data.action);
      }
    } catch (error: any) {
      messages = [
        ...messages,
        {
          id: messageId('assistant-error'),
          role: 'assistant',
          content:
            error?.message ||
            'I could not contact the CodeForge assistant service. The rest of CodeForge is unaffected.',
          source: 'local',
        },
      ];
      saveConversation();
      await scrollToLatest();
    } finally {
      busy = false;
      await tick();
      inputElement?.focus();
    }
  }

  function handleInputKeydown(event: KeyboardEvent): void {
    if (event.key === 'Enter' && !event.shiftKey) {
      event.preventDefault();
      void send();
    }
  }

  function clearConversation(): void {
    messages = [intro];
    suggestions = ['Open Ghost Race', 'What should I practice?', 'Open SQL Battle'];
    saveConversation();
  }

  function handleWindowKeydown(event: KeyboardEvent): void {
    if (event.altKey && event.key.toLowerCase() === 'a') {
      event.preventDefault();
      void toggle();
      return;
    }

    if (event.key === 'Escape' && open) {
      closeAssistant();
    }
  }

  onMount(() => {
    messages = [intro];
    restoreConversation();
  });
</script>

<svelte:window on:keydown={handleWindowKeydown} />

<div class="ai-assistant" class:open class:maximized={panelMode === 'maximized'}>
  {#if open}
    <section
      class="assistant-panel"
      class:minimized={panelMode === 'minimized'}
      class:maximized={panelMode === 'maximized'}
      role="dialog"
      aria-label="CodeForge Copilot"
    >
      <header class="assistant-head">
        <div class="assistant-title">
          <span class="assistant-mark" aria-hidden="true">AI</span>
          <div>
            <strong>CodeForge Copilot</strong>
            <small><i></i> NAVIGATION + ASSISTANCE</small>
          </div>
        </div>
        <div class="assistant-head-actions" aria-label="Copilot window controls">
          <button class="clear-control" type="button" on:click={clearConversation} title="Clear conversation"
            >CLR</button
          >
          <button
            class="window-control"
            type="button"
            on:click={toggleMinimize}
            aria-label={panelMode === 'minimized' ? 'Restore assistant' : 'Minimize assistant'}
            title={panelMode === 'minimized' ? 'Restore' : 'Minimize'}
            >{panelMode === 'minimized' ? '▢' : '−'}</button
          >
          <button
            class="window-control maximize-control"
            class:active={panelMode === 'maximized'}
            type="button"
            on:click={toggleMaximize}
            aria-label={panelMode === 'maximized' ? 'Restore assistant' : 'Maximize assistant'}
            title={panelMode === 'maximized' ? 'Restore window' : 'Maximize window'}
          >
            <span
              class="maximize-icon"
              class:restore={panelMode === 'maximized'}
              aria-hidden="true"
            ></span>
          </button>
          <button
            class="window-control exit-control"
            type="button"
            on:click={closeAssistant}
            aria-label="Exit assistant"
            title="Exit"
            >×</button
          >
        </div>
      </header>

      <div class="message-list" bind:this={messageList} aria-live="polite">
        {#each messages as message (message.id)}
          <article
            class:assistant-message={message.role === 'assistant'}
            class:user-message={message.role === 'user'}
          >
            <div class="message-meta">
              <span>{message.role === 'assistant' ? 'COPILOT' : 'YOU'}</span>
              {#if message.role === 'assistant' && message.source}
                <em>{message.source === 'ai' ? 'AI' : 'LOCAL'}</em>
              {/if}
            </div>
            <p>{message.content}</p>
            {#if message.action}
              <button
                class="action-button"
                type="button"
                on:click={() => executeAction(message.action)}
              >
                {message.action.label} <span>→</span>
              </button>
            {/if}
          </article>
        {/each}

        {#if busy}
          <article class="assistant-message thinking">
            <div class="message-meta"><span>COPILOT</span></div>
            <div class="typing" aria-label="Copilot is thinking"><i></i><i></i><i></i></div>
          </article>
        {/if}
      </div>

      {#if suggestions.length > 0 && !busy}
        <div class="suggestions" aria-label="Suggested commands">
          {#each suggestions as suggestion}
            <button type="button" on:click={() => send(suggestion)}>{suggestion}</button>
          {/each}
        </div>
      {/if}

      <form class="assistant-input" on:submit|preventDefault={() => send()}>
        <textarea
          bind:this={inputElement}
          bind:value={input}
          maxlength="12000"
          rows="3"
          autocomplete="off"
          placeholder="Ask Copilot to navigate, debug code, explain an algorithm, or review a solution..."
          aria-label="Message CodeForge Copilot"
          disabled={busy}
          on:keydown={handleInputKeydown}
        ></textarea>
        <button type="submit" disabled={busy || !input.trim()} aria-label="Send command">→</button>
      </form>

      <footer>
        <span>ALT + A</span>
        <small>Safe actions only · authenticated session</small>
      </footer>
    </section>
  {/if}

  <button
    class="assistant-fab"
    class:active={open}
    type="button"
    on:click={toggle}
    aria-label={open ? 'Close CodeForge Copilot' : 'Open CodeForge Copilot'}
    aria-expanded={open}
    title="CodeForge Copilot (Alt+A)"
  >
    <span class="fab-core">AI</span>
    <span class="fab-label">COPILOT</span>
  </button>
</div>

<style>
  .ai-assistant {
    position: fixed;
    right: 22px;
    bottom: 22px;
    z-index: 1200;
    pointer-events: none;
  }

  .assistant-fab,
  .assistant-panel {
    pointer-events: auto;
  }

  .assistant-fab {
    display: flex;
    min-width: 132px;
    height: 50px;
    align-items: center;
    justify-content: center;
    gap: 9px;
    padding: 0 14px 0 9px;
    border: 1px solid rgba(255, 105, 70, 0.42);
    border-radius: 14px;
    background:
      linear-gradient(135deg, rgba(255, 49, 29, 0.18), rgba(43, 223, 245, 0.06)),
      rgba(8, 10, 14, 0.97);
    box-shadow:
      0 18px 55px rgba(0, 0, 0, 0.48),
      0 0 30px rgba(255, 50, 29, 0.08);
    color: #fff;
    cursor: pointer;
    transition:
      transform 160ms ease,
      border-color 160ms ease,
      background 160ms ease;
  }

  .assistant-fab:hover,
  .assistant-fab.active {
    transform: translateY(-2px);
    border-color: rgba(255, 105, 70, 0.78);
    background:
      linear-gradient(135deg, rgba(255, 49, 29, 0.25), rgba(43, 223, 245, 0.09)),
      rgba(8, 10, 14, 0.99);
  }

  .fab-core,
  .assistant-mark {
    display: grid;
    place-items: center;
    font-family: 'JetBrains Mono', monospace;
    font-weight: 900;
    color: #fff;
    background: linear-gradient(135deg, var(--red), var(--orange));
    box-shadow: 0 0 18px rgba(255, 70, 35, 0.25);
  }

  .fab-core {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    font-size: 11px;
  }

  .fab-label {
    font:
      800 10px 'JetBrains Mono',
      monospace;
    letter-spacing: 0.13em;
  }

  .assistant-panel {
    position: absolute;
    right: 0;
    bottom: 62px;
    display: grid;
    width: min(410px, calc(100vw - 28px));
    height: min(650px, calc(100vh - 120px));
    grid-template-rows: auto minmax(0, 1fr) auto auto auto;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.11);
    border-radius: 17px;
    background:
      radial-gradient(circle at 88% 0%, rgba(43, 223, 245, 0.07), transparent 28%),
      radial-gradient(circle at 8% 0%, rgba(255, 50, 29, 0.1), transparent 34%),
      rgba(7, 9, 12, 0.985);
    box-shadow: 0 28px 85px rgba(0, 0, 0, 0.62);
    backdrop-filter: blur(18px);
  }

  .assistant-panel.maximized {
    position: fixed;
    top: 50%;
    left: 50%;
    right: auto;
    bottom: auto;
    width: min(1120px, calc(100vw - 44px));
    height: min(820px, calc(100vh - 44px));
    max-width: none;
    max-height: none;
    transform: translate(-50%, -50%);
    grid-template-rows: auto minmax(0, 1fr) auto auto auto;
    border-radius: 20px;
    box-shadow:
      0 34px 110px rgba(0, 0, 0, 0.72),
      0 0 0 1px rgba(255, 255, 255, 0.025);
  }

  .ai-assistant.maximized .assistant-fab {
    display: none;
  }

  .assistant-panel.maximized .assistant-head {
    padding: 15px 18px;
  }

  .assistant-panel.maximized .message-list {
    width: min(100%, 980px);
    margin: 0 auto;
    padding: 24px 28px 18px;
  }

  .assistant-panel.maximized article {
    max-width: min(76%, 780px);
    padding: 12px 14px;
  }

  .assistant-panel.maximized article p {
    font-size: 13px;
    line-height: 1.62;
  }

  .assistant-panel.maximized .suggestions {
    width: min(100%, 980px);
    margin: 0 auto;
    padding: 0 28px 12px;
  }

  .assistant-panel.maximized .assistant-input {
    width: min(calc(100% - 48px), 960px);
    margin: 0 auto 8px;
    padding: 10px;
    border: 1px solid rgba(255, 255, 255, 0.075);
    border-radius: 14px;
    background: rgba(0, 0, 0, 0.2);
  }

  .assistant-panel.maximized .assistant-input textarea {
    min-height: 86px;
    max-height: 240px;
  }

  .assistant-panel.maximized footer {
    width: min(calc(100% - 48px), 960px);
    margin: 0 auto;
    padding: 0 2px 14px;
  }

  .assistant-panel.minimized {
    width: min(350px, calc(100vw - 28px));
    height: auto;
    grid-template-rows: auto;
  }

  .assistant-panel.minimized > :not(.assistant-head) {
    display: none;
  }

  .assistant-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 14px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(255, 255, 255, 0.018);
  }

  .assistant-title {
    display: flex;
    min-width: 0;
    align-items: center;
    gap: 10px;
  }

  .assistant-mark {
    width: 38px;
    height: 38px;
    flex: 0 0 auto;
    border-radius: 11px;
    font-size: 11px;
  }

  .assistant-title strong {
    display: block;
    font-size: 14px;
    letter-spacing: -0.01em;
  }

  .assistant-title small {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 3px;
    color: #6f7882;
    font-size: 8px;
    letter-spacing: 0.09em;
  }

  .assistant-title small i {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--green);
    box-shadow: 0 0 9px var(--green);
  }

  .assistant-head-actions {
    display: flex;
    gap: 6px;
  }

  .assistant-head-actions button {
    min-width: 30px;
    height: 30px;
    padding: 0 8px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.025);
    color: #8c949e;
    font:
      700 9px 'JetBrains Mono',
      monospace;
    cursor: pointer;
  }

  .assistant-head-actions .window-control {
    width: 30px;
    min-width: 30px;
    padding: 0;
    font-family: inherit;
    font-size: 16px;
    line-height: 1;
  }

  .assistant-head-actions .clear-control {
    min-width: 38px;
  }

  .assistant-head-actions .maximize-control {
    display: grid;
    place-items: center;
  }

  .assistant-head-actions .maximize-control.active {
    border-color: rgba(43, 223, 245, 0.26);
    background: rgba(43, 223, 245, 0.07);
    color: #8be9f3;
  }

  .maximize-icon {
    position: relative;
    display: block;
    width: 11px;
    height: 11px;
    border: 1.5px solid currentColor;
    border-radius: 2px;
    box-sizing: border-box;
  }

  .maximize-icon.restore {
    width: 10px;
    height: 10px;
    transform: translate(-1px, 1px);
  }

  .maximize-icon.restore::before {
    content: '';
    position: absolute;
    top: -4px;
    right: -4px;
    width: 8px;
    height: 8px;
    border: 1.5px solid currentColor;
    border-radius: 2px;
    background: rgba(7, 9, 12, 0.98);
    box-sizing: border-box;
    z-index: -1;
  }

  .assistant-head-actions .exit-control {
    font-size: 18px;
  }

  .assistant-head-actions .exit-control:hover {
    border-color: rgba(255, 70, 45, 0.5);
    background: rgba(255, 55, 30, 0.1);
    color: #ff7a62;
  }

  .assistant-head-actions button:hover {
    border-color: rgba(255, 105, 70, 0.35);
    color: #fff;
  }

  .message-list {
    overflow-y: auto;
    padding: 14px;
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.12) transparent;
  }

  article {
    max-width: 88%;
    margin-bottom: 12px;
    padding: 10px 11px;
    border: 1px solid rgba(255, 255, 255, 0.07);
    border-radius: 11px;
  }

  .assistant-message {
    margin-right: auto;
    background: rgba(255, 255, 255, 0.025);
  }

  .user-message {
    margin-left: auto;
    border-color: rgba(255, 96, 62, 0.18);
    background: rgba(255, 74, 42, 0.07);
  }

  .message-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 6px;
    color: #69717b;
    font:
      700 8px 'JetBrains Mono',
      monospace;
    letter-spacing: 0.1em;
  }

  .message-meta em {
    padding: 2px 5px;
    border: 1px solid rgba(43, 223, 245, 0.16);
    border-radius: 999px;
    color: #65d9e8;
    font-style: normal;
    letter-spacing: 0.07em;
  }

  article p {
    margin: 0;
    color: #d7dbe0;
    font-size: 12px;
    line-height: 1.55;
    white-space: pre-wrap;
  }

  .user-message p {
    color: #f2f4f6;
  }

  .action-button {
    display: flex;
    width: 100%;
    min-height: 34px;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-top: 9px;
    padding: 0 10px;
    border: 1px solid rgba(255, 96, 62, 0.28);
    border-radius: 7px;
    background: rgba(255, 84, 48, 0.06);
    color: #ff7a5d;
    font:
      700 9px 'JetBrains Mono',
      monospace;
    cursor: pointer;
  }

  .action-button:hover {
    border-color: rgba(255, 96, 62, 0.55);
    background: rgba(255, 84, 48, 0.1);
  }

  .thinking {
    width: 72px;
  }

  .typing {
    display: flex;
    gap: 4px;
    padding: 4px 1px 2px;
  }

  .typing i {
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: #7b838d;
    animation: pulse 950ms ease-in-out infinite;
  }

  .typing i:nth-child(2) {
    animation-delay: 120ms;
  }

  .typing i:nth-child(3) {
    animation-delay: 240ms;
  }

  .suggestions {
    display: flex;
    gap: 6px;
    overflow-x: auto;
    padding: 0 14px 10px;
  }

  .suggestions button {
    flex: 0 0 auto;
    min-height: 29px;
    padding: 0 9px;
    border: 1px solid rgba(255, 255, 255, 0.075);
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.02);
    color: #9299a2;
    font:
      600 8px 'JetBrains Mono',
      monospace;
    cursor: pointer;
  }

  .suggestions button:hover {
    border-color: rgba(43, 223, 245, 0.25);
    color: #dce4e8;
  }

  .assistant-input {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 42px;
    gap: 7px;
    padding: 11px 14px;
    border-top: 1px solid rgba(255, 255, 255, 0.075);
    background: rgba(0, 0, 0, 0.16);
  }

  .assistant-input textarea {
    min-width: 0;
    min-height: 54px;
    max-height: 130px;
    padding: 10px 12px;
    border: 1px solid rgba(255, 255, 255, 0.09);
    border-radius: 9px;
    outline: none;
    background: #090b0f;
    color: #f2f4f5;
    font-size: 12px;
    line-height: 1.45;
    resize: vertical;
    font-family: 'JetBrains Mono', monospace;
  }

  .assistant-input textarea:focus {
    border-color: rgba(255, 96, 62, 0.45);
    box-shadow: 0 0 0 3px rgba(255, 75, 40, 0.05);
  }

  .assistant-input button {
    border: 0;
    border-radius: 9px;
    background: linear-gradient(135deg, var(--red), var(--orange));
    color: #fff;
    font-size: 18px;
    cursor: pointer;
  }

  .assistant-input button:disabled {
    cursor: not-allowed;
    opacity: 0.4;
  }

  footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 0 14px 11px;
    color: #555d67;
  }

  footer span,
  footer small {
    font:
      600 8px 'JetBrains Mono',
      monospace;
    letter-spacing: 0.06em;
  }

  footer span {
    padding: 2px 5px;
    border: 1px solid rgba(255, 255, 255, 0.07);
    border-radius: 4px;
  }

  @keyframes pulse {
    0%,
    100% {
      transform: translateY(0);
      opacity: 0.35;
    }
    50% {
      transform: translateY(-2px);
      opacity: 1;
    }
  }

  @media (max-width: 700px) {
    .ai-assistant {
      right: 12px;
      bottom: 12px;
    }

    .assistant-panel {
      right: 0;
      bottom: 60px;
      width: calc(100vw - 24px);
      height: min(620px, calc(100vh - 90px));
    }

    .assistant-panel.maximized {
      top: 8px;
      left: 8px;
      right: 8px;
      bottom: 8px;
      width: auto;
      height: auto;
      transform: none;
      border-radius: 14px;
    }

    .assistant-panel.maximized .message-list {
      width: 100%;
      padding: 14px 12px 10px;
    }

    .assistant-panel.maximized article {
      max-width: 90%;
      padding: 10px 11px;
    }

    .assistant-panel.maximized .suggestions {
      width: 100%;
      padding: 0 12px 8px;
    }

    .assistant-panel.maximized .assistant-input {
      width: calc(100% - 20px);
      margin: 0 auto 7px;
      padding: 8px;
    }

    .assistant-panel.maximized .assistant-input textarea {
      min-height: 70px;
      max-height: 180px;
    }

    .assistant-panel.maximized footer {
      width: calc(100% - 20px);
      padding: 0 2px 9px;
    }

    .assistant-panel.minimized {
      width: min(330px, calc(100vw - 24px));
      height: auto;
    }

    .assistant-head {
      padding: 11px;
    }

    .assistant-title small {
      display: none;
    }

    .assistant-head-actions {
      gap: 4px;
    }

    .assistant-fab {
      min-width: 50px;
      width: 50px;
      padding: 0;
      border-radius: 14px;
    }

    .fab-label {
      display: none;
    }
  }

  :global(html[data-theme='light']) .assistant-fab {
    border-color: rgba(205, 68, 40, 0.3);
    background:
      linear-gradient(135deg, rgba(255, 49, 29, 0.1), rgba(43, 180, 195, 0.05)),
      rgba(255, 255, 255, 0.98);
    box-shadow:
      0 18px 55px rgba(26, 32, 44, 0.14),
      0 0 25px rgba(255, 50, 29, 0.06);
    color: #15181d;
  }

  :global(html[data-theme='light']) .assistant-fab:hover,
  :global(html[data-theme='light']) .assistant-fab.active {
    background:
      linear-gradient(135deg, rgba(255, 49, 29, 0.14), rgba(43, 180, 195, 0.07)),
      #ffffff;
  }

  :global(html[data-theme='light']) .assistant-panel {
    border-color: rgba(20, 28, 38, 0.14);
    background:
      radial-gradient(circle at 88% 0%, rgba(43, 180, 195, 0.07), transparent 28%),
      radial-gradient(circle at 8% 0%, rgba(255, 50, 29, 0.08), transparent 34%),
      rgba(255, 255, 255, 0.99);
    box-shadow: 0 28px 85px rgba(26, 32, 44, 0.2);
  }

  :global(html[data-theme='light']) .assistant-head {
    border-bottom-color: rgba(20, 28, 38, 0.1);
    background: rgba(16, 24, 32, 0.02);
  }

  :global(html[data-theme='light']) .assistant-title strong {
    color: #171a1f;
  }

  :global(html[data-theme='light']) .assistant-title small,
  :global(html[data-theme='light']) .message-meta,
  :global(html[data-theme='light']) footer {
    color: #68717c;
  }

  :global(html[data-theme='light']) .assistant-head-actions button,
  :global(html[data-theme='light']) .suggestions button {
    border-color: rgba(20, 28, 38, 0.12);
    background: rgba(20, 28, 38, 0.035);
    color: #626c77;
  }

  :global(html[data-theme='light']) article {
    border-color: rgba(20, 28, 38, 0.1);
  }

  :global(html[data-theme='light']) .assistant-message {
    background: #f3f5f7;
  }

  :global(html[data-theme='light']) .user-message {
    border-color: rgba(255, 96, 62, 0.22);
    background: rgba(255, 74, 42, 0.08);
  }

  :global(html[data-theme='light']) article p,
  :global(html[data-theme='light']) .user-message p {
    color: #20252b;
  }

  :global(html[data-theme='light']) .maximize-icon.restore::before {
    background: rgba(255, 255, 255, 0.99);
  }

  :global(html[data-theme='light']) .assistant-panel.maximized .assistant-input {
    border-color: rgba(20, 28, 38, 0.1);
    background: #f7f8fa;
  }

  :global(html[data-theme='light']) .assistant-input {
    border-top-color: rgba(20, 28, 38, 0.1);
    background: #f7f8fa;
  }

  :global(html[data-theme='light']) .assistant-input textarea {
    border-color: rgba(20, 28, 38, 0.14);
    background: #ffffff;
    color: #171a1f;
  }

  :global(html[data-theme='light']) footer span {
    border-color: rgba(20, 28, 38, 0.1);
  }

  @media (prefers-reduced-motion: reduce) {
    .assistant-fab,
    .typing i {
      transition: none;
      animation: none;
    }
  }
</style>
