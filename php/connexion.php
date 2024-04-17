<?php

// chargement des bibliothèques de fonctions
require_once('bibli_gazette.php');
require_once('bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage ou reprise de la session
session_start();

// si l'utilisateur est déjà authentifié
if (estAuthentifie()){
    header ('Location: ../index.php');
    exit();
}

if (isset($_POST['btnConnexion'])) {
    $erreur = traitementConnexionL(); // ne revient pas quand les données soumises sont valides
}
else{
    $erreur = null;
}



// génération de la page
affEntete('Connexion');

affFormulaireL($erreur);

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
 * @param ?bool    $err    Booleen à true si il y a des erreurs, false sinon
 *
 * @return void
 */
function affFormulaireL(?bool $err): void {
    // réaffichage des données soumises en cas d'erreur, sauf les mots de passe
    if (isset($_POST['btnConnexion'])){
        $values = htmlProtegerSorties($_POST);
    }
    else{
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
 *  @return bool    un booleen à true si il y a des erreurs, false sinon
 */
function traitementConnexionL(): bool {
    if( !parametresControle('post', ['pseudo', 'passe', 'btnConnexion'])) {
        sessionExit();
    }

    $erreur = null;

    // vérification du pseudo et des mots de passe
    $pseudo = $_POST['pseudo'] = trim($_POST['pseudo']);
    $_POST['passe'] = trim($_POST['passe']);


    // calcul du hash du mot de passe pour enregistrement dans la base.
    // $passe = password_hash($_POST['passe'], PASSWORD_DEFAULT);

    // var_dump($passe);


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
    
    // vérification du mot de passe
    if (! password_verify($_POST['passe'], $passe)) {
        $erreur = true;
    }

    // Libération de la mémoire associée au résultat de la requête
    mysqli_free_result($res);


    // si erreurs --> retour
    if ($erreur) {
        // fermeture de la connexion à la base de données
        mysqli_close($bd);
        return $erreur;   //===> FIN DE LA FONCTION
    }


    // fermeture de la connexion à la base de données
    mysqli_close($bd);

    $_SESSION['pseudo'] = $pseudo;
    $_SESSION['redacteur'] = $redacteur; // utile pour l'affichage de la barre de navigation

    if (isset($_SERVER['HTTP_REFERER'])) {
        // Récupérer l'URL référente
        $referer = $_SERVER['HTTP_REFERER'];

        header('Location: '. $referer);
        exit(); //===> Fin du script
    }
    header('Location: ../index.php');
    exit(); //===> Fin du script
}
