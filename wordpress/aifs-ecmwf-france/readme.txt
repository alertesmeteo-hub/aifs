=== AIFS / ECMWF France ===
Contributors: alertesmeteo
Tags: meteo, aifs, ecmwf, ia, carte, previsions, avada
Requires at least: 5.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Cartes interactives et prévisions du modèle IA déterministe AIFS/ECMWF pour 34 746 communes françaises.

== Description ==

Le shortcode [aifs_meteo] affiche dans un seul module :

* une carte AIFS interactive avec zoom, animation et valeur au survol ;
* une recherche par ville ou code postal et la géolocalisation ;
* les prévisions générales jusqu'à +360 h (15 jours) ;
* quatre graphiques et un diagnostic neige.

Les données proviennent directement d'ECMWF Open Data, modèle AIFS Single (IA déterministe) à 0,25°.

**Limite du modèle :** AIFS ne publie ni rafales, ni CAPE, ni réflectivité radar dérivée. Contrairement au module CEP/IFS, ce module n'affiche donc ni carte de rafales, ni tableau orages — plutôt que d'afficher une valeur toujours nulle qui laisserait croire à une absence de risque.

== Installation ==

1. Téléversez le ZIP dans Extensions > Ajouter une extension.
2. Activez AIFS / ECMWF France.
3. Vérifiez l'URL dans Réglages > AIFS / ECMWF.
4. Insérez [aifs_meteo] dans un bloc Avada.

Exemple : [aifs_meteo code="75056" departement="75" ville="Paris" heures="360"]

== Changelog ==

= 1.0.0 =
* Première version indépendante AIFS/ECMWF 0,25°, construite sur la base du module CEP/IFS.
* Pipeline GitHub Actions jusqu'à +360 h (échéances 6 h) et publication sur la branche data.
* Cartes, recherche, tableaux, graphiques et outils interactifs dans un shortcode unique.
* Pas de carte rafales ni de tableau orages : champs non publiés par AIFS Single.
