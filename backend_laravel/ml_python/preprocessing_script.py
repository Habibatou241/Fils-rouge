import pandas as pd
import json
import sys
import os
import numpy as np

def convert_numpy(obj):
    if isinstance(obj, (np.integer, np.floating)):
        return obj.item()
    elif isinstance(obj, np.ndarray):
        return obj.tolist()
    return str(obj)

def clean_dataset(file_path):
    try:
        df = pd.read_csv(file_path)
        df_cleaned = df.dropna()
        summary = {
            "initial_shape": df.shape,
            "cleaned_shape": df_cleaned.shape,
            "rows_removed": df.shape[0] - df_cleaned.shape[0]
        }
        output_path = file_path.replace(".csv", "_cleaned.csv")
        df_cleaned.to_csv(output_path, index=False)
        return {"file_path": output_path, "summary": summary}
    except Exception as e:
        raise Exception(f"Cleaning error: {str(e)}")

def fill_missing_values(file_path, method):
    try:
        df = pd.read_csv(file_path)
        if method == "mean":
            df_filled = df.fillna(df.mean(numeric_only=True))
        elif method == "median":
            df_filled = df.fillna(df.median(numeric_only=True))
        elif method == "mode":
            df_filled = df.fillna(df.mode().iloc[0])
        else:
            raise ValueError("Invalid fill method")

        summary = {
            "missing_before": df.isnull().sum().sum(),
            "missing_after": df_filled.isnull().sum().sum(),
            "method_used": method
        }

        output_path = file_path.replace(".csv", f"_{method}_filled.csv")
        df_filled.to_csv(output_path, index=False)
        return {"file_path": output_path, "summary": summary}
    except Exception as e:
        raise Exception(f"Filling error: {str(e)}")

def apply_scaling(file_path, method):
    try:
        df = pd.read_csv(file_path)
        numeric_cols = df.select_dtypes(include='number').columns.tolist()

        if not numeric_cols:
            raise ValueError("No numeric columns found for scaling.")

        df_scaled = df.copy()

        if method == "normalization":
            for col in numeric_cols:
                min_val = df[col].min()
                max_val = df[col].max()
                if max_val - min_val == 0:
                    df_scaled[col] = 0
                else:
                    df_scaled[col] = (df[col] - min_val) / (max_val - min_val)

        elif method == "standardization":
            for col in numeric_cols:
                mean = df[col].mean()
                std = df[col].std()
                if std == 0:
                    df_scaled[col] = 0
                else:
                    df_scaled[col] = (df[col] - mean) / std
        else:
            raise ValueError("Invalid scaling method. Use 'normalization' or 'standardization'.")

        summary = {
            "method": method,
            "scaled_columns": numeric_cols,
            "original_shape": df.shape,
            "scaled_shape": df_scaled.shape
        }

        output_path = file_path.replace(".csv", f"_{method}_scaled.csv")
        df_scaled.to_csv(output_path, index=False)
        return {"file_path": output_path, "summary": summary}

    except Exception as e:
        raise Exception(f"Scaling error: {str(e)}")

def main():
    try:
        if len(sys.argv) < 3:
            raise Exception("Usage: python preprocessing_script.py <file_path> <type> [method]")

        file_path = sys.argv[1]
        preprocess_type = sys.argv[2]
        method = sys.argv[3] if len(sys.argv) > 3 else None

        if not os.path.exists(file_path):
            raise FileNotFoundError(f"Le fichier {file_path} n'existe pas.")

        if preprocess_type == "cleaning":
            result = clean_dataset(file_path)
        elif preprocess_type == "fill":
            if not method:
                raise Exception("Fill method is required (mean, median, mode).")
            result = fill_missing_values(file_path, method)
        elif preprocess_type == "scaling":
            if not method:
                raise Exception("Scaling method is required (normalization, standardization).")
            result = apply_scaling(file_path, method)
        else:
            raise Exception("Invalid preprocessing type")

        sys.stdout.write(json.dumps(result, ensure_ascii=False, default=convert_numpy))
    except Exception as e:
        sys.stderr.write(json.dumps({"error": str(e)}, ensure_ascii=False))

if __name__ == "__main__":
    main()
