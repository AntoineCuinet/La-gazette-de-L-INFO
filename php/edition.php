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
    // TODO: à utiliser / modifier
    // echo '<script>alert("Étes vous sûr de vouloir supprimer cet article ?");</script>';
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
function affContenuL(?array $errs): void {
    if (! parametresControle('get', ['article'])){
        affErreur('Il faut utiliser une URL de la forme : http://..../php/article.php?id=XXX');
        return; // ==> fin de la fonction
    }

    // Déchiffrement de l'URL
    $id = dechiffrerSignerURL($_GET['article']);

    if (! estEntier($id)){
        affErreur('L\'identifiant doit être un entier');
        return; // ==> fin de la fonction
    }

    if ($id <= 0){
        affErreur('L\'identifiant doit être un entier strictement positif');
        return; // ==> fin de la fonction
    }

    echo '<main>';
    affContenuArticleL($errs, $id);
    affContenuSuppL($id);
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
function affContenuArticleL(?array $err, int $id): void {

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
        return; // ==> fin de la fonction
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
            echo    '<div class="succes">L\'article à bien été modifié.</div>';
        }

        // chiffrement de l'id
        $id_chiffre = chiffrerSignerURL($id);
        echo '<form method="post" action="edition.php?article=', $id_chiffre, '" class="nouveau">',
        '<table>',
        '<input type="hidden" name="MAX_FILE_SIZE" value="10000">';

    affLigneInput('Sélectionnez le fichier à télécharger (facultatif) :', array('type' => 'file', 'name' => 'file', 'value' => ''));
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
    echo '<form method="post" action="edition.php?article=', $id_chiffre, '.php">',
            '<input type="hidden" name="article_id" value="', $id, '">', 
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
    // TODO: à utiliser / modifier
    // if(! parametresControle('post', ['title', 'resumAr', 'textAr', 'btnCreerArticle'], ['file'])) {
    //     sessionExit();
    // }

    $erreurs = [];


    // Vérification du titre
    $title = $_POST['title'] = trim($_POST['title']);
    if (empty($title)) {
        $erreurs[] = 'Le titre ne doit pas être vide.';
    } else if (mb_strlen($title) > LMAX_TITRE) {
        $erreurs[] = 'Le titre ne doit pas dépasser ' . LMAX_TITRE . ' caractères.';
    }

    // Vérification du résumé
    $resumAr = $_POST['resumAr'] = trim($_POST['resumAr']);
    if (empty($resumAr)) {
        $erreurs[] = 'Le résumé ne doit pas être vide.';
    }

    // Vérification du texte
    $textAr = $_POST['textAr'] = trim($_POST['textAr']);
    if (empty($textAr)) {
        $erreurs[] = 'Le texte ne doit pas être vide.';
    }


    // Vérification de l'image
    if(! isset($_FILES)) {
        // TODO: à utiliser / modifier
        // TODO: remplacement encienne image si existante par nouvelle si upload
        // Validation de l'image
        if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image_info = getimagesize($_FILES['image']['tmp_name']);
            $file_size = $_FILES['image']['size'];

            // Vérifie si le fichier est une image au format JPG
            if ($image_info !== false && $image_info['mime'] === 'image/jpeg') {
                // Vérifie si la taille du fichier est inférieure à 100 Ko
                if ($file_size <= 100 * 1024) {
                    // Vérifie si les dimensions correspondent au format 4/3
                    if ($image_info[0] / $image_info[1] === 4 / 3) {
                        // Redimensionne l'image si nécessaire
                        // Note: vous devez utiliser une bibliothèque de traitement d'image comme GD ou Imagick

                        // Stockage de l'image
                        $upload_dir = '/chemin/vers/upload/';
                        $file_name = uniqid('image_') . '.jpg'; // Génère un nom de fichier unique
                        $upload_path = $upload_dir . $file_name;

                        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                            // Mise à jour de la date de dernière modification de l'article
                            // Assurez-vous d'avoir une méthode pour identifier et mettre à jour l'article associé
                        } else {
                            echo "Erreur lors de l'enregistrement de l'image.";
                        }
                    } else {
                        echo "Les dimensions de l'image ne correspondent pas au format 4/3.";
                    }
                } else {
                    echo "La taille de l'image dépasse 100 Ko.";
                }
            } else {
                echo "Le fichier n'est pas au format JPG.";
            }
        } else {
            echo "Erreur lors du téléchargement de l'image.";
        }
    }

    // Modification de l'article
    if (empty($erreurs)) {
        $date = date('Ymdhm');
        $pseudo = $_SESSION['pseudo'];
        $id = dechiffrerSignerURL($_GET['article']);

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
    $idAr = $_POST['article_id'];

    // Ouverture de la connexion à la base
    $bd = bdConnect();
    
    // TODO: faire en cascade
    // Requête SQL pour supprimer le commentaire
    $sqlDel = "DELETE FROM article WHERE arID = '$idAr'";

    bdSendRequest($bd, $sqlDel);

    // Fermeture de la connexion à la base de données
    mysqli_close($bd);

    // TODO: supprimer aussi l'image associée dans upload

    // redirection vers la page index.php
    header('Location: ../index.php');
    exit(); //===> Fin du script
}
