<?php
//_____________________________________________________________\\
//                                                             \\
//                      La Gazette de L-INFO                   \\
//     Page de rédaction d'un nouvel article (nouveau.php)     \\
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

affEntete('Rédaction d\'un nouvel article');

if (isset($_POST['btnCreerArticle'])) {
    $err = traitementAjoutAr();
} else {
    $err = null;
}

// Génération du contenu de la page
affContenuL($err);

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
 * @param   array   $err    tableau associatif contenant les erreurs de saisie
 * 
 * @return  void
 */
function affContenuL(?array $err): void {
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

        echo '<form method="post" action="nouveau.php" class="nouveau" enctype="multipart/form-data">',
        '<table>';

    affLigneInput('Sélectionnez le fichier à télécharger (facultatif) :', array('type' => 'file', 'name' => 'file'));
    echo '<input type="hidden" name="MAX_FILE_SIZE" value="102400">';
    affLigneInput('Le titre de l\'article : ', array('type' => 'text', 'name' => 'title', 'value' => '', 'required' => null));

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


//_______________________________________________________________
/**
 * Traitement de l'ajout d'un nouvel article
 *
 * @return  array|null  tableau associatif contenant les erreurs de saisie ou null si l'ajout a été effectué
 */
function traitementAjoutAr(): array|null {
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

    // Vérification de l'image (si elle est présente)
    if(! empty($_FILES)) {
        if ($_FILES['file']['error'] === 0) {

            // Vérification de la taille du fichier, si elle est supérieure à 100 Ko
            $maxSize = 100 * 1024; // 100 Ko
            $file_size = $_FILES['file']['size'];
            if ($file_size > $maxSize) {
                $erreurs[] = 'La taille de l\'image dépasse 100 Ko.';
            }

            // Vérification de l’extension du fichier (JPG)
            $oks = array('.jpg');
            $nom = $_FILES['file']['name'];
            $ext = strtolower(substr($nom, strrpos($nom, '.')));
            if (! in_array($ext, $oks)) {
                $erreurs[] = 'Le fichier n\'est pas au format JPG.';
            }

            // Vérification du contenu du fichier avec son type MIME
            $oks = array('image/jpeg');
            $type = mime_content_type($_FILES['file']['tmp_name']);
            if (! in_array($type, $oks)) {
                $erreurs[] = 'Le contenu du fichier n\'est pas autorisé.';
            }

            // Vérifie si les dimensions correspondent au format 4/3
            $image_info = getimagesize($_FILES['file']['tmp_name']);
            if ($image_info[0] / $image_info[1] !== 4 / 3) {
                $erreurs[] = 'Les dimensions de l\'image ne correspondent pas au format 4/3.';
            }
        } else {
            $erreurs[] = 'Erreur lors du téléchargement de l\'image, réessayer.';
        }
    }

    // Ajout de l'article
    if (empty($erreurs)) {
        $date = date('Ymdhm');
        $pseudo = $_SESSION['pseudo'];

        // ouverture de la connexion à la base
        $bd = bdConnect();

        // protection des entrées
        $title2 = mysqli_real_escape_string($bd, $title);
        $resumAr2 = mysqli_real_escape_string($bd, $resumAr);
        $textAr2 = mysqli_real_escape_string($bd, $textAr);
        
        // Requête d'insertion
        $sql = "INSERT INTO article (arTitre, arResume, arTexte, arDatePubli, arDateModif, arAuteur)
        VALUES ('$title2', '$resumAr2', '$textAr2', '$date', NULL, '$pseudo')";

        bdSendRequest($bd, $sql);

        if (! empty($_FILES)){
            // Vérification du droit d'écriture sur le répertoire upload
            $uploadDir = '../upload/';
            if (!file_exists($uploadDir)) {
                // Le répertoire n'existe pas, on le créer
                mkdir($uploadDir, 0700, true);
            }
            if (!is_writable($uploadDir)) {
                chmod($uploadDir, 0700);
            }

            // Requête pour récupérer l'id de l'article créer
            $sql = "SELECT arID FROM article 
            WHERE arAuteur = '$pseudo'
            ORDER BY arDatePubli DESC
            LIMIT 1;";

            $result = bdSendRequest($bd, $sql);
            $row = mysqli_fetch_assoc($result);
            $ID = $row['arID'];

            // TODO: Redimensionne l'image, vous devez utiliser une bibliothèque de traitement d'image comme GD ou Imagick
            // Stockage de l'image
            $Dest = $uploadDir . $ID . '.jpg';
            if ($_FILES['file']['error'] === 0 && @is_uploaded_file($_FILES['file']['tmp_name']) && @move_uploaded_file($_FILES['file']['tmp_name'], $Dest)) {
                echo $_FILES['file']['name'], ' uploadé';
            }
        }

        // fermeture de la connexion à la base de données
        mysqli_close($bd);

        return null;
    } else {
        return $erreurs;
    }
}


// TODO: à utiliser / modifier
function vérifUpload() {

}

// TODO: à utiliser / modifier
function verifTypeUpload() {
}

// TODO: à utiliser / modifier
function depotFile() {
}