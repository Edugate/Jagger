Jagger
===

Jagger (http://jagger.heanet.ie) is developed by HEAnet to manage the Edugate multiparty SAML federation. Other organisations use Jagger to manage their federations but it can be used to manage the web-of-trust for a single entity. It can also be used a a GUI for the Shibboleth SAML Identity Provider (www.shibboleth.net)

Features: 

1. Synchronise SAML metadata from another federation.
2. Create and manage a federation
3. Create a single circle of trust containing metadata of all entities that your organisation particpates via multiple federations.
4. GUI to manage to the attribute policy of identity providers based on the Shibboleth SAML implementation.
5. Filter the RequestedAttribute's of a SAML service provider to allow and IdP release attributes to such providers based on a policy set in the Jagger GUI.
6. Create and edit metadata of individual entities.
7. Notification subsystem with subscription options


Installation: [docs/INSTALL.md](docs/INSTALL.md) (Ubuntu 26.04 LTS / Debian 13.6.0 — `.deb`
package or `install.sh`)

Upgrades: [docs/UPGRADE.md](docs/UPGRADE.md)

Troubleshooting: [docs/TROUBLESHOOTING.md](docs/TROUBLESHOOTING.md)

Uninstall: [docs/UNINSTALL.md](docs/UNINSTALL.md)

This fork's modernization for production deployment (dependency upgrades, a CodeIgniter 4
migration in progress, systemd units, a dedicated service user, `.deb` packaging, CI): see
[docs/AUDIT.md](docs/AUDIT.md) and [docs/CI4_MIGRATION.md](docs/CI4_MIGRATION.md).


Documentation (Admin guide) - http://jagger.heanet.ie/jaggerdocadmin/ (not final yet)

----

