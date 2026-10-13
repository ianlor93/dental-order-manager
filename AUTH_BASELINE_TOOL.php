<?php
/**
 * AUTH_BASELINE_TOOL.php — "foto del prima" (RETE DI SICUREZZA autorizzazioni OM)
 * ---------------------------------------------------------------------------------
 * Strumento USA-E-GETTA, additivo, READ-ONLY. NON modifica alcun file esistente,
 * NON crea/modifica/cancella dati di business. Registra, per ogni (utente x rotta GET):
 *   - status HTTP
 *   - SHA-256 dell'HTML renderizzato (token CSRF normalizzato => niente falsi diff)
 *
 * A cosa serve: baseline PRIMA del refactor permessi (AccessContext). Si rifà la stessa
 * foto DOPO e si diffa: zero differenze non volute = niente rotto.
 *
 * GARANZIE DI SICUREZZA:
 *   - SOLO GET. Le rotte che mutano (POST/PUT/PATCH/DELETE) non vengono toccate.
 *   - session/cache/mail forzati driver 'array' => nessuna scrittura infrastrutturale.
 *   - OGNI richiesta e' avvolta in una transazione DB con ROLLBACK garantito (rete di
 *     sicurezza: anche una scrittura DB imprevista viene annullata => 0 modifiche nette).
 *   - Le rotte GET con effetti collaterali IRREVERSIBILI/ESTERNI (ZIP/PDF su
 *     disco, ArchiveAccess audit, firstOrCreate, backfill lazy) sono SALTATE e ELENCATE.
 *
 * AUTENTICAZIONE simulata: setUser() in-memory sul guard 'web'. Il NextcloudAuthBridge
 * vede Auth::check()=true e, in assenza di JWT in query, prosegue (nessuna riconvalida).
 *
 * OUTPUT: JSON su STDOUT. Diagnostica su STDERR.
 *
 * USO (dentro il container OM, una riga):
 *   docker exec -w /app <container> php AUTH_BASELINE_TOOL.php > ./AUTH-BASELINE-before.json
 */

use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/* --------------------------------------------------------------------------- */
/* Bootstrap Laravel (l'app vive accanto a questo file: /app)                    */
/* --------------------------------------------------------------------------- */
require __DIR__ . '/vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app    = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(HttpKernel::class);

// Bind di una request-segnaposto PRIMA del bootstrap: in un giro HTTP reale e' handle()
// a bindare la request prima di bootstrappare; qui bootstrappiamo "a secco", e
// AppServiceProvider::boot() chiama URL::forceScheme() che costruisce UrlGenerator con
// $app['request'] — null a bootstrap-time => TypeError. Ogni handle() la ri-bindera'.
$app->instance('request', Request::create('https://localhost/', 'GET'));

$kernel->bootstrap();

// Neutralizza ogni scrittura infrastrutturale (tutto in-memory, niente DB/file).
// NB: queue lasciata al default (database): un eventuale dispatch imprevisto finirebbe
// come riga 'jobs' -> comunque annullata dal ROLLBACK per-richiesta. mail=array => nessun invio.
config([
    'session.driver' => 'array',
    'cache.default'  => 'array',
    'mail.default'   => 'array',
]);

function err(string $m): void { fwrite(STDERR, $m . "\n"); }

/* --------------------------------------------------------------------------- */
/* Helper                                                                       */
/* --------------------------------------------------------------------------- */

/** Estrae il body della risposta (gestisce anche risposte "streamed"). */
function grab_content($response): string
{
    $c = $response->getContent();
    if ($c === false) {
        ob_start();
        try { $response->sendContent(); } catch (\Throwable $e) { /* no-op */ }
        $c = ob_get_clean();
    }
    return (string) $c;
}

/** Normalizza i token CSRF (volatili) prima dell'hash: evita falsi diff before/after. */
function normalize_content(string $content, ?string $token): string
{
    if ($token !== null && $token !== '') {
        $content = str_replace($token, '__CSRF__', $content);
    }
    $content = preg_replace('/(name="_token"\s+value=")[^"]*(")/', '${1}__CSRF__${2}', $content);
    $content = preg_replace('/(<meta name="csrf-token" content=")[^"]*(">)/', '${1}__CSRF__${2}', $content);
    return $content;
}

