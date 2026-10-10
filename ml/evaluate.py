"""Conservative reference-based scoring shared with the PHP mock-test flow.

This is an explainable lexical rubric, not a semantic or factual-reasoning model.
It only credits reference concepts supported by multiple matching terms and
discounts answers padded with unrelated content.
"""

import math
import re

STOP_WORDS = set(
    "a an the is are was were be been being of to for in on at by with from as "
    "it this that these those into than then which who how what when where why "
    "and or but so because while whereas meaning also can may might should "
    "would could will do does did has have had its their your our i we you they "
    "he she them us me my very more most some such only each all about after "
    "before across using used use another related together within "
    "both per if no yes mean"
    .split()
)
CONTRADICTION_PAIRS = (
    ("lifo", "fifo"),
    ("yes", "no"),
    ("same", "different"),
    ("increase", "decrease"),
    ("higher", "lower"),
    ("more", "less"),
    ("before", "after"),
    ("reliable", "unreliable"),
    ("ordered", "unordered"),
    ("true", "false"),
)
CLAUSE_SPLIT = re.compile(
    r"[.!?;]+|\b(?:which\s+means|while|whereas|but|and|so|because|meaning)\b",
    re.IGNORECASE,
)


def _normalized_text(text: str) -> str:
    text = (text or "").strip().lower()
    text = re.sub(r"\blast\s+in\s+first\s+out\b", " lifo ", text)
    text = re.sub(r"\bfirst\s+in\s+first\s+out\b", " fifo ", text)
    return text.replace("contiguously", " contiguous ").replace("contiguous", " contiguous ")


def _tokens(text: str) -> list[str]:
    tokens = re.findall(r"[a-z]+|\d+(?:\.\d+)?", _normalized_text(text))
    normalized = []
    for token in tokens:
        if len(token) > 4 and token.endswith("ies"):
            token = token[:-3] + "y"
        elif len(token) > 4 and token.endswith("ing"):
            token = token[:-3]
        elif len(token) > 3 and token.endswith("ed"):
            token = token[:-2]
        elif len(token) > 3 and token.endswith("s"):
            token = token[:-1]
        if token not in STOP_WORDS:
            normalized.append(token)
    return normalized


def _clauses(text: str) -> list[str]:
    return [clause.strip() for clause in CLAUSE_SPLIT.split(_normalized_text(text)) if clause.strip()]


def _is_meaningful(answer: str) -> bool:
    answer = (answer or "").strip().lower()
    if not answer:
        return False
    compact = re.sub(r"[^a-z0-9]", "", answer)
    if re.fullmatch(r"(.)\1{5,}", compact):
        return False
    letters = "".join(re.findall(r"[a-z]+", answer))
    if len(letters) >= 8 and sum(char in "aeiou" for char in letters) / len(letters) < 0.12:
        return False
    return bool(_tokens(answer))


def _concepts(reference_answer: str) -> list[dict]:
    concepts = []
    for clause in _clauses(reference_answer):
        tokens = list(dict.fromkeys(_tokens(clause)))
        if len(tokens) == 1 and tokens[0] in {"no", "yes"}:
            continue
        if tokens:
            concepts.append({"text": clause, "tokens": tokens})
    return concepts


def _contrast_tokens(text: str) -> list[str]:
    text = (text or "").strip().lower()
    text = re.sub(r"\blast\s+in\s+first\s+out\b", " lifo ", text)
    text = re.sub(r"\bfirst\s+in\s+first\s+out\b", " fifo ", text)
    return re.findall(r"[a-z]+|\d+(?:\.\d+)?", text)


def _has_shared_phrase(reference_tokens: list[str], answer_clauses: list[list[str]]) -> bool:
    if len(reference_tokens) == 1:
        return any(reference_tokens[0] in clause for clause in answer_clauses)
    return any(
        reference_tokens[index : index + 2] == clause[position : position + 2]
        for index in range(len(reference_tokens) - 1)
        for clause in answer_clauses
        for position in range(len(clause) - 1)
    )


