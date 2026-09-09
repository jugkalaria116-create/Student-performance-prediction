# Student Performance Predictor & Analytics System

A local college-lab web application built with **Core PHP + MySQL + HTML5 + CSS3 + JavaScript**,
designed to run on **XAMPP**. It lets a teacher/administrator record a student's academic factors,
generate a predicted performance score, classify it, determine pass/fail, and track prediction
history over time.

---

## 1. Features

- Add / edit / delete students
- Course and semester assignment (courses loaded dynamically from the database)
- Prediction form with a transparent, explainable weighted-factor formula
- Every prediction is saved permanently — **prediction history is never overwritten**
- Dashboard with live statistics and charts
- Dedicated Analytics page (pass/fail split, performance distribution, course-wise and
  semester-wise averages)
- Student detail page with full performance history and a trend chart
- Search by name/email, filter by course and semester
- CSRF protection on all state-changing forms
- Client-side (JavaScript) **and** server-side (PHP) validation on every form
- No login/authentication — intentional, see section 9 below

---

## 2. Requirements

- [XAMPP](https://www.apachefriends.org/) (Apache + MySQL + PHP 8+)
- A modern web browser
- Internet connection **only** for loading Bootstrap, Bootstrap Icons, and Chart.js from their CDNs
  (see section 8)

---

## 3. Installation

1. Install and open **XAMPP**.
2. Start the **Apache** and **MySQL** services from the XAMPP Control Panel.
3. Copy the entire `student_predictor` folder into your XAMPP `htdocs` directory, so the path looks like:
   ```
   C:\xampp\htdocs\student_predictor\   (Windows)
   /Applications/XAMPP/htdocs/student_predictor/   (Mac)
   /opt/lampp/htdocs/student_predictor/   (Linux)
   ```
4. Open **phpMyAdmin** in your browser: `http://localhost/phpmyadmin`
5. Click **Import**, choose the `database.sql` file from the project folder, and click **Go**.
   This creates the `student_predictor` database with the schema and sample data already loaded.
6. Open the application in your browser:
   ```
   http://localhost/student_predictor/
   ```

That's it — the dashboard should immediately show statistics and charts from the sample data.

---

## 4. Database Structure

**`courses`** — id, course_name (unique)

**`students`** — id, name, email (unique, nullable), course_id (FK → courses), semester (1–12)

**`student_performance`** — one row per prediction:
student_id (FK → students, `ON DELETE CASCADE`), semester, the five input factors,
predicted_marks, performance, result, model_version, created_at, updated_at

### Relationship
```
courses (1) ───< (many) students (1) ───< (many) student_performance
```
One course can have many students. One student can have many performance/prediction
records — especially across different semesters, or even multiple attempts within the
same semester.

### Why multiple predictions per student/semester are allowed
The system is designed to **preserve prediction history**, not overwrite it. If a
teacher re-enters updated attendance or marks partway through a semester, a *new* row
is added rather than replacing the old one. This lets you see how a student's estimated
performance changed over time. There is intentionally **no** `UNIQUE(student_id, semester)`
constraint.

### "Latest prediction" logic
Wherever the app needs a student's most recent prediction (e.g. the Students table), it
uses a correlated subquery that picks the performance row with the most recent
`created_at` (ties broken by `id`) for that student — never just an arbitrary joined row.

### CHECK constraints
The schema defines `CHECK` constraints on all numeric ranges (marks 0–100, study hours
0–24, semester 1–12). **However, older MySQL/MariaDB builds (MariaDB, or MySQL below
8.0.16) silently ignore CHECK constraints.** Because of this, the application performs
the same range validation independently in PHP on every form submission, so data
integrity does not depend on which MySQL version XAMPP happens to bundle.

---

## 5. Prediction Formula

Study hours are on a 0–24 scale while the other inputs are percentages, so study hours
are normalised first:

```
Study Hours Score = (study_hours_per_day / 10) × 100   (capped at 100)
```

Then:

```
Predicted Marks =
      (Study Hours Score × 0.20)
    + (Attendance         × 0.20)
    + (Previous Marks     × 0.30)
    + (Assignment Marks   × 0.15)
    + (Internal Marks     × 0.15)
```

Result is rounded to 2 decimal places and clamped between 0 and 100.

**Classification:** 80–100 Excellent · 60–79.99 Good · 40–59.99 Average · 0–39.99 Poor

**Result:** ≥ 40 = Pass, < 40 = Fail

**Model version:** every prediction is stamped with `model_version = "v1.0"` so future
formula changes remain traceable against old data.

This is an **educational weighted-factor model**, not a trained machine-learning model.

---

## 6. Prediction Data Flow

```
predict.php (GET)  →  shows form
predict.php (POST) →  PHP validates input
                    →  PHP calculates the prediction (never trusts JS/browser math)
                    →  INSERT into student_performance
                    →  $pdo->lastInsertId()
                    →  redirect to result.php?id=<new id>
result.php          →  validates the id
                    →  re-queries the database
                    →  displays the saved record
```
Prediction values are never passed through the URL/GET — only the new row's ID is.

---

## 7. CRUD & Security

- All database access goes through **PDO with prepared statements** — no raw SQL
  concatenation of user input anywhere.
- All output is passed through `htmlspecialchars()` (via the `h()` helper) before printing.
- All state-changing actions (add/edit/delete student, create prediction) require **POST**
  and a valid **CSRF token** (one token per session, stored in `$_SESSION`, checked with
  `hash_equals()`).
- Deleting a student uses a POST form with JS confirmation — never a plain GET link.
- Deleting a student cascades to delete their performance history automatically, because
  the foreign key is defined `ON DELETE CASCADE`.
- Database errors are logged with `error_log()` and never shown to the user — the user
  always sees a friendly message instead of a raw PDOException.

---

## 8. Chart.js Dependency

Chart.js, Bootstrap, and Bootstrap Icons are loaded from public CDNs (`jsdelivr.net`).
Because the app otherwise runs fully locally through XAMPP, **an internet connection is
required only to load these front-end libraries** — not for any application logic. If you
need a fully offline demo, download `chart.js`, `bootstrap.min.css`,
`bootstrap.bundle.min.js`, and the Bootstrap Icons font locally and update the `<script>` /
`<link>` tags in `includes/header.php` and `includes/footer.php` to point at local files;
no other code needs to change.

---

## 9. Why there's no login/authentication

This is a **local, single-user college lab project**. It assumes one teacher/administrator
is using the app directly through XAMPP on their own machine. Adding login, registration,
password reset, or roles would add complexity without a real security benefit in this
context — anyone with access to the machine already has full access to phpMyAdmin and the
database directly.

---

## 10. Project Structure

```
student_predictor/
├── index.php            Dashboard
├── predict.php           Prediction form + handler
├── result.php            Shows a saved prediction
├── students.php           Student list, search, filter
├── add_student.php       Add student form + handler
├── edit_student.php      Edit student form + handler
├── delete_student.php    POST-only delete handler
├── student_view.php      Single student profile + history + chart
├── analytics.php         Analytics page
├── about.php              About / disclaimer
├── db.php                 PDO database connection
├── style.css              Custom design system
├── database.sql           Schema + sample data
├── includes/
│   ├── header.php         Opens HTML, starts session, loads db + helpers
│   ├── navbar.php          Top bar
│   ├── sidebar.php         Left navigation
│   ├── footer.php          Closes HTML, loads JS
│   └── functions.php       Prediction formula, CSRF, badges, escaping
└── assets/
    └── js/
        └── script.js       Validation, sidebar toggle, delete confirm, live preview
```

---

## 11. Testing Checklist

| # | Test | Expected result |
|---|------|------------------|
| 1 | Add a student | Appears in Students page |
| 2 | Create a prediction | Calculated → saved → redirected to `result.php?id=X` |
| 3 | Predict again for same student/semester | Old record stays, new one is added, both show in history |
| 4 | Edit a student | Info updates, performance records untouched |
| 5 | Delete a student | Student and their performance rows are both removed |
| 6 | Search by name/email | Correct filtered results |
| 7 | Filter by course/semester | Correct filtered results |
| 8 | Dashboard | Stats match actual DB records |
| 9 | Student details page | Full history + chart appear |
| 10 | Submit invalid input (e.g. attendance = 150) | PHP rejects it with a message |
| 11 | Visit `result.php?id=99999` | Friendly "not found" message, no crash |
| 12 | Submit a form with a missing/invalid CSRF token | Request is rejected |

---

## 12. Viva Questions & Answers

1. **Why did you use PHP?**
   It's simple, runs natively on Apache via XAMPP, and doesn't need a separate framework
   for a project this size.

2. **Why did you use MySQL?**
   It's a relational database, works well with structured data like students and their
   performance records, and integrates natively with PHP through PDO.

3. **What is PDO?**
   PHP Data Objects — a database access layer that supports prepared statements and
   works consistently across different database drivers.

4. **What is a prepared statement?**
   A SQL query with placeholders (`?`) for values. The database compiles the query
   structure first, then safely binds user values — this prevents SQL injection because
   user input is never interpreted as SQL code.

5. **What is a primary key?**
   A column that uniquely identifies each row in a table (e.g. `id` in `students`).

6. **What is a foreign key?**
   A column that references a primary key in another table, enforcing that the
   relationship stays valid (e.g. `students.course_id` must exist in `courses.id`).

7. **Explain the relationship between students and performance.**
   One student can have many performance/prediction records — a one-to-many relationship,
   linked through `student_performance.student_id`.

8. **Why is `ON DELETE CASCADE` used?**
   So that deleting a student automatically removes their performance history too,
   instead of leaving orphaned rows that reference a student that no longer exists.

9. **Why can multiple predictions exist for one semester?**
   Because the system is designed to preserve prediction history rather than overwrite
   it — useful if data is re-entered or updated partway through a semester.

10. **How does the prediction formula work?**
    It's a weighted sum of five factors (study hours, attendance, previous marks,
    assignment marks, internal marks), each contributing a fixed percentage to the
    final predicted score.