/** Costruisce l'URI concreto sostituendo i parametri di rotta. */
function concrete_uri($route, array $params): string
{
    $uri = $route->uri();
    foreach ($params as $k => $val) {
        $uri = str_replace(['{' . $k . '}', '{' . $k . '?}'], rawurlencode((string) $val), $uri);
    }
    $uri = preg_replace('/\{[^}]+\?\}/', '', $uri); // rimuove eventuali opzionali residui
    $uri = '/' . ltrim($uri, '/');
    $uri = rtrim($uri, '/');
    return $uri === '' ? '/' : $uri;
}

/**
 * Genera le "varianti" (mappe di parametri) per una rotta.
 * Le rotte incluse hanno al massimo UN parametro: {order} (2 varianti), {step}, {vat}.
 * Qualunque altro parametro => variante "unresolved" (verra' registrata come NON testata).
 */
function build_variants(array $paramNames, ?string $orderA, ?string $orderB): array
{
    if (empty($paramNames)) {
        return [['label' => '', 'params' => []]];
    }
    if (count($paramNames) > 1) {
        return [['unresolved' => 'rotta multi-parametro non risolvibile in sola lettura: {' . implode('},{', $paramNames) . '}']];
    }
    $p = $paramNames[0];
    if ($p === 'order') {
        $v = [];
        if ($orderA) $v[] = ['label' => 'studioA', 'params' => ['order' => $orderA]];
        if ($orderB) $v[] = ['label' => 'studioB', 'params' => ['order' => $orderB]];
        return $v ?: [['unresolved' => 'nessun ordine reale disponibile per {order}']];
    }
    if ($p === 'step') return [['label' => '', 'params' => ['step' => '1']]];
    if ($p === 'vat')  return [['label' => '', 'params' => ['vat' => '00000000000']]];
    return [['unresolved' => 'parametro {' . $p . '} non risolvibile in sola lettura']];
}

/** Esegue UNA cella (utente x uri): actingAs in-memory, transazione con rollback, cattura status+hash. */
function test_cell($app, $kernel, $userModel, string $uri, array $extraQuery): array
{
    $server = [
        'HTTPS'       => 'on',
        'HTTP_HOST'   => parse_url(config('app.url'), PHP_URL_HOST),
        'SERVER_NAME' => parse_url(config('app.url'), PHP_URL_HOST),
        'SERVER_PORT' => '443',
        'REMOTE_ADDR' => '127.0.0.1',
    ];
    $request = Request::create($uri, 'GET', $extraQuery, [], [], $server);

    // actingAs in-memory: il bridge vede Auth::check()=true (nessun JWT => prosegue).
    // Riuso il guard 'web' cached (creato al 1o giro) e ne aggiorno solo l'utente:
    // setUser() imposta $this->user + loggedOut=false; il guard non consulta la sessione.
    $app['auth']->shouldUse('web');
    $app['auth']->guard('web')->setUser($userModel);

    $status = null; $hash = null; $note = null; $token = null;

    DB::beginTransaction();
    try {
        $response = $kernel->handle($request);
        $status   = $response->getStatusCode();
        try { if ($request->hasSession()) { $token = $request->session()->token(); } } catch (\Throwable $e) { /* no session (rotte api) */ }
        $content  = normalize_content(grab_content($response), $token);
        $hash     = hash('sha256', $content);
    } catch (\Throwable $e) {
        $status = -1;
        $note   = 'EXCEPTION: ' . substr($e->getMessage(), 0, 200);
    } finally {
        try { DB::rollBack(); } catch (\Throwable $e) { /* no-op */ }
    }

    $cell = ['status' => $status, 'htmlHash' => $hash];
    if ($note !== null) { $cell['note'] = $note; }
    return $cell;
}

