
// ─── Popup ───────────────────────────────────────────────────
function showPopup(message) {
  document.getElementById("popupText").innerText = message;
  document.getElementById("popup").style.display = "flex";
}

function closePopup() {
  document.getElementById("popup").style.display = "none";
}

// ─── Ajout / Suppression bébé 2 ──────────────────────────────
let babyCount = 1;

document.getElementById("add-baby").addEventListener("click", function () {
  if (babyCount === 2) {
    const existing = document.getElementById("baby2");
    if (existing) existing.remove();
    babyCount = 1;
    this.value = "Ajouter un autre Bébé";
    return;
  }

  if (!verifierBebe(1)) return;

  
  const originalBaby = document.getElementById("baby1");
  const newBaby = originalBaby.cloneNode(true);
  newBaby.id = "baby2";

  const title = newBaby.querySelector("h5");
  if (title) title.textContent = "Information Bébé 2";

  newBaby.querySelectorAll("input").forEach((input) => {
    if (input.type === "radio") {
      input.checked = false;
      input.name = input.name.replace(/\d+$/, "2");
      input.id = input.id.replace(/\d+$/, "2");
    } else {
      input.value = "";
      const oldName = input.name || input.id;
      input.name = oldName.replace(/\d+$/, "2");
      input.id = input.id.replace(/\d+$/, "2");
    }
  });

  newBaby.querySelector("#D_bebe2").value =
    document.getElementById("D_bebe1").value;
  newBaby.querySelector("#L_bebe2").value =
    document.getElementById("L_bebe1").value;

  originalBaby.insertAdjacentElement("afterend", newBaby);
  babyCount = 2;
  this.value = "Supprimer le 2ème Bébé";
});

// ─── Reset ───────────────────────────────────────────────────
function resetBabySection() {
  const baby2 = document.getElementById("baby2");
  if (baby2) baby2.remove();
  babyCount = 1;
  document.getElementById("add-baby").value = "Ajouter un autre Bébé";
}

// ─── Validation d'un bébé ────────────────────────────────────
function verifierBebe(i) {
  const nameRegex = /^[A-Za-zÀ-ÿ\s]+$/;
  const nom = document.getElementById("P_bebe" + i).value.trim();
  const date = document.getElementById("D_bebe" + i).value;
  const lieu = document.getElementById("L_bebe" + i).value.trim();
  const garcon = document.getElementById("garcon" + i);
  const fille = document.getElementById("fille" + i);

  if (nom === "" || !nameRegex.test(nom)) {
    showPopup("Nom du bébé " + i + " invalide.");
    return false;
  }
  if (date === "") {
    showPopup("Date de naissance du bébé " + i + " obligatoire.");
    return false;
  }
  if (lieu === "" || !nameRegex.test(lieu)) {
    showPopup("Lieu de naissance du bébé " + i + " invalide.");
    return false;
  }
  if (!garcon.checked && !fille.checked) {
    showPopup("Choisir le sexe du bébé " + i + ".");
    return false;
  }
  return true;
}

// ─── Soumission ──────────────────────────────────────────────
async function verifier(event) {
  event.preventDefault();

  const nameRegex = /^[A-Za-zÀ-ÿ\s]+$/;
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  const cinRegex = /^[0-9]{8}$/;
  const telRegex = /^[24579][0-9]{7}$/;

  for (let i = 1; i <= babyCount; i++) {
    if (!verifierBebe(i)) return;
  }

  if (babyCount === 2) {
    if (
      document.getElementById("D_bebe1").value !==
      document.getElementById("D_bebe2").value
    ) {
      showPopup("Jumeaux requis : même DATE de naissance.");
      return;
    }
    if (
      document.getElementById("L_bebe1").value.toLowerCase() !==
      document.getElementById("L_bebe2").value.toLowerCase()
    ) {
      showPopup("Jumeaux requis : même LIEU de naissance.");
      return;
    }
  }

  const parent = document.getElementById("P_parent").value.trim();
  const email = document.getElementById("Adr_mail").value.trim();
  const cin = document.getElementById("N_cin").value.trim();
  const mdp = document.getElementById("mdp").value;
  const conf = document.getElementById("Conf_mdp").value;
  const tel = document.getElementById("tel").value.trim();

  if (parent === "" || !nameRegex.test(parent)) {
    showPopup("Nom du parent invalide.");
    return;
  }
  if (tel === "" || !telRegex.test(tel)) {
    showPopup("Numéro de téléphone invalide.");
    return;
  }
  if (email !== "" && !emailRegex.test(email)) {
    showPopup("Email invalide.");
    return;
  }
  if (!cinRegex.test(cin)) {
    showPopup("CIN invalide (8 chiffres).");
    return;
  }
  if (mdp.length < 6) {
    showPopup("Mot de passe faible (min 6 caractères).");
    return;
  }
  if (mdp !== conf) {
    showPopup("Les mots de passe ne correspondent pas.");
    return;
  }

  const formData = new FormData();
  formData.append("baby_count", babyCount);
  formData.append("P_parent", parent);
  formData.append("Adr_mail", email);
  formData.append("N_cin", cin);
  formData.append("N_cnam", document.getElementById("N_cnam").value.trim());
  formData.append("tel", tel);
  formData.append("mdp", mdp);
  formData.append("Conf_mdp", conf);

  for (let i = 1; i <= babyCount; i++) {
    formData.append(
      "P_bebe" + i,
      document.getElementById("P_bebe" + i).value.trim()
    );
    formData.append("D_bebe" + i, document.getElementById("D_bebe" + i).value);
    formData.append(
      "L_bebe" + i,
      document.getElementById("L_bebe" + i).value.trim()
    );
    const garconEl = document.getElementById("garcon" + i);
    formData.append("sexe" + i, garconEl.checked ? "garcon" : "fille");
  }

  // ✅ Envoi vers le fichier PHP séparé
  try {
    const response = await fetch("traitement_inscription.php", {
      method: "POST",
      body: formData,
    });
    const result = await response.json();
    showPopup(result.message);
    if (result.success) {
      event.target.reset();
      resetBabySection();
      setTimeout(() => {
        window.location.href = "connexion.php";
      }, 1800);
    }
  } catch (err) {
    showPopup("Erreur réseau. Veuillez réessayer.");
    console.error(err);
  }
}
