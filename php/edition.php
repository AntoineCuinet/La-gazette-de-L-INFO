<?php
//_____________________________________________________________\\
//                                                             \\
//                      La Gazette de L-INFO                   \\
//  Page d'édition ou suppression d'un article (edition.php)   \\
//                                                             \\
//                    CUINET ANTOINE TP2A-CMI                  \\
//                        Langages du Web                      \\
//                        L2 Informatique                      \\
//                         UFC - UFR ST                        \\
//_____________________________________________________________\\



// Chargement des bibliothèques de fonctions
require_once('./bibli_gazette.php');
require_once('./bibli_generale.php');

// Bufferisation des sorties
ob_start();

// Démarrage ou reprise de la session
session_start();


// Si l'utilisateur n'est pas authentifié ou s'il n'a pas les droit de rédacteur, on le redirige sur la page index.php
if (! estAuthentifie() || ! $_SESSION['redacteur']){
    header('Location: ../index.php');
    exit;
}

affEntete('Édition/suppression d\'articles');

if (isset($_POST['btnEditerArticle'])) {
    $errs = traitementModifAr();
} else if (isset($_POST['btnSuppArticle'])) {
    $errs = null;
    confirmationSupp();
} else if (isset($_POST['btnComfirmerSuppArticle'])) {
    $errs = null;
    traitementSuppAr();
} else {
    $errs = null;
}

// Génération du contenu de la page
affContenuL($errs);

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
 * @param   array   $errs   tableau associatif contenant les erreurs de saisie
 * 
 * @return  void
 */
function affContenuL(?array &$errs): void {
    // Vérification de GET et déchiffrement de l'id
    $id = verifGet('article', 'article');

    echo '<main>';
    affContenuSuppL($id);
    affContenuArticleL($errs, $id);
    echo '</main>';
}


//_______________________________________________________________
/**
 * Affichage du contenu de l'édition de l'article
 *
 * @param   array   $err    tableau associatif contenant les erreurs de saisie
 * @param   int     $id     identifiant de l'article
 * 
 * @return  void
 */
function affContenuArticleL(?array &$err, int $id): void {

    // Ouverture de la connexion à la base
    $bd = bdConnect();

    // Requête de récupération de l'article
    $sql = "SELECT * FROM article WHERE arID = '$id'";
    $res = bdSendRequest($bd, $sql);

    // Fermeture de la connexion à la base de données
    mysqli_close($bd);

    // Vérification de l'existence de l'article
    if (mysqli_num_rows($res) == 0) {
        affErreur('L\'article que vous souhaitez éditer n\'existe pas.</p>');
        exit(1); // ==> fin de la fonction
    }

    // Récupération des données de l'article
    $tab = htmlProtegerSorties(mysqli_fetch_assoc($res));

    echo '<section>',
        '<h2>Éditer votre article</h2>',
        '<p>Pour éditer un article, le titre, le résumé et le contenu ne doivent pas êtres vides.</p>';

    if (! empty($err)) {
        echo    '<div class="erreur">Les erreurs suivantes ont été relevées lors de l\'enregistrement de l\'article :',
                '<ul>';
    foreach ($err as $e) {
        echo        '<li>', $e, '</li>';
    }
    echo        '</ul>',
            '</div>';
    } else if (isset($_POST['btnEditerArticle'])) {

            // ouverture de la connexion à la base
            $bd = bdConnect();
            // Requête pour récupérer l'id de l'article créé
            $sql = "SELECT arID FROM article 
                    WHERE arAuteur = '{$_SESSION['pseudo']}'
                    ORDER BY arDateModif DESC
                    LIMIT 1;";
            $result = bdSendRequest($bd, $sql);

            // fermeture de la connexion à la base de données
            mysqli_close($bd);

            $row = mysqli_fetch_assoc($result);
            $id = $row['arID'];
            // Chiffrement de l'id pour le passage dans l'URL
            $id_chiffre = chiffrerSignerURL($id); 
            echo    '<div class="succes">L\'article à bien été modifié. <a href="./article.php?id=', $id_chiffre, '">cliquez ici pour le voir !</a></div>';
    }

    // chiffrement de l'id
    $id_chiffre = chiffrerSignerURL($id);
    echo '<form method="post" action="edition.php?article=', $id_chiffre, '" class="nouveau" enctype="multipart/form-data">',
    '<table>';

    affLigneInput('Sélectionnez le fichier à télécharger (facultatif) :', array('type' => 'file', 'name' => 'file'));
    echo '<tr><td colspan="2"><input type="hidden" name="MAX_FILE_SIZE" value="102400"></td></tr>';
    affLigneInput('Le titre de l\'article : ', array('type' => 'text', 'name' => 'title', 'value' => $tab['arTitre'] , 'required' => null));

    echo '<tr>',
            '<td><label for="resumAr">Le résumé de l\'article :</label></td>',
            '<td><textarea id="resumAr" name="resumAr" rows="20" cols="40" placeholder="Saisissez ici le résumé de l\'article" required>', $tab['arResume'], '</textarea></td>',
        '</tr>',
        '<tr>',
            '<td><label for="textAr">Le contenu de l\'article : <br>(Utilisez le langage de balisage ad hoc de type BBCode)</label></td>',
            '<td><textarea id="textAr" name="textAr" rows="30" cols="40" placeholder="Saisissez ici le contenu de l\'article (type BBCode)" required>', $tab['arTexte'], '</textarea></td>',
        '</tr>';

        echo '<tr>',
            '<td colspan="2">',
                '<input type="submit" name="btnEditerArticle" value="Éditer l\'article !"> ',
                '<input type="reset" value="Réinitialiser">',
            '</td>',
        '</tr>',
        '</table>',
        '</form>',
        '</section>';
}

