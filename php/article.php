<?php
//_____________________________________________________________\\
//                                                             \\
//                     La Gazette de L-INFO                    \\
//       Page de consultation d'un article (article.php)       \\
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

affEntete('Article');

// Génération du contenu de la page
affContenuL();

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
 * @return  void
 */
function affContenuL(): void {

    if (! parametresControle('get', ['id'])){
        affErreur('Il faut utiliser une URL de la forme : http://..../php/article.php?id=XXX');
        return; // ==> fin de la fonction
    }

    // Déchiffrement de l'URL
    $id = dechiffrerSignerURL($_GET['id']);

    if (! estEntier($id)){
        affErreur('L\'identifiant doit être un entier');
        return; // ==> fin de la fonction
    }

    if ($id <= 0){
        affErreur('L\'identifiant doit être un entier strictement positif');
        return; // ==> fin de la fonction
    }


    // ouverture de la connexion à la base de données
    $bd = bdConnect();


    // enregistrement d'un commentaire
    if (isset($_POST['btnAjoutCommentaire'])) {

        $textCom = $_POST['textCom'] = trim($_POST['textCom']);

        // Vérification de la validité du commentaire avant de l'envoyer dans la BD
        if (! empty($textCom) && strip_tags($textCom) == $textCom){
            $pseudo = $_SESSION['pseudo'];
            $date = date('Ymdhm');
            $textCom = htmlspecialchars($textCom);

            if (mb_strlen($textCom) > LMAX_COM){
                $textCom = mb_substr($textCom, 0, LMAX_COM);
            }
    
            $sqlAdd = "INSERT INTO commentaire (coAuteur, coTexte, coDate, coArticle)
                       VALUES ('$pseudo', '$textCom', '$date', '$id')";
    
            bdSendRequest($bd, $sqlAdd);
        }
    }


    // suppression d'un commentaire
    if (isset($_POST['btnSuprimerCommentaire'])) {
        $idCom = $_POST['commentaire_id'];

        // Requête SQL pour supprimer le commentaire
        $sqlDel = "DELETE FROM commentaire WHERE coID = '$idCom'";

        bdSendRequest($bd, $sqlDel);
    }
    

    // Récupération de l'article, des informations sur son auteur,
    // et de ses éventuelles commentaires
    // $id est un entier, donc pas besoin de le protéger avec mysqli_real_escape_string()
    $sql = "SELECT *
            FROM (article INNER JOIN utilisateur ON arAuteur = utPseudo)
            LEFT OUTER JOIN commentaire ON arID = coArticle
            WHERE arID = $id
            ORDER BY coDate DESC, coID DESC";

    $result = bdSendRequest($bd, $sql);


    // Fermeture de la connexion au serveur de BdD, réalisée le plus tôt possible
    mysqli_close($bd);

    // pas d'articles --> fin de la fonction
    if (mysqli_num_rows($result) == 0) {
        affErreur('L\'identifiant de l\'article n\'a pas été trouvé dans la base de données');
        // Libération de la mémoire associée au résultat de la requête
        mysqli_free_result($result);
        return; // ==> fin de la fonction
    }

    $tab = mysqli_fetch_assoc($result);

    // Mise en forme du prénom et du nom de l'auteur pour affichage dans le pied du texte de l'article
    // Exemple :
    // - pour 'johNnY' 'bigOUde', cela donne 'J. Bigoude'
    // - pour 'éric' 'merlet', cela donne 'É. Merlet'
    // À faire avant la protection avec htmlentities() à cause des éventuels accents
    $auteur = upperCaseFirstLetterLowerCaseRemainderL(mb_substr($tab['utPrenom'], 0, 1, encoding:'UTF-8')) . '. ' . upperCaseFirstLetterLowerCaseRemainderL($tab['utNom']);

    // ATTENTION : protection contre les attaques XSS
    $auteur = htmlProtegerSorties($auteur);

    // ATTENTION : protection contre les attaques XSS
    $tab = htmlProtegerSorties($tab);


    affContenuMainL($tab, $auteur, $id, $result);
}


//_______________________________________________________________
/**
 * Conversion d'une date format AAAAMMJJHHMM au format JJ mois AAAA à HHhMM
 *
 * @param  int      $date   la date à afficher.
 *
 * @return string           la chaîne qui représente la date
 */