/* --------------------------------------------------------------------------- */
/* SKIP-LIST — rotte GET con effetti collaterali / output non deterministico     */
/* --------------------------------------------------------------------------- */
$skipByName = [
    'gdpr.export'                     => 'Scrive uno ZIP FISICO in storage/app/private/gdpr_exports + PDF; output non deterministico; throttle:critical.',
    'orders.downloadZip'              => 'ArchiveAccess audit (ordini archiviati) + scrive/OVERWRITE un .zip FISICO su disco; binario non deterministico.',
    'orders.downloadRequestPdf'       => 'ArchiveAccess audit + PDF dompdf non deterministico. Abilita OrderPolicy::view coperta da orders.prescription.preview.',
    'orders.downloadPrescription'     => 'ArchiveAccess audit + PDF non deterministico. view coperta da orders.prescription.preview.',
    'orders.downloadProductionReport' => 'ArchiveAccess audit + PDF non deterministico. view coperta da orders.prescription.preview.',
    'orders.downloadLabel'            => 'ArchiveAccess audit + PDF non deterministico. view coperta da orders.prescription.preview.',
    'orders.downloadConformity'       => 'ArchiveAccess audit + PDF non deterministico. view coperta da orders.prescription.preview.',
    'orders.downloadPatientInfo'      => 'ArchiveAccess audit + PDF non deterministico. view coperta da orders.prescription.preview.',
    'orders.downloadPmsReport'        => 'PDF dompdf non deterministico (hash inutile). view coperta da orders.prescription.preview.',
    'orders.production.create'        => 'firstOrCreate() INSERISCE una riga OrderProduction => effetto collaterale su GET.',
    'profile.contract'                => 'PDF dompdf non deterministico (hash inutile).',
    'logistics.download'              => 'PDF DDT dompdf non deterministico (hash inutile).',
    'dashboard.archive'               => 'Backfill lazy su GET: imposta pms_next_review_at/pms_status + $order->save() sugli ordini archiviati privi del dato => effetto collaterale. Scope di lista equivalente coperto da dashboard (/lab) via scopedOrders().',
];
$skipByUri = [
    'debug-auth' => 'Corpo JSON contiene session_id volatile => hash non deterministico; rotta di debug staff-only.',
];
// Rotte a cui passare parametri Resumable fittizi (il ramo GET fa solo File::exists()).
$resumableRoutes = ['orders.chunk', 'orders.supplemental'];

/* --------------------------------------------------------------------------- */
/* Fixtures: utenti reali + ordini rappresentativi (studioA con piu' ordini,     */
/* studioB di un altro studio) => cattura ownsOrder / cross-studio.              */
/* --------------------------------------------------------------------------- */
try {
    $userRows = DB::table('oc_users')->orderBy('uid')->get(['uid', 'displayname']);
} catch (\Throwable $e) {
    err('FATAL: impossibile leggere oc_users: ' . $e->getMessage());
    exit(2);
}

$users = [];
foreach ($userRows as $r) {
    $model = \App\Models\User::where('uid', $r->uid)->first();
    if ($model) {
        $users[] = ['uid' => $r->uid, 'displayname' => $r->displayname, 'model' => $model];
    }
}
err('Utenti reali trovati (oc_users): ' . count($users));

// IGIENE 2026-08-07 — Fixtures HARD-PINNED agli identificatori esatti del golden
// before.json. Prima si sceglieva "uno studio con N ordini": fra studi a PARI
// conteggio il tie-break SQL non e' deterministico => studioB "ballava" fra
// QCENT8 (studio 6) e HLG0VC (studio 4), sporcando i gate. Ora valori fissi con
// controllo d'esistenza FAIL-CLOSED: se un pin sparisce lo strumento si FERMA
// (mai ripiegare in silenzio su un altro ordine — era la causa del wobble).
$pinned = [
    'studioA' => ['studio_id' => 3, 'order_code' => 'YJGUAP'],
    'studioB' => ['studio_id' => 6, 'order_code' => 'QCENT8'],
];

