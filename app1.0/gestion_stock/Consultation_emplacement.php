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
      background-color: #f5f8ff;
    }

    main {
      display: flex;
      justify-content: center;
      padding: 2.5rem 1.5rem 3rem;
    }

    .consultation {
      width: min(760px, 100%);
      border: 2px solid #415a77;
      border-radius: 16px;
      padding: 28px;
      text-align: center;
      background: #f9fbff;
      box-shadow: 0 18px 48px rgba(65, 90, 119, 0.22);
    }

    .consultation h3 {
      margin-top: 0;
      color: #1b263b;
    }

    .consultation p {
      margin-top: 12px;
      color: #4a5568;
      font-size: 0.9rem;
    }

    /* Vue 3D */
    .scene {
      --rotate-x: 18deg;
      --rotate-y: -26deg;
      width: min(520px, 100%);
      height: 340px;
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
    }

    .floor {
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, #e6edf7 0%, #c9d8f2 100%);
      border-radius: 18px;
      box-shadow: inset 0 0 30px rgba(0, 0, 0, 0.1);
      transform: translateZ(-30px);
    }

    .floor::before,
    .floor::after {
      content: "";
      position: absolute;
      background: rgba(25, 42, 86, 0.4);
      height: 4px;
      border-radius: 4px;
    }

    .floor::before {
      width: 65%;
      bottom: 40px;
      left: 18%;
    }

    .floor::after {
      width: 45%;
      left: 30%;
      bottom: 90px;
      transform: rotateY(90deg);
      transform-origin: left;
    }

    .door {
      position: absolute;
      right: 35px;
      top: 40px;
      width: 54px;
      height: 110px;
      background: #f1f5f9;
      border: 3px solid #9ca3af;
      border-radius: 6px;
      box-shadow: inset 0 0 10px rgba(0, 0, 0, 0.15);
      transform: translateZ(40px) rotateY(90deg);
    }

    .door::after {
      content: "";
      position: absolute;
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: #9ca3af;
      top: 50%;
      left: 8px;
    }

    .rack {
      position: absolute;
      width: 150px;
      height: 200px;
      padding: 18px 16px;
      background: linear-gradient(160deg, #eef2f8 0%, #dce3ed 100%);
      border: 2px solid #9aa5b1;
      border-radius: 12px;
      display: grid;
      grid-template-rows: repeat(2, 1fr);
      gap: 14px;
      box-shadow: 0 18px 32px rgba(27, 38, 59, 0.28);
      transform-style: preserve-3d;
    }

    .rack::after {
      content: "";
      position: absolute;
      inset: -2px;
      border-radius: 12px;
      border: 2px solid rgba(27, 38, 59, 0.08);
      pointer-events: none;
    }

    .rack-left-front {
      transform: translate3d(40px, 74px, 96px);
    }

    .rack-right-front {
      transform: translate3d(226px, 64px, 68px) rotateY(-14deg);
    }

    .rack-left-back {
      transform: translate3d(74px, 42px, -96px) rotateY(18deg);
    }

    .rack-right-back {
      transform: translate3d(260px, 36px, -128px) rotateY(-10deg);
    }

    .shelf {
      background: rgba(255, 255, 255, 0.75);
      border-radius: 10px;
      padding: 8px;
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 8px;
      box-shadow: inset 0 10px 18px rgba(65, 90, 119, 0.14);
    }

    .location {
      position: relative;
      background: rgba(65, 90, 119, 0.12);
      border: 1px solid rgba(65, 90, 119, 0.4);
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      color: transparent;
      transition: transform 0.3s, background 0.3s, box-shadow 0.3s;
      transform-style: preserve-3d;
    }

    .location::after {
      content: attr(data-location);
      position: absolute;
      top: -26px;
      right: -4px;
      background: #e11d48;
      color: #fff;
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 0.7rem;
      transform: translateZ(14px);
      box-shadow: 0 4px 10px rgba(225, 29, 72, 0.35);
      opacity: 0;
      transition: opacity 0.25s, transform 0.25s;
      pointer-events: none;
    }

    .location:hover,
    .location:focus-visible,
    .location.active {
      background: #e11d48;
      box-shadow: 0 14px 20px rgba(225, 29, 72, 0.35);
      transform: translateZ(16px) scale(1.04);
    }

    .location:hover::after,
    .location:focus-visible::after,
    .location.active::after {
      opacity: 1;
      transform: translateZ(18px);
    }

    .location:focus-visible {
      outline: 3px solid rgba(65, 90, 119, 0.35);
    }

    @media (max-width: 900px) {
      main {
        padding: 1.5rem 1rem 2rem;
      }
    }
  </style>
</head>
<body>
<?php $baseUrl = '..'; require __DIR__ . '/../partials/top_nav.php'; ?>

<main>
  <div class="consultation">
    <h3>Vue 3D de l’emplacement</h3>
    <div class="scene" id="scene">
      <div class="warehouse" id="warehouse">
        <div class="floor"></div>
        <div class="door"></div>

        <div class="rack rack-left-front">
          <div class="shelf">
            <div class="location" data-location="A1" aria-label="A1" tabindex="0"></div>
            <div class="location" data-location="A2" aria-label="A2" tabindex="0"></div>
            <div class="location" data-location="A3" aria-label="A3" tabindex="0"></div>
          </div>
          <div class="shelf">
            <div class="location" data-location="B1" aria-label="B1" tabindex="0"></div>
            <div class="location" data-location="B2" aria-label="B2" tabindex="0"></div>
            <div class="location" data-location="B3" aria-label="B3" tabindex="0"></div>
          </div>
        </div>

        <div class="rack rack-right-front">
          <div class="shelf">
            <div class="location" data-location="C1" aria-label="C1" tabindex="0"></div>
            <div class="location" data-location="C2" aria-label="C2" tabindex="0"></div>
            <div class="location" data-location="C3" aria-label="C3" tabindex="0"></div>
          </div>
          <div class="shelf">
            <div class="location" data-location="D1" aria-label="D1" tabindex="0"></div>
            <div class="location" data-location="D2" aria-label="D2" tabindex="0"></div>
            <div class="location" data-location="D3" aria-label="D3" tabindex="0"></div>
          </div>
        </div>

        <div class="rack rack-left-back">
          <div class="shelf">
            <div class="location" data-location="AA1" aria-label="AA1" tabindex="0"></div>
            <div class="location" data-location="AA2" aria-label="AA2" tabindex="0"></div>
            <div class="location" data-location="AA3" aria-label="AA3" tabindex="0"></div>
          </div>
          <div class="shelf">
            <div class="location" data-location="BB1" aria-label="BB1" tabindex="0"></div>
            <div class="location" data-location="BB2" aria-label="BB2" tabindex="0"></div>
            <div class="location" data-location="BB3" aria-label="BB3" tabindex="0"></div>
          </div>
        </div>

        <div class="rack rack-right-back">
          <div class="shelf">
            <div class="location" data-location="CC1" aria-label="CC1" tabindex="0"></div>
            <div class="location" data-location="CC2" aria-label="CC2" tabindex="0"></div>
            <div class="location" data-location="CC3" aria-label="CC3" tabindex="0"></div>
          </div>
          <div class="shelf">
            <div class="location" data-location="DD1" aria-label="DD1" tabindex="0"></div>
            <div class="location" data-location="DD2" aria-label="DD2" tabindex="0"></div>
            <div class="location" data-location="DD3" aria-label="DD3" tabindex="0"></div>
          </div>
        </div>
      </div>
    </div>
    <p>Faites pivoter la scène en maintenant le clic et survolez une zone pour afficher sa désignation.</p>
  </div>

</main>

  <script>
    const scene = document.getElementById('scene');
    let currentRotation = { x: 18, y: -26 };
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
      const newY = Math.max(-70, Math.min(20, currentRotation.y + deltaX * 0.15));
      const newX = Math.max(5, Math.min(65, currentRotation.x - deltaY * 0.15));
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

    // Initial rotation values
    setRotation(currentRotation.x, currentRotation.y);
  </script>

</body>
</html>
