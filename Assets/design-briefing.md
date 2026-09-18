# Design briefing — Urenregistratie & Facturatiesysteem (Accurity)

## Project in het kort

Een intern urenregistratie- en facturatiesysteem voor een ZZP'er (eigenaar van **Accurity — online communicatie**). Het systeem heeft twee gebruikersgroepen met een heel verschillend gebruiksdoel:

- **Beheerder (admin)** — de ZZP'er zelf. Beheert klanten, projecten, tarieven, registreert uren en maakt facturen. Gebruikt het systeem dagelijks/wekelijks, intern, functioneel.
- **Opdrachtgever (client)** — de klant van de ZZP'er. Logt maandelijks kort in om geregistreerde uren in te zien, goed of af te keuren, en facturen te downloaden. Dit is het enige contactmoment dat de klant met het systeem heeft — het moet vertrouwen wekken en in één oogopslag duidelijk zijn.

Technisch: Laravel 13, standaard controllers + Blade templates, geen JS-framework, eenvoudige/lichte CSS. Het ontwerp moet dus **uitvoerbaar zijn in platte HTML/CSS** — geen afhankelijkheid van componentbibliotheken, geen complexe interactiepatronen die JS vereisen.

## Huisstijl (bestaand, aanwezig in deze map)

Er is al een huisstijl vanuit het bedrijf **Accurity**, aangeleverd in deze `Assets`-map:

| Bestand | Gebruik |
|---|---|
| `Logo Accurity.png`, `Logo Accurity.psd` | Logo (blauw vlak + witte "Accurity" + wit vlak met blauwe tagline "online communicatie") |
| `Logo Accurity.base64.txt` | Logo als base64, voor inline gebruik (bv. in de PDF-template of e-mail) |
| `Bitter.woff2` | Display/heading-font (slab-serif, gebruikt in het logo voor "Accurity") — voor gebruik op het web (`@font-face`) |
| `Bitter_ttf.base64.txt` | Bitter als base64-TTF — nodig voor het registreren van het lettertype in **dompdf** (PDF-generatie ondersteunt geen woff2, wel TTF/OTF) |
| `Calibri.woff2` | Body-font — voor gebruik op het web |
| `Calibri_ttf.base64.txt` | Calibri als base64-TTF — voor gebruik in de factuur-PDF |

**Merkkleur** (gesampled uit het logo): `#027CB5` — een stevig, helder blauw. Wit (`#FFFFFF`) als contrastkleur, zoals in het logo (blauw vlak + wit vlak).

### Wat de designer hiermee moet doen
Bovenstaande is vastgelegd materiaal, geen startpunt om vanaf nul te ontwerpen. De opdracht is: **bouw een consistent visueel systeem rond deze bestaande huisstijl** — niet een nieuw merk bedenken. Concreet nodig:

1. Een volledig **kleurenpalet** afgeleid van het merkblauw `#027CB5`: een lichtere/donkerdere variant voor hover-states, een neutrale grijstinten-schaal voor tekst/achtergronden/randen, en semantische kleuren voor status (bijv. groen voor "goedgekeurd/betaald", geel/oranje voor "openstaand/wacht op actie", rood voor "afgekeurd/vervallen"). Deze semantische kleuren moeten wél passen bij het rustige, betrouwbare karakter van het blauw — geen felle systeemkleuren die ermee botsen.
2. Een **typografieschaal**: Bitter voor koppen (H1–H3, en labels waar nadruk gewenst is), Calibri voor lopende tekst, formulieren, tabellen. Duidelijke groottes/gewichten voor koppen, body, kleine hulptekst (bv. datums, statuslabels).
3. Een **componentstijl** die met platte CSS te bouwen is: primaire/secundaire knoppen, formuliervelden (input/select/textarea + focus-state), tabellen (voor urenoverzichten), statuslabels/badges (open/goedgekeurd/afgekeurd/gefactureerd/betaald/vervallen), een simpele navigatiebalk.

## Schermen — in prioriteitsvolgorde

### 1. Login + algemene layout/navigatie
De basis die overal terugkomt: inlogscherm (met logo), de vaste layout-shell (navbar/header, content-container, eventueel voettekst), en hoe primaire/secundaire acties er in het algemeen uitzien. Dit bepaalt de "look and feel" voor de rest van de applicatie — hier ligt de meeste ontwerpvrijheid, en de rest bouwt hierop voort.

