# StudierendenFinanzSoftware StuFiS

StuFiS ist ein auf verfasste Studierendenschaften zugeschnitten Software zur Verwaltung von Anträgen, Abrechnungen, Zahlungsvorgängen, Buchungen und Haushaltsplänen. Mit dieser Finanzverwaltungssoftware ist es möglich den gesamten Projektlebenslauf vom Antrag bei der Studierendenvertretung bis hin zum Haushaltsabschluss dokumentiert nachvollziehen und durchführen zu können. Ziel ist es eine intuitive Lösung für die kurzen Amtszeiten und schnellen Übergaben zu bieten und neben dem Datenschutz möglichst viel Transparenz gegenüber den Entscheider:innen sowie Studierenden zu schaffen. Diese Web-App ermöglicht ein digitales Arbeiten auch von Zuhause aus, ohne dass ein Informationsverlust entstehen kann. Durch das Mehraugen-Prinzip und eine geschickte Gestaltung der Software werden Fehler und Veruntreuung vermieden.

Mehr Infos zu Software finden sich [hier](https://open-administration.de/index.php/finanzverwaltungssoftware/).

## Demo 
Alle Infos zur Demo finden sich unter https://www.stufis.de/demo-login

## Dokumentation
Unsere Dokumentation und die Hilfeseiten zur Software können [hier](https://doku.open-administration.de/) gefunden werden

# Sponsoren und Finanzierung

StuFis ist Teil der 15. Runde des [PrototypeFund](https://prototypefund.de/project/studierendenfinanzsoftware/) und damit gefördert durch das [BMBF](https://www.bmbf.de).

Als Open-Source-Software finanziert sich die Weiterentwicklung aktuell aus dem Hosting-Dienstleistungs-Angebot (Software-as-a-Service) von Open Administration. Unsere aktuellen Unterstützer:innen sind 
- StuRa der Universität Erfurt
- StuRa der Technischen Universität Ilmenau
- StuRa der Ernst-Abbe-Hochschule Jena
- StuRa der Fachhochschule Erfurt
- StuKo der Bauhaus-Universität Weimar
- AStA der ev. Hochschule für Soziale Arbeit Hamburg (Rauhes Haus)

# Installation 

The installation process is pretty simple and straight forward. The configuration can be a bit more challenging. See our [User-Guide](https://doku.stufis.de) as reference. 

## Requirements: 
* php8.2
* composer
* nodeJS / npm
* MariaDB / Mysql
* optional: a registration Number from [Deutsche Kreditwirtschaft](https://www.hbci-zka.de/register/hersteller.htm)
* OAuth2 Identity Provider (IdP) or StuMV
* commandline access to your server, ftp only could work, but is more work / not officially supported
* basic knowledge how to admin a (linux) server
* since v4.2.0 a fluxui.dev licence, [more infos](https://www.stufis.de/blog/2025/warum-flux)

There might be an unsupported docker image in the future, but right now it has no priority. PRs appreciated.

## Installation (Dev Local & Production)

```bash
git clone https://github.com/openAdministration/StuFis 
composer install # in production: composer install --no-dev --optimize  
npm install # in production: npm ci
npm run dev # in production: npm run build
cp .env.example .env
php artisan key:generate
```
And fill in your Environment File! 

```bash
php artisan migrate
php artisan serve
# other terminal (for tailwindcss live-compiling)
npm run dev
```

# Feature Request 
Please write an issue in this github repository 

# Security 

Please write a mail to [service@open-administration.de](mailto:service@open-administration.de) for a responsible disclosure procedure.




