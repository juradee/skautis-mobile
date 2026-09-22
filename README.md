# skautIS mobil

Mobilní webová aplikace nad webovými službami [skautISu](https://is.skaut.cz).
Symfony 8.1, mobile first, instalovatelná do telefonu jako PWA.

Aktuálně umí to, co člověk potřebuje na schůzce nebo na výpravě: přihlásit se
skautISovým účtem, přepínat role, projít členy jednotky a jedním ťuknutím jim
zavolat nebo napsat.

## Rychlý start

Na hostiteli stačí Docker – PHP, Composer ani Node nejsou potřeba.

```bash
make build      # postaví PHP image (jednorázově, pár minut)
make install    # composer install + frontend vendor assety
make up         # nastartuje php-fpm + nginx + PostgreSQL
```

Aplikace běží na <http://localhost:8080>. Bez nastaveného `SKAUTIS_APP_ID` jede
v **demo režimu** – přihlásíš se tlačítkem „Přihlásit se jako demo uživatel“ a
všechna data pocházejí ze souborů ve `fixtures/skautis/`.

`make` bez parametrů vypíše všechny cíle (`sh`, `console`, `test`, `stan`, `cs`, …).

> Service worker potřebuje zabezpečený kontext. `http://localhost` se za
> zabezpečený považuje, takže PWA jde testovat lokálně; přes IP adresu v síti už ne.

## Připojení k opravdovému skautISu

1. Požádej o registraci aplikace na <https://ws.skautis.cz/> a nech si přidělit AppID.
   V registraci jsou zapsané tyhle návratové adresy:

   | | |
   |---|---|
   | URL po přihlášení | `https://mobile.skauting.cz/login` |
   | URL po odhlášení  | `https://mobile.skauting.cz/logout` |

   Routy `app_login` a `app_logout` musí těmhle cestám odpovídat – skautIS posílá
   POST na první z nich a přesměrovává na druhou.

2. Zkopíruj `.env.local.example` jako `.env.local` a doplň `SKAUTIS_APP_ID`.
   Nastav `SKAUTIS_MOCK=0`. `.env.local` se necommituje.

3. Testovací účty do `test-is.skaut.cz` jsou popsané v
   [nápovědě skautISu](https://napoveda.skaut.cz/skautis/testovaci).

**Pozor na lokální vývoj:** skautIS posílá POST na adresu zaregistrovanou u AppID,
tedy na produkční doménu – na `localhost` se nedostane. Buď si nech zaregistrovat
druhou testovací aplikaci s lokální adresou, nebo vyvíjej v demo režimu.

## Jak to funguje

### Přihlášení

skautIS řeší autentizaci sám. Uživatel odejde na `{skautIS}/Login/?appid=…`,
přihlásí se tam, a skautIS pošle POST na `/login` s poli `skautIS_Token`,
`skautIS_IDRole`, `skautIS_IDUnit` a `skautIS_DateLogout`. Token je jediná
přihlašovací informace, kterou máme – ověřujeme ho tím, že s ním zavoláme
`UserManagement.UserDetail`.

Token platí 30 minut. [`SkautisSessionListener`](src/EventListener/SkautisSessionListener.php)
ho jednou za 10 minut prodlužuje přes `LoginUpdateRefresh`; když vyprší,
[`SkautisExceptionListener`](src/EventListener/SkautisExceptionListener.php)
pošle uživatele zpátky na přihlášení místo toho, aby spadla pětistovka.

Odhlášení musí proběhnout i na straně skautISu, jinak by se příští návštěva
přihlásila sama: `/odhlasit` → `{skautIS}/Login/LogOut.aspx` → `/logout`.

### Vrstva nad skautISem

Klient, přihlašování a role **nejsou součástí aplikace** — žijí v samostatném
balíčku [`packages/skautis-symfony/`](packages/skautis-symfony/README.md)
(`webwingscz/skautis-symfony`, MIT). Aplikace si ho tahá přes Composer path
repository, takže se edituje přímo tady, ale je připravený k vydání na Packagist.

Důvod odděleně: Symfony integrace na skautIS veřejně neexistuje (jediná je
`skautis/nette`), tohle je použitelné i mimo tenhle projekt — a proto má
permisivní MIT, zatímco aplikace zůstává AGPL.

```
packages/skautis-symfony/src/
├── Client/         SOAP klient, fixture klient, držák tokenu
├── Api/            UserApi, UnitApi, PersonApi
├── Dto/            Person, Unit, Role, Contact
├── Security/       SkautisUser, authenticator, user provider
└── EventListener/  prodlužování přihlášení, překlad chyb
```

V aplikaci zůstávají jen controllery, šablony a PWA. Konfigurace bundlu je
v [`config/packages/skautis.yaml`](config/packages/skautis.yaml).

**Vyštípnutí do vlastního repozitáře**, až na to dojde:

```bash
git subtree split --prefix=packages/skautis-symfony -b skautis-symfony-release
git push git@github.com:webwingscz/skautis-symfony.git skautis-symfony-release:master
```

Pak se v root `composer.json` zahodí `repositories` a `@dev` se vymění za verzi.

### Přidání další funkce skautISu

Postup je popsaný v [README balíčku](packages/skautis-symfony/README.md#přidání-další-metody-skautisu).
Ve zkratce: ověř názvy polí na `…/JunakWebservice/<Služba>.asmx?op=<Metoda>`,
přidej metodu do `packages/skautis-symfony/src/Api/`, dopiš fixture do
`packages/skautis-symfony/resources/fixtures/` a obsluž ji ve `FixtureSkautisClient`,
ať demo režim a testy dál fungují.

### PWA

- Manifest se generuje v [`PwaController`](src/Controller/PwaController.php), aby
  URL ikon nesly AssetMapper digest.
- [`public/sw.js`](public/sw.js) je service worker. Stránky jsou **network first** –
  data o členech jsou osobní údaje a mění se ve skautISu, takže se online nikdy
  neservírují z cache. Cache drží jen skořápku, statické assety (ty mají v URL
  digest, takže je hit vždycky správný) a poslední viděnou podobu stránky pro
  offline stav.
- Ikony v `assets/icons/` generuje `tools/generate-icons.py` (SVG i PNG ze stejné geometrie).

## Vývoj

```bash
make test       # PHPUnit - aplikace i balíček
make stan       # PHPStan level 8 - aplikace i balíček
make cs         # php-cs-fixer
make console c="debug:router"
```

`make test` a `make stan` pokrývají aplikaci i balíček proti `vendor/` aplikace.
Balíček má navíc **vlastní** `phpunit.xml.dist` a `composer.json`, takže obstojí
i samostatně — `make test-bundle` mu nainstaluje jeho vlastní závislosti a pustí
jeho sadu včetně funkčního testu, který postaví kernel s bundlem. To je test,
který v CI projíždí matici verzí Symfony.

Databáze (PostgreSQL) v compose běží a Doctrine je nastavené, ale zatím ji nic
nepoužívá – skautIS je zdroj pravdy. Je tam připravená pro věci, které ve
skautISu nejsou: oblíbené kontakty, offline fronta, nastavení aplikace.

## Zdroje

- [Dokumentace pro programátory](https://napoveda.skaut.cz/programatori) – postup připojení, registrace aplikace
- [Obsluha přihlášení a odhlášení](https://napoveda.skaut.cz/programatori/uzivatel) – token, jeho platnost, `LoginUpdateRefresh`
- [ws.skautis.cz](https://ws.skautis.cz/) – rozcestník, živé zkoušení volání, žádost o registraci aplikace
- [Přehled webových služeb](https://test-is.skaut.cz/JunakWebservice) – seznam služeb a jejich metod
- [test-is.skaut.cz/WebAPI](https://test-is.skaut.cz/WebAPI/) – novější REST API, zatím jen vzdělávací akce a pronájmy
- [github.com/skaut](https://github.com/skaut) – knihovny a nástroje skautského IT

## Licence

AGPL-3.0-or-later, viz [LICENSE](LICENSE).
