let babyCount = 1; // Commence avec Bébé 1

document.getElementById("add-baby").addEventListener("click", function () {
  if (babyCount >= 4) {
    // Optionnel : désactiver le bouton après affichage
    this.disabled = true;
  }
  babyCount++; // Nouveau bébé
  const originalBaby = document.getElementById("baby1");

  // Cloner la section
  const newBaby = originalBaby.cloneNode(true);

  // Modifier l'ID de la section
  newBaby.id = "baby" + babyCount;

  // Modifier le titre
  const title = newBaby.querySelector("h5");
  title.textContent = "Information Bébé " + babyCount;

  // Vider tous les champs du clone
  const inputs = newBaby.querySelectorAll("input");
  inputs.forEach((input) => {
    if (input.type === "radio" || input.type === "checkbox") {
      input.checked = false;
    } else {
      input.value = "";
    }

    // Modifier les IDs et noms des radios pour éviter conflit
    if (input.type === "radio") {
      const baseName = input.name.replace(/\d+$/, ""); // sexe
      input.name = baseName + babyCount;
      input.id = input.id.replace(/\d+$/, babyCount);
    } else {
      input.id = input.id.replace(/\d+$/, babyCount);
    }
  });

  // Ajouter le clone après la dernière section bébé
  const lastBaby = document.getElementById("baby" + (babyCount - 1));
  lastBaby.insertAdjacentElement("afterend", newBaby);
});
