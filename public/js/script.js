// Récupération des boutons de la page de la liste d'anime
const buttons = document.querySelectorAll(".animeListButton");

// On rajoute une écoute du click
buttons.forEach((button) => {
    // Quand un des boutons est cliqué
    button.addEventListener("click", () => {
        // On enlève la classe active à tout les boutons
        buttons.forEach((btn) => {
            btn.classList.remove("active");
        });

        // On ajoute la classe active au bouton cliqué
        button.classList.add("active");
    });
});

// Récupération des boutons de manière distinctives
const animeListButtonAll = document.getElementById("animeListButtonAll");
const animeListButtonWatching = document.getElementById("animeListButtonWatching");
const animeListButtonCompleted = document.getElementById("animeListButtonCompleted");
const animeListButtonPlanned = document.getElementById("animeListButtonPlanned");

// Récupération des sections de manières distinctives
const animeListSectionAll = document.getElementById("animeListAll");
const animeListSectionWatching = document.getElementById("animeListWatching");
const animeListSectionCompleted = document.getElementById("animeListCompleted");
const animeListSectionPlanned = document.getElementById("animeListPlanned");

// Ajout d'écouteur d'evenement
animeListButtonAll.addEventListener("click", () => showSection(animeListSectionAll));
animeListButtonWatching.addEventListener("click", () => showSection(animeListSectionWatching));
animeListButtonCompleted.addEventListener("click", () => showSection(animeListSectionCompleted));
animeListButtonPlanned.addEventListener("click", () => showSection(animeListSectionPlanned));

// Fonction qui gère le click d'un bouton pour faire afficher une section
function showSection(selectedSection){
    const sections = [animeListSectionAll, animeListSectionWatching, animeListSectionCompleted, animeListSectionPlanned];

    sections.forEach(section => {
        section.style.display = "none";
    });

    selectedSection.style.display = "grid";
}

showSection(animeListSectionAll);