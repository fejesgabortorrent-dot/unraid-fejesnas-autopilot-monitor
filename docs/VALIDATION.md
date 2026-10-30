# Observed validation — 2026-10-30

- NAS PHP lint passed for the installed controller and compatibility helper.
- Local JavaScript syntax validation passed.
- Unauthenticated access returned 401; foreign origins and missing request nonce returned 403; unsupported HTTP method returned 405 in isolated PHP CLI tests. These tests did not create real authenticated web sessions.
- Unraid's existing CSRF validation remains enabled. The browser supplies its platform token plus a separate session-bound controller nonce.
- The browser's actual backup button completed successfully and returned a verified result for 68 files. After the compatibility/persistence change, a second browser backup appeared in history with 73 files and a successful restore check.
- The controller showed the active CH704 profile, torrent count, port agreement, snapshot timestamp and backup history from the installed sources. Health values can change; they are not permanent release assertions.
- Pure compatibility tests allowed 7.3.4 and rejected 8.0.0, an unknown string and unverified 7.3.5 for mutations.
- The startup installer now copies the JS, CSS and compatibility helper as well as the existing page, PHP and icon. The script was executed and resulting deployed files were byte-compared with persistent sources. No reboot was performed.
- Docker WebUI metadata was added by replacing only the read-only snapshot-service container with the same image and host configuration. The prior stopped container was retained for rollback. The Docker WebUI menu visibly opened the authenticated AutoPilot page.
- The qBittorrent container identity and start time, active WireGuard file hash and disabled automatic-switch setting were checked unchanged across that operation. No torrent stop, VPN switch or NAS restart was performed.

Not validated: Unraid 8 runtime, unattended scheduled backup after this session, complete application/database restore, fresh public installation, public image pull or Community Apps portal scan.
