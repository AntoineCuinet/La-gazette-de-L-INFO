<?php
//_____________________________________________________________\\
//                                                             \\
//                     La Gazette de L-INFO                    \\
//            Page du compte utilisateur (compte.php)          \\
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


// Si l'utilisateur n'est pas authentifié, on le redirige sur la page d'acceuil (index.php)
if (! estAuthentifie()){
    header('Location: ../index.php');
    exit;
}

if (isset($_POST['btnModifInfo'])) {
    $erreursInfos = traitementModifInfo();
    $erreursMDP = false;
} else if (isset($_POST['btnModifMDP'])) {
    $erreursMDP = traitementModifMDP();
    $erreursInfos = null;
} else {
    $erreursInfos = null;
    $erreursMDP = false;
}


affEntete('Mon compte');


echo '<main>';
affFormulaireModifInfoL($erreursInfos);
affFormulaireModifMDPL($erreursMDP);
echo '</main>';

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
 * Affichage du contenu du formulaire de modification des informations
 *
 * @param   array   $errs   Tableau d'erreurs
 * 
 * @return  void
 */
function affFormulaireModifInfoL(?array $errs): void {
    // réaffichage des données soumises en cas d'erreur
    if (isset($_POST['btnModifInfo'])){
        $values = htmlProtegerSorties($_POST);
    } 
    
    $pseudo = $_SESSION['pseudo'];

    // ouverture de la connexion à la base
    $bd = bdConnect();

    // Requête SQL pour récupérer les articles
    $sql = "SELECT *
            FROM utilisateur
            WHERE utPseudo = '$pseudo'";
    $result = bdSendRequest($bd, $sql);

    $values = mysqli_fetch_assoc($result);
    // Fermeture de la connexion au serveur de BdD
    mysqli_close($bd);


    echo '<section>',
        '<h2>Informations personnelles</h2>',
        '<p>Vous pouvez modifier les informations suivantes.</p>';

    if (is_array($errs) && ! empty($errs)) {
        echo    '<div class="erreur">Les erreurs suivantes ont été relevées :',
                    '<ul>';
        foreach ($errs as $e) {
            echo        '<li>', $e, '</li>';
        }
        echo        '</ul>',
                '</div>';
    } else if(isset($_POST['btnModifInfo'])) {
        echo    '<div class="succes">Les informations ont été mises à jour avec succès.</div>';
    }
    
    
    echo '<form method="post" action="compte.php">',
        '<table>',
        '<tr>',
        '<td>Votre civilité :</td>',
        '<td>';


    // Switch sur la civilité pour faire correspondre h,f,nb aux chiffres 1,2,3
    $sex = 0;
    switch ($values['utCivilite']) {
        case 'h':
            $sex = 1;
            break;
        case 'f':
            $sex = 2;
            break;
        case 'nb':
            $sex = 3;
            break;
    }
    $radios = [1 => 'Monsieur', 2 => 'Madame', 3 => 'Non binaire'];
    foreach ($radios as $value => $label){
            echo    '<label><input type="radio" name="radSexe" value="', $value, '"',
                    $value === $sex ? ' checked' : '', '> ', $label, '</label> ';
    }
    echo    '</td>',
        '</tr>';


    // Formatage de la date de naissance
    $naissance = $values['utDateNaissance'];
    // Extraire les parties de la date
    $annee = substr($naissance, 0, 4);
    $mois = substr($naissance, 4, 2);
    $jour = substr($naissance, 6, 2);
    // Concaténer les parties avec des tirets pour former la date au format AAAA-MM-JJ
    $date_formattee = $annee . "-" . $mois . "-" . $jour;

    affLigneInput('Votre nom :', array('type' => 'text', 'name' => 'nom', 'value' => $values['utNom'], 'required' => null));
    affLigneInput('Votre prénom :', array('type' => 'text', 'name' => 'prenom', 'value' => $values['utPrenom'], 'required' => null));
    affLigneInput('Votre date de naissance :', array('type' => 'date', 'name' => 'naissance', 'value' => $date_formattee, 'required' => null));
    affLigneInput('Votre email :', array('type' => 'email', 'name' => 'email', 'value' => $values['utEmail'], 'required' => null));
    
    echo
                    '<tr>',
                        '<td colspan="2">',
                            '<label><input type="checkbox" name="cbSpam" value="1"',
                            $values['utMailsPourris'] ? ' checked' : '',
                                '> J\'accepte de recevoir des tonnes de mails pourris</label>',
                        '</td>',
                    '</tr>',
                    '<tr>',
                        '<td colspan="2">',
                            '<input type="submit" name="btnModifInfo" value="Enregister"> ',
                            '<input type="reset" value="Réinitialiser">',
                        '</td>',
                    '</tr>',
                '</table>',
            '</form>';
    echo '</section>';
}


