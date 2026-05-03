function showPopup(message) {
  document.getElementById("popupText").innerText = message;
  document.getElementById("popup").style.display = "flex";
}

function closePopup() {
  document.getElementById("popup").style.display = "none";
}

function verifier(event) {
  event.preventDefault();
  const nom = document.getElementById("nom").value;
  const prenom = document.getElementById("prenom").value;
  const email = document.getElementById("email").value;
  const tel = document.getElementById("tel").value;
  const dateNaissance = document.getElementById("date").value;
  const password = document.getElementById("mdp").value;
  const confirmPassword = document.getElementById("cmdp").value;
  const numOrdre = document.getElementById("num_inscri").value;
  const diplome = document.getElementById("diplom").value;
  const nomCabinet = document.getElementById("nom_cabin").value;
  const adresse = document.getElementById("adr").value;
  const codePostal = document.getElementById("code_post").value;
  const telCabinet = document.getElementById("tel_cabin").value;

  const nameRegex = /^[A-Za-zÀ-ÿ\s]+$/;
  if (!nameRegex.test(nom) || !nameRegex.test(prenom)) {
    showPopup("Nom et prénom doivent contenir uniquement des lettres.");
    return false;
  }

  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(email)) {
    showPopup("Email invalide.");
    return false;
  }

  const phoneRegex = /^[2-9][0-9]{7}$/;
  if (!phoneRegex.test(tel)) {
    showPopup("Numéro de téléphone invalide.");
    return false;
  }

  const today = new Date();
  const birthDate = new Date(dateNaissance);
  const age = today.getFullYear() - birthDate.getFullYear();

  if (age < 25 || age > 80) {
    showPopup("Date de naissance incohérente pour un médecin.");
    return false;
  }

  const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;
  if (!passwordRegex.test(password)) {
    showPopup("Mot de passe faible (min 8 caractères, majuscule, minuscule, chiffre).");
    return false;
  }

  if (password !== confirmPassword) {
    showPopup("La confirmation de mots de passe ne correspond pas au mot de passe donné.");
    return false;
  }

  const ordreRegex = /^[A-Z0-9-]{5,30}$/;
  if (!ordreRegex.test(numOrdre)) {
    showPopup("Numéro d'inscription invalide.");
    return false;
  }

  const textRegex = /^[A-Za-zÀ-ÿ0-9\s,.()-]*$/;
  if (diplome && !textRegex.test(diplome)) {
    showPopup("Diplôme invalide.");
    return false;
  }

  if (!nameRegex.test(nomCabinet)) {
    showPopup("Nom du cabinet invalide.");
    return false;
  }

  if (adresse.length < 5) {
    showPopup("Adresse trop courte.");
    return false;
  }

  const codePostalRegex = /^[0-9]{4}$/;
  if (!codePostalRegex.test(codePostal)) {
    showPopup("Code postal invalide.");
    return false;
  }

  const fixeRegex = /^[2-9][0-9]{7}$/;
  if (telCabinet && !fixeRegex.test(telCabinet)) {
    showPopup("Téléphone du cabinet invalide.");
    return false;
  }

  // --- Toutes les validations sont ok, on envoie au serveur ---
  const formData = new FormData(document.getElementById("form"));

  fetch("inscrir_pediatre.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.text())
    .then((text) => {
      let data;
      try {
        // Extraire uniquement la partie JSON si du HTML est mélangé
        const jsonMatch = text.match(/\{[\s\S]*\}/);
        if (!jsonMatch) throw new Error("Pas de JSON dans la réponse");
        data = JSON.parse(jsonMatch[0]);
      } catch (e) {
        console.error("Réponse brute du serveur :", text);
        showPopup("Erreur serveur. Consultez la console pour plus de détails.");
        return;
      }
      showPopup(data.message);
      if (data.succes) {
        document.getElementById("form").reset();
        setTimeout(() => {
          window.location.href = "connexion.php?role=medecin";
        }, 1800);
      }
    })
    .catch((err) => {
      showPopup("Erreur réseau. Veuillez réessayer.");
      console.error(err);
    });
}