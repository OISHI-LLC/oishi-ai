(() => {
  const config = window.OISHI_CHATBOT_CONFIG || {};
  const typingLogo = config.typingLogo || { src: "", srcset: "", sizes: "28px" };
  const form = document.getElementById("chat-form");
  const resetForm = document.getElementById("reset-form");
  const textarea = document.getElementById("chat-message");
  const sendButton = document.getElementById("send-button");
  const messages = document.getElementById("messages");
  const errorBox = document.getElementById("error-box");
  const intro = document.getElementById("intro");
  const suggestionButtons = Array.from(document.querySelectorAll(".suggestion"));

  if (!form || !resetForm || !textarea || !sendButton || !messages || !errorBox) {
    return;
  }

  let inflightController = null;
  let stickToBottom = true;

  const escapeHtml = (value) => String(value)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");

  const formatInlineText = (value) => {
    let html = escapeHtml(value);
    html = html.replace(/`([^`]+)`/g, "<code>$1</code>");
    html = html.replace(/\*\*(.+?)\*\*/g, "<strong>$1</strong>");
    return html;
  };

  const splitTableCells = (line) => {
    let trimmed = line.trim();
    if (trimmed.startsWith("|")) {
      trimmed = trimmed.slice(1);
    }
    if (trimmed.endsWith("|")) {
      trimmed = trimmed.slice(0, -1);
    }
    return trimmed.split("|").map((cell) => cell.trim());
  };

  const isTableRow = (line) => /^\s*\|.*\|\s*$/.test(line);

  const isTableSeparatorLine = (line) => {
    const cells = splitTableCells(line);
    return cells.length > 0 && cells.every((cell) => /^:?-{3,}:?$/.test(cell.replace(/\s+/g, "")));
  };

  const parseUnorderedListLine = (line) => {
    const match = line.match(/^\s*[-*・]\s+(.+)$/);
    return match ? match[1].trim() : null;
  };

  const parseOrderedListLine = (line) => {
    const match = line.match(/^\s*(\d+)[.)]\s+(.+)$/);
    return match ? { index: match[1], text: match[2].trim() } : null;
  };

  const parseHeadingLine = (line) => {
    const match = line.match(/^\s{0,3}(#{1,3})\s+(.+)$/);
    return match ? match[2].trim() : null;
  };

  const renderParagraph = (lines) => `<p>${lines.map((line) => formatInlineText(line)).join("<br>")}</p>`;

  const renderUnorderedList = (lines) => {
    const items = lines
      .map(parseUnorderedListLine)
      .filter((item) => item !== null)
      .map((item) => `<li>${formatInlineText(item)}</li>`)
      .join("");
    return `<ul>${items}</ul>`;
  };

  const renderOrderedList = (lines) => {
    const items = lines
      .map(parseOrderedListLine)
      .filter((item) => item !== null)
      .map((item) => `<li>${formatInlineText(item.text)}</li>`)
      .join("");
    return `<ol>${items}</ol>`;
  };

  const renderTable = (lines) => {
    const rows = lines.map(splitTableCells);
    const headerCells = rows[0] || [];
    const bodyRows = rows.slice(2);
    const headerHtml = headerCells.map((cell) => `<th>${formatInlineText(cell)}</th>`).join("");
    const bodyHtml = bodyRows
      .map((row) => `<tr>${row.map((cell) => `<td>${formatInlineText(cell)}</td>`).join("")}</tr>`)
      .join("");

    return [
      '<div class="assistant-table-wrap">',
      "<table>",
      `<thead><tr>${headerHtml}</tr></thead>`,
      `<tbody>${bodyHtml}</tbody>`,
      "</table>",
      "</div>",
    ].join("");
  };

  const renderRichText = (rawText) => {
    const normalized = rawText.replace(/\r\n/g, "\n").trim();
    if (!normalized) {
      return "";
    }

    const blocks = normalized
      .split(/\n{2,}/)
      .map((block) => block.trim())
      .filter((block) => block !== "");

    return blocks.map((block) => {
      const lines = block
        .split("\n")
        .map((line) => line.trim())
        .filter((line) => line !== "");

      if (lines.length === 0) {
        return "";
      }

      if (lines.length >= 2 && lines.every(isTableRow) && isTableSeparatorLine(lines[1])) {
        return renderTable(lines);
      }

      if (lines.length > 1) {
        const unorderedItems = lines.map(parseUnorderedListLine);
        if (unorderedItems.every((item) => item !== null)) {
          return renderUnorderedList(lines);
        }

        const orderedItems = lines.map(parseOrderedListLine);
        if (orderedItems.every((item) => item !== null)) {
          return renderOrderedList(lines);
        }

        const firstHeading = parseHeadingLine(lines[0]);
        const remainingUnordered = lines.slice(1).map(parseUnorderedListLine);
        if (firstHeading && remainingUnordered.length > 0 && remainingUnordered.every((item) => item !== null)) {
          return `<p class="assistant-heading">${formatInlineText(firstHeading)}</p>${renderUnorderedList(lines.slice(1))}`;
        }

        const remainingOrdered = lines.slice(1).map(parseOrderedListLine);
        if (firstHeading && remainingOrdered.length > 0 && remainingOrdered.every((item) => item !== null)) {
          return `<p class="assistant-heading">${formatInlineText(firstHeading)}</p>${renderOrderedList(lines.slice(1))}`;
        }
      }

      const heading = lines.length === 1 ? parseHeadingLine(lines[0]) : null;
      if (heading) {
        return `<p class="assistant-heading">${formatInlineText(heading)}</p>`;
      }

      return renderParagraph(lines);
    }).join("");
  };

  const formatAssistantBubble = (bubble) => {
    if (!bubble || bubble.classList.contains("is-waiting")) {
      return;
    }

    const rawText = bubble.dataset.rawText !== undefined ? bubble.dataset.rawText : bubble.textContent || "";
    bubble.dataset.rawText = rawText;
    bubble.innerHTML = renderRichText(rawText);
    bubble.classList.add("is-rich");
  };

  const hideIntro = () => {
    if (!intro) {
      return;
    }

    intro.hidden = true;
  };

  const isNearBottom = () => {
    const scrollTop = window.scrollY || document.documentElement.scrollTop || 0;
    const viewportBottom = scrollTop + window.innerHeight;
    const documentBottom = document.documentElement.scrollHeight;
    return viewportBottom >= documentBottom - 160;
  };

  const maybeScrollToBottom = () => {
    if (!stickToBottom) {
      return;
    }

    window.requestAnimationFrame(() => {
      window.scrollTo({ top: document.documentElement.scrollHeight, behavior: "auto" });
    });
  };

  const clearError = () => {
    errorBox.hidden = true;
    errorBox.textContent = "";
  };

  const showError = (message) => {
    errorBox.hidden = false;
    errorBox.textContent = message;
  };

  const takeRenderChunk = (text) => {
    const chars = Array.from(text);
    const chunkSize = chars.length > 120 ? 6 : chars.length > 60 ? 4 : 2;
    return chars.slice(0, chunkSize).join("");
  };

  const setBusy = (busy) => {
    textarea.disabled = busy;
    sendButton.disabled = busy;
    sendButton.textContent = busy ? "生成中..." : "送信";
  };

  const appendUserBubble = (content) => {
    hideIntro();
    messages.classList.add("has-messages");
    const bubble = document.createElement("div");
    bubble.className = "bubble user";
    bubble.textContent = content;
    messages.appendChild(bubble);
  };

  const appendAssistantEntry = () => {
    messages.classList.add("has-messages");

    const entry = document.createElement("article");
    entry.className = "assistant-entry";

    const reasoning = document.createElement("details");
    reasoning.className = "reasoning";
    reasoning.open = false;
    reasoning.hidden = true;

    const summary = document.createElement("summary");
    summary.textContent = "思考";

    const reasoningBody = document.createElement("div");
    reasoningBody.className = "reasoning-body";

    reasoning.append(summary, reasoningBody);

    const answer = document.createElement("div");
    answer.className = "bubble assistant is-waiting";

    const typingIndicator = document.createElement("span");
    typingIndicator.className = "typing-indicator";

    const typingLogoImage = document.createElement("img");
    typingLogoImage.className = "typing-indicator-logo";
    typingLogoImage.src = typingLogo.src;
    typingLogoImage.srcset = typingLogo.srcset;
    typingLogoImage.sizes = typingLogo.sizes;
    typingLogoImage.width = 28;
    typingLogoImage.height = 28;
    typingLogoImage.alt = "";
    typingLogoImage.decoding = "async";
    typingLogoImage.loading = "eager";
    typingLogoImage.setAttribute("aria-hidden", "true");

    typingIndicator.appendChild(typingLogoImage);
    answer.appendChild(typingIndicator);

    entry.append(reasoning, answer);
    messages.appendChild(entry);

    return {
      entry,
      reasoning,
      reasoningBody,
      answer,
      typingIndicator,
      reasoningQueue: "",
      contentQueue: "",
      renderTimer: null,
      answerStarted: false,
      pendingFinalize: false,
    };
  };

  const stopWaitingIndicator = (nodes) => {
    if (!nodes.typingIndicator) {
      return;
    }

    nodes.typingIndicator.remove();
    nodes.typingIndicator = null;
    nodes.answer.classList.remove("is-waiting");
  };

  const finalizeAssistantEntry = (nodes) => {
    stopWaitingIndicator(nodes);
    if (!nodes.reasoningBody.textContent.trim()) {
      nodes.reasoning.hidden = true;
    }
    formatAssistantBubble(nodes.answer);
    nodes.pendingFinalize = false;
  };

  const flushRenderQueue = (nodes) => {
    if (nodes.renderTimer !== null) {
      return;
    }

    const step = () => {
      let didRender = false;

      if (nodes.reasoningQueue) {
        nodes.reasoning.hidden = false;
        const chunk = takeRenderChunk(nodes.reasoningQueue);
        nodes.reasoningQueue = nodes.reasoningQueue.slice(chunk.length);
        nodes.reasoningBody.textContent += chunk;
        didRender = true;
      }

      if (nodes.contentQueue) {
        if (!nodes.answerStarted) {
          stopWaitingIndicator(nodes);
          nodes.answerStarted = true;
        }

        const chunk = takeRenderChunk(nodes.contentQueue);
        nodes.contentQueue = nodes.contentQueue.slice(chunk.length);
        nodes.answer.textContent += chunk;
        didRender = true;
      }

      if (didRender) {
        maybeScrollToBottom();
      }

      if (nodes.reasoningQueue || nodes.contentQueue) {
        nodes.renderTimer = window.setTimeout(step, 26);
        return;
      }

      nodes.renderTimer = null;
      if (nodes.pendingFinalize) {
        finalizeAssistantEntry(nodes);
      }
    };

    step();
  };

  const waitForRenderIdle = (nodes) => new Promise((resolve) => {
    const check = () => {
      if (nodes.renderTimer === null && !nodes.reasoningQueue && !nodes.contentQueue) {
        resolve();
        return;
      }

      window.setTimeout(check, 24);
    };

    check();
  });

  const handleEventBlock = (block, nodes) => {
    const lines = block.split("\n");
    let eventName = "message";
    const dataLines = [];

    for (const rawLine of lines) {
      if (rawLine.startsWith("event:")) {
        eventName = rawLine.slice(6).trim();
        continue;
      }

      if (rawLine.startsWith("data:")) {
        dataLines.push(rawLine.slice(5).trimStart());
      }
    }

    if (dataLines.length === 0) {
      return;
    }

    let payload;
    try {
      payload = JSON.parse(dataLines.join("\n"));
    } catch (error) {
      return;
    }

    if (eventName === "reasoning") {
      if (payload.delta) {
        nodes.reasoningQueue += payload.delta;
        flushRenderQueue(nodes);
      }
      return;
    }

    if (eventName === "content") {
      if (payload.delta) {
        nodes.contentQueue += payload.delta;
        flushRenderQueue(nodes);
      }
      return;
    }

    if (eventName === "error") {
      nodes.reasoningQueue = "";
      nodes.contentQueue = "";
      nodes.pendingFinalize = false;
      if (nodes.renderTimer !== null) {
        window.clearTimeout(nodes.renderTimer);
        nodes.renderTimer = null;
      }
      stopWaitingIndicator(nodes);
      showError(payload.message || "AI応答に失敗しました。");
      return;
    }

    if (eventName === "done") {
      nodes.pendingFinalize = true;
      if (nodes.renderTimer === null && !nodes.reasoningQueue && !nodes.contentQueue) {
        finalizeAssistantEntry(nodes);
      }
    }
  };

  const processStreamBuffer = (buffer, nodes) => {
    let working = buffer.replace(/\r\n/g, "\n");
    let boundary = working.indexOf("\n\n");

    while (boundary !== -1) {
      const block = working.slice(0, boundary);
      working = working.slice(boundary + 2);
      handleEventBlock(block, nodes);
      boundary = working.indexOf("\n\n");
    }

    return working;
  };

  const submitMessage = async (message) => {
    if (inflightController) {
      return;
    }

    if (!message) {
      showError("メッセージを入力してください。");
      textarea.focus();
      return;
    }

    stickToBottom = isNearBottom();
    clearError();
    appendUserBubble(message);
    const assistantNodes = appendAssistantEntry();
    maybeScrollToBottom();

    textarea.value = "";
    setBusy(true);

    const controller = new AbortController();
    inflightController = controller;

    try {
      const body = new URLSearchParams();
      body.set("message", message);

      const response = await fetch(`${window.location.pathname}?stream=1`, {
        method: "POST",
        headers: {
          Accept: "text/event-stream",
        },
        body,
        signal: controller.signal,
      });

      if (!response.ok || !response.body) {
        throw new Error("ストリーミング接続の開始に失敗しました。");
      }

      const reader = response.body.getReader();
      const decoder = new TextDecoder();
      let buffer = "";

      while (true) {
        const { done, value } = await reader.read();
        if (done) {
          break;
        }

        buffer += decoder.decode(value, { stream: true });
        buffer = processStreamBuffer(buffer, assistantNodes);
      }

      buffer += decoder.decode();
      processStreamBuffer(buffer + "\n\n", assistantNodes);
      await waitForRenderIdle(assistantNodes);

      if (!assistantNodes.answer.textContent.trim()) {
        assistantNodes.entry.remove();
        throw new Error("AIから空の応答が返されました。");
      }
    } catch (error) {
      if (error instanceof DOMException && error.name === "AbortError") {
        return;
      }

      if (!assistantNodes.answer.textContent.trim() && !assistantNodes.reasoningBody.textContent.trim()) {
        assistantNodes.entry.remove();
      }

      showError(error instanceof Error ? error.message : "AI応答に失敗しました。");
    } finally {
      inflightController = null;
      setBusy(false);
      textarea.focus();
    }
  };

  window.addEventListener("scroll", () => {
    stickToBottom = isNearBottom();
  }, { passive: true });

  suggestionButtons.forEach((button) => {
    button.addEventListener("click", () => {
      const prompt = (button.dataset.prompt || "").trim();
      if (!prompt || inflightController) {
        return;
      }

      textarea.value = prompt;
      form.requestSubmit();
    });
  });

  resetForm.addEventListener("submit", () => {
    if (inflightController) {
      inflightController.abort();
    }
  });

  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    await submitMessage(textarea.value.trim());
  });

  messages.querySelectorAll(".bubble.assistant").forEach((bubble) => {
    formatAssistantBubble(bubble);
  });

  if (messages.classList.contains("has-messages")) {
    hideIntro();
  }
})();
