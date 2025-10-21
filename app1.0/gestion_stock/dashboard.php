<?php
require_once 'connexion.php';
session_start();
if (!isset($_SESSION['logged_in'])) {
    header('Location: ../login.php');
    exit();
}
$isAdmin = (($_SESSION['role'] ?? '') === 'admin');
$defaultSection = $_GET['section'] ?? '';

$lieuxDisponibles = [
  'Rabat' => 'Consulter stock Rabat',
  'Ouled Saleh' => 'Consulter stock Ouled Saleh',
];
$defaultLieu = array_key_first($lieuxDisponibles);
$currentLieu = $_GET['lieu'] ?? $defaultLieu;
if (!isset($lieuxDisponibles[$currentLieu])) {
  $currentLieu = $defaultLieu;
}

$consultationPage = $currentLieu === 'Ouled Saleh'
  ? 'Consultation_emplacement_ouled_saleh.php'
  : 'Consultation_emplacement.php';

if ($currentLieu === $defaultLieu) {
  $stmt = $pdo->prepare("SELECT * FROM stock WHERE lieu = :lieu OR lieu IS NULL ORDER BY id DESC");
  $stmt->execute(['lieu' => $currentLieu]);
} else {
  $stmt = $pdo->prepare("SELECT * FROM stock WHERE lieu = :lieu ORDER BY id DESC");
  $stmt->execute(['lieu' => $currentLieu]);
}
$stocks = $stmt->fetchAll();

