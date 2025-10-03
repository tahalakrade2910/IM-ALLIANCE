<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header('Location: ../login.php');
    exit();
}
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
      top: 20px;
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

    .hint,
    .testpanel {
      position: fixed;
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

    .hint {
      left: 12px;
      bottom: 12px;
    }

    .hint b {
      font-weight: 700;
    }

    .testpanel {
      right: 12px;
      bottom: 12px;
      font-size: 12px;
    }

    .top-nav {
      position: relative;
      z-index: 30;
    }

    @media (max-width: 640px) {
      .hint,
      .testpanel {
        left: 50%;
        right: auto;
        transform: translateX(-50%);
        margin-bottom: 8px;
      }

      .testpanel {
        bottom: 56px;
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
    <a href="dashboard.php#consulter" class="btn-retour">
      ← Retour à la liste des stocks
    </a>
  </nav>
  <div id="app" aria-label="Visualisation 3D de l'entrepôt"></div>
  <div class="hint"><b>Contrôles :</b> glisser pour orbiter • molette pour zoomer • clic droit pour déplacer</div>
  <div class="testpanel" id="testpanel" role="status">Initialisation de la scène…</div>
</main>

<script type="module">
  import * as THREE from 'three';
  import { OrbitControls } from 'three/addons/controls/OrbitControls.js';

  const scene = new THREE.Scene();
  scene.background = new THREE.Color(0xf3f6fb);

  const camera = new THREE.PerspectiveCamera(55, innerWidth / innerHeight, 0.1, 100);
  camera.position.set(7, 4.5, 8);

  const renderer = new THREE.WebGLRenderer({ antialias: true });
  renderer.setPixelRatio(devicePixelRatio);
  renderer.setSize(innerWidth, innerHeight);
  renderer.shadowMap.enabled = true;
  document.getElementById('app').appendChild(renderer.domElement);

  const controls = new OrbitControls(camera, renderer.domElement);
  controls.target.set(0, 1.3, -2.0);
  controls.update();

  const hemi = new THREE.HemisphereLight(0xffffff, 0xbcc7d6, 0.85);
  scene.add(hemi);

  const dir = new THREE.DirectionalLight(0xffffff, 0.65);
  dir.position.set(6, 8, 5);
  dir.castShadow = true;
  dir.shadow.mapSize.set(2048, 2048);
  scene.add(dir);

  const roomWidth = 6;
  const roomDepth = 7;
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

  function createRack({ width = 1.8, depth = 0.6, height = 2.2, levels = 3 } = {}) {
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
      const y = 0.35 + (i * (height - 0.7)) / (levels - 1);

      const f = new THREE.Mesh(beamGeomW, beamMat); f.position.set(0, y, depth / 2);
      const b = new THREE.Mesh(beamGeomW, beamMat); b.position.set(0, y, -depth / 2);
      const l = new THREE.Mesh(beamGeomD, beamMat); l.position.set(-width / 2, y, 0);
      const r = new THREE.Mesh(beamGeomD, beamMat); r.position.set(width / 2, y, 0);

      rack.add(f, b, l, r);

      const board = new THREE.Mesh(
        new THREE.BoxGeometry(width - 0.08, 0.04, depth - 0.08),
        new THREE.MeshStandardMaterial({ color: SHELF, roughness: 0.95 })
      );
      board.position.set(0, y - 0.05, 0);
      board.castShadow = true;
      board.receiveShadow = true;
      rack.add(board);
    }

    return rack;
  }

  const rackNorth = createRack();
  rackNorth.position.set(0, 0, -roomDepth / 2 + 0.3);
  scene.add(rackNorth);

  const rackWest = createRack();
  rackWest.position.set(-roomWidth / 2 + 0.3, 0, 0);
  rackWest.rotation.y = Math.PI / 2;
  scene.add(rackWest);

  const rackEast = createRack();
  rackEast.position.set(roomWidth / 2 - 0.3, 0, 0);
  rackEast.rotation.y = -Math.PI / 2;
  scene.add(rackEast);

  const doorGroup = new THREE.Group();
  const door = new THREE.Mesh(new THREE.BoxGeometry(0.08, 2.05, 0.9), new THREE.MeshStandardMaterial({ color: 0x884422 }));
  door.position.set(0, 1.025, 0);
  door.castShadow = true;
  door.receiveShadow = true;
  doorGroup.add(door);

  const knob = new THREE.Mesh(new THREE.SphereGeometry(0.05, 24, 16), new THREE.MeshStandardMaterial({ color: 0xffcc00 }));
  knob.position.set(0.1, 1.0, -0.35);
  knob.castShadow = true;
  doorGroup.add(knob);

  doorGroup.position.set(roomWidth / 2 - 0.1, 0, 1.6);
  doorGroup.rotation.y = Math.PI;
  scene.add(doorGroup);

  function createTable({ width = 1.8, depth = 0.8, height = 0.9 } = {}) {
    const table = new THREE.Group();
    const top = new THREE.Mesh(
      new THREE.BoxGeometry(width, 0.05, depth),
      new THREE.MeshStandardMaterial({ color: 0xc2a27a })
    );
    top.position.y = height;
    top.castShadow = true;
    top.receiveShadow = true;
    table.add(top);

    const legMat = new THREE.MeshStandardMaterial({ color: 0x1e90ff, metalness: 0.3, roughness: 0.6 });
    const legGeo = new THREE.BoxGeometry(0.07, height - 0.05, 0.07);

    const leg1 = new THREE.Mesh(legGeo, legMat);
    leg1.position.set(-width / 2 + 0.1, (height - 0.05) / 2, -depth / 2 + 0.1);
    const leg2 = leg1.clone(); leg2.position.x = width / 2 - 0.1;
    const leg3 = leg1.clone(); leg3.position.z = depth / 2 - 0.1;
    const leg4 = leg2.clone(); leg4.position.z = depth / 2 - 0.1;

    [leg1, leg2, leg3, leg4].forEach((leg) => {
      leg.castShadow = true;
      leg.receiveShadow = true;
    });

    table.add(leg1, leg2, leg3, leg4);

    const braceMat = new THREE.MeshStandardMaterial({ color: 0x808080, metalness: 0.4, roughness: 0.6 });
    const braceW = new THREE.Mesh(new THREE.BoxGeometry(width - 0.2, 0.05, 0.05), braceMat);
    braceW.position.set(0, 0.25, depth / 2 - 0.05);
    const braceW2 = braceW.clone(); braceW2.position.z = -depth / 2 + 0.05;

    const braceD = new THREE.Mesh(new THREE.BoxGeometry(0.05, 0.05, depth - 0.2), braceMat);
    braceD.position.set(-width / 2 + 0.05, 0.25, 0);
    const braceD2 = braceD.clone(); braceD2.position.x = width / 2 - 0.05;

    table.add(braceW, braceW2, braceD, braceD2);

    return table;
  }

  const table = createTable();
  table.position.set(-roomWidth / 2 + 0.1 + 0.9 / 2, 0, 2.5);
  table.rotation.y = Math.PI / 2;
  scene.add(table);

  function onResize() {
    camera.aspect = innerWidth / innerHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(innerWidth, innerHeight);
  }
  window.addEventListener('resize', onResize);

  const testPanel = document.getElementById('testpanel');

  function animate() {
    requestAnimationFrame(animate);
    renderer.render(scene, camera);
  }

  animate();

  if (testPanel) {
    testPanel.textContent = 'Scène 3D prête pour la consultation des emplacements.';
  }
</script>
</body>
</html>
