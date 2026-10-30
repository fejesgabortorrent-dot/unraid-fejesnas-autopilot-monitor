# FejesNAS AutoPilot Monitor

Development source, 2026-10-30, under the MIT license. No installable release or Community Applications listing has been published from this package.

FejesNAS AutoPilot Monitor combines a small read-only snapshot service with an authenticated Unraid host interface. The host interface shows VPN health, torrent count, forwarding-port agreement, known profile details, version compatibility and backup history. Its backup action calls a separately installed, fixed host backup program and checks its verified result. Hungarian, English, German and French, Unraid-following or explicit white/black/gray/azure themes, 3D/flat cards, a configurable moving button border, an icon and a favicon are implemented in the host UI.

## Components

- `snapshot-service/`: unprivileged HTTP viewer, allowlisted JSON fields, read-only state mount, no Docker socket and no WireGuard configuration mounts. Its standalone UI remains a read-only preview.
- `host-ui/`: the Unraid-session protected interface tested on the maintainer's Unraid 7.3.4 installation. It requires the host backup script, snapshot service, existing Unraid page wrapper and startup installation. This directory alone is not a complete fresh-install package.
- `drafts/`: proposed metadata, deliberately outside a submission templates directory. The image and repository URLs are proposed destinations, not proof of existing published artifacts.
- `docs/`: observed validation, deployment requirements and release gates.

The source contains no runtime snapshots, credentials, private keys, VPN profiles, torrent inventory, NAS hostname, Tailscale address or backup archives. Host paths in `host-ui/ui.php` are implementation defaults for the existing installation; they must become validated installation settings before a public controller release.

## Current behavior

Manual and scheduled host backups are versioned on an explicitly selected physical array disk. They are reread, restored to a separate directory and compared by SHA-256. The 14-daily/4-weekly policy selects retained generations; automatic deletion is disabled. A separate prechange invocation is available. The backup engine itself is not included in this source candidate, so the web interface must not be presented as a turnkey backup product.

Automatic VPN profile switching remains disabled. Profile switching, new-profile cataloguing/testing, destructive retention and live restore are not web actions in this candidate.

## Unraid 8

The host interface has a separate pure compatibility function and a host version reader. Only the observed 7.3.4 version passes the mutation gate. Version 8, unknown versions and other unverified versions fail closed. This is preparation, not Unraid 8 certification.

## Distribution status

Do not submit this package to Community Applications yet. A complete clean installer, connected first-run wizard, portable refresh adapter, public image and clean-machine installation/rollback tests remain release gates. The generic first-run configuration validator is included and tested separately; it is not yet connected to the installed host UI. See LICENSE for the MIT license.

The current submission process and field requirements are documented by [Unraid Community Apps](https://ca.unraid.net/submit/help/repository-xml) and its [field reference](https://ca.unraid.net/submit/help/xml-field-reference).
