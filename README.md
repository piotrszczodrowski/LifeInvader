# 🛡️ LifeInvader

Projekt zaliczeniowy z przedmiotu **Warsztaty Programistyczne**.
Platforma społecznościowa stworzona w celach edukacyjnych, rozrywkowych i – przede wszystkim – w celu uzyskania oceny bardzo dobrej (5.0).

---

## 🛠 Wymagania wstępne

Aby uruchomić projekt na swoim komputerze, nie musisz instalować lokalnie serwera WWW, PHP ani serwera bazy danych. Wystarczy, że posiadasz:

*   **Docker** (wersja Desktop dla Windows/macOS lub Engine dla Linuxa)
*   **Docker Compose** (zazwyczaj instalowany razem z Docker Desktop)
*   Opcjonalnie: **Git** (do pobrania repozytorium)

---

## ⚙️ Konfiguracja przed uruchomieniem

Przed pierwszym uruchomieniem projektu, należy skonfigurować zmienne środowiskowe.

### 1. Utworzenie pliku `.env`
W głównym katalogu projektu znajduje się plik `.env.example`. Skopiuj go i zmień nazwę kopii na `.env`. Plik ten centralizuje całą konfigurację.

```bash
cp .env.example .env
```

### 2. Konfiguracja zmiennych
Otwórz plik `.env` i uzupełnij go swoimi danymi. Poniżej opis najważniejszych zmiennych:

*   **Porty aplikacji:**
    *   `APP_PORT`: Port, na którym będzie dostępna aplikacja (domyślnie `2137`).
    *   `PHPMYADMIN_PORT`: Port dla narzędzia PhpMyAdmin (domyślnie `2138`).

*   **Klucze Cloudflare Turnstile:**
    Formularz rejestracji wymaga kluczy API dla usługi Cloudflare Turnstile. Aby je uzyskać, załóż darmowe konto Cloudflare, dodaj nową witrynę w sekcji Turnstile, a w polu **Domain** wpisz wszystkie nazwy hostów, pod którymi aplikacja będzie dostępna (np. `localhost`, `192.168.1.10`, `twoja-domena.com`).
    *   `CLOUDFLARE_SITE_KEY`: Wklej tutaj swój "Site Key".
    *   `CLOUDFLARE_SECRET_KEY`: Wklej tutaj swój "Secret Key".

*   **Baza danych i administrator:**
    *   `DB_NAME`, `DB_USER`, `DB_PASSWORD`: Dane do połączenia z bazą danych.
    *   `ADMIN_EMAIL`, `ADMIN_PASSWORD`: Dane logowania dla domyślnego konta administratora, które zostanie utworzone podczas instalacji.

---

## 🚀 Szybkie uruchomienie projektu

Po skonfigurowaniu pliku `.env`, postępuj zgodnie z poniższymi krokami.

### 1. Pobranie projektu
Sklonuj repozytorium na swój dysk lokalny i przejdź do folderu z projektem:

```bash
git clone https://github.com/piotrszczodrowski/LifeInvader.git
cd LifeInvader
```

### 2. Uruchomienie kontenerów
W głównym katalogu projektu uruchom środowisko za pomocą narzędzia Docker Compose:

```bash
docker compose up -d
```

---

## 🌐 Dostęp do aplikacji i usług

Po uruchomieniu kontenerów, poszczególne usługi będą dostępne pod następującymi adresami (zgodnie z domyślną konfiguracją w `.env`):

*   **Aplikacja LifeInvader:**
    *   Adres: `http://localhost:2137`

*   **PhpMyAdmin (zarządzanie bazą danych przez przeglądarkę):**
    *   Adres: `http://localhost:2138`
    *   Serwer: `db`
    *   Użytkownik: `root`
    *   Hasło: wartość `DB_ROOT_PASSWORD` z pliku `.env`

*   **Bezpośredni dostęp do bazy danych (np. z klienta SQL):**
    *   Host: `127.0.0.1`
    *   Port: `3307`
    *   Użytkownik: wartość `DB_USER` z pliku `.env`
    *   Hasło: wartość `DB_PASSWORD` z pliku `.env`
    *   Baza danych: wartość `DB_NAME` z pliku `.env`

### Pierwsze kroki w systemie:
System automatycznie tworzy konto administratora na podstawie danych `ADMIN_EMAIL` i `ADMIN_PASSWORD` z pliku `.env`.

1.  Wejdź na stronę główną aplikacji i zaloguj się, używając powyższych danych.
2.  Przy pierwszym logowaniu zostaniesz poproszony o **obowiązkową zmianę hasła** administratora.
3.  Po zmianie hasła możesz w pełni korzystać z aplikacji, w tym z panelu administratora. Możesz również rejestrować kolejne, standardowe konta użytkowników.

---

## 🧰 Przydatne komendy

*   **Zatrzymanie projektu:**
    ```bash
    docker compose down
    ```

*   **Całkowity reset środowiska** (Uwaga: usuwa wszystkie dane z bazy i wolumeny):
    ```bash
    docker compose down -v
    ```

*   **Podgląd logów aplikacji:**
    ```bash
    docker compose logs -f app
    ```

---

> **Nota autorska:** Akceptacja i poprawne uruchomienie niniejszego projektu jest w pełni wiążące i tożsame z wystawieniem oceny 5.0. Wszelkie próby ataków XSS w edytorze (choć kuszące) są mile widziane jako testy penetracyjne i zostaną odnotowane przez wbudowane mechanizmy audytu. Dziękuję za wyrozumiałość i życzę miłego testowania!