$fixtures = ['studioA' => null, 'studioB' => null];
foreach ($pinned as $label => $pin) {
    $row = DB::table('oc_enamel_orders')->whereNull('deleted_at')
        ->where('studio_id', $pin['studio_id'])
        ->where('code', $pin['order_code'])
        ->first(['id']);
    if ($row === null) {
        err('FATAL: fixture pinnata ASSENTE => ' . $label . ' = studio ' . $pin['studio_id']
            . ' / ordine ' . $pin['order_code'] . ' (soft-deleted, spostato, o studio rimosso). '
            . 'Lo strumento NON ripiega su un altro ordine (sarebbe il wobble): '
            . 'aggiorna il pin dopo aver verificato il dato, poi ri-scatta.');
        exit(4);
    }
    // order_count e' solo informativo per il meta (non piu' criterio di scelta).
    $c = DB::table('oc_enamel_orders')->whereNull('deleted_at')
        ->where('studio_id', $pin['studio_id'])->count();
    $fixtures[$label] = ['studio_id' => (int) $pin['studio_id'], 'order_count' => (int) $c, 'order_code' => $pin['order_code']];
}
$orderA = $fixtures['studioA']['order_code'];
$orderB = $fixtures['studioB']['order_code'];
err('Fixture ordini (HARD-PINNED): studioA=' . json_encode($fixtures['studioA']) . ' studioB=' . json_encode($fixtures['studioB']));

/* --------------------------------------------------------------------------- */
/* Piano: enumera TUTTE le rotte GET, applica skip-list, risolve i parametri.    */
/* --------------------------------------------------------------------------- */
// Warm-up: garantisce il caricamento del route file (withRouting puo' caricarle
// pigramente). GET /up (health) e' pubblico e senza effetti collaterali.
try {
    $kernel->handle(Request::create('/up', 'GET', [], [], [], ['HTTPS' => 'on', 'HTTP_HOST' => parse_url(config('app.url'), PHP_URL_HOST)]));
} catch (\Throwable $e) { /* no-op */ }

$getRoutes = [];
foreach ($app['router']->getRoutes() as $route) {
    if (in_array('GET', $route->methods(), true)) {
        $getRoutes[] = $route;
    }
}
if (count($getRoutes) === 0) {
    err('FATAL: nessuna rotta GET trovata dal router (route file non caricato).');
    exit(3);
}
usort($getRoutes, fn($x, $y) => strcmp($x->uri(), $y->uri()));

$plan    = [];   // celle da testare: ['key'=>, 'uri'=>, 'query'=>]
$skipped = [];   // rotte saltate/non testate con motivo

foreach ($getRoutes as $route) {
    $name = $route->getName();
    $uri  = $route->uri();

    $reason = null;
    if ($name !== null && isset($skipByName[$name])) {
        $reason = $skipByName[$name];
    } elseif (isset($skipByUri[$uri])) {
        $reason = $skipByUri[$uri];
    }
    if ($reason !== null) {
        $skipped[] = ['route' => $name ?: '(unnamed)', 'uri' => '/' . $uri, 'methods' => implode('|', $route->methods()), 'reason' => $reason];
        continue;
    }

    foreach (build_variants($route->parameterNames(), $orderA, $orderB) as $var) {
        if (isset($var['unresolved'])) {
            $skipped[] = ['route' => $name ?: '(unnamed)', 'uri' => '/' . $uri, 'reason' => 'NON TESTATA: ' . $var['unresolved']];
            continue;
        }
        $curi  = concrete_uri($route, $var['params']);
        $query = [];
        if ($name !== null && in_array($name, $resumableRoutes, true)) {
            $query = ['resumableChunkNumber' => '1', 'resumableIdentifier' => 'baseline-probe', 'resumableFilename' => 'probe.stl', 'resumableTotalChunks' => '1'];
        }
        $label = $var['label'] !== '' ? ' (' . $var['label'] . ')' : '';
        $plan[] = ['key' => 'GET ' . $curi . $label, 'uri' => $curi, 'query' => $query];
    }
}
err('Rotte GET totali: ' . count($getRoutes) . ' | celle/utente: ' . count($plan) . ' | rotte saltate/non testate: ' . count($skipped));

