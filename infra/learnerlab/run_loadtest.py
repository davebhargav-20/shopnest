import base64, subprocess, sys, os
b = base64.b64encode(open(os.path.join(os.path.dirname(__file__), "loadtest.sh"), "rb").read()).decode()
print(subprocess.run([sys.executable, os.path.join(os.path.dirname(__file__), "ssm_run.py"), f"echo {b} | base64 -d | bash"], capture_output=True, text=True, encoding="utf-8").stdout)
