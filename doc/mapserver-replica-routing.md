# Routing automatico primary / replica per MapServer

Le richieste OGC di sola lettura vengono servite da una replica read-only,
mentre editing e transazioni restano sul primary. La classificazione è dedotta
dalla richiesta stessa: non c'è nessuna lista di operazioni da mantenere
allineata in configurazione.

Senza le variabili `MAP_DB_*` non cambia assolutamente nulla.

## Variabili

| Variabile | Significato | Fallback |
| --- | --- | --- |
| `MAP_DB_HOST` | host PostgreSQL read-only usato da MapServer | `DB_HOST` |
| `MAP_DB_NAME` | database del deployment usato da MapServer | `DB_NAME` |

Le credenziali restano quelle già configurate (`MAP_USER` / `MAP_PASSWORD`, con
fallback su `DB_USER` / `DB_PASSWORD`): il routing sostituisce solo l'endpoint,
nessuna credenziale è scritta nel codice.

## Come vengono classificate le richieste

Vanno alla **replica**:

| Servizio | Operazione |
| --- | --- |
| WMS | GetMap, GetFeatureInfo, GetCapabilities |
| WFS | GetFeature, DescribeFeatureType, GetCapabilities |

Restano sul **primary**:

- WFS Transaction
- HTTP PUT, DELETE, PATCH
- POST con `GC_EDITMODE` nella query string
- `gcRequestType=OLWFS`
- **qualsiasi richiesta non riconosciuta o ambigua**

L'ultimo punto è la scelta di fondo: una lettura mandata per errore sul primary
costa solo il mancato alleggerimento, una scrittura mandata per errore su una
replica fallisce. Per lo stesso motivo `GetLegendGraphic` e `DescribeLayer`,
pur essendo read-only, non sono nell'insieme instradato — aggiungerli è una
riga in `OgcRequestClassifier::READ_ONLY_OPERATIONS` se in futuro lo si vuole.

## Dove agisce il routing

In `MsMapObjFactory::from()`, cioè il punto da cui passano entrambi gli entry
point OGC (`ows.php` e `owsgw.php`). La riscrittura è applicata a **tutti** i
layer PostGIS, non solo a quelli con `SETROLE`: un layer senza RLS
continuerebbe altrimenti a usare la connessione statica scritta nel mapfile.

`MsMapObjFactoryDecorator` ricostruisce da zero la connessione dei layer RLS e
annullerebbe il routing, quindi ricalcola la stessa decisione e la rispetta.
`SETROLE` e l'autenticazione non sono toccati.

## Mapfile: host e database hanno ruoli diversi

- **`MAP_DB_NAME` viene scritto nel mapfile.** Non è routing ma correttezza del
  deployment: se `catalog_path` porta con sé il nome di un altro database (un
  catalogo copiato da produzione conserva `app` anche su `staging`), il
  deployment ha la precedenza e il mapfile punta a `app_staging`.
- **`MAP_DB_HOST` non viene scritto nel mapfile.** Il mapfile su disco resta
  sul primary e lo scambio di host avviene a runtime, per richiesta.

Questa separazione è deliberata: il ramo OLWFS di `ows.php` inoltra la
richiesta al CGI `mapserv` passando il percorso del mapfile, quindi legge il
file senza passare da PHP. Con il primary come default su disco quel percorso
resta corretto senza bisogno di casi speciali.

## Esempio di configurazione per staging

```bash
# ambiente del deployment staging
DB_HOST=pg-cluster-rw
DB_DBNAME=app_staging          # database del deployment: scritture e catalogo Author
DB_USER=gisclient
DB_PASSWORD=...

MAP_USER=mapserver
MAP_PASSWORD=...

# nuove: lettura OGC servita dalla replica
MAP_DB_HOST=pg-cluster-ro
MAP_DB_NAME=app_staging
```

Su Apache le due variabili vanno esposte a PHP come le altre, in
`docker/frontend/location.conf` (`PassEnv MAP_DB_HOST`, `PassEnv MAP_DB_NAME`).

## Procedura di test con staging

```bash
# 1. rigenerare i mapfile e verificare il database
kubectl -n staging exec $POD -c author-backend -- \
  php /app/author/bin/console gisclient:refresh-mapfile
kubectl -n staging exec $POD -c author-backend -- \
  sh -c 'grep -m1 -o "dbname=[^ ]* host=[^ ]*" /app/author/map/*/*.map'
# atteso: dbname=app_staging host=pg-cluster-rw   (host primary sul file, corretto)

# 2. verificare il routing a runtime, richiesta per richiesta
kubectl -n staging exec $POD -c author-backend -- php -r '
$_SERVER["REQUEST_METHOD"]="GET"; $_REQUEST=[];
require "/app/author/bootstrap.php";
foreach ([["WMS","GetMap"],["WFS","GetFeature"],["WFS","Transaction"]] as [$s,$op]) {
  $r = ms_newOwsrequestObj();
  $r->setParameter("project","<progetto>"); $r->setParameter("map","<mapset>");
  $r->setParameter("service",$s); $r->setParameter("request",$op);
  $m = \GCApp::getMsMapObjFactory()->from($r);
  printf("%-22s %s\n", "$s $op",
    \GisClient\MapServer\Connection\ConnectionString::redact($m->getLayer(0)->connection));
}'
# atteso: GetMap e GetFeature su MAP_DB_HOST, Transaction su DB_HOST
```

Con `APP_DEBUG=true` ogni richiesta lascia in `kubectl logs` una riga della
categoria `ows` con host e database scelti. La password non viene mai stampata:
i log passano da `ConnectionString::redact()`.

## Rollback

Rimuovere `MAP_DB_HOST` e `MAP_DB_NAME` dall'ambiente e riavviare il pod.
Entrambe ricadono su `DB_HOST` / `DB_NAME` e il routing si disattiva
(`MAP_DB_ROUTING` diventa falso), quindi non serve rigenerare i mapfile per
tornare al comportamento precedente.

Se erano stati rigenerati con `MAP_DB_NAME` attivo e il database indicato da
`catalog_path` era diverso, un `gisclient:refresh-mapfile` dopo la rimozione
riporta il mapfile al valore di `catalog_path`.

## Casi non intercettabili

Percorsi che non passano da `MsMapObjFactory` e quindi **non** vengono
instradati — restano tutti sul primary, che è il comportamento voluto:

- **OLWFS** (`ows.php:25`, `owsgw.php:24`): inoltrato al CGI `mapserv`, che
  legge il mapfile da disco. È anche il motivo per cui l'host nel mapfile resta
  il primary.
- **PUT / DELETE / POST con `GC_EDITMODE`** (`ows.php:18`): dirottati su
  `include/putrequest.php` con `exit(0)` prima che il mapObj venga creato.
  Nota: quel file non esiste in questo repository, sta nell'immagine R3-GIS.
- **Editing applicativo**: `public/services/saveEdit.php` e il datamanager
  usano `GCApp::getDataDB()`, connessione separata su `DB_HOST`.
- **TinyOWS**: ha una propria configurazione XML con `DB_USER` / `DB_HOST`,
  generata dal writer del mapfile e indipendente da queste variabili.
- **Catalogo Author**: `GCApp::getDB()` non è toccato in alcun modo.
- **MapProxy**: serve tile dalla propria cache o via HTTP verso `ows.php`; in
  quest'ultimo caso la richiesta è una GetMap normale e viene instradata.

## Nota sulla replica

La replica deve essere fisica (streaming): i ruoli e le policy RLS sono
replicati con il cluster, quindi `SET ROLE` continua a funzionare sullo
standby. Con replica logica ruoli e policy non verrebbero replicati.