/* --------------------------------------------------------------------------- */
/* Esecuzione: 13 utenti x piano                                                 */
/* --------------------------------------------------------------------------- */
$baseline = [];
$summary  = [];

foreach ($users as $ud) {
    $uid = $ud['uid'];
    $baseline[$uid] = [];
    $summary[$uid]  = ['displayname' => $ud['displayname'], '200' => 0, '302' => 0, '403' => 0, 'other' => []];

    foreach ($plan as $cellPlan) {
        $cell = test_cell($app, $kernel, $ud['model'], $cellPlan['uri'], $cellPlan['query']);
        $baseline[$uid][$cellPlan['key']] = $cell;

        $s = $cell['status'];
        if ($s === 200)      { $summary[$uid]['200']++; }
        elseif ($s === 302)  { $summary[$uid]['302']++; }
        elseif ($s === 403)  { $summary[$uid]['403']++; }
        else {
            $k = (string) $s;
            $summary[$uid]['other'][$k] = ($summary[$uid]['other'][$k] ?? 0) + 1;
        }
    }
    err('  utente ' . $uid . ' -> ' . count($plan) . ' celle');
}

/* --------------------------------------------------------------------------- */
/* Output                                                                        */
/* --------------------------------------------------------------------------- */
$cellsPerUser = count($plan);
$defaultConn  = config('database.default');

$out = [
    'meta' => [
        'tool'                   => 'AUTH_BASELINE_TOOL.php',
        'purpose'                => 'Baseline READ-ONLY autorizzazioni OrderManager (foto del PRIMA) — status + hash HTML per (utente x rotta GET).',
        'generated_at'           => date('c'),
        'app_env'                => $app->environment(),
        'app_debug'              => (bool) config('app.debug'),
        'legal_version'          => config('legal.version'),
        'db_connection_default'  => $defaultConn,
        'db_database'            => config('database.connections.' . $defaultConn . '.database'),
        'forced_drivers'         => ['session' => config('session.driver'), 'cache' => config('cache.default'), 'mail' => config('mail.default')],
        'users_count'            => count($users),
        'get_routes_total'       => count($getRoutes),
        'cells_per_user'         => $cellsPerUser,
        'cells_total'            => $cellsPerUser * count($users),
        'routes_skipped_count'   => count($skipped),
        'order_fixtures'         => $fixtures,
        'vat_fixture'            => '00000000000',
        'step_fixture'           => '1',
        'safety'                 => [
            'method'        => 'SOLO GET',
            'infra_writes'  => 'session/cache/mail forzati ad array (nessuna scrittura)',
            'db_writes'     => 'ogni richiesta in transazione DB con ROLLBACK garantito (0 modifiche nette)',
            'skipped_side_effects' => count($skipped),
        ],
        'csrf_normalized'        => true,
        'notes'                  => [
            'actingAs in-memory (setUser sul guard web): il NextcloudAuthBridge vede Auth::check()=true e prosegue (nessun JWT in query => nessuna riconvalida).',
            'Redirect NON seguiti: il 302 e registrato tale e quale (es. legal.gate -> onboarding; welcome gate -> onboarding).',
            'htmlHash = SHA-256 del body con token CSRF normalizzato a __CSRF__ (evita falsi diff tra run).',
            'Rotte {order} testate su studioA (piu ordini, atteso lo "studio 3" da ~80) e studioB (altro studio) => cattura IDOR/cross-studio: OrderPolicy view/upload + authz per-creatore di comments.',
            'Nessuna rotta GET parametrizzata per sede/indirizzo esiste (addresses.* sono solo POST/DELETE) => fuori scope read-only.',
            'status -1 = eccezione durante la richiesta (dettaglio nel campo note della cella).',
        ],
    ],
    'summary_per_user' => $summary,
    'skipped_routes'   => $skipped,
    'baseline'         => $baseline,
];

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
err("\nDONE. celle/utente=" . $cellsPerUser . " utenti=" . count($users) . " saltate=" . count($skipped));
