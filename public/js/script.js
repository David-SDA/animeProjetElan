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
