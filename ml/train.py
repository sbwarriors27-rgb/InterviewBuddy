"""Train the answer evaluation model."""

import argparse
import csv
from pathlib import Path

from joblib import dump
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.linear_model import Ridge
from sklearn.pipeline import FeatureUnion, Pipeline


def load_dataset(path: Path):
    texts = []
    scores = []
    with path.open("r", encoding="utf-8", newline="") as handle:
        for row in csv.DictReader(handle):
            student = row["student_answer"].strip()
            ideal = row["ideal_answer"].strip()
            if student and ideal:
                texts.append(f"student: {student} ideal: {ideal}")
                scores.append(float(row["score"]))
    if len(texts) < 4:
        raise ValueError("The training dataset must contain at least four valid rows.")
    return texts, scores


def build_model():
    features = FeatureUnion([
        ("word", TfidfVectorizer(ngram_range=(1, 2), sublinear_tf=True)),
        ("character", TfidfVectorizer(analyzer="char_wb", ngram_range=(3, 5), sublinear_tf=True)),
    ])
    return Pipeline([("features", features), ("regressor", Ridge(alpha=2.0))])


def main():
    parser = argparse.ArgumentParser(description="Train Interview Buddy answer scoring model")
    parser.add_argument("--data", type=Path, default=Path(__file__).parent / "data" / "answer_evaluation.csv")
    parser.add_argument("--output", type=Path, default=Path(__file__).parent / "model.joblib")
    args = parser.parse_args()
    texts, scores = load_dataset(args.data)
    model = build_model()
    model.fit(texts, scores)
    args.output.parent.mkdir(parents=True, exist_ok=True)
    dump(model, args.output)
    print(f"Trained on {len(texts)} examples and saved {args.output}")


if __name__ == "__main__":
    main()