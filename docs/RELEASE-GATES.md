# Release gates

1. Dedicated repository name and MIT license selected: fejesgabortorrent-dot/unraid-fejesnas-autopilot-monitor.
2. Move installation-specific paths into a root-owned, validated configuration outside the web directory; require a mounted physical array destination.
3. Include and test the backup engine and a generic read-only status exporter. Never package live WireGuard profiles, keys, configuration exports, screenshots with identifiers or production state.
4. Build a complete companion-plugin installer with checksummed artifacts, atomic updates, prechange backup, explicit rollback and startup refresh restoration. Rehearse a clean installation without modifying VPN services.
5. Decide whether the store product is the read-only Docker viewer or the complete host companion. Its description must match what a fresh install actually does.
6. Build, publish and pull-test a versioned image; keep the container read-only, unprivileged, without the Docker socket and without VPN mounts. Bind the snapshot service to loopback when paired with Unraid authentication.
7. Publish project/support/icon/template URLs only after those resources exist. Avoid maintainer-specific NAS hostnames in store metadata.
8. Run the Community Apps portal Validate and Scan steps on the final repository. Resolve review findings. The account owner must accept any portal terms themselves.
9. Test Unraid 8 on an actual isolated Unraid 8 installation before enabling that version's mutating adapter.

Draft XML is a working document. It is intentionally not in a scanned `templates/` directory and is not an installable public listing.
