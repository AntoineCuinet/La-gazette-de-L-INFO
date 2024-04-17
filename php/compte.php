<?php

// chargement des bibliothèques de fonctions
require_once('./bibli_gazette.php');
require_once('./bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage ou reprise de la session
session_start();


// si l'utilisateur n'est pas authentifié, on le redirige sur la page index.php
if (! estAuthentifie()){
    header('Location: ../index.php');
    exit;
}

if (isset($_POST['btnModifInfo'])) {
    $erreursInfos = traitementModifInfo();
    $erreursMDP = false;
} else if (isset($_POST['btnModifMDP'])) {
    $erreurMDP = traitementModifMDP();
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

// envoi du buffer
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

    if (is_array($errs)) {
        echo    '<div class="erreur">Les erreurs suivantes ont été relevées :',
                    '<ul>';
        foreach ($errs as $e) {
            echo        '<li>', $e, '</li>';
        }
        echo        '</ul>',
                '</div>';
    }
    
    
    echo '<form method="post" action="inscription.php">',
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


    // TODO: modifier la date pour la faire correspondre, voir cours (list($annee, $mois, $jour) = explode('-', $_POST['naissance']);)
    $naissance = $values['utDateNaissance'];
    affLigneInput('Votre nom :', array('type' => 'text', 'name' => 'nom', 'value' => $values['utNom'], 'required' => null));
    affLigneInput('Votre prénom :', array('type' => 'text', 'name' => 'prenom', 'value' => $values['utPrenom'], 'required' => null));
    affLigneInput('Votre date de naissance :', array('type' => 'date', 'name' => 'naissance', 'value' => $naissance, 'required' => null));
    affLigneInput('Votre email :', array('type' => 'email', 'name' => 'email', 'value' => $values['utEmail'], 'required' => null));
    
    echo
                    '<tr>',
                        '<td colspan="2">',
                            '<label><input type="checkbox" name="cbSpam" value="1"',
                            $values['utMailsPourris'], ' checked',
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
        echo    '<div class="erreur">Erreur lors de la modification du mot de passe.';
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
    return array();
}

//_______________________________________________________________
/**
 * Traitement de la modification du mot de passe
 *
 * @return  bool    Booléen à true si une erreur est rencontrée, false sinon
 */
function traitementModifMDP(): bool {
    return true;
}