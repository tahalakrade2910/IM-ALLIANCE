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
      gap: 30px;
      padding: 2rem;
      flex-wrap: wrap;
      justify-content: center;
    }

    /* Grille */
    .grille {
      display: grid;
      grid-template-columns: repeat(3, 80px);
      grid-gap: 10px;
    }

    .case {
      width: 80px;
      height: 80px;
      background: #d9e2ef;
      border: 2px solid #415a77;
      color: #1b263b;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: background 0.3s, transform 0.3s;
      font-weight: bold;
      border-radius: 8px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .case:hover {
      background: #a9c4eb;
      transform: translateY(-2px);
    }

    /* Zone aperçu */
    .consultation {
      flex: 1;
      min-width: 360px;
      border: 2px solid #415a77;
      border-radius: 12px;
      padding: 20px;
      text-align: center;
      background: #f9fbff;
      box-shadow: 0 10px 30px rgba(65, 90, 119, 0.18);
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
      width: min(480px, 100%);
      height: 320px;
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
      width: 140px;
      height: 190px;
      padding: 16px 14px;
      background: linear-gradient(160deg, #eceff4 0%, #d8dee9 100%);
      border: 2px solid #9aa5b1;
      border-radius: 10px;
      display: grid;
      grid-template-rows: repeat(2, 1fr);
      gap: 12px;
      box-shadow: 0 15px 25px rgba(27, 38, 59, 0.25);
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
      transform: translate3d(40px, 70px, 90px);
    }

    .rack-right-front {
      transform: translate3d(210px, 60px, 60px) rotateY(-14deg);
    }

    .rack-left-back {
      transform: translate3d(70px, 40px, -90px) rotateY(18deg);
    }

    .rack-right-back {
      transform: translate3d(240px, 30px, -120px) rotateY(-10deg);
    }

    .shelf {
      background: rgba(255, 255, 255, 0.7);
      border-radius: 8px;
      padding: 6px;
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 6px;
      box-shadow: inset 0 8px 12px rgba(65, 90, 119, 0.12);
    }

    .location {
      position: relative;
      background: rgba(65, 90, 119, 0.12);
      border: 1px solid rgba(65, 90, 119, 0.4);
      border-radius: 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      color: #1b263b;
      transition: transform 0.3s, background 0.3s, color 0.3s, box-shadow 0.3s;
      transform-style: preserve-3d;
    }

    .location::after {
      content: attr(data-location);
      position: absolute;
      top: -22px;
      right: -6px;
      background: #e11d48;
      color: #fff;
      padding: 2px 6px;
      border-radius: 4px;
      font-size: 0.7rem;
      transform: translateZ(10px);
      box-shadow: 0 2px 6px rgba(225, 29, 72, 0.35);
      opacity: 0;
      transition: opacity 0.3s;
    }

    .location.active {
      background: #e11d48;
      color: #fff;
      box-shadow: 0 10px 16px rgba(225, 29, 72, 0.35);
      transform: translateZ(12px) scale(1.05);
    }

    .location.active::after {
      opacity: 1;
    }

    @media (max-width: 900px) {
      main {
        flex-direction: column;
        align-items: center;
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
  <!-- Grille -->
  <div class="grille">
    <div class="case" data-img="A1.png">A1</div>
    <div class="case" data-img="A2.png">A2</div>
    <div class="case" data-img="A3.png">A3</div>
    <div class="case" data-img="B1.png">B1</div>
    <div class="case" data-img="B2.png">B2</div>
    <div class="case" data-img="B3.png">B3</div>
    <div class="case" data-img="C1.png">C1</div>
    <div class="case" data-img="C2.png">C2</div>
    <div class="case" data-img="C3.png">C3</div>
    <div class="case" data-img="D1.png">D1</div>
    <div class="case" data-img="D2.png">D2</div>
    <div class="case" data-img="D3.png">D3</div>

    <div class="case" data-img="AA1.png">AA1</div>
    <div class="case" data-img="AA2.png">AA2</div>
    <div class="case" data-img="AA3.png">AA3</div>
    <div class="case" data-img="BB1.png">BB1</div>
    <div class="case" data-img="BB2.png">BB2</div>
    <div class="case" data-img="BB3.png">BB3</div>
    <div class="case" data-img="CC1.png">CC1</div>
    <div class="case" data-img="CC2.png">CC2</div>
    <div class="case" data-img="CC3.png">CC3</div>
    <div class="case" data-img="DD1.png">DD1</div>
    <div class="case" data-img="DD2.png">DD2</div>
    <div class="case" data-img="DD3.png">DD3</div>
  </div>

  <!-- Zone de consultation -->
  <div class="consultation">
    <h3>Vue 3D de l’emplacement</h3>
    <div class="scene" id="scene">
      <div class="warehouse" id="warehouse">
        <div class="floor"></div>
        <div class="door"></div>

        <div class="rack rack-left-front">
          <div class="shelf">
            <div class="location" data-location="A1">A1</div>
            <div class="location" data-location="A2">A2</div>
            <div class="location" data-location="A3">A3</div>
          </div>
          <div class="shelf">
            <div class="location" data-location="B1">B1</div>
            <div class="location" data-location="B2">B2</div>
            <div class="location" data-location="B3">B3</div>
          </div>
        </div>

        <div class="rack rack-right-front">
          <div class="shelf">
            <div class="location" data-location="C1">C1</div>
            <div class="location" data-location="C2">C2</div>
            <div class="location" data-location="C3">C3</div>
          </div>
          <div class="shelf">
            <div class="location" data-location="D1">D1</div>
            <div class="location" data-location="D2">D2</div>
            <div class="location" data-location="D3">D3</div>
          </div>
        </div>

        <div class="rack rack-left-back">
          <div class="shelf">
            <div class="location" data-location="AA1">AA1</div>
            <div class="location" data-location="AA2">AA2</div>
            <div class="location" data-location="AA3">AA3</div>
          </div>
          <div class="shelf">
            <div class="location" data-location="BB1">BB1</div>
            <div class="location" data-location="BB2">BB2</div>
            <div class="location" data-location="BB3">BB3</div>
          </div>
        </div>

        <div class="rack rack-right-back">
          <div class="shelf">
            <div class="location" data-location="CC1">CC1</div>
            <div class="location" data-location="CC2">CC2</div>
            <div class="location" data-location="CC3">CC3</div>
          </div>
          <div class="shelf">
            <div class="location" data-location="DD1">DD1</div>
            <div class="location" data-location="DD2">DD2</div>
            <div class="location" data-location="DD3">DD3</div>
          </div>
        </div>
      </div>
    </div>
    <p>Faites pivoter la scène en maintenant le clic et surlignez un emplacement depuis la liste.</p>
  </div>

</main>

  <script>
    const cases = document.querySelectorAll('.case');
    const locations = document.querySelectorAll('.location');
    const scene = document.getElementById('scene');
    const warehouse = document.getElementById('warehouse');

    const locationMap = new Map();
    locations.forEach(location => {
      locationMap.set(location.dataset.location, location);
      location.addEventListener('click', () => {
        highlightLocation(location.dataset.location);
      });
    });

    let currentRotation = { x: 18, y: -26 };
    let isPointerDown = false;
    let start = { x: 0, y: 0 };

    function setRotation(x, y) {
      currentRotation = { x, y };
      scene.style.setProperty('--rotate-x', `${x}deg`);
      scene.style.setProperty('--rotate-y', `${y}deg`);
    }

    function highlightLocation(id) {
      locations.forEach(location => location.classList.remove('active'));
      const target = locationMap.get(id);
      if (target) {
        target.classList.add('active');
        target.scrollIntoView({ block: 'nearest', inline: 'nearest' });
      }
    }

    cases.forEach(c => {
      c.addEventListener('click', () => {
        highlightLocation(c.textContent.trim());
      });
    });

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
