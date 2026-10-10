"""
Interview Buddy - ML Evaluation API
-------------------------------------
Run with:  python app.py
Listens on http://127.0.0.1:5000

Endpoint:
  POST /evaluate
  Body (JSON): { "student_answer": "...", "ideal_answer": "...", "question": "..." }
  Response:    { "score": 82.5, "status": "evaluated", "feedback": "..." }
"""

from flask import Flask, request, jsonify
from evaluate import evaluate_answer

app = Flask(__name__)

# Allow simple cross-origin calls (useful if you ever call this directly from JS)
@app.after_request
def add_cors_headers(response):
    response.headers["Access-Control-Allow-Origin"] = "*"
    response.headers["Access-Control-Allow-Headers"] = "Content-Type"
    response.headers["Access-Control-Allow-Methods"] = "POST, OPTIONS"
    return response


@app.route("/evaluate", methods=["POST", "OPTIONS"])
def evaluate():
    if request.method == "OPTIONS":
        return jsonify({}), 200

    data = request.get_json(silent=True)
    if not isinstance(data, dict):
        return jsonify({"error": "A JSON object is required."}), 400

    student_answer = data.get("student_answer", "")
    ideal_answer = data.get("ideal_answer", "")
    question = data.get("question", "")
    if not all(isinstance(value, str) for value in (student_answer, ideal_answer, question)):
        return jsonify({"error": "Answer, reference answer, and question must be strings."}), 400

    result = evaluate_answer(student_answer, ideal_answer, question)
    return jsonify(result)


@app.route("/health", methods=["GET"])
def health():
    return jsonify({"status": "ok", "service": "Interview Buddy ML API"})


if __name__ == "__main__":
    app.run(host="127.0.0.1", port=5000, debug=False)
