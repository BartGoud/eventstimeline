# Agenda-tijdlijn (iCal) · handleiding en beperkingen

Versie 1.1.0 · 6 oktober 2026 · getest op ... (WordPress, Blocksy, Elementor Free, WPCode Lite)

Een WPCode-snippet die een Google Agenda via het geheime iCal-adres uitleest en als verticale tijdlijn toont (aankomend bovenaan, voorbij eronder). Eén shortcode: `[agenda_tijdlijn]`.

## 1. Installeren op een klantsite

1. **WPCode** > Snippet toevoegen > Eigen code > **PHP-snippet**. Plak het hele bestand `agenda-tijdlijn-v1.1.0.php`, **zonder** een `<?php` erbij. Locatie: *Overal uitvoeren*. Titel bijvoorbeeld "Agenda-tijdlijn (iCal) v1.1.0".
2. Pas het blok **INSTELLINGEN** bovenaan aan (zie punt 2). Zet de feed-URL erin.
3. Opslaan en activeren.
4. In Elementor: zet op de pagina een container in **volle breedte** en plaats daarin de **Shortcode-widget** met `[agenda_tijdlijn]`. De kop en de introtekst boven de tijdlijn zijn gewone Elementor-widgets. De snippet levert alleen de tijdlijn.
5. Een kleinere variant, bijvoorbeeld voor de homepage: `[agenda_tijdlijn aankomend="3" voorbij="0"]`.

## 2. Wat je per klant aanpast (alleen het blok INSTELLINGEN)

| Instelling | Wat het doet | Standaard |
|---|---|---|
| `feed_url` | Geheim iCal-adres van de agenda (moet met https beginnen) | leeg |
| `max_aankomend` | Aantal aankomende events | 10 |
| `max_voorbij` | Aantal voorbije events (0 = geen) | 5 |
| `voorbij_dagen` | Voorbije events ouder dan dit aantal dagen worden niet getoond | 365 |
| `cache_minuten` | Hoe lang de feed bewaard blijft (minimaal 5) | 60 |
| `tijdzone` | Tijdzone voor alle weergegeven tijden | Europe/Amsterdam |
| `css_voorvoegsel` | Voorvoegsel van de CSS-klassen (letters, cijfers, streepje) | k9tl |
| `kop_niveau` | h-niveau van de event-titels (2 t/m 6) | 3 |
| `tekst_geen_events`, `tekst_fout` | Berichten voor bezoekers | zie bestand |
| `label_aankomend`, `label_nu`, `label_voorbij`, `tekst_hele_dag`, `link_tekst_aankomend`, `link_tekst_voorbij` | Teksten op de kaarten | zie bestand |
| `kleur_*` | Kleuren als CSS-variabelen van het thema (bij Blocksy `--theme-palette-color-1` t/m 8) | zie hieronder |

**Kleuren per klant controleren.** De slots 1 t/m 8 hebben per site een andere rol. Koppel de zes rollen aan de goede slot:

- `kleur_hoofd`: donkere merkkleur (titels, links, lijn)
- `kleur_tekst`: lopende tekst
- `kleur_accent`: marker bij aankomende events en het pijltje
- `kleur_accent_tekst`: een **donkerder** variant van het accent voor het label "Aankomend". Op een lichte kaart moet die minimaal 4,5:1 contrast halen. Een licht oranje accent haalt dat meestal niet.
- `kleur_zand`: zachte kaartachtergrond bij aankomend
- `kleur_vlak`: achtergrond van de pagina (rand om de marker)

## 3. De agenda invullen (afspraken voor de klant)

- Maak een **aparte agenda** (bijvoorbeeld "Website") en zet alleen events daarin die op de site mogen staan.
- Het adres vind je in Google Agenda op een computer: agenda > Instellingen en delen > **Agenda integreren** > **Geheim adres in iCal-indeling**. Het adres begint met `https://calendar.google.com/calendar/ical/` en eindigt op `basic.ics`. Een abonneerlink met `cid=` werkt **niet**.
- De agenda hoeft **niet** openbaar te zijn. Laat "Beschikbaar maken voor het publiek" uit staan.
- **Titel, locatie en beschrijving komen letterlijk op de site.** Zet er niets in dat niet publiek mag.
- Vul altijd een **eindtijd** in. Zonder eindtijd geldt een event tot het einde van die dag.
- Een event van een hele dag of meerdere dagen: vink "Hele dag" aan.
- Een knop op de kaart? Zet in de beschrijving een eigen regel: `link: https://voorbeeld.nl | Aanmelden`. Zonder het deel na de `|` komt de standaardtekst. Zonder `link:`-regel komt er geen knop.
- Een geannuleerd of verwijderd event verdwijnt vanzelf. Aankomend of voorbij wordt automatisch uit de datum bepaald.

