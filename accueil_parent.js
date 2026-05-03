    // Éléments DOM
    const rdvModal = document.getElementById('rdvModal');
    const propositionModal = document.getElementById('propositionModal');
    const rdvForm = document.getElementById('rdvForm');
    const notificationDiv = document.getElementById('notification');

    // Variables pour stocker la demande en cours
    let currentRdvRequest = {
        date: '',
        time: '',
        motif: ''
    };

    // Ouvrir la modale de rendez-vous
    window.openModal = function() {
        if (rdvModal) {
            rdvModal.classList.add('show');
            rdvModal.style.display = 'flex';
            // Réinitialiser le formulaire
            rdvForm.reset();
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('rdvDate').min = today;
            document.getElementById('rdvDate').value;
            document.getElementById('rdvTime').value;
        }
    }

    // Fermer la modale de rendez-vous
    window.closeModal = function() {
        if (rdvModal) {
            rdvModal.classList.remove('show');
            rdvModal.style.display = 'none';
        }
    }

    // Fermer la modale de proposition
    window.closePropositionModal = function() {
        if (propositionModal) {
            propositionModal.classList.remove('show');
            propositionModal.style.display = 'none';
        }
    }

    // Afficher une notification toast
    function showNotification(message, type = 'success') {
        notificationDiv.innerHTML = message;
        notificationDiv.classList.add('show');
        setTimeout(() => {
            notificationDiv.classList.remove('show');
        }, 3000);
    }

    // Mettre à jour l'affichage du prochain rendez-vous dans la carte
    function updateNextAppointment(dateFormatted, time, motif, doctorName = 'Dr. Sophie Laurent') {
        const nextDateElem = document.querySelector('.card:has(.fa-calendar-check) .info-value');
        const nextMotifElem = document.querySelectorAll('.card:has(.fa-calendar-check) .info-item')[1]?.querySelector('.info-value');
        const statusBadge = document.querySelector('.card:has(.fa-calendar-check) .badge.confirmed');
        
        if (nextDateElem) {
            nextDateElem.innerHTML = `${dateFormatted} à ${time}`;
        }
        if (nextMotifElem) {
            nextMotifElem.innerHTML = motif;
        }
        if (statusBadge) {
            statusBadge.innerHTML = '<i class="fas fa-clock"></i> En attente de confirmation';
            statusBadge.classList.remove('confirmed');
            statusBadge.classList.add('pending');
        }
    }

    // Soumission du formulaire
    rdvForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const date = document.getElementById('rdvDate').value;
        const time = document.getElementById('rdvTime').value;
        const motif = document.getElementById('motif').value.trim();

        if (!date || !time || !motif) {
            showNotification('Veuillez remplir tous les champs', 'error');
            return;
        }

        // Stocker la demande
        currentRdvRequest = { date, time, motif };

        // Formater la date pour affichage
        const dateObj = new Date(date);
        const options = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
        const formattedDate = dateObj.toLocaleDateString('fr-FR', options);

        // Mise à jour de l'interface
        updateNextAppointment(formattedDate, time, motif);
        showNotification(` Demande envoyée au pédiatre – ${formattedDate} à ${time}`);

        // Fermer la modale
        closeModal();

        // Simuler une proposition de créneau après 2 secondes (optionnel)
        // Vous pouvez activer/désactiver selon votre besoin
        // simulateProposition();
    });

    // Optionnel : fonction pour simuler une proposition de créneau
    function simulateProposition() {
        setTimeout(() => {
            const propositionMsg = document.getElementById('propositionMessage');
            if (propositionMsg) {
                propositionMsg.innerHTML = `Le Dr. Sophie Laurent vous propose un créneau le <strong>${currentRdvRequest.date}</strong> à <strong>14h30</strong>.<br>Acceptez-vous ?`;
            }
            if (propositionModal) {
                propositionModal.classList.add('show');
                propositionModal.style.display = 'flex';
            }
        }, 2000);
    }

    // Gestion des boutons de proposition (à décommenter si utilisé)
    const acceptBtn = document.getElementById('acceptPropositionBtn');
    const refuseBtn = document.getElementById('btn-refuse');

    if (acceptBtn) {
        acceptBtn.addEventListener('click', () => {
            showNotification(' Rendez-vous confirmé avec le créneau proposé !');
            closePropositionModal();
            // Mettre à jour le statut du RDV
            const statusBadge = document.querySelector('.card:has(.fa-calendar-check) .badge');
            if (statusBadge) {
                statusBadge.innerHTML = '<i class="fas fa-check-circle"></i> Confirmé';
                statusBadge.classList.remove('pending');
                statusBadge.classList.add('confirmed');
            }
        });
    }

    if (refuseBtn) {
        refuseBtn.addEventListener('click', () => {
            showNotification(' Vous avez refusé le créneau. Un autre sera proposé.');
            closePropositionModal();
        });
    }

    // Fermer les modals en cliquant à l'extérieur
    window.addEventListener('click', (e) => {
        if (e.target === rdvModal) closeModal();
        if (e.target === propositionModal) closePropositionModal();
    });