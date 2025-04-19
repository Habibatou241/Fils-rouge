import pandas as pd
import json
import sys

# For UTF-8 stdout (Python 3.7+)
if hasattr(sys.stdout, "reconfigure"):
    sys.stdout.reconfigure(encoding='utf-8')

def apply_cleaning(file_path):
    try:
        try:
            df = pd.read_csv(file_path, encoding='utf-8')
        except UnicodeDecodeError:
            df = pd.read_csv(file_path, encoding='ISO-8859-1')
        except pd.errors.ParserError:
            df = pd.read_csv(file_path, encoding='utf-8', sep=';')

        df_cleaned = df.dropna()

        for col in df_cleaned.select_dtypes(include=['float']).columns:
            if (df_cleaned[col] % 1 == 0).all():
                df_cleaned[col] = df_cleaned[col].astype(int)

        summary = {
            "original_rows": len(df),
            "cleaned_rows": len(df_cleaned),
            "dropped_rows": len(df) - len(df_cleaned),
            "columns": df_cleaned.columns.tolist()
        }

        cleaned_file_path = file_path.replace(".csv", "_cleaned.csv")
        df_cleaned.to_csv(cleaned_file_path, index=False, encoding='utf-8')

        result = {
            "file_path": cleaned_file_path,
            "summary": summary
        }

        safe_output = json.dumps(result, ensure_ascii=False)
        sys.stdout.write(safe_output.encode('utf-8', 'replace').decode('utf-8'))

    except Exception as e:
        safe_error = json.dumps({"error": str(e)}, ensure_ascii=False)
        sys.stderr.write(safe_error.encode('utf-8', 'replace').decode('utf-8'))

def main():
    if len(sys.argv) != 3:
        safe_error = json.dumps({"error": "Invalid arguments. Expected: <file_path> <preprocessing_type>"}, ensure_ascii=False)
        sys.stderr.write(safe_error.encode('utf-8', 'replace').decode('utf-8'))
        sys.exit(1)

    file_path = sys.argv[1]
    preprocessing_type = sys.argv[2]

    if preprocessing_type == "cleaning":
        apply_cleaning(file_path)
    else:
        safe_error = json.dumps({"error": f"Unknown preprocessing type: {preprocessing_type}"}, ensure_ascii=False)
        sys.stderr.write(safe_error.encode('utf-8', 'replace').decode('utf-8'))
        sys.exit(1)

if __name__ == "__main__":
    main()