function dateIntToStringFootL(int $date): string {
    // les champs date (coDate, arDatePubli, arDateModif) sont de type BIGINT dans la base de données
    // donc pas besoin de les protéger avec htmlentities()

    // si un article a été publié avant l'an 1000, ça marche encore ;)
    $minutes = substr($date, -2);
    $heure = (int)substr($date, -4, 2); //conversion en int pour supprimer le 0 de '07' pax exemple
    $jour = (int)substr($date, -6, 2);
    $mois = substr($date, -8, 2);
    $annee = substr($date, 0, -8);

    $months = getArrayMonths();

    return $jour. ' '. mb_strtolower($months[$mois - 1], encoding:'UTF-8'). ' '. $annee . ' à ' . $heure . 'h' . $minutes;
}


//___________________________________________________________________
/**
 * Renvoie une copie de la chaîne UTF8 transmise en paramètre après avoir mis sa
 * première lettre en majuscule et toutes les suivantes en minuscule
 *
 * @param  string   $str    la chaîne à transformer
 *
 * @return string           la chaîne résultat
 */
function upperCaseFirstLetterLowerCaseRemainderL(string $str): string {
    $str = mb_strtolower($str, encoding:'UTF-8');
    $fc = mb_strtoupper(mb_substr($str, 0, 1, encoding:'UTF-8'));
    return $fc.mb_substr($str, 1, mb_strlen($str), encoding:'UTF-8');
}



//_______________________________________________________________
/**
 * Conversion des balises BBCode en balises HTML
 *
 * @param  string  $text   le texte à convertir
 *
 * @return string          le texte converti
 */
function bbcodeToHtml(string $text): string {

    // Définition des règles de remplacement
    $bbcode_rules = array(
        '/\[p\](.*?)\[\/p\]/s' => '<p>$1</p>',
        '/\[gras\](.*?)\[\/gras\]/s' => '<strong>$1</strong>',
        '/\[it\](.*?)\[\/it\]/s' => '<em>$1</em>',
        '/\[citation\](.*?)\[\/citation\]/s' => '<blockquote>$1</blockquote>',
        '/\[liste\](.*?)\[\/liste\]/s' => '<ul>$1</ul>',
        '/\[item\](.*?)\[\/item\]/s' => '<li>$1</li>',
        '/\[br\]/' => '<br>',
        '/\[widget-deezer:(\d+):(\d+):(.*?)( .*?)\]/' => '<figure><iframe width="$1" height="$2" src="$3" allow="encrypted-media; clipboard-write"></iframe><figcaption>$4</figcaption></figure>',
        '/\[widget-deezer:(\d+):(\d+):(.*?)\]/' => '<iframe width="$1" height="$2" src="$3" allow="encrypted-media; clipboard-write"></iframe>',
        '/\[#(\d+)\]/' => '&#$1;',
        '/\[#x([0-9a-fA-F]+)\]/' => '&#x$1;'
    );

    // Boucle de traitement
    foreach ($bbcode_rules as $pattern => $replacement) {
        $text = preg_replace($pattern, $replacement, $text);
    } 
    
    // Gestion des URLs pour les balises a
    $text = preg_replace_callback('/\[a:(.*?)\](.*?)\[\/a\]/s', function($matches) {
        $url = $matches[1];
        $content = $matches[2];
        if (filter_var($url, FILTER_VALIDATE_URL) || file_exists($url)) {
            if (strpos($url, 'http') === 0) {
                return '<a href="' . $url . '" target="_blank">' . $content . '</a>';
            } else {
                return '<a href="' . $url . '">' . $content . '</a>';
            }
        }
    }, $text);

    // Retourne le texte avec les balises BBCode remplacées par les balises HTML correspondantes
    return $text;
}


//_______________________________________________________________
/**
 * Conversion des balises BBCode (seulement les unicodes) en balises HTML
 *
 * @param  string  $text   le texte à convertir
 *
 * @return string          le texte converti
 */
function bbcodeToHtmlUnicode(string $text): string {
    $text = htmlProtegerSorties($text);

    // Définition des règles de remplacement unicode
    $bbcode_rules = array(
        '/\[#(\d+)\]/' => '&#$1;',
        '/\[#x([0-9a-fA-F]+)\]/' => '&#x$1;'
    );

    // Boucle de traitement
    foreach ($bbcode_rules as $pattern => $replacement) {
        $text = preg_replace($pattern, $replacement, $text);
    }

    // Retourne le texte avec les balises BBCode unicode remplacées par les balises HTML correspondantes
    return $text;
}



//_______________________________________________________________
/**
 * Affichage du contenu main de la page
 * 
 * @param array         $tab        le tableau contenant les informations de l'article
 * @param string        $auteur     le nom de l'auteur de l'article
 * @param int           $id         l'identifiant de l'article
 * @param mysqli_result $result     le résultat de la requête SQL
 * 
 * @return void
 */