//_______________________________________________________________
/**
 * Affichage du contenu du formulaire de modification du mot de passe
 *
 * @param   bool   $err   Booléen sur la réussite de la modification
 * 
 * @return  void
 */
function affFormulaireModifMDPL(bool $err): void {
    echo '<section>',
        '<h2>Mot de passe</h2>',
        '<p>Vous pouvez modifier votre mot de passe ci-dessous.</p>';


    if ($err) {
        echo    '<div class="erreur">Erreur lors de la modification du mot de passe.</div>';
    } else if (isset($_POST['btnModifMDP'])) {
        echo    '<div class="succes">Le mot de passe a été changé avec succès.</div>';
    }

    echo '<form method="post" action="compte.php">',
                '<table>';

    affLigneInput(  'Choisissez un mot de passe :', array('type' => 'password', 'name' => 'passe1', 'value' => '',
                    'placeholder' => LMIN_PASSWORD . ' caractères minimum', 'required' => null));
    affLigneInput('Répétez le mot de passe :', array('type' => 'password', 'name' => 'passe2', 'value' => '', 'required' => null));

    echo '<tr>',
                '<td colspan="2">',
                    '<input type="submit" name="btnModifMDP" value="Enregister"> ',
                '</td>',
                '</tr>',
            '</table>',
        '</form>',
    '</section>';
}


//_______________________________________________________________
/**
 * Traitement de la modification des informations
 *
 * @return  array    Tableau contenant les erreurs rencontrées (si il y en a)
 */
