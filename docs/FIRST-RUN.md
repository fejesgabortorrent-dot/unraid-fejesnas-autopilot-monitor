# First-run contract

The generic configuration module starts without a backup destination or service name. It does not inherit the maintainer's NAS disk, hostname or VPN profile. Backup scheduling remains off until a user selects their own physical array disk and share.

The installer/wizard must present the existing mounted array disks, available shares, optional qBittorrent container, schedule and retained generation counts. Settings use schema version 1, fixed field names and strict types. Shell command fields, traversal paths, unknown fields, automatic deletion and automatic VPN switching are rejected.

`fap_physical_destination()` constructs a physical destination from validated names; it does not establish that the disk is mounted or that a directory is safe. The installer and backup worker must separately inspect mount identity, each parent component, symlinks, ownership and permissions before creating or using it.

This first-run library is release preparation. The wizard and generic installer are not yet connected to the production host interface. Do not replace the working NAS configuration with these defaults.