function affContenuMainL(array $tab, string $auteur, int $id, mysqli_result $result): void {
    echo
        '<main id="article">';

        // Affichage du bandeau pour modifier/supprimer l'article si utilisateur est rédacteur
        if (estAuthentifie() && $tab['utPseudo'] == $_SESSION['pseudo']) {
            // Chiffrement de l'id pour le passage dans l'URL
            $id_chiffre = chiffrerSignerURL($id);
    
            echo '<section class="modification-article">',
            '<p>Vous êtes l\'auteur de cet article, <a href="./edition.php?article=', $id_chiffre, '">cliquer ici pour le modifier</a>.</p>',
            '</section>';
        }

    $BBCode_article = bbcodeToHtml($tab['arTexte']);
    echo    '<article>',
                '<h3>', $tab['arTitre'], '</h3>',
                '<img src="../upload/', $tab['arID'], '.jpg" alt="Photo d\'illustration | ', $tab['arTitre'], '" onerror="this.style.display=\'none\';">',
                $BBCode_article,
                '<footer>',
                    'Par <a href="redaction.php#', $tab['utPseudo'], '">', $auteur, '</a>. ',
                    'Publié le ', dateIntToStringFootL($tab['arDatePubli']),
                    isset($tab['arDateModif']) ? ', modifié le '. dateIntToStringFootL($tab['arDateModif']) : '',
                '</footer>',
            '</article>';

    // pour accéder une seconde fois au premier enregistrement de la sélection
    mysqli_data_seek($result, 0);

    affContenuComL($tab, $id, $result);

    echo '</main>';
}


//_______________________________________________________________
/**
 * Affichage des commentaires de l'article
 * 
 * @param array         $tab        le tableau contenant les informations de l'article
 * @param int           $id         l'identifiant de l'article
 * @param mysqli_result $result     le résultat de la requête SQL
 * 
 * @return void
 */
function affContenuComL(array $tab, int $id, mysqli_result $result): void {

    // chiffrement de l'id
    $id_chiffre = chiffrerSignerURL($id);

    // Génération du début de la zone de commentaires
    echo '<section>',
            '<h2>Réactions</h2>';

    // s'il existe des commentaires, on les affiche un par un.
    if (isset($tab['coID'])) {
        echo '<ul>';
        while ($tab = mysqli_fetch_assoc($result)) {
            $commentaire_id = $tab['coID'];
            if (estAuthentifie() && $tab['coAuteur'] === $_SESSION['pseudo']) {
                echo '<li class="auteurCom">',
                '<form method="post" action="article.php?id=', $id_chiffre, '">',
                '<input type="hidden" name="commentaire_id" value="', $commentaire_id, '">',
                '<input type="submit" name="btnSuprimerCommentaire" value="Supprimer le commentaire">',
                '</form>';
            } else if (estAuthentifie() && $tab['arAuteur'] === $_SESSION['pseudo']) {
                echo '<li class="auteurAr">',
                '<form method="post" action="article.php?id=', $id_chiffre, '">',
                '<input type="hidden" name="commentaire_id" value="', $commentaire_id, '">',
                '<input type="submit" name="btnSuprimerCommentaire" value="Supprimer le commentaire">',
                '</form>';
            } else {
                echo '<li>';
            }
            $BBCode_commentaire = bbcodeToHtmlUnicode($tab['coTexte']);
            echo '<p>Commentaire de <strong>', htmlProtegerSorties($tab['coAuteur']),
                        '</strong>, le ', dateIntToStringFootL($tab['coDate']),
                    '</p>',
                    '<blockquote>', $BBCode_commentaire, '</blockquote>',
                '</li>';
        }
        echo '</ul>';
    }
    // sinon on indique qu'il n'y a pas de commentaires
    else {
        echo '<p>Il n\'y a pas de commentaire pour cet article. </p>';
    }

    // Libération de la mémoire associée au résultat de la requête
    mysqli_free_result($result);

    if (! estAuthentifie()){
        echo '<p>',
                '<a href="./connexion.php">Connectez-vous</a> ou <a href="./inscription.php">inscrivez-vous</a> pour pouvoir commenter cet article !',
            '</p>',
            '</section>';
    } else {
        echo '<form method="post" action="article.php?id=', $id_chiffre, '" class="ajout-article">',
        '<fieldset>',
        '<legend>Ajoutez un commentaire</legend>',
        '<textarea name="textCom" rows="18" cols="80"></textarea>',
        '<input type="submit" name="btnAjoutCommentaire" value="Publier ce commentaire">',
        '</fieldset>',
        '</form>';
    }
}