Let op: er zijn **twee losse layout-contexten** — een interne (admin) en een klantgerichte (portal). Ze mogen zichtbaar bij elkaar horen (zelfde huisstijl/kleuren/typografie), maar de klant-context moet net iets soberder/formeler aanvoelen (dit is een zakelijk contactmoment met een klant, geen intern werktool).

### 2. Admin-dashboard & beheerschermen
Overzichtsschermen voor de beheerder: een dashboard met kengetallen (openstaande facturen, maanden klaar om te factureren, openstaande goedkeuringen), en standaard CRUD-schermen (lijst + formulier) voor klanten en projecten. Functioneel, informatiedicht, maar niet druk — de beheerder gebruikt dit regelmatig en moet snel kunnen scannen.

### 3. Uren registratie-scherm
Het scherm waar de beheerder per project/dag uren invoert: datum, aantal uren (decimaal), omschrijving. Waarschijnlijk een tabelvorm (lijst van dagen binnen een maand) met een toevoeg-formulier. Dit scherm wordt vaak gebruikt — moet snel en foutloos in te vullen zijn, dus rust in de tabel-layout en duidelijke invoervelden zijn belangrijker dan visuele flair.

### 4. Klant-portal (goedkeuring)
Het scherm waar de opdrachtgever de uren van een maand ziet (datum, uren, omschrijving, totaal) met twee duidelijke acties: **Goedkeuren** / **Afkeuren** (met reden). Dit is het belangrijkste vertrouwensmoment in de hele applicatie — moet overzichtelijk, professioneel en ondubbelzinnig zijn. De status (nog te beoordelen / goedgekeurd / afgekeurd) moet in één oogopslag duidelijk zijn. Ook een "Mijn facturen"-overzicht met downloadlink en betaalstatus hoort in deze context.

### 5. Factuur-PDF layout
Het visuele ontwerp van de factuur zelf, gegenereerd via dompdf (**beperkte CSS-ondersteuning** — geen flexbox/grid, geen moderne layout-technieken; werk met tabellen/`display:block`/`float` zoals in klassieke e-mail-/printontwerpen). Bevat:
- Header met logo en bedrijfsgegevens (Accurity) links of rechts, factuurgegevens (nummer, datum, vervaldatum) ernaast.
- "Factuur aan"-blok met klantgegevens.
- Eén samengevatte regel per project/maand (uren × tarief), met BTW-berekening en totaal.
- Een los urenoverzicht (tabel: datum, omschrijving, uren) als bijlage/tweede sectie.
- Simpele, zakelijke uitstraling — dit document wordt door de klant bewaard/geboekhoud, moet er "officieel" uitzien.

## Technische randvoorwaarden voor de designer

- **Geen JS-afhankelijke interactiepatronen** (geen modals/dropdowns/carousels die JS-libraries vereisen) — Blade + server-side forms, eventueel een paar regels vanilla JS voor kleine dingen (bv. een bevestigingsdialoog), maar het ontwerp moet zonder JS-framework te bouwen zijn.
- **CSS blijft licht**: geen Tailwind/Bootstrap-component-library, gewoon een eigen `app.css`. Ontwerp moet dus als concrete stijlregels (kleuren, spacing, typografie-schaal) overdraagbaar zijn, niet als losse Figma-componenten die een framework veronderstellen.
- **Twee losse stylesheets/contexten denkbaar**: één voor de webapplicatie (admin + klantportal, mag `Bitter.woff2`/`Calibri.woff2` gebruiken), één voor de PDF-template (moet de TTF-varianten gebruiken, beperkte CSS).
- Schermbreedte: primair desktop/laptop-gebruik (beheerder werkt op een laptop, klant beoordeelt waarschijnlijk ook op desktop), maar het moet niet volledig breken op een tablet-breedte. Geen harde eis voor volledige mobiele responsiveness.

## Gewenste oplevering

- Kleurenpalet (hex-waarden, incl. semantische statuskleuren) en typografieschaal, als naslag-tabel.
- Stijlvoorbeelden ("style tile" of vergelijkbaar) voor knoppen, formuliervelden, tabellen, statuslabels.
- Schermontwerpen (of duidelijke lo-fi/hi-fi schetsen) voor de 5 bovenstaande schermen, in volgorde van prioriteit — het is prima als niet alles even uitgewerkt is, zolang schermen 1–3 solide zijn (dat bepaalt de rest van de applicatie).