function traitementModifInfo(): array {
    $erreurs = [];
    $pseudo = $_SESSION['pseudo'];

    // vérification de la civilité
    if (! isset($_POST['radSexe'])){
        $erreurs[] = 'Vous devez choisir une civilité.';
    }
    else if (! (estEntier($_POST['radSexe']) && estEntre($_POST['radSexe'], 1, 3))){
        sessionExit();
    }

    // vérification des noms et prénoms
    $expRegNomPrenom = '/^[[:alpha:]]([\' -]?[[:alpha:]]+)*$/u';
    $nom = $_POST['nom'] = trim($_POST['nom']);
    $prenom = $_POST['prenom'] = trim($_POST['prenom']);
    verifierTexte($nom, 'Le nom', $erreurs, LMAX_NOM, $expRegNomPrenom);
    verifierTexte($prenom, 'Le prénom', $erreurs, LMAX_PRENOM, $expRegNomPrenom);

    // vérification du format de l'adresse email
    $email = $_POST['email'] = trim($_POST['email']);
    verifierTexte($email, 'L\'adresse email', $erreurs, LMAX_EMAIL);
    if(! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreurs[] = 'L\'adresse email n\'est pas valide.';
    }

    // vérification de la date de naissance
    if (empty($_POST['naissance'])){
        $erreurs[] = 'La date de naissance doit être renseignée.';
    }
    else{
        if(! preg_match('/^\\d{4}(-\\d{2}){2}$/u', $_POST['naissance'])){ //vieux navigateur qui ne supporte pas le type date ?
            $erreurs[] = 'la date de naissance doit être au format "AAAA-MM-JJ".';
        }
        else{
            list($annee, $mois, $jour) = explode('-', $_POST['naissance']);
            if (!checkdate($mois, $jour, $annee)) {
                $erreurs[] = 'La date de naissance n\'est pas valide.';
            }
            else if (mktime(0,0,0,$mois,$jour,$annee + AGE_MINIMUM) > time()) {
                $erreurs[] = 'Vous devez avoir au moins '. AGE_MINIMUM. ' ans pour vous inscrire.';
            }
        }
    }

    // vérification de la valeur de $_POST['cbSpam'] si l'utilisateur accepte de recevoir des mails pourris
    if (isset($_POST['cbSpam']) && $_POST['cbSpam'] !== '1'){
        sessionExit();
    }

    // si erreurs --> retour
    if (count($erreurs) > 0) {
        return $erreurs;   //===> FIN DE LA FONCTION
    }


    // ouverture de la connexion à la base
    $bd = bdConnect();

    $email = mysqli_real_escape_string($bd, $email);

    $sql = "SELECT utEmail 
            FROM utilisateur 
            WHERE utEmail = '$email'
            AND utPseudo != '$pseudo'";

    $res = bdSendRequest($bd, $sql);

    while($tab = mysqli_fetch_assoc($res)) {
        if ($tab['utEmail'] == $email){
            $erreurs[] = 'L\'adresse email est déjà utilisée.';
        }
    }
    // Libération de la mémoire associée au résultat de la requête
    mysqli_free_result($res);


    // si erreurs --> retour
    if (count($erreurs) > 0) {
        // fermeture de la connexion à la base de données
        mysqli_close($bd);
        return $erreurs;   //===> FIN DE LA FONCTION
    }

    $dateNaissance = $annee*10000 + $mois*100 + $jour;

    $nom = mysqli_real_escape_string($bd, $nom);
    $prenom = mysqli_real_escape_string($bd, $prenom);

    $civilite = (int) $_POST['radSexe'];
    $civilite = $civilite == 1 ? 'h' : ($civilite == 2 ? 'f' : 'nb');

    $mailsPourris = isset($_POST['cbSpam']) ? 1 : 0;

    // Requête SQL pour mettre à jour les informations dans la table utilisateur
    $sql = "UPDATE utilisateur
            SET utCivilite = '$civilite', utNom = '$nom', utPrenom = '$prenom', utDateNaissance = $dateNaissance, utEmail = '$email', utMailsPourris = $mailsPourris
            WHERE utPseudo = '$pseudo'";

    bdSendRequest($bd, $sql);

    // fermeture de la connexion à la base de données
    mysqli_close($bd);

    return array();
}


//_______________________________________________________________
/**
 * Traitement de la modification du mot de passe
 *
 * @return  bool    Booléen à true si une erreur est rencontrée, false sinon
 */
function traitementModifMDP(): bool {
    // vérification des mots de passe
    $_POST['passe1'] = trim($_POST['passe1']);
    $_POST['passe2'] = trim($_POST['passe2']);
    if ($_POST['passe1'] !== $_POST['passe2']) {
        return true;
    }
    $nb = mb_strlen($_POST['passe1'], encoding:'UTF-8');
    if ($nb < LMIN_PASSWORD){
        return true;
    }

    // ouverture de la connexion à la base
    $bd = bdConnect();

    // calcul du hash du mot de passe pour enregistrement dans la base.
    $passe = password_hash($_POST['passe1'], PASSWORD_DEFAULT);
    $passe = mysqli_real_escape_string($bd, $passe);

    $pseudo = $_SESSION['pseudo'];

    // Requête SQL pour mettre à jour le mot de passe dans la table utilisateur
    $sql = "UPDATE utilisateur
            SET utPasse = '$passe'
            WHERE utPseudo = '$pseudo'";

    bdSendRequest($bd, $sql);

    // fermeture de la connexion à la base de données
    mysqli_close($bd);

    return false;
}