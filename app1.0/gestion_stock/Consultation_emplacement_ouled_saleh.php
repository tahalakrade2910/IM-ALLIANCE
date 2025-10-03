<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header('Location: ../login.php');
    exit();
}

$currentLieu = 'Ouled Saleh';
$dashboardUrl = 'dashboard.php?' . http_build_query([
    'lieu' => $currentLieu,
    'section' => $_GET['section'] ?? 'consulter',
]);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Consultation des emplacements - Ouled Saleh</title>
  <link rel="icon" type="image/svg+xml" href="../assets/images/imalliance-logo.svg" />
  <link rel="stylesheet" href="../assets/css/styles.css" />
  <style>
    html, body { height: 100%; margin: 0; background:#eaf2fb; }
    #app { position: fixed; inset: 0; }
    .hint {
      position: fixed; left: 12px; bottom: 12px; padding: 10px 12px; font: 14px/1.4 system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif; color:#0f172a; background:#ffffffcc; border:1px solid #cbd5e1; border-radius:10px; backdrop-filter: blur(3px);
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
  <nav class="return-buttons" aria-label="Navigation retour">
    <a href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES) ?>" class="btn-retour">
      ← Retour à la gestion des stocks
    </a>
  </nav>
  <div id="app" aria-label="Visualisation 3D de l'entrepôt d'Ouled Saleh"></div>
  <div class="hint"><b>Contrôles :</b> glisser pour orbiter • molette pour zoomer • clic droit pour déplacer</div>

  <script type="module">
    import * as THREE from 'three';
    import { OrbitControls } from 'three/addons/controls/OrbitControls.js';

    const scene = new THREE.Scene();
    scene.background = new THREE.Color(0xf3f6fb);

    const camera = new THREE.PerspectiveCamera(55, innerWidth/innerHeight, 0.1, 100);
    camera.position.set(6, 4, 6);

    const renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setPixelRatio(devicePixelRatio);
    renderer.setSize(innerWidth, innerHeight);
    document.getElementById('app').appendChild(renderer.domElement);

    const controls = new OrbitControls(camera, renderer.domElement);
    controls.target.set(0, 1.3, 0);
    controls.update();

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

    const hemi = new THREE.HemisphereLight(0xffffff, 0xbcc7d6, 0.85);
    scene.add(hemi);

    const dir = new THREE.DirectionalLight(0xffffff, 0.65);
    dir.position.set(6, 8, 5);
    dir.castShadow = true;
    scene.add(dir);

    const roomWidth = 6;
    const roomDepth = 6;
    const wallHeight = 3;

    const floor = new THREE.Mesh(
      new THREE.PlaneGeometry(roomWidth, roomDepth),
      new THREE.MeshStandardMaterial({ color: 0xe6eaef, roughness:0.95 })
    );
    floor.rotation.x = -Math.PI/2;
    scene.add(floor);

    const fenceMat = new THREE.MeshStandardMaterial({ color: 0x888888, metalness:0.9, roughness:0.2 });
    const barThickness = 0.02;
    const spacing = 0.2;

    function createFenceGrid(width, height, opening=false, openingWidth=2) {
      const group = new THREE.Group();
      const halfOpening = openingWidth/2;
      for (let x=-width/2; x<=width/2; x+=spacing) {
        if (opening && x > -halfOpening && x < halfOpening) continue;
        const bar = new THREE.Mesh(new THREE.BoxGeometry(barThickness, height, barThickness), fenceMat);
        bar.position.set(x, height/2, 0);
        group.add(bar);
      }
      for (let y=0; y<=height; y+=spacing) {
        const horiz = new THREE.Mesh(new THREE.BoxGeometry(width, barThickness, barThickness), fenceMat);
        horiz.position.set(0, y, 0);
        group.add(horiz);
      }
      return group;
    }

    const fenceNorth = createFenceGrid(roomWidth, wallHeight);
    fenceNorth.position.set(0, 0, -roomDepth/2);
    scene.add(fenceNorth);

    const fenceWest = createFenceGrid(roomDepth, wallHeight);
    fenceWest.rotation.y = Math.PI/2;
    fenceWest.position.set(-roomWidth/2, 0, 0);
    scene.add(fenceWest);

    function createRack({ width=1.8, depth=0.6, height=2.2, levels=3 }={}) {
      const rack = new THREE.Group();
      const uprGeom = new THREE.BoxGeometry(0.08, height, 0.08);
      const uprMat = new THREE.MeshStandardMaterial({ color: 0xff6600 });

      const uprA = new THREE.Mesh(uprGeom, uprMat);
      uprA.position.set(-width/2, height/2, -depth/2);
      const uprB = uprA.clone(); uprB.position.x = width/2;
      const uprC = uprA.clone(); uprC.position.z = depth/2;
      const uprD = uprB.clone(); uprD.position.z = depth/2;

      rack.add(uprA,uprB,uprC,uprD);

      for (let i=0; i<levels; i++) {
        const y = 0.35 + (i * (height-0.7)/(levels-1));
        const shelf = new THREE.Mesh(
          new THREE.BoxGeometry(width, 0.05, depth),
          new THREE.MeshStandardMaterial({ color: 0x333333 })
        );
        shelf.position.y = y;
        rack.add(shelf);
      }
      return rack;
    }

    const rack = createRack({});
    rack.position.set(-roomWidth/2 + 1.5, 0, -roomDepth/2 + 0.5);
    scene.add(rack);

    function createTable({ width=1.8, depth=0.8, height=0.9 }={}) {
      const table = new THREE.Group();
      const top = new THREE.Mesh(
        new THREE.BoxGeometry(width, 0.05, depth),
        new THREE.MeshStandardMaterial({ color: 0xc2a27a })
      );
      top.position.y = height;
      table.add(top);

      const legMat = new THREE.MeshStandardMaterial({ color: 0x1e90ff });
      const legGeo = new THREE.BoxGeometry(0.07, height-0.05, 0.07);

      const leg1 = new THREE.Mesh(legGeo, legMat);
      leg1.position.set(-width/2+0.1, (height-0.05)/2, -depth/2+0.1);
      const leg2 = leg1.clone(); leg2.position.x = width/2-0.1;
      const leg3 = leg1.clone(); leg3.position.z = depth/2-0.1;
      const leg4 = leg2.clone(); leg4.position.z = depth/2-0.1;
      table.add(leg1, leg2, leg3, leg4);

      return table;
    }

    const table = createTable({});
    table.position.set(-roomWidth/2 + 0.5, 0, -roomDepth/2 + 2.5);
    table.rotation.y = Math.PI/2;
    scene.add(table);

    function onResize() {
      camera.aspect = innerWidth / innerHeight;
      camera.updateProjectionMatrix();
      renderer.setSize(innerWidth, innerHeight);
    }
    window.addEventListener('resize', onResize);

    (function animate(){
      requestAnimationFrame(animate);
      renderer.render(scene, camera);
    })();
  </script>
</body>
</html>
