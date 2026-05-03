const pediatres=[
    {
        id:1,
        nom:"Dr. Donia Bensalem",
        specialite:"Pédiatre généraliste",
        ville:"Tunis",
        quartier:"Centre Ville",
        distance:1.2,
        rating:4.9,
        avis:48,
        disponibilite:"Demain",
        telephone:"+216 71 234 567",
        photo:"DB"
    },
    {
        id:2,
        nom:"Dr. Kamel Ayari",
        specialite:"Pédiatre - Gastroentérologie",
        ville:"La Marsa",
        quartier:"Secteur 2",
        distance:3.8,
        rating:4.6,
        avis:32,
        disponibilite:"Lundi",
        telephone:"+216 71 345 678",
        photo:"KA"
    },
    {
        id:3,
        nom:"Dr. Salma Mellouli",
        specialite:"Pédiatre - Neurologie",
        ville:"Ariana",
        quartier:"Cité Ennasr",
        distance:5.2,
        rating:4.8,
        avis:56,
        disponibilite:"Mercredi",
        telephone:"+216 71 456 789",
        photo:"SM"
    },
    {
        id:4,
        nom:"Dr. Mohamed Gharbi",
        specialite:"Pédiatre généraliste",
        ville:"Tunis",
        quartier:"Mutuelleville",
        distance:2.5,
        rating:4.7,
        avis:124,
        disponibilite:"Aujourd'hui",
        telephone:"+216 71 567 890",
        photo:"MG"
    },
    {
        id:5,
        nom:"Dr. Leila Ben Amor",
        specialite:"Pédiatre - Allergologue",
        ville:"Sousse",
        quartier:"Sahloul",
        distance:8.3,
        rating:4.9,
        avis:89,
        disponibilite:"Jeudi",
        telephone:"+216 73 123 456",
        photo:"LA"
    },
    {
        id:6,
        nom:"Dr. Nabil Saidi",
        specialite:"Pédiatre - Cardiologue",
        ville:"Tunis",
        quartier:"El Menzah",
        distance:4.1,
        rating:4.5,
        avis:67,
        disponibilite:"Mardi",
        telephone:"+216 71 678 901",
        photo:"NS"
    },
    {
        id:7,
        nom:"Dr. Amira Khedher",
        specialite:"Pédiatre généraliste",
        ville:"La Marsa",
        quartier:"Gammarth",
        distance:6.7,
        rating:4.8,
        avis:203,
        disponibilite:"Vendredi",
        telephone:"+216 71 789 012",
        photo:"AK"
    },
    {
        id:8,
        nom:"Dr. Moncef Aisseoui",
        specialite:"Pédiatre - Néonatologue",
        ville:"Ariana",
        quartier:"Ennasr 2",
        distance:5.9,
        rating:4.9,
        avis:156,
        disponibilite:"Aujourd'hui",
        telephone:"+216 71 890 123",
        photo:"MA"
    },
    {
        id:9,
        nom:"Dr. Rim Ben Hassine",
        specialite:"Pédiatre généraliste",
        ville:"Sousse",
        quartier:"Khezama",
        distance:2.1,
        rating:4.7,
        avis:78,
        disponibilite:"Demain",
        telephone:"+216 73 234 567",
        photo:"RH"
    },
    {
        id:10,
        nom:"Dr. Mehdi Trabelsi",
        specialite:"Pédiatre - Pneumologue",
        ville:"Tunis",
        quartier:"El Khadra",
        distance:3.4,
        rating:4.8,
        avis:112,
        disponibilite:"Lundi",
        telephone:"+216 71 901 234",
        photo:"MT"
    }
];
let currentPage=1;
const itemsPerPage=5;
let filteredDoctors=[];
const locationInput=document.getElementById('locationInput');
const distanceSelect=document.getElementById('distanceSelect');
const disponibiliteSelect=document.getElementById('disponibiliteSelect');
const sortSelect=document.getElementById('sortSelect');
const searchBtn=document.getElementById('searchBtn');
const doctorsList=document.getElementById('doctorsList');
const resultCountSpan=document.getElementById('resultCount');
const visibleCountSpan=document.getElementById('visibleCount');
const paginationDiv=document.getElementById('pagination');

