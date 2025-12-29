import os
from pathlib import Path

# Set your paths
target = Path("../gitFTP.py")
link_name = "s.py"

# Create the link
os.symlink(target, link_name)