# Order Manager

[English](README.md) · **Italiano**

Order Manager è un'applicazione web per **laboratori odontotecnici digitali** che fabbricano **dispositivi medici su misura** ai sensi del Regolamento UE sui dispositivi medici (**MDR 2017/745**). Copre l'intera vita di un ordine: dalla prescrizione inviata dallo studio dentistico alla produzione, alla spedizione e all'archiviazione a lungo termine. Gli obblighi regolatori e di privacy (MDR, GDPR) sono parte del flusso di lavoro, invece di restare sulla carta.

È stata progettata per un laboratorio odontotecnico digitale italiano, dove è stata usata in produzione. Questo repository ne è una **versione di riferimento ripulita**: sono stati tolti l'identità del laboratorio, le credenziali, i dettagli dell'infrastruttura e le funzioni commerciali, e tutto ciò che identifica chi gestisce l'applicazione è diventato configurazione.

---

## Cosa fa

**Ordini e produzione**
- **Scambio diretto dei file tra studio e laboratorio.** Scansioni, progetti e file di produzione passano dall'uno all'altro sui server del laboratorio, senza servizi esterni di trasferimento file né archivi cloud di terzi. I dati clinici restano sotto il controllo del laboratorio, e l'elenco dei sub-responsabili resta corto. Se davanti al server c'è un reverse proxy o una CDN, il traffico passa comunque da lì durante il transito.
- Prescrizioni dagli studi dentistici, con caricamento dei file CAD/CAM a blocchi ripristinabili e scansione antivirus (ClamAV).
- Gli ordini attraversano aree di archiviazione dedicate: ingresso, banco di lavoro, finitura e spedizione, archivio storico MDR.
- Documenti PDF generati per ogni ordine: foglio di richiesta, rapporto di produzione, bolla di consegna, etichetta di spedizione, informativa per il paziente e la **dichiarazione per i dispositivi su misura** (Allegato XIII MDR).
- Logistica (bolle di consegna) e un'area contabile per fatturare il lavoro del laboratorio, con riepiloghi mensili.

**Conformità regolatoria (MDR)**
- Sorveglianza post-commercializzazione: segnalazione degli incidenti, stato di revisione e scadenza della notifica all'autorità competente, con un rapporto PMS dedicato.
- Archivio a lungo termine con registrazione degli accessi e rapporti di conservazione.

**Privacy e testi legali (GDPR)**
- Testi legali con versione: condizioni di fornitura, clausole vessatorie, informativa privacy, accordo sul trattamento dei dati. Le accettazioni sono registrate documento per documento, con versione e impronta del contenuto.
- I testi legali sono modelli: i dati di chi gestisce l'applicazione (ragione sociale, partita IVA, indirizzo, foro competente…) vengono inseriti dalla configurazione al momento della visualizzazione.
- Diritti dell'interessato: esportazione dei dati (ZIP con riepilogo PDF), richieste di rettifica e un flusso di cancellazione (vedi *Limiti noti*).
- Elenco pubblico dei sub-responsabili del trattamento, in HTML e JSON.
- I dati personali vengono rimossi dai log dell'applicazione.

**Account e integrazione**
- Accesso unico da **Nextcloud** tramite token JWT firmati (ES256), verificati con una chiave pubblica configurata.
- Percorso di registrazione autonoma: iscrizione da una pagina pubblica, verifica dell'email, approvazione manuale e creazione dell'account Nextcloud su un canale firmato separato.
- Livelli di spazio per studio, che impostano la quota Nextcloud dell'utente, e crediti mensili usati da un'applicazione CAD collegata, pubblicata a parte.
- Email di servizio costruite su un unico modello, con un comando per generarne le anteprime senza inviarle (`php artisan mail:preview`).
- Limitazione graduata delle richieste sulle route pubbliche e autenticate.

---

## Architettura

- **Laravel 12** (PHP 8.2 o successivo; sviluppata e verificata su PHP 8.4).
- **MySQL/MariaDB**, condiviso con Nextcloud: gli utenti sono quelli della tabella utenti di Nextcloud, e le tabelle dell'applicazione stanno accanto alle sue.
- **Nextcloud** gestisce le identità e lo spazio file degli studi. L'integrazione si appoggia ad app Nextcloud **non incluse in questa pubblicazione**: l'handshake JWT di *External Sites* e due app di supporto, una per il controllo degli accessi e una per la creazione degli utenti.
- **dompdf** per i PDF, **ClamAV** per la scansione dei caricamenti, SMTP per le email.
- Pensata per girare in Docker dietro un reverse proxy.

Alcuni identificatori tecnici (prefissi delle tabelle, classi CSS, una classe di notifica) mantengono il prefisso interno originale `enamel`. Sono nomi dentro il codice, non riferimenti a un servizio attivo.

### Usarlo senza Nextcloud

La logica di ordini, documenti, MDR e GDPR non dipende da Nextcloud: tutti i dati propri dell'applicazione stanno in tabelle sue (`oc_enamel_*`). Il legame è concentrato in pochi punti:

- **Accesso.** Un unico middleware (`NextcloudAuthBridge`) accetta il token firmato che arriva da Nextcloud. Non esiste un login proprio.
- **Utenti.** Il modello `User` legge la tabella utenti di Nextcloud (`oc_users`). Due migrazioni vi fanno riferimento, e alcuni controller e modelli leggono dalle tabelle di Nextcloud i dati degli utenti (per esempio il nome visualizzato).
- **Quota di spazio.** Il livello di spazio di uno studio viene scritto nelle preferenze di Nextcloud (`StudioController`, `ResetStudioCredits`).
- **Registrazione.** La creazione degli account e il controllo di accesso passano da app di supporto di Nextcloud (`ProvisioningController`, `GateController`).
- **Visualizzazione.** Un middleware permette di mostrare l'applicazione dentro Nextcloud, in un iframe (`AllowNextcloudFrame`).

