# 💾 SQL Tutor ASIX — Plataforma d'Aprenentatge Dinàmic de T-SQL

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8" />
  <img src="https://img.shields.io/badge/SGBD-SQL_Server_2025-CC292B?style=for-the-badge&logo=microsoftsqlserver&logoColor=white" alt="SQL Server" />
  <img src="https://img.shields.io/badge/SQLite-PDO-003B57?style=for-the-badge&logo=sqlite&logoColor=white" alt="SQLite" />
  <img src="https://img.shields.io/badge/Editor-CodeMirror_5-D84924?style=for-the-badge" alt="CodeMirror" />
  <img src="https://img.shields.io/badge/Cicle-ASIX_2_Educem-0284c7?style=for-the-badge" alt="ASIX2 Educem" />
  <img src="https://img.shields.io/badge/Idioma-Catal%C3%A0-FFD700?style=for-the-badge" alt="Català" />
  <img src="https://img.shields.io/badge/Llic%C3%A8ncia-MIT-green?style=for-the-badge" alt="MIT License" />
</p>

> **SQL Tutor ASIX** és una aplicació web d'aprenentatge actiu i gamificat de **Transact-SQL (T-SQL)** desenvolupada específicament per a l'alumnat de 2n curs d'**Administració de Sistemes Informàtics en Xarxa (ASIX)** a **Educem**, dins del mòdul **M0377 – Administració de SGBDs** (assignatura impartida pel professor *David Porti Pujal*).
> 
> La plataforma utilitza el material de classe en temps real (PDFs de teoria, exercicis i scripts reals de la base de dades **HelpdeskTIC**) i ofereix un **tutor virtual socràtic**, execució segura en un **sandbox local** i **repàs espaiat**.

---

