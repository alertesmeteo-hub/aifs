# AIFS / ECMWF France — cartes et prévisions WordPress

Ce dépôt construit une chaîne directe **ECMWF Open Data AIFS Single 0,25° → GitHub Actions → WordPress/Avada**. Il publie les cartes interactives et les prévisions de 34 746 communes françaises sur une branche `data`, sans intermédiaire météorologique. Construit sur la même base technique que le module sœur `alertesmeteo-hub/cep`.

## Production

- modèle IA déterministe AIFS Single (ECMWF) ;
- grille ouverte 0,25° (environ 28 km), identique à la grille IFS Open Data ;
- runs principaux 00 UTC et 12 UTC ;
- échéances toutes les 6 h jusqu'à +360 h (15 jours) ;
- température, point de rosée, humidité, vent moyen, pression, pluie, neige et nuages ;
- cartes WebP en isovaleurs remplies, lissées par interpolation bicubique, valeur sous la souris et prévisions par commune ;
- isobares de pression tous les 4 hPa et flèches directionnelles lisibles sur la carte de vent ;
- copie directe de la carte dans le presse-papiers et téléchargement PNG compatible WebGL ;
- limites départementales détaillées issues des tracés IGN à 25 %, intégrées au dépôt ;
- module et tableaux élargis jusqu'à 1 480 px sur les grands écrans.

**Limite du modèle :** AIFS ne publie ni rafales (10fg), ni CAPE (mucape), ni réflectivité radar dérivée. Contrairement à `alertesmeteo-hub/cep`, ce module n'affiche donc **ni carte de rafales, ni CAPE, ni tableau orages/grêle/foudre** — ces champs sont absents du produit plutôt que de publier une valeur toujours nulle qui laisserait croire à une absence de risque.

Les données AIFS Open Data sont en GRIB2 et publiées sous licence CC BY 4.0. Aucune clé API ECMWF n'est nécessaire.

## Installation GitHub

1. Copiez tout le contenu de cette archive à la racine du dépôt `alertesmeteo-hub/aifs`.
2. Dans **Settings → Actions → General → Workflow permissions**, activez **Read and write permissions**.
3. Lancez **Actions → Mise à jour AIFS France → Run workflow**.
4. Vérifiez ensuite la branche `data` et son fichier `index.json`.

Le workflow automatique est lancé une seule fois par jour à 08 h 30 UTC,
soit vers 10 h en France (10 h 30 en été, 9 h 30 en hiver).

Commande locale équivalente :

```bash
python -m pip install -r requirements.txt
python scripts/update_aifs_france.py \
  --catalog config/communes-france.json \
  --output-dir build/national \
  --forecast-hours 360
```

## Installation WordPress

Installez le ZIP séparé `aifs-ecmwf-france-v1.0.0.zip`, activez-le, puis utilisez :

```text
[aifs_meteo]
```

Exemple :

```text
[aifs_meteo ville="Paris" code="75056" departement="75" heures="360"]
```

L'URL de données par défaut est :

```text
https://raw.githubusercontent.com/alertesmeteo-hub/aifs/data
```

## Sources

- [ECMWF Open Data](https://www.ecmwf.int/en/forecasts/datasets/open-data)
- [Client officiel ecmwf-opendata](https://github.com/ecmwf/ecmwf-opendata)
- API Découpage administratif de la République française pour la recherche des communes
- [France GeoJSON](https://github.com/gregoiredavid/france-geojson), tracés IGN Admin Express COG sous Licence Ouverte

Site : [www.alertes-meteo.com](https://www.alertes-meteo.com/) — module v1.0.0.
