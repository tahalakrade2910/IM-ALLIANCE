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
  <link rel="icon" type="image/svg+xml" href="../assets/images/imalliance-logo.svg" />
  <meta charset="UTF-8">
  <title>Consultation des emplacements</title>
  <link rel="stylesheet" href="../assets/css/styles.css" />
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      background-color: #dfe8f4;
    }

    main {
      display: flex;
      justify-content: center;
      padding: 2.5rem 1.5rem 3rem;
    }

    .layout {
      display: flex;
      gap: 36px;
      align-items: flex-start;
      width: min(1180px, 100%);
    }

    .location-panel {
      background: #e9edf5;
      border: 2px solid #b8c4d5;
      border-radius: 16px;
      padding: 24px;
      width: 300px;
      box-shadow: 0 12px 32px rgba(96, 125, 139, 0.28);
    }

    .location-panel h2 {
      margin: 0 0 18px;
      text-transform: uppercase;
      font-size: 1rem;
      letter-spacing: 0.04em;
      color: #2f3e58;
      text-align: center;
    }

    .location-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 12px;
    }

    .location-button {
      background: #d1d6de;
      border: 1px solid #a6afbe;
      border-radius: 8px;
      padding: 14px 0;
      font-size: 0.95rem;
      font-weight: 600;
      color: #2f3e58;
      cursor: pointer;
      transition: background 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
    }

    .location-button:hover,
    .location-button:focus-visible {
      background: #c2c8d3;
      outline: none;
      box-shadow: 0 0 0 3px rgba(65, 90, 119, 0.35);
    }

    .location-button.active {
      background: #415a77;
      color: #fff;
      transform: translateY(-2px);
      box-shadow: 0 10px 18px rgba(65, 90, 119, 0.35);
    }

    .location-grid div[aria-hidden="true"] {
      background: #d1d6de;
      border: 1px solid #a6afbe;
      border-radius: 8px;
      min-height: 48px;
    }

    .consultation {
      flex: 1;
      border: 2px solid #415a77;
      border-radius: 16px;
      padding: 28px 28px 36px;
      text-align: center;
      background: #f9fbff;
      box-shadow: 0 18px 48px rgba(65, 90, 119, 0.22);
    }

    .consultation h3 {
      margin-top: 0;
      color: #1b263b;
      font-size: 1.35rem;
      margin-bottom: 24px;
    }

    .consultation p {
      margin-top: 24px;
      color: #4a5568;
      font-size: 0.9rem;
    }

    /* Vue 3D */
    .scene {
      --rotate-x: 12deg;
      --rotate-y: -12deg;
      width: min(540px, 100%);
      height: 360px;
      margin: 0 auto;
      perspective: 1200px;
      cursor: grab;
      position: relative;
    }

    .scene:active {
      cursor: grabbing;
    }

    .warehouse {
      position: relative;
      width: 100%;
      height: 100%;
      transform-style: preserve-3d;
      transform: rotateX(var(--rotate-x)) rotateY(var(--rotate-y));
      transition: transform 0.1s linear;
      display: block;
    }

    .floor {
      position: absolute;
      inset: 30px 60px 0;
      background: linear-gradient(180deg, rgba(207, 220, 238, 0.65), rgba(153, 174, 210, 0.85));
      border-radius: 26px 26px 10px 10px;
      box-shadow: inset 0 0 26px rgba(0, 0, 0, 0.18);
      transform: translateZ(-60px) rotateX(90deg);
    }

    .floor-line {
      position: absolute;
      background: linear-gradient(90deg, rgba(20, 20, 25, 0.85), rgba(45, 45, 60, 0.95));
      transform: translateZ(-59px) rotateX(90deg);
      box-shadow: 0 10px 18px rgba(0, 0, 0, 0.25);
      border-radius: 12px;
    }

    .floor-line.horizontal {
      height: 10px;
      width: 360px;
      left: 110px;
      bottom: 90px;
    }

    .floor-line.vertical {
      width: 10px;
      height: 240px;
      left: 110px;
      bottom: 90px;
    }

    .rack {
      position: absolute;
      top: 50%;
      left: 50%;
      width: 260px;
      height: 260px;
      transform-style: preserve-3d;
    }

    .rack-front {
      transform: translate3d(-50%, -50%, 0) translate3d(-140px, 0, 130px) rotateY(18deg);
    }

    .rack-side {
      transform: translate3d(-50%, -50%, 0) translate3d(-260px, 0, -10px) rotateY(108deg);
    }

    .rack-back {
      transform: translate3d(-50%, -50%, 0) translate3d(-20px, 0, -140px) rotateY(-8deg);
    }

    .rack::before,
    .rack::after {
      content: "";
      position: absolute;
      top: -26px;
      width: 22px;
      height: calc(100% + 52px);
      background: linear-gradient(180deg, #3563b1 0%, #214b8f 100%);
      border-radius: 12px;
      box-shadow: 0 14px 24px rgba(17, 34, 64, 0.28);
      transform: translateZ(24px);
    }

    .rack::before {
      left: 16px;
    }

    .rack::after {
      right: 16px;
    }

    .rack .post-back {
      position: absolute;
      top: -18px;
      width: 18px;
      height: calc(100% + 36px);
      background: linear-gradient(180deg, #507ac6 0%, #335da7 100%);
      border-radius: 12px;
      transform: translateZ(-26px);
    }

    .rack .post-back.left {
      left: 36px;
    }

    .rack .post-back.right {
      right: 36px;
    }

    .brace {
      position: absolute;
      width: 6px;
      height: 210px;
      background: linear-gradient(180deg, #2d5094, #1e3770);
      transform-origin: center;
      transform: translateZ(12px) rotateZ(12deg);
      border-radius: 4px;
      box-shadow: 0 10px 14px rgba(17, 34, 64, 0.25);
    }

    .rack-side .brace {
      height: 180px;
    }

    .brace.right {
      right: 56px;
      transform: translateZ(12px) rotateZ(-12deg);
    }

    .brace.left {
      left: 56px;
    }

    .beam-layer {
      position: absolute;
      left: 52px;
      right: 52px;
      height: 16px;
      background: linear-gradient(180deg, #ff7a29, #dd4f13);
      border-radius: 8px;
      transform-origin: center;
      transform: translateZ(32px);
      box-shadow: 0 12px 18px rgba(148, 53, 9, 0.32);
    }

    .beam-layer[data-level="1"] { top: 18px; }
    .beam-layer[data-level="2"] { top: 88px; }
    .beam-layer[data-level="3"] { top: 158px; }
    .beam-layer[data-level="4"] { top: 228px; }

    .beam-layer::after {
      content: "";
      position: absolute;
      inset: 2px 14px;
      border-radius: 6px;
      background: linear-gradient(180deg, rgba(255, 255, 255, 0.35), rgba(255, 153, 85, 0.6));
    }

    .shelf-group {
      position: absolute;
      left: 62px;
      right: 62px;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
      gap: 22px;
      transform: translateZ(40px);
    }

    .shelf-group[data-level="1"] { top: 30px; }
    .shelf-group[data-level="2"] { top: 100px; }
    .shelf-group[data-level="3"] { top: 170px; }
    .shelf-group[data-level="4"] { top: 240px; }

    .shelf-surface {
      position: relative;
      height: 46px;
      border-radius: 10px;
      background: linear-gradient(180deg, #ffffff 0%, #d9e1ef 65%, #b7c5df 100%);
      box-shadow: inset 0 -6px 12px rgba(38, 61, 105, 0.18);
      padding: 4px 10px 8px;
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 8px;
    }

    .shelf-surface::before {
      content: "";
      position: absolute;
      inset: -8px -12px -18px;
      border-radius: 12px;
      background: linear-gradient(180deg, rgba(255, 255, 255, 0.65), rgba(199, 214, 239, 0.3));
      box-shadow: 0 16px 28px rgba(20, 34, 63, 0.28);
      transform: translateZ(-16px);
      pointer-events: none;
    }

    .door {
      position: absolute;
      top: 50%;
      left: 50%;
      width: 78px;
      height: 160px;
      border-radius: 10px;
      background: linear-gradient(180deg, #f1f5f9 0%, #d9e1ef 45%, #b8c5dc 100%);
      box-shadow: 0 18px 26px rgba(15, 23, 42, 0.28);
      transform: translate3d(-50%, -50%, 0) translate3d(210px, -10px, -60px) rotateY(-94deg);
      transform-origin: left center;
    }

    .door::before {
      content: "";
      position: absolute;
      top: 50%;
      left: 18px;
      width: 24px;
      height: 6px;
      border-radius: 3px;
      background: linear-gradient(90deg, #334155, #0f172a);
      transform: translateY(-50%);
      box-shadow: 0 6px 12px rgba(15, 23, 42, 0.2);
    }

    .location {
      position: relative;
      background: linear-gradient(180deg, rgba(65, 90, 119, 0.12), rgba(65, 90, 119, 0.28));
      border: 1px solid rgba(65, 90, 119, 0.55);
      border-radius: 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 0.8rem;
      color: rgba(31, 41, 55, 0.7);
      transform: translateZ(2px);
      transition: transform 0.3s, background 0.3s, box-shadow 0.3s, color 0.3s;
    }

    .location::after {
      content: attr(data-location);
      position: absolute;
      bottom: -26px;
      padding: 3px 8px;
      border-radius: 4px;
      background: rgba(65, 90, 119, 0.85);
      color: #f8fafc;
      font-size: 0.65rem;
      letter-spacing: 0.04em;
      opacity: 0;
      transform: translateZ(10px) translateY(6px);
      transition: opacity 0.25s ease, transform 0.25s ease;
      pointer-events: none;
      box-shadow: 0 8px 14px rgba(15, 23, 42, 0.25);
    }

    .location:hover,
    .location:focus-visible,
    .location.active {
      background: linear-gradient(180deg, #f97316, #ea580c);
      border-color: #b93807;
      color: #fff;
      box-shadow: 0 18px 28px rgba(233, 88, 12, 0.35);
      transform: translateZ(14px) scale(1.05);
    }

    .location:hover::after,
    .location:focus-visible::after,
    .location.active::after {
      opacity: 1;
      transform: translateZ(18px) translateY(0);
    }

    .location:focus-visible {
      outline: 3px solid rgba(249, 115, 22, 0.4);
      outline-offset: 2px;
    }

    @media (max-width: 900px) {
      main {
        padding: 1.5rem 1rem 2rem;
      }
    }

    @media (max-width: 1120px) {
      .layout {
        flex-direction: column;
        align-items: center;
      }

      .location-panel {
        width: 100%;
        max-width: 480px;
      }

      .consultation {
        width: 100%;
      }
    }
  </style>
</head>
<body>
<?php $baseUrl = '..'; require __DIR__ . '/../partials/top_nav.php'; ?>

<main>
  <div class="layout">
    <aside class="location-panel" aria-label="Liste des emplacements">
      <h2>Emplacements</h2>
      <div class="location-grid">
        <button type="button" class="location-button" data-location="A1">A1</button>
        <button type="button" class="location-button" data-location="A2">A2</button>
        <button type="button" class="location-button" data-location="A3">A3</button>
        <div aria-hidden="true"></div>
        <button type="button" class="location-button" data-location="B1">B1</button>
        <button type="button" class="location-button" data-location="B2">B2</button>
        <button type="button" class="location-button" data-location="B3">B3</button>
        <div aria-hidden="true"></div>
        <button type="button" class="location-button" data-location="C1">C1</button>
        <button type="button" class="location-button" data-location="C2">C2</button>
        <button type="button" class="location-button" data-location="C3">C3</button>
        <div aria-hidden="true"></div>
        <button type="button" class="location-button" data-location="D1">D1</button>
        <button type="button" class="location-button" data-location="D2">D2</button>
        <button type="button" class="location-button" data-location="D3">D3</button>
        <div aria-hidden="true"></div>
        <button type="button" class="location-button" data-location="AA1">AA1</button>
        <button type="button" class="location-button" data-location="AA2">AA2</button>
        <button type="button" class="location-button" data-location="AA3">AA3</button>
        <div aria-hidden="true"></div>
        <button type="button" class="location-button" data-location="BB1">BB1</button>
        <button type="button" class="location-button" data-location="BB2">BB2</button>
        <button type="button" class="location-button" data-location="BB3">BB3</button>
        <div aria-hidden="true"></div>
        <button type="button" class="location-button" data-location="CC1">CC1</button>
        <button type="button" class="location-button" data-location="CC2">CC2</button>
        <button type="button" class="location-button" data-location="CC3">CC3</button>
        <div aria-hidden="true"></div>
        <button type="button" class="location-button" data-location="DD1">DD1</button>
        <button type="button" class="location-button" data-location="DD2">DD2</button>
        <button type="button" class="location-button" data-location="DD3">DD3</button>
        <div aria-hidden="true"></div>
      </div>
    </aside>

    <div class="consultation">
      <h3>Vue 3D de l’emplacement</h3>
      <div class="scene" id="scene">
        <div class="warehouse" id="warehouse">
          <div class="floor"></div>
          <div class="floor-line horizontal" aria-hidden="true"></div>
          <div class="floor-line vertical" aria-hidden="true"></div>

          <div class="rack rack-front" aria-label="Rack avant">
            <span class="post-back left"></span>
            <span class="post-back right"></span>
            <span class="brace left"></span>
            <span class="brace right"></span>
            <span class="beam-layer" data-level="1"></span>
            <span class="beam-layer" data-level="2"></span>
            <span class="beam-layer" data-level="3"></span>
            <span class="beam-layer" data-level="4"></span>

            <div class="shelf-group" data-level="1">
              <div class="shelf-surface">
                <div class="location" data-location="A1" aria-label="A1" tabindex="0"></div>
                <div class="location" data-location="A2" aria-label="A2" tabindex="0"></div>
                <div class="location" data-location="A3" aria-label="A3" tabindex="0"></div>
              </div>
            </div>

            <div class="shelf-group" data-level="2">
              <div class="shelf-surface">
                <div class="location" data-location="B1" aria-label="B1" tabindex="0"></div>
                <div class="location" data-location="B2" aria-label="B2" tabindex="0"></div>
                <div class="location" data-location="B3" aria-label="B3" tabindex="0"></div>
              </div>
            </div>

            <div class="shelf-group" data-level="3">
              <div class="shelf-surface">
                <div class="location" data-location="C1" aria-label="C1" tabindex="0"></div>
                <div class="location" data-location="C2" aria-label="C2" tabindex="0"></div>
                <div class="location" data-location="C3" aria-label="C3" tabindex="0"></div>
              </div>
            </div>

            <div class="shelf-group" data-level="4">
              <div class="shelf-surface">
                <div class="location" data-location="D1" aria-label="D1" tabindex="0"></div>
                <div class="location" data-location="D2" aria-label="D2" tabindex="0"></div>
                <div class="location" data-location="D3" aria-label="D3" tabindex="0"></div>
              </div>
            </div>
          </div>

          <div class="rack rack-side" aria-label="Rack latéral">
            <span class="post-back left"></span>
            <span class="post-back right"></span>
            <span class="brace left"></span>
            <span class="brace right"></span>
            <span class="beam-layer" data-level="1"></span>
            <span class="beam-layer" data-level="2"></span>
            <span class="beam-layer" data-level="3"></span>

            <div class="shelf-group" data-level="1">
              <div class="shelf-surface">
                <div class="location" data-location="AA1" aria-label="AA1" tabindex="0"></div>
                <div class="location" data-location="AA2" aria-label="AA2" tabindex="0"></div>
                <div class="location" data-location="AA3" aria-label="AA3" tabindex="0"></div>
              </div>
            </div>

            <div class="shelf-group" data-level="2">
              <div class="shelf-surface">
                <div class="location" data-location="BB1" aria-label="BB1" tabindex="0"></div>
                <div class="location" data-location="BB2" aria-label="BB2" tabindex="0"></div>
                <div class="location" data-location="BB3" aria-label="BB3" tabindex="0"></div>
              </div>
            </div>
          </div>

          <div class="rack rack-back" aria-label="Rack arrière">
            <span class="post-back left"></span>
            <span class="post-back right"></span>
            <span class="brace left"></span>
            <span class="brace right"></span>
            <span class="beam-layer" data-level="1"></span>
            <span class="beam-layer" data-level="2"></span>
            <span class="beam-layer" data-level="3"></span>

            <div class="shelf-group" data-level="1">
              <div class="shelf-surface">
                <div class="location" data-location="CC1" aria-label="CC1" tabindex="0"></div>
                <div class="location" data-location="CC2" aria-label="CC2" tabindex="0"></div>
                <div class="location" data-location="CC3" aria-label="CC3" tabindex="0"></div>
              </div>
            </div>

            <div class="shelf-group" data-level="2">
              <div class="shelf-surface">
                <div class="location" data-location="DD1" aria-label="DD1" tabindex="0"></div>
                <div class="location" data-location="DD2" aria-label="DD2" tabindex="0"></div>
                <div class="location" data-location="DD3" aria-label="DD3" tabindex="0"></div>
              </div>
            </div>
          </div>

          <div class="door" role="img" aria-label="Porte"></div>
        </div>
      </div>
      <p>Faites pivoter la scène en maintenant le clic et survolez une zone pour afficher sa désignation.</p>
    </div>
  </div>

</main>

  <script>
    const scene = document.getElementById('scene');
    let currentRotation = { x: 12, y: -12 };
    let isPointerDown = false;
    let start = { x: 0, y: 0 };

    function setRotation(x, y) {
      currentRotation = { x, y };
      scene.style.setProperty('--rotate-x', `${x}deg`);
      scene.style.setProperty('--rotate-y', `${y}deg`);
    }

    scene.addEventListener('pointerdown', (event) => {
      isPointerDown = true;
      start = { x: event.clientX, y: event.clientY };
      scene.setPointerCapture(event.pointerId);
    });

    scene.addEventListener('pointermove', (event) => {
      if (!isPointerDown) return;
      const deltaX = event.clientX - start.x;
      const deltaY = event.clientY - start.y;
      const newY = Math.max(-40, Math.min(40, currentRotation.y + deltaX * 0.12));
      const newX = Math.max(4, Math.min(52, currentRotation.x - deltaY * 0.12));
      setRotation(newX, newY);
      start = { x: event.clientX, y: event.clientY };
    });

    scene.addEventListener('pointerup', (event) => {
      isPointerDown = false;
      scene.releasePointerCapture(event.pointerId);
    });

    scene.addEventListener('pointerleave', () => {
      isPointerDown = false;
    });

    const listLocations = document.querySelectorAll('.location-button');
    const sceneLocations = document.querySelectorAll('.warehouse .location');

    function clearActive() {
      listLocations.forEach((button) => button.classList.remove('active'));
      sceneLocations.forEach((element) => element.classList.remove('active'));
    }

    function setActive(locationId) {
      clearActive();
      const targetButton = Array.from(listLocations).find(
        (button) => button.dataset.location === locationId
      );
      const targetLocation = Array.from(sceneLocations).find(
        (element) => element.dataset.location === locationId
      );

      if (targetButton) {
        targetButton.classList.add('active');
        targetButton.focus({ preventScroll: true });
      }

      if (targetLocation) {
        targetLocation.classList.add('active');
      }
    }

    listLocations.forEach((button) => {
      button.addEventListener('click', () => {
        setActive(button.dataset.location);
      });

      button.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          setActive(button.dataset.location);
        }
      });
    });

    sceneLocations.forEach((element) => {
      element.addEventListener('click', () => {
        setActive(element.dataset.location);
      });

      element.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          setActive(element.dataset.location);
        }
      });
    });

    // Initial rotation values
    setRotation(currentRotation.x, currentRotation.y);
  </script>

</body>
</html>
