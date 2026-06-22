# Laravel Security Audit Workflow

Defenzivní bezpečnostní auditor v autorizovaném prostředí. Cílem je najít a nahlásit slabiny a navrhnout opravu + regresní test — **ne exploitovat**.

## Severity škála

Auditní reportování používá 5 stupňů; konvergenční brána repo (CR) mapuje na 3 stupně (High+Medium splývají do Moderate, Low+Info splývají do Minor):

| Auditní severity | CR severity | Blokuje konvergenci? |
|------------------|-------------|----------------------|
| Critical         | Critical    | ANO                  |
| High             | Moderate    | ANO                  |
| Medium           | Moderate    | ANO                  |
| Low              | Minor       | NE                   |
| Info             | Minor       | NE                   |

Piny athena.md (`Critical`/`Moderate`/`Minor`) zůstávají beze změny — audit severity je reportovací vrstva nad nimi.

## Každý potvrzený nález nese

1. **Oblast** (1–7 níže) + **severity** (Critical/High/Medium/Low/Info).
2. **Konkrétní soubor + řádek** (nebo vzorec vyhledávání).
3. **Navrhovaná oprava** — odkazem na příslušnou sekci `@skills/laravel-security/SKILL.md`.
4. **Návrh regresního testu** (Pest/PHPUnit) — auditor načrtne test, který by nález odhalil; aplikační opravu implementuje `talos`.

## 7 oblastí auditu

### 1. Authorization — IDOR/BOLA

**Co hledat:**

- Chybějící `$this->authorize()` / `Gate::authorize()` / `@can` v controllerech a Livewire komponentách — policy nebo gate pro každý resource endpoint.
- Přímé dotazy bez scope na aktuálního uživatele: `Post::find($id)` místo `auth()->user()->posts()->findOrFail($id)`.
- Chybějící Route Model Binding s policy: controller přijme `{post}` a nikdy neověří vlastnictví.
- Tenant isolation: multi-tenant app bez globálního scope nebo filtrování `tenant_id`.
- Role bypass: admin route group bez middleware `role:admin`; Filament panel bez `canAccessPanel()`.
- Livewire actions: mounted component není autorizační hranice — každá action musí znovu volat authorize.

**Vzory Grep:**

```bash
grep -rn "::find\(\|::findOrFail\(\|::firstOrFail\(" app/Http/Controllers/
grep -rn "->authorize\|Gate::authorize\|@can" app/ --include="*.php" --include="*.blade.php"
```

**Referenční oprava:** sekce *Authorization* v `@skills/laravel-security/SKILL.md`.

**Příklad regresního testu:**

```php
it('prevents accessing another user post', function (): void {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $post = Post::factory()->for($owner)->create();

    $response = actingAs($attacker)->get("/posts/{$post->id}");

    $response->assertForbidden(); // nebo assertNotFound() dle zvolené strategie
});
```

---

### 2. Authentication

**Co hledat:**

- Chybějící rate limiting na `/login`, `/forgot-password`, `/register` — viz sekce *API Security* (RateLimiter).
- Session fixation: `$request->session()->regenerate()` chybí po úspěšném přihlášení.
- Logout: session není invalidována (`invalidate()` + `regenerateToken()`) nebo token není revokován.
- Přístup deaktivovaných uživatelů: `Authenticatable::banned/is_active` není kontrolováno po přihlášení (event `Authenticated` nebo middleware `EnsureUserIsActive`).
- Reset hesla: tokenová platnost; žetony nejsou jednorázové nebo mají příliš dlouhou expiraci.

**Vzory Grep:**

```bash
grep -rn "session()->regenerate\b" app/
grep -rn "throttle:" routes/ app/
```

**Referenční oprava:** sekce *Authentication* a *API Security* v `@skills/laravel-security/SKILL.md`.

**Příklad regresního testu:**

```php
it('regenerates session on login', function (): void {
    $user = User::factory()->create();
    $this->get('/login');
    $guestToken = $this->app['session']->token();

    $this->post('/login', ['email' => $user->email, 'password' => 'password']);

    expect($this->app['session']->token())->not->toBe($guestToken);
});
```

---

### 3. Validation a requesty

**Co hledat:**

- Chybějící FormRequest — inline `$request->validate()` ve více místech nebo žádná validace.
- `authorize()` vrací `true` napevno nebo chybí logika — FormRequest musí skutečně autorizovat.
- Mass assignment: `$request->all()` nebo `$request->except(...)` předáno do `create()`/`fill()`; model nemá `$fillable`.
- Nevalidované parametry: query string / route parameter použit v dotazu bez sanitizace.
- DB::raw nebo whereRaw s interpolací: `DB::select("... '{$input}'")`; `orderByRaw($request->sort)`.

**Vzory Grep:**

```bash
grep -rn "request()->all()\|->all()" app/Http/Controllers/
grep -rn "DB::raw\|whereRaw\|orderByRaw\|selectRaw" app/ --include="*.php"
grep -rn "return true;" app/Http/Requests/ --include="*.php"
```

**Referenční oprava:** sekce *Eloquent Security* a *Input Validation* v `@skills/laravel-security/SKILL.md`.

**Příklad regresního testu:**

```php
it('rejects unvalidated sort parameter', function (): void {
    $response = get('/posts?sort=malicious_sql_fragment--');
    $response->assertUnprocessable(); // nebo assertBadRequest()
});
```

---

### 4. XSS

**Co hledat:**

- `{!! $variable !!}` v Blade šablonách — zkontroluj, zda proměnná pochází z uživatelského vstupu.
- `innerHTML` nebo `x-html` v Alpine.js bez sanitizace.
- Markdown renderování bez HTML purification (nepurifikovaný výstup v `{!! Str::markdown($input) !!}`).
- Livewire / Inertia: props z databáze zobrazené bez escapování ve Vue/React.
- `@json($data)` s citlivými nebo uživatelskými daty — ověř, co se serializuje.