$references = [
  "SPAA4656" => "Assembly-diverter ; processor",
  "SP9G6744" => "KIT-assembly,PICKUP",
  "SP2G0985" => "Charcoalter Filter",
  "SPAB8123" => "Assembly:Drum-processor,240V,lamp heater",
  "SPAB8125" => "Assembly:Drum-processor,700W,lamp heater",
  "SPAB8126" => "Assembly:Exposure transport",
  "SPAB8130" => "Assembly:Optics",
  "SPAA4993" => "KIT optics",
  "SPAD3067" => "Assembly-Motor,pickup",
  "SPAB3790" => "Assembly:STEPPER MOTOR,tensioner",
  "SPAG9796" => "Motor-STEPPER,Transport,5.0 MOUNT",
  "SPAG7962" => "Motor-stepper,processor drive",
  "SPAB8129" => "Assembly local panel",
  "SPAH7204" => "VITA FLEX"
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="icon" type="image/svg+xml" href="../assets/images/imalliance-logo.svg" />
  <link rel="stylesheet" href="../assets/css/styles.css" />

  
  <meta charset="UTF-8" />
  <title>Gestion de Stock</title>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
  <style>
    * { box-sizing: border-box; }
    body {
      * { box-sizing: border-box; }

body {
  margin: 0;
  font-family: 'Segoe UI', sans-serif;
  background-color: #eaf4fc; /* bleu très clair */
  color: #003366; /* bleu foncé */
  padding: 2rem;
}

.container {
  max-width: 1200px;
  margin: auto;
  background-color: #eaf4fc;
  padding: 2rem;
  border-radius: 12px;
  box-shadow: 0 0 15px rgba(0, 51, 102, 0.1); /* ombre bleue */
}

h1 {
  text-align: center;
  margin-bottom: 1.5rem;
  color: #003366;
}

.alert {
  color: #d9534f;
  font-weight: bold;
  text-align: center;
}

.success {
  color: #28a745;
  text-align: center;
  font-weight: bold;
}

.location-switch {
  display: flex;
  justify-content: center;
  gap: 1rem;
  margin: 1.5rem 0;
}

.location-switch .location-btn {
  background-color: #f8f9fa;
  color: #003366;
  border: 2px solid #007bff;
  padding: 0.6rem 1.5rem;
  border-radius: 30px;
  text-decoration: none;
  font-weight: 600;
  transition: all 0.3s ease;
}

.location-switch .location-btn:hover {
  background-color: #007bff;
  color: #fff;
}

.location-switch .location-btn.active {
  background-color: #007bff;
  color: #fff;
  box-shadow: 0 0 10px rgba(0, 123, 255, 0.4);
}

.search-actions {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.75rem;
  justify-content: center;
  margin-bottom: 1.5rem;
}

.search-input-wrapper {
  position: relative;
}

form {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.2rem;
  margin-bottom: 2rem;
}

form div {
  display: flex;
  flex-direction: column;
}

label {
  margin-bottom: 5px;
  color: #003366;
  font-weight: bold;
}

input, select {
  padding: 0.6rem;
  border-radius: 8px;
  border: 1px solid #ccc;
  background-color: #ffffff;
  color: #000;
  font-size: 1rem;
}

form button[type="submit"] {
  grid-column: span 2;
  background-color: var(--primary-color, #1d4ed8);
  color: #fff;
  border: none;
  padding: 0.8rem;
  border-radius: 30px;
  font-size: 1rem;
  cursor: pointer;
  transition: background-color 0.3s ease, box-shadow 0.3s ease;
}

form button[type="submit"]:hover {
  background-color: var(--primary-dark, #1e3a8a);
  box-shadow: 0 6px 15px rgba(29, 78, 216, 0.25);
}

#searchInput {
  padding: 0.6rem;
  border-radius: 8px;
  border: 1px solid #ccc;
  width: 300px;
  max-width: 100%;
  font-size: 1rem;
  margin-right: 0;
  padding-right: 2.5rem;
}

.search-clear-btn {
  position: absolute;
  right: 0.6rem;
  top: 50%;
  transform: translateY(-50%);
  background: transparent;
  border: none;
  color: #6c757d;
  cursor: pointer;
  font-size: 1rem;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
}

.search-clear-btn:hover {
  color: #dc3545;
}

.action-btn {
  background-color: var(--primary-color, #1d4ed8);
  color: #fff;
  border: none;
  padding: 0.6rem 1.5rem;
  font-size: 1rem;
  border-radius: 30px;
  cursor: pointer;
  transition: background-color 0.3s ease, box-shadow 0.3s ease;
}

a.action-btn {
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.action-btn:hover,
a.action-btn:hover {
  background-color: var(--primary-dark, #1e3a8a);
  box-shadow: 0 6px 15px rgba(29, 78, 216, 0.25);
}

.action-btn.secondary {
  background-color: var(--primary-dark, #1e3a8a);
  color: #fff;
}

.action-btn.secondary:hover,
a.action-btn.secondary:hover {
  background-color: var(--primary-color, #1d4ed8);
  color: #fff;
  box-shadow: 0 6px 15px rgba(29, 78, 216, 0.25);
}

.action-btn.toggle-edit.active {
  background-color: #15803d;
  box-shadow: 0 6px 15px rgba(21, 128, 61, 0.25);
}

.action-btn.toggle-edit.active:hover {
  background-color: #166534;
}

.add-section-actions {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 1rem;
}

table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 1.5rem;
  background-color: #eaf4fc;
  color: #000;
  border-radius: 8px;
  overflow: hidden;
}

th, td {
  padding: 0.75rem;
  text-align: center;
  border: 1px solid #ddd;
}

th {
  background-color: #007bff;
  color: white;
  cursor: pointer;
}

tr:nth-child(even) {
  background-color: #f2f8ff;
}

table td button {
  background: none;
  border: none;
  font-size: 1.1rem;
  cursor: pointer;
  margin: 0 4px;
  color: #007bff;
}

table td button:hover {
  color: #0056b3;
}

.hidden { display: none; }

a.logout {
  float: right;
  color: #fff;
  text-decoration: none;
  font-weight: bold;
  background-color: #007bff;
  padding: 0.5rem 1rem;
  border-radius: 20px;
}

a.logout:hover {
  background-color: #0056b3;
}

a.back-home {
  display: inline-block;
  background-color: #007bff;
  color: #fff;
  text-decoration: none;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  margin-bottom: 1rem;
  font-weight: bold;
}

a.back-home:hover {
  background-color: #0056b3;
}

  </style>
</head>
<body>

<?php $baseUrl = '..'; require __DIR__ . '/../partials/top_nav.php'; ?>

  <div class="container">
    <a href="accueil.php" class="back-home">← Accueil</a>
    <h1 class="titre-stock"> Gestion de stock </h1>

    <div class="location-switch">
      <?php foreach ($lieuxDisponibles as $lieu => $label): ?>
        <a class="location-btn <?= $lieu === $currentLieu ? 'active' : '' ?>" href="dashboard.php?lieu=<?= urlencode($lieu) ?>&section=consulter">
          <?= htmlspecialchars($label) ?>
        </a>
      <?php endforeach; ?>
    </div>

    <?php if (isset($_GET['error'])): ?>
      <?php if ($_GET['error'] === 'exist'): ?>
        <p class="alert">❌ Cette référence existe déjà dans la base de données.</p>
      <?php elseif ($_GET['error'] === 'forbidden'): ?>
        <p class="alert">❌ Seul un administrateur peut modifier ou supprimer une pièce.</p>
      <?php endif; ?>
    <?php endif; ?>

    <?php if (isset($_GET['added'])): ?>
      <p class="success">✅ Élément ajouté avec succès.</p>
    <?php endif; ?>

    <?php if (isset($_GET['updated'])): ?>
      <p class="success">✅ Élément mis à jour avec succès.</p>
    <?php endif; ?>

    <div id="section-ajouter" class="hidden">
      <div class="add-section-actions">
        <button type="button" class="action-btn secondary" onclick="showSection('consulter')">↩️ Retour à la liste</button>
      </div>
      <form method="POST" action="ajouter_stock.php">
        <div><label>Groupe :</label>
          <select id="groupe" name="groupe" required>
            <option value="">-- Choisir --</option>
            <option>Reprographe</option>
            <option>Numériseur</option>
            <option>Capteur plan</option>
          </select>
        </div>
        <div><label>Famille :</label>
          <select id="famille" name="famille" required></select>
        </div>
        <div><label>Référence :</label>
  <select id="reference" name="reference" onchange="handleReferenceChange()" required>
    <option value="">-- Choisir dans la liste ou ajouter --</option>
    <?php foreach ($references as $ref => $des) echo "<option value=\"$ref\">$ref</option>"; ?>
    <option value="autre">Autre (ajouter manuellement)</option>
  </select>
</div>

<div id="other-reference-container" class="hidden">
  <label>Nouvelle Référence :</label>
  <input type="text" name="new_reference" id="new_reference" placeholder="Ex: REF1234">
</div>

<div><label>Désignation :</label>
  <input type="text" id="designation" name="designation" placeholder="Désignation" required readonly>
</div>

<div id="other-designation-container" class="hidden">
  <label>Nouvelle Désignation :</label>
  <input type="text" name="new_designation" id="new_designation" placeholder="Ex: Carte mère XYZ">
</div>

        <div><label>Quantité :</label>
          <input type="number" name="quantite" min="1" required>
        </div>
        <div><label>Emplacement :</label>
          <input type="text" name="emplacement" required>
        </div>
        <div><label>Lieu :</label>
          <select name="lieu" required>
            <?php foreach (array_keys($lieuxDisponibles) as $lieuOption): ?>
              <option value="<?= htmlspecialchars($lieuOption) ?>" <?= $lieuOption === $currentLieu ? 'selected' : '' ?>>
                <?= htmlspecialchars($lieuOption) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div><label>État :</label>
          <select name="etat" required>
            <option value="">-- Choisir --</option>
            <option>OK</option>
            <option>NO</option>
            <option>à vérifier</option>
            <option>Pièce de rechange</option>
          </select>
        </div>
        <button type="submit">✅ Ajouter</button>
      </form>
    </div>

    <div id="section-consulter" class="hidden">
      <div class="search-actions">
        <div class="search-input-wrapper">
          <input id="searchInput" placeholder="🔍 Rechercher..." />
          <button type="button" class="search-clear-btn" onclick="resetSearch()" aria-label="Réinitialiser la recherche">✖</button>
        </div>
        <?php if ($isAdmin): ?>
          <button type="button" class="action-btn toggle-edit" id="toggle-edit-mode" aria-pressed="false">🛠️ Modifier l'emplacement</button>
        <?php endif; ?>
        <button type="button" class="action-btn" onclick="showSection('ajouter')">➕ Ajouter</button>
        <button type="button" class="action-btn secondary" onclick="exportToCSV()">📗 CSV</button>
        <button type="button" class="action-btn secondary" onclick="exportToPDF()">📄 PDF</button>
      </div>


      <table id="stockTableContainer" class="hidden">
        <thead><tr>
          <th>Groupe</th><th>Famille</th><th>Réf</th><th>Désignation</th>
          <th>Qté</th><th>Emplacement</th><th>Lieu</th><th>État</th><?php if ($isAdmin): ?><th id="actions-header" class="hidden">Actions</th><?php endif; ?>
        </tr></thead>
        <tbody id="stockTable"></tbody>
      </table>
    </div>
  </div>
<div id="editModal" class="modal hidden">
  <div class="modal-content">
    <h3>Modifier l'emplacement</h3>
    <p class="modal-description">Choisissez le nouvel emplacement pour l'article sélectionné.</p>
    <div class="modal-stock-summary" id="edit-stock-summary"></div>
    <form id="editForm" method="POST" action="edit_stock.php">
      <input type="hidden" name="id" id="edit-id">
      <input type="hidden" name="current_lieu" id="edit-current-lieu" value="<?= htmlspecialchars($currentLieu) ?>">
      <label for="edit-emplacement">Nouvel emplacement :
        <input type="text" name="emplacement" id="edit-emplacement" required>
      </label>
      <div class="modal-actions">
        <button type="submit">✅ Enregistrer</button>
        <button type="button" onclick="closeModal()">❌ Annuler</button>
      </div>
    </form>
  </div>
</div>


<style>
.modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); justify-content: center; align-items: center; padding: 1rem; }
.modal-content { background: #fff; padding: 24px; border-radius: 12px; width: min(420px, calc(100% - 2rem)); box-shadow: 0 18px 40px rgba(15, 23, 42, 0.25); }
.modal-content label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #1f2937; }
.modal-content input[type="text"] { width: 100%; padding: 0.7rem; border-radius: 8px; border: 1px solid #cbd5f5; font-size: 1rem; }
.modal-content input[type="text"]:focus { outline: 2px solid var(--primary-color, #1d4ed8); outline-offset: 2px; }
.modal.show { display: flex; }
.modal-description { margin: 0 0 0.5rem; color: #334155; font-size: 0.95rem; }
.modal-stock-summary { margin: 0 0 1rem; padding: 0.75rem 1rem; border-radius: 10px; background: #eef2ff; color: #1e293b; font-weight: 600; line-height: 1.4; box-shadow: inset 0 0 0 1px rgba(79, 70, 229, 0.1); }
.modal-actions { display: flex; gap: 0.75rem; margin-top: 1.25rem; }
.modal-actions button { flex: 1; border: none; border-radius: 30px; padding: 0.75rem 1rem; font-size: 1rem; cursor: pointer; transition: background-color 0.3s ease, box-shadow 0.3s ease; }
.modal-actions button[type="submit"] { background-color: var(--primary-color, #1d4ed8); color: #fff; }
.modal-actions button[type="submit"]:hover { background-color: var(--primary-dark, #1e3a8a); box-shadow: 0 10px 25px rgba(29, 78, 216, 0.25); }
.modal-actions button[type="button"] { background-color: #e2e8f0; color: #0f172a; }
.modal-actions button[type="button"]:hover { background-color: #cbd5e1; }
</style>


  <script>
    const currentLieu = <?= json_encode($currentLieu) ?>;
    const references = <?= json_encode($references) ?>;
    const allStocks = <?= json_encode($stocks) ?>;
    const isAdmin = <?= json_encode($isAdmin) ?>;

    let adminEditMode = false;
    let lastRenderedStocks = Array.from(allStocks);

    function updateDesignation() {
      const referenceSelect = document.getElementById("reference");
      const designationInput = document.getElementById("designation");
      if (!referenceSelect || !designationInput) {
        return;
      }
      designationInput.value = references[referenceSelect.value] || "";
    }

    const famillesParGroupe = {
      "Reprographe": ["DV5700", "DV5950", "DV6950"],
      "Numériseur": ["CR CLASSIC", "CRVITA FLEX", "CR VITA"],
      "Capteur plan": ["LUX", "FOCUS", "DRX"]
    };

    const groupeSelect = document.getElementById("groupe");
    if (groupeSelect) {
      groupeSelect.addEventListener("change", function () {
        const options = famillesParGroupe[this.value] || [];
        const famille = document.getElementById("famille");
        if (!famille) {
          return;
        }
        famille.innerHTML = '<option value="">-- Choisir --</option>';
        options.forEach(f => {
          famille.innerHTML += `<option>${f}</option>`;
        });
      });
    }

    const editCurrentLieuInput = document.getElementById("edit-current-lieu");
    if (editCurrentLieuInput) {
      editCurrentLieuInput.value = currentLieu;
    }

    function updateActionsHeader(show) {
      const header = document.getElementById("actions-header");
      if (header) {
        header.classList.toggle("hidden", !show);
      }
    }

    function updateAdminModeUI() {
      const toggleButton = document.getElementById("toggle-edit-mode");
      if (toggleButton) {
        toggleButton.classList.toggle("active", adminEditMode);
        toggleButton.setAttribute("aria-pressed", adminEditMode ? "true" : "false");
        toggleButton.textContent = adminEditMode
          ? "🔒 Terminer la modification"
          : "🛠️ Modifier l'emplacement";
      }
      document.body.classList.toggle("admin-edit-active", adminEditMode);
    }

    function setAdminEditMode(enabled) {
      if (!isAdmin) {
        return;
      }
      const shouldEnable = Boolean(enabled);
      if (adminEditMode === shouldEnable) {
        updateAdminModeUI();
        updateActionsHeader(isAdmin && adminEditMode);
        return;
      }
      adminEditMode = shouldEnable;
      updateAdminModeUI();
      renderTable(lastRenderedStocks);
    }

    function showSection(section) {
      const ajouterSection = document.getElementById("section-ajouter");
      const consulterSection = document.getElementById("section-consulter");
      if (ajouterSection && consulterSection) {
        ajouterSection.classList.add("hidden");
        consulterSection.classList.add("hidden");
        const target = document.getElementById(`section-${section}`);
        if (target) {
          target.classList.remove("hidden");
        }
      }
      if (section === "consulter") {
        resetSearch();
      } else if (adminEditMode) {
        setAdminEditMode(false);
      }
    }

    function renderTable(data) {
      const tbody = document.getElementById("stockTable");
      const container = document.getElementById("stockTableContainer");
      if (!tbody || !container) {
        return;
      }

      const records = Array.isArray(data) ? data : [];
      lastRenderedStocks = records;

      const showActions = isAdmin && adminEditMode;
      updateActionsHeader(showActions);

      tbody.innerHTML = "";
      const columnCount = showActions ? 9 : 8;
      if (!records.length) {
        tbody.innerHTML = `<tr><td colspan="${columnCount}">Aucun article trouvé pour ${currentLieu}.</td></tr>`;
      } else {
        const rows = records.map(s => {
          const encodedStock = encodeURIComponent(JSON.stringify(s));
          const lieu = s.lieu ?? currentLieu;
          const actionsHtml = showActions ? `
              <td class="actions-column">
                <button type="button" class="edit-stock-btn" data-stock="${encodedStock}" aria-label="Modifier l'emplacement">
                  <i class="fas fa-location-dot fa-lg" style="color:#2563eb;"></i>
                </button>
              </td>` : '';
          return `
            <tr>
              <td>${s.groupe ?? ""}</td><td>${s.famille ?? ""}</td><td>${s.reference ?? ""}</td>
              <td>${s.designation ?? ""}</td><td>${s.quantite ?? ""}</td>
              <td>${s.emplacement ?? ""}</td><td>${lieu}</td><td>${s.etat ?? ""}</td>
              ${actionsHtml}
            </tr>`;
        });
        tbody.innerHTML = rows.join("");
      }

      container.classList.remove("hidden");
      if (showActions) {
        attachActionHandlers();
      }
    }

    function resetSearch() {
      const searchField = document.getElementById("searchInput");
      if (searchField) {
        searchField.value = "";
        searchField.focus();
      }
      renderTable(allStocks);
    }

    const searchInput = document.getElementById("searchInput");
    if (searchInput) {
      searchInput.addEventListener("keyup", () => {
        const q = searchInput.value.trim().toLowerCase();
        if (!q) {
          renderTable(allStocks);
          return;
        }
        const results = allStocks.filter(s => {
          const fields = [s.reference, s.designation, s.groupe, s.famille, s.emplacement, s.lieu, s.etat];
          return fields.some(field => (field ?? "").toString().toLowerCase().includes(q));
        });
        renderTable(results);
      });
    }

    function exportToCSV() {
      let csv = '\uFEFFGroupe;Famille;Référence;Désignation;Quantité;Emplacement;Lieu;État\n';
      allStocks.forEach(s => {
        const lieu = s.lieu ?? currentLieu;
        csv += [s.groupe, s.famille, s.reference, s.designation, s.quantite, s.emplacement, lieu, s.etat]
          .map(v => `"${(v ?? "").toString().replace(/"/g, '""')}"`).join(";") + "\n";
      });
      const blob = new Blob([csv], { type: "text/csv" });
      const link = document.createElement("a");
      link.href = URL.createObjectURL(blob);
      link.download = `stock_${currentLieu.toLowerCase().replace(/\s+/g, '-')}.csv`;
      link.click();
    }

    function exportToPDF() {
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF({ orientation: "landscape", unit: "mm", format: "a4" });
      doc.setFontSize(12);
      doc.text(`Gestion de Stock - ${currentLieu}`, 14, 14);
      doc.autoTable({
        startY: 22,
        head: [["Groupe", "Famille", "Référence", "Désignation", "Quantité", "Emplacement", "Lieu", "État"]],
        body: allStocks.map(s => [
          s.groupe ?? "",
          s.famille ?? "",
          s.reference ?? "",
          s.designation ?? "",
          s.quantite ?? "",
          s.emplacement ?? "",
          s.lieu ?? currentLieu,
          s.etat ?? ""
        ]),
        styles: {
          fontSize: 9,
          cellPadding: 3
        },
        columnStyles: {
          0: { cellWidth: 30 },
          1: { cellWidth: 30 },
          2: { cellWidth: 30 },
          3: { cellWidth: 70 },
          4: { cellWidth: 22, halign: "center" },
          5: { cellWidth: 32 },
          6: { cellWidth: 32 },
          7: { cellWidth: 22, halign: "center" }
        },
        margin: { left: 14, right: 14 },
        tableWidth: "auto"
      });
      doc.save(`stock_${currentLieu.toLowerCase().replace(/\s+/g, '-')}.pdf`);
    }

    function closeModal() {
      const modal = document.getElementById('editModal');
      if (modal) {
        modal.classList.remove('show');
        modal.classList.add('hidden');
      }
    }
    window.closeModal = closeModal;

    function attachActionHandlers() {
      const editButtons = document.querySelectorAll('.edit-stock-btn');
      editButtons.forEach(button => {
        button.addEventListener('click', () => {
          const { stock: encodedStock } = button.dataset;
          if (!encodedStock) {
            return;
          }
          try {
            const stock = JSON.parse(decodeURIComponent(encodedStock));
            if (stock) {
              openEditModal(stock);
            }
          } catch (error) {
            console.error('Impossible de préparer la modification du stock.', error);
            alert("Une erreur est survenue lors de l'ouverture de la fenêtre d'édition.");
          }
        });
      });
    }

    function openEditModal(stock) {
      if (!isAdmin || !adminEditMode) {
        return;
      }
      const idInput = document.getElementById('edit-id');
      if (idInput) {
        idInput.value = stock.id;
      }
      const emplacementInput = document.getElementById('edit-emplacement');
      if (emplacementInput) {
        emplacementInput.value = stock.emplacement ?? "";
        emplacementInput.focus({ preventScroll: true });
        emplacementInput.select();
      }
      const lieuInput = document.getElementById('edit-current-lieu');
      if (lieuInput) {
        lieuInput.value = currentLieu;
      }
      const summary = document.getElementById('edit-stock-summary');
      if (summary) {
        const details = [];
        if (stock.reference) {
          details.push(`Réf : ${stock.reference}`);
        }
        if (stock.designation) {
          details.push(stock.designation);
        }
        if (stock.emplacement) {
          details.push(`Emplacement actuel : ${stock.emplacement}`);
        }
        const lieu = stock.lieu ?? currentLieu;
        if (lieu) {
          details.push(`Lieu : ${lieu}`);
        }
        summary.textContent = details.join(' • ');
      }
      const modal = document.getElementById('editModal');
      if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('show');
      }
    }
    window.openEditModal = openEditModal;

    const toggleEditButton = document.getElementById('toggle-edit-mode');
    if (toggleEditButton) {
      toggleEditButton.addEventListener('click', () => {
        setAdminEditMode(!adminEditMode);
      });
    }

    window.addEventListener("DOMContentLoaded", () => {
      const defaultSection = "<?= $defaultSection ?>";
      if (defaultSection) {
        showSection(defaultSection);
      } else {
        showSection("consulter");
      }
      updateAdminModeUI();
    });

    document.querySelectorAll(".action-btn").forEach(btn => {
      btn.addEventListener("click", () => {
        document.querySelectorAll(".alert, .success").forEach(el => el.remove());
      });
    });

    setTimeout(() => {
      document.querySelectorAll(".alert, .success").forEach(el => el.remove());
    }, 5000);

    function handleReferenceChange() {
      const select = document.getElementById("reference");
      const ref = select ? select.value : "";
      const designationField = document.getElementById("designation");
      const otherRefContainer = document.getElementById("other-reference-container");
      const otherDesContainer = document.getElementById("other-designation-container");

      if (!designationField || !otherRefContainer || !otherDesContainer) {
        return;
      }

      if (ref === "autre") {
        designationField.value = "";
        designationField.setAttribute("readonly", true);
        otherRefContainer.classList.remove("hidden");
        otherDesContainer.classList.remove("hidden");
      } else if (ref === "") {
        designationField.value = "";
        designationField.removeAttribute("readonly");
        otherRefContainer.classList.add("hidden");
        otherDesContainer.classList.add("hidden");
      } else {
        designationField.value = references[ref] || "";
        designationField.removeAttribute("readonly");
        otherRefContainer.classList.add("hidden");
        otherDesContainer.classList.add("hidden");
      }
    }
  </script>


<div class="menu-actions" style="margin:20px 0; text-align:center;">
    <a href="<?= htmlspecialchars($consultationPage, ENT_QUOTES) ?>?lieu=<?= urlencode($currentLieu) ?>&section=consulter"
       style="background:#007bff; color:white; padding:10px 20px;
              border-radius:8px; text-decoration:none; font-weight:bold;">
        🗂️ Voir les emplacements en 3D
    </a>
</div>





</body>
</html>

</body> 
