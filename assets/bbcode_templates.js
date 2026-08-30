/** Modèles de contenu repris de l’ancien site Glitchworlds. */
export const MODELES_BBCODE = {
    glitch: {
        label: 'Glitch',
        contenu: `[i]Veuillez insérer une description du glitch.[/i]

[u]Jeux Compatibles[/u] :

[u]Liste des étapes[/u] : [liste][elementliste]Première étape[/elementliste][elementliste]Deuxième étape[/elementliste][elementliste]Troisième étape[/elementliste][/liste]`,
    },
    presentation_pokemon: {
        label: 'Présentation Pokémon',
        contenu: `[center]Veuillez insérer une description du jeu.[/center]
[center][titre=h4][icone=histoir.png][/icone]Histoire :[/titre][/center]
[center][titre=h4][icone=Important.png][/icone] Fonctionnalités :[/titre][/center]
[center][titre=h4]Durée de vie :[/titre][/center]
[center][titre=h4][icone=pokedex-kanto.png][/icone]Pokédex :[/titre][/center]
 [center][titre=h4][icone=iconemap.png][/icone] Région :[/titre][/center]
[center][titre=h4]Personnages :[/titre][/center][center][titre=h4]Starters :[/titre][/center]
[center][titre=h4]Langues :[/titre][/center][center][titre=h4]Plateformes :[/titre][/center]
[center][titre=h4]Date de Sortie :[/titre][/center]
[center][titre=h4][icone=iconecoupe.png][/icone]Crédits :[/titre][/center]
[center][titre=h4][icone=iconecapture.png][/icone]Captures d'écran :[/titre]
[gallery][/gallery][/center]
[center][titre=h4]Téléchargement :[/titre]
[lien][/lien][texteLien][icone=iconetelechargement.png][/icone]Pokémon[/texteLien]`,
    },
    presentation_complete: {
        label: 'Présentation complète',
        contenu: `[section type=presentation titre="Présentation générale"]
Présentez le jeu, son concept et ce qui le distingue.
[/section]

[section type=histoire titre="Histoire"]
Décrivez l’univers, le scénario et le point de départ de l’aventure.
[/section]

[section type=gameplay titre="Gameplay et fonctionnalités"]
[liste]
[elementliste]Fonctionnalité principale[/elementliste]
[elementliste]Autre fonctionnalité[/elementliste]
[/liste]
[/section]

[section type=avis titre="Avis"]
Donnez votre avis sur les points forts et les points à améliorer.
[/section]

[section type=captures titre="Captures d’écran"]
[gallery][/gallery]
[/section]

[section type=telechargement titre="Téléchargement"]
[lien]https://[/lien][texteLien]Télécharger le jeu[/texteLien]
[/section]

[section type=credits titre="Crédits"]
Indiquez les auteurs et les personnes ayant participé au projet.
[/section]`,
    },
    section_presentation: {
        label: 'Présentation générale',
        contenu: `[section type=presentation titre="Présentation générale"]
Présentez le jeu, son concept et ce qui le distingue.
[/section]`,
    },
    section_avis: {
        label: 'Avis',
        contenu: `[section type=avis titre="Avis"]
Donnez votre avis sur les points forts et les points à améliorer.
[/section]`,
    },
    section_histoire: {
        label: 'Histoire',
        contenu: `[section type=histoire titre="Histoire"]
Décrivez l’univers, le scénario et le point de départ de l’aventure.
[/section]`,
    },
    section_gameplay: {
        label: 'Gameplay et fonctionnalités',
        contenu: `[section type=gameplay titre="Gameplay et fonctionnalités"]
[liste]
[elementliste]Fonctionnalité principale[/elementliste]
[elementliste]Autre fonctionnalité[/elementliste]
[/liste]
[/section]`,
    },
    section_captures: {
        label: 'Captures d’écran',
        contenu: `[section type=captures titre="Captures d’écran"]
[gallery][/gallery]
[/section]`,
    },
    section_telechargement: {
        label: 'Téléchargement',
        contenu: `[section type=telechargement titre="Téléchargement"]
[lien]https://[/lien][texteLien]Télécharger le jeu[/texteLien]
[/section]`,
    },
    section_credits: {
        label: 'Crédits',
        contenu: `[section type=credits titre="Crédits"]
Indiquez les auteurs et les personnes ayant participé au projet.
[/section]`,
    },
    section_date_sortie: { label: 'Date de sortie', contenu: `[section type=date_sortie titre="Date de sortie"]\nIndiquez la date de sortie.\n[/section]` },
    section_plateformes: { label: 'Plateformes', contenu: `[section type=plateformes titre="Plateformes"]\nIndiquez les plateformes compatibles.\n[/section]` },
    section_langues: { label: 'Langues', contenu: `[section type=langues titre="Langues"]\nIndiquez les langues disponibles.\n[/section]` },
    section_duree_vie: { label: 'Durée de vie', contenu: `[section type=duree_vie titre="Durée de vie"]\nIndiquez la durée de vie estimée.\n[/section]` },
    section_pokedex: { label: 'Pokédex', contenu: `[section type=pokedex titre="Pokédex"]\nPrésentez le Pokédex disponible.\n[/section]` },
    section_region: { label: 'Région', contenu: `[section type=region titre="Région"]\nPrésentez la région explorée.\n[/section]` },
    section_personnages: { label: 'Personnages', contenu: `[section type=personnages titre="Personnages"]\nPrésentez les personnages importants.\n[/section]` },
    section_starters: { label: 'Starters', contenu: `[section type=starters titre="Starters"]\nPrésentez les starters disponibles.\n[/section]` },
    section_difficulte: { label: 'Difficulté', contenu: `[section type=difficulte titre="Difficulté"]\nDécrivez les niveaux ou modes de difficulté.\n[/section]` },
    section_jouabilite: { label: 'Jouabilité', contenu: `[section type=jouabilite titre="Jouabilité"]\nDécrivez la prise en main et les mécaniques.\n[/section]` },
    section_modes_jeu: { label: 'Modes de jeu', contenu: `[section type=modes_jeu titre="Modes de jeu"]\nPrésentez les différents modes de jeu.\n[/section]` },
    section_multijoueur: { label: 'Multijoueur', contenu: `[section type=multijoueur titre="Multijoueur"]\nPrésentez les fonctionnalités multijoueur.\n[/section]` },
    section_post_game: { label: 'Post-game', contenu: `[section type=post_game titre="Post-game"]\nPrésentez le contenu disponible après l’aventure principale.\n[/section]` },
    section_nouveautes: { label: 'Nouveautés', contenu: `[section type=nouveautes titre="Nouveautés"]\nPrésentez les nouveautés et changements majeurs.\n[/section]` },
    section_quetes: { label: 'Quêtes', contenu: `[section type=quetes titre="Quêtes"]\nPrésentez les quêtes principales et annexes.\n[/section]` },
    section_objets: { label: 'Objets', contenu: `[section type=objets titre="Objets"]\nPrésentez les objets et équipements importants.\n[/section]` },
    section_niveaux: { label: 'Niveaux', contenu: `[section type=niveaux titre="Niveaux"]\nPrésentez les niveaux, mondes ou circuits.\n[/section]` },
    section_graphismes: { label: 'Graphismes', contenu: `[section type=graphismes titre="Graphismes"]\nPrésentez la direction artistique et les améliorations visuelles.\n[/section]` },
    section_bande_son: { label: 'Bande-son', contenu: `[section type=bande_son titre="Bande-son"]\nPrésentez les musiques et l’ambiance sonore.\n[/section]` },
    section_trailer: { label: 'Trailer et vidéos', contenu: `[section type=trailer titre="Trailer et vidéos"]\n[video]https://www.youtube.com/watch?v=...[/video]\n[/section]` },
    section_installation: { label: 'Installation', contenu: `[section type=installation titre="Installation"]\nExpliquez comment installer et lancer le jeu.\n[/section]` },
    section_liens: { label: 'Liens officiels', contenu: `[section type=liens titre="Liens officiels"]\n[lien]https://[/lien][texteLien]Site officiel[/texteLien]\n[/section]` },
    section_mises_a_jour: { label: 'Mises à jour', contenu: `[section type=mises_a_jour titre="Mises à jour"]\nIndiquez la version et les dernières mises à jour.\n[/section]` },
    section_legendaires: { label: 'Légendaires', contenu: `[section type=legendaires titre="Légendaires"]\nPrésentez les Pokémon légendaires disponibles.\n[/section]` },
    section_monde: { label: 'Monde et environnement', contenu: `[section type=monde titre="Monde et environnement"]\nPrésentez les lieux, biomes et environnements.\n[/section]` },
    section_power_ups: { label: 'Pouvoirs et power-ups', contenu: `[section type=power_ups titre="Pouvoirs et power-ups"]\nPrésentez les pouvoirs, capacités ou transformations.\n[/section]` },
    section_fonctionnalites: { label: 'Fonctionnalités', contenu: `[section type=fonctionnalites titre="Fonctionnalités"]\n[liste]\n[elementliste]Fonctionnalité principale[/elementliste]\n[elementliste]Autre fonctionnalité[/elementliste]\n[/liste]\n[/section]` },
    section_synopsis: { label: 'Synopsis', contenu: `[section type=synopsis titre="Synopsis"]\nRésumez le point de départ de l’aventure.\n[/section]` },
    section_aventure: { label: 'Aventure', contenu: `[section type=aventure titre="Aventure"]\nPrésentez le déroulement de l’aventure.\n[/section]` },
    section_discord: { label: 'Discord', contenu: `[section type=discord titre="Discord"]\n[lien]https://discord.gg/[/lien][texteLien]Rejoindre le serveur Discord[/texteLien]\n[/section]` },
    section_github: { label: 'GitHub', contenu: `[section type=github titre="GitHub"]\n[lien]https://github.com/[/lien][texteLien]Voir le projet sur GitHub[/texteLien]\n[/section]` },
    section_wiki: { label: 'Wiki', contenu: `[section type=wiki titre="Wiki"]\n[lien]https://[/lien][texteLien]Consulter le wiki[/texteLien]\n[/section]` },
    section_site_officiel: { label: 'Site officiel', contenu: `[section type=site_officiel titre="Site officiel"]\n[lien]https://[/lien][texteLien]Visiter le site officiel[/texteLien]\n[/section]` },
    section_documentation: { label: 'Documentation', contenu: `[section type=documentation titre="Documentation"]\nAjoutez les informations et liens de documentation.\n[/section]` },
    section_mods: { label: 'Mods', contenu: `[section type=mods titre="Mods"]\nPrésentez les mods, extensions ou packs compatibles.\n[/section]` },
    section_configuration: { label: 'Configuration', contenu: `[section type=configuration titre="Configuration"]\nIndiquez la configuration minimale ou recommandée.\n[/section]` },
};