## 📑 Taula de Continguts
1. [Característiques Principals](#-característiques-principals)
2. [Arquitectura del Sistema](#-arquitectura-del-sistema)
3. [Requisits de l'Entorn](#-requisits-de-lentorn)
4. [Guia d'Instal·lació i Desplegament](#-guia-dinstallaci%C3%B3-i-desplegament)
5. [Seguretat i Aïllament de la Sandbox (Notes ASIX)](#-seguretat-i-a%C3%AFllament-de-la-sandbox-notes-asix)
6. [Modes de l'Aplicació](#-modes-de-laplicaci%C3%B3)
7. [Indexador Dinàmic del Temari](#-indexador-din%C3%A0mic-del-temari)
8. [Configuració del Tutor IA i Mode Offline](#-configuraci%C3%B3-del-tutor-ia-i-mode-offline)
9. [Estructura del Projecte](#-estructura-del-projecte)
10. [Llicència i Crèdits](#-llic%C3%A8ncia-i-cr%C3%A8dits)

---

## ✨ Característiques Principals

- 🎯 **Fidelitat Absoluta al Temari de Classe:** ~80% dels continguts provenen directament dels apunts i pràctiques de classe (`BLOC01.Tema01.Funcions (UDF).pdf`, `BLOC01.Tema02.Stored Procedures.pdf` i l'escenari `HelpdeskTIC`).
- 🔄 **Indexador Intel·ligent:** Detecta automàticament canvis i nous fitxers a la carpeta del curs mitjançant comprovació de hash MD5, adaptant-se a mesura que avança el trimestre.
- 🧑‍🏫 **Tutor Socràtic amb IA (Claude / Gemini / Offline):** No dona la solució completa; formula preguntes guia, assenyala la fallada de lògica i estimula la deducció autònoma de l'alumne.
- 🧪 **Laboratori Guiat Pas a Pas:** Els exercicis no mostren tot l'enunciat de cop. Es desglossen en fites petites amb validació automàtica i sistema de pistes de 3 nivells.
- ⚡ **Traductor d'Errors en Català:** Els errors críptics de SQL Server (*"Must declare scalar variable"*, violacions de claus foranes, o oblidar el prefix `dbo.`) s'expliquen en llenguatge senzill i amigable.
- 🎮 **Gamificació Completa:** Punts d'experiència (XP), pujada de nivells d'administrador/a, comptador de ratxa diària (🔥) i insígnies.
- 🧠 **Repàs Espaiat (Spaced Repetition):** Registre automàtic dels conceptes on l'alumne falla per tornar-los a proposar passades 48 hores.

---

## 🏗️ Arquitectura del Sistema

```
  ┌─────────────────────────────────────────────────────────────┐
  │                      Navegador Web                          │
  │   (HTML5 + CSS Modern + Vanilla JS + CodeMirror 5 CDN)      │
  └──────────────────────────────┬──────────────────────────────┘
                                 │ HTTP / JSON (AJAX)
                                 ▼
  ┌─────────────────────────────────────────────────────────────┐
  │                  Servidor Web Apache (XAMPP)                │
  │                 Controlador Frontal PHP 8                   │
  └──────────────┬───────────────────────────────┬──────────────┘
                 │                               │
                 ▼                               ▼
  ┌──────────────────────────────┐ ┌─────────────────────────────┐
  │   Persistència Local SQLite  │ │    Sandbox d'Execució       │
  │         (data/app.db)        │ │        SqlExecutor          │
  │  - Progrés & Punts XP        │ │  - Timeout 5s (proc_open)   │
  │  - Índex del temari (MD5)    │ │  - Filtratge d'ordres       │
  │  - Historial d'errors        │ │  - sqlcmd (Windows Auth)    │
  └──────────────────────────────┘ └─────────────┬───────────────┘
                 ▲                               │
                 │ Sincronització (Només lectura)│ Execució T-SQL
                 │                               ▼
  ┌──────────────────────────────┐ ┌─────────────────────────────┐
  │   Carpeta Temari ASIX2       │ │   Microsoft SQL Server      │
  │  - PDFs Diapositives David P.│ │         (Local)             │
  │  - Scripts HelpdeskTIC (.sql)│ │  - DB: HelpdeskTIC          │
  └──────────────────────────────┘ └─────────────────────────────┘
```

---

## 💻 Requisits de l'Entorn

| Component | Versió Recomanada | Propòsit |
| :--- | :--- | :--- |
| **Windows** | 10 o 11 (x64) | Sistema operatiu amfitrió |
| **XAMPP** | Amb PHP 8.1 o superior | Servidor web Apache i intèrpret PHP |
| **Extensions PHP** | `pdo_sqlite`, `curl`, `json` | Persistència local i connexions |
| **SQL Server** | 2017 o posterior (2025 provat) | Motor de base de dades local per a T-SQL |
| **sqlcmd** | Inclòs amb SQL Server Client Tools | Utilitat CLI d'execució de consultes |

---

## 🚀 Guia d'Instal·lació i Desplegament

### 1. Clonar o col·locar el projecte a XAMPP
Ubica el projecte a la teva carpeta d'aplicacions web de XAMPP:
```bash
git clone https://github.com/el-teu-usuari/sql-tutor-asix.git C:\xampp\htdocs\sql-tutor
```

### 2. Configurar el fitxer `config.php`
Copia la plantilla d'exemple i personalitza la ruta del teu temari si és necessari:
```bash
cd C:\xampp\htdocs\sql-tutor
copy config.example.php config.php
```

Paràmetres principals a [`config.php`](file:///C:/xampp/htdocs/sql-tutor/config.php):
```php
return [
    // Ruta absoluta on tens els fitxers de classe (mode només lectura)
    'syllabus_path' => 'C:\\Users\\josep\\OneDrive\\Escritorio\\educem\\ASIX2\\BBDD',

    // Paràmetres d'execució SQL Server
    'sql_server' => [
        'sqlcmd_path' => 'C:\\Program Files\\Microsoft SQL Server\\Client SDK\\ODBC\\180\\Tools\\Binn\\SQLCMD.EXE',
        'default_db' => 'HelpdeskTIC',
        'timeout_seconds' => 5,
    ],

    // IA (offline per defecte; canvia a 'claude' o 'gemini' si tens API Key)
    'ai' => [
        'provider' => 'offline',
        'api_key' => '',
        'model' => 'claude-3-5-sonnet-20241022',
    ]
];
```

### 3. Iniciar Apache a XAMPP
Obre el **XAMPP Control Panel** i prem **Start** al servei **Apache**.

*(Opcional: Si el teu DocumentRoot principal d'Apache apunta a una altra carpeta, afegeix un àlies al teu `httpd.conf`)*:
```apache
Alias /sql-tutor "C:/xampp/htdocs/sql-tutor"
<Directory "C:/xampp/htdocs/sql-tutor">
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

### 4. Obrir l'aplicació
Obre el teu navegador a:
👉 **`http://localhost/sql-tutor/`**

La base de dades SQLite local (`data/app.db`) i la primera indexació del temari es crearan automàticament en entrar.

---

## 🔒 Seguretat i Aïllament de la Sandbox (Notes ASIX)

Com a futurs administradors de sistemes, és crític no permetre que un entorn d'execució de codi pugui malmetre el servidor:

1. **Aïllament per procés independent:** L'execució del codi T-SQL es realitza mitjançant la funció nativa de PHP `proc_open` invocant el binari oficial `sqlcmd`.
2. **Limitació estricta de temps (Watchdog 5s):** S'aplica un timeout rígid de 5 segons. Si l'usuari comet un error d'un bucle `WHILE` sense increment de variable o bloqueja una transacció, el procés és terminat immediatament (`proc_terminate`), alliberant recursos de CPU.
3. **Blacklist d'ordres del sistema:** Es bloquegen de forma proactiva comandes que interactuïn amb el sistema operatiu o la seguretat global del SGBD (`xp_cmdshell`, `sp_configure`, `SHUTDOWN`, aturada de serveis).
4. **Validació d'origen Localhost:** Només s'admet trànsit provinent de `127.0.0.1` o `::1` per evitar accessos no autoritzats des de la xarxa local.

---

## 🎮 Modes de l'Aplicació

```
┌────────────────────────────────────────────────────────────────────────┐
│                        MODES DE L'APLICACIÓ                            │
├────────────────────┬────────────────────┬──────────────────────────────┤
│ 1. 📖 APRENDRE     │ 2. 🧪 LABORATORI   │ 3. ⚡ REPTES & REPÀS         │
├────────────────────┼────────────────────┼──────────────────────────────┤
│ • Lliçons per tema │ • Passos petits    │ • "Troba l'error" (bugs)     │
│ • "Prova-ho" live  │ • Execució real    │ • "Què imprimeix?"           │
│ • Micro-tests XP   │ • Tutor Socràtic   │ • Algorisme Spaced Review    │
│ • Analogies ASIX   │ • Pistes 1-2-3     │ • Puntuació extra XP         │
└────────────────────┴────────────────────┴──────────────────────────────┘
```

---

## 🔄 Indexador Dinàmic del Temari

El fitxer [`core/Indexer.php`](file:///C:/xampp/htdocs/sql-tutor/core/Indexer.php) automatitza la integració de continguts:
- Llegeix fitxers `.pdf`, `.sql`, `.docx`, `.txt` de la carpeta de l'assignatura.
- **Detecció de canvis:** Genera un hash MD5 per fitxer. Només reprocessa allò que ha canviat o és nou.
- **Descobriment de nous temes:** En detectar paraules clau com `trigger` o `cursor`, desbloqueja automàticament les noves branques a l'arbre d'aprenentatge.
- Es pot forçar la sincronització en qualsevol moment prement el botó **"🔄 Actualitzar temari"** a la interfície.

---

## 🤖 Configuració del Tutor IA i Mode Offline

Per defecte, l'aplicació funciona en **Mode Offline** amb un motor heurístic integrat que ofereix pistes intel·ligents i explicacions en català sense cap connexió a Internet ni despesa.

Per connectar un model avançat (com **Claude 3.5 Sonnet** o **Gemini**):
1. Edita `config.php`.
2. Estableix `'provider' => 'claude'` (o `'gemini'`).
3. Afegeix la teva clau a `'api_key' => 'LA_TEVA_CLAU'`.
4. El sistema fa les crides sempre des de PHP al servidor; **la clau mai queda exposada al client**.

---

## 📁 Estructura del Projecte

```text
sql-tutor/
├── config.php                  # Configuració activa (credencials, rutes)
├── config.example.php          # Plantilla neta de configuració
├── index.php                   # Controlador frontal i encaminament
├── README.md                   # Documentació completa
├── api/                        # Endpoints REST interns (JSON)
│   ├── execute.php             # Execució de consultes al Sandbox
│   ├── tutor.php               # Endpoint del tutor socràtic i pistes
│   ├── indexer.php             # Sincronització del temari
│   ├── progress.php            # Gestió d'XP, nivell i ratxa
│   └── challenges.php          # Reptes ràpids i repàs
├── core/                       # Lògica de negoci (PHP 8 pur)
│   ├── Database.php            # Connexió SQLite (PDO)
│   ├── Migrations.php          # Esquema de la BD i dades llavor
│   ├── Indexer.php             # Motor d'indexació de fitxers de classe
│   ├── SqlExecutor.php         # Execució proc_open amb sqlcmd i timeout
│   ├── AITutor.php             # Connector amb API Claude / Gemini
│   ├── OfflineTutor.php        # Tutor heurístic per al mode sense connexió
│   └── ChallengesService.php   # Banc de preguntes i depuració de codi
├── data/
│   ├── app.db                  # Base de dades SQLite d'aplicació
│   └── sandbox/                # Directori aïllat per a consultes temporals
├── assets/
│   ├── css/app.css             # Disseny visual responsive
│   └── js/app.js               # Frontend reactiu, CodeMirror i xat
└── views/                      # Vistes HTML en català
    ├── layout.php              # Marc comú (barra lateral i superior)
    ├── dashboard.php           # Arbre de competències
    ├── learn.php               # Mode teoria interactiva
    ├── lab.php                 # Mode laboratori amb tutor lateral
    └── challenges.php          # Mode reptes i repàs espaiat
```

---

## 📜 Llicència i Crèdits

- **Autor de l'aplicació:** Josep (Estudiant de 2n d'ASIX).
- **Temari i exercicis originals:** Professor **David Porti Pujal** — Centre d'Estudis **Educem** (Granollers / Catalunya).
- **Llicència:** Distribuït sota la llicència [MIT](LICENSE). Ús lliure per a fins educatius.
