<?php

// chargement des bibliothèques de fonctions
require_once('bibli_gazette.php');
require_once('bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage ou reprise de la session
session_start();

affEntete('L\'actu');

// génération du contenu de la page
affContenuL();

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
function affContenuL() : void {
    echo '<main>';

    $bd = bdConnect();

    // Requête SQL pour récupérer les articles
    $sql = 'SELECT arID, arTitre, arResume, arDatePubli
            FROM article
            ORDER BY arDatePubli DESC';
    $result = bdSendRequest($bd, $sql);

    // Fermeture de la connexion au serveur de BdD
    mysqli_close($bd);


    // Nombre d'articles par page
    $articlesParPage = 4;
    // Nombre total d'articles dans la base de données
    $totalArticles = $result->num_rows;
    // Calculer le nombre total de pages nécessaires
    $totalPages = ceil($totalArticles / $articlesParPage);
    // Récupérer le numéro de page à afficher
    $pageCourante = isset($_GET['page']) ? $_GET['page'] : 1;
    // Afficher les articles récupérés
    affPagination($totalPages, $pageCourante);


    // Calcul de l'index de début et de fin pour les articles à afficher sur la page actuelle
    $indexDebut = ($pageCourante - 1) * $articlesParPage;
    $indexFin = $indexDebut + $articlesParPage;


    // Stockage de tous les articles dans un tableau
    $articles = [];
    while ($tab = mysqli_fetch_assoc($result)) {
        $articles[] = $tab;
    }

    // Créer un tableau associatif pour stocker les articles par mois
    $articlesParMois = [];
    foreach ($articles as $article) {
        $mois = dateIntToStringL($article['arDatePubli']);
        $articlesParMois[$mois][] = $article;
    }


    // Variable pour stocker le nombre d'articles déjà affichés
    $articlesAffiches = 0;
    // Tableau pour indiquer si un mois a déjà été affiché
    // TODO: à faire
    $sectionDejaAffiche = [];


    // Parcourir les articles à afficher sur la page actuelle
    foreach ($articlesParMois as $mois => $articlesDuMois) {
        echo '<section>',
        '<h2>', $mois, '</h2>';
        
        // Parcourir les articles du mois
        foreach ($articlesDuMois as $article) {
            // Vérifier si l'index de l'article est compris entre l'index de début et de fin
            if ($articlesAffiches >= $indexDebut && $articlesAffiches < $indexFin) {
                affUnArticle($article['arTitre'], $article['arID'], $article['arResume']);
            }
            // Incrémenter le nombre d'articles affichés
            $articlesAffiches++;

            // Vérifier si le nombre d'articles affichés atteint 4 (fin de la page)
            if ($articlesAffiches >= $indexFin) {
                // Sortir de la boucle des articles du mois
                break;
            }
        }

        // Vérifier si le nombre d'articles affichés atteint 4 (fin de la page)
        if ($articlesAffiches >= $indexFin) {
            // Sortir de la boucle des mois
            break;
        }
        echo '</section>';
    }
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
            echo '<a href="?page=' . $i . '">' . $i . '</a>';
        }
    }
    echo '</p>',
    '</section>';
}
