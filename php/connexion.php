<?php
//_____________________________________________________________\\
//                                                             \\
//                     La Gazette de L-INFO                    \\
//            Page d'authentification (connexion.php)          \\
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

// Si l'utilisateur est déjà authentifié, on le redirige vers la page d'accueil
if (estAuthentifie()){
    header ('Location: ../index.php');
    exit();
}


// Détermination de la page de destination
if (isset($_POST['destinationURL'])) {
    $destinationURL = $_POST['destinationURL'];
} else if (empty($destinationURL)) {
    $destinationURL = (isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : '../index.php';
}


if (isset($_POST['btnConnexion'])) {
    $erreur = traitementConnexionL($destinationURL);
} else {
    $erreur = null;
}

// Génération de la page
affEntete('Connexion');

affFormulaireL($erreur, $destinationURL);

affPiedDePage();

ob_end_flush();


/*********************************************************
 *
 * Définitions des fonctions locales de la page
 *
 *********************************************************/
//_______________________________________________________________
/**
 * Contenu de la page : affichage du formulaire de connexion
 *
 * En absence de soumission (i.e. lors du premier affichage), $err est égal à null
 * Quand l'inscription échoue, $err est un booleen à true
 *
 * @param ?bool     $err               Booleen à true si il y a des erreurs, false sinon
 * @param string    $destinationURL    URL de la page de destination
 *
 * @return void
 */
function affFormulaireL(?bool $err, string $destinationURL): void {
    // réaffichage des données soumises en cas d'erreur, sauf les mots de passe
    if (isset($_POST['btnConnexion'])){
        $values = htmlProtegerSorties($_POST);
    } else {
        $values['pseudo'] = '';
    }

    echo
        '<main>',
            '<section>',
                '<h2>Formulaire de connexion</h2>',
                '<p>Pour vous authentifier, remplissez le formulaire ci-dessous.</p>';

    if ($err) {
        echo    '<p class="erreur">Échec d\'authentification. Utilisateur inconnu ou mot de passe incorrect.</p>';
    }


    echo
            '<form method="post" action="connexion.php">',
                '<table>';

    affLigneInput('Pseudo :', array('type' => 'text', 'name' => 'pseudo', 'value' => $values['pseudo'], 'required' => null));
    affLigneInput('Mot de passe :', array('type' => 'password', 'name' => 'passe', 'value' => '', 'required' => null));

    echo
                    '<tr><td colspan="2"><input type="hidden" name="destinationURL" value="', $destinationURL, '"></td></tr>',
                    '<tr>',
                        '<td colspan="2">',
                            '<input type="submit" name="btnConnexion" value="Se connecter"> ',
                            '<input type="reset" value="Annuler">',
                        '</td>',
                    '</tr>',
                '</table>',
            '</form>',
            '<p>',
                'Pas encore inscrit ? N\'attendez pas, <a href="./inscription.php">inscrivez-vous</a> !',
            '</p>',
        '</section>',
    '</main>';
}


/**
 * Traitement d'une demande de connexion
 *
 * Vérification de la validité des données
 * Si on trouve des erreurs => return un booleen à true
 * Sinon
 *     Connexion de l'utilisateur
 * FinSi
 * 
 * @param string    $destinationURL    URL de la page de destination
 *
 *  @return bool    un booleen à true si il y a des erreurs, false sinon
 */
function traitementConnexionL(string $destinationURL): bool {
    if( !parametresControle('post', ['pseudo', 'passe', 'destinationURL', 'btnConnexion'])) {
        sessionExit();
    }

    $erreur = null;

    // vérification du pseudo et des mots de passe
    $pseudo = $_POST['pseudo'] = trim($_POST['pseudo']);
    $_POST['passe'] = trim($_POST['passe']);


    // ouverture de la connexion à la base de données
    $bd = bdConnect();

    $sql = "SELECT utPseudo, utPasse, utRedacteur
            FROM utilisateur
            WHERE utPseudo = '$pseudo'";
    $res = bdSendRequest($bd, $sql);

    while($t = mysqli_fetch_assoc($res)){
        $passe = $t['utPasse'];
        $redacteur = $t['utRedacteur'];
    }

    // si $passe est vide --> retour (pas d'utilisateur avec ce pseudo)
    if (empty($passe)) {
        // fermeture de la connexion à la base de données
        mysqli_close($bd);
        $erreur = true;
        return $erreur;   //===> FIN DE LA FONCTION
    }
    
    // vérification du mot de passe
    if (! password_verify($_POST['passe'], $passe)) {
        $erreur = true;
    }

    // Libération de la mémoire associée au résultat de la requête
    mysqli_free_result($res);


    // si erreur --> retour
    if ($erreur) {
        // fermeture de la connexion à la base de données
        mysqli_close($bd);
        return $erreur;   //===> FIN DE LA FONCTION
    }


    // fermeture de la connexion à la base de données
    mysqli_close($bd);

    $_SESSION['pseudo'] = $pseudo;
    $_SESSION['redacteur'] = $redacteur; // utile pour l'affichage de la barre de navigation

    
    // Redirection vers la page de destination
    if (! empty($destinationURL)) {
        header('Location: '. $destinationURL);
        exit(); //===> Fin du script
    }

    header('Location: ../index.php');
    exit(); //===> Fin du script
}
