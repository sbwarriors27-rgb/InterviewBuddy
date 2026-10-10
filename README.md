# Interview Buddy

A centralized platform for placement interview preparation: students upload real interview
experiences, an admin verifies and organizes them into a structured question bank, and students
can take AI-evaluated mock interviews built from that verified bank.

## Tech Stack
- **Frontend:** HTML, CSS, JavaScript
- **Backend:** PHP (procedural, PDO for MySQL)
- **Database:** MySQL
- **Answer scoring:** Explainable, local reference-concept rubric in PHP (also exposed by optional Flask API)

---

## 1. Setup (XAMPP)

1. Copy the whole `InterviewBuddy` folder into your XAMPP `htdocs` directory, so it's reachable at:
   `http://localhost/InterviewBuddy/`
2. Start **Apache** and **MySQL** from the XAMPP Control Panel.
3. Open **phpMyAdmin** (`http://localhost/phpmyadmin`).
4. Click **Import**, choose `database/interviewbuddy.sql`, and run it.
   This creates the `interviewbuddy` database, all tables, and seed data
   (4 sample companies, 7 subjects, common topics, and a default admin account).
5. Check `config/database.php` — defaults (`localhost` / `root` / no password) match a stock
   XAMPP install. Edit if your MySQL credentials differ.

### Default logins
| Role    | Email                        | Password  |
|---------|-------------------------------|-----------|
| Admin   | admin@interviewbuddy.com      | admin123  |
| Student | (register your own via the app) | — |

> Change the admin password after first login using `database/generate_admin_hash.php`
> (open it in the browser, copy the hash, run an `UPDATE admin SET password='<hash>' ...` in
> phpMyAdmin), then delete that file.

---

## 2. Setup the ML Service (Flask)

Mock-interview scoring runs locally in PHP and does not require cURL or Flask. The optional Flask
API exposes the same reference-concept scoring policy for API consumers:

```bash
cd InterviewBuddy/ml
pip install -r requirements.txt
python app.py
```

This starts the API at `http://127.0.0.1:5000`. Keep this terminal running alongside XAMPP while
you use the app. `config/database.php`/`api/send_to_ml.php` already points at this address.

The existing Ridge model and starter training data are not used as proof of correctness. Answers
without an approved reference are stored as unevaluated (NULL score), not assigned a default mark.
Add or update question references in the Admin **Question Bank** before expecting those answers to
contribute to scores.

---

## 3. Using the App

1. Visit `http://localhost/InterviewBuddy/` → **Register** as a student.
2. **Upload Interview Experience** — add a company, role, and questions (technical/HR/aptitude/coding).
3. Log in as **admin** (`/InterviewBuddy/admin/login.php`) → **Pending Experiences** → Approve the
   submission → **Approve Questions** → categorize by subject/difficulty and add an ideal answer.
4. Back as a student → **Mock Interview** → pick a company/role → answer the generated questions →
   Submit. Each answer is assessed against its admin-approved reference; the **Result** page shows
   the saved per-answer score, concept feedback, reference answer, and aggregates only evaluated
   responses.
5. **Dashboard** and **History** show performance over time; **Search Questions** lets students
   browse/bookmark the approved question bank directly.

---

## Folder Structure
The local evaluator compares reference concepts using a conservative lexical rubric. It is not a
semantic model and cannot reliably validate every paraphrase or domain-specific fact; admins should
review and maintain the reference answers. It deliberately avoids the old Ridge prediction and
default-neutral score.

See the project layout in the repo — `admin/` and `student/` hold the two portals, `config/` has
DB/session/auth bootstrap, `includes/` has shared header/footer/sidebar/functions, `api/` bridges
PHP ↔ the Flask ML service, and `ml/` contains the Python evaluation model.

## Notes / Next Steps
- This is a functional MVP covering the full workflow end-to-end. Consider adding: file/resume
  uploads (folder is scaffolded at `uploads/`), pagination on large tables, CSRF tokens on forms,
  and richer ML scoring (e.g. sentence-transformer embeddings) as you extend it.