def _result(score, status, feedback, correct=None, missing=None) -> dict:
    return {
        "score": score,
        "status": status,
        "feedback": feedback,
        "correct_concepts": correct or [],
        "missing_concepts": missing or [],
    }


def evaluate_answer(student_answer: str, ideal_answer: str, question: str = "") -> dict:
    """Return a 0-100 score, or ``None`` when a reference is not evaluable."""
    student_answer = (student_answer or "").strip()
    ideal_answer = (ideal_answer or "").strip()
    question = (question or "").strip()

    if not student_answer:
        return _result(
            0.0, "evaluated", "No answer was submitted. The answer receives 0/100."
        )
    if not _is_meaningful(student_answer):
        return _result(
            0.0,
            "evaluated",
            "Your response does not contain a meaningful answer to the question. "
            "Review the reference answer and try again. Score: 0/100.",
        )
    if not ideal_answer:
        return _result(
            None,
            "unavailable",
            "Evaluation unavailable — reference answer missing.",
        )

    concepts = _concepts(ideal_answer)
    answer_tokens = _tokens(student_answer)
    answer_token_set = set(answer_tokens)
    answer_clauses = [_tokens(clause) for clause in _clauses(student_answer)]
    answer_clauses = [clause for clause in answer_clauses if clause]
    reference_token_set = {
        token for concept in concepts for token in concept["tokens"]
    }
    if len(reference_token_set) < 2:
        return _result(
            None,
            "unavailable",
            "Evaluation unavailable — the reference answer does not contain "
            "enough detail for a reliable rubric.",
        )

    answer_contrast_set = set(_contrast_tokens(student_answer))
    reference_contrast_set = set(_contrast_tokens(ideal_answer))
    answer_has_contradiction = False
    reference_has_contradiction = False
    for first, second in CONTRADICTION_PAIRS:
        if (first in reference_contrast_set and second in answer_contrast_set) or (
            second in reference_contrast_set and first in answer_contrast_set
        ):
            answer_has_contradiction = True
        if first in reference_contrast_set and second in reference_contrast_set:
            reference_has_contradiction = True
    contradictory = answer_has_contradiction and not reference_has_contradiction

    correct_concepts = []
    missing_concepts = []
    credited_tokens = 0
    total_reference_tokens = 0
    for concept in concepts:
        tokens = concept["tokens"]
        total_reference_tokens += len(tokens)
        matched = [token for token in tokens if token in answer_token_set]
        phrase = _has_shared_phrase(tokens, answer_clauses)
        required_matches = max(2, math.ceil(len(tokens) * 0.5))
        supported = len(matched) >= required_matches and (
            phrase or len(matched) >= math.ceil(len(tokens) * 0.75)
        )
        if supported:
            correct_concepts.append(concept["text"])
            credited_tokens += len(matched)
        if not supported or len(matched) < len(tokens):
            missing_concepts.append(concept["text"])

    matched_answer_tokens = len(answer_token_set & reference_token_set)
    precision = matched_answer_tokens / len(answer_token_set) if answer_token_set else 0
    score = (
        0.0
        if contradictory or not total_reference_tokens
        else round(
            100 * (credited_tokens / total_reference_tokens) * precision, 1
        )
    )
    score = min(100.0, max(0.0, score))

    question_context = f'For "{question}", ' if question else ""
    if score >= 90:
        opening = question_context + "your answer covers nearly all concepts in the reference."
    elif score > 0:
        opening = (
            question_context
            + "your answer demonstrates some of the concepts in the reference, but is incomplete."
        )
    else:
        opening = question_context + "your answer does not demonstrate the key concepts in the reference."
    correct_text = "; ".join(correct_concepts) if correct_concepts else "none identified"
    missing_text = "; ".join(missing_concepts) if missing_concepts else "none identified"
    feedback = (
        f"{opening} Score: {score:.1f}/100. Correct concepts: {correct_text}. "
        f"Missing or unclear concepts: {missing_text}."
    )
    if contradictory:
        feedback += " The answer also states a concept that conflicts with the reference."

    return _result(score, "evaluated", feedback, correct_concepts, missing_concepts)
