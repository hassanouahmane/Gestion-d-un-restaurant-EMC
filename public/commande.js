const lignes = document.querySelector('#lignes');
const modele = document.querySelector('#ligne');
let prochainIndex = 0;

function ajouterLigne() {
    if (lignes.children.length >= 30) return;

    const index = prochainIndex++;
    const ligne = modele.content.firstElementChild.cloneNode(true);
    const article = ligne.querySelector('[data-field="article"]');
    const choix = ligne.querySelector('[data-choix]');
    article.name = `articles[${index}][article]`;
    ligne.querySelector('[data-field="quantite"]').name = `articles[${index}][quantite]`;

    article.addEventListener('change', () => {
        choix.replaceChildren();
        if (!article.value.startsWith('menu:')) return;

        const menu = document.querySelector(`#menu-${article.value.split(':')[1]}`);
        choix.append(menu.content.cloneNode(true));
        for (const champ of choix.querySelectorAll('[data-groupe]')) {
            champ.name = `articles[${index}][choix][${champ.dataset.groupe}]`;
        }
    });

    ligne.querySelector('[data-retirer]').addEventListener('click', () => {
        if (lignes.children.length > 1) ligne.remove();
    });
    lignes.append(ligne);
}

document.querySelector('#ajouter').addEventListener('click', ajouterLigne);
ajouterLigne();
