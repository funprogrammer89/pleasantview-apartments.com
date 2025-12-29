import os
import subprocess
import sys
from pathlib import Path

# 1. Configuration
target = Path("../gitFTP.py") 
link_name = "temp_gitFTP_link.py"

try:
    # 2. Create the shortcut
    if os.path.exists(link_name):
        os.remove(link_name)
    os.symlink(target, link_name)
    print(f"--- Shortcut created: {link_name} ---")

    # 3. EXECUTE the file
    print(f"--- Executing {link_name} ---")
    # This runs the shortcut as a python script
    result = subprocess.run([sys.executable, link_name], capture_output=False)
    
    print(f"--- Execution finished with code: {result.returncode} ---")

except Exception as e:
    print(f"An error occurred: {e}")

finally:
    # 4. Cleanup
    if os.path.exists(link_name):
        os.remove(link_name)
        print(f"--- Shortcut deleted: {link_name} ---")