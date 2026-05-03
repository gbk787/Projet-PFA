let donnees = [];

let chartPoids = null;
let chartTaille = null;

function initialiser() {
    chargerDonneesLocales();
    afficherTableau();
    afficherGraphiques();
    afficherStatistiques();
}

function chargerDonneesLocales() {
    const donneesStockees = localStorage.getItem('croissance_donnees');

    if (donneesStockees) {
        try {
            donnees = JSON.parse(donneesStockees) || [];
        } catch (e) {
            donnees = [];
        }
    } else {
        donnees = [];
    }

    donnees.sort((a, b) => a.mois - b.mois);
}

function sauvegarderDonnees() {
    localStorage.setItem('croissance_donnees', JSON.stringify(donnees));
}

/* ══════════════════ TABLE ══════════════════ */
function afficherTableau() {
    const tbody = document.getElementById('tableBody');
    if (!tbody) return;

    if (donnees.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="3" style="text-align:center; color:#94a3b8; padding:30px 16px; font-style:italic;">
                    Aucune mesure enregistrée. Ajoutez une mesure ci-dessous.
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = donnees.map((d) => `
        <tr>
            <td>Mois ${d.mois}</td>
            <td>${d.poids} kg</td>
            <td>${d.taille} cm</td>
        </tr>
    `).join('');
}

/* ══════════════════ CHARTS ══════════════════ */
function getAxisMaxX() {
    if (donnees.length === 0) return 12;
    const maxMois = Math.max(...donnees.map(d => d.mois));
    return Math.max(12, maxMois); // never smaller than 12, expand if data goes further
}

function afficherGraphiques() {
    if (typeof Chart === 'undefined') {
        console.error('Chart.js non chargé');
        return;
    }

    const canvasPoids = document.getElementById('chartPoids');
    const canvasTaille = document.getElementById('chartTaille');

    if (!canvasPoids || !canvasTaille) {
        console.error('Canvas introuvable');
        return;
    }

    const pointsPoids = donnees.map(d => ({ x: d.mois, y: d.poids }));
    const pointsTaille = donnees.map(d => ({ x: d.mois, y: d.taille }));

    const xMax = getAxisMaxX();

    // Shared X scale
    const xScaleConfig = {
        type: 'linear',
        min: 1,
        max: xMax,
        ticks: {
            stepSize: 1,
            callback: function (value) {
                return Number.isInteger(value) ? `M${value}` : '';
            },
            font: { size: 11 },
            color: '#64748b'
        },
        title: {
            display: true,
            text: 'Âge (mois)',
            font: { size: 12, weight: '600' },
            color: '#5a6e7c',
            padding: { top: 8 }
        },
        grid: { color: 'rgba(15, 23, 42, 0.06)' },
        border: { color: 'rgba(15, 23, 42, 0.1)' }
    };

    // Shared options
    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        layout: { padding: { top: 10, right: 16, bottom: 4, left: 4 } },
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#0a2540',
                titleColor: '#ffffff',
                bodyColor: '#e0e7ff',
                titleFont: { size: 13, weight: '600' },
                bodyFont: { size: 12 },
                padding: 12,
                cornerRadius: 10,
                displayColors: false,
                borderColor: 'rgba(255,255,255,0.1)',
                borderWidth: 1
            }
        }
    };

    // ---- COURBE POIDS ----
    const ctxPoids = canvasPoids.getContext('2d');
    if (chartPoids) chartPoids.destroy();

    const gradPoids = ctxPoids.createLinearGradient(0, 0, 0, 380);
    gradPoids.addColorStop(0, 'rgba(43, 110, 158, 0.28)');
    gradPoids.addColorStop(1, 'rgba(43, 110, 158, 0.02)');

    chartPoids = new Chart(ctxPoids, {
        type: 'line',
        data: {
            datasets: [{
                label: 'Poids',
                data: pointsPoids,
                parsing: false,
                borderColor: '#2b6e9e',
                backgroundColor: gradPoids,
                borderWidth: 3,
                fill: true,
                tension: 0.3,
                pointRadius: 5,
                pointHoverRadius: 8,
                pointBackgroundColor: '#2b6e9e',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2
            }]
        },
        options: {
            ...commonOptions,
            plugins: {
                ...commonOptions.plugins,
                tooltip: {
                    ...commonOptions.plugins.tooltip,
                    callbacks: {
                        title: (ctx) => `Mois ${ctx[0].parsed.x}`,
                        label: (ctx) => `Poids : ${ctx.parsed.y} kg`
                    }
                }
            },
            scales: {
                x: xScaleConfig,
                y: {
                    beginAtZero: false,
                    grace: '15%',
                    title: {
                        display: true,
                        text: 'Poids (kg)',
                        font: { size: 12, weight: '600' },
                        color: '#5a6e7c'
                    },
                    ticks: { font: { size: 11 }, color: '#64748b' },
                    grid: { color: 'rgba(15, 23, 42, 0.06)' },
                    border: { color: 'rgba(15, 23, 42, 0.1)' }
                }
            }
        }
    });

    // ---- COURBE TAILLE ----
    const ctxTaille = canvasTaille.getContext('2d');
    if (chartTaille) chartTaille.destroy();

    const gradTaille = ctxTaille.createLinearGradient(0, 0, 0, 380);
    gradTaille.addColorStop(0, 'rgba(91, 163, 217, 0.30)');
    gradTaille.addColorStop(1, 'rgba(91, 163, 217, 0.02)');

    chartTaille = new Chart(ctxTaille, {
        type: 'line',
        data: {
            datasets: [{
                label: 'Taille',
                data: pointsTaille,
                parsing: false,
                borderColor: '#5ba3d9',
                backgroundColor: gradTaille,
                borderWidth: 3,
                fill: true,
                tension: 0.3,
                pointRadius: 5,
                pointHoverRadius: 8,
                pointBackgroundColor: '#5ba3d9',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2
            }]
        },
        options: {
            ...commonOptions,
            plugins: {
                ...commonOptions.plugins,
                tooltip: {
                    ...commonOptions.plugins.tooltip,
                    callbacks: {
                        title: (ctx) => `Mois ${ctx[0].parsed.x}`,
                        label: (ctx) => `Taille : ${ctx.parsed.y} cm`
                    }
                }
            },
            scales: {
                x: xScaleConfig,
                y: {
                    beginAtZero: false,
                    grace: '15%',
                    title: {
                        display: true,
                        text: 'Taille (cm)',
                        font: { size: 12, weight: '600' },
                        color: '#5a6e7c'
                    },
                    ticks: { font: { size: 11 }, color: '#64748b' },
                    grid: { color: 'rgba(15, 23, 42, 0.06)' },
                    border: { color: 'rgba(15, 23, 42, 0.1)' }
                }
            }
        }
    });
}

