import pandas as pd
import json
import sys

def apply_cleaning(file_path):
    """
    Clean the dataset by removing rows with NaN values and convert columns to integer if applicable.
    """
    try:
        # Load the dataset
        df = pd.read_csv(file_path)

        # Drop rows with NaN values
        df_cleaned = df.dropna()

        # Convert columns with float type to int if all values are whole numbers
        for col in df_cleaned.select_dtypes(include=['float']).columns:
            if (df_cleaned[col] % 1 == 0).all():  # Check if all values are integers
                df_cleaned[col] = df_cleaned[col].astype(int)  # Convert to int

        # Generate summary statistics
        summary = {
            "original_rows": len(df),
            "cleaned_rows": len(df_cleaned),
            "dropped_rows": len(df) - len(df_cleaned),
            "columns": df_cleaned.columns.tolist()
        }

        # Save cleaned dataset
        cleaned_file_path = file_path.replace(".csv", "_cleaned.csv")
        df_cleaned.to_csv(cleaned_file_path, index=False)

        # Return result as dictionary
        return {
            "file_path": cleaned_file_path,
            "summary": summary
        }

    except Exception as e:
        return {"error": str(e)}

def main():
    # Expecting two arguments from the Laravel controller: file_path and preprocessing type
    if len(sys.argv) != 3:
        print(json.dumps({"error": "Invalid arguments"}))
        sys.exit(1)

    file_path = sys.argv[1]
    preprocessing_type = sys.argv[2]

    # Apply the preprocessing type
    if preprocessing_type == "cleaning":
        result = apply_cleaning(file_path)
    else:
        result = {"error": "Unknown preprocessing type"}

    # Print the result as a JSON string to be used by the Laravel app
    print(json.dumps(result))

if __name__ == "__main__":
    main()
