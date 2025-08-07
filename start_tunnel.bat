@echo off
echo Starting Cloudflare Tunnel for Flask API...
"C:\Program Files (x86)\cloudflared\cloudflared.exe" tunnel --url http://127.0.0.1:5000
pause