11. **Why do study hours need normalization?**
    Study hours are measured 0–24, while all other inputs are already percentages
    (0–100). Normalizing puts them on the same scale before applying weights.

12. **What is model version?**
    A label (`v1.0`) stored with every prediction so that if the formula is ever
    changed later, old predictions remain traceable to the formula version that
    produced them.

13. **Why is this not a true ML model?**
    It uses fixed, manually chosen weights rather than weights learned from training
    data — there's no training process, so it can't adapt or improve from data.

14. **What is CSRF?**
    Cross-Site Request Forgery — an attack where a malicious site tricks a logged-in
    user's browser into submitting a request they didn't intend. A CSRF token (a random
    value tied to the session) proves the form was really submitted from this app.

15. **Why do we validate data on both client and server?**
    Client-side (JavaScript) validation gives instant feedback and a better user
    experience, but it can be bypassed (disabled JS, direct requests). Server-side
    (PHP) validation is the real safeguard and always runs regardless of what the
    browser does.

16. **Why is there no login system?**
    This is a local, single-user lab project — the person running XAMPP already has
    full access to the database, so a login layer wouldn't add real security here.

17. **How is "latest prediction" determined for a student?**
    A correlated subquery selects the performance row with the most recent
    `created_at` (using `id` as a tiebreaker) for that specific student.

18. **What does `htmlspecialchars()` do and why use it?**
    It converts special characters like `<`, `>`, and `"` into safe HTML entities
    before printing user data, preventing stored/reflected XSS attacks.

19. **What happens if MySQL's CHECK constraints aren't supported?**
    The application doesn't rely on them — PHP independently validates every numeric
    range before insertion, so data integrity holds either way.

20. **Why use POST instead of GET for delete/add/edit actions?**
    GET requests can be triggered accidentally (e.g. by a browser prefetch, a shared
    link, or a crawler) and shouldn't have side effects. POST is the correct method
    for any action that changes data.
#   S t u d e n t - p e r f o r m a n c e - p r e d i c t i o n  
 