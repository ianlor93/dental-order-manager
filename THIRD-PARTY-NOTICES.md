# Third-party notices

Questo file elenca il materiale di terze parti **incluso nel repository**, con
percorso, origine e licenza. Il progetto nel suo insieme è distribuito con licenza
**AGPL-3.0-or-later**; le licenze qui sotto si applicano ai soli file indicati.

## `app/Support/Firebase/JWT/`

Copia vendorizzata della libreria **firebase/php-jwt**, con il namespace riscritto
per convivere con la copia distribuita dall'app Nextcloud che la usa.

- **Origine:** https://github.com/firebase/php-jwt
- **Licenza:** BSD-3-Clause
- **Testo della licenza:** `app/Support/Firebase/JWT/LICENSE`

## `public/ds/fonts/`

Famiglia di caratteri **Ubuntu**, inclusa nel repository per servire i font in
locale senza dipendere da una CDN esterna.

- **Origine:** https://design.ubuntu.com/font
- **Licenza:** Ubuntu Font Licence 1.0
- **Testo della licenza:** `public/ds/fonts/UFL.txt`

## Struttura del progetto

La struttura di directory e i file di configurazione derivano dallo **skeleton di
Laravel** (`laravel/laravel`).

- **Origine:** https://github.com/laravel/laravel
- **Licenza:** MIT

## Dipendenze installate con Composer

Le dipendenze gestite da Composer **non sono incluse** in questo repository: la
directory `vendor/` non è versionata e viene generata con `composer install`.
Ciascuna dipendenza ha la propria licenza; l'elenco completo si ottiene con:

```
composer licenses
```