## 4. Controleren dat het werkt

1. Maak een **privé testpagina** met de shortcode (nooit direct op de echte pagina beginnen).
2. Staat onder de tijdlijn het grijze beheerdersblok met "Bron: ..." en zonder foutmelding?
3. Kloppen datums en tijden met Google Agenda (let op de tijdzone)?
4. Klopt de volgorde: aankomend bovenaan (oplopend), daaronder voorbij (nieuwste eerst)?
5. Bekijk desktop en telefoon. Onder 1000 px breed staat alles onder elkaar.
6. Klik **Feed nu verversen** en kijk of de uitslag verschijnt.
7. Haal de testpagina weg zodra de echte pagina werkt.

## 5. Verversen en caching

- De feed wordt `cache_minuten` (standaard 60) bewaard. Bij een foutmelding van Google blijft de **laatst bewaarde versie** zichtbaar. Na een mislukte poging wacht de snippet 5 minuten voordat hij Google opnieuw bevraagt.
- Alleen **beheerders** zien onder de tijdlijn het blok met de knop **Feed nu verversen**. De knop haalt de feed opnieuw op en leegt de paginacache van **die pagina** (xSpeed, LiteSpeed Cache, WP Rocket, W3 Total Cache, SiteGround; bij WP Super Cache de hele cache). Een andere cache koppel je aan de actie `bgtl_cache_legen`.
- **De knop kan Googles eigen vertraging niet versnellen.** Een nieuw of gewijzigd event kan na het opslaan in Google nog minuten tot uren ontbreken in de feed.
- Op een site **met paginacache** wisselt een event pas van "aankomend" naar "voorbij" als die cache verloopt. Zet de paginacache voor de agendapagina niet langer dan ongeveer een uur, of sluit die pagina uit.

## 6. Bekende beperkingen (wat je klanten niet moet beloven)

**Inhoud**
- **Herhalende events** (bijvoorbeeld een wekelijkse les) worden **overgeslagen**. Beheerders zien een melding met het aantal. Wil een klant dat, dan moet dat apart gebouwd worden.
- **Eén agenda** per tijdlijn. Meerdere agenda's samenvoegen kan niet.
- **Geen foto's** per event.
- In beschrijvingen blijven alleen vet, cursief, links (http en https) en regeleinden over. Opsommingen worden regels met een bolletje. Afbeeldingen en tabellen verdwijnen.
- Geen filters, paginering of event-schema voor Google (SEO).

**Werking**
- Nieuwe of gewijzigde events verschijnen met vertraging (zie punt 5).
- Het geheime adres staat leesbaar in WPCode voor iedere beheerder. Lekt het, reset het dan in Google (Instellingen en delen) en pas het adres aan.
- Datums volgen de **sitetaal**. Staat WordPress niet op Nederlands, dan zijn de maanden Engels.
- Een tijdzone is één instelling per site. Events in andere tijdzones worden omgerekend.
- Ontworpen voor een **volle breedte**. In een smalle kolom op desktop wordt de zigzag onrustig.
- Het thema moet de CSS-variabelen bieden die je in de kleurinstellingen noemt. Oudere browsers (van vóór 2023) tonen de kleurtinten vereenvoudigd.
- Grenzen: feed maximaal 5 MB en 5000 events, maximaal 100 events per lijst, 200 aankomende en 200 voorbije events worden bewaard.

**Zelf doen**
- Updaten is **handmatig plakken**. Het versienummer staat in het commentaarblok bovenaan het bestand en als `data-versie` in de HTML van de tijdlijn.
- Niet getest op Safari/iOS en in de Elementor-editor (daar kan het voorbeeld afwijken van de echte pagina).

## 7. Updaten en versielog

**Updaten:** kopieer eerst het blok INSTELLINGEN van de klantsite, plak de nieuwe versie over de oude, en zet je instellingen terug. Controleer daarna op de testpagina.

| Versie | Datum | Wijziging |
|---|---|---|
| 1.1.0 | 2026-10-06 | Knop "Feed nu verversen" voor beheerders, met paginacache legen en een uitslag. De oude `?bgtl_ververs=1`-truc is verwijderd. |
| 1.0.0 | 2026-10-06 | Eerste versie. |
