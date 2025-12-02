import os
import face_recognition
import pickle
import traceback # Import traceback to print detailed errors

# Define the face data directory globally
FACE_DIR = 'face_data'

def retrain():
    print(f"Starting retraining process in directory: {FACE_DIR}") # Debug start
    known_encodings = []
    known_names = []
    processed_images = 0
    failed_images = []

    try:
        # Check if face_data directory exists
        if not os.path.isdir(FACE_DIR):
            print(f"Error: Directory '{FACE_DIR}' not found.")
            return False

        for person in os.listdir(FACE_DIR):
            folder = os.path.join(FACE_DIR, person)
            # Ensure it's a directory and not the pickle file itself
            if os.path.isdir(folder):
                print(f"Processing folder: {person}") # Debug folder
                image_count_in_folder = 0
                for file in os.listdir(folder):
                    path = os.path.join(folder, file)
                    print(f"  Processing file: {file}") # Debug file
                    try:
                        # Check if it's actually a file
                        if not os.path.isfile(path):
                            print(f"    Skipping (not a file): {file}")
                            continue

                        img = face_recognition.load_image_file(path)
                        encodings = face_recognition.face_encodings(img)

                        if encodings:
                            # Use the first encoding found
                            known_encodings.append(encodings[0])
                            known_names.append(person) # Use folder name as label
                            processed_images += 1
                            image_count_in_folder += 1
                            print(f"    Successfully encoded face from: {file}") # Debug success
                        else:
                            print(f"    Warning: No face found or could not encode face in: {path}") # Debug warning
                            failed_images.append(path)

                    except Exception as e:
                        print(f"    Error processing file {path}: {e}") # Debug file processing error
                        # Print detailed traceback
                        # traceback.print_exc()
                        failed_images.append(f"{path} (Error: {e})")

                if image_count_in_folder == 0:
                    print(f"  Warning: No valid faces encoded in folder {person}. Ensure images contain clear faces.")


        # Check if any faces were actually encoded before saving
        if not known_encodings:
            print("Error: No faces were encoded during retraining. Check images in face_data.")
            # Optionally delete the old pickle file if it exists?
            # if os.path.exists(os.path.join(FACE_DIR, 'encodings.pkl')):
            #     os.remove(os.path.join(FACE_DIR, 'encodings.pkl'))
            return False # Indicate failure if nothing was encoded

        # Save the encodings
        pickle_path = os.path.join(FACE_DIR, 'encodings.pkl')
        print(f"Saving {len(known_encodings)} encodings to {pickle_path}...") # Debug saving
        with open(pickle_path, 'wb') as f:
            pickle.dump((known_encodings, known_names), f)

        print(f"Retraining complete. Processed {processed_images} images successfully.") # Debug completion
        if failed_images:
            print("Images that failed processing or had no faces:")
            for failed in failed_images:
                print(f"  - {failed}")
        return True # Indicate success

    except PermissionError as pe:
        print(f"Error: Permission denied when trying to write to '{FACE_DIR}/encodings.pkl'. Check file/folder permissions.")
        # traceback.print_exc()
        return False
    except Exception as e:
        print(f"An unexpected error occurred during retraining: {e}") # Catch other potential errors
        # Print detailed traceback for unexpected errors
        traceback.print_exc()
        return False

# You can add this block to test train.py directly if needed
# if __name__ == "__main__":
#     if retrain():
#         print("\nDirect execution: Retraining successful.")
#     else:
#         print("\nDirect execution: Retraining failed.")