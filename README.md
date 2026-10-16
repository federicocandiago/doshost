# DOSHost

Hosting self-hosted di macchine DOS nel browser: PHP 8 + MySQL, Bootstrap, `fetch`,
DOSBox via [js-dos](https://js-dos.com) (WebAssembly, servito in locale). Nessuna dipendenza Composer/npm a runtime.

## Avvio rapido

```bash
cp .env.example .env        # poi modifica DB_* e ADMIN_*
php bin/migrate.php         # facoltativo: il sito crea tabelle e admin da solo alla prima visita
./serve.sh                  # http://localhost:8000
```

`php bin/migrate.php --fresh` cancella tutte le tabelle e rifà le migrazioni.
Se MySQL non risponde, sia lo script sia il sito mostrano un messaggio chiaro (`DatabaseException`).

## Struttura

```
app/Core/         mini framework: Env, Database, Router, Request, Response, View,
                  Session, Auth, Csrf, Middleware, Translator, Migrator
app/Models/       User, Instance, Setting (active record minimale)
app/Services/     DiskService (dischi .jsdos), AvatarService (immagini profilo)
app/Controllers/  Home, Page, Auth, Dashboard, Instance, Account, Admin
routes/web.php    tutte le rotte con i loro middleware (auth, guest, admin)
views/            layouts/, components/, pages/   (PHP puro, stile Blade-like)
lang/             messages.pot + {en,it}/LC_MESSAGES/messages.po
database/migrations/
public/           index.php, assets/, vendor/ (bootstrap, js-dos)
storage/          disks/{uuid}/bundle.jsdos, avatars/, logs/
```

Una pagina: `return view('pages/dashboard', [...])`. Un componente: `<?= component('instance-card', [...]) ?>`.
I form con `data-ajax` vengono inviati con `fetch` e ricevono `{ok, message, errors, redirect}`;
i bottoni con `data-confirm` + `data-action` passano dalla modale di conferma.

## Come funziona il disco

Ogni istanza è un bundle js-dos (`storage/disks/{uuid}/bundle.jsdos`): zip con `.jsdos/dosbox.conf`
più tutto il contenuto di `C:`. Gli zip caricati alla creazione vengono estratti in `C:`.
Il browser invia l'intero disco ogni `AUTOSAVE_MINUTES`, con «Salva», quando la scheda viene nascosta
e con «Salva ed esci» (un hash evita upload inutili). Il file precedente resta in `bundle.jsdos.bak`.

Il peso dell'istanza è la dimensione del disco compresso: oltre `DISK_WARN_PERCENT` compare un avviso,
oltre `DISK_MAX_MB` il salvataggio viene rifiutato finché non si cancellano dati.
La memoria RAM dell'emulatore non viene salvata: si conserva il disco, non lo stato del programma in esecuzione.

## Traduzioni

Le stringhe sono in inglese nel codice: `__('Hello, :name', ['name' => $n])`.

```bash
php bin/i18n.php   # rigenera lang/messages.pot e aggiunge le stringhe nuove ai .po
```

I `.po` si modificano con un editor o con Poedit, e non serve compilarli in `.mo`.

## Hosting condiviso (Apache + PHP + MySQL)

Requisiti: **PHP 8.1+** con le estensioni `pdo_mysql`, `zip`, `gd`, `mbstring`; MySQL 5.7+ / MariaDB 10.3+;
Apache con `mod_rewrite`. Funziona nella radice di un dominio (`https://dos.tuodominio.it`) o in una
sottocartella (`https://tuodominio.it/doshost`): il percorso viene preso da `APP_URL`.

1. **Carica i file** (FTP o file manager) mantenendo la struttura del progetto.
2. **Cartella pubblica** — in ordine di sicurezza:
   - *progetto fuori dalla cartella web* (es. Tophost, cPanel): il progetto va nella home, accanto
     alla cartella web, e nella cartella web va **solo il contenuto di `public/`**.
     `index.php` trova da solo il progetto in `../doshost`:
     ```
     home/
       doshost/     ← progetto completo (app, storage, .env…): nessun URL
       htdocs/      ← contenuto di doshost/public (index.php, assets, vendor, .htaccess, .user.ini)
     ```
   - *document root su `public/`*, se il pannello lo permette;
   - *ultima scelta*: progetto intero nella cartella web; il `.htaccess` in radice inoltra
     ogni richiesta a `public/` e blocca `.env`, `app/`, `storage/`, ecc.

   I dati (dischi, avatar, log) stanno in `storage/` del progetto; con `STORAGE_PATH` nel `.env`
   puoi spostarli altrove, con percorso assoluto o relativo al progetto (es. `../doshost-data`).
   Non devono mai stare dentro la cartella web.
3. **Database** — crea database e utente dal pannello e scrivili nel `.env`. Non serve altro:
   alla **prima visita** il sito crea da solo le tabelle (da `database/migrations/*.sql`) e l'admin
   indicato in `ADMIN_EMAIL` / `ADMIN_PASSWORD`. Con SSH puoi farlo anche con `php bin/migrate.php`;
   in alternativa il file `.sql` si può importare così com'è da phpMyAdmin.
4. **`.env`** — copia `.env.example` in `.env` e imposta almeno: `APP_URL` (con https), `APP_DEBUG=false`,
   `DB_*`, `ADMIN_*`, `LEGAL_*`.
5. **Permessi** — `storage/disks`, `storage/avatars` e `storage/logs` devono essere scrivibili da PHP (di solito 755 o 775).
6. **Limiti di upload** — `public/.user.ini` alza `post_max_size` / `upload_max_filesize` a 80M (valido con PHP-FPM/CGI);
   se l'hosting usa un altro sistema, imposta valori più alti di `DISK_MAX_MB` dal pannello PHP.
7. **HTTPS** attivo (Let's Encrypt dal pannello): serve al cookie di sessione e a ciò che dichiarano le policy.

Verifica: apri `https://tuodominio/` (home), `https://tuodominio/.env` e `https://tuodominio/storage/logs/app.log` (devono dare 404/403)
e `https://tuodominio/vendor/js-dos/emulators/wdosbox.wasm` (deve scaricarsi come `application/wasm`).

Se qualcosa non va:
- **errore 500 subito**: alcuni hosting non permettono `Options` nei `.htaccess` → togli le righe `Options -Indexes`;
  se ancora 500, togli il commento a `RewriteBase` nei due `.htaccess` (radice del progetto e `public/`);
- **pagine 404 ma la home si vede**: controlla che `APP_URL` corrisponda esattamente all'indirizzo (con la sottocartella);
- **pagina bianca o errore PHP**: con `APP_DEBUG=true` l'errore viene mostrato; serve PHP 8.1 o superiore;
- **file `.htaccess` mancanti**: molti client FTP non mostrano i file che iniziano con il punto: verificali dal file manager.

Per aggiornare: carica i file nuovi senza toccare `.env` e `storage/`. Le modifiche al database vanno in
nuovi file `database/migrations/AAAA_MM_GG_NNNNNN_descrizione.sql`: il sito li applica da solo, una volta
ciascuno, alla prima richiesta che trova una tabella o una colonna mancante (o con `php bin/migrate.php`).

## Produzione

- Punta il document root su `public/` (c'è `.htaccess` per Apache; con nginx usa `try_files $uri /index.php`).
  Senza accesso al document root vedi «Hosting condiviso» sopra.
- Servi i `.wasm` come `application/wasm`.
- Alza `post_max_size` / `upload_max_filesize` oltre `DISK_MAX_MB` (vedi `public/.user.ini`).
- `APP_DEBUG=false`, HTTPS, e rendi scrivibile `storage/`.

## Librerie incluse

Servite in locale da `public/vendor/`, senza CDN:

- [js-dos](https://js-dos.com) 8.4.1 (DOSBox / DOSBox-X compilati in WebAssembly) — GPL-2.0
- [Bootstrap](https://getbootstrap.com) 5.3.3 — MIT
