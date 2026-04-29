(function () {
"use strict";

/* ═══════════════════════════════════════════
   CONFIGURATION — easy to edit
   ═══════════════════════════════════════════ */

var CONFIG = {
  // Colors (Etherr brand)
  accent: "#19b7b1",
  accentRgb: "25, 183, 177",
  accentDeep: "#118a85",
  bgDark: "#0a1a1c",
  textLight: "#e8f4f4",

  // Scoring
  basePoints: 1000,
  lineEfficiencyWeight: 500,   // bonus for optimal lines
  timeBonusMax: 500,           // max time bonus
  timeBonusDecaySeconds: 60,   // time bonus reaches 0 at this many seconds
  extraLinePenalty: 80,        // penalty per extra line

  // Levels: [nodeCount]
  levels: [5, 8, 11, 14, 15],

  // CTA links (easy to swap per QR source)
  ctaVisitUrl: "https://etherr.hr",
  ctaAuditUrl: "https://etherr.hr/#contact",

  // Node visuals
  nodeMinRadius: 16,
  nodeMaxRadius: 26,
  nodeHitExtra: 12,            // extra tap target padding
  lineWidth: 2.5,
  lineGlowWidth: 8,

  // Background nodes
  bgNodeCount: 60,
};

/* ═══════════════════════════════════════════
   TRANSLATIONS
   ═══════════════════════════════════════════ */

var LANG = {
  hr: {
    "hud.level": "Razina",
    "hud.score": "Bodovi",
    "hud.time": "Vrijeme",
    "levelInfo.lines": "linija",
    "intro.title": "Poveži čvorove",
    "intro.body": "Izgradi jednu povezanu mrežu spajanjem svih čvorova. Koristi što manje linija. Čišći sustav — bolji rezultat.",
    "intro.start": "Započni",
    "preLevel.go": "Kreni",
    "preLevel.level": "Razina",
    "preLevel.nodes": "čvorova",
    "preLevel.min": "Minimalno poveznica:",
    "levelComplete.title": "Razina završena",
    "levelComplete.time": "Vrijeme",
    "levelComplete.lines": "Linije",
    "levelComplete.min": "Minimum",
    "levelComplete.points": "Bodovi",
    "levelComplete.next": "Sljedeća razina",
    "gameOver.title": "Mreža kompletna",
    "gameOver.totalScore": "Ukupni bodovi",
    "gameOver.totalTime": "Ukupno vrijeme",
    "gameOver.totalLines": "Ukupno linija",
    "gameOver.tagline": "Čisti sustavi rade bolje.",
    "gameOver.body": "Etherr gradi web stranice, automatizacije, digitalna iskustva i povezane sustave.",
    "gameOver.cta": "Posjeti Etherr",
    "gameOver.ctaAudit": "Zatraži besplatni mini audit",
    "gameOver.restart": "Igraj ponovo",
  },
  en: {
    "hud.level": "Level",
    "hud.score": "Score",
    "hud.time": "Time",
    "levelInfo.lines": "lines",
    "intro.title": "Connect the Nodes",
    "intro.body": "Build one complete network by connecting all nodes. Use as few lines as possible. The cleaner the system, the better the score.",
    "intro.start": "Start",
    "preLevel.go": "Go",
    "preLevel.level": "Level",
    "preLevel.nodes": "nodes",
    "preLevel.min": "Minimum connections:",
    "levelComplete.title": "Level Complete",
    "levelComplete.time": "Time",
    "levelComplete.lines": "Lines",
    "levelComplete.min": "Minimum",
    "levelComplete.points": "Points",
    "levelComplete.next": "Next Level",
    "gameOver.title": "Network Complete",
    "gameOver.totalScore": "Total Score",
    "gameOver.totalTime": "Total Time",
    "gameOver.totalLines": "Total Lines",
    "gameOver.tagline": "Clean systems work better.",
    "gameOver.body": "Etherr builds websites, automations, digital experiences, and connected systems.",
    "gameOver.cta": "Visit Etherr",
    "gameOver.ctaAudit": "Request a free mini audit",
    "gameOver.restart": "Play Again",
  },
  de: {
    "hud.level": "Level",
    "hud.score": "Punkte",
    "hud.time": "Zeit",
    "levelInfo.lines": "Linien",
    "intro.title": "Verbinde die Knoten",
    "intro.body": "Baue ein vollständiges Netzwerk, indem du alle Knoten verbindest. Verwende so wenige Linien wie möglich. Je sauberer das System, desto besser das Ergebnis.",
    "intro.start": "Starten",
    "preLevel.go": "Los",
    "preLevel.level": "Level",
    "preLevel.nodes": "Knoten",
    "preLevel.min": "Minimale Verbindungen:",
    "levelComplete.title": "Level abgeschlossen",
    "levelComplete.time": "Zeit",
    "levelComplete.lines": "Linien",
    "levelComplete.min": "Minimum",
    "levelComplete.points": "Punkte",
    "levelComplete.next": "Nächstes Level",
    "gameOver.title": "Netzwerk komplett",
    "gameOver.totalScore": "Gesamtpunktzahl",
    "gameOver.totalTime": "Gesamtzeit",
    "gameOver.totalLines": "Gesamtlinien",
    "gameOver.tagline": "Saubere Systeme funktionieren besser.",
    "gameOver.body": "Etherr entwickelt Websites, Automatisierungen, digitale Erlebnisse und vernetzte Systeme.",
    "gameOver.cta": "Etherr besuchen",
    "gameOver.ctaAudit": "Kostenloses Mini-Audit anfordern",
    "gameOver.restart": "Nochmal spielen",
  },
};

/* ═══════════════════════════════════════════
   URL PARAMS & LANGUAGE DETECTION
   ═══════════════════════════════════════════ */

var urlParams = new URLSearchParams(window.location.search);
var trackingSource = urlParams.get("src") || "";

function detectLanguage() {
  // 1. URL param
  var paramLang = urlParams.get("lang");
  if (paramLang && LANG[paramLang]) return paramLang;

  // 2. Etherr site cookie/localStorage
  try {
    var stored = window.localStorage.getItem("etherr-language");
    if (stored && LANG[stored]) return stored;
  } catch (_e) {}

  // 3. Browser language
  var nav = (navigator.language || "").slice(0, 2).toLowerCase();
  if (LANG[nav]) return nav;

  return "hr";
}

var currentLang = detectLanguage();
document.documentElement.lang = currentLang;

function t(key) {
  return (LANG[currentLang] && LANG[currentLang][key]) || (LANG.en && LANG.en[key]) || key;
}

function applyI18n() {
  var els = document.querySelectorAll("[data-i18n]");
  for (var i = 0; i < els.length; i++) {
    els[i].textContent = t(els[i].getAttribute("data-i18n"));
  }
}

/* ═══════════════════════════════════════════
   DOM REFERENCES
   ═══════════════════════════════════════════ */

var dom = {
  bgCanvas: document.getElementById("bgCanvas"),
  gameCanvas: document.getElementById("gameCanvas"),
  confettiCanvas: document.getElementById("confettiCanvas"),
  hud: document.getElementById("hud"),
  hudLevel: document.getElementById("hudLevel"),
  hudScore: document.getElementById("hudScore"),
  hudTime: document.getElementById("hudTime"),
  levelInfo: document.getElementById("levelInfo"),
  lineCount: document.getElementById("lineCount"),
  lineMin: document.getElementById("lineMin"),
  btnReset: document.getElementById("btnReset"),
  overlayIntro: document.getElementById("overlayIntro"),
  btnStart: document.getElementById("btnStart"),
  overlayPreLevel: document.getElementById("overlayPreLevel"),
  preLevelTag: document.getElementById("preLevelTag"),
  preLevelTitle: document.getElementById("preLevelTitle"),
  preLevelMin: document.getElementById("preLevelMin"),
  btnBeginLevel: document.getElementById("btnBeginLevel"),
  overlayLevelComplete: document.getElementById("overlayLevelComplete"),
  levelStats: document.getElementById("levelStats"),
  levelScoreDisplay: document.getElementById("levelScoreDisplay"),
  btnNextLevel: document.getElementById("btnNextLevel"),
  overlayGameOver: document.getElementById("overlayGameOver"),
  gameStats: document.getElementById("gameStats"),
  gameScoreDisplay: document.getElementById("gameScoreDisplay"),
  btnRestart: document.getElementById("btnRestart"),
  ctaVisit: document.getElementById("ctaVisit"),
  ctaAudit: document.getElementById("ctaAudit"),
};

/* ═══════════════════════════════════════════
   GAME STATE
   ═══════════════════════════════════════════ */

var game = {
  currentLevel: 0,
  totalScore: 0,
  totalTime: 0,
  totalLines: 0,
  levelStartTime: 0,
  levelElapsed: 0,
  timerInterval: null,
  globalTimerInterval: null,
  globalStartTime: 0,
  globalElapsed: 0,
  nodes: [],
  edges: [],
  edgeSet: {},
  selectedNode: -1,
  playing: false,
  dpr: 1,
  canvasW: 0,
  canvasH: 0,
};

/* ═══════════════════════════════════════════
   LEVEL LAYOUTS — curated node positions
   ═══════════════════════════════════════════ */

// Positions are in 0-1 normalized space, mapped to game area at render time.
// Each node: { x, y, shape, size }
// shape: "circle" | "square" | "diamond"
// size: relative multiplier (0.7 – 1.3)

var LEVEL_LAYOUTS = [
  // Level 1: 5 nodes — simple pentagon-ish
  [
    { x: 0.50, y: 0.18, shape: "square", size: 1.2 },
    { x: 0.20, y: 0.42, shape: "circle", size: 1.0 },
    { x: 0.80, y: 0.42, shape: "circle", size: 0.9 },
    { x: 0.30, y: 0.78, shape: "diamond", size: 1.1 },
    { x: 0.70, y: 0.78, shape: "square", size: 0.85 },
  ],
  // Level 2: 8 nodes
  [
    { x: 0.50, y: 0.12, shape: "square", size: 1.15 },
    { x: 0.18, y: 0.28, shape: "circle", size: 0.9 },
    { x: 0.82, y: 0.28, shape: "circle", size: 1.0 },
    { x: 0.35, y: 0.48, shape: "diamond", size: 1.1 },
    { x: 0.65, y: 0.48, shape: "square", size: 0.85 },
    { x: 0.15, y: 0.72, shape: "circle", size: 1.0 },
    { x: 0.50, y: 0.72, shape: "square", size: 1.2 },
    { x: 0.85, y: 0.72, shape: "diamond", size: 0.95 },
  ],
  // Level 3: 11 nodes
  [
    { x: 0.30, y: 0.10, shape: "square", size: 1.1 },
    { x: 0.70, y: 0.10, shape: "circle", size: 0.9 },
    { x: 0.12, y: 0.30, shape: "circle", size: 1.0 },
    { x: 0.50, y: 0.25, shape: "diamond", size: 1.2 },
    { x: 0.88, y: 0.30, shape: "square", size: 0.85 },
    { x: 0.25, y: 0.50, shape: "circle", size: 0.95 },
    { x: 0.60, y: 0.48, shape: "square", size: 1.05 },
    { x: 0.15, y: 0.72, shape: "diamond", size: 1.0 },
    { x: 0.42, y: 0.75, shape: "circle", size: 0.9 },
    { x: 0.72, y: 0.70, shape: "square", size: 1.15 },
    { x: 0.90, y: 0.85, shape: "circle", size: 0.8 },
  ],
  // Level 4: 14 nodes
  [
    { x: 0.20, y: 0.08, shape: "square", size: 1.0 },
    { x: 0.50, y: 0.08, shape: "diamond", size: 1.15 },
    { x: 0.80, y: 0.08, shape: "circle", size: 0.9 },
    { x: 0.10, y: 0.28, shape: "circle", size: 0.85 },
    { x: 0.40, y: 0.28, shape: "square", size: 1.1 },
    { x: 0.68, y: 0.30, shape: "circle", size: 1.0 },
    { x: 0.90, y: 0.28, shape: "diamond", size: 0.9 },
    { x: 0.22, y: 0.52, shape: "diamond", size: 1.05 },
    { x: 0.55, y: 0.50, shape: "square", size: 1.2 },
    { x: 0.82, y: 0.52, shape: "circle", size: 0.95 },
    { x: 0.12, y: 0.76, shape: "circle", size: 1.0 },
    { x: 0.38, y: 0.78, shape: "square", size: 0.85 },
    { x: 0.62, y: 0.76, shape: "diamond", size: 1.1 },
    { x: 0.88, y: 0.78, shape: "circle", size: 0.9 },
  ],
  // Level 5: 15 nodes — final
  [
    { x: 0.50, y: 0.06, shape: "square", size: 1.25 },
    { x: 0.22, y: 0.14, shape: "circle", size: 0.9 },
    { x: 0.78, y: 0.14, shape: "diamond", size: 0.95 },
    { x: 0.10, y: 0.32, shape: "circle", size: 1.0 },
    { x: 0.38, y: 0.28, shape: "square", size: 0.85 },
    { x: 0.62, y: 0.28, shape: "circle", size: 1.1 },
    { x: 0.90, y: 0.32, shape: "diamond", size: 1.0 },
    { x: 0.25, y: 0.50, shape: "diamond", size: 1.05 },
    { x: 0.50, y: 0.48, shape: "square", size: 1.2 },
    { x: 0.75, y: 0.50, shape: "circle", size: 0.9 },
    { x: 0.12, y: 0.70, shape: "square", size: 0.9 },
    { x: 0.35, y: 0.72, shape: "circle", size: 1.0 },
    { x: 0.58, y: 0.70, shape: "diamond", size: 0.85 },
    { x: 0.82, y: 0.72, shape: "circle", size: 1.1 },
    { x: 0.50, y: 0.90, shape: "square", size: 1.15 },
  ],
];


/* ═══════════════════════════════════════════
   UTILITY FUNCTIONS
   ═══════════════════════════════════════════ */

function clamp(val, min, max) {
  return val < min ? min : val > max ? max : val;
}

function formatTime(seconds) {
  var m = Math.floor(seconds / 60);
  var s = Math.floor(seconds % 60);
  return (m < 10 ? "0" : "") + m + ":" + (s < 10 ? "0" : "") + s;
}

function edgeKey(a, b) {
  return a < b ? a + "-" + b : b + "-" + a;
}

/* ═══════════════════════════════════════════
   GRAPH CONNECTIVITY (BFS)
   ═══════════════════════════════════════════ */

function isFullyConnected(nodeCount, edges) {
  if (nodeCount <= 1) return true;
  if (edges.length === 0) return false;

  var adj = {};
  for (var i = 0; i < nodeCount; i++) adj[i] = [];
  for (var e = 0; e < edges.length; e++) {
    adj[edges[e][0]].push(edges[e][1]);
    adj[edges[e][1]].push(edges[e][0]);
  }

  var visited = {};
  var queue = [0];
  visited[0] = true;
  var count = 1;

  while (queue.length > 0) {
    var node = queue.shift();
    var neighbors = adj[node];
    for (var n = 0; n < neighbors.length; n++) {
      if (!visited[neighbors[n]]) {
        visited[neighbors[n]] = true;
        count++;
        queue.push(neighbors[n]);
      }
    }
  }

  return count === nodeCount;
}

/* ═══════════════════════════════════════════
   SCORING
   ═══════════════════════════════════════════ */

function calculateScore(usedLines, minLines, timeSeconds) {
  var base = CONFIG.basePoints;

  // Line efficiency: full bonus if optimal, scales down
  var extraLines = Math.max(0, usedLines - minLines);
  var efficiencyRatio = minLines / Math.max(usedLines, 1);
  var lineBonus = Math.round(CONFIG.lineEfficiencyWeight * efficiencyRatio);

  // Time bonus: decays linearly
  var timeFactor = clamp(1 - timeSeconds / CONFIG.timeBonusDecaySeconds, 0, 1);
  var timeBonus = Math.round(CONFIG.timeBonusMax * timeFactor);

  // Penalty for extra lines
  var penalty = extraLines * CONFIG.extraLinePenalty;

  var total = Math.max(0, base + lineBonus + timeBonus - penalty);
  return total;
}

/* ═══════════════════════════════════════════
   CANVAS SETUP & RESIZE
   ═══════════════════════════════════════════ */

function setupCanvas(canvas) {
  var dpr = Math.min(window.devicePixelRatio || 1, 2);
  var w = window.innerWidth;
  var h = window.innerHeight;
  canvas.width = Math.round(w * dpr);
  canvas.height = Math.round(h * dpr);
  canvas.style.width = w + "px";
  canvas.style.height = h + "px";
  var ctx = canvas.getContext("2d");
  ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
  return { ctx: ctx, w: w, h: h, dpr: dpr };
}

function resizeAll() {
  var info = setupCanvas(dom.gameCanvas);
  game.dpr = info.dpr;
  game.canvasW = info.w;
  game.canvasH = info.h;
  game.gameCtx = info.ctx;

  var bgInfo = setupCanvas(dom.bgCanvas);
  bgState.ctx = bgInfo.ctx;
  bgState.w = bgInfo.w;
  bgState.h = bgInfo.h;

  var confInfo = setupCanvas(dom.confettiCanvas);
  confettiState.ctx = confInfo.ctx;
  confettiState.w = confInfo.w;
  confettiState.h = confInfo.h;

  // Remap node positions on resize if a level is active
  if (game.playing && game.currentLevel < LEVEL_LAYOUTS.length) {
    game.nodes = mapNodePositions(LEVEL_LAYOUTS[game.currentLevel]);
    renderGame();
  }
}

/* ═══════════════════════════════════════════
   GAME AREA — padded region for nodes
   ═══════════════════════════════════════════ */

function getGameArea() {
  var w = game.canvasW;
  var h = game.canvasH;
  // Padding: top accounts for HUD, bottom for level info
  var padTop = 70;
  var padBottom = 70;
  var padX = Math.max(30, w * 0.06);
  return {
    x: padX,
    y: padTop,
    w: w - padX * 2,
    h: h - padTop - padBottom,
  };
}

/* ═══════════════════════════════════════════
   NODE POSITION MAPPING
   ═══════════════════════════════════════════ */

function mapNodePositions(layout) {
  var area = getGameArea();
  var nodes = [];
  for (var i = 0; i < layout.length; i++) {
    var def = layout[i];
    var baseR = CONFIG.nodeMinRadius + (CONFIG.nodeMaxRadius - CONFIG.nodeMinRadius) * (def.size - 0.7) / 0.6;
    nodes.push({
      x: area.x + def.x * area.w,
      y: area.y + def.y * area.h,
      shape: def.shape,
      radius: clamp(baseR, CONFIG.nodeMinRadius, CONFIG.nodeMaxRadius),
      pulse: 0,
    });
  }
  return nodes;
}

/* ═══════════════════════════════════════════
   RENDERING — GAME BOARD
   ═══════════════════════════════════════════ */

function renderGame() {
  var ctx = game.gameCtx;
  if (!ctx) return;

  ctx.clearRect(0, 0, game.canvasW, game.canvasH);

  // Draw edges
  for (var e = 0; e < game.edges.length; e++) {
    var edge = game.edges[e];
    var nA = game.nodes[edge[0]];
    var nB = game.nodes[edge[1]];
    drawEdgeLine(ctx, nA, nB, 1.0);
  }

  // Draw line preview if a node is selected
  if (game.selectedNode >= 0 && game.previewPos) {
    var selNode = game.nodes[game.selectedNode];
    ctx.save();
    ctx.globalAlpha = 0.35;
    ctx.strokeStyle = CONFIG.accent;
    ctx.lineWidth = CONFIG.lineWidth;
    ctx.setLineDash([6, 6]);
    ctx.beginPath();
    ctx.moveTo(selNode.x, selNode.y);
    ctx.lineTo(game.previewPos.x, game.previewPos.y);
    ctx.stroke();
    ctx.setLineDash([]);
    ctx.restore();
  }

  // Draw nodes
  for (var n = 0; n < game.nodes.length; n++) {
    var node = game.nodes[n];
    var isSelected = n === game.selectedNode;
    var isHovered = n === game.hoveredNode;
    drawNode(ctx, node, isSelected, isHovered);
  }
}

function drawEdgeLine(ctx, nA, nB, alpha) {
  // Glow
  ctx.save();
  ctx.globalAlpha = alpha * 0.2;
  ctx.strokeStyle = CONFIG.accent;
  ctx.lineWidth = CONFIG.lineGlowWidth;
  ctx.lineCap = "round";
  ctx.beginPath();
  ctx.moveTo(nA.x, nA.y);
  ctx.lineTo(nB.x, nB.y);
  ctx.stroke();

  // Core line
  ctx.globalAlpha = alpha * 0.7;
  ctx.lineWidth = CONFIG.lineWidth;
  ctx.beginPath();
  ctx.moveTo(nA.x, nA.y);
  ctx.lineTo(nB.x, nB.y);
  ctx.stroke();
  ctx.restore();
}

function drawNode(ctx, node, isSelected, isHovered) {
  var r = node.radius;
  var glowR = r * 1.8;
  var pulseExtra = node.pulse * 4;

  // Outer glow
  ctx.save();
  var glowAlpha = isSelected ? 0.3 : isHovered ? 0.18 : 0.08;
  ctx.fillStyle = "rgba(" + CONFIG.accentRgb + ", " + glowAlpha + ")";

  if (node.shape === "square") {
    drawRoundedRect(ctx, node.x - glowR - pulseExtra, node.y - glowR - pulseExtra,
      (glowR + pulseExtra) * 2, (glowR + pulseExtra) * 2, r * 0.4);
    ctx.fill();
  } else if (node.shape === "diamond") {
    ctx.beginPath();
    var dSize = glowR + pulseExtra;
    ctx.moveTo(node.x, node.y - dSize * 1.15);
    ctx.lineTo(node.x + dSize, node.y);
    ctx.lineTo(node.x, node.y + dSize * 1.15);
    ctx.lineTo(node.x - dSize, node.y);
    ctx.closePath();
    ctx.fill();
  } else {
    ctx.beginPath();
    ctx.arc(node.x, node.y, glowR + pulseExtra, 0, Math.PI * 2);
    ctx.fill();
  }

  // Core shape
  var coreAlpha = isSelected ? 1.0 : isHovered ? 0.85 : 0.65;
  ctx.fillStyle = "rgba(" + CONFIG.accentRgb + ", " + coreAlpha + ")";

  if (node.shape === "square") {
    drawRoundedRect(ctx, node.x - r, node.y - r, r * 2, r * 2, r * 0.25);
    ctx.fill();
  } else if (node.shape === "diamond") {
    ctx.beginPath();
    ctx.moveTo(node.x, node.y - r * 1.15);
    ctx.lineTo(node.x + r, node.y);
    ctx.lineTo(node.x, node.y + r * 1.15);
    ctx.lineTo(node.x - r, node.y);
    ctx.closePath();
    ctx.fill();
  } else {
    ctx.beginPath();
    ctx.arc(node.x, node.y, r, 0, Math.PI * 2);
    ctx.fill();
  }

  // Inner bright dot for selected
  if (isSelected) {
    ctx.fillStyle = "#fff";
    ctx.globalAlpha = 0.6;
    ctx.beginPath();
    ctx.arc(node.x, node.y, r * 0.3, 0, Math.PI * 2);
    ctx.fill();
  }

  ctx.restore();
}

function drawRoundedRect(ctx, x, y, w, h, radius) {
  var sr = Math.min(radius, w * 0.5, h * 0.5);
  ctx.beginPath();
  ctx.moveTo(x + sr, y);
  ctx.lineTo(x + w - sr, y);
  ctx.quadraticCurveTo(x + w, y, x + w, y + sr);
  ctx.lineTo(x + w, y + h - sr);
  ctx.quadraticCurveTo(x + w, y + h, x + w - sr, y + h);
  ctx.lineTo(x + sr, y + h);
  ctx.quadraticCurveTo(x, y + h, x, y + h - sr);
  ctx.lineTo(x, y + sr);
  ctx.quadraticCurveTo(x, y, x + sr, y);
  ctx.closePath();
}


/* ═══════════════════════════════════════════
   BACKGROUND NETWORK (decorative)
   ═══════════════════════════════════════════ */

var bgState = {
  ctx: null,
  w: 0,
  h: 0,
  nodes: [],
  frame: null,
  time: 0,
};

function initBgNodes() {
  bgState.nodes = [];
  for (var i = 0; i < CONFIG.bgNodeCount; i++) {
    var isSquare = Math.random() < 0.15;
    bgState.nodes.push({
      u: Math.random(),
      v: Math.random(),
      shape: isSquare ? "square" : "circle",
      radius: isSquare ? 3 + Math.random() * 5 : 0.8 + Math.random() * 3,
      speedX: 0.08 + Math.random() * 0.2,
      speedY: 0.1 + Math.random() * 0.22,
      driftX: 2 + Math.random() * 6,
      driftY: 2 + Math.random() * 6,
      phaseX: Math.random() * Math.PI * 2,
      phaseY: Math.random() * Math.PI * 2,
    });
  }
}

function drawBgFrame(timestamp) {
  if (!bgState.ctx) return;
  var dt = 0.016;
  bgState.time += dt;

  var ctx = bgState.ctx;
  var w = bgState.w;
  var h = bgState.h;
  ctx.clearRect(0, 0, w, h);

  var nodes = bgState.nodes;
  var positions = [];

  for (var i = 0; i < nodes.length; i++) {
    var n = nodes[i];
    var x = n.u * w + Math.sin(bgState.time * n.speedX + n.phaseX) * n.driftX;
    var y = n.v * h + Math.cos(bgState.time * n.speedY + n.phaseY) * n.driftY;
    positions.push({ x: x, y: y });
  }

  // Draw connections
  var maxDist = Math.min(w, h) * 0.15;
  ctx.lineWidth = 0.8;
  for (var a = 0; a < nodes.length; a++) {
    for (var b = a + 1; b < nodes.length; b++) {
      var dx = positions[a].x - positions[b].x;
      var dy = positions[a].y - positions[b].y;
      var dist = Math.sqrt(dx * dx + dy * dy);
      if (dist < maxDist) {
        var alpha = (1 - dist / maxDist) * 0.12;
        ctx.strokeStyle = "rgba(" + CONFIG.accentRgb + ", " + alpha.toFixed(3) + ")";
        ctx.beginPath();
        ctx.moveTo(positions[a].x, positions[a].y);
        ctx.lineTo(positions[b].x, positions[b].y);
        ctx.stroke();
      }
    }
  }

  // Draw nodes
  for (var j = 0; j < nodes.length; j++) {
    var nd = nodes[j];
    var p = positions[j];
    var nodeAlpha = nd.radius > 2.5 ? 0.25 : 0.15;
    ctx.fillStyle = "rgba(" + CONFIG.accentRgb + ", " + nodeAlpha + ")";

    if (nd.shape === "square") {
      var half = nd.radius;
      var cr = nd.radius * 0.25;
      drawRoundedRect(ctx, p.x - half, p.y - half, half * 2, half * 2, cr);
      ctx.fill();
    } else {
      ctx.beginPath();
      ctx.arc(p.x, p.y, nd.radius, 0, Math.PI * 2);
      ctx.fill();
    }
  }

  bgState.frame = requestAnimationFrame(drawBgFrame);
}

function startBg() {
  initBgNodes();
  bgState.frame = requestAnimationFrame(drawBgFrame);
}

function stopBg() {
  if (bgState.frame) {
    cancelAnimationFrame(bgState.frame);
    bgState.frame = null;
  }
}

/* ═══════════════════════════════════════════
   CONFETTI EFFECT
   ═══════════════════════════════════════════ */

var confettiState = {
  ctx: null,
  w: 0,
  h: 0,
  particles: [],
  frame: null,
  active: false,
};

function launchConfetti() {
  var particles = [];
  var count = 50;
  var cx = confettiState.w / 2;
  var cy = confettiState.h * 0.4;

  for (var i = 0; i < count; i++) {
    var angle = (Math.PI * 2 * i) / count + (Math.random() - 0.5) * 0.5;
    var speed = 3 + Math.random() * 5;
    var isSquare = Math.random() < 0.4;
    particles.push({
      x: cx,
      y: cy,
      vx: Math.cos(angle) * speed,
      vy: Math.sin(angle) * speed - 3,
      size: isSquare ? 4 + Math.random() * 6 : 2 + Math.random() * 4,
      shape: isSquare ? "square" : "circle",
      alpha: 0.8 + Math.random() * 0.2,
      decay: 0.012 + Math.random() * 0.008,
      rotation: Math.random() * Math.PI * 2,
      rotSpeed: (Math.random() - 0.5) * 0.15,
      hue: Math.random() < 0.7 ? 0 : 1, // 0 = accent, 1 = white
    });
  }

  confettiState.particles = particles;
  confettiState.active = true;
  if (!confettiState.frame) {
    confettiState.frame = requestAnimationFrame(drawConfetti);
  }
}

function drawConfetti() {
  var ctx = confettiState.ctx;
  if (!ctx) return;

  ctx.clearRect(0, 0, confettiState.w, confettiState.h);

  var alive = false;
  for (var i = 0; i < confettiState.particles.length; i++) {
    var p = confettiState.particles[i];
    if (p.alpha <= 0) continue;
    alive = true;

    p.x += p.vx;
    p.y += p.vy;
    p.vy += 0.12; // gravity
    p.alpha -= p.decay;
    p.rotation += p.rotSpeed;

    if (p.alpha <= 0) continue;

    ctx.save();
    ctx.translate(p.x, p.y);
    ctx.rotate(p.rotation);
    ctx.globalAlpha = Math.max(0, p.alpha);

    if (p.hue === 0) {
      ctx.fillStyle = CONFIG.accent;
    } else {
      ctx.fillStyle = "rgba(255, 255, 255, 0.8)";
    }

    if (p.shape === "square") {
      var half = p.size / 2;
      ctx.fillRect(-half, -half, p.size, p.size);
    } else {
      ctx.beginPath();
      ctx.arc(0, 0, p.size / 2, 0, Math.PI * 2);
      ctx.fill();
    }

    ctx.restore();
  }

  if (alive) {
    confettiState.frame = requestAnimationFrame(drawConfetti);
  } else {
    confettiState.active = false;
    confettiState.frame = null;
    ctx.clearRect(0, 0, confettiState.w, confettiState.h);
  }
}

/* ═══════════════════════════════════════════
   INPUT HANDLING
   ═══════════════════════════════════════════ */

function getCanvasPos(e) {
  var rect = dom.gameCanvas.getBoundingClientRect();
  var clientX, clientY;
  if (e.touches && e.touches.length > 0) {
    clientX = e.touches[0].clientX;
    clientY = e.touches[0].clientY;
  } else {
    clientX = e.clientX;
    clientY = e.clientY;
  }
  return {
    x: clientX - rect.left,
    y: clientY - rect.top,
  };
}

function hitTestNode(pos) {
  for (var i = 0; i < game.nodes.length; i++) {
    var n = game.nodes[i];
    var dx = pos.x - n.x;
    var dy = pos.y - n.y;
    var hitR = n.radius + CONFIG.nodeHitExtra;
    if (dx * dx + dy * dy <= hitR * hitR) {
      return i;
    }
  }
  return -1;
}

function handleTap(pos) {
  if (!game.playing) return;

  var hitIdx = hitTestNode(pos);

  if (hitIdx < 0) {
    // Tapped empty space — deselect
    game.selectedNode = -1;
    renderGame();
    return;
  }

  if (game.selectedNode < 0) {
    // First selection
    game.selectedNode = hitIdx;
    animateNodePulse(hitIdx);
    renderGame();
    return;
  }

  if (game.selectedNode === hitIdx) {
    // Tapped same node — deselect
    game.selectedNode = -1;
    renderGame();
    return;
  }

  // Try to create edge
  var key = edgeKey(game.selectedNode, hitIdx);
  if (game.edgeSet[key]) {
    // Edge already exists — just move selection
    game.selectedNode = hitIdx;
    animateNodePulse(hitIdx);
    renderGame();
    return;
  }

  // Create new edge
  game.edges.push([game.selectedNode, hitIdx]);
  game.edgeSet[key] = true;
  game.selectedNode = -1;
  game.previewPos = null;

  // Update line count display
  dom.lineCount.textContent = game.edges.length;

  // Animate the new edge
  animateNewEdge(game.edges.length - 1);

  // Check completion
  if (isFullyConnected(game.nodes.length, game.edges)) {
    onLevelComplete();
  } else {
    renderGame();
  }
}

function handlePointerMove(pos) {
  if (!game.playing) return;

  var hitIdx = hitTestNode(pos);
  game.hoveredNode = hitIdx;

  if (game.selectedNode >= 0) {
    game.previewPos = pos;
  }

  renderGame();
}

// Mouse events
dom.gameCanvas.addEventListener("click", function (e) {
  handleTap(getCanvasPos(e));
});

dom.gameCanvas.addEventListener("mousemove", function (e) {
  handlePointerMove(getCanvasPos(e));
});

dom.gameCanvas.addEventListener("mouseleave", function () {
  game.hoveredNode = -1;
  game.previewPos = null;
  if (game.playing) renderGame();
});

// Touch events
dom.gameCanvas.addEventListener("touchstart", function (e) {
  e.preventDefault();
  handleTap(getCanvasPos(e));
}, { passive: false });

dom.gameCanvas.addEventListener("touchmove", function (e) {
  e.preventDefault();
  handlePointerMove(getCanvasPos(e));
}, { passive: false });

/* ═══════════════════════════════════════════
   ANIMATIONS
   ═══════════════════════════════════════════ */

function animateNodePulse(idx) {
  var node = game.nodes[idx];
  if (!node) return;
  node.pulse = 1;
  var start = performance.now();
  function tick(now) {
    var elapsed = (now - start) / 300;
    node.pulse = Math.max(0, 1 - elapsed);
    if (game.playing) renderGame();
    if (node.pulse > 0) requestAnimationFrame(tick);
  }
  requestAnimationFrame(tick);
}

function animateNewEdge(edgeIdx) {
  // Simple flash effect — re-render handles it
  renderGame();
}


/* ═══════════════════════════════════════════
   TIMER
   ═══════════════════════════════════════════ */

function startLevelTimer() {
  game.levelStartTime = performance.now();
  game.levelElapsed = 0;
  clearInterval(game.timerInterval);
  game.timerInterval = setInterval(function () {
    game.levelElapsed = (performance.now() - game.levelStartTime) / 1000;
    updateHudTime();
  }, 250);
}

function stopLevelTimer() {
  clearInterval(game.timerInterval);
  game.levelElapsed = (performance.now() - game.levelStartTime) / 1000;
}

function startGlobalTimer() {
  game.globalStartTime = performance.now();
  game.globalElapsed = 0;
  clearInterval(game.globalTimerInterval);
  game.globalTimerInterval = setInterval(function () {
    game.globalElapsed = (performance.now() - game.globalStartTime) / 1000;
  }, 500);
}

function stopGlobalTimer() {
  clearInterval(game.globalTimerInterval);
  game.globalElapsed = (performance.now() - game.globalStartTime) / 1000;
}

function updateHudTime() {
  dom.hudTime.textContent = formatTime(game.globalElapsed);
}

/* ═══════════════════════════════════════════
   OVERLAY MANAGEMENT
   ═══════════════════════════════════════════ */

function showOverlay(id) {
  var el = document.getElementById(id);
  if (el) el.classList.add("active");
}

function hideOverlay(id) {
  var el = document.getElementById(id);
  if (el) el.classList.remove("active");
}

function hideAllOverlays() {
  hideOverlay("overlayIntro");
  hideOverlay("overlayPreLevel");
  hideOverlay("overlayLevelComplete");
  hideOverlay("overlayGameOver");
}

function showHud(visible) {
  dom.hud.classList.toggle("visible", visible);
  dom.levelInfo.classList.toggle("visible", visible);
  dom.btnReset.classList.toggle("visible", visible);
}

/* ═══════════════════════════════════════════
   GAME FLOW
   ═══════════════════════════════════════════ */

function startGame() {
  game.currentLevel = 0;
  game.totalScore = 0;
  game.totalTime = 0;
  game.totalLines = 0;
  dom.hudScore.textContent = "0";
  startGlobalTimer();
  showPreLevel();
}

function showPreLevel() {
  game.playing = false;
  hideAllOverlays();

  var levelIdx = game.currentLevel;
  var nodeCount = CONFIG.levels[levelIdx];
  var minLines = nodeCount - 1;

  dom.preLevelTag.textContent = t("preLevel.level") + " " + (levelIdx + 1);
  dom.preLevelTitle.textContent = nodeCount + " " + t("preLevel.nodes");
  dom.preLevelMin.textContent = t("preLevel.min") + " " + minLines;

  showHud(true);
  dom.hudLevel.textContent = levelIdx + 1;
  updateHudTime();

  showOverlay("overlayPreLevel");
}

function beginLevel() {
  hideAllOverlays();

  var levelIdx = game.currentLevel;
  var layout = LEVEL_LAYOUTS[levelIdx];
  var nodeCount = CONFIG.levels[levelIdx];
  var minLines = nodeCount - 1;

  game.nodes = mapNodePositions(layout);
  game.edges = [];
  game.edgeSet = {};
  game.selectedNode = -1;
  game.hoveredNode = -1;
  game.previewPos = null;
  game.playing = true;

  dom.lineCount.textContent = "0";
  dom.lineMin.textContent = minLines;

  startLevelTimer();
  renderGame();
}

function resetLevel() {
  if (!game.playing) return;
  game.edges = [];
  game.edgeSet = {};
  game.selectedNode = -1;
  game.hoveredNode = -1;
  game.previewPos = null;
  dom.lineCount.textContent = "0";
  startLevelTimer();
  renderGame();
}

function onLevelComplete() {
  game.playing = false;
  stopLevelTimer();

  var nodeCount = CONFIG.levels[game.currentLevel];
  var minLines = nodeCount - 1;
  var usedLines = game.edges.length;
  var timeSeconds = game.levelElapsed;
  var points = calculateScore(usedLines, minLines, timeSeconds);

  game.totalScore += points;
  game.totalTime += timeSeconds;
  game.totalLines += usedLines;

  dom.hudScore.textContent = game.totalScore;

  // Confetti
  launchConfetti();

  // Build stats
  dom.levelStats.innerHTML =
    '<div class="stat-item"><div class="stat-label">' + t("levelComplete.time") + '</div><div class="stat-value">' + formatTime(timeSeconds) + '</div></div>' +
    '<div class="stat-item"><div class="stat-label">' + t("levelComplete.lines") + '</div><div class="stat-value">' + usedLines + '</div></div>' +
    '<div class="stat-item"><div class="stat-label">' + t("levelComplete.min") + '</div><div class="stat-value">' + minLines + '</div></div>' +
    '<div class="stat-item"><div class="stat-label">' + t("levelComplete.points") + '</div><div class="stat-value">' + points + '</div></div>';

  dom.levelScoreDisplay.innerHTML =
    '<div class="score-total">' + game.totalScore + '</div>' +
    '<div class="score-label">' + t("hud.score") + '</div>';

  // Is this the last level?
  var isLast = game.currentLevel >= CONFIG.levels.length - 1;
  dom.btnNextLevel.textContent = isLast ? t("gameOver.title") : t("levelComplete.next");

  showOverlay("overlayLevelComplete");
}

function nextLevel() {
  hideAllOverlays();

  if (game.currentLevel >= CONFIG.levels.length - 1) {
    showGameOver();
    return;
  }

  game.currentLevel++;
  showPreLevel();
}

function showGameOver() {
  game.playing = false;
  stopGlobalTimer();
  hideAllOverlays();

  dom.gameStats.innerHTML =
    '<div class="stat-item"><div class="stat-label">' + t("gameOver.totalTime") + '</div><div class="stat-value">' + formatTime(game.totalTime) + '</div></div>' +
    '<div class="stat-item"><div class="stat-label">' + t("gameOver.totalLines") + '</div><div class="stat-value">' + game.totalLines + '</div></div>';

  dom.gameScoreDisplay.innerHTML =
    '<div class="score-total">' + game.totalScore + '</div>' +
    '<div class="score-label">' + t("gameOver.totalScore") + '</div>';

  // Append tracking source to CTA links if present
  var visitUrl = CONFIG.ctaVisitUrl;
  var auditUrl = CONFIG.ctaAuditUrl;
  if (trackingSource) {
    var sep1 = visitUrl.indexOf("?") >= 0 ? "&" : "?";
    visitUrl += sep1 + "src=" + encodeURIComponent(trackingSource);
    var sep2 = auditUrl.indexOf("?") >= 0 ? "&" : "?";
    auditUrl += sep2 + "src=" + encodeURIComponent(trackingSource);
  }
  dom.ctaVisit.href = visitUrl;
  dom.ctaAudit.href = auditUrl;

  showHud(false);
  showOverlay("overlayGameOver");
}

function restartGame() {
  hideAllOverlays();
  startGame();
}

/* ═══════════════════════════════════════════
   EVENT BINDINGS
   ═══════════════════════════════════════════ */

dom.btnStart.addEventListener("click", function () {
  hideOverlay("overlayIntro");
  startGame();
});

dom.btnBeginLevel.addEventListener("click", function () {
  beginLevel();
});

dom.btnNextLevel.addEventListener("click", function () {
  nextLevel();
});

dom.btnRestart.addEventListener("click", function () {
  restartGame();
});

dom.btnReset.addEventListener("click", function () {
  resetLevel();
});

window.addEventListener("resize", function () {
  resizeAll();
});

/* ═══════════════════════════════════════════
   INIT
   ═══════════════════════════════════════════ */

function init() {
  applyI18n();
  resizeAll();
  startBg();
  showOverlay("overlayIntro");
  showHud(false);
}

init();

})();
