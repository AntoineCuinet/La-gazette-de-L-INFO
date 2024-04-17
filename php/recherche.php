<?php

// chargement des bibliothèques de fonctions
require_once('bibli_gazette.php');
require_once('bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage ou reprise de la session
session_start();

affEntete('Recherche');

$recherches = [];
$results = [];

if (isset($_POST['btnRecherche'])) {
    $err = traitementRechercheL();
} else {
    $err = false;
}

// génération du contenu de la page
affContenuL($err, $recherches, $results);

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
 * Traitement d'une recherche
 *
 * Vérification de la validité des données
 * Si on trouve des erreurs => return un booléen à true
 * A false sinon
 * 
 *  @param array    $recherches    Tableaux contenant les caractères de la recherches
 * 
 *  @return bool    un booléen à true si des erreurs sont détectées, false sinon
 */
function traitementRechercheL(): bool {
    // TODO: améliorer la regex
    if (preg_match('/^[0-9a-zA-Z\' -]{3,255}$/u', $_POST['textRecherche'])) {
        

        // TODO: réussir à remplir le tab
        // TODO: coucper le tab à chaque espace pour recherche 
        $recherches[] = $_POST['textRecherche'];

        $bd = bdConnect();

        // Requête SQL pour récupérer les articles
        $sql = 'SELECT arID, arTitre, arResume, arDatePubli
                FROM article
                ORDER BY arDatePubli DESC';
                // TODO: ajouter la recherche de sous chaine (voir cours/TD)
        $result = bdSendRequest($bd, $sql);

        // Fermeture de la connexion au serveur de BdD
        mysqli_close($bd);


        return false;
    }
    return true;
}



//_______________________________________________________________
/**
 * Affichage du contenu principal de la page
 * 
 * @param   bool   $err           Booléen à true si il y a des erreurs de détectés
 * @param   array  $recherches    Tableaux contenant les caractères de la recherches
 * @param   array  $results       Tableaux contenant les résultats de la recherches
 *
 * @return  void
 */
function affContenuL(bool $err, array $recherches, array $results): void {
    echo '<main>',
    '<section>',
        '<h2>Rechercher des articles</h2>',
        '<p>Les critères de recherche doivent faire au moins 3 caractères pour être pris en compte.</p>';
    
    if ($err) {
        echo '<div class="erreur">Le ou les critères de recherche ne sont pas valides.</div>';
    } else if (isset($_POST['btnRecherche'])) {
        echo '<div class="succes">Critères de recherche utilisés : "';
        foreach ($recherches as $e) {
            echo $e;
        }
        echo '".</div>';
    }

    echo '<form method="post" action="recherche.php" class="form-recherche">',
        '<input type="text" name="textRecherche" value="" required>',
        '<input type="submit" name="btnRecherche" value="Rechercher">',
        '</form>',
        '</section>';

    // Pas de résultats
    if (empty($results) && !empty($recherches)) {
        echo '<section>',
        '<h2>Résultats</h2>',
        '<p>Aucun article ne correspond à vos critères de recherche.</p>';
        '</section>';
    } else {
        // TODO: faire comme dans actus
        foreach ($results as $res) {
            echo '<section>',
            '<h2>', $res['arTitre'], '</h2>',
            '</section>';
        }
    }

    echo '</main>';
}