
# StudyPlanner

StudyPlanner este o aplicație web pentru organizarea activităților școlare.

## Funcționalități

- Înregistrare, autentificare și deconectare.
- Gestionarea materiilor școlare.
- Gestionarea sarcinilor și a termenelor-limită.
- Planificarea sesiunilor de studiu.
- Gestionarea examenelor.
- Editarea și ștergerea înregistrărilor.
- Panou principal cu statistici.
- Validarea formularelor și protecție CSRF.

## Tehnologii

- PHP
- MySQL
- PDO
- HTML5 și CSS3
- Bootstrap 5
- JavaScript
- XAMPP

## Instalare locală

1. Instalează și pornește Apache și MySQL din XAMPP.
2. Copiază proiectul în `C:\xampp\htdocs\studyplanner`.
3. Creează baza de date `studyplanner_db` în phpMyAdmin.
4. Creează tabelele necesare conform structurii bazei de date a proiectului.
5. Copiază `config/database.example.php` în `config/database.php`.
6. Configurează datele de conectare în `config/database.php`.
7. Deschide `http://localhost/studyplanner/` în browser.
8. Creează un cont și începe să folosești aplicația.

## Structura proiectului

- `config/` — configurarea bazei de date.
- `pages/` — paginile aplicației.
- `assets/css/` — stilurile CSS.
- `assets/js/` — validarea formularelor.
- `index.php` — panoul principal.

## Autor

Proiect realizat în scop educațional.
