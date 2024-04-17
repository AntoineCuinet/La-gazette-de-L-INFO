# La-gazette-de-L-INFO

L'application à développer est un site d'informations parodiques publiant des articles ayant pour thème l'actualité de la Licence Informatique, et nommé La Gazette de L-INFO.

## Description de l'application

Ce site est composé de plusieurs pages dynamiques dont le contenu est généré à partir d'une base de données. Cette base de données est, elle-même, alimentée par une partie du site, nécessitant une authentification du visiteur.

Un visiteur non authentifié a uniquement accès à la partie publique du site : il peut accéder à la page de présentation de la rédaction et aux articles :

soit depuis la page d'accueil, qui met en lumière une sélection d'articles,
soit depuis la page des actualités, qui liste tous les articles par ordre chronologique,
soit depuis la page de recherche, qui permet de rechercher des articles en fonction de leur titre ou de leur résumé.
Les visiteurs peuvent s'incrire, ce qui leur permet, une fois authentifiés, d'ajouter des commentaires aux articles ou de supprimer leurs propres commentaires.

Parmi les utilisateurs inscrits du site (c'est-à-dire enregistrés dans la table utilisateur de la base de données), une catégorie particulière est celle des rédacteurs qui ont le droit d'ajouter de nouveaux articles, d'éditer et de supprimer les articles qu'ils ont écrits.

De plus, les rédacteurs ont le droit de supprimer n'importe quel commentaire (écrit par n'importe qui).
