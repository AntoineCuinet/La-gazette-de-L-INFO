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

affEntete('Édition d\'articles');

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


    echo '</main>';
}