//_______________________________________________________________
/**
 * Affichage du contenu de la suppression de l'article
 *
 * @param   int     $id     identifiant de l'article
 * 
 * @return  void
 */
function affContenuSuppL(int $id): void {
    echo '<section>',
    '<h2>Suppression de l\'article</h2>',
    '<p>Cliquez ci-dessous pour supprimer cet article.</p>';

    // chiffrement de l'id
    $id_chiffre = chiffrerSignerURL($id);
    echo '<form method="post" action="edition.php?article=', $id_chiffre, '">',
            '<input type="submit" name="btnSuppArticle" value="Supprimer"> ',
        '</form>',
    '</section>';
}




//_______________________________________________________________
/**
 * Traitement de la modification d'un article
 *
 * @return  array|null  tableau associatif contenant les erreurs de saisie ou null si l'ajout a été effectué
 */
function traitementModifAr(): array|null {
    if(!parametresControle('post', ['MAX_FILE_SIZE', 'title', 'resumAr', 'textAr', 'btnEditerArticle'])) {
        sessionExit();
    }

    $erreurs = [];

    // Vérification des entrées
    $title = $_POST['title'] = trim($_POST['title']);
    verifierTexte($title, 'Le titre', $erreurs, LMAX_TITRE);
    
    $resumAr = $_POST['resumAr'] = trim($_POST['resumAr']);
    verifierTexte($resumAr, 'Le résumé', $erreurs);

    $textAr = $_POST['textAr'] = trim($_POST['textAr']);
    verifierTexte($textAr, 'Le texte', $erreurs);

    // Vérification de l'image (si elle est présente)
    if(isset($_FILES['file']) && !empty($_FILES['file']['name'])) {
        verifUpload($erreurs);
    }

    // Modification de l'article
    if (empty($erreurs)) {
        $date = date('Ymdhm');
        $pseudo = $_SESSION['pseudo'];
        $id = verifGet('article', 'article');

        // Ouverture de la connexion à la base
        $bd = bdConnect();

        // protection des entrées
        $title2 = mysqli_real_escape_string($bd, $title);
        $resumAr2 = mysqli_real_escape_string($bd, $resumAr);
        $textAr2 = mysqli_real_escape_string($bd, $textAr);
        
        // Requête de mise à jour de l'article
        $sql = "UPDATE article
                SET arTitre = '$title2', arResume = '$resumAr2', arTexte = '$textAr2', arDateModif = '$date' 
                WHERE arAuteur = '$pseudo' 
                AND arID = '$id'";

        bdSendRequest($bd, $sql);

        // Fermeture de la connexion à la base de données
        mysqli_close($bd);

        if (isset($_FILES['file']) && !empty($_FILES['file']['name'])){
            // Vérification du droit d'écriture sur le répertoire upload
            $uploadDir = '../upload/';
            verifDroitEcriture($uploadDir);

            // Vérification de l'existence d'une ancienne image + suppression
            $cheminFichier = '../upload/' . $id . '.jpg';
            if (file_exists($cheminFichier)) {
                unlink($cheminFichier);
            }

            depotFile($id, $uploadDir);
        }

        return null;
    } else {
        return $erreurs;
    }
}


//_______________________________________________________________
/**
 * Traitement de la suppression de l'article
 *
 * @return  void
 */
function traitementSuppAr() {
    if(!parametresControle('post', ['btnComfirmerSuppArticle'])) {
        sessionExit();
    }

    // Vérification de GET et déchiffrement de l'id
    $id = verifGet('article', 'article');

    // Ouverture de la connexion à la base
    $bd = bdConnect();
    
    // Requête SQL pour supprimer les commentaires de l'article
    $sqlDelComments = "DELETE FROM commentaire WHERE coArticle = '$id'";
    bdSendRequest($bd, $sqlDelComments);

    // Requête SQL pour supprimer le commentaire
    $sqlDel = "DELETE FROM article WHERE arID = '$id'";
    bdSendRequest($bd, $sqlDel);

    // Fermeture de la connexion à la base de données
    mysqli_close($bd);

    // Supprimer aussi l'image associée dans upload (si elle existe)
    $cheminFichier = '../upload/' . $id . '.jpg';

    // Vérifier si le fichier existe avant de le supprimer
    if (file_exists($cheminFichier)) {
        unlink($cheminFichier);
    }

    // redirection vers la page index.php
    header('Location: ../index.php');
    exit(); //===> Fin du script
}


//_______________________________________________________________
/**
 * Affichage de la fenêtre modale de confirmation de suppression
 * 
 * @return  void
 */
function confirmationSupp() {
    if(!parametresControle('post', ['btnSuppArticle'])) {
        sessionExit();
    }

    // Vérification de GET et déchiffrement de l'id
    $id = verifGet('article', 'article');
    $id_chiffre = chiffrerSignerURL($id);

    echo 
    '<div class="modal">',
        '<div class="modal-content">',
            '<h3>Êtes-vous sûr de vouloir supprimer cet article ?</h3>',
            '<form method="post" action="edition.php?article=', $id_chiffre, '">',
                '<table>',
                    '<tr>',
                        '<td>',
                            '<input type="submit" name="btnComfirmerSuppArticle" value="Supprimer"> ',
                            '<input type="submit" name="btnAnnulerSuppArticle" value="Annuler"> ',
                        '</td>',
                    '</tr>',
                '</table>',
            '</form>',
        '</div>',
    '</div>';
}
