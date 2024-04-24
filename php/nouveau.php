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

if (isset($_POST['btnCreerArticle'])) {
    $err = traitementAjoutAr();
} else {
    $err = null;
}

// génération du contenu de la page
affContenuL($err);

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
 * @param   array   $err    tableau associatif contenant les erreurs de saisie
 * 
 * @return  void
 */
function affContenuL(?array $err) : void {
    echo '<main>',
    '<section>',
        '<h2>Créer votre nouvel article</h2>',
        '<p>Pour créer un article, le titre, le résumé et le contenu ne doivent pas êtres vides.</p>';

        if (! empty($err)) {
            echo    '<div class="erreur">Les erreurs suivantes ont été relevées lors de l\'enregistrement de l\'article :',
                    '<ul>';
        foreach ($err as $e) {
            echo        '<li>', $e, '</li>';
        }
        echo        '</ul>',
                '</div>';
        } else if (isset($_POST['btnCreerArticle'])) {
            echo    '<div class="succes">L\'article à bien été créer.</div>';
        }

        echo '<form method="post" action="nouveau.php" class="nouveau">',
        '<table>',
        '<input type="hidden" name="MAX_FILE_SIZE" value="10000">';

    affLigneInput('Sélectionnez le fichier à télécharger (facultatif) :', array('type' => 'file', 'name' => 'leFichier', 'value' => ''));
    affLigneInput('Le titre : ', array('type' => 'text', 'name' => 'title', 'value' => '', 'required' => null));

    echo '<tr>',
            '<td><label for="resumAr">Le résumé de l\'article :</label></td>',
            '<td><textarea id="resumAr" name="resumAr" rows="20" cols="40" placeholder="Saisissez ici le résumé de l\'article" required></textarea></td>',
        '</tr>',
        '<tr>',
            '<td><label for="textAr">Le contenu de l\'article : <br>(Utilisez le langage de balisage ad hoc de type BBCode)</label></td>',
            '<td><textarea id="textAr" name="textAr" rows="30" cols="40" placeholder="Saisissez ici le contenu de l\'article (type BBCode)" required></textarea></td>',
        '</tr>';

        echo '<tr>',
            '<td colspan="2">',
                '<input type="submit" name="btnCreerArticle" value="Créer l\'article !"> ',
                '<input type="reset" value="Réinitialiser">',
            '</td>',
        '</tr>',
        '</table>',
        '</form>',
        '</section>',
    '</main>';
}


// TODO: Lors de l'upload d'une nouvelle image d'illustration, la date de dernière modification de l'article doit être mise à jour.
// TODO: fonction de vérif à faire 
// TODO: ajouter l'upload d'une image + vérif 
        //Les informations sur le fichier sont récupérées dans le tableau super-global $_FILES

// Limiter la taille maximum autorisée des fichiers uploadés -> upload_max_filesize (2 Mo) et post_max_size (8 Mo)
// Si le fichier est trop gros, alors $_FILES['leFichier']['error'] = 2


// TODO: à utiliser / modifier
function traitementAjoutAr() {
    // TODO: à refaire 


    if(! isset($_FILES)) {
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

    else {
        return null;
    }
}




// TODO: à utiliser / modifier
function vérifUpload() {
    // Vérification de l’extension du fichier
    $oks = array('.gif', '.png', '.jpg');
    $nom = $_FILES['leFichier']['name'];
    $ext = strtolower(substr($nom, strrpos($nom, '.')));
    if (! in_array($ext, $oks)) {
    // extension du fichier non autorisée
    }
}

// TODO: à utiliser / modifier
function verifTypeUpload() {
    // Vérification du contenu du fichier avec son type MIME
    $oks = array('image/gif', 'image/png', 'image/jpeg');
    // $_FILES['leFichier']['type'] inutilisable car pas fiable
    $type = mime_content_type($_FILES['leFichier']['tmp_name']);
    if (! in_array($type, $oks)) {
    // contenu du fichier non autorisé
    }
}



// TODO: à utiliser / modifier
// Déplacer le fichier vers le répertoire final de stockage. 
    // is_uploaded_file() vérifie que l'on manipule bien un fichier uploadé 
    // move_uploaded_file() déplace le fichier temporaire
function depotFile() {
    $Dest = 'repertDestUpload/'.$_FILES['leFichier']['name'];
    if ($_FILES['leFichier']['error'] === 0 && @is_uploaded_file($_FILES['leFichier']['tmp_name']) && @move_uploaded_file($_FILES['leFichier']['tmp_name'], $Dest)) {
        echo $_FILES['leFichier']['name'], ' uploadé';
    } else {
        echo 'Erreur lors de l\'upload';
    }
}