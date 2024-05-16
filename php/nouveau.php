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
    // réaffichage des données soumises en cas d'erreur
    if (isset($_POST['btnCreerArticle'])){
        $values = htmlProtegerSorties($_POST);
    } else {
        $values = ['title' => '', 'resumAr' => '', 'textAr' => ''];
    }
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

            // ouverture de la connexion à la base
            $bd = bdConnect();
            // Requête pour récupérer l'id de l'article créé
            $sql = "SELECT arID FROM article 
                    WHERE arAuteur = '{$_SESSION['pseudo']}'
                    ORDER BY arDatePubli DESC
                    LIMIT 1;";
            $result = bdSendRequest($bd, $sql);

            // fermeture de la connexion à la base de données
            mysqli_close($bd);

            $row = mysqli_fetch_assoc($result);
            $id = $row['arID'];
            // Chiffrement de l'id pour le passage dans l'URL
            $id_chiffre = chiffrerSignerURL($id); 
            echo    '<div class="succes">L\'article à bien été créer. <a href="./article.php?id=', $id_chiffre, '">cliquez ici pour le voir !</a></div>';
        }

        echo '<form method="post" action="nouveau.php" class="nouveau" enctype="multipart/form-data">',
        '<table>';

    affLigneInput('Sélectionnez le fichier à télécharger (facultatif) :', array('type' => 'file', 'name' => 'file'));
    echo '<tr><td colspan="2"><input type="hidden" name="MAX_FILE_SIZE" value="102400"></td></tr>';
    affLigneInput('Le titre de l\'article : ', array('type' => 'text', 'name' => 'title', 'value' => $values['title'], 'required' => null));

    echo '<tr>',
            '<td><label for="resumAr">Le résumé de l\'article :</label></td>',
            '<td><textarea id="resumAr" name="resumAr" rows="20" cols="40" placeholder="Saisissez ici le résumé de l\'article" required>', $values['resumAr'], '</textarea></td>',
        '</tr>',
        '<tr>',
            '<td><label for="textAr">Le contenu de l\'article : <br>(Utilisez le langage de balisage ad hoc de type BBCode)</label></td>',
            '<td><textarea id="textAr" name="textAr" rows="30" cols="40" placeholder="Saisissez ici le contenu de l\'article (type BBCode)" required>', $values['textAr'], '</textarea></td>',
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
    if(!parametresControle('post', ['MAX_FILE_SIZE', 'title', 'resumAr', 'textAr', 'btnCreerArticle'])) {
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

        if (isset($_FILES['file']) && !empty($_FILES['file']['name'])){
            // Vérification du droit d'écriture sur le répertoire upload
            $uploadDir = '../upload/';
            verifDroitEcriture($uploadDir);

            // Requête pour récupérer l'id de l'article créer
            $sql = "SELECT arID FROM article 
            WHERE arAuteur = '$pseudo'
            ORDER BY arDatePubli DESC
            LIMIT 1;";

            $result = bdSendRequest($bd, $sql);
            $row = mysqli_fetch_assoc($result);
            $ID = $row['arID'];

            // Enregistrement du fichier
            depotFile($ID, $uploadDir);
        }

        // fermeture de la connexion à la base de données
        mysqli_close($bd);

        return null;
    } else {
        return $erreurs;
    }
}
