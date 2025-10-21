<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header('Location: ../login.php');
    exit();
}

$lieuxDisponibles = ['Rabat', 'Ouled Saleh'];
$currentLieu = $_GET['lieu'] ?? $lieuxDisponibles[0];
if (!in_array($currentLieu, $lieuxDisponibles, true)) {
    $currentLieu = $lieuxDisponibles[0];
}

$isAdmin = (($_SESSION['role'] ?? '') === 'admin');

$gestionStockUrl = 'dashboard.php?' . http_build_query([
    'lieu' => $currentLieu,
    'section' => 'consulter',
]);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <title>Consultation des emplacements</title>
  <link rel="icon" type="image/svg+xml" href="../assets/images/imalliance-logo.svg" />
  <link rel="stylesheet" href="../assets/css/styles.css" />
  <style>
    html, body {
      height: 100%;
      margin: 0;
      background: #eaf2fb;
      overflow: hidden;
    }

    main.visualisation-3d {
      position: relative;
      min-height: 100%;
    }

    .return-buttons {
      position: fixed;
      top: calc(var(--top-nav-bottom, 96px) + 16px);
      left: 20px;
      display: flex;
      flex-direction: column;
      gap: 10px;
      z-index: 40;
    }

    .return-buttons a {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 10px 16px;
      font: 600 14px/1.2 system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
      color: #0f172a;
      background-color: #f8fafc;
      border: 1px solid #cbd5e1;
      border-radius: 999px;
      text-decoration: none;
      box-shadow: 0 10px 25px rgba(15, 23, 42, 0.1);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .return-buttons a:hover,
    .return-buttons a:focus-visible {
      transform: translateY(-1px);
      box-shadow: 0 12px 30px rgba(15, 23, 42, 0.18);
    }

    .return-buttons a:focus-visible {
      outline: 2px solid #1d4ed8;
      outline-offset: 2px;
    }

    #app {
      position: fixed;
      inset: 0;
    }

    .hint {
      position: fixed;
      left: 12px;
      bottom: 12px;
      padding: 10px 12px;
      font: 14px/1.4 system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
      color: #0f172a;
      background: #ffffffcc;
      border: 1px solid #cbd5e1;
      border-radius: 10px;
      backdrop-filter: blur(3px);
      box-shadow: 0 10px 30px rgba(15, 23, 42, 0.15);
      z-index: 20;
    }

    .hint b {
      font-weight: 700;
    }

    .control-panel {
      position: fixed;
      top: calc(var(--top-nav-bottom, 96px) + 16px);
      right: 20px;
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      gap: 10px;
      z-index: 40;
    }

    .control-panel__status {
      min-width: 240px;
      padding: 10px 12px;
      font: 14px/1.4 system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
      color: #0f172a;
      background: #ffffffcc;
      border: 1px solid #cbd5e1;
      border-radius: 12px;
      backdrop-filter: blur(3px);
      box-shadow: 0 10px 30px rgba(15, 23, 42, 0.15);
      transition: border-color 0.2s ease, background-color 0.2s ease, color 0.2s ease;
    }

    .control-panel__status.status--success {
      border-color: #22c55e;
      background: #dcfce7cc;
      color: #166534;
    }

    .control-panel__status.status--error {
      border-color: #ef4444;
      background: #fee2e2cc;
      color: #991b1b;
    }

    .control-panel__button {
      border: none;
      border-radius: 999px;
      padding: 10px 18px;
      font: 600 14px/1.2 system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
      color: #0f172a;
      background: linear-gradient(135deg, #38bdf8, #2563eb);
      color: #fff;
      box-shadow: 0 10px 25px rgba(37, 99, 235, 0.25);
      cursor: pointer;
      transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
    }

    .control-panel__button:hover,
    .control-panel__button:focus-visible {
      transform: translateY(-1px);
      box-shadow: 0 14px 32px rgba(37, 99, 235, 0.35);
    }

    .control-panel__button:focus-visible {
      outline: 2px solid #1d4ed8;
      outline-offset: 2px;
    }

    .control-panel__button[disabled] {
      cursor: wait;
      opacity: 0.7;
      box-shadow: none;
      transform: none;
    }

    .spot-label {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 2px 6px;
      font: 600 13px/1.2 system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
      color: #0f172a;
      background: rgba(255, 255, 255, 0.92);
      border: 1px solid rgba(148, 163, 184, 0.65);
      border-radius: 999px;
      box-shadow: 0 6px 20px rgba(15, 23, 42, 0.15);
      white-space: nowrap;
      pointer-events: none;
      transform: translate(-50%, -50%);
    }

    .spot-tooltip {
      position: fixed;
      padding: 6px 10px;
      font: 600 13px/1.2 system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
      color: #0f172a;
      background: rgba(248, 250, 252, 0.95);
      border: 1px solid rgba(148, 163, 184, 0.7);
      border-radius: 8px;
      box-shadow: 0 10px 30px rgba(15, 23, 42, 0.18);
      transform: translate(-50%, calc(-100% - 12px));
      pointer-events: none;
      transition: opacity 0.15s ease;
      opacity: 0;
    }

    .spot-tooltip[aria-hidden="true"] {
      opacity: 0;
    }

    .spot-tooltip[aria-hidden="false"] {
      opacity: 1;
    }

    .top-nav {
      position: relative;
      z-index: 30;
    }

    @media (max-width: 640px) {
      .hint {
        left: 50%;
        right: auto;
        transform: translateX(-50%);
        margin-bottom: 8px;
      }

      .control-panel {
        top: auto;
        right: auto;
        left: 50%;
        bottom: 72px;
        transform: translateX(-50%);
        align-items: center;
      }

      .control-panel__status {
        min-width: 0;
        width: min(320px, calc(100vw - 48px));
        text-align: center;
      }
    }
  </style>
  <script type="importmap">
    {
      "imports": {
        "three": "https://unpkg.com/three@0.160.0/build/three.module.js",
        "three/addons/": "https://unpkg.com/three@0.160.0/examples/jsm/"
      }
    }
  </script>
</head>
<body>
<?php $baseUrl = '..'; require __DIR__ . '/../partials/top_nav.php'; ?>
<main class="visualisation-3d" role="main">
  <nav class="return-buttons" aria-label="Navigation retour">
    <a href="<?= htmlspecialchars($gestionStockUrl, ENT_QUOTES) ?>" class="btn-retour">
      ← Retour à la gestion des stocks
    </a>
  </nav>
  <div id="app" aria-label="Visualisation 3D de l'entrepôt"></div>
  <div class="hint"><b>Contrôles :</b> glisser pour orbiter • molette pour zoomer • clic droit pour déplacer<?php if ($isAdmin): ?> • cliquer &amp; glisser un élément pour le repositionner<?php else: ?> • disposition verrouillée (lecture seule)<?php endif; ?></div>
  <div class="control-panel" id="control-panel">
    <div class="control-panel__status" id="layout-status" role="status" aria-live="polite">Chargement de la disposition…</div>
    <?php if ($isAdmin): ?>
      <button type="button" class="control-panel__button" id="save-layout">💾 Sauvegarder la disposition</button>
    <?php endif; ?>
  </div>
</main>

<script type="module">
  import * as THREE from 'three';
  import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
  import { DragControls } from 'three/addons/controls/DragControls.js';
  import { CSS2DRenderer, CSS2DObject } from 'three/addons/renderers/CSS2DRenderer.js';

  const isAdmin = <?= json_encode($isAdmin, JSON_THROW_ON_ERROR) ?>;
  const locationKey = <?= json_encode($currentLieu, JSON_THROW_ON_ERROR) ?>;

  const statusElement = document.getElementById('layout-status');
  const saveButton = document.getElementById('save-layout');

  function setStatus(message, type = 'info') {
    if (!statusElement) {
      return;
    }

    statusElement.textContent = message;
    statusElement.classList.remove('status--success', 'status--error');

    if (type === 'success') {
      statusElement.classList.add('status--success');
    } else if (type === 'error') {
      statusElement.classList.add('status--error');
    }
  }

  const scene = new THREE.Scene();
  scene.background = new THREE.Color(0xf3f6fb);

  const camera = new THREE.PerspectiveCamera(55, innerWidth / innerHeight, 0.1, 100);
  camera.position.set(7, 4.5, 8);

  const renderer = new THREE.WebGLRenderer({ antialias: true });
  renderer.setPixelRatio(window.devicePixelRatio);
  renderer.setSize(innerWidth, innerHeight);
  renderer.shadowMap.enabled = true;
  renderer.domElement.style.touchAction = 'none';

  const appContainer = document.getElementById('app');
  appContainer.appendChild(renderer.domElement);

  const labelRenderer = new CSS2DRenderer();
  labelRenderer.setSize(innerWidth, innerHeight);
  labelRenderer.domElement.style.position = 'fixed';
  labelRenderer.domElement.style.top = '0';
  labelRenderer.domElement.style.left = '0';
  labelRenderer.domElement.style.width = '100%';
  labelRenderer.domElement.style.height = '100%';
  labelRenderer.domElement.style.pointerEvents = 'none';
  appContainer.appendChild(labelRenderer.domElement);

  const topNav = document.querySelector('.top-nav');

  function updateReturnButtonOffset() {
    if (!topNav) {
      return;
    }

    const navBounds = topNav.getBoundingClientRect();
    const offset = Math.max(navBounds.bottom, 0);
    document.documentElement.style.setProperty('--top-nav-bottom', `${offset}px`);
  }

  updateReturnButtonOffset();
  window.addEventListener('resize', updateReturnButtonOffset);

  const controls = new OrbitControls(camera, renderer.domElement);
  controls.target.set(0, 1.3, -2.0);
  controls.enableDamping = true;
  controls.dampingFactor = 0.08;
  controls.update();

  const hemi = new THREE.HemisphereLight(0xffffff, 0xbcc7d6, 0.85);
  scene.add(hemi);

  const dir = new THREE.DirectionalLight(0xffffff, 0.65);
  dir.position.set(6, 8, 5);
  dir.castShadow = true;
  dir.shadow.mapSize.set(2048, 2048);
  scene.add(dir);

  const roomWidth = 6;
  const roomDepth = 5.5;
  const wallHeight = 3.5;
  const wallThickness = 0.2;

  const floorGeo = new THREE.PlaneGeometry(roomWidth, roomDepth);
  const floorMat = new THREE.MeshStandardMaterial({ color: 0xe6eaef, roughness: 0.95, metalness: 0.0 });
  const floor = new THREE.Mesh(floorGeo, floorMat);
  floor.rotation.x = -Math.PI / 2;
  floor.receiveShadow = true;
  scene.add(floor);

  const wallMat = new THREE.MeshStandardMaterial({ color: 0xf5f7fb, side: THREE.DoubleSide });

  const wallNorth = new THREE.Mesh(new THREE.BoxGeometry(roomWidth, wallHeight, wallThickness), wallMat);
  wallNorth.position.set(0, wallHeight / 2, -roomDepth / 2 - wallThickness / 2);
  scene.add(wallNorth);

  const wallWest = new THREE.Mesh(new THREE.BoxGeometry(wallThickness, wallHeight, roomDepth), wallMat);
  wallWest.position.set(-roomWidth / 2 - wallThickness / 2, wallHeight / 2, 0);
  scene.add(wallWest);

  const wallEast = new THREE.Mesh(new THREE.BoxGeometry(wallThickness, wallHeight, roomDepth), wallMat);
  wallEast.position.set(roomWidth / 2 + wallThickness / 2, wallHeight / 2, 0);
  scene.add(wallEast);

  const BLUE = 0x1e3a8a;
  const ORANGE = 0xff6b00;
  const SHELF = 0xf7fafc;

  const spotMeshes = [];

  function createSpot({ size, position, label }) {
    const mesh = new THREE.Mesh(
      new THREE.BoxGeometry(size.x, size.y, size.z),
      new THREE.MeshBasicMaterial({ color: 0x2563eb, transparent: true, opacity: 0 })
    );
    mesh.position.copy(position);
    mesh.material.colorWrite = false;
    mesh.material.depthWrite = false;
    mesh.userData.label = label;
    spotMeshes.push(mesh);

    if (label) {
      const labelElement = document.createElement('span');
      labelElement.className = 'spot-label';
      labelElement.textContent = label;
      const labelObject = new CSS2DObject(labelElement);
      labelObject.position.set(0, size.y / 2 + 0.12, 0);
      mesh.add(labelObject);
    }

    return mesh;
  }

  function createRack({ width = 1.8, depth = 0.6, height = 2.2, levels = 4, rackCode = '' } = {}) {
    const rack = new THREE.Group();
    const uprGeom = new THREE.BoxGeometry(0.08, height, 0.08);
    const uprMat = new THREE.MeshStandardMaterial({ color: BLUE, metalness: 0.2, roughness: 0.6 });

    const uprA = new THREE.Mesh(uprGeom, uprMat);
    const uprB = uprA.clone();
    const uprC = uprA.clone();
    const uprD = uprA.clone();

    uprA.position.set(-width / 2, height / 2, -depth / 2);
    uprB.position.set(width / 2, height / 2, -depth / 2);
    uprC.position.set(-width / 2, height / 2, depth / 2);
    uprD.position.set(width / 2, height / 2, depth / 2);

    rack.add(uprA, uprB, uprC, uprD);

    const beamGeomW = new THREE.BoxGeometry(width, 0.07, 0.07);
    const beamGeomD = new THREE.BoxGeometry(0.07, 0.07, depth);
    const beamMat = new THREE.MeshStandardMaterial({ color: ORANGE, metalness: 0.1, roughness: 0.5 });

    for (let i = 0; i < levels; i++) {
      const y = 0.25 + (i * (height - 0.5)) / (levels - 1);

      const front = new THREE.Mesh(beamGeomW, beamMat);
      front.position.set(0, y, depth / 2);
      const back = new THREE.Mesh(beamGeomW, beamMat);
      back.position.set(0, y, -depth / 2);
      const left = new THREE.Mesh(beamGeomD, beamMat);
      left.position.set(-width / 2, y, 0);
      const right = new THREE.Mesh(beamGeomD, beamMat);
      right.position.set(width / 2, y, 0);

      rack.add(front, back, left, right);

      const shelf = new THREE.Mesh(
        new THREE.BoxGeometry(width - 0.08, 0.04, depth - 0.08),
        new THREE.MeshStandardMaterial({ color: SHELF, roughness: 0.95 })
      );
      shelf.position.set(0, y - 0.05, 0);
      rack.add(shelf);
    }

    const levelLabels = ['A', 'B', 'C', 'D'];
    const levelPositions = Array.from({ length: levels }, (_, index) => 0.25 + (index * (height - 0.5)) / (levels - 1));
    const columnCount = 3;
    const columnWidth = width / columnCount;

    const rackSpots = new THREE.Group();
    const labelsToUse = levelLabels.slice(0, Math.min(levelLabels.length, levelPositions.length));
    labelsToUse.forEach((levelLabel, levelIndex) => {
      const positionIndex = levelPositions.length - 1 - levelIndex;
      const y = levelPositions[positionIndex];

      for (let columnIndex = 0; columnIndex < columnCount; columnIndex++) {
        const slotNumber = columnIndex + 1;
        const x = -width / 2 + (columnIndex + 0.5) * columnWidth;
        const spotLabel = rackCode ? `${rackCode}${slotNumber}` : `${levelLabel}${slotNumber}`;

        const spot = createSpot({
          size: new THREE.Vector3(columnWidth * 0.95, 0.4, depth * 0.95),
          position: new THREE.Vector3(x, y, 0),
          label: spotLabel,
        });
        rackSpots.add(spot);
      }
    });

    rack.add(rackSpots);

    return rack;
  }

  function createTable({ width = 1.8, depth = 0.8, height = 0.9 } = {}) {
    const tableGroup = new THREE.Group();
    const top = new THREE.Mesh(new THREE.BoxGeometry(width, 0.05, depth), new THREE.MeshStandardMaterial({ color: 0xc2a27a }));
    top.position.y = height;
    tableGroup.add(top);

    const legMat = new THREE.MeshStandardMaterial({ color: 0x1e90ff, metalness: 0.3, roughness: 0.6 });
    const legGeo = new THREE.BoxGeometry(0.07, height - 0.05, 0.07);

    const leg1 = new THREE.Mesh(legGeo, legMat);
    leg1.position.set(-width / 2 + 0.1, (height - 0.05) / 2, -depth / 2 + 0.1);
    const leg2 = leg1.clone();
    leg2.position.x = width / 2 - 0.1;
    const leg3 = leg1.clone();
    leg3.position.z = depth / 2 - 0.1;
    const leg4 = leg2.clone();
    leg4.position.z = depth / 2 - 0.1;

    tableGroup.add(leg1, leg2, leg3, leg4);

    const tableSpots = new THREE.Group();
    const columns = 3;
    const columnWidth = width / columns;
    const spotHeight = 0.2;
    const rowConfigs = [
      { label: 'U', zIndex: 0, y: height + spotHeight / 2 },
      { label: 'D', zIndex: 1, y: height / 2 },
    ];
    const rowDepth = depth / rowConfigs.length;

    rowConfigs.forEach(({ label: rowLabel, zIndex, y }) => {
      const z = -depth / 2 + (zIndex + 0.5) * rowDepth;

      for (let columnIndex = 0; columnIndex < columns; columnIndex++) {
        const x = -width / 2 + (columnIndex + 0.5) * columnWidth;
        const label = rowLabel ? `${rowLabel}${columnIndex + 1}` : `${columnIndex + 1}`;

        const spot = createSpot({
          size: new THREE.Vector3(columnWidth * 0.9, spotHeight, rowDepth * 0.9),
          position: new THREE.Vector3(x, y, z),
          label,
        });

        tableSpots.add(spot);
      }
    });

    tableGroup.add(tableSpots);

    return tableGroup;
  }

  const rackNorth = createRack({ rackCode: 'AA' });
  const rackWest = createRack({ rackCode: 'AAA' });
  const rackEast = createRack({ rackCode: 'A' });
  const table = createTable();

  const doorGroup = new THREE.Group();
  const door = new THREE.Mesh(new THREE.BoxGeometry(0.08, 2.05, 0.9), new THREE.MeshStandardMaterial({ color: 0x884422 }));
  door.position.set(0, 1.025, 0);
  door.castShadow = true;
  door.receiveShadow = true;
  doorGroup.add(door);

  const knob = new THREE.Mesh(new THREE.SphereGeometry(0.05, 24, 16), new THREE.MeshStandardMaterial({ color: 0xffcc00 }));
  knob.position.set(0.1, 1.0, -0.35);
  doorGroup.add(knob);

  scene.add(rackNorth, rackWest, rackEast, table, doorGroup);

  const layoutItems = {
    rack_north: rackNorth,
    rack_west: rackWest,
    rack_east: rackEast,
    table,
    door: doorGroup,
  };

  const defaultLayout = {
    rack_north: { position: { x: 0, y: 0, z: -roomDepth / 2 + 0.3 }, rotationY: 0 },
    rack_west: { position: { x: -roomWidth / 2 + 0.3, y: 0, z: 0 }, rotationY: Math.PI / 2 },
    rack_east: { position: { x: roomWidth / 2 - 0.3, y: 0, z: 0 }, rotationY: -Math.PI / 2 },
    table: { position: { x: -roomWidth / 2 + 1, y: 0, z: 2 }, rotationY: Math.PI / 2 },
    door: { position: { x: roomWidth / 2 - 0.1, y: 0, z: 1.6 }, rotationY: Math.PI },
  };

  const draggableObjects = [];
  const proxies = new Map();

  function syncProxyPosition(key) {
    const proxy = proxies.get(key);
    const target = layoutItems[key];
    if (!proxy || !target) {
      return;
    }

    proxy.position.set(target.position.x, 0, target.position.z);
  }

  function resetToDefault() {
    for (const [key, config] of Object.entries(defaultLayout)) {
      const target = layoutItems[key];
      if (!target) {
        continue;
      }

      target.position.set(config.position.x, config.position.y, config.position.z);
      target.rotation.y = config.rotationY;
      syncProxyPosition(key);
    }
  }

  function addDraggableProxyFor(key, group) {
    if (!isAdmin) {
      return null;
    }

    const box = new THREE.Box3().setFromObject(group);
    const size = new THREE.Vector3();
    box.getSize(size);
    const geo = new THREE.BoxGeometry(size.x + 0.1, Math.max(0.2, size.y + 0.1), size.z + 0.1);
    const mat = new THREE.MeshBasicMaterial({ color: 0x000000, transparent: true, opacity: 0.0, depthWrite: false });
    const proxy = new THREE.Mesh(geo, mat);
    proxy.userData = { key, target: group };
    scene.add(proxy);
    proxies.set(key, proxy);
    draggableObjects.push(proxy);
    syncProxyPosition(key);
    return proxy;
  }

  resetToDefault();

  addDraggableProxyFor('rack_north', rackNorth);
  addDraggableProxyFor('rack_west', rackWest);
  addDraggableProxyFor('rack_east', rackEast);
  addDraggableProxyFor('table', table);
  addDraggableProxyFor('door', doorGroup);

  if (saveButton && !isAdmin) {
    saveButton.remove();
  }

  if (isAdmin && draggableObjects.length > 0) {
    const dragControls = new DragControls(draggableObjects, camera, renderer.domElement);
    dragControls.addEventListener('dragstart', () => {
      controls.enabled = false;
    });

    dragControls.addEventListener('drag', (event) => {
      const proxy = event.object;
      proxy.position.y = 0;
      const key = proxy.userData?.key;
      const target = key ? layoutItems[key] : null;
      if (target) {
        target.position.set(proxy.position.x, 0, proxy.position.z);
      }
    });

    dragControls.addEventListener('dragend', (event) => {
      controls.enabled = true;
      const proxy = event.object;
      proxy.position.y = 0;
      const key = proxy.userData?.key;
      const target = key ? layoutItems[key] : null;
      if (target) {
        target.position.set(proxy.position.x, 0, proxy.position.z);
      }
    });
  }

  function applyLayout(items) {
    for (const item of items) {
      const key = item.item_key ?? item.itemKey;
      const target = layoutItems[key];
      if (!target) {
        continue;
      }

      const x = Number(item.position_x ?? item.position?.x);
      const y = Number(item.position_y ?? item.position?.y ?? 0);
      const z = Number(item.position_z ?? item.position?.z);
      const rotationY = Number(item.rotation_y ?? item.rotationY ?? target.rotation.y);

      if (!Number.isNaN(x) && !Number.isNaN(y) && !Number.isNaN(z)) {
        target.position.set(x, y, z);
      }

      if (!Number.isNaN(rotationY)) {
        target.rotation.y = rotationY;
      }

      syncProxyPosition(key);
    }
  }

  function roundTo(value, precision = 3) {
    const factor = 10 ** precision;
    return Math.round(value * factor) / factor;
  }

  function collectLayoutPayload() {
    const items = [];
    for (const [key, object] of Object.entries(layoutItems)) {
      items.push({
        itemKey: key,
        position: {
          x: roundTo(object.position.x, 3),
          y: roundTo(object.position.y, 3),
          z: roundTo(object.position.z, 3),
        },
        rotationY: roundTo(object.rotation.y, 6),
      });
    }
    return items;
  }

  async function loadLayout() {
    resetToDefault();

    try {
      const response = await fetch(`api/get_layout_positions.php?lieu=${encodeURIComponent(locationKey)}`, {
        credentials: 'same-origin',
      });

      if (!response.ok) {
        throw new Error(`Statut de réponse inattendu: ${response.status}`);
      }

      const data = await response.json();
      if (Array.isArray(data.items) && data.items.length > 0) {
        applyLayout(data.items);
        setStatus('Disposition chargée.', 'success');
      } else {
        setStatus('Disposition par défaut chargée.', 'info');
      }
    } catch (error) {
      console.error(error);
      setStatus('Impossible de charger la disposition sauvegardée.', 'error');
    }
  }

  loadLayout();

  if (saveButton && isAdmin) {
    saveButton.addEventListener('click', async () => {
      setStatus('Sauvegarde en cours…');
      saveButton.disabled = true;

      try {
        const response = await fetch('api/save_layout_positions.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          credentials: 'same-origin',
          body: JSON.stringify({
            location: locationKey,
            items: collectLayoutPayload(),
          }),
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok || data.status !== 'success') {
          throw new Error(data.error ?? 'Erreur lors de la sauvegarde de la disposition.');
        }

        setStatus('Disposition enregistrée avec succès.', 'success');
      } catch (error) {
        console.error(error);
        setStatus(error.message || "Impossible d'enregistrer la disposition.", 'error');
      } finally {
        saveButton.disabled = false;
      }
    });
  }

  function onResize() {
    camera.aspect = innerWidth / innerHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(innerWidth, innerHeight);
    labelRenderer.setSize(innerWidth, innerHeight);
  }

  window.addEventListener('resize', onResize);

  const tooltip = document.createElement('div');
  tooltip.className = 'spot-tooltip';
  tooltip.setAttribute('role', 'status');
  tooltip.setAttribute('aria-hidden', 'true');
  document.body.appendChild(tooltip);

  const raycaster = new THREE.Raycaster();
  const pointer = new THREE.Vector2();

  function updatePointer(event) {
    pointer.x = (event.clientX / innerWidth) * 2 - 1;
    pointer.y = -(event.clientY / innerHeight) * 2 + 1;
  }

  function handlePointerMove(event) {
    updatePointer(event);
    raycaster.setFromCamera(pointer, camera);
    const intersects = raycaster.intersectObjects(spotMeshes, false);

    if (intersects.length > 0) {
      const { label } = intersects[0].object.userData;
      tooltip.textContent = label ?? '';
      tooltip.style.left = `${event.clientX}px`;
      tooltip.style.top = `${event.clientY}px`;
      tooltip.setAttribute('aria-hidden', 'false');
    } else {
      tooltip.setAttribute('aria-hidden', 'true');
    }
  }

  function handlePointerLeave() {
    tooltip.setAttribute('aria-hidden', 'true');
  }

  renderer.domElement.addEventListener('pointermove', handlePointerMove);
  renderer.domElement.addEventListener('pointerleave', handlePointerLeave);

  function animate() {
    requestAnimationFrame(animate);
    controls.update();
    renderer.render(scene, camera);
    labelRenderer.render(scene, camera);
  }

  animate();
</script>
</body>
</html>
