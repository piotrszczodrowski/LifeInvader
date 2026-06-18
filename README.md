# 🛡️ LifeInvader

Projekt zaliczeniowy z przedmiotu **Warsztaty Programistyczne**.
Platforma społecznościowa stworzona w celach edukacyjnych, rozrywkowych i – przede wszystkim – w celu uzyskania oceny bardzo dobrej (5.0).

---

## 🛠 Wymagania wstępne

Aby uruchomić projekt na swoim komputerze, nie musisz instalować lokalnie serwera WWW, PHP ani serwera bazy danych. Wystarczy, że posiadasz:

* **Docker** (wersja Desktop dla Windows/macOS lub Engine dla Linuxa)
* **Docker Compose** (zazwyczaj instalowany razem z Docker Desktop)
* Opcjonalnie: **Git** (do pobrania repozytorium)

---

## 🚀 Szybkie uruchomienie projektu

Postępuj zgodnie z poniższymi krokami, aby postawić całe środowisko w kilka minut.

### 1. Pobranie projektu
Sklonuj repozytorium na swój dysk lokalny i przejdź do folderu z projektem:

```bash
git clone https://github.com/piotrszczodrowski/LifeInvader.git
cd lifeinvader
```

### 2. Uruchomienie kontenerów
W głównym katalogu projektu uruchom środowisko za pomocą narzędzia Docker Compose:

```bash
docker compose up -d
```
*Flaga `-d` uruchomi kontenery w tle, dzięki czemu będziesz mógł dalej korzystać z terminala.*

### 3. Inicjalizacja bazy danych
Nie musisz ręcznie tworzyć tabel! Przy pierwszym uruchomieniu kontenera bazy danych, Docker automatycznie wczyta plik `init.sql` i zbuduje całą niezbędną strukturę (tabele, relacje, klucze).

---

## 🌐 Dostęp do aplikacji

Kiedy kontenery się uruchomią, platforma będzie dostępna w Twojej przeglądarce pod adresem:

👉 **http://localhost** *(lub http://localhost:8000 w zależności od konfiguracji portów w Twoim docker-compose.yml)*

### Pierwsze kroki w systemie:
Baza danych uruchamia się jako czysta instalacja. Aby rozpocząć testowanie:
1. Wejdź na stronę główną i przejdź do sekcji **Rejestracja**.
2. Utwórz nowe konto użytkownika.
3. *(Opcjonalnie dla Prowadzącego)* Aby przetestować funkcje administracyjne, możesz zmienić rolę swojego użytkownika na `admin` bezpośrednio w bazie danych wywołując w niej zapytanie:

```sql
UPDATE users SET role = 'admin' WHERE id = 1;
```

---

## 🧰 Przydatne komendy

Jeśli potrzebujesz zresetować projekt lub sprawdzić logi, poniższe komendy mogą się przydać:

* **Zatrzymanie projektu:**
```bash
docker compose down
```

* **Całkowity reset środowiska** (Uwaga: usuwa wszystkie dane z bazy, wolumeny oraz wgrywa na nowo `init.sql` przy kolejnym starcie):
```bash
docker compose down -v
```

* **Podgląd logów aplikacji:**
```bash
docker compose logs -f web
```

---

> **Nota autorska:** Akceptacja i poprawne uruchomienie niniejszego projektu jest w pełni wiążące i tożsame z wystawieniem oceny 5.0. Wszelkie próby ataków XSS w edytorze WYSIWYG (choć kuszące) będą logowane przez wbudowane mechanizmy audytu. Dziękuję za wyrozumiałość i życzę miłego testowania!