/* ══════════════════ STATS ══════════════════ */
function afficherStatistiques() {
    const poidsActuelEl = document.getElementById('poidsActuel');
    const tailleActuelleEl = document.getElementById('tailleActuelle');
    const gainPoidsEl = document.getElementById('gainPoids');
    const gainTailleEl = document.getElementById('gainTaille');

    if (!poidsActuelEl || !tailleActuelleEl || !gainPoidsEl || !gainTailleEl) return;

    if (donnees.length === 0) {
        poidsActuelEl.textContent = '-';
        tailleActuelleEl.textContent = '-';
        gainPoidsEl.textContent = '-';
        gainTailleEl.textContent = '-';
        return;
    }

    const poids = donnees.map(d => d.poids);
    const taille = donnees.map(d => d.taille);

    const poidsActuel = poids[poids.length - 1];
    const tailleActuelle = taille[taille.length - 1];

    const gainPoids = (poidsActuel - poids[0]).toFixed(1);
    const gainTaille = (tailleActuelle - taille[0]).toFixed(1);

    poidsActuelEl.textContent = `${poidsActuel} kg`;
    tailleActuelleEl.textContent = `${tailleActuelle} cm`;
    gainPoidsEl.textContent = donnees.length > 1 ? `+${gainPoids} kg` : '-';
    gainTailleEl.textContent = donnees.length > 1 ? `+${gainTaille} cm` : '-';
}

/* ══════════════════ ACTIONS ══════════════════ */
function ajouterMesure() {
    const mois = parseInt(document.getElementById('mois').value);
    const poids = parseFloat(document.getElementById('poids').value);
    const taille = parseFloat(document.getElementById('taille').value);

    if (!mois || !poids || !taille) {
        afficherMessage('Veuillez remplir tous les champs', 'error');
        return;
    }

    if (mois < 1 || mois > 36) {
        afficherMessage('Le mois doit être entre 1 et 36', 'error');
        return;
    }

    const index = donnees.findIndex(d => d.mois === mois);

    if (index !== -1) {
        donnees[index] = { mois, poids, taille };
        afficherMessage('Mesure mise à jour avec succès !', 'success');
    } else {
        donnees.push({ mois, poids, taille });
        donnees.sort((a, b) => a.mois - b.mois);
        afficherMessage('Nouvelle mesure ajoutée !', 'success');
    }

    sauvegarderDonnees();
    afficherTableau();
    afficherGraphiques();
    afficherStatistiques();

    document.getElementById('mois').value = '';
    document.getElementById('poids').value = '';
    document.getElementById('taille').value = '';
}

function telechargerDonnees() {
    let csv = 'Mois,Poids (kg),Taille (cm)\n';

    donnees.forEach(d => {
        csv += `${d.mois},${d.poids},${d.taille}\n`;
    });

    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'croissance_donnees.csv';
    a.click();
    window.URL.revokeObjectURL(url);

    afficherMessage('Données téléchargées !', 'success');
}

function reinitialiserDonnees() {
    if (confirm('Êtes-vous sûr ? Cette action ne peut pas être annulée. Toutes les données seront supprimées.')) {
        donnees = [];
        localStorage.removeItem('croissance_donnees');
        afficherTableau();
        afficherGraphiques();
        afficherStatistiques();
        afficherMessage('Toutes les données ont été supprimées !', 'success');
    }
}

function afficherMessage(texte, type) {
    const messageDiv = document.getElementById('message');
    if (!messageDiv) return;

    messageDiv.className = `message ${type}`;
    messageDiv.textContent = texte;

    setTimeout(() => {
        messageDiv.textContent = '';
        messageDiv.className = 'message';
    }, 3000);
}

window.addEventListener('load', initialiser);