**Vzory Grep:**

```bash
grep -rn "{!!" resources/views/ --include="*.blade.php"
grep -rn "x-html\|innerHTML" resources/
grep -rn "Str::markdown\|commonmark" app/ resources/
```

**Referenční oprava:** sekce *XSS Prevention* v `@skills/laravel-security/SKILL.md`.

**Příklad regresního testu:**

```php
it('escapes user-supplied content in view', function (): void {
    $post = Post::factory()->create(['title' => '<script>alert(1)</script>']);

    $response = get("/posts/{$post->id}");

    $response->assertSee('&lt;script&gt;', false);
    $response->assertDontSee('<script>alert(1)</script>', false);
});
```

---

### 5. File upload

**Co hledat:**

- Chybějící `mimes:` nebo `extensions:` validace — lze nahrát `.php`, `.html`, `.svg`, `.phar`.
- Chybějící `max:` — DoS přes velké soubory.
- Uložení na `public` disk bez autorizačního serve endpointu — soubory dostupné přímo bez auth.
- Path traversal: použití `$request->file('f')->getClientOriginalName()` v cestě bez sanitizace.
- SVG upload bez sanitizace (SVG může obsahovat `<script>`).

**Vzory Grep:**

```bash
grep -rn "->store\|->storeAs" app/ --include="*.php"
grep -rn "getClientOriginalName\|getClientOriginalExtension" app/ --include="*.php"
grep -rn "'public'" app/ --include="*.php"
```

**Referenční oprava:** sekce *File Upload Security* v `@skills/laravel-security/SKILL.md`.

**Příklad regresního testu:**

```php
it('rejects php file upload', function (): void {
    $file = UploadedFile::fake()->create('shell.php', 10, 'application/x-php');

    $response = actingAs(User::factory()->create())
        ->post('/uploads', ['file' => $file]);

    $response->assertUnprocessable();
});
```

---

### 6. Secrets a konfigurace

**Co hledat:**

- `.env` commitováno do repozitáře (`.gitignore` chybí nebo je chybné).
- API klíče, hesla, tokeny napevno v kódu nebo v testech (např. `'secret' => 'hardcoded_key'`).
- `APP_DEBUG=true` v `.env.example` nebo v production configu.
- Credentials v logu: `Log::info('Login', ['password' => $password])`.
- Cookie nastavení: `Secure`, `HttpOnly`, `SameSite` — viz sekce *Production Configuration*.

**Vzory Grep:**

```bash
grep -rn "APP_DEBUG=true" . --include=".env*"
grep -rn "password\|secret\|api_key" --include="*.php" app/ config/ | grep -v "env(\|config("
grep -rn "Log::" app/ --include="*.php" | grep -i "password\|secret\|token"
```

**Referenční oprava:** sekce *Production Configuration* a *Secrets and Dependencies* v `@skills/laravel-security/SKILL.md`. Pro cookie viz `@rules/security/backend.md`.

**Příklad regresního testu:**

```php
// Cíl: ten konkrétní Log:: řádek, který grep výše odhalí, např.:
// Log::info('Auth attempt', ['token' => $request->bearerToken(), 'api_key' => $request->input('api_key')]);
it('does not include secret/token/api_key value in the flagged log call context', function (): void {
    $logs = [];
    Log::listen(static function ($log) use (&$logs): void {
        $logs[] = $log->context;
    });

    $secret = 'supersecret-' . uniqid();
    // Nahraď volání endpointu tím, kde grep odhalil Log:: s citlivým klíčem.
    $this->post('/api/example', ['api_key' => $secret]);

    foreach ($logs as $context) {
        expect(json_encode($context))->not->toContain($secret);
    }
});
```

---

### 7. Dependencies

**Co hledat:**

- Nespuštěný `composer audit` — CI pipeline bez audit stepu.
- Zastaralé balíčky s CVE v `composer.lock`.
- Frontend: `npm audit` / `yarn audit` pokud projekt obsahuje `package.json`.
- Balíčky nainstalované z dev-dependencies v produkci (nedostatečný `--no-dev`).

**Příkazy:**

```bash
composer audit
# pokud frontend:
npm audit --audit-level=high
```

**Referenční oprava:** sekce *Secrets and Dependencies* v `@skills/laravel-security/SKILL.md`. Pro dependency-selection viz `@rules/php/dependency-selection.mdc`.

**Příklad regresního testu (CI pin):**

```yaml
# .github/workflows/ci.yml
- name: Security audit
  run: composer audit
```

Regresní test pro PHP: ověřit, že CI krok `composer audit` existuje v pipeline YML (obsahový test v testsuite).

---

## Výstupní formát nálezu

```
[Oblast] [Severity] Popis nálezu
Soubor: app/Http/Controllers/PostController.php:42
Oprava: viz sekce Authorization v @skills/laravel-security/SKILL.md
Regresní test: <načrtnutý test výše>
```

Severity-sorted výstup (Critical → High → Medium → Low → Info). Každý confirmed nález musí mít návrh regresního testu.

## Dedup/gating

Pokud oblast už pokrývá existující bullet v `@skills/laravel-security/SKILL.md` nebo v `@rules/security/backend.md` / `@rules/security/frontend.md`, audit sekce odkazuje na něj a nepřidává duplicitní detekční logiku. Nová detekce v tomto souboru pokrývá pouze vzory specifické pro auditní workflow (Grep příkazy, příklady testů), nikoli secure-by-default bloky (ty žijí v SKILL.md).
