document.addEventListener('DOMContentLoaded', function() {
    const pageHeader = document.querySelector('.page-header');
    
    // 2. Insertion dans le header
    if (pageHeader) {
        pageHeader.insertAdjacentHTML('beforeend', searchHTML);
    }

    // --- Le reste de ta logique de filtrage ---
    const searchInput = document.getElementById('babyNameSearch');
    const clearButton = document.getElementById('clearBabySearch');
    const table = document.querySelector('.data-table');
    const tbody = table ? table.querySelector('tbody') : null;
    const badge = document.querySelector('.badge');

    if (!tbody) return; // Sécurité si la table n'existe pas

    const rows = Array.from(tbody.querySelectorAll('tr')).filter(row => !row.querySelector('.empty-state'));

    function filterByBabyName() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        let visibleCount = 0;
        
        rows.forEach(row => {
            const babyNameCell = row.cells[0]; // Colonne "Bébé"
            if (babyNameCell) {
                const babyName = babyNameCell.textContent.toLowerCase();
                if (searchTerm === '' || babyName.includes(searchTerm)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            }
        });

        // Mise à jour du badge
        if (badge) {
            badge.textContent = searchTerm === '' 
                ? rows.length + ' patient(s) trouvé(s)' 
                : visibleCount + ' patient(s) trouvé(s) (filtrés)';
        }
    }

    // Événements
    searchInput.addEventListener('input', filterByBabyName);
    
    clearButton.onclick = function() {
        searchInput.value = '';
        filterByBabyName();
        searchInput.focus();
    };
});