Sostituendo questi punti Order Manager diventerebbe autonomo. Servirebbero il login nativo di Laravel oppure un provider OpenID Connect (Keycloak, Authentik, Microsoft Entra, Google…), una tabella utenti propria, e la rimozione o l'adattamento dei canali di quota e di creazione degli account. Questo adattamento **non** è stato fatto né provato in questa pubblicazione.

---

## Installazione

> Order Manager non è un'applicazione autonoma: richiede un'istanza Nextcloud e le app di supporto descritte sopra. Questa pubblicazione è pensata prima di tutto per essere **letta e studiata**. Per farla funzionare da capo a fondo bisogna ricostruire quell'ambiente.

```bash
composer install --no-dev
cp .env.example .env
php artisan key:generate
# modificare il .env, poi:
php artisan config:cache
php artisan migrate
```

Le migrazioni presuppongono che lo schema di Nextcloud sia già presente nello stesso database.

Il file `.env.example` documenta ogni impostazione. Queste, nella pratica, non possono restare vuote:

| Variabile | Se resta vuota |
|---|---|
| `NEXTCLOUD_URL` | i link e i pulsanti nelle email non portano da nessuna parte |
| `NEXTCLOUD_SSO_PUBKEY_B64` | **nessuno può accedere** (chiusura per default) |
| `NC_PROVISIONING_PUBKEY_B64` | la creazione degli utenti è disattivata (chiusura per default) |
| `LAB_MANUFACTURER_NAME`, `LAB_EMAIL` | i testi legali mostrano campi vuoti |
| `LANDING_URL`, `LANDING_ORIGINS` | il form di iscrizione della pagina pubblica viene rifiutato |
| `LEGAL_VERSION` | deve coincidere con la versione dichiarata nei testi legali |

Le due chiavi pubbliche sono file PEM codificati in base64 su una sola riga (`base64 -w0 chiave.pem`).

---

## Limiti noti

- **La cancellazione GDPR è distribuita disattivata** (`ProcessGdprDeletions`, `ENABLED = false`). Prima della pubblicazione è stata corretta perché usi i dischi di archiviazione giusti e verifichi la cancellazione prima di registrarla. Però individua ancora la cartella di un ordine dal campo `directory_name` salvato, che può non coincidere con il nome reale della cartella su disco (`Order::resolveDirectoryOnDisk()` non viene usato). Va rivista prima di attivarla.
- **Le clausole vessatorie** (artt. 1341–1342 c.c.) non si possono scaricare dal profilo utente.
- **Non c'è una suite di test automatici.** `tests/` contiene solo i file di esempio di Laravel.
- **Le dipendenze sono più recenti di quelle usate in produzione.** Sono state aggiornate per chiudere tutti gli avvisi di sicurezza noti. La pubblicazione è stata verificata come descritto sotto, ma questa combinazione esatta non ha girato in produzione.
- **La libreria JWT è inclusa nel codice** (`app/Support/Firebase/JWT/`, con namespace riscritto). Composer segnala che queste classi non seguono lo standard PSR-4 e le salta; il middleware di autenticazione le carica con un proprio autoloader.
- **Presupposti sull'infrastruttura:** i proxy fidati sono impostati sulla sottorete Docker `172.18.0.0/16` in `bootstrap/app.php`, e i dischi di archiviazione mantengono i nomi dell'installazione originale. I loro percorsi sono configurabili.
- **I testi legali sono scritti per il diritto italiano.** Sono modelli, non consulenza legale: vanno fatti rivedere prima dell'uso.

---

## Come è stata preparata questa pubblicazione

Prima della pubblicazione il codice ha seguito una revisione strutturata:

- sono stati tolti l'identità del laboratorio, i nomi degli host, i dati personali, le credenziali e le chiavi scritte nel codice, e ogni valore legato a chi gestisce l'applicazione è passato in configurazione;
- sono stati tolti l'integrazione dei pagamenti e il listino;
- è stato rimosso il codice morto, e sono stati corretti alcuni difetti preesistenti trovati lungo la strada: un modello PDF che non compilava, una route verso un metodo inesistente, i percorsi di archiviazione del comando di cancellazione GDPR.

Lo stato finale è stato verificato in un container pulito:
- sintassi di ogni file PHP;
- ogni route collegata a un metodo esistente;
- compilazione di ogni view Blade;
- generazione di ogni modello di email;
- produzione dei PDF;
- resa dei quattro testi legali, con tutti i segnaposto risolti;
- controllo dei token di accesso: accettati con la chiave giusta, rifiutati con una chiave sbagliata, rifiutati quando nessuna chiave è configurata.

`composer audit` non segnala vulnerabilità note.

---

## Autore

Progettato e diretto da **Lorenzo Ianniello**.

Il codice è stato sviluppato con **Claude Code** (Anthropic) sotto la sua direzione. Suoi sono i requisiti, l'architettura, la progettazione regolatoria e di privacy, e la verifica del risultato.

---

## Licenza

Distribuito con licenza **GNU Affero General Public License v3.0 o successiva**: vedi [`LICENSE`](LICENSE). Chi fa girare una versione modificata come servizio di rete è tenuto, secondo l'AGPL, a renderne disponibile il codice sorgente ai suoi utenti.

Il materiale di terzi incluso nel repository (la libreria JWT, la famiglia di caratteri Ubuntu, lo skeleton di Laravel) mantiene la propria licenza: vedi [`THIRD-PARTY-NOTICES.md`](THIRD-PARTY-NOTICES.md).
