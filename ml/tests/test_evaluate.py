import json
import shutil
import subprocess
import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from evaluate import evaluate_answer
from app import app


STACK_QUESTION = "Explain the LIFO principle of a stack."
STACK_REFERENCE = (
    "A stack follows LIFO, meaning the last element inserted is the first "
    "element removed."
)


class AnswerEvaluationTests(unittest.TestCase):
    def test_empty_answer_scores_zero(self):
        self.assertEqual(evaluate_answer("", STACK_REFERENCE)["score"], 0)

    def test_repeated_letters_score_zero(self):
        self.assertEqual(evaluate_answer("aaaaaaaaaaaa", STACK_REFERENCE)["score"], 0)

    def test_repeated_digits_score_zero(self):
        self.assertEqual(evaluate_answer("111111111111", STACK_REFERENCE)["score"], 0)

    def test_gibberish_scores_zero(self):
        self.assertEqual(evaluate_answer("qwrtypsdfghjkl", STACK_REFERENCE)["score"], 0)

    def test_unrelated_answer_scores_zero(self):
        result = evaluate_answer("Photosynthesis converts sunlight into energy.", STACK_REFERENCE)
        self.assertEqual(result["score"], 0)
        self.assertEqual(result["status"], "evaluated")

    def test_contradictory_answer_scores_zero(self):
        reference = "A queue follows FIFO, meaning the first item inserted is the first item removed."
        result = evaluate_answer("A queue follows LIFO, the last item inserted is removed first.", reference)
        self.assertEqual(result["score"], 0)

    def test_wrong_aptitude_answer_scores_zero(self):
        reference = "No. The final price becomes 96% of the original price, so it is 4% lower."
        result = evaluate_answer("Yes, the final price stays the same.", reference)
        self.assertEqual(result["score"], 0)

    def test_partial_answer_gets_partial_credit(self):
        result = evaluate_answer("A stack follows LIFO.", STACK_REFERENCE, STACK_QUESTION)
        self.assertGreater(result["score"], 0)
        self.assertLess(result["score"], 100)
        self.assertTrue(result["correct_concepts"])
        self.assertTrue(result["missing_concepts"])
        self.assertIn(STACK_QUESTION, result["feedback"])

    def test_correct_but_incomplete_answer_names_missing_concepts(self):
        reference = (
            "An array stores elements in contiguous memory and provides "
            "efficient indexed access."
        )
        result = evaluate_answer("An array stores elements in contiguous memory.", reference)
        self.assertGreater(result["score"], 0)
        self.assertLess(result["score"], 100)
        self.assertTrue(result["missing_concepts"])

    def test_fully_correct_technical_answer_scores_high(self):
        result = evaluate_answer(STACK_REFERENCE, STACK_REFERENCE)
        self.assertGreaterEqual(result["score"], 90)

    def test_long_irrelevant_padding_does_not_score_high(self):
        filler = " ".join(f"unrelatedtopic{index}" for index in range(30))
        result = evaluate_answer(f"{STACK_REFERENCE} {filler}", STACK_REFERENCE)
        self.assertLess(result["score"], 80)

    def test_missing_reference_is_unavailable(self):
        result = evaluate_answer("A stack follows LIFO.", "")
        self.assertIsNone(result["score"])
        self.assertEqual(result["status"], "unavailable")
        self.assertEqual(result["feedback"], "Evaluation unavailable — reference answer missing.")

    def test_representative_aptitude_and_hr_answers(self):
        aptitude = (
            "No. The final price becomes 96% of the original price, so it is 4% lower."
        )
        hr = (
            "I would listen to the teammate, clarify the shared goal, discuss evidence "
            "and alternatives calmly, and agree on a practical solution."
        )
        self.assertGreaterEqual(evaluate_answer(aptitude, aptitude)["score"], 90)
        self.assertGreaterEqual(evaluate_answer(hr, hr)["score"], 90)

    def test_flask_endpoint_uses_same_scoring_policy(self):
        client = app.test_client()
        result = client.post(
            "/evaluate",
            json={
                "student_answer": "aaaaaaaaaaaa",
                "ideal_answer": STACK_REFERENCE,
                "question": STACK_QUESTION,
            },
        )
        self.assertEqual(result.status_code, 200)
        self.assertEqual(result.get_json()["score"], 0)
        unavailable = client.post(
            "/evaluate",
            json={"student_answer": "A stack follows LIFO.", "ideal_answer": ""},
        )
        self.assertIsNone(unavailable.get_json()["score"])
        self.assertEqual(unavailable.get_json()["status"], "unavailable")

    def test_flask_endpoint_rejects_non_string_inputs(self):
        result = app.test_client().post(
            "/evaluate", json={"student_answer": ["not", "text"], "ideal_answer": ""}
        )
        self.assertEqual(result.status_code, 400)

    @unittest.skipUnless(shutil.which("php"), "PHP CLI is not available")
    def test_php_fallback_works_without_the_flask_service(self):
        test_cases = [
            ("", STACK_REFERENCE, STACK_QUESTION),
            ("aaaaaaaaaaaa", STACK_REFERENCE, STACK_QUESTION),
            ("111111111111", STACK_REFERENCE, STACK_QUESTION),
            ("qwrtypsdfghjkl", STACK_REFERENCE, STACK_QUESTION),
            ("Photosynthesis converts sunlight into energy.", STACK_REFERENCE, STACK_QUESTION),
            ("A stack follows LIFO.", STACK_REFERENCE, STACK_QUESTION),
            (STACK_REFERENCE, STACK_REFERENCE, STACK_QUESTION),
            ("A stack follows LIFO.", "", STACK_QUESTION),
        ]
        php_file = (Path(__file__).resolve().parents[2] / "api" / "send_to_ml.php")
        php_file = str(php_file).replace("\\", "/").replace("'", "\\'")
        program = (
            "$cases=json_decode(stream_get_contents(STDIN),true);"
            f"require '{php_file}';"
            "$results=[];"
            "foreach($cases as $case){$results[]=evaluateAnswer($case[0],$case[1],$case[2]);}"
            "echo json_encode($results);"
        )
        process = subprocess.run(
            ["php", "-r", program],
            input=json.dumps(test_cases),
            capture_output=True,
            text=True,
            check=True,
        )
        php_results = json.loads(process.stdout)
        python_results = [
            evaluate_answer(answer, reference, question)
            for answer, reference, question in test_cases
        ]
        for php_result, python_result in zip(php_results, python_results):
            self.assertEqual(php_result["score"], python_result["score"])
            self.assertEqual(php_result["status"], python_result["status"])
            self.assertEqual(php_result["feedback"], python_result["feedback"])

    @unittest.skipUnless(shutil.which("php"), "PHP CLI is not available")
    def test_php_persists_only_evaluated_scores_and_consistent_aggregate(self):
        php_file = (Path(__file__).resolve().parents[2] / "api" / "send_to_ml.php")
        php_file = str(php_file).replace("\\", "/").replace("'", "\\'")
        reference_sql = "'" + STACK_REFERENCE.replace("'", "''") + "'"
        program = f"""
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->sqliteCreateFunction('NOW', function () {{ return date('Y-m-d H:i:s'); }});
        $pdo->exec("CREATE TABLE subjects (subject_id INTEGER PRIMARY KEY, subject_name TEXT)");
        $pdo->exec("CREATE TABLE questions (question_id INTEGER PRIMARY KEY, subject_id INTEGER, question TEXT)");
        $pdo->exec("CREATE TABLE answers (question_id INTEGER PRIMARY KEY, ideal_answer TEXT)");
        $pdo->exec("CREATE TABLE responses (response_id INTEGER PRIMARY KEY, mock_id INTEGER, question_id INTEGER, student_answer TEXT, score REAL, feedback TEXT)");
        $pdo->exec("CREATE TABLE results (result_id INTEGER PRIMARY KEY AUTOINCREMENT, mock_id INTEGER UNIQUE, overall_score REAL, subject_wise_score TEXT, strengths TEXT, weaknesses TEXT, suggestions TEXT)");
        $pdo->exec("CREATE TABLE mock_tests (mock_id INTEGER PRIMARY KEY, status TEXT, end_time TEXT, score REAL)");
        $pdo->exec("INSERT INTO subjects VALUES (1, 'DSA')");
        $pdo->exec("INSERT INTO questions VALUES (1, 1, 'Explain the LIFO principle of a stack.'), (2, 1, 'Question without a reference'), (3, 1, 'Explain the LIFO principle of a stack.')");
        $pdo->exec("INSERT INTO answers VALUES (1, {reference_sql}), (3, {reference_sql})");
        $pdo->exec("INSERT INTO responses VALUES (1, 1, 1, {reference_sql}, NULL, NULL), (2, 1, 2, 'A meaningful answer with no reference.', NULL, NULL), (3, 1, 3, 'The moon orbits Earth.', NULL, NULL)");
        $pdo->exec("INSERT INTO mock_tests VALUES (1, 'In Progress', NULL, NULL)");
        require '{php_file}';
        $overall = evaluateMockTest($pdo, 1);
        echo json_encode([
            'responses' => $pdo->query('SELECT score, feedback FROM responses ORDER BY response_id')->fetchAll(PDO::FETCH_ASSOC),
            'result' => $pdo->query('SELECT overall_score FROM results WHERE mock_id=1')->fetchColumn(),
            'mock' => $pdo->query('SELECT score FROM mock_tests WHERE mock_id=1')->fetchColumn(),
            'overall' => $overall
        ]);
        """
        process = subprocess.run(
            ["php", "-r", program],
            capture_output=True,
            text=True,
            check=True,
        )
        persisted = json.loads(process.stdout)
        self.assertEqual([row["score"] for row in persisted["responses"]], [100, None, 0])
        self.assertEqual(persisted["responses"][1]["feedback"], "Evaluation unavailable — reference answer missing.")
        self.assertEqual(persisted["result"], persisted["mock"])
        self.assertEqual(persisted["result"], persisted["overall"])
        self.assertEqual(persisted["result"], 50)