const doctorsAvailability={
    1:{
        name:"Dr.Donia Bensalem",
        disponibilites:['09:00','10:00','11:00','14:00','15:00','16:00'],
        tempsReponse:2000
    },
    2:{
        name:"Dr.Kamel Ayari",
        disponibilites:['08:30' ,'09:30','10:30','13:00','14:00','15:00'],
        tempsReponse:2500
    },
    3:{
        name:"Dr.Salma Mellouli",
        disponibilites:['09:00' ,'10:00','11:00','14:00','15:00','16:00'],
        tempsReponse:1800
    },
    4:{
        name:"Dr.Mohamed Gharbi",
        disponibilites:['08:30' ,'09:30','10:30','14:30','15:00','16:00'],
        tempsReponse:2200
    },
    5:{
        name:"Dr.Leila Ben Amor",
        disponibilites:['10:00','11:00','13:00','14:00','15:00','16:00'],
        tempsReponse:2200
    },
    6:{
        name:"Dr.Nabil Saidi",
        disponibilites:['09:00','10:00','11:00','14:00','15:00'],
        tempsReponse:2000
    },
    7:{
        name:"Dr.Amira Khedher",
        disponibilites:['08:00' ,'09:00','10:00','11:00','13:00','14:00','15:00'],
        tempsReponse:1500
    },
    8:{
        name:"Dr.Moncef Aisseoui",
        disponibilites:['09:00','10:00','11:00','14:00','15:00','16:00','17:00'],
        tempsReponse:2800
    },
    9:{
        name:"Dr.Rim Ben Hassine",
        disponibilites:['09:30','10:30','11:30','14:30','15:30'],
        tempsReponse:1900
    },
    10:{
        name:"Dr.Mehdi Trabelsi",
        disponibilites:['08:30' ,'09:30','10:30','13:30','14:30','15:30'],
        tempsReponse:2300
    }
};
const rdvModal=document.getElementById('rdvModal');
const propositionModal=document.getElementById('propositionModal');
const notification=document.getElementById('notification');
const rdvForm=document.getElementById('rdvForm');

