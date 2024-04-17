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
    $err = traitementRechercheL($recherches, $results);
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
 *  @param array    $results       Tableaux contenant les résultats de la recherches
 * 
 *  @return bool    un booléen à true si des erreurs sont détectées, false sinon
 */
function traitementRechercheL(array &$recherches, array &$results): bool {
    if (preg_match('/^(?=.*[^ ]{3})[0-9a-zA-Z\' -]{3,255}$/u', $_POST['textRecherche'])) {
         
        $recherches = explode(" ", $_POST['textRecherche']);
        $tmp = [];
        foreach ($recherches as $e) {
            if (strlen($e) >= 3) {
                $tmp[] = $e;
            }
        }
        $recherches = $tmp;

        // Chaîne vide pour stocker les conditions de recherche
        $whereConditions = '';

        // Boucle pour ajouter chaque recherche à la clause WHERE
        foreach ($recherches as $recherche) {
            $whereConditions .= '(arTitre LIKE "%' . $recherche . '%" OR arResume LIKE "%' . $recherche . '%") AND ';
        }

        // Supprimer le dernier "OR" de la chaîne
        $whereConditions = rtrim($whereConditions, ' AND ');


        $bd = bdConnect();
        // Requête SQL pour récupérer les articles
        $sql = "SELECT arID, arTitre, arResume, arDatePubli
                FROM article
                WHERE $whereConditions
                ORDER BY arDatePubli DESC";

        $result = bdSendRequest($bd, $sql);

        // Fermeture de la connexion au serveur de BdD
        mysqli_close($bd);

        while ($row = mysqli_fetch_assoc($result)) {
            $results[] = $row;
        }

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
        $count = count($recherches); // Nombre total d'éléments dans le tableau
        foreach ($recherches as $key => $e) {
            echo $e;
            if ($key < $count - 1) { // Vérifie si ce n'est pas le dernier élément
                echo ' '; // Ajoute un espace seulement si ce n'est pas le dernier élément
            }
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

        // TODO: correction superposition des articles (test avec "lic")
        // Créer un tableau associatif pour stocker les articles par mois
        $articlesParMois = [];
        foreach ($results as $article) {
            $mois = dateIntToStringL($article['arDatePubli']);
            $articlesParMois[$mois][] = $article;
        }

        // Parcourir les articles à afficher sur la page actuelle
        foreach ($articlesParMois as $mois => $articlesDuMois) {
            echo '<section>',
            '<h2>', $mois, '</h2>';
            
            // Parcourir les articles du mois
            foreach ($articlesDuMois as $article) {
                affUnArticle($article['arTitre'], $article['arID'], $article['arResume']);
            }
            '</section>';
        }
    }

    echo '</main>';
}