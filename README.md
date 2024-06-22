# StudierendenFinanzSoftware StuFiS

StuFiS ist ein auf verfasste Studierendenschaften zugeschnitten Software zur Verwaltung von Anträgen, Abrechnungen, Zahlungsvorgängen, Buchungen und Haushaltsplänen. Mit dieser Finanzverwaltungssoftware ist es möglich den gesamten Projektlebenslauf vom Antrag bei der Studierendenvertretung bis hin zum Haushaltsabschluss dokumentiert nachvollziehen und durchführen zu können. Ziel ist es eine intuitive Lösung für die kurzen Amtszeiten und schnellen Übergaben zu bieten und neben dem Datenschutz möglichst viel Transparenz gegenüber den Entscheider:innen sowie Studierenden zu schaffen. Diese Web-App ermöglicht ein digitales Arbeiten auch von Zuhause aus, ohne dass ein Informationsverlust entstehen kann. Durch das Mehraugen-Prinzip und eine geschickte Gestaltung der Software werden Fehler und Veruntreuung vermieden.

Mehr Infos zu Software finden sich [hier](https://open-administration.de/index.php/finanzverwaltungssoftware/).

## Demo 
Alle Infos zur Demo finden sich unter [https://www.stufis.de/demo-login]

## Dokumentation
Unsere Dokumentation und die Hilfeseiten zur Software können [hier](https://doku.open-administration.de/) gefunden werden

# Sponsoren und Finanzierung

StuFis ist Teil der 15. Runde des [PrototypeFund](https://prototypefund.de/project/studierendenfinanzsoftware/) und damit gefördert durch das [BMBF](https://www.bmbf.de).

Als Open-Source-Software finanziert sich die Weiterentwicklung aktuell aus dem Hosting-Dienstleistungs-Angebot (Software-as-a-Service) der Open Administration UG (haftungsbeschränkt). Unsere aktuellen Unterstützer:innen sind 
- StuRa der Universität Erfurt
- StuRa der Technischen Universität Ilmenau
- StuRa der Ernst-Abbe-Hochschule Jena
- StuRa der Hochschule Schmalkalden
- AStA der ev. Hochschule für Soziale Arbeit Hamburg (Rauhes Haus)

# Installation 

The installation process is pretty simple and straight forward. The configuration can be a bit more challenging. See our [User-Guide](https://doku.stufis.de) as reference. 

## Requirements: 
* php8.2
* MariaDB / Mysql
* a registration Number from [Deutsche Kreditwirtschaft](https://www.hbci-zka.de/register/hersteller.htm)
* OAuth2 Identity Provider (IdP), SAML in the future 

## Installation Dev

```bash
git clone https://github.com/openAdministration/StuFis 
composer install 
npm install
npm run dev
cp .env.example .env
php artisan key:generate
```
And fill in your Environment File! 

```bash
php artisan migrate
php artisan serve
# other terminal (for tailwindcss live-compiling)
npm run watch
```

# Feature Request 
Please write an issue in this github repository 

# Security 

Please write a mail to [service@open-administration.de](mailto:service@open-administration.de) for a responsible disclosure procedure.




