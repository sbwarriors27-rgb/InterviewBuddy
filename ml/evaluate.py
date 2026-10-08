"""
Interview Buddy - Answer Evaluation Model
-------------------------------------------
Compares a student's answer against the administrator-approved ideal answer
and produces a similarity-based score (0-100) plus short feedback.

Primary method: TF-IDF vectorization + cosine similarity (scikit-learn).
Fallback method (if scikit-learn isn't installed): difflib sequence matching.
"""

import re
from pathlib import Path

try:
    from sklearn.feature_extraction.text import TfidfVectorizer
    from sklearn.metrics.pairwise import cosine_similarity
    SKLEARN_AVAILABLE = True
except ImportError:
    SKLEARN_AVAILABLE = False

import difflib

try:
    from joblib import load
except ImportError:
    load = None


MODEL_PATH = Path(__file__).with_name("model.joblib")
TRAINED_MODEL = load(MODEL_PATH) if load and MODEL_PATH.exists() else None


def _clean(text: str) -> str:
    text = text or ""
    text = text.lower().strip()
    text = re.sub(r"\s+", " ", text)
    return text


def _tfidf_similarity(student_answer: str, ideal_answer: str) -> float:
    vectorizer = TfidfVectorizer(stop_words="english")
    tfidf = vectorizer.fit_transform([student_answer, ideal_answer])
    sim = cosine_similarity(tfidf[0:1], tfidf[1:2])[0][0]
    return float(sim)


def _difflib_similarity(student_answer: str, ideal_answer: str) -> float:
    return difflib.SequenceMatcher(None, student_answer, ideal_answer).ratio()


def _trained_score(student_answer: str, ideal_answer: str) -> float:
    text = f"student: {student_answer} ideal: {ideal_answer}"
    return float(TRAINED_MODEL.predict([text])[0])


def evaluate_answer(student_answer: str, ideal_answer: str) -> dict:
    """
    Returns:
        {
          "score": float (0-100),
          "feedback": str
        }
    """
    student_clean = _clean(student_answer)
    ideal_clean = _clean(ideal_answer)

    if not student_clean:
        return {"score": 0.0, "feedback": "No answer was submitted for this question."}

    if not ideal_clean:
        return {"score": 50.0, "feedback": "No ideal answer on file yet — scored as neutral."}

    try:
        if TRAINED_MODEL is not None:
            similarity = max(0.0, min(100.0, _trained_score(student_clean, ideal_clean))) / 100.0
        elif SKLEARN_AVAILABLE and len(student_clean.split()) > 1 and len(ideal_clean.split()) > 1:
            similarity = _tfidf_similarity(student_clean, ideal_clean)
        else:
            similarity = _difflib_similarity(student_clean, ideal_clean)
    except ValueError:
        # e.g. TF-IDF vocabulary ends up empty (answers are only stopwords)
        similarity = _difflib_similarity(student_clean, ideal_clean)

    score = round(min(1.0, max(0.0, similarity)) * 100, 1)

    if score >= 75:
        feedback = "Excellent! Your answer closely matches the expected response."
    elif score >= 50:
        feedback = "Good attempt, but some key points are missing compared to the ideal answer."
    elif score >= 25:
        feedback = "Partially correct. Review this topic and try to include more relevant details."
    else:
        feedback = "Your answer differs significantly from the ideal answer. Revise this concept."

    return {"score": score, "feedback": feedback}
