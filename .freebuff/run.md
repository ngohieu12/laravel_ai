# Preview Run Doc

## Reproduce Artifacts

1. Copy `.env` from main checkout (already synced — same worktree)
2. Ensure `database/database.sqlite` exists (already migrated)
3. Run `php artisan migrate:fresh --seed` if DB is empty

## Run Server

```powershell
# From project root
php artisan serve --port=8000 --host=127.0.0.1
```

Alternatively, start detached via PowerShell:
```powershell
powershell -NoProfile -Command "(Start-Process -FilePath 'php.exe' -ArgumentList 'artisan','serve','--port=8000','--host=127.0.0.1' -RedirectStandardOutput '<log>' -RedirectStandardError '<log>.err' -WindowStyle Hidden -PassThru).Id"
```

## URL

http://127.0.0.1:8000
