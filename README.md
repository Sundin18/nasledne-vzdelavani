# Následné vzdělávání

Webová aplikace pro přihlašování na kurzy následného vzdělávání. Postavená na Laravelu, Livewire a Flux UI (Pro).

## Co aplikace umí

**Účastník**

- Úvodní stránka s popiskem, přihlášením a registrací.
- **Kurzy** (`/kurzy`): nadcházející kurzy seřazené od nejbližšího, proběhlé se nezobrazují. Přihlášení i odhlášení jedním kliknutím, dokud je volné místo a kurz ještě nezačal.
- **Moje kurzy** (`/moje-kurzy`): nadcházející přihlášky (s možností odhlásit se) a proběhlé kurzy. U kurzů s potvrzenou účastí si účastník stáhne PDF certifikát.

**Administrátor**

- Na stránce Kurzy vidí i proběhlé kurzy a může kurz přidat, upravit, smazat nebo otevřít jeho detail.
- **Formulář kurzu**: název, kategorie (lze vybrat více), začátek a konec s časem, místo, kapacita a obsah (rich text).
- **Detail kurzu**: seznam přihlášených, zaškrtnutí „Zúčastnil se“ (jednotlivě i hromadně), PDF prezenční listina, PDF certifikát pro jednoho účastníka nebo hromadně pro všechny zúčastněné.

**Notifikace**: po každém přihlášení odejde na pozadí (fronta) e-mail na `akreditovane.zkousky@mycomm.cz` se jménem, e-mailem účastníka, názvem a datem kurzu.

## Zprovoznění

Vyžaduje PHP 8.4+, Composer, Node.js a licenci Flux Pro.

```bash
composer setup            # instalace závislostí, .env, klíč, migrace, build frontendu
php artisan db:seed       # volitelně ukázková data (admin@example.com / password)
composer dev              # vývojový server
```

Musí běžet worker fronty (QUEUE_CONNECTION=database), jinak se notifikační e-maily neodešlou:

```bash
php artisan queue:work
```

### Administrátor

Uživatel se zaregistruje běžně přes `/register` a práva administrátora mu přidělíte příkazem:

```bash
php artisan app:make-admin jmeno@firma.cz
php artisan app:make-admin jmeno@firma.cz --revoke   # odebrání práv
```

### Nastavení (`.env`)

| Proměnná | Význam |
|---|---|
| `COURSES_NOTIFICATION_EMAIL` | Kam chodí oznámení o nových přihláškách (výchozí `akreditovane.zkousky@mycomm.cz`). |
| `COURSES_ORGANIZATION` | Název organizace na certifikátu. |
| `COURSES_SIGNATORY` | Jméno podepisující osoby na certifikátu. |
| `MAIL_*` | Nastavení odesílání e-mailů (SMTP). |
| `APP_TIMEZONE` | Časové pásmo, výchozí `Europe/Prague`. |

Seznam kategorií kurzů je v `config/courses.php`.

## Struktura

| Část | Soubor |
|---|---|
| Stránka Kurzy | `resources/views/pages/courses/⚡index.blade.php` |
| Moje kurzy | `resources/views/pages/courses/⚡mine.blade.php` |
| Formulář kurzu (admin) | `resources/views/pages/admin/courses/⚡form.blade.php` |
| Detail kurzu (admin) | `resources/views/pages/admin/courses/⚡show.blade.php` |
| Přihlášení na kurz + e-mail | `app/Actions/Courses/RegisterForCourse.php`, `app/Mail/CourseRegistrationCreated.php` |
| PDF certifikáty a prezenční listina | `app/Http/Controllers/CertificateController.php`, `AttendanceSheetController.php`, `resources/views/pdf/` |
| Oprávnění | `app/Providers/AppServiceProvider.php` (gate `admin`, `download-certificate`) |

## Testy

```bash
composer test
```
