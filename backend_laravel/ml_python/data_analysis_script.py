import sys
import pandas as pd
import json
import os

def analyze_data(file_path):
    try:
        df = pd.read_csv(file_path)

        # Informations générales
        summary = {
            "shape": {"rows": df.shape[0], "columns": df.shape[1]},
            "columns": list(df.columns),
            "dtypes": df.dtypes.astype(str).to_dict(),
            "missing_values": df.isnull().sum().to_dict(),
            "describe": df.describe(include='all').fillna("").to_dict()
        }

        # Corrélation numérique (si possible)
        numeric_df = df.select_dtypes(include=['number'])
        if not numeric_df.empty:
            corr_matrix = numeric_df.corr().round(3).to_dict()
            summary["correlation_matrix"] = corr_matrix
        else:
            summary["correlation_matrix"] = {}

        return summary

    except Exception as e:
        return {"error": str(e)}

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"error": "No file path provided"}))
        sys.exit(1)

    file_path = sys.argv[1]

    if not os.path.exists(file_path):
        print(json.dumps({"error": "File not found"}))
        sys.exit(1)

    result = analyze_data(file_path)
    print(json.dumps(result, ensure_ascii=False, indent=2))
