-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : ven. 24 avr. 2026 à 18:53
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `tinysteps`
--

-- --------------------------------------------------------

--
-- Structure de la table `admin`
--

CREATE TABLE `admin` (
  `idadmin` int(11) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `email` varchar(70) NOT NULL,
  `motdepasse` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `admin`
--

INSERT INTO `admin` (`idadmin`, `nom`, `email`, `motdepasse`) VALUES
(1, 'admin', 'douaasalah262@gmail.com', 'admin');

-- --------------------------------------------------------

--
-- Structure de la table `ages`
--

CREATE TABLE `ages` (
  `id` int(11) NOT NULL,
  `nom` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `ages`
--

INSERT INTO `ages` (`id`, `nom`) VALUES
(1, '0-3 mois'),
(2, '4-6 mois'),
(3, '7-9 mois'),
(4, '10-12 mois'),
(5, '1-2 ans');

-- --------------------------------------------------------

--
-- Structure de la table `avis`
--

CREATE TABLE `avis` (
  `id` int(11) NOT NULL,
  `medecin_id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `note` int(11) NOT NULL CHECK (`note` between 1 and 5),
  `commentaire` text DEFAULT NULL,
  `statut` varchar(50) DEFAULT 'approuve',
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `avis`
--

INSERT INTO `avis` (`id`, `medecin_id`, `parent_id`, `note`, `commentaire`, `statut`, `date_creation`) VALUES
(14, 1, 1, 2, 'bien', 'en_attente', '2026-04-21 21:09:52');

-- --------------------------------------------------------

--
-- Structure de la table `bebe`
--

CREATE TABLE `bebe` (
  `id_bebe` int(11) NOT NULL,
  `nom_prenom` varchar(100) NOT NULL,
  `date_naissance` date NOT NULL,
  `lieu_naissance` varchar(100) NOT NULL,
  `sexe` varchar(10) NOT NULL,
  `id_parent` int(11) DEFAULT NULL,
  `pediatre_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `bebe`
--

INSERT INTO `bebe` (`id_bebe`, `nom_prenom`, `date_naissance`, `lieu_naissance`, `sexe`, `id_parent`, `pediatre_id`) VALUES
(6, 'yosra', '2026-03-04', 'medenin', 'F', 1, 61),
(7, 'mohsen', '2026-03-22', 'monastir', 'M', 2, 1),
(8, 'ghada bk', '2025-09-11', 'monastir', 'F', 3, NULL),
(9, 'ghada bk', '2025-04-18', 'jamel', 'F', 4, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `cabinet`
--

CREATE TABLE `cabinet` (
  `id` int(11) NOT NULL,
  `medecin_id` int(11) NOT NULL,
  `nom_cabinet` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `adresse` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ville` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `code_postal` char(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `pays` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Tunisie',
  `tel_cabin` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `cabinet`
--

INSERT INTO `cabinet` (`id`, `medecin_id`, `nom_cabinet`, `adresse`, `ville`, `code_postal`, `pays`, `tel_cabin`) VALUES
(1, 49, 'cabinet', 'monastir', 'Monastir', '5099', 'Tunisie', '93223079'),
(3, 51, 'cabinet kasr hlel', 'monastir', 'Sfax', '5050', 'Tunisie', '96408412'),
(11, 60, 'cabinet douaa', 'monastir', 'Sfax', '5050', 'Tunisie', '96408412'),
(12, 61, 'cabinet du monastir', 'monastir', 'Sfax', '5050', 'Tunisie', '96408412');

-- --------------------------------------------------------

--
-- Structure de la table `calendrier`
--

CREATE TABLE `calendrier` (
  `id` int(11) NOT NULL,
  `age` varchar(20) DEFAULT NULL,
  `vaccin_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `calendrier`
--

INSERT INTO `calendrier` (`id`, `age`, `vaccin_id`) VALUES
(1, 'Naissance', 1),
(2, 'Naissance', 2),
(3, '2 mois', 3),
(4, '2 mois', 4),
(5, '2 mois', 5),
(6, '3 mois', 6),
(7, '3 mois', 7),
(8, '4 mois', 8),
(9, '6 mois', 9),
(10, '6 mois', 10),
(11, '11 mois', 11),
(12, '12 mois', 12),
(13, '12 mois', 13),
(14, '18 mois', 14),
(15, '18 mois', 15),
(16, '18 mois', 16);

-- --------------------------------------------------------

--
-- Structure de la table `conseils`
--

CREATE TABLE `conseils` (
  `id` int(11) NOT NULL,
  `age_id` int(11) NOT NULL,
  `type` varchar(10) NOT NULL,
  `contenu` text NOT NULL,
  `astuce` text NOT NULL,
  `categorie` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `conseils`
--

INSERT INTO `conseils` (`id`, `age_id`, `type`, `contenu`, `astuce`, `categorie`) VALUES
(1, 1, 'aliment', 'Lait maternel ou maternisé uniquement – 8 à 12 tétées par jour', 'Timing : Nourrir tôt le matin aide à observer les réactions.', 'alimentation'),
(2, 1, 'aliment', 'Nourrir à la demande (toutes les 2-3 heures)', ' Quantité : Commencer par de petites quantités et augmenter graduellement.', 'alimentation'),
(3, 1, 'aliment', 'Repérer les signes de faim : fouissement, sucement des mains', 'Variété : Proposer différentes couleurs et textures pour un équilibre nutritionnel.', 'alimentation'),
(4, 1, 'aliment', 'Pas d\'eau, de jus ou d\'aliments solides nécessaires', '', 'alimentation'),
(5, 1, 'nutrition', 'Assurer une bonne prise du sein lors de l\'allaitement', '', 'alimentation'),
(6, 1, 'nutrition', 'Si biberon, suivre attentivement les instructions de préparation\nSi biberon, suivre attentivement les instructions de préparation', '', 'alimentation'),
(7, 1, 'nutrition', 'Faire roter bébé après chaque tétée\n', '', 'alimentation'),
(8, 1, 'nutrition', 'Maintenir un horaire flexible et suivre les signaux de bébé\n', '', 'alimentation'),
(9, 1, 'eviter', 'Miel (risque de botulisme)\n', '', 'alimentation'),
(10, 1, 'eviter', 'Lait de vache\n', '', 'alimentation'),
(11, 1, 'eviter', 'Aliments solides\n', '', 'alimentation'),
(12, 1, 'eviter', 'Eau (sauf avis médical)\n', '', 'alimentation'),
(13, 2, 'aliment', 'Continuer le lait maternel ou maternisé en priorité\n', 'Moment idéal : Introduire les nouveaux aliments le matin pour surveiller les réactions.', 'alimentation'),
(14, 2, 'aliment', 'Purées de légumes : carottes, courgettes, patate douce\n', 'Texture : Purées très lisses au début, sans grumeaux.', 'alimentation'),
(15, 2, 'aliment', 'Compotes de fruits : pomme, poire, banane bien mûre\n', 'Patience : Bébé peut refuser un aliment jusqu\'à 10 fois — persévérez !', 'alimentation'),
(16, 2, 'aliment', 'Introduire un seul aliment à la fois, attendre 3 jours avant le suivant\n', '', 'alimentation'),
(17, 2, 'nutrition', 'Proposer les nouveaux aliments le matin pour surveiller les réactions\n', '', 'alimentation'),
(18, 2, 'nutrition', 'Ne pas forcer bébé à terminer son assiette\n', '', 'alimentation'),
(19, 2, 'nutrition', 'La texture doit être très lisse, sans morceaux\n', '', 'alimentation'),
(20, 2, 'nutrition', 'Pas de sel ni de sucre ajouté\n', '', 'alimentation'),
(21, 2, 'eviter', 'Noix entières et fruits à coques\r\n', '', 'alimentation'),
(22, 2, 'eviter', 'Aliments trop épicés ou trop salés\r\n', '', 'alimentation'),
(25, 3, 'aliment', '3 repas par jour + lait maternel ou maternisé\r\n', 'Finger foods : Proposer des morceaux mous que bébé peut saisir seul.', 'alimentation'),
(26, 3, 'aliment', 'Viandes mixées : poulet, dinde, veau\r\n', ' Hydratation : Introduire un peu d\'eau à la tasse ou sippy cup.', 'alimentation'),
(27, 3, 'aliment', 'Légumineuses : lentilles, pois chiches en purée\n', 'Couleurs : Varier les légumes colorés pour un maximum de nutriments.', 'alimentation'),
(28, 3, 'aliment', 'Céréales sans gluten : riz, maïs\n', '', 'alimentation'),
(29, 3, 'aliment', 'Yaourt nature entier (sans sucre)', '', 'alimentation'),
(30, 3, 'nutrition', 'Passer à des textures légèrement plus épaisses', '', 'alimentation'),
(31, 3, 'nutrition', 'Proposer des morceaux fondants pour développer la mastication', '', 'alimentation'),
(35, 3, 'nutrition', 'Boire de l\'eau en petites quantités (50-100ml/jour)', '', 'alimentation'),
(36, 3, 'eviter', 'Morceaux durs pouvant causer l\'étouffement\r\n', '', 'alimentation'),
(37, 3, 'eviter', 'Aliments trop sucrés et jus de fruits commerciaux\r\n', '', 'alimentation'),
(38, 3, 'eviter', 'Fruits de mer et poissons fumés\r\n', '', 'alimentation'),
(39, 4, 'aliment', '3 repas + 2 collations par jour\r\n', 'Autonomie : Laisser bébé manger seul avec ses doigts encourage l\'autonomie.', 'alimentation'),
(40, 4, 'aliment', 'Tous les groupes alimentaires : viande, légumes, fruits, féculents\r\n', ' Repas partagés : Manger ensemble favorise l\'apprentissage par imitation', 'alimentation'),
(41, 4, 'aliment', 'Finger food : petits morceaux tendres que bébé peut prendre seul\r\n', ' Pas de sel : Éviter tout sel et sucre dans les préparations de bébé.', 'alimentation'),
(42, 4, 'aliment', 'Fromages pasteurisés doux : gruyère râpé, fromage blanc\r\n', '', 'alimentation'),
(43, 4, 'nutrition', 'Encourager l\'autonomie alimentaire (finger food)\r\n', '', 'alimentation'),
(44, 4, 'nutrition', 'Manger ensemble crée une relation positive avec la nourriture\r\n', '', 'alimentation'),
(45, 4, 'nutrition', '200-300ml d\'eau par jour minimum\r\n', '', 'alimentation'),
(46, 4, 'eviter', 'Miel (encore avant 1 an)', '', 'alimentation'),
(47, 4, 'eviter', 'Lait de vache en boisson principale (pas avant 1 an)\r\n', '', 'alimentation'),
(48, 4, 'eviter', 'Noix entières et raisins entiers (risque d\'étouffement)\r\n', '', 'alimentation'),
(49, 4, 'eviter', 'Boissons sucrées et sodas\r\n', '', 'alimentation'),
(50, 5, 'aliment', '3 repas complets + 1-2 collations', 'Repas structurés : Des horaires réguliers aident à réguler l\'appétit.', 'alimentation'),
(51, 5, 'aliment', 'Alimentation variée identique à la famille (adaptée)\r\n', 'Néophobie : Normal de refuser de nouveaux aliments - continuer à les proposer.', 'alimentation'),
(53, 5, 'aliment', 'Une variété de fruits, légumes, céréales complètes et protéines', 'Cuisine ensemble : Impliquer l\'enfant dans la préparation des repas.', 'alimentation'),
(54, 5, 'aliment', 'Lait entier jusqu\'à 2 ans (500 ml/jour max)\r\n', '', 'alimentation'),
(55, 5, 'aliment', 'De petites portions – laissez l\'enfant en redemander', '', 'alimentation'),
(56, 5, 'nutrition', 'Encourager la consommation d\'eau tout au long de la journée', '', 'alimentation'),
(57, 5, 'nutrition', 'Maintenir des horaires de repas réguliers\r\n', '', 'alimentation'),
(58, 5, 'nutrition', 'Limiter les écrans pendant les repas\r\n', '', 'alimentation'),
(59, 5, 'eviter', 'Bonbons et chocolat en excès\r\n', '', 'alimentation'),
(60, 5, 'eviter', 'Aliments ultra-transformés et fast-food\r\n', '', 'alimentation'),
(61, 5, 'eviter', 'Noix et graines entières (risque d\'étouffement)', '', 'alimentation'),
(62, 5, 'eviter', 'Boissons sucrées et jus industriels\r\n', '', 'alimentation'),
(63, 1, 'faire', 'Parlez, chantez et lisez à voix haute à votre bébé', ' Le temps face à face, yeux dans les yeux, est l\'outil de développement le plus puissant.', 'developpement'),
(64, 1, 'faire', 'Posez bébé sur le ventre quelques minutes par jour en surveillance. Cela renforce la nuque et prépare le retournement.\r\n', 'Vision : Bébé voit à 20–30 cm — se placer à cette distance.', 'developpement'),
(65, 1, 'eviter', 'Laisser le bébé longtemps attaché (siège, poussette)', ' Langage : Répondre aux vocalisations encourage la communication', 'developpement'),
(66, 2, 'faire', 'Donner objets colorés à manipuler', ' Jouets texturés : Proposer des objets de textures différentes à explorer.', 'developpement'),
(67, 2, 'faire', 'Parler souvent, imiter ses sons', 'Parole : Parler lentement et distinctement - le cerveau enregistre chaque mot', 'developpement'),
(68, 2, 'faire', 'Jeux simples (cache-cache visage)', '', 'developpement'),
(69, 2, 'eviter', 'Placer le bébé devant un écran', '', 'developpement'),
(70, 2, 'eviter', 'Ignorer ses tentatives de communication', '', 'developpement'),
(71, 3, 'faire', 'Lire des livres cartonnés avec images simples', ' Construire : Empiler et renverser des cubes développe la coordination.', 'developpement'),
(72, 3, 'faire', 'Encourager exploration sécurisée\r\n', '4 pattes : Ne pas sauter l\'étape du ramper - elle structure les deux hémisphères cérébraux (OMS)', 'developpement'),
(73, 3, 'faire', 'Cacher un jouet sous un tissu — entraîne la permanence de l\'objet', 'Explorer : Laisser explorer la maison sécurisée - chaque pièce est une découverte', 'developpement'),
(74, 3, 'eviter', 'Sauter l\'étape du 4 pattes — elle structure le cerveau', '', 'developpement'),
(75, 3, 'eviter', 'Écrans de toute nature — OMS : zéro écran avant 2 ans', '', 'developpement'),
(76, 3, 'eviter', 'Empêcher exploration (trop protéger)', '', 'developpement'),
(77, 4, 'faire', 'Les premières vocalises significatives apparaissent vers 9-12 mois. Parlez-lui constamment, nommez les objets.\r\n', 'Lisez des livres illustrés et nommez ce que vous voyez ensemble.', 'developpement'),
(78, 4, 'faire', 'Applaudir et célébrer chaque progrès — renforce la confiance (OMS Nurturing Care)', 'Marche : Laisser marcher pieds nus à la maison - développe l\'équilibre naturellement', 'developpement'),
(79, 4, 'faire', '180 min/jour d\'activité physique variée (OMS 2019)', 'Trotteur : Formellement déconseillé par l\'OMS - retarde la marche et risque de chutes', 'developpement'),
(80, 4, 'eviter', 'Comparer avec d\'autres bébés du même âge', '', 'developpement'),
(81, 4, 'eviter', 'Parler à la place de l\'enfant systématiquement', '', 'developpement'),
(82, 4, 'eviter', 'Forcer la marche (développement naturel)', '', 'developpement'),
(83, 5, 'faire', 'Entre 18 mois et 2 ans, le vocabulaire explose. Lisez quotidiennement, nommez tout, chantez.\r\n', ' Si l\'enfant n\'a pas 50 mots à 2 ans ou ne fait pas d\'association de 2 mots, consultez le pédiatre.', 'developpement'),
(84, 5, 'faire', 'L\'enfant imite tout : c\'est son principal mode d\'apprentissage. Soyez attentif à vos comportements.\r\n', 'Patience : Laisser l\'enfant finir seul une tâche même lentement - développe la persévérance', 'developpement'),
(85, 5, 'faire', 'Laisser l\'enfant manger seul même si c\'est sale — développe l\'autonomie', 'Langage : Poser des questions simples - \"où est le chat ?\" stimule la réflexion', 'developpement'),
(86, 5, 'faire', 'Peinture aux doigts, modelage, découpage — motricité fine (OMS 2019)', 'Activité : 180 min d\'activité physique variée par jour ', 'developpement'),
(87, 5, 'eviter', 'Pousser à la perfection ( erreurs = apprentissage)', '', 'developpement'),
(88, 5, 'eviter', 'Isolement social prolongé', '', 'developpement'),
(89, 5, 'eviter', 'Activités trop structurées — le jeu libre est prioritaire', '', 'developpement'),
(90, 1, 'faire', 'Consulter en urgence si fièvre > 38°C avant 3 mois (OMS)', 'Premier bain : Attendre la chute du cordon (J5–J15) avant le premier bain', 'sante'),
(91, 1, 'faire', 'Nettoyer le cordon avec compresse stérile sèche — laisser sécher à l\'air (OMS 2017)', 'Peau : Produits sans parfum, pH neutre - la peau du nouveau-né est 3x plus fine', 'sante'),
(92, 1, 'faire', 'Vaccins à J0 : Hépatite B — M2 : DTCaP-Hib-HB + Pneumocoque (calendrier OMS)', 'Vaccins : Respecter le calendrier vaccinal protège bébé et l\'entourage.', 'sante'),
(93, 1, 'eviter', 'Alcool ou antiseptique sur le cordon — OMS déconseille formellement', '', 'sante'),
(94, 1, 'eviter', 'Bain avant la chute du cordon', '', 'sante'),
(95, 1, 'eviter', 'Donner du paracétamol sans avis médical avant 3 mois', '', 'sante'),
(96, 2, 'faire', 'Vaccins M4 : DTCaP-Hib-HB + Pneumocoque 2ème dose (OMS)', ' Vue : Tester le suivi oculaire lors des consultations.', 'sante'),
(97, 2, 'faire', 'Bain quotidien à 37°C — 5 à 10 minutes maximum', 'Ongles : Couper après le bain - plus souples, risque d\'incarnation réduit', 'sante'),
(98, 2, 'faire', 'Utilisez des produits sans parfum, sans conservateur, adaptés aux peaux néonatales.\r\n', ' Dentition : Anneau de dentition froid soulage les gencives.', 'sante'),
(99, 2, 'eviter', 'Automédication sans avis pédiatrique', '', 'sante'),
(100, 2, 'eviter', 'Bain trop chaud — peau très sensible', '', 'sante'),
(101, 2, 'eviter', 'Retarder les vaccins sans raison médicale', '', 'sante'),
(102, 3, 'faire', 'Vaccin M9 : Méningocoque C (selon calendrier national)', 'Évitez les cotons-tiges dans les oreilles.', 'sante'),
(103, 3, 'faire', 'Anneau de dentition réfrigéré pour soulager les gencives', 'Plis : Nettoyer soigneusement plis du cou, aisselles,... - zones à risque d\'irritation', 'sante'),
(104, 3, 'eviter', 'Donner des médicaments contre la diarrhée sans avis médical', 'Cuillère : Ne pas partager cuillère  — transmission de bactéries cariogènes (OMS)', 'sante'),
(105, 3, 'eviter', 'Laisser bébé seul dans la baignoire même 1 seconde', '', 'sante'),
(106, 4, 'faire', 'Consultation M12 : bilan complet vision, audition, développement (OMS)', ' Chaussures : Premières chaussures souples qui laissent le pied libre.', 'sante'),
(107, 4, 'faire', 'Vaccin M12 : ROR (Rougeole-Oreillons-Rubéole) — OMS priorité mondiale', 'Bilan M12 : Examen complet : vue, ouïe, développement moteur et langagier.', 'sante'),
(108, 4, 'faire', 'Surveiller courbe poids/taille sur carnet — alerte si cassure (OMS Growth Charts)', 'Vitamine D : Supplémenter en vitamine D toute l\'année jusqu\'à 18 mois.', 'sante'),
(109, 4, 'eviter', 'Sauter les consultations de suivi — dépistage précoce essentiel', '', 'sante'),
(110, 5, 'faire', 'Premiers brossages des dents\r\nDès l\'apparition des premières dents, brossez 2 fois par jour avec un doigtier puis une brosse adaptée.', 'Sucette : Arrêter avant 18 mois — déforme la mâchoire et les dents (SFP)', 'sante'),
(111, 5, 'faire', 'Apprenez le lavage des mains : avant les repas, après les toilettes, après avoir joué dehors.', 'Lavage des mains : Les savons moussants colorés rendent le geste plus attractif.', 'sante'),
(112, 5, 'faire', 'Vaccins M15 : ROR 2ème dose + Méningocoque C rappel (OMS)', 'Activité : 1 à 2h d\'activité physique libre par jour est recommandé.', 'sante'),
(113, 5, 'faire', 'Verrouiller médicaments, produits ménagers, hors de portée (OMS Child Safety)', '', 'sante'),
(114, 5, 'eviter', 'Médicaments sans ordonnance pour rhume — inutiles avant 6 ans (OMS)', '', 'sante'),
(115, 5, 'eviter', 'Laisser seul près d\'une piscine, seau, baignoire', '', 'sante'),
(116, 1, 'faire', '14 à 17h de sommeil par 24h (siestes incluses)', ' Routine : Même courte, une routine du soir rassure bébé.', 'sommeil'),
(117, 1, 'faire', 'Couchage sur le dos, sur matelas ferme, dans sa propre surface de sommeil', 'Lumière : Lumière vive le jour, obscurité la nuit pour régler le rythme.', 'sommeil'),
(118, 1, 'faire', 'Température idéale 18-20°C.', 'Dormez quand bébé dort pour récupérer, surtout les premières semaines.', 'sommeil'),
(119, 1, 'faire', 'Distinguer jour et nuit (Dès 2-3 semaines, différenciez les moments : lumière et stimulations en journée vs obscurité et calme la nuit).', '', 'sommeil'),
(120, 1, 'eviter', 'Coucher sur le ventre ou le côté', '', 'sommeil'),
(121, 1, 'eviter', 'Faire de nuits complètes avant 3 mois, c\'est physiologiquement impossible.', '', 'sommeil'),
(122, 1, 'eviter', 'Le co-sleeping sur canapé ou fauteuil', '', 'sommeil'),
(123, 2, 'faire', '12 à 16h de sommeil/24h', 'Régularité : Même heure de coucher chaque soir.', 'sommeil'),
(124, 2, 'faire', '3 siestes/jour', 'Sieste : Ne pas laisser dormir plus de 2h en sieste — perturbe la nuit', 'sommeil'),
(125, 2, 'faire', 'Commencer un rituel du soir', 'Le co-sleeping sur canapé ou fauteuil est dangereux.', 'sommeil'),
(126, 2, 'eviter', 'Sieste trop tard (après 17h)', '', 'sommeil'),
(127, 2, 'eviter', 'Bercer jusqu\'à endormissement complet', '', 'sommeil'),
(128, 3, 'faire', 'Établir routine régulière (coucher/réveil)\r\n', 'Si les réveils sont très fréquents et épuisants, parlez-en à votre pédiatre.', 'sommeil'),
(129, 3, 'faire', '12 à 16h de sommeil/24h', 'Sieste2 siestes : 45 min le matin + 1h30 l\'après-midi', 'sommeil'),
(130, 3, 'faire', '2 siestes/jour', '', 'sommeil'),
(131, 3, 'eviter', 'Changer la routine pendant la régression', '', 'sommeil'),
(132, 3, 'eviter', 'Sieste du matin trop longue (+1h30)', '', 'sommeil'),
(133, 4, 'faire', '11 à 14h de sommeil/24h', 'Dents : Poussées dentaires = réveils normaux, anneau froid avant le coucher', 'sommeil'),
(134, 4, 'faire', 'Laisser se rendormir seul', 'Sécurité : Sécuriser la chambre — bébé peut se lever et explorer', 'sommeil'),
(135, 4, 'faire', 'Transition vers 1 seule sieste progressivement', 'Coucher fixe: Entre 19h et 20h30 — la régularité est plus importante que l\'heure exacte', 'sommeil'),
(136, 4, 'faire', 'Instaurez une routine courte et constante', '', 'sommeil'),
(137, 4, 'eviter', 'Écrans avant le coucher ', '', 'sommeil'),
(138, 4, 'eviter', 'Coucher trop tard — fatigue = résistance\r\n', '', 'sommeil'),
(139, 5, 'faire', '11 à 14h de sommeil/24h', 'Histoire : Une courte histoire calme le système nerveux avant le sommeil', 'sommeil'),
(140, 5, 'faire', '1 sieste après-midi 1 à 2h', 'Heure fixe : Coucher avant 20h30 pour éviter la surstimulation.', 'sommeil'),
(141, 5, 'faire', 'Heure de coucher fixe chaque soir avec une routine régulière (histoire, calme, lumière tamisée).\r\n', 'Si l\'enfant refuse la sieste, proposez un \'temps calme\' de 30 min dans sa chambre.', 'sommeil'),
(142, 5, 'eviter', 'Rester jusqu\'à endormissement complet', '', 'sommeil'),
(143, 5, 'eviter', 'Sieste après 15h (perturbe la nuit)', '', 'sommeil');

-- --------------------------------------------------------

--
-- Structure de la table `conseils_lus`
--

CREATE TABLE `conseils_lus` (
  `id` int(11) NOT NULL,
  `id_parent` int(11) NOT NULL,
  `conseil_id` int(11) NOT NULL,
  `lu_le` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `conseils_sauvegardes`
--

CREATE TABLE `conseils_sauvegardes` (
  `id` int(11) NOT NULL,
  `id_parent` int(11) NOT NULL,
  `conseil_id` int(11) NOT NULL,
  `sauvegarde_le` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `consultation`
--

CREATE TABLE `consultation` (
  `id_consultation` int(11) NOT NULL,
  `date_consultation` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `motif` text NOT NULL,
  `observation` text NOT NULL,
  `diagnostic` text NOT NULL,
  `poids` decimal(5,2) NOT NULL,
  `taille` decimal(5,2) NOT NULL,
  `temperature` decimal(4,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `compte_rendu` text DEFAULT NULL,
  `id_pediatre` int(11) DEFAULT NULL,
  `id_bebe` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `consultation`
--

INSERT INTO `consultation` (`id_consultation`, `date_consultation`, `motif`, `observation`, `diagnostic`, `poids`, `taille`, `temperature`, `notes`, `compte_rendu`, `id_pediatre`, `id_bebe`) VALUES
(1, '2026-04-23 15:40:48', 'dn', 'exame,', 'jci', 6.99, 68.50, 36.90, 'csjk', '', 42, 6);

-- --------------------------------------------------------

--
-- Structure de la table `disponibilites`
--

CREATE TABLE `disponibilites` (
  `id` int(11) NOT NULL,
  `medecin_id` int(11) NOT NULL,
  `jour_semaine` int(11) NOT NULL COMMENT '1=Lundi, 2=Mardi, 3=Mercredi, 4=Jeudi, 5=Vendredi, 6=Samedi, 7=Dimanche',
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL,
  `actif` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `disponibilites`
--

INSERT INTO `disponibilites` (`id`, `medecin_id`, `jour_semaine`, `heure_debut`, `heure_fin`, `actif`) VALUES
(1, 1, 1, '09:00:00', '12:00:00', 1),
(2, 1, 1, '14:00:00', '17:00:00', 1),
(3, 1, 2, '09:00:00', '12:00:00', 1),
(4, 1, 2, '14:00:00', '17:00:00', 1),
(5, 1, 3, '09:00:00', '12:00:00', 1),
(6, 1, 3, '14:00:00', '17:00:00', 1),
(7, 1, 4, '09:00:00', '12:00:00', 1),
(8, 1, 4, '14:00:00', '17:00:00', 1),
(9, 1, 5, '09:00:00', '12:00:00', 1),
(10, 1, 5, '14:00:00', '17:00:00', 1),
(11, 1, 6, '09:00:00', '12:00:00', 0),
(12, 1, 7, '00:00:00', '00:00:00', 0),
(13, 2, 1, '08:30:00', '12:00:00', 1),
(14, 2, 1, '13:00:00', '16:00:00', 1),
(15, 2, 2, '08:30:00', '12:00:00', 1),
(16, 2, 2, '13:00:00', '16:00:00', 1),
(17, 2, 4, '08:30:00', '12:00:00', 1),
(18, 2, 4, '13:00:00', '16:00:00', 1),
(19, 2, 5, '08:30:00', '12:00:00', 1),
(20, 2, 5, '13:00:00', '16:00:00', 1),
(21, 2, 3, '00:00:00', '00:00:00', 0),
(22, 2, 6, '00:00:00', '00:00:00', 0),
(23, 2, 7, '00:00:00', '00:00:00', 0),
(24, 3, 1, '09:00:00', '13:00:00', 1),
(25, 3, 1, '14:00:00', '18:00:00', 1),
(26, 3, 3, '09:00:00', '13:00:00', 1),
(27, 3, 3, '14:00:00', '18:00:00', 1),
(28, 3, 4, '09:00:00', '13:00:00', 1),
(29, 3, 4, '14:00:00', '18:00:00', 1),
(30, 3, 5, '09:00:00', '13:00:00', 1),
(31, 3, 5, '14:00:00', '18:00:00', 1),
(32, 3, 2, '00:00:00', '00:00:00', 0),
(33, 3, 6, '00:00:00', '00:00:00', 0),
(34, 3, 7, '00:00:00', '00:00:00', 0),
(35, 4, 1, '08:30:00', '12:30:00', 1),
(36, 4, 1, '14:30:00', '17:30:00', 1),
(37, 4, 2, '08:30:00', '12:30:00', 1),
(38, 4, 2, '14:30:00', '17:30:00', 1),
(39, 4, 3, '08:30:00', '12:30:00', 1),
(40, 4, 3, '14:30:00', '17:30:00', 1),
(41, 4, 4, '08:30:00', '12:30:00', 1),
(42, 4, 4, '14:30:00', '17:30:00', 1),
(43, 4, 5, '08:30:00', '12:30:00', 1),
(44, 4, 5, '14:30:00', '17:30:00', 1),
(45, 4, 6, '08:30:00', '12:00:00', 0),
(46, 4, 7, '00:00:00', '00:00:00', 0),
(47, 5, 2, '10:00:00', '13:00:00', 1),
(48, 5, 2, '14:00:00', '18:00:00', 1),
(49, 5, 3, '10:00:00', '13:00:00', 1),
(50, 5, 3, '14:00:00', '18:00:00', 1),
(51, 5, 4, '10:00:00', '13:00:00', 1),
(52, 5, 4, '14:00:00', '18:00:00', 1),
(53, 5, 5, '10:00:00', '13:00:00', 1),
(54, 5, 5, '14:00:00', '18:00:00', 1),
(55, 5, 1, '00:00:00', '00:00:00', 0),
(56, 5, 6, '00:00:00', '00:00:00', 0),
(57, 5, 7, '00:00:00', '00:00:00', 0),
(58, 6, 1, '09:00:00', '12:00:00', 1),
(59, 6, 1, '14:00:00', '17:00:00', 1),
(60, 6, 2, '09:00:00', '12:00:00', 1),
(61, 6, 2, '14:00:00', '17:00:00', 1),
(62, 6, 4, '09:00:00', '12:00:00', 1),
(63, 6, 4, '14:00:00', '17:00:00', 1),
(64, 6, 5, '09:00:00', '12:00:00', 1),
(65, 6, 5, '14:00:00', '17:00:00', 1),
(66, 6, 3, '00:00:00', '00:00:00', 0),
(67, 6, 6, '00:00:00', '00:00:00', 0),
(68, 6, 7, '00:00:00', '00:00:00', 0),
(69, 7, 1, '08:00:00', '12:00:00', 1),
(70, 7, 1, '13:00:00', '17:00:00', 1),
(71, 7, 2, '08:00:00', '12:00:00', 1),
(72, 7, 2, '13:00:00', '17:00:00', 1),
(73, 7, 3, '08:00:00', '12:00:00', 1),
(74, 7, 3, '13:00:00', '17:00:00', 1),
(75, 7, 4, '08:00:00', '12:00:00', 1),
(76, 7, 4, '13:00:00', '17:00:00', 1),
(77, 7, 5, '08:00:00', '12:00:00', 1),
(78, 7, 5, '13:00:00', '17:00:00', 1),
(79, 7, 6, '08:00:00', '12:00:00', 0),
(80, 7, 7, '00:00:00', '00:00:00', 0),
(81, 8, 1, '09:00:00', '12:00:00', 1),
(82, 8, 1, '14:00:00', '18:00:00', 1),
(83, 8, 2, '09:00:00', '12:00:00', 1),
(84, 8, 2, '14:00:00', '18:00:00', 1),
(85, 8, 3, '09:00:00', '12:00:00', 1),
(86, 8, 3, '14:00:00', '18:00:00', 1),
(87, 8, 4, '09:00:00', '12:00:00', 1),
(88, 8, 4, '14:00:00', '18:00:00', 1),
(89, 8, 5, '09:00:00', '12:00:00', 1),
(90, 8, 5, '14:00:00', '18:00:00', 1),
(91, 8, 6, '09:00:00', '12:00:00', 1),
(92, 8, 7, '00:00:00', '00:00:00', 0),
(93, 9, 1, '09:30:00', '12:30:00', 1),
(94, 9, 1, '14:30:00', '17:30:00', 1),
(95, 9, 2, '09:30:00', '12:30:00', 1),
(96, 9, 2, '14:30:00', '17:30:00', 1),
(97, 9, 4, '09:30:00', '12:30:00', 1),
(98, 9, 4, '14:30:00', '17:30:00', 1),
(99, 9, 5, '09:30:00', '12:30:00', 1),
(100, 9, 5, '14:30:00', '17:30:00', 1),
(101, 9, 3, '00:00:00', '00:00:00', 0),
(102, 9, 6, '00:00:00', '00:00:00', 0),
(103, 9, 7, '00:00:00', '00:00:00', 0),
(104, 10, 1, '08:30:00', '12:30:00', 1),
(105, 10, 1, '13:30:00', '16:30:00', 1),
(106, 10, 2, '08:30:00', '12:30:00', 1),
(107, 10, 2, '13:30:00', '16:30:00', 1),
(108, 10, 3, '08:30:00', '12:30:00', 1),
(109, 10, 3, '13:30:00', '16:30:00', 1),
(110, 10, 4, '08:30:00', '12:30:00', 1),
(111, 10, 4, '13:30:00', '16:30:00', 1),
(112, 10, 5, '08:30:00', '12:30:00', 1),
(113, 10, 5, '13:30:00', '16:30:00', 1),
(114, 10, 6, '00:00:00', '00:00:00', 0),
(115, 10, 7, '00:00:00', '00:00:00', 0),
(116, 23, 1, '09:00:00', '12:00:00', 1),
(117, 23, 1, '14:00:00', '17:00:00', 1),
(118, 23, 2, '09:00:00', '12:00:00', 1),
(119, 23, 2, '14:00:00', '17:00:00', 1),
(120, 23, 3, '09:00:00', '12:00:00', 1),
(121, 23, 4, '09:00:00', '12:00:00', 1),
(122, 23, 4, '14:00:00', '17:00:00', 1),
(123, 23, 5, '09:00:00', '12:00:00', 1),
(124, 23, 5, '14:00:00', '17:00:00', 1),
(125, 24, 1, '10:00:00', '13:00:00', 1),
(126, 24, 1, '14:00:00', '18:00:00', 1),
(127, 24, 2, '10:00:00', '13:00:00', 1),
(128, 24, 2, '14:00:00', '18:00:00', 1),
(129, 24, 4, '10:00:00', '13:00:00', 1),
(130, 24, 4, '14:00:00', '18:00:00', 1),
(131, 24, 5, '10:00:00', '13:00:00', 1),
(132, 24, 5, '14:00:00', '18:00:00', 1),
(133, 25, 1, '08:30:00', '12:30:00', 1),
(134, 25, 1, '14:30:00', '17:30:00', 1),
(135, 25, 2, '08:30:00', '12:30:00', 1),
(136, 25, 2, '14:30:00', '17:30:00', 1),
(137, 25, 3, '08:30:00', '12:30:00', 1),
(138, 25, 4, '08:30:00', '12:30:00', 1),
(139, 25, 4, '14:30:00', '17:30:00', 1),
(140, 25, 5, '08:30:00', '12:30:00', 1),
(141, 25, 5, '14:30:00', '17:30:00', 1),
(142, 26, 1, '08:00:00', '12:00:00', 1),
(143, 26, 1, '13:00:00', '16:00:00', 1),
(144, 26, 2, '08:00:00', '12:00:00', 1),
(145, 26, 2, '13:00:00', '16:00:00', 1),
(146, 26, 3, '08:00:00', '12:00:00', 1),
(147, 26, 4, '08:00:00', '12:00:00', 1),
(148, 26, 4, '13:00:00', '16:00:00', 1),
(149, 26, 5, '08:00:00', '12:00:00', 1),
(150, 26, 5, '13:00:00', '16:00:00', 1),
(159, 11, 1, '09:00:00', '12:00:00', 1),
(160, 11, 1, '14:00:00', '17:00:00', 1),
(161, 11, 2, '09:00:00', '12:00:00', 1),
(162, 11, 2, '14:00:00', '17:00:00', 1),
(163, 11, 3, '09:00:00', '12:00:00', 1),
(164, 11, 4, '09:00:00', '12:00:00', 1),
(165, 11, 4, '14:00:00', '17:00:00', 1),
(166, 11, 5, '09:00:00', '12:00:00', 1),
(167, 11, 5, '14:00:00', '17:00:00', 1),
(168, 12, 1, '10:00:00', '13:00:00', 1),
(169, 12, 1, '14:00:00', '18:00:00', 1),
(170, 12, 2, '10:00:00', '13:00:00', 1),
(171, 12, 2, '14:00:00', '18:00:00', 1),
(172, 12, 4, '10:00:00', '13:00:00', 1),
(173, 12, 4, '14:00:00', '18:00:00', 1),
(174, 12, 5, '10:00:00', '13:00:00', 1),
(175, 12, 5, '14:00:00', '18:00:00', 1),
(176, 13, 1, '08:30:00', '12:30:00', 1),
(177, 13, 1, '14:30:00', '17:30:00', 1),
(178, 13, 2, '08:30:00', '12:30:00', 1),
(179, 13, 2, '14:30:00', '17:30:00', 1),
(180, 13, 3, '08:30:00', '12:30:00', 1),
(181, 13, 4, '08:30:00', '12:30:00', 1),
(182, 13, 4, '14:30:00', '17:30:00', 1),
(183, 13, 5, '08:30:00', '12:30:00', 1),
(184, 13, 5, '14:30:00', '17:30:00', 1),
(185, 14, 1, '08:00:00', '12:00:00', 1),
(186, 14, 1, '13:00:00', '16:00:00', 1),
(187, 14, 2, '08:00:00', '12:00:00', 1),
(188, 14, 2, '13:00:00', '16:00:00', 1),
(189, 14, 3, '08:00:00', '12:00:00', 1),
(190, 14, 4, '08:00:00', '12:00:00', 1),
(191, 14, 4, '13:00:00', '16:00:00', 1),
(192, 14, 5, '08:00:00', '12:00:00', 1),
(193, 14, 5, '13:00:00', '16:00:00', 1),
(194, 15, 1, '09:30:00', '12:30:00', 1),
(195, 15, 1, '14:00:00', '17:00:00', 1),
(196, 15, 2, '09:30:00', '12:30:00', 1),
(197, 15, 2, '14:00:00', '17:00:00', 1),
(198, 15, 4, '09:30:00', '12:30:00', 1),
(199, 15, 4, '14:00:00', '17:00:00', 1),
(200, 15, 5, '09:30:00', '12:30:00', 1),
(201, 15, 5, '14:00:00', '17:00:00', 1),
(202, 16, 1, '08:00:00', '12:00:00', 1),
(203, 16, 1, '14:00:00', '18:00:00', 1),
(204, 16, 2, '08:00:00', '12:00:00', 1),
(205, 16, 2, '14:00:00', '18:00:00', 1),
(206, 16, 3, '08:00:00', '12:00:00', 1),
(207, 16, 4, '08:00:00', '12:00:00', 1),
(208, 16, 4, '14:00:00', '18:00:00', 1),
(209, 16, 5, '08:00:00', '12:00:00', 1),
(210, 16, 5, '14:00:00', '18:00:00', 1),
(211, 17, 1, '09:00:00', '12:00:00', 1),
(212, 17, 1, '14:00:00', '17:00:00', 1),
(213, 17, 2, '09:00:00', '12:00:00', 1),
(214, 17, 2, '14:00:00', '17:00:00', 1),
(215, 17, 3, '09:00:00', '12:00:00', 1),
(216, 17, 4, '09:00:00', '12:00:00', 1),
(217, 17, 4, '14:00:00', '17:00:00', 1),
(218, 17, 5, '09:00:00', '12:00:00', 1),
(219, 17, 5, '14:00:00', '17:00:00', 1),
(220, 18, 1, '08:00:00', '12:00:00', 1),
(221, 18, 1, '13:00:00', '16:00:00', 1),
(222, 18, 2, '08:00:00', '12:00:00', 1),
(223, 18, 2, '13:00:00', '16:00:00', 1),
(224, 18, 3, '08:00:00', '12:00:00', 1),
(225, 18, 4, '08:00:00', '12:00:00', 1),
(226, 18, 4, '13:00:00', '16:00:00', 1),
(227, 18, 5, '08:00:00', '12:00:00', 1),
(228, 18, 5, '13:00:00', '16:00:00', 1),
(229, 19, 1, '09:30:00', '12:30:00', 1),
(230, 19, 1, '14:00:00', '17:00:00', 1),
(231, 19, 2, '09:30:00', '12:30:00', 1),
(232, 19, 2, '14:00:00', '17:00:00', 1),
(233, 19, 4, '09:30:00', '12:30:00', 1),
(234, 19, 4, '14:00:00', '17:00:00', 1),
(235, 19, 5, '09:30:00', '12:30:00', 1),
(236, 19, 5, '14:00:00', '17:00:00', 1),
(237, 20, 1, '08:30:00', '12:30:00', 1),
(238, 20, 1, '14:00:00', '17:00:00', 1),
(239, 20, 2, '08:30:00', '12:30:00', 1),
(240, 20, 2, '14:00:00', '17:00:00', 1),
(241, 20, 3, '08:30:00', '12:30:00', 1),
(242, 20, 4, '08:30:00', '12:30:00', 1),
(243, 20, 4, '14:00:00', '17:00:00', 1),
(244, 20, 5, '08:30:00', '12:30:00', 1),
(245, 20, 5, '14:00:00', '17:00:00', 1),
(246, 21, 1, '09:00:00', '12:00:00', 1),
(247, 21, 1, '14:00:00', '18:00:00', 1),
(248, 21, 2, '09:00:00', '12:00:00', 1),
(249, 21, 2, '14:00:00', '18:00:00', 1),
(250, 21, 3, '09:00:00', '12:00:00', 1),
(251, 21, 4, '09:00:00', '12:00:00', 1),
(252, 21, 4, '14:00:00', '18:00:00', 1),
(253, 21, 5, '09:00:00', '12:00:00', 1),
(254, 21, 5, '14:00:00', '18:00:00', 1),
(255, 22, 1, '08:00:00', '12:00:00', 1),
(256, 22, 1, '13:00:00', '17:00:00', 1),
(257, 22, 2, '08:00:00', '12:00:00', 1),
(258, 22, 2, '13:00:00', '17:00:00', 1),
(259, 22, 4, '08:00:00', '12:00:00', 1),
(260, 22, 4, '13:00:00', '17:00:00', 1),
(261, 22, 5, '08:00:00', '12:00:00', 1),
(262, 22, 5, '13:00:00', '17:00:00', 1),
(263, 23, 1, '10:00:00', '13:00:00', 1),
(264, 23, 1, '14:00:00', '17:00:00', 1),
(265, 23, 2, '10:00:00', '13:00:00', 1),
(266, 23, 2, '14:00:00', '17:00:00', 1),
(267, 23, 3, '10:00:00', '13:00:00', 1),
(268, 23, 4, '10:00:00', '13:00:00', 1),
(269, 23, 4, '14:00:00', '17:00:00', 1),
(270, 23, 5, '10:00:00', '13:00:00', 1),
(271, 23, 5, '14:00:00', '17:00:00', 1),
(272, 24, 1, '08:30:00', '12:30:00', 1),
(273, 24, 1, '14:30:00', '17:30:00', 1),
(274, 24, 2, '08:30:00', '12:30:00', 1),
(275, 24, 2, '14:30:00', '17:30:00', 1),
(276, 24, 3, '08:30:00', '12:30:00', 1),
(277, 24, 4, '08:30:00', '12:30:00', 1),
(278, 24, 4, '14:30:00', '17:30:00', 1),
(279, 24, 5, '08:30:00', '12:30:00', 1),
(280, 24, 5, '14:30:00', '17:30:00', 1),
(281, 25, 1, '09:00:00', '12:00:00', 1),
(282, 25, 1, '14:00:00', '17:00:00', 1),
(283, 25, 2, '09:00:00', '12:00:00', 1),
(284, 25, 2, '14:00:00', '17:00:00', 1),
(285, 25, 4, '09:00:00', '12:00:00', 1),
(286, 25, 4, '14:00:00', '17:00:00', 1),
(287, 25, 5, '09:00:00', '12:00:00', 1),
(288, 25, 5, '14:00:00', '17:00:00', 1),
(289, 30, 1, '08:00:00', '12:00:00', 1),
(290, 30, 1, '13:00:00', '16:00:00', 1),
(291, 30, 2, '08:00:00', '12:00:00', 1),
(292, 30, 2, '13:00:00', '16:00:00', 1),
(293, 30, 3, '08:00:00', '12:00:00', 1),
(294, 30, 4, '08:00:00', '12:00:00', 1),
(295, 30, 4, '13:00:00', '16:00:00', 1),
(296, 30, 5, '08:00:00', '12:00:00', 1),
(297, 30, 5, '13:00:00', '16:00:00', 1),
(298, 31, 1, '09:30:00', '12:30:00', 1),
(299, 31, 1, '14:30:00', '17:30:00', 1),
(300, 31, 2, '09:30:00', '12:30:00', 1),
(301, 31, 2, '14:30:00', '17:30:00', 1),
(302, 31, 3, '09:30:00', '12:30:00', 1),
(303, 31, 4, '09:30:00', '12:30:00', 1),
(304, 31, 4, '14:30:00', '17:30:00', 1),
(305, 31, 5, '09:30:00', '12:30:00', 1),
(306, 31, 5, '14:30:00', '17:30:00', 1),
(307, 31, 6, '09:00:00', '12:00:00', 0),
(308, 32, 1, '08:30:00', '12:30:00', 1),
(309, 32, 1, '14:00:00', '17:00:00', 1),
(310, 32, 2, '08:30:00', '12:30:00', 1),
(311, 32, 2, '14:00:00', '17:00:00', 1),
(312, 32, 4, '08:30:00', '12:30:00', 1),
(313, 32, 4, '14:00:00', '17:00:00', 1),
(314, 32, 5, '08:30:00', '12:30:00', 1),
(315, 32, 5, '14:00:00', '17:00:00', 1),
(316, 32, 3, '09:00:00', '12:00:00', 0),
(317, 33, 1, '09:00:00', '12:00:00', 1),
(318, 33, 1, '14:00:00', '17:00:00', 1),
(319, 33, 2, '09:00:00', '12:00:00', 1),
(320, 33, 2, '14:00:00', '17:00:00', 1),
(321, 33, 3, '09:00:00', '12:00:00', 1),
(322, 33, 4, '09:00:00', '12:00:00', 1),
(323, 33, 4, '14:00:00', '17:00:00', 1),
(324, 33, 5, '09:00:00', '12:00:00', 1),
(325, 33, 5, '14:00:00', '17:00:00', 1),
(326, 33, 6, '09:00:00', '12:00:00', 0),
(327, 34, 1, '08:00:00', '12:00:00', 1),
(328, 34, 1, '14:00:00', '18:00:00', 1),
(329, 34, 2, '08:00:00', '12:00:00', 1),
(330, 34, 2, '14:00:00', '18:00:00', 1),
(331, 34, 3, '08:00:00', '12:00:00', 1),
(332, 34, 4, '08:00:00', '12:00:00', 1),
(333, 34, 4, '14:00:00', '18:00:00', 1),
(334, 34, 5, '08:00:00', '12:00:00', 1),
(335, 34, 5, '14:00:00', '18:00:00', 1),
(336, 49, 1, '08:00:00', '17:00:00', 1),
(337, 60, 1, '08:00:00', '12:00:00', 1),
(338, 60, 2, '08:00:00', '12:00:00', 1),
(339, 60, 3, '08:00:00', '12:00:00', 1),
(340, 60, 4, '08:00:00', '12:00:00', 1),
(341, 61, 1, '08:00:00', '12:00:00', 1),
(342, 61, 2, '08:00:00', '12:00:00', 1),
(343, 61, 3, '08:00:00', '12:00:00', 1),
(344, 61, 4, '08:00:00', '12:00:00', 1),
(345, 61, 5, '08:00:00', '12:00:00', 1),
(346, 61, 6, '08:00:00', '12:00:00', 1),
(347, 61, 7, '08:00:00', '12:00:00', 1);

-- --------------------------------------------------------

--
-- Structure de la table `medecin`
--

CREATE TABLE `medecin` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) DEFAULT NULL,
  `prenom` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `tel` varchar(20) DEFAULT NULL,
  `date_nai` date DEFAULT NULL,
  `date_inscription` date DEFAULT NULL,
  `sexe` enum('Homme','Femme') DEFAULT NULL,
  `mdp` varchar(255) DEFAULT NULL,
  `num_inscri_med` varchar(100) DEFAULT NULL,
  `ann_expr` int(11) DEFAULT NULL,
  `diplome` text DEFAULT NULL,
  `specialite` varchar(100) DEFAULT 'Pédiatre généraliste',
  `cabinet` varchar(255) DEFAULT 'Cabinet El Menzah, Tunis',
  `statut` varchar(20) NOT NULL DEFAULT 'en_attente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `medecin`
--

INSERT INTO `medecin` (`id`, `nom`, `prenom`, `email`, `tel`, `date_nai`, `date_inscription`, `sexe`, `mdp`, `num_inscri_med`, `ann_expr`, `diplome`, `specialite`, `cabinet`, `statut`) VALUES
(1, 'Bensalem', 'Donia', 'donia.bensalem@tinysteps.com', '+216 71 234 567', '1985-03-15', '2026-04-18', 'Femme', '12356M*', 'MED-001', 2015, 'Diplôme de médecine - Université Tunis', 'Pédiatre généraliste', 'Tunis Centre', 'en_attente'),
(2, 'Ayari', 'Kamel', 'kamel.ayari@tinysteps.com', '+216 71 345 678', '1982-07-22', '2026-04-18', 'Homme', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MED-002', 2012, 'Diplôme de gastroentérologie pédiatrique', 'Pédiatre - Gastroentérologie', 'La Marsa', 'en_attente'),
(3, 'Mellouli', 'Salma', 'salma.mellouli@tinysteps.com', '+216 71 456 789', '1988-11-05', '2026-04-18', 'Femme', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MED-003', 2016, 'Diplôme de neurologie pédiatrique', 'Pédiatre - Neurologie', 'Ariana', 'en_attente'),
(4, 'Gharbi', 'Mohamed', 'mohamed.gharbi@tinysteps.com', '+216 71 567 890', '1979-09-30', '2026-04-18', 'Homme', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MED-004', 2010, 'Diplôme de médecine générale', 'Pédiatre généraliste', 'Tunis Mutuelleville', 'en_attente'),
(5, 'Ben Amor', 'Leila', 'leila.benamor@tinysteps.com', '+216 73 123 456', '1986-04-18', '2026-04-18', 'Femme', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MED-005', 2014, 'Diplôme d\'allergologie pédiatrique', 'Pédiatre - Allergologue', 'Sousse', 'en_attente'),
(6, 'Saidi', 'Nabil', 'nabil.saidi@tinysteps.com', '+216 71 678 901', '1981-12-12', '2026-04-18', 'Homme', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MED-006', 2011, 'Diplôme de cardiologie pédiatrique', 'Pédiatre - Cardiologue', 'Tunis El Menzah', 'en_attente'),
(7, 'Khedher', 'Amira', 'amira.khedher@tinysteps.com', '+216 71 789 012', '1990-06-25', '2026-04-18', 'Femme', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MED-007', 2018, 'Diplôme de médecine générale', 'Pédiatre généraliste', 'La Marsa Gammarth', 'en_attente'),
(8, 'Aisseoui', 'Moncef', 'moncef.aisseoui@tinysteps.com', '+216 71 890 123', '1975-08-08', '2026-04-18', 'Homme', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MED-008', 2005, 'Diplôme de néonatologie', 'Pédiatre - Néonatologue', 'Ariana Ennasr', 'en_attente'),
(9, 'Ben Hassine', 'Rim', 'rim.benhassine@tinysteps.com', '+216 73 234 567', '1987-02-14', '2026-04-18', 'Femme', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MED-009', 2015, 'Diplôme de médecine générale', 'Pédiatre généraliste', 'Sousse Khezama', 'en_attente'),
(10, 'Trabelsi', 'Mehdi', 'mehdi.trabelsi@tinysteps.com', '+216 71 901 234', '1984-10-20', '2026-04-18', 'Homme', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MED-010', 2013, 'Diplôme de pneumologie pédiatrique', 'Pédiatre - Pneumologue', 'Tunis El Khadra', 'accepte'),
(11, 'Ben Othman', 'Aymen', 'aymen.benothman@tinysteps.com', '+216 72 123 456', '1985-03-15', '2015-06-10', 'Homme', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MED-NAB-001', 15, 'Doctorat en médecine - Université de Tunis El Manar, Spécialisation en Pédiatrie', 'Pédiatre généraliste', 'Nabeul Centre', 'en_attente'),
(12, 'Hamzoui', 'Bessem', 'bessem.hamzoui@tinysteps.com', '+216 72 234 567', '1978-11-22', '2008-09-15', 'Homme', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MED-NAB-002', 22, 'Doctorat en médecine - Université de Sousse, Diplôme d\'Allergologie Pédiatrique', 'Pédiatre - Allergologue', 'Nabeul Hammamet', 'en_attente'),
(13, 'Azzaibi', 'Mehdi', 'mehdi.Azzaibi@tinysteps.com', '+216 72 345 678', '1982-07-08', '2012-03-20', 'Homme', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MED-NAB-003', 18, 'Doctorat en médecine - Université de Tunis, Diplôme de Pneumologie Pédiatrique', 'Pédiatre - Pneumologue', 'Nabeul Dar Chaabane', 'en_attente'),
(14, 'Zayani', 'Ahmed', 'zayani.ahmedi@tinysteps.com', '+216 72 246 678', '1980-09-30', '2010-01-05', 'Homme', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MED-NAB-004', 20, 'Doctorat en médecine - Université de Monastir, Spécialisation en Pédiatrie Générale', 'Pédiatre - généraliste', 'Nabeul Beni khiar', 'en_attente'),
(15, 'Turki', 'Dorra', 'turki.dorra@tinysteps.com', '+216 72 345 678', '1986-04-18', '2016-11-12', 'Femme', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MED-NAB-005', 14, 'Doctorat en médecine - Université de Sfax, Diplôme de Pneumologie Pédiatrique', 'Pédiatre - Pneumologue', 'Nabeul Dar Chaabane', 'en_attente'),
(16, 'Hammami', 'Mohamed Ferouk', 'medferouk@tinysteps.com', '+216 72 345 678', '1975-12-25', '2005-08-18', 'Homme', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MED-NAB-006', 25, 'Doctorat en médecine - Université de Tunis, Diplôme de Pneumologie Pédiatrique', 'Pédiatre - Pneumologue', 'Nabeul Hammamet', 'en_attente'),
(17, 'Slama', 'Marwa', 'marwa.slama@tinysteps.com', '+216 72 345 678', '1988-06-10', '2018-02-25', 'Femme', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MED-NAB-007', 12, 'Doctorat en médecine - Université de Monastir, Diplôme de Cardiologie Pédiatrique', 'Pédiatre - cardiologue', 'Nabeul Centre', 'en_attente'),
(18, 'Jelassi', 'Hatem', 'hatem.jelassi@tinysteps.com', '+216 72 456 789', '1976-05-14', '2006-07-19', 'Homme', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MED-BIZ-001', 24, 'Doctorat en médecine - Université de Tunis, Spécialisation en Pédiatrie Générale', 'Pédiatre généraliste', 'Bizerte Centre', 'en_attente'),
(19, 'Boubakri', 'Rim', 'rim.boubakri@tinysteps.com', '+216 72 567 890', '1983-09-27', '2013-04-02', 'Femme', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MED-BIZ-002', 17, 'Doctorat en médecine - Université de Sousse, Diplôme de Néonatologie', 'Pédiatre - Néonatologue', 'Bizerte Menzel Bourguiba', 'en_attente'),
(20, 'Toumi', 'Walid', 'walid.toumi@tinysteps.com', '+216 72 678 901', '1979-01-03', '2009-09-10', 'Homme', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MED-BIZ-003', 21, 'Doctorat en médecine - Université de Tunis, Diplôme de Cardiologie Pédiatrique', 'Pédiatre - Cardiologue', 'Bizerte Zarzouna', 'en_attente'),
(21, 'Bouzgarro', 'Mehdi', 'mehdi.bouzgarro@tinysteps.com', '+216 73 123 456', '1981-07-19', '2011-12-15', 'Homme', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MED-MON-001', 19, 'Doctorat en médecine - Université de Monastir, Spécialisation en Pédiatrie Générale', 'Pédiatre généraliste', 'Monastir Centre', 'en_attente'),
(22, 'Benzarti', 'Mohamed Ali', 'mohamedali.benzarti@tinysteps.com', '+216 73 234 567', '1977-10-08', '2007-05-22', 'Homme', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MED-MON-002', 23, 'Doctorat en médecine - Université de Tunis, Diplôme de Neurologie Pédiatrique', 'Pédiatre - Neurologie', 'Monastir Skanes', 'en_attente'),
(23, 'Sakly', 'Sonia', 'sonia.sakly@tinysteps.com', '+216 73 345 678', '1984-12-12', '2014-08-28', 'Femme', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MED-MON-003', 16, 'Doctorat en médecine - Université de Sfax, Diplôme de Gastroentérologie Pédiatrique', 'Pédiatre - Gastroentérologie', 'Monastir Moknine', 'en_attente'),
(24, 'Karray', 'Mehdi', 'mehdi.karray@tinysteps.com', '+216 73 456 789', '1979-06-17', '2009-11-08', 'Homme', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MED-MAH-001', 21, 'Doctorat en médecine - Université de Sousse, Spécialisation en Pédiatrie Générale', 'Pédiatre généraliste', 'Mahdia Centre', 'en_attente'),
(25, 'Saidi', 'Ines', 'ines.saidi@tinysteps.com', '+216 73 567 890', '1985-02-28', '2015-07-21', 'Femme', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MED-MAH-002', 15, 'Doctorat en médecine - Université de Monastir, Diplôme d\'Allergologie Pédiatrique', 'Pédiatre - Allergologue', 'Mahdia Hiboun', 'en_attente'),
(26, 'Ferjani', 'Amine', 'amine.ferjani@tinysteps.com', '+216 73 678 901', '1983-11-11', '2013-04-25', 'Homme', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MED-MAH-003', 17, 'Doctorat en médecine - Université de Tunis, Diplôme de Néonatologie', 'Pédiatre - Urgentiste', 'Mahdia Rejiche', 'en_attente'),
(27, 'Chatti', 'Emna', 'emna.chatti@tinysteps.com', '+216 73 789 012', '1980-04-23', '2010-09-16', 'Femme', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MED-MAH-004', 20, 'Doctorat en médecine - Université de Sfax, Spécialisation en Pédiatrie Générale', 'Pédiatre - Néonatologue', 'Mahdia Corniche', 'en_attente'),
(28, 'Zarrouk', 'Yakine', 'yakine.zarrouk@tinysteps.com', '+216 73 890 123', '1982-03-21', '2012-06-30', 'Homme', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MED-MON-004', 18, 'Doctorat en médecine - Université de Monastir, Diplôme de Cardiologie Pédiatrique', 'Pédiatre généraliste', 'Mahdia Hiboun', 'en_attente'),
(29, 'Moussa', 'Marwen', 'marwen.moussa@tinysteps.com', '+216 73 901 234', '1987-08-05', '2017-03-14', 'Homme', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MED-MON-005', 13, 'Doctorat en médecine - Université de Tunis, Diplôme d\'Allergologie Pédiatrique', 'Pédiatre - Cardiologue', 'Monastir Centre', 'en_attente'),
(30, 'Ben Nasr', 'Sarra', 'sarra.bennasr@tinysteps.com', '+216 73 012 345', '1987-08-05', '2017-03-14', 'Femme', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MED-MON-006', 13, 'Doctorat en médecine - Université de Monastir, Diplôme d\'Allergologie Pédiatrique', 'Pédiatre - Allergologue', 'Monastir Skanes', 'en_attente'),
(31, 'Zariat', 'Emir', 'emir.zariat@tinysteps.com', '+216 73 963 012', '1984-02-18', '2014-09-22', 'Homme', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MED-NAB-008', 16, 'Doctorat en médecine - Université de Tunis, Diplôme de Néonatologie', 'Pédiatre - Néonatologue', 'Nabeul Grombalia', 'en_attente'),
(32, 'Karoui', 'Aissa', 'aissa.karoui@tinysteps.com', '+216 73 890 901', '1981-11-12', '2011-06-30', 'Homme', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MED-NAB-009', 19, 'Doctorat en médecine - Université de Sousse, Spécialisation en Pédiatrie Générale', 'Pédiatre généraliste', 'Nabeul Dar Chabenne', 'en_attente'),
(33, 'Soukeh', 'Malek', 'malek.soukeh@tinysteps.com', '+216 73 973 234', '1983-05-25', '2013-12-18', 'Femme', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MED-NAB-010', 17, 'Doctorat en médecine - Université de Monastir, Diplôme de Cardiologie Pédiatrique', 'Pédiatre - Cardiologue', 'Nabeul Beni Khiar', 'en_attente'),
(34, 'Aisseoui', 'Saif', 'saif.aisseoui@tinysteps.com', '+216 73 019 003', '1986-09-07', '2016-04-11', 'Homme', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MED-NAB-011', 14, 'Doctorat en médecine - Université de Tunis, Diplôme d\'Allergologie Pédiatrique', 'Pédiatre - Allergologue', 'Nabeul Mamoura', 'en_attente'),
(35, 'Ben Mahmoud', 'Eskander', 'eskander.benmahmoud@tinysteps.com', '+216 73 102 406', '1982-12-20', '2012-08-05', 'Homme', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MED-NAB-012', 18, 'Doctorat en médecine - Université de Sfax, Spécialisation en Pédiatrie Générale', 'Pédiatre - généraliste', 'Nabeul Centre', 'en_attente'),
(36, 'bk', 'g', 'ghadabenkahla3@gmai.com', '20123456', '1996-04-17', NULL, 'Femme', '123456789Mm*', '14562', 5, NULL, 'Pédiatre généraliste', 'Cabinet El Menzah, Tunis', 'en_attente'),
(37, 'ben ahmed', 'ameel', 'ameelba@gmail.com', '20123478', '1996-04-17', NULL, 'Femme', '123456789Mm*', NULL, 5, NULL, 'Pédiatre généraliste', 'Cabinet El Menzah, Tunis', 'en_attente'),
(38, 'ben ahmed', 'ameel', 'ameel@gmail.com', '20123478', '1996-04-17', NULL, 'Femme', '123456789Mp', '14569', 5, NULL, 'Pédiatre généraliste', 'Cabinet El Menzah, Tunis', 'en_attente'),
(41, 'ben ahmed', 'ameel', 'baa@gmail.com', '20123478', '1996-04-17', NULL, 'Femme', '123456789Mp', '14569', 5, NULL, 'Pédiatre généraliste', 'Cabinet El Menzah, Tunis', 'en_attente'),
(42, 'amin', 'ben salah', 'amine12@gmail.com', '93062728', '1998-04-20', NULL, 'Homme', '$2y$10$H939rDWWSv89fqhM1kqDOuxpNIpY6qKzFiqBZmmMJl9t03BB7HErK', '123456', 1, '', 'Pédiatre généraliste', 'Cabinet El Menzah, Tunis', 'accepte'),
(49, 'marwa', 'bm', 'douaasalah262@gmail.com', '93062728', '1998-03-23', NULL, 'Femme', '$2y$10$FMMOilMHYEu20PSqnZOh2.KlR6kFQ.RPRxUITCuDMIOrUUBgT2Rhy', '123456', 0, 'Doctorat en médecine - Université de Monastir, Diplôme de Cardiologie Pédiatrique', 'Pédiatre généraliste', 'Cabinet El Menzah, Tunis', 'accepte'),
(52, 'bk', 'Aicha', 'benkahlaaicha@gmail.com', '25123456', '1970-09-11', NULL, 'Femme', '$2y$10$XV.dATaH3R1vpAbTr1cxDOjGxrl/QzFLenwl8d.0.MxszqhPDWHmS', '123456', 3, '', 'Pédiatre généraliste', 'Cabinet El Menzah, Tunis', 'accepte'),
(60, 'bk', 'douaa', 'benkahladouaa@gmail.com', '25123456', '1970-09-11', NULL, 'Femme', 'Douaa123*', '123456', 1, '', 'Pédiatre généraliste', 'Cabinet El Menzah, Tunis', 'accepte'),
(61, 'bk', 'mariem', 'mariembk@gmail.com', '50123458', '1970-09-11', NULL, 'Femme', '$2y$10$FY92WIUn.W0KQbbB7h4LyuBq8tA3hTpIEuU7xaoj35f9tDHp50FiG', '123456', 1, '', 'Pédiatre généraliste', 'Cabinet El Menzah, Tunis', 'accepte');

-- --------------------------------------------------------

--
-- Structure de la table `mesure`
--

CREATE TABLE `mesure` (
  `id` int(11) NOT NULL,
  `id_bebe` int(11) NOT NULL,
  `id_medecin` int(11) NOT NULL,
  `date_mesure` date NOT NULL,
  `poids` decimal(5,2) DEFAULT NULL COMMENT 'kg',
  `taille` decimal(5,2) DEFAULT NULL COMMENT 'cm',
  `pc` decimal(5,2) DEFAULT NULL COMMENT 'périmètre crânien cm',
  `imc` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `mesure`
--

INSERT INTO `mesure` (`id`, `id_bebe`, `id_medecin`, `date_mesure`, `poids`, `taille`, `pc`, `imc`) VALUES
(1, 6, 42, '2026-04-22', 6.50, 65.00, 42.00, 15.38),
(2, 6, 42, '2026-04-23', 6.99, 68.50, 42.00, 14.90),
(3, 6, 42, '2026-04-30', 8.00, 70.00, 50.00, 16.33);

-- --------------------------------------------------------

--
-- Structure de la table `notification`
--

CREATE TABLE `notification` (
  `id` int(11) NOT NULL,
  `destinataire_id` int(11) NOT NULL,
  `role` enum('parent','medecin') NOT NULL DEFAULT 'parent',
  `type` enum('rdv_confirme','rdv_annule','rdv_en_attente') NOT NULL,
  `message` text NOT NULL,
  `rdv_id` int(11) DEFAULT NULL,
  `lu` tinyint(1) NOT NULL DEFAULT 0,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `notification`
--

INSERT INTO `notification` (`id`, `destinataire_id`, `role`, `type`, `message`, `rdv_id`, `lu`, `date_creation`) VALUES
(1, 1, 'parent', 'rdv_confirme', 'Votre rendez-vous pour Moncef Martin le 2026-04-17 à 16:57 a été confirmé.', 6, 1, '2026-04-16 14:57:15'),
(2, 1, 'parent', 'rdv_confirme', 'Votre rendez-vous pour Moncef Martin le 2026-04-17 à 17:15 a été confirmé.', 7, 1, '2026-04-16 15:15:03'),
(3, 1, 'parent', 'rdv_confirme', 'Votre rendez-vous pour Moncef Martin le 2026-04-17 à 18:27 a été confirmé.', 8, 1, '2026-04-16 16:28:15'),
(4, 60, 'medecin', 'rdv_en_attente', 'Nouvelle demande de RDV de sena ben ahmed le 2026-04-27 à 11:00.', 25, 0, '2026-04-24 16:09:42'),
(5, 61, 'medecin', 'rdv_en_attente', 'Nouvelle demande de RDV de jazia tiss le 2026-04-30 à 10:00.', 26, 0, '2026-04-24 16:42:49'),
(6, 1, 'parent', 'rdv_confirme', 'Votre rendez-vous pour yosra le 2026-04-30 à 10:00 a été confirmé.', 26, 0, '2026-04-24 16:43:12');

-- --------------------------------------------------------

--
-- Structure de la table `ordonnance`
--

CREATE TABLE `ordonnance` (
  `id` int(11) NOT NULL,
  `id_consultation` int(11) NOT NULL,
  `id_bebe` int(11) NOT NULL,
  `id_medecin` int(11) NOT NULL,
  `date_ord` date NOT NULL,
  `contenu` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `parent`
--

CREATE TABLE `parent` (
  `id_parent` int(11) NOT NULL,
  `nom_prenom` varchar(100) NOT NULL,
  `telephone` varchar(8) NOT NULL,
  `email` varchar(100) NOT NULL,
  `cin` varchar(8) NOT NULL,
  `ncnam` varchar(20) DEFAULT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `date_inscription` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `parent`
--

INSERT INTO `parent` (`id_parent`, `nom_prenom`, `telephone`, `email`, `cin`, `ncnam`, `mot_de_passe`, `date_inscription`) VALUES
(1, 'jazia tiss', '93062728', 'douaasalah262@gmail.com', '12345600', '12345600', '$2y$10$kGyATdkf8n/gj86LYhogEuZtjbLNwmeonHpkddemE3S3L819KeOD2', '2026-04-21 15:45:45'),
(2, 'mohamed', '93062728', 'mohamed@gmail.com', '12345602', '12345601', '$2y$10$kOco2I8gA9rGgQOn6P4m8OGGsni4Dn5jl9xZ51rJI5e.osm8yKODO', '2026-04-24 08:34:50'),
(3, 'sena ben ahmed', '20123456', 'ghadabenkahla3@gmail.com', '12345678', NULL, '$2y$10$r4wlubw18/HdfpAcR2ntRuNnMgrhZ462w8OBDCIZ0SjBia6f1WZ7i', '2026-04-24 15:44:01'),
(4, 'sena ben ahmed', '50145236', 'senabenahmed@gmail.com', '14523698', NULL, '$2y$10$fjgnEtnkEdAsBxzBnbFnT.pvllCYPpBLapbG3vE008WryMhxrMNp.', '2026-04-24 17:05:38');

-- --------------------------------------------------------

--
-- Structure de la table `prescription`
--

CREATE TABLE `prescription` (
  `id` int(11) NOT NULL,
  `id_consultation` int(11) DEFAULT NULL,
  `contenu` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Structure de la table `rendez_vous`
--

CREATE TABLE `rendez_vous` (
  `id` int(11) NOT NULL,
  `pediatre_id` int(11) NOT NULL,
  `bebe_id` int(11) NOT NULL,
  `date_rdv` date NOT NULL,
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL,
  `type` enum('consult','suivi','urgence') DEFAULT 'consult',
  `motif` text DEFAULT NULL,
  `statut` enum('confirmé','attente','annulé') DEFAULT 'attente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `rendez_vous`
--

INSERT INTO `rendez_vous` (`id`, `pediatre_id`, `bebe_id`, `date_rdv`, `heure_debut`, `heure_fin`, `type`, `motif`, `statut`, `created_at`) VALUES
(1, 1, 1, '2026-04-18', '08:30:00', '09:00:00', 'consult', 'Vaccination DTP', 'confirmé', '2026-04-18 17:51:26'),
(2, 1, 1, '2026-04-18', '09:00:00', '09:30:00', 'suivi', 'Courbe de croissance', 'confirmé', '2026-04-18 17:51:26'),
(3, 1, 1, '2026-04-18', '09:30:00', '10:00:00', 'urgence', 'Fièvre 39.5°C', 'confirmé', '2026-04-18 17:51:26'),
(4, 1, 2, '2026-04-18', '10:30:00', '11:00:00', 'consult', 'Bilan annuel', 'attente', '2026-04-18 17:51:26'),
(5, 1, 1, '2026-04-19', '14:00:00', '14:30:00', 'consult', 'Consultation de suivi', 'confirmé', '2026-04-18 17:51:26'),
(6, 1, 1, '2026-04-07', '08:30:00', '09:00:00', 'consult', 'Vaccination DTP', 'confirmé', '2026-04-18 17:57:30'),
(7, 1, 2, '2026-04-07', '09:00:00', '09:30:00', 'suivi', 'Courbe de croissance', 'confirmé', '2026-04-18 17:57:30'),
(8, 1, 1, '2026-04-07', '10:30:00', '11:00:00', 'urgence', 'Fièvre 39.5°C', 'confirmé', '2026-04-18 17:57:30'),
(9, 1, 2, '2026-04-08', '09:00:00', '09:30:00', 'consult', 'Bilan annuel', 'confirmé', '2026-04-18 17:57:30'),
(10, 1, 1, '2026-04-08', '11:00:00', '11:30:00', 'suivi', 'Suivi développement', 'attente', '2026-04-18 17:57:30'),
(11, 1, 1, '2026-04-09', '08:30:00', '09:00:00', 'consult', 'Vaccin ROR', 'confirmé', '2026-04-18 17:57:30'),
(12, 1, 2, '2026-04-09', '14:00:00', '14:30:00', 'urgence', 'Otite', 'confirmé', '2026-04-18 17:57:30'),
(13, 1, 1, '2026-04-10', '10:00:00', '10:30:00', 'suivi', 'Contrôle poids', 'attente', '2026-04-18 17:57:30'),
(14, 1, 2, '2026-04-14', '09:30:00', '10:00:00', 'consult', 'Consultation routine', 'confirmé', '2026-04-18 17:57:30'),
(15, 1, 1, '2026-04-15', '11:00:00', '11:30:00', 'urgence', 'Toux persistante', 'attente', '2026-04-18 17:57:30'),
(16, 1, 2, '2026-04-16', '08:30:00', '09:00:00', 'suivi', 'Suivi post-hospitalisation', 'confirmé', '2026-04-18 17:57:30'),
(17, 1, 1, '2026-04-17', '09:00:00', '09:30:00', 'consult', 'Vaccination', 'confirmé', '2026-04-18 17:57:30'),
(18, 1, 2, '2026-04-17', '10:30:00', '11:00:00', 'consult', 'Bilan', 'attente', '2026-04-18 17:57:31'),
(19, 1, 1, '2026-04-18', '11:00:00', '11:30:00', 'suivi', 'Suivi croissance', 'confirmé', '2026-04-18 17:57:31'),
(20, 1, 2, '2026-04-20', '09:00:00', '09:30:00', 'consult', 'Vaccin DTCP', 'confirmé', '2026-04-18 17:57:31'),
(21, 1, 1, '2026-04-21', '15:00:00', '15:30:00', 'urgence', 'Éruption cutanée', 'attente', '2026-04-18 17:57:31'),
(22, 1, 2, '2026-04-25', '11:00:00', '11:30:00', 'consult', 'Consultation annuelle', 'confirmé', '2026-04-18 17:57:31'),
(23, 49, 2, '2026-04-24', '09:37:43', '00:00:00', 'consult', NULL, 'attente', '2026-04-24 07:38:47'),
(24, 49, 2, '2026-04-24', '09:37:43', '09:57:00', 'consult', NULL, 'attente', '2026-04-24 07:39:16'),
(25, 60, 9, '2026-04-27', '11:00:00', '11:30:00', 'consult', '', 'attente', '2026-04-24 16:09:41'),
(26, 61, 6, '2026-04-30', '10:00:00', '10:30:00', 'consult', '', 'confirmé', '2026-04-24 16:42:49');

-- --------------------------------------------------------

--
-- Structure de la table `vaccin`
--

CREATE TABLE `vaccin` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) DEFAULT NULL,
  `maladies` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `vaccin`
--

INSERT INTO `vaccin` (`id`, `nom`, `maladies`) VALUES
(1, 'BCG', 'Tuberculose'),
(2, 'HBV-0', 'Hépatite B'),
(3, 'Pentavalent-1', 'Diphtérie, Tétanos, Coqueluche, Hépatite B, Hib'),
(4, 'VPI-1', 'Poliomyélite'),
(5, 'PCV1', 'Pneumocoque'),
(6, 'Pentavalent-2', 'Diphtérie, Tétanos, Coqueluche, Hépatite B, Hib'),
(7, 'VPI-2', 'Poliomyélite'),
(8, 'PCV2', 'Pneumocoque'),
(9, 'Pentavalent-3', 'Diphtérie, Tétanos, Coqueluche, Hépatite B, Hib'),
(10, 'VPI-3', 'Poliomyélite'),
(11, 'PCV3', 'Pneumocoque'),
(12, 'RR-1', 'Rougeole, Rubéole'),
(13, 'HVA', 'Hépatite A'),
(14, 'DTC4', 'Diphtérie, Tétanos, Coqueluche'),
(15, 'VPO', 'Poliomyélite orale'),
(16, 'RR-2', 'Rougeole, Rubéole');

-- --------------------------------------------------------

--
-- Structure de la table `vaccination`
--

CREATE TABLE `vaccination` (
  `id` int(11) NOT NULL,
  `date_prevue` date DEFAULT NULL,
  `date_effectuee` date DEFAULT NULL,
  `statut` varchar(20) DEFAULT NULL,
  `note_medicale` text DEFAULT NULL,
  `bebe_id` int(11) DEFAULT NULL,
  `vaccin_id` int(11) DEFAULT NULL,
  `pediatre_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `vaccination`
--

INSERT INTO `vaccination` (`id`, `date_prevue`, `date_effectuee`, `statut`, `note_medicale`, `bebe_id`, `vaccin_id`, `pediatre_id`) VALUES
(1, NULL, '2026-04-23', 'effectue', '', 6, 6, 1);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`idadmin`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `ages`
--
ALTER TABLE `ages`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `avis`
--
ALTER TABLE `avis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `medecin_id` (`medecin_id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Index pour la table `bebe`
--
ALTER TABLE `bebe`
  ADD PRIMARY KEY (`id_bebe`),
  ADD KEY `id_parent` (`id_parent`),
  ADD KEY `fk_pediatre` (`pediatre_id`);

--
-- Index pour la table `cabinet`
--
ALTER TABLE `cabinet`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `medecin_id` (`medecin_id`),
  ADD UNIQUE KEY `nom_cabinet` (`nom_cabinet`);

--
-- Index pour la table `calendrier`
--
ALTER TABLE `calendrier`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vaccin_id` (`vaccin_id`);

--
-- Index pour la table `conseils`
--
ALTER TABLE `conseils`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `consultation`
--
ALTER TABLE `consultation`
  ADD PRIMARY KEY (`id_consultation`),
  ADD KEY `fk_medecin` (`id_pediatre`),
  ADD KEY `fk_bebe` (`id_bebe`);

--
-- Index pour la table `disponibilites`
--
ALTER TABLE `disponibilites`
  ADD PRIMARY KEY (`id`),
  ADD KEY `medecin_id` (`medecin_id`);

--
-- Index pour la table `medecin`
--
ALTER TABLE `medecin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `mesure`
--
ALTER TABLE `mesure`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bebe` (`id_bebe`),
  ADD KEY `idx_medecin` (`id_medecin`);

--
-- Index pour la table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dest` (`destinataire_id`,`role`),
  ADD KEY `idx_lu` (`lu`),
  ADD KEY `fk_notification_rdv` (`rdv_id`);

--
-- Index pour la table `ordonnance`
--
ALTER TABLE `ordonnance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_consultation` (`id_consultation`),
  ADD KEY `idx_bebe` (`id_bebe`);

--
-- Index pour la table `parent`
--
ALTER TABLE `parent`
  ADD PRIMARY KEY (`id_parent`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `prescription`
--
ALTER TABLE `prescription`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_consultation` (`id_consultation`);

--
-- Index pour la table `rendez_vous`
--
ALTER TABLE `rendez_vous`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `vaccin`
--
ALTER TABLE `vaccin`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `vaccination`
--
ALTER TABLE `vaccination`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk2_bebe` (`bebe_id`),
  ADD KEY `fk_vaccin` (`vaccin_id`),
  ADD KEY `fk2_pediatre` (`pediatre_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `admin`
--
ALTER TABLE `admin`
  MODIFY `idadmin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `ages`
--
ALTER TABLE `ages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `avis`
--
ALTER TABLE `avis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT pour la table `bebe`
--
ALTER TABLE `bebe`
  MODIFY `id_bebe` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `cabinet`
--
ALTER TABLE `cabinet`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `calendrier`
--
ALTER TABLE `calendrier`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT pour la table `consultation`
--
ALTER TABLE `consultation`
  MODIFY `id_consultation` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `disponibilites`
--
ALTER TABLE `disponibilites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=348;

--
-- AUTO_INCREMENT pour la table `medecin`
--
ALTER TABLE `medecin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT pour la table `mesure`
--
ALTER TABLE `mesure`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `notification`
--
ALTER TABLE `notification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `ordonnance`
--
ALTER TABLE `ordonnance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `parent`
--
ALTER TABLE `parent`
  MODIFY `id_parent` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `prescription`
--
ALTER TABLE `prescription`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `rendez_vous`
--
ALTER TABLE `rendez_vous`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT pour la table `vaccin`
--
ALTER TABLE `vaccin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT pour la table `vaccination`
--
ALTER TABLE `vaccination`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `avis`
--
ALTER TABLE `avis`
  ADD CONSTRAINT `avis_ibfk_1` FOREIGN KEY (`medecin_id`) REFERENCES `medecin` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `avis_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `parent` (`id_parent`) ON DELETE CASCADE;

--
-- Contraintes pour la table `disponibilites`
--
ALTER TABLE `disponibilites`
  ADD CONSTRAINT `disponibilites_ibfk_1` FOREIGN KEY (`medecin_id`) REFERENCES `medecin` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
