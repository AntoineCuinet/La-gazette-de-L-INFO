<?php
//_____________________________________________________________\\
//                                                             \\
//                     La Gazette de L-INFO                    \\
//             Page de déconnexion (deconnexion.php)           \\
//                                                             \\
//                    CUINET ANTOINE TP2A-CMI                  \\
//                        Langages du Web                      \\
//                        L2 Informatique                      \\
//                         UFC - UFR ST                        \\
//_____________________________________________________________\\


require_once 'bibli_gazette.php';

// démarrage ou reprise de la session
// pas besoin de démarrer la bufferisation des sorties
session_start();

sessionExit();
