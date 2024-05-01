<?php
//_____________________________________________________________\\
//                                                             \\
//                      La Gazette de L-INFO                   \\
//                  Page d'actualités (actus.php)              \\
//                                                             \\
//                    CUINET ANTOINE TP2A-CMI                  \\
//                        Langages du Web                      \\
//                        L2 Informatique                      \\
//                         UFC - UFR ST                        \\
//_____________________________________________________________\\



// Chargement des bibliothèques de fonctions
require_once('bibli_gazette.php');
require_once('bibli_generale.php');

// Bufferisation des sorties
ob_start();

// Démarrage ou reprise de la session
session_start();

affEntete('L\'actu');

// Génération du contenu de la page
affContenuL();

affPiedDePage();

// Envoi du buffer
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
function affContenuL() : void {
    if(isset($_GET['page'])){
        if (! parametresControle('get', ['page'])){
            affErreur('Il faut utiliser une URL de la forme : http://..../php/article.php?page=XXX');
            return; // ==> fin de la fonction
        }

        // Récupéreration du numéro de page à afficher en échiffrant l'URL
        $pageCourante = dechiffrerSignerURL($_GET['page']);

        if (! estEntier($pageCourante)){
            affErreur('L\'identifiant doit être un entier');
            return; // ==> fin de la fonction
        }

        if ($pageCourante <= 0){
            affErreur('L\'identifiant doit être un entier strictement positif');
            return; // ==> fin de la fonction
        }

    } else {
        $pageCourante = 1;
    }

    // Nombre d'articles par page
    $articlesParPage = 4;
    // Calcul de l'index de début pour les articles à afficher sur la page actuelle
    $indexDebut = ($pageCourante - 1) * $articlesParPage;

    // Connexion au serveur de BD
    $bd = bdConnect();

    // Requête SQL pour récupérer les articles
    $sql = "SELECT article.arID, article.arTitre, article.arResume, article.arDatePubli,
               (SELECT COUNT(*) FROM article) AS totalArticles
        FROM article
        ORDER BY arDatePubli DESC
        LIMIT $articlesParPage OFFSET $indexDebut";
    $result = bdSendRequest($bd, $sql);

    // Fermeture de la connexion au serveur de BdD
    mysqli_close($bd);

    // Stockage de tous les articles dans un tableau
    $articles = [];
    while ($tab = mysqli_fetch_assoc($result)) {
        $mois = dateIntToStringL($tab['arDatePubli']);
        $articles[$mois][] = $tab;

        // Nombre total d'articles dans la base de données
        $totalArticles = $tab['totalArticles'];
    }
    // Libération de la mémoire associée au résultat de la requête
    mysqli_free_result($result);


    echo '<main>';

    // Calculer le nombre total de pages nécessaires
    $totalPages = ceil($totalArticles / $articlesParPage);
    // Afficher les articles récupérés
    affPagination($totalPages, $pageCourante);

    ParcoursEtAffArticlesParMois($articles);
    echo '</main>';
}


//_______________________________________________________________
/**
 * Affiche la pagination de la page
 *
 * @param  int      $totalPages     Le nombre de pages au total.
 * @param  int      $pageCourante   La page actuelle.
 *
 * @return void
 */
function affPagination(int $totalPages, int $pageCourante): void {
    echo '<section class="pagination">',
    '<p>Pages : ';

    for($i = 1; $i <= $totalPages; $i++) {
        if ($i == $pageCourante) {
            echo '<span class="current-page">' . $i . '</span>';
        } else {
            // Chiffrement de l'id pour le passage dans l'URL
            $id_chiffre = chiffrerSignerURL($i);
            
            echo '<a href="?page=' . $id_chiffre . '">' . $i . '</a>';
        }
    }
    echo '</p>',
    '</section>';
}
