<?php

// chargement des bibliothèques de fonctions
require_once('./bibli_gazette.php');
require_once('./bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage ou reprise de la session
session_start();


// si l'utilisateur n'est pas authentifié ou s'il n'a pas les droit de rédacteur, on le redirige sur la page index.php
if (! estAuthentifie() || ! $_SESSION['redacteur']){
    header('Location: ../index.php');
    exit;
}

affEntete('Rédaction d\'un nouvel article');

$values = array('title' => '');
// génération du contenu de la page
affContenuL($values);

affPiedDePage();

// envoi du buffer
ob_end_flush();


/*********************************************************
 *
 * Définitions des fonctions locales de la page
 *
 *********************************************************/
//_______________________________________________________________
/**
 * Affichage du contenu principal de la page
 *
 * @return  void
 */
function affContenuL(array $values) : void {
    echo '<main>',
    '<section>',
        '<h2>Créer votre nouvel article</h2>',
        '<p>Pour créer un article, le titre, le résumé et le texte ne doivent pas êtres vides.</p>',
        '<form method="post" action="nouveau.php">';

    affLigneInput('Le titre : ', array('type' => 'text', 'name' => 'title', 'value' => $values['title'], 'required' => 1));

        // TODO: ajouter l'upload d'une image + vérif 
        echo '<textarea name="resumAr" rows="18" cols="80">Saisissez ici le résumé de l\'article</textarea>',
            '<textarea name="textAr" rows="18" cols="80">Saisissez ic le contenu de l\'article</textarea>';

        echo '<input type="submit" name="btnCreerArticle" value="Créer l\'article !">',
        '</form>';



    echo '</section>',
    '</main>';
}