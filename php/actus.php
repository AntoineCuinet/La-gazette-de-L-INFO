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
    // Date qui sera affichée en titre d'une section
    $dateAff = '';
    // Tableau associatif pour classer par moi les articles
    $articlesByMonth = array();

    // Afficher les articles récupérés
    affPagination($totalPages, $pageCourante);







    // Compteur pour les articles affichés
    //$articleCount = 0;
    $articleIndex = $pageCourante % $articlesParPage;
    // Afficher les articles en fonction de la page actuelle
    while ($tab = mysqli_fetch_assoc($result)) {
        
        //for($i = $pageCourante; $i <= $articleIndex; $i++) {
            // $tab = mysqli_fetch_assoc($result);
        // Calculer l'index de l'article dans la liste des articles
        //$articleIndex = $articleCount % $articlesParPage;

        // 
        // $publicationDate = strtotime($tab['arDatePubli']);
        // Clé du tableau basée sur l'année et le mois de publication de l'article
        // $key = date('Y-m', $publicationDate);

        // Récupérer la date de publication de l'article
        $dateAff = dateIntToStringL($tab['arDatePubli']);

        // Vérifier si l'article doit être affiché sur la page actuelle
        //if ($articleIndex == ($pageCourante - 1)) {
            // Stocker l'article dans le tableau associatif par mois
        $articlesByMonth[$dateAff][] = $tab;
        //}
        //$articleCount++;
    }

    // $affStart = $articleIndex;
    // $affEnd = $affStart + $articlesParPage;


    // Afficher les articles pour chaque mois
    foreach ($articlesByMonth as $month => $articles) {
        echo '<section>',
        '<h2>', $month, '</h2>';

        foreach ($articles as $article) {
            affUnArticle($article['arTitre'], $article['arID'], $article['arResume']);
        }
        echo '</section>';
    }

    echo '</main>';
}



//_______________________________________________________________
/**
 * Affiche la pagination de la page
 *
 * @param  string   $titre     Le titre de l'article.
 * @param  int      $id        L'id de l'article.
 * @param  string   $resume    Le résumé de l'article.
 *
 * @return void
 */
function affUnArticle(string $titre, int $id, string $resume): void {
    echo '<article class="resume">',
    '<img src="../upload/', $id, '.jpg" alt="Photo d\'illustration | ', $titre, '">',
    '<h3>', $titre, '</h3>',
    '<p>', $resume, '</p>',
    '<footer><a href="../php/article.php?id=', $id, '">Lire l\'article</a></footer>',
    '</article>';
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


//_______________________________________________________________
/**
 * Conversion d'une date format AAAAMMJJHHMM au format mois AAAA
 *
 * @param  int      $date   la date à afficher.
 *
 * @return string           la chaîne qui représente la date
 */
function dateIntToStringL(int $date): string {
    $mois = substr($date, -8, 2);
    $annee = substr($date, 0, -8);

    $months = getArrayMonths();

    return $months[$mois - 1] . ' ' . $annee;
}