let currentProposition=null;
function openRdvModal(doctorId,doctorName){
    const doctorIdInput=document.getElementById('doctorId');
    const doctorNameInput=document.getElementById('doctorName');
    if(doctorIdInput) doctorIdInput.value=doctorId;
    if(doctorNameInput) doctorNameInput.value=doctorName;
    const rdvDate=document.getElementById('rdvDate');
    const rdvTime=document.getElementById('rdvTime');
    const motif=document.getElementById('motif');
    if(rdvDate) rdvDate.value='';
    if(rdvTime) rdvTime.value='';
    if(motif) motif.value='';
    if(rdvModal) rdvModal.style.display='flex';
}
function closeModal(){
    const rdvModal=document.getElementById('rdvModal');
    const propositionModal=document.getElementById('propositionModal');
    if(rdvModal) rdvModal.style.display='none';
    if(propositionModal) propositionModal.style.display='none';
}
function closePropositionModal(){
    const propositionModal=document.getElementById('propositionModal');
    if(propositionModal) propositionModal.style.display='none';
}
function showNotification(message,isError=false){
    const notification=document.getElementById('notification');
    if(!notification) return;
    notification.textContent=message;
    notification.className=`notification ${isError ? 'error':'success'}`;
    notification.style.display='block';
    setTimeout(() =>{
        notification.style.display='none';
    },3000);
}
function checkAvailability(doctorId,date,time){
    const doctor=doctorsAvailability[doctorId];
    if(!doctor) return false;
    return doctor.disponibilites.includes(time);
}
function getAlternativeSlot(doctorId){
    const doctor=doctorsAvailability[doctorId];
    if(!doctor || !doctor.disponibilites.length) return null;
    return doctor.disponibilites[0]
}
function sendToDoctor(doctorId,doctorName,date,time,motif){
    console.log(`Rendez-vous envoyé à ${doctorName} pour le ${date} à ${time}`);
    return true;
}
function showProposition(message,nouveauCreneau,doctorId,doctorName,motif){
    currentProposition={
        doctorId,
        doctorName,
        nouveauCreneau,
        motif,
        date:document.getElementById('rdvDate').value
    };
    const propositionModal=document.getElementById('propositionModal');
    const propositionMessage=document.getElementById('propositionMessage');
    const newSlotSpan=document.getElementById('newSlot');
    if (propositionMessage) propositionMessage.textContent=message;
    if(newSlotSpan) newSlotSpan.textContent=nouveauCreneau;
    if(propositionModal) propositionModal.style.display='flex';
}
function accepterProposition(){
    if(currentProposition){
        sendToDoctor(
            currentProposition.doctorId,
            currentProposition.doctorName,
            currentProposition.date,
            currentProposition.nouveauCreneau,
            currentProposition.motif
        );
        showNotification(`Rendez-vous confirmé avec ${currentProposition.doctorName} à ${currentProposition.nouveauCreneau}`,false);
        closePropositionModal();
        closeModal();
    }
}
function refuserProposition(){
    currentProposition=null;
    closePropositionModal();
    showNotification("Proposition refusée. Veuillez choisir un autre médecin.",true);
}
function submitRdvForm(event){
    if(event) event.preventDefault();
    const doctorIdInput=parseInt(document.getElementById('doctorId').value);
    const doctorNameInput=document.getElementById('doctorName').value;
    const rdvDate=document.getElementById('rdvDate').value;
    const rdvTime=document.getElementById('rdvTime').value;
    const motif=document.getElementById('motif').value;
    if(!rdvDate || !rdvDate){
        showNotification("Veuillez sélectionner une date et un horaire",true);
        return;
    }
    const isAvailable=checkAvailability(doctorId,rdvDate,rdvTime);
    if(isAvailable){
        sendToDoctor(doctorId,doctorName,rdvDate,rdvTime,motif);
        showNotification(`Rendez-vous confirmé avec ${doctorName} le ${rdvDate} à ${rdvTime}`,false);
        closeModal();
    } else{
        const  alternativeSlot=getAlternativeSlot(doctorId);
        if(alternativeSlot){
            showProposition(`Le créneau ${rdvTime} n'et pas disponible, Voulez-vous accepter le créneau suivant,`,alternativeSlot,doctorId,doctorName,motif);

        }else{
            showNotification("Aucun créneau disponible pour ce médecin",true);
        }

    }
}
function calculateDistance(doctorCity,searchCity){
    const distances={
        'Tunis':{'Tunis':1.2,'La Marsa':6.5, 'Ariana':8.3,'Sousse':140},
        'La Marsa':{'Tunis':6.5,'La Marsa':1.8, 'Ariana':5.2,'Sousse':145},
        'Ariana':{'Tunis':8.3,'La Marsa':5.2, 'Ariana':2.1,'Sousse':150},
        'Sousse':{'Tunis':140,'La Marsa':145, 'Ariana':150,'Sousse':1.5},
    };
    if(distances[searchCity] && distances[searchCity][doctorCity]){
        return distances[searchCity][doctorCity];
    }
    return 999;
}
function isCityMatch(doctorCity,searchCity){
    if(!searchCity)return true;
    const doctorCityLower=doctorCity.toLowerCase();
    const searchCityLower=searchCity.toLowerCase();
    return doctorCityLower.includes(searchCityLower) || searchCityLower.includes(doctorCityLower);
}
function isDisponible(doctorDispo,filterDispo){
    if(filterDispo==='toujours') return true;
    if(filterDispo==='aujourdhui'){
        return doctorDispo==='Aujourd\'hui';
    }
    if(filterDispo==='demain') {
        return doctorDispo==='Demain';
    }
    if(filterDispo==='cettesemaine') {
        const disposSemaine =['Aujourd\'hui','Demain','Lundi','Mardi','Mercredi','Jeudi','Vendredi'];
        return disposSemaine.includes(doctorDispo);
    }
    return true;
}
function getDisponibilityClass(disponibilite){
    if(disponibilite==='Aujourd\'hui') return 'today';
    if(disponibilite==='Demain') return 'urgent';
    return '';
}
function filterDoctors(){
    console.log("Filtrage en cours...");
    const searchLocation=locationInput.value.trim();
    const maxDistance=parseFloat(distanceSelect.value);
    const disponibilite=disponibiliteSelect.value;
    if(!searchLocation){
        console.log("Aucune localisation saisie");
        filteredDoctors=[];
        if(resultCountSpan) resultCountSpan.textContent="0";
        if(visibleCountSpan) visibleCountSpan.textContent="0";
        if (doctorsList){
            doctorsList.innerHTML=`
                <div class="doctor-card" style="text-align:center;padding:40px;">
                    <i class="fas fa-search" style="font-size:48px; color:#cbd5e1; margin-bottom:16px;"></i>
                    <h3 style="margin-bottom:8px;">Veuillez saisir une localisation</h3>
                    <p style="color:#64748b;">Entrez une ville ou un code postal pour voir les pédiatres à proximité</p>
                    <p style="color:#64748b; margin-top:8px;">Exemple:Tunis, Sousse, La Marsa...</p>
                </div> 
            `;
        }
        if(paginationDiv) paginationDiv.innerHTML='';
        return;
    }
    filteredDoctors=pediatres.filter(doctor => {
        let locationMatch=true;
        let doctorDistance=doctor.distance;
        if(searchLocation){
            const cityMatch=isCityMatch(doctor.ville,searchLocation);
            if (cityMatch){
                doctorDistance=calculateDistance(doctor.ville,searchLocation);
            } else{
                locationMatch=false;
            }
        }
        if(!locationMatch) return false;
        if(doctorDistance > maxDistance) return false;
        const disponibiliteMatch=isDisponible(doctor.disponibilite,disponibilite);
        if(!disponibiliteMatch) return false;
        doctor.calculatedDistance=doctorDistance;
        return true;
    });
    console.log("Médecins filtrés:",filteredDoctors.length);
    sortDoctors();
    currentPage=1;
    updateDisplay();
}

