(function () {
  const rootConfig = window.ETHERR_ASSISTANT_CONFIG || {};
  const routes = Object.assign(
    {
      bootstrap: "/api/assistant/bootstrap.php",
      send: "/api/assistant/send.php",
      restart: "/api/assistant/restart.php",
      startIntake: "/api/assistant/start-intake.php",
      submitIntake: "/api/assistant/submit-intake.php",
    },
    rootConfig.routes || {}
  );

  const texts = {
    hr: {
      launcher: "AI asistent",
      title: "Etherr AI",
      subtitle: "Pitanja, ideje i prvi smjer za projekt.",
      close: "Zatvori",
      restart: "Restart",
      send: "Pošalji",
      thinking: "Razmišljam...",
      error: "Nešto nije prošlo. Pokušajte ponovno ili pošaljite upit kroz kontakt formu.",
      contact: "Kontakt forma",
    },
    en: {
      launcher: "AI assistant",
      title: "Etherr AI",
      subtitle: "Questions, ideas and first direction for your project.",
      close: "Close",
      restart: "Restart",
      send: "Send",
      thinking: "Thinking...",
      error: "Something went wrong. Please try again or use the contact form.",
      contact: "Contact form",
    },
    de: {
      launcher: "KI-Assistent",
      title: "Etherr KI",
      subtitle: "Fragen, Ideen und erste Richtung für Ihr Projekt.",
      close: "Schließen",
      restart: "Restart",
      send: "Senden",
      thinking: "Denke nach...",
      error: "Etwas ist fehlgeschlagen. Bitte versuchen Sie es erneut oder nutzen Sie das Kontaktformular.",
      contact: "Kontaktformular",
    },
  };

  function currentLang() {
    const lang = document.documentElement.lang || window.ETHERR_BOOT_LANG || "hr";
    return Object.prototype.hasOwnProperty.call(texts, lang) ? lang : "hr";
  }

  function t(key) {
    return texts[currentLang()][key] || texts.hr[key] || key;
  }

  function escapeHtml(value) {
    const div = document.createElement("div");
    div.textContent = value || "";
    return div.innerHTML;
  }

  async function postJson(url, payload) {
    const response = await fetch(url, {
      method: "POST",
      credentials: "same-origin",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: JSON.stringify(payload || {}),
    });
    const data = await response.json().catch(() => null);
    if (!response.ok || !data) {
      throw new Error(data?.message || t("error"));
    }
    return data;
  }

  function isExternalUrl(url) {
    try {
      return new URL(url, window.location.origin).origin !== window.location.origin;
    } catch (error) {
      return false;
    }
  }

  function renderMessageActions(item, actions) {
    const cleanActions = Array.isArray(actions)
      ? actions.filter((action) => action && action.label && (action.url || action.kind)).slice(0, 2)
      : [];
    if (!cleanActions.length) {
      return;
    }

    const wrap = document.createElement("div");
    wrap.className = "etherr-ai-message-actions";
    cleanActions.forEach((action) => {
      if (action.kind) {
        const button = document.createElement("button");
        button.className = "etherr-ai-message-action";
        button.type = "button";
        button.textContent = action.label;
        button.dataset.etherrAiActionKind = action.kind;
        wrap.appendChild(button);
        return;
      }
      const link = document.createElement("a");
      link.className = "etherr-ai-message-action";
      link.href = action.url;
      link.textContent = action.label;
      if (isExternalUrl(action.url)) {
        link.target = "_blank";
        link.rel = "noopener noreferrer";
      }
      wrap.appendChild(link);
    });
    item.appendChild(wrap);
  }

  function createMessage(role, text, actionsOrTransient, transient) {
    const item = document.createElement("div");
    item.className = `etherr-ai-message etherr-ai-message-${role}`;
    const actions = Array.isArray(actionsOrTransient) ? actionsOrTransient : [];
    const isTransient = Array.isArray(actionsOrTransient) ? transient : actionsOrTransient;
    if (isTransient) {
      item.classList.add("etherr-ai-message-transient");
    }
    item.innerHTML = `<div class="etherr-ai-message-bubble">${escapeHtml(text)}</div>`;
    renderMessageActions(item, actions);
    return item;
  }

  function typeMessage(container, text, scrollTarget) {
    return new Promise((resolve) => {
      const bubble = container.querySelector(".etherr-ai-message-bubble");
      if (!bubble) { resolve(); return; }
      bubble.textContent = "";
      bubble.classList.add("etherr-ai-typing-cursor");

      let i = 0;
      const len = text.length;
      // ~30ms per char = ~33 chars/sec ≈ 400 wpm (faster than reading speed)
      // Add slight variance for natural feel
      function tick() {
        if (i >= len) {
          bubble.classList.remove("etherr-ai-typing-cursor");
          resolve();
          return;
        }
        // Type 1-3 chars at a time for speed variation
        var chunk = 1;
        if (text[i] === " " || text[i] === "\n") chunk = 1;
        else if (Math.random() > 0.7) chunk = 2;
        else if (Math.random() > 0.9) chunk = 3;
        chunk = Math.min(chunk, len - i);
        bubble.textContent += text.slice(i, i + chunk);
        i += chunk;
        if (scrollTarget) scrollTarget.scrollTop = scrollTarget.scrollHeight;
        // Base delay with variance: 18-38ms per step
        var delay = 22 + Math.random() * 16;
        // Pause slightly longer after punctuation
        if (i > 0 && ".!?,;:".includes(text[i - 1])) delay += 60 + Math.random() * 80;
        // Tiny pause after spaces
        else if (i > 0 && text[i - 1] === " ") delay += 5;
        window.setTimeout(tick, delay);
      }
      tick();
    });
  }

  function createThinkingIndicator() {
    var el = document.createElement("div");
    el.className = "etherr-ai-thinking";
    el.innerHTML = '<div class="etherr-ai-thinking-bubble"><canvas class="etherr-ai-thinking-canvas"></canvas></div>';
    var canvas = el.querySelector("canvas");
    var ctx = canvas.getContext("2d");
    var dpr = window.devicePixelRatio || 1;
    var w = 44, h = 36;
    canvas.width = w * dpr;
    canvas.height = h * dpr;
    canvas.style.width = w + "px";
    canvas.style.height = h + "px";
    ctx.scale(dpr, dpr);

    // 4 nodes in a loose cluster
    var nodes = [
      { x: 10, y: 10, r: 3.2, phase: 0 },
      { x: 34, y: 8, r: 2.6, phase: 1.2 },
      { x: 22, y: 28, r: 3.8, phase: 2.4 },
      { x: 38, y: 26, r: 2.2, phase: 3.6 },
    ];

    var frameId = null;
    var startTime = performance.now();

    function draw() {
      var t = (performance.now() - startTime) / 1000;
      ctx.clearRect(0, 0, w, h);

      // Draw connecting lines that fade in/out
      for (var i = 0; i < nodes.length; i++) {
        for (var j = i + 1; j < nodes.length; j++) {
          var linePhase = Math.sin(t * 1.8 + i * 1.5 + j * 0.9);
          var lineAlpha = Math.max(0, linePhase) * 0.45;
          if (lineAlpha < 0.02) continue;
          ctx.beginPath();
          ctx.moveTo(nodes[i].x, nodes[i].y);
          ctx.lineTo(nodes[j].x, nodes[j].y);
          ctx.strokeStyle = "rgba(25, 183, 177, " + lineAlpha.toFixed(3) + ")";
          ctx.lineWidth = 1;
          ctx.stroke();
        }
      }

      // Draw nodes with breathing glow
      for (var n = 0; n < nodes.length; n++) {
        var nd = nodes[n];
        var breath = 0.5 + 0.5 * Math.sin(t * 2.2 + nd.phase);
        var glowR = nd.r + breath * 4;
        var alpha = 0.12 + breath * 0.18;

        // Glow
        var grad = ctx.createRadialGradient(nd.x, nd.y, nd.r * 0.3, nd.x, nd.y, glowR);
        grad.addColorStop(0, "rgba(25, 183, 177, " + (alpha + 0.15).toFixed(3) + ")");
        grad.addColorStop(1, "rgba(25, 183, 177, 0)");
        ctx.beginPath();
        ctx.arc(nd.x, nd.y, glowR, 0, Math.PI * 2);
        ctx.fillStyle = grad;
        ctx.fill();

        // Core node
        ctx.beginPath();
        ctx.arc(nd.x, nd.y, nd.r * (0.8 + breath * 0.2), 0, Math.PI * 2);
        ctx.fillStyle = "rgba(25, 183, 177, " + (0.55 + breath * 0.4).toFixed(3) + ")";
        ctx.fill();
      }

      frameId = requestAnimationFrame(draw);
    }

    draw();

    el._stopThinking = function () {
      if (frameId) cancelAnimationFrame(frameId);
      frameId = null;
    };

    return el;
  }

  function animatedIconMarkup(extraClass) {
    const className = extraClass ? ` ${extraClass}` : "";
    return `
      <span class="etherr-ai-face-icon${className}" aria-hidden="true">
        <img class="etherr-ai-face-icon-base" src="/assets/assistant/icon/ai.png" alt="" decoding="async" />
        <span class="etherr-ai-face-eyes">
          <span class="etherr-ai-face-eye etherr-ai-face-eye-left"></span>
          <span class="etherr-ai-face-eye etherr-ai-face-eye-right"></span>
        </span>
      </span>
    `;
  }

  function initAssistant() {
    if (document.querySelector("[data-etherr-ai-widget]") || document.body.classList.contains("etherr-ai-admin-page")) {
      return;
    }

    const homeSlot = document.querySelector("[data-etherr-ai-home-slot]");
    const useInlineLauncher = document.body.dataset.page === "home" && homeSlot;
    const mount = document.createElement("div");
    mount.className = `etherr-ai-widget${useInlineLauncher ? " etherr-ai-widget--inline" : ""}`;
    mount.dataset.etherrAiWidget = "1";
    mount.innerHTML = `
      <button class="etherr-ai-launcher" type="button" aria-expanded="false" aria-label="${escapeHtml(t("launcher"))}" title="${escapeHtml(t("launcher"))}">
        ${animatedIconMarkup("etherr-ai-face-icon-launcher")}
      </button>
      <section class="etherr-ai-panel" role="dialog" aria-modal="false" aria-label="${escapeHtml(t("title"))}" hidden>
        <header class="etherr-ai-header">
          <button class="etherr-ai-icon-button etherr-ai-header-left" type="button" data-etherr-ai-restart title="${escapeHtml(t("restart"))}" aria-label="${escapeHtml(t("restart"))}">
            <img src="/assets/assistant/icon/refresh.svg" alt="" width="18" height="18" />
          </button>
          <div class="etherr-ai-header-center">
            ${animatedIconMarkup("etherr-ai-face-icon-header")}
          </div>
          <button class="etherr-ai-icon-button etherr-ai-header-right" type="button" data-etherr-ai-close title="${escapeHtml(t("close"))}" aria-label="${escapeHtml(t("close"))}">×</button>
        </header>
        <div class="etherr-ai-notice" data-etherr-ai-status hidden></div>
        <div class="etherr-ai-messages" data-etherr-ai-messages aria-live="polite"></div>
        <form class="etherr-ai-composer" data-etherr-ai-form>
          <div class="etherr-ai-composer-wrap">
            <textarea data-etherr-ai-input rows="2"></textarea>
            <div class="etherr-ai-scrollbar" data-etherr-ai-scrollbar>
              <div class="etherr-ai-scrollbar-thumb" data-etherr-ai-scrollbar-thumb></div>
            </div>
          </div>
          <button type="submit" data-etherr-ai-send aria-label="${escapeHtml(t("send"))}">
            <span aria-hidden="true">➤</span>
          </button>
        </form>
      </section>
    `;

    if (useInlineLauncher) {
      // Place in the home slot for inline positioning.
      // Widget will be rescued to body before SPA main-swap.
      homeSlot.appendChild(mount);
    } else {
      document.body.appendChild(mount);
    }

    const launcher = mount.querySelector(".etherr-ai-launcher");
    const panel = mount.querySelector(".etherr-ai-panel");

    // Always move the panel to document.body so it escapes any
    // parent stacking-context (isolation, backdrop-filter, etc.)
    document.body.appendChild(panel);

    const closeButton = panel.querySelector("[data-etherr-ai-close]");
    const restartButton = panel.querySelector("[data-etherr-ai-restart]");
    const form = panel.querySelector("[data-etherr-ai-form]");
    const input = panel.querySelector("[data-etherr-ai-input]");
    const sendButton = panel.querySelector("[data-etherr-ai-send]");
    const messages = panel.querySelector("[data-etherr-ai-messages]");
    const status = panel.querySelector("[data-etherr-ai-status]");
    const scrollbarEl = panel.querySelector("[data-etherr-ai-scrollbar]");
    const scrollbarThumb = panel.querySelector("[data-etherr-ai-scrollbar-thumb]");
    function getFaceIcons() {
      return Array.from(document.querySelectorAll(".etherr-ai-face-icon"));
    }
    let bootstrapped = false;
    let loading = false;
    let blinkTimer = null;
    let lastTriggerEl = null;
    let idleEyeTimer = null;
    let idleEyeResetTimer = null;
    let panelAnimation = null;

    function applyEyePosition(clientX, clientY) {
      if (loading) {
        resetEyePosition();
        return;
      }

      getFaceIcons().forEach((icon) => {
        const rect = icon.getBoundingClientRect();
        const centerX = rect.left + rect.width * 0.5;
        const centerY = rect.top + rect.height * 0.59;
        const dx = Math.max(-1, Math.min(1, (clientX - centerX) / Math.max(1, rect.width * 0.82)));
        const dy = Math.max(-1, Math.min(1, (clientY - centerY) / Math.max(1, rect.height * 0.82)));
        const yRange = dy > 0 ? 6.2 : 5;
        const scale = (icon.classList.contains("etherr-ai-face-icon-launcher") || icon.classList.contains("etherr-ai-face-icon-dock")) ? 1 : 0.72;
        icon.style.setProperty("--eye-x", `${(dx * 5 * scale).toFixed(2)}px`);
        icon.style.setProperty("--eye-y", `${(dy * yRange * scale).toFixed(2)}px`);
      });
    }

    function updateEyePosition(clientX, clientY) {
      applyEyePosition(clientX, clientY);
    }

    function resetEyePosition() {
      getFaceIcons().forEach((icon) => {
        icon.style.setProperty("--eye-x", "0px");
        icon.style.setProperty("--eye-y", "0px");
      });
    }

    function setEyeOffset(x, y) {
      getFaceIcons().forEach((icon) => {
        const scale = (icon.classList.contains("etherr-ai-face-icon-launcher") || icon.classList.contains("etherr-ai-face-icon-dock")) ? 1 : 0.72;
        icon.style.setProperty("--eye-x", `${(x * scale).toFixed(2)}px`);
        icon.style.setProperty("--eye-y", `${(y * scale).toFixed(2)}px`);
      });
    }

    function canPlayIdleEyeAnimation() {
      return !loading && panel.hidden && document.activeElement !== input;
    }

    function lookAtInput() {
      if (loading || panel.hidden) {
        return;
      }

      const inputRect = input.getBoundingClientRect();
      applyEyePosition(inputRect.left + inputRect.width * 0.5, inputRect.top + inputRect.height * 0.55);
    }

    function blinkEyes() {
      getFaceIcons().forEach((icon) => {
        icon.classList.add("is-blinking");
        window.setTimeout(() => {
          icon.classList.remove("is-blinking");
        }, 130);
      });
    }

    function scheduleBlink() {
      window.clearTimeout(blinkTimer);
      blinkTimer = window.setTimeout(() => {
        blinkEyes();
        scheduleBlink();
      }, 2600 + Math.random() * 4200);
    }

    function playIdleEyeAnimation() {
      if (!canPlayIdleEyeAnimation()) {
        scheduleIdleEyeAnimation();
        return;
      }

      const animations = [
        [[-2.6, 0], [2.4, 0], [0, 0]],
        [[0, -2.4], [0, 1.4], [0, 0]],
        [[2.2, -1.6], [-2.2, 1.2], [0, 0]],
        [[-2.4, -1.4], [-2.4, 1.3], [2.2, 1.1], [2.2, -1.3], [0, 0]],
      ];
      const frames = animations[Math.floor(Math.random() * animations.length)];

      window.clearTimeout(idleEyeResetTimer);
      frames.forEach((frame, index) => {
        window.setTimeout(() => {
          if (canPlayIdleEyeAnimation()) {
            setEyeOffset(frame[0], frame[1]);
          }
        }, index * 170);
      });

      idleEyeResetTimer = window.setTimeout(() => {
        if (canPlayIdleEyeAnimation()) {
          resetEyePosition();
        }
      }, frames.length * 170 + 120);

      scheduleIdleEyeAnimation();
    }

    function scheduleIdleEyeAnimation() {
      window.clearTimeout(idleEyeTimer);
      idleEyeTimer = window.setTimeout(playIdleEyeAnimation, 3600 + Math.random() * 6200);
    }

    function finishPanelAnimation() {
      if (!panelAnimation) {
        return;
      }

      try {
        panelAnimation.cancel();
      } catch (_error) {}
      panelAnimation = null;
      panel.style.removeProperty("transform");
      panel.style.removeProperty("transform-origin");
      panel.style.removeProperty("opacity");
      panel.style.removeProperty("filter");
    }

    function prefersReducedMotion() {
      return window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    }

    function measureOpenPanelRect() {
      const previousVisibility = panel.style.visibility;
      const previousPointerEvents = panel.style.pointerEvents;

      panel.hidden = false;
      panel.style.visibility = "hidden";
      panel.style.pointerEvents = "none";
      const rect = panel.getBoundingClientRect();
      panel.style.visibility = previousVisibility;
      panel.style.pointerEvents = previousPointerEvents;

      return rect;
    }

    function animatePanel(opening) {
      finishPanelAnimation();

      if (!panel.animate || prefersReducedMotion()) {
        panel.hidden = !opening;
        return;
      }

      // Measure positions — use the element that was clicked, or fall back to launcher
      // When closing, prefer the dock icon so the panel always collapses toward it
      var triggerEl = lastTriggerEl || launcher;
      if (!opening) {
        var dockIcon = document.querySelector("[data-header-ai-dock]");
        if (dockIcon && dockIcon.classList.contains("is-docked") && window.innerWidth > 640) {
          triggerEl = dockIcon;
        }
      }
      const triggerRect = triggerEl.getBoundingClientRect();
      const panelRect = opening ? measureOpenPanelRect() : panel.getBoundingClientRect();

      const launcherCenterX = triggerRect.left + triggerRect.width * 0.5;
      const launcherCenterY = triggerRect.top + triggerRect.height * 0.5;
      const panelCenterX = panelRect.left + panelRect.width * 0.5;
      const panelCenterY = panelRect.top + panelRect.height * 0.5;
      const deltaX = launcherCenterX - panelCenterX;
      const deltaY = launcherCenterY - panelCenterY;

      // Uniform start scale — tiny version next to the icon
      const startScale = Math.max(triggerRect.width / Math.max(1, panelRect.width), 0.06);

      panel.hidden = false;

      const keyframes = opening
        ? [
            {
              transform: `translate(${deltaX}px, ${deltaY}px) scale(${startScale})`,
              opacity: 0,
              borderRadius: "50%",
              offset: 0,
            },
            {
              transform: `translate(${deltaX * 0.6}px, ${deltaY * 0.6}px) scale(${startScale + 0.12})`,
              opacity: 0.5,
              borderRadius: "40px",
              offset: 0.18,
            },
            {
              transform: `translate(${deltaX * 0.15}px, ${deltaY * 0.15}px) scale(0.7)`,
              opacity: 0.92,
              borderRadius: "24px",
              offset: 0.6,
            },
            {
              transform: "translate(0px, 0px) scale(1)",
              opacity: 1,
              borderRadius: "22px",
              offset: 1,
            },
          ]
        : [
            {
              transform: "translate(0px, 0px) scale(1)",
              opacity: 1,
              borderRadius: "22px",
              offset: 0,
            },
            {
              transform: `translate(${deltaX * 0.25}px, ${deltaY * 0.25}px) scale(0.55)`,
              opacity: 0.7,
              borderRadius: "30px",
              offset: 0.45,
            },
            {
              transform: `translate(${deltaX * 0.7}px, ${deltaY * 0.7}px) scale(${startScale + 0.08})`,
              opacity: 0.25,
              borderRadius: "44px",
              offset: 0.8,
            },
            {
              transform: `translate(${deltaX}px, ${deltaY}px) scale(${startScale})`,
              opacity: 0,
              borderRadius: "50%",
              offset: 1,
            },
          ];

      panelAnimation = panel.animate(keyframes, {
        duration: opening ? 620 : 400,
        easing: "ease-in-out",
        fill: "none",
      });

      panelAnimation.addEventListener(
        "finish",
        () => {
          panelAnimation = null;
          panel.style.removeProperty("transform");
          panel.style.removeProperty("transform-origin");
          panel.style.removeProperty("opacity");
          panel.style.removeProperty("filter");
          panel.style.removeProperty("border-radius");
          if (!opening) {
            panel.hidden = true;
          }
        },
        { once: true }
      );
      panelAnimation.addEventListener(
        "cancel",
        () => {
          panelAnimation = null;
          panel.style.removeProperty("transform");
          panel.style.removeProperty("transform-origin");
          panel.style.removeProperty("opacity");
          panel.style.removeProperty("filter");
          panel.style.removeProperty("border-radius");
        },
        { once: true }
      );
    }

    var dockAnimation = null;
    var dockCloseRestoreTimer = null;

    function animateDockToCorner(dockEl) {
      if (dockAnimation) { dockAnimation.cancel(); dockAnimation = null; }
      if (!dockEl.animate) {
        dockEl.style.top = "auto";
        dockEl.style.bottom = "28px";
        dockEl.style.transform = "translateY(0)";
        return;
      }
      var startRect = dockEl.getBoundingClientRect();
      var targetY = window.innerHeight - 28 - dockEl.offsetHeight;
      var deltaY = targetY - startRect.top;
      dockAnimation = dockEl.animate([
        { transform: "translateY(-50%)" },
        { transform: "translateY(calc(-50% + " + deltaY + "px))" }
      ], { duration: 600, easing: "cubic-bezier(0.22, 1, 0.36, 1)", fill: "forwards" });
      dockAnimation.addEventListener("finish", function () {
        dockEl.style.top = "auto";
        dockEl.style.bottom = "28px";
        dockEl.style.transform = "translateY(0)";
        if (dockAnimation) { dockAnimation.cancel(); dockAnimation = null; }
      }, { once: true });
    }

    function animateDockToCenter(dockEl) {
      if (dockAnimation) { dockAnimation.cancel(); dockAnimation = null; }
      if (!dockEl.animate) {
        dockEl.style.top = "50%";
        dockEl.style.bottom = "auto";
        dockEl.style.transform = "translateY(-50%)";
        return;
      }
      var startRect = dockEl.getBoundingClientRect();
      // Reset to center position to measure target
      dockEl.style.top = "50%";
      dockEl.style.bottom = "auto";
      dockEl.style.transform = "translateY(-50%)";
      var targetRect = dockEl.getBoundingClientRect();
      var deltaY = startRect.top - targetRect.top;
      dockAnimation = dockEl.animate([
        { transform: "translateY(calc(-50% + " + deltaY + "px))" },
        { transform: "translateY(-50%)" }
      ], { duration: 600, easing: "cubic-bezier(0.22, 1, 0.36, 1)", fill: "forwards" });
      dockAnimation.addEventListener("finish", function () {
        dockEl.style.top = "50%";
        dockEl.style.bottom = "auto";
        dockEl.style.transform = "translateY(-50%)";
        if (dockAnimation) { dockAnimation.cancel(); dockAnimation = null; }
      }, { once: true });
    }

    function setOpen(isOpen) {
      if (dockCloseRestoreTimer) {
        window.clearTimeout(dockCloseRestoreTimer);
        dockCloseRestoreTimer = null;
      }

      launcher.setAttribute("aria-expanded", isOpen ? "true" : "false");
      mount.classList.toggle("is-open", isOpen);
      resetEyePosition();
      if (isOpen) {
        document.body.classList.add("etherr-ai-panel-open");
      }

      // Animate dock position on desktop (center-right → bottom-right when open)
      var dockEl = document.querySelector("[data-header-ai-dock]");
      var dockController = window.etherrAiDockController || null;
      var openDockState = null;
      if (isOpen && dockEl && dockController && typeof dockController.onPanelOpen === "function") {
        openDockState = dockController.onPanelOpen(dockEl) || null;
      }
      var canAnimateDock = dockEl && !dockEl.classList.contains("header-ai-dock-inline-card") && window.innerWidth > 640;
      if (canAnimateDock) {
        if (isOpen) {
          if (!(openDockState && openDockState.pinnedInlineToFloating)) {
            animateDockToCorner(dockEl);
          }
        }
      }

      if (isOpen) {
        // Set correct height on mobile before animation
        if (window.innerWidth <= 640) {
          panel.style.height = (window.innerHeight - 16) + "px";
          document.body.style.overflow = "hidden";
        } else {
          panel.style.removeProperty("height");
        }
        animatePanel(true);
        bootstrap();
      } else {
        animatePanel(false);
        panel.style.removeProperty("height");
        document.body.style.overflow = "";

        var restoreDockAfterClose = function () {
          dockCloseRestoreTimer = null;
          document.body.classList.remove("etherr-ai-panel-open");
          var closeHandledByController = false;
          if (dockController && typeof dockController.onPanelClose === "function") {
            closeHandledByController = dockController.onPanelClose() === true;
          }
          var canAnimateDockAfterClose =
            dockEl && !dockEl.classList.contains("header-ai-dock-inline-card") && window.innerWidth > 640;
          if (canAnimateDockAfterClose && !closeHandledByController) {
            animateDockToCenter(dockEl);
          }
        };

        if (!panel.animate || prefersReducedMotion()) {
          restoreDockAfterClose();
        } else {
          dockCloseRestoreTimer = window.setTimeout(restoreDockAfterClose, 410);
        }
      }
    }

    function setStatus(message) {
      status.hidden = !message;
      status.textContent = message || "";
    }

    function setLoading(isLoading) {
      loading = isLoading;
      form.classList.toggle("is-loading", isLoading);
      input.disabled = isLoading;
      sendButton.disabled = isLoading;
      restartButton.disabled = isLoading;
      if (isLoading) {
        resetEyePosition();
      } else if (!panel.hidden && document.activeElement === input) {
        window.requestAnimationFrame(lookAtInput);
      }
    }

    function renderMessages(items) {
      messages.innerHTML = "";
      (items || []).forEach((message) => {
        if (!message?.text) {
          return;
        }
        messages.appendChild(createMessage(message.role || "assistant", message.text, message.actions || []));
      });
      messages.scrollTop = messages.scrollHeight;
    }

    async function bootstrap(force) {
      if (bootstrapped && !force) {
        return;
      }
      setLoading(true);
      setStatus("");
      try {
        const payload = await postJson(routes.bootstrap, { locale: currentLang() });
        input.placeholder = payload.input_placeholder || "";
        renderMessages(payload.messages || []);
        bootstrapped = true;
      } catch (error) {
        setStatus(error.message || t("error"));
      } finally {
        setLoading(false);
      }
    }

    async function sendMessage(message) {
      messages.appendChild(createMessage("user", message));
      var thinking = createThinkingIndicator();
      messages.appendChild(thinking);
      messages.scrollTop = messages.scrollHeight;
      setLoading(true);
      setStatus("");
      try {
        const payload = await postJson(routes.send, { locale: currentLang(), message });
        if (thinking._stopThinking) thinking._stopThinking();
        thinking.remove();
        if (payload.assistant_message?.text) {
          const msgEl = createMessage("assistant", payload.assistant_message.text);
          messages.appendChild(msgEl);
          messages.scrollTop = messages.scrollHeight;
          await typeMessage(msgEl, payload.assistant_message.text, messages);
          renderMessageActions(msgEl, payload.assistant_message.actions || []);
          messages.scrollTop = messages.scrollHeight;
        }
      } catch (error) {
        if (thinking._stopThinking) thinking._stopThinking();
        thinking.remove();
        setStatus(error.message || t("error"));
      } finally {
        setLoading(false);
      }
    }

    async function appendAssistantMessage(payload, animate) {
      if (!payload?.assistant_message?.text) {
        return;
      }
      const msgEl = createMessage("assistant", payload.assistant_message.text);
      messages.appendChild(msgEl);
      messages.scrollTop = messages.scrollHeight;
      if (animate) {
        await typeMessage(msgEl, payload.assistant_message.text, messages);
        renderMessageActions(msgEl, payload.assistant_message.actions || []);
      } else {
        renderMessageActions(msgEl, payload.assistant_message.actions || []);
      }
      messages.scrollTop = messages.scrollHeight;
    }

    async function runIntakeAction(kind) {
      if (loading) {
        return;
      }
      const endpoint = kind === "intake_submit" ? routes.submitIntake : routes.startIntake;
      if (!endpoint) {
        return;
      }
      var thinking = createThinkingIndicator();
      messages.appendChild(thinking);
      messages.scrollTop = messages.scrollHeight;
      setLoading(true);
      setStatus("");
      try {
        const payload = await postJson(endpoint, { locale: currentLang() });
        if (thinking._stopThinking) thinking._stopThinking();
        thinking.remove();
        await appendAssistantMessage(payload, true);
      } catch (error) {
        if (thinking._stopThinking) thinking._stopThinking();
        thinking.remove();
        setStatus(error.message || t("error"));
      } finally {
        setLoading(false);
      }
    }

    launcher.addEventListener("click", () => {
      lastTriggerEl = launcher;
      setOpen(panel.hidden);
    });

    messages.addEventListener("click", (event) => {
      const target = event.target instanceof Element ? event.target : null;
      const actionButton = target?.closest("[data-etherr-ai-action-kind]");
      if (actionButton) {
        runIntakeAction(actionButton.dataset.etherrAiActionKind || "");
        return;
      }
      const actionLink = target?.closest(".etherr-ai-message-action");
      if (actionLink) {
        setOpen(false);
      }
    });

    // Header dock button also opens the chatbot (use delegation for SPA navigation)
    document.addEventListener("click", (event) => {
      var dockBtn = event.target.closest("[data-header-ai-dock]");
      if (dockBtn) {
        lastTriggerEl = dockBtn;
        setOpen(panel.hidden);
      }
    });

    closeButton.addEventListener("click", () => setOpen(false));
    restartButton.addEventListener("click", async () => {
      if (loading) {
        return;
      }
      setLoading(true);
      setStatus("");
      try {
        const payload = await postJson(routes.restart, { locale: currentLang() });
        renderMessages(payload.payload?.messages || []);
      } catch (error) {
        setStatus(error.message || t("error"));
      } finally {
        setLoading(false);
      }
    });
    form.addEventListener("submit", (event) => {
      event.preventDefault();
      const message = input.value.trim();
      if (!message || loading) {
        return;
      }
      input.value = "";
      input.style.height = "";
      sendMessage(message);
    });
    input.addEventListener("keydown", (event) => {
      if (event.key === "Enter" && !event.shiftKey && window.innerWidth > 640) {
        event.preventDefault();
        form.requestSubmit();
      }
    });
    input.addEventListener("focus", lookAtInput);
    input.addEventListener("input", () => {
      // Auto-grow textarea up to max-height, then scroll
      input.style.height = "auto";
      var naturalHeight = input.scrollHeight;
      input.style.height = Math.min(naturalHeight, 132) + "px";
      input.style.overflowY = naturalHeight > 132 ? "auto" : "hidden";
      updateCustomScrollbar();
      lookAtInput();
    });
    input.addEventListener("scroll", updateCustomScrollbar);
    input.addEventListener("blur", () => {
      if (!loading) {
        resetEyePosition();
      }
    });

    function updateCustomScrollbar() {
      if (!scrollbarEl || !scrollbarThumb) return;
      var canScroll = input.scrollHeight > input.clientHeight;
      scrollbarEl.classList.toggle("is-visible", canScroll);
      if (!canScroll) return;
      var ratio = input.clientHeight / input.scrollHeight;
      var thumbPct = Math.max(ratio * 100, 15);
      // Cap thumb so it never exceeds 80% of the track
      if (thumbPct > 80) thumbPct = 80;
      var scrollRange = input.scrollHeight - input.clientHeight;
      var scrollPos = scrollRange > 0 ? input.scrollTop / scrollRange : 0;
      var maxTop = 100 - thumbPct;
      scrollbarThumb.style.height = thumbPct + "%";
      scrollbarThumb.style.top = (scrollPos * maxTop) + "%";
    }

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape" && !panel.hidden) {
        setOpen(false);
      }
    });
    document.addEventListener("pointermove", (event) => {
      updateEyePosition(event.clientX, event.clientY);
    }, { passive: true });
    document.addEventListener("pointerleave", resetEyePosition);
    document.addEventListener("click", blinkEyes, { passive: true });

    // Handle mobile viewport resize (Chrome address bar show/hide)
    function updateMobileHeight() {
      if (window.innerWidth > 640 || panel.hidden) return;
      panel.style.height = (window.innerHeight - 16) + "px";
    }
    window.addEventListener("resize", updateMobileHeight, { passive: true });

    scheduleBlink();
    scheduleIdleEyeAnimation();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initAssistant);
  } else {
    initAssistant();
  }
})();
