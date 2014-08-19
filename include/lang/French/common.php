<?php

// Language definitions for frequently used strings
$lang_common = array(

// Text orientation and encoding
'lang_direction'					=>	'ltr',	// ltr (Left-To-Right) or rtl (Right-To-Left)
'lang_identifier'					=>	'fr', //iso code 639-1 value (see http://www.loc.gov/standards/iso639-2/php/code_list.php)

// Number formatting
'lang_decimal_point'            	=>	',',
'lang_thousands_sep'            	=>	' ',

// Menu
'Nav Index'							=>	'Acceuil',
'Nav Game'							=>	'Jeu',
'Nav News'							=>	'Actualité',
'Nav Board'							=>	'Board',
'Nav Database'						=>	'Base de données',
'Nav Account'						=>	'Compte',
'Nav Language'						=>	'Langage',
'Login'								=>	'Connexion',
'Logout'							=>	'Déconnexion',
'Signin'							=>	'Création de compte',
'Manage account'					=>	'Gestion de compte',
'Not logged in'						=>	'Vous n\'êtes pas connecté.',
'Logged in as'						=>	'Connecté en tant que ',
'Remember me'						=>	'Se souvenir',
'Forgotten pass'					=>	'Mot de passe oublié',

// Board
'Board'								=>	'Board',
'Forum'								=>	'Forum',
'Topic'								=>	'Sujet',
'Post'								=>	'Message',
'Submit'							=>	'Valider',
'Preview'							=>	'Prévisualisation',
'Cat Realm'							=>	'Royaume d\'Aviana',

// User
'Account'							=>	'Compte',
'Username'							=>	'Nom d\'affichage',
'Password'							=>	'Mot de passe',
'Password2'							=>	'Confirmation',
'Email'								=>	'Adresse mail',
'ToolTip Account'					=>	'Nom de compte utilisé pour vous connecter.',
'ToolTip Username'					=>	'Affiché publiquement. Peut différer de votre nom de compte.',
'ToolTip Password'					=>	'Mot de passe utilisé pour vous connecter.',
'ToolTip Password2'					=>	'Confirmez votre mot de passe.',
'ToolTip Email'						=>	'Utilisée pour retrouver votre mot de passe ou vous envoyer des informations importantes.',
'Wrong user/pass'					=>	'Ces informations de correspondent à aucun compte.',

// Notices
'Bad request'						=>	'Erreur. Le lien que vous avez suivi est incorrect ou périmé.',
'No view'							=>	'Vous n\'êtes pas autorisé(e) à visiter ces forums.',
'No permission'						=>	'Vous n\'êtes pas autorisé(e) à afficher cette page.',
'Bad referrer'						=>	'Mauvais HTTP_REFERER. Vous avez été renvoyé(e) vers cette page par une source inconnue ou interdite. Si le problème persiste, assurez-vous que le champ «&#160;URL de base&#160;» de la page Administration&#160;» Options est correctement renseigné et que vous vous rendez sur ces forums en utilisant cette URL. Vous pourrez trouver davantage d\'informations dans la documentation de FluxBB.',
'No cookie'							=>	'Vous semblez avoir été identifié(e), cependant aucun cookie n\'a été envoyé. Veuillez vérifier vos paramètres et, si possible, activer les cookies pour ce site.',
'Pun include extension'  			=>	'Impossible de procéder à l\'inclusion utilisateur %s depuis le gabarit %s. Fichiers "%s" non autorisés',
'Pun include directory'				=>	'Impossible de procéder à l\'inclusion utilisateur %s depuis le gabarit %s. Ouverture de dossier non autorisé',
'Pun include error'					=>	'Impossible de procéder à l\'inclusion utilisateur %s à partir du gabarit %s. Ce fichier ne se trouve ni dans le dossier des gabarits, ni dans le dossier d\'inclusion d\'utilisateur.',

// Miscellaneous
'Announcement'			=>	'Annonce',
'Options'			=>	'Options',
'Submit'			=>	'Valider',
'Never'				=>	'Jamais',
'Today'				=>	'Aujourd\'hui',
'Yesterday'			=>	'Hier',
'Info'				=>	'Info',		// a common table header
'Go back'			=>	'Retour',
'Maintenance'			=>	'Maintenance',
'Redirecting'			=>	'Redirection',
'Click redirect'		=>	'Cliquez ici si vous ne voulez pas attendre (ou si votre navigateur ne vous redirige pas automatiquement).',
'on'				=>	'activé',		// as in "BBCode is on"
'off'				=>	'désactivé',
'Invalid email'			=>	'L\'adresse électronique que vous avez saisie est invalide.',
'Required'			=>	'(obligatoire)',
'required field'		=>	'est un champ obligatoire pour ce formulaire.',	// for javascript form validation
'Last post'			=>	'Dernier message',
'by'				=>	'par',	// as in last post by someuser
'New posts'			=>	'Nouveaux messages',
'New posts info'		=>	'Atteindre le premier nouveau message de cette discussion.',	// the popup text for new posts links
'Send email'			=>	'Envoyer un e-mail',
'Moderated by'			=>	'Modéré par',
'Registered'			=>	'Date d\'inscription',
'Subject'			=>	'Sujet',
'Message'			=>	'Message',
'Topic'				=>	'Discussion',
'Forum'				=>	'Forum',
'Posts'				=>	'Messages',
'Replies'			=>	'Réponses',
'Pages'				=>	'Pages&#160;:',
'Page'				=>	'Page %s',
'BBCode'			=>	'BBCode&#160;:',	// You probably shouldn't change this
'url tag'			=>	'Balise [url]&#160;:',
'img tag'			=>	'Balise [img]&#160;:',
'Smilies'			=>	'Émoticônes&#160;:',
'and'				=>	'et',
'Image link'			=>	'image',	// This is displayed (i.e. <image>) instead of images when "Show images" is disabled in the profile
'wrote'				=>	'a écrit&#160;:',	// For [quote]'s
'Mailer'			=>	'%s E-mail automatique',	// As in "MyForums Mailer" in the signature of outgoing e-mails
'Important information'		=>	'Information importante',
'Write message legend'		=>	'Veuillez composer votre message et l\'envoyer',
'Previous'                      =>      'Précédent',
'Next'                          =>      'Suivant',
'Spacer'                        =>      '…', // Ellipsis for paginate

// Title
'Title'				=>	'Titre de l\'utilisateur',
'Member'			=>	'Membre',	// Default title
'Moderator'			=>	'Modérateur',
'Administrator'			=>	'Administrateur',
'Banned'			=>	'Banni(e)',
'Guest'				=>	'Invité',
 
// Stuff for include/parser.php
'BBCode error no opening tag'		=>	'La balise [/%1$s] a été trouvée sans balise [%1$s] correspondante',
'BBCode error invalid nesting'		=>	'La balise [%1$s] a été ouverte dans la balise [%2$s], ceci n\'est pas autorisé',
'BBCode error invalid self-nesting'	=>	'La balise [%s] a été ouverte dans cette même balise, ceci n\'est pas autorisé',
'BBCode error no closing tag'		=>	'La balise [%1$s] a été trouvée sans balise [/%1$s] correspondante',
'BBCode error empty attribute'		=>	'La balise [%s] comporte un attribut non défini',
'BBCode error tag not allowed'		=>	'Vous n\'êtes pas autorisé à utiliser la balise [%s]',
'BBCode error tag url not allowed'	=>	'Vous n\'êtes pas autorisé à mettre des liens',
'BBCode code problem'			=>	'Il y a un problème avec vos balises [code]',
'BBCode list size error'		=>	'Votre liste étant trop longue pour être analysée, veuillez la réduire s\'il vous plaît&#160;!',

// Stuff for the navigator (top of every page)
'User list'			=>	'Liste des membres',
'Rules'				=>  	'Règles',
'Search'			=>  	'Recherche',
'Register'			=>  	'Inscription',
'Admin'				=>	'Administration',
'Last visit'			=>	'Dernière visite&#160;: %s',
'Topic searches'		=>	'Contributions&#160;:',
'New posts header'		=>	'Nouvelles',
'Active topics'			=>	'Récentes',
'Unanswered topics'		=>      'Sans réponse',
'Posted topics'			=>	'Personnelles',
'Show new posts'		=>	'Trouver les discussions avec de nouveaux messages depuis votre dernière visite.',
'Show active topics'		=>	'Trouver les discussions comportant des messages récents.',
'Show unanswered topics'	=>	'Trouver les discussions sans réponse.',
'Show posted topics'		=>	'Trouver les discussions auxquelles vous avez participé.',
'Mark all as read'		=>	'Marquer toutes les discussions comme lues',
'Mark forum read'               =>      'Marquer ce forum comme lu',
'Title separator'		=>	' / ',

// Stuff for the page footer
'Move topic'			=>  	'Déplacer la discussion',
'Open topic'			=>  	'Ouvrir la discussion',
'Close topic'			=>  	'Fermer la discussion',
'Unstick topic'			=>  	'Détacher la discussion',
'Stick topic'			=>  	'Épingler la discussion',
 
// Debug information
'Debug table'                   =>        'Informations de débogage',
'Querytime'                     =>        'Générées en %1$s secondes, %2$s requêtes exécutées',
'Memory usage'			=>	  'Utilisation de la mémoire : %1$s',
'Peak usage'			=>	  '(pic d\'utilisation : %1$s)',
'Query times'                   =>        'Temps (s)',
'Query'                         =>        'Requête',
'Total query time'              =>        'Temps total d\'exécution de la requête&#160;: %s',

// Admin related stuff in the header
'New reports'					  =>	    'De nouveaux signalements ont été envoyés&#160;!',
'Maintenance mode enabled'			  =>	    'Le mode maintenance est activé&#160;!',

// Units for file sizes
'Size unit B'            =>  '%s O',
'Size unit KiB'            =>  '%s Kio',
'Size unit MiB'            =>  '%s Mio',
'Size unit GiB'            =>  '%s Gio',
'Size unit TiB'            =>  '%s Tio',
'Size unit PiB'            =>  '%s Pio',
'Size unit EiB'            =>  '%s Eio',

); 