function sortDoctors(){
    const sortBy=sortSelect.value;
    switch(sortBy){
        case 'distance':
            filteredDoctors.sort((a,b) => {
                const distA=a.calculatedDistance || a.distance;
                const distB=b.calculatedDistance || b.distance;
                return distA-distB;
            });
            break;
        case 'disponibilite':
            const priorite={
                'Aujourd\'hui':1,
                'Demain':2,
                'Lundi':3,
                'Mardi':4,
                'Mercredi':5,
                'Jeudi':6,
                'Vendredi':7
            };
            filteredDoctors.sort((a,b) => {
                return (priorite[a.disponibilite] || 99) - (priorite[b.disponibilite] || 99);
            });
            break;
        case 'nom':
        default:
            filteredDoctors.sort((a,b)=> a.nom.localeCompare(b.nom));
            break;
    }
}
function displayDoctors(){
    const start=(currentPage-1)*itemsPerPage;
    const end=start+itemsPerPage;
    const pageDoctors=filteredDoctors.slice(start,end);
    if (pageDoctors.length===0){
        doctorsList.innerHTML=`
        <div class="doctor-card" style="text-align:center;padding:40px;">
            <i class="fas fa-search" style="font-size:48px; color:#cbd5e1; margin-bottom:16px;"></i>
            <h3 style="margin-bottom:8px;">Aucun pédiatre trouvé</h3>
            <p style="color:#64748b;">Modifiez vos critères de recherche</p>
            <p style="color:#64748b; margin-top:8px;">Essayez avec une distance plus grande ou une disponibilité différente</p>
        </div> 
        `;
        return ;
    }
    doctorsList.innerHTML=pageDoctors.map(doctor=>{
        const displayDistance=doctor.calculatedDistance ? doctor.calculatedDistance.toFixed(1) : doctor.distance;
        const disponibilityClass=getDisponibilityClass(doctor.disponibilite);
        return `
        <div class="doctor-card" data-doctor-id="${doctor.id}">
            <div class="card-left">
                <div class="doctor-avatar">
                    ${doctor.photo}
                </div>
                <span class="disponibility-badge ${disponibilityClass}">
                    <i class="fas fa-calendar-check"></i>${doctor.disponibilite}
                </span>
            </div>
            <div class="card-right">
                <div class="doctor-header">
                    <h3>${doctor.nom}</h3>
                    <span class="specialty-badge">${doctor.specialite}</span>
                </div>
                <div class="location">
                    <i class="fas fa-map-marker-alt"></i> ${doctor.quartier} - ${doctor.ville}
                </div>
                <div class="doctor-details">
                    <div class="detail">
                        <i class="fas fa-road"></i>
                        <span>${displayDistance} km</span>
                    </div>
                    <div class="detail">
                        <i class="fas fa-phone"></i>
                        <span>${doctor.telephone}</span>
                    </div>
                </div>
                <button class="profile-btn" onclick="viewProfile(${doctor.id})">
                    <i class="fas fa-calendar-plus"></i> Prendre rendez-vous
                </button>
            </div>
        </div>
        `;
    }).join('');
}
function displayPagination(){
    const totalPages=Math.ceil(filteredDoctors.length/itemsPerPage);
    paginationDiv.innerHTML='';
    if(totalPages<=1) return;
    const prevBtn=document.createElement('button');
    prevBtn.className='page-btn';
    prevBtn.innerHTML='<i class="fas fa-chevron-left"></i>';
    prevBtn.disabled=currentPage===1;
    prevBtn.onclick=() =>{
        if(currentPage>1){
            currentPage--;
            updateDisplay();
        }
    };
    paginationDiv.appendChild(prevBtn)
    for(let i=1;i<=totalPages;i++){
        const btn=document.createElement('button');
        btn.className='page-btn';
        if(i===currentPage) btn.classList.add('active');
        btn.textContent=i;
        btn.onclick=() =>{
            currentPage=i;
            updateDisplay();
        };
        paginationDiv.appendChild(btn);
    }
    const nextBtn=document.createElement('button');
    nextBtn.className='page-btn';
    nextBtn.innerHTML='<i class="fas fa-chevron-right"></i>';
    nextBtn.disabled=currentPage===totalPages;
    nextBtn.onclick=() =>{
        if(currentPage<totalPages){
            currentPage++;
            updateDisplay();
        }
    };
    paginationDiv.appendChild(nextBtn);
}
function updateDisplay(){
    displayDoctors();
    displayPagination();
    resultCountSpan.textContent=filteredDoctors.length;
    visibleCountSpan.textContent=filteredDoctors.length;
}
function viewProfile(doctorId){
    const doctor=pediatres.find(d => d.id === doctorId);
    if(doctor){
        openRdvModal(doctorId,doctor.nom);
    }
}
document.addEventListener('DOMContentLoaded',function(){
    console.log("Page chargée, initialisation...");
    if(searchBtn){
        searchBtn.addEventListener('click',filterDoctors);
        console.log("Bouton rechercher trouvé");
    } else{
        console.log("ERREUR: Bouton rechercher trouvé!");
    }
    if (sortSelect){
        sortSelect.addEventListener('change',function(){
            sortDoctors();
            updateDisplay();
        });
    }
    if(rdvForm){
        rdvForm.addEventListener('submit',submitRdvForm);
        console.log("Formulaire rendez-vous initialisé");
    }
});
        