# Certificate Fonts

The `.ttf` files in this directory are **not committed to the repository** (they
total ~8.6MB). They are fetched on demand and bundled into the distributable
release zip.

## Populating this directory

From the plugin root:

```bash
bin/download-fonts.sh
```

This downloads a complete static TrueType instance for each weight/style listed
in [`includes/Core/Fonts.php`](../../includes/Core/Fonts.php). The filenames the
script writes (e.g. `montserrat-bold.ttf`) must match that registry exactly.

Static instances are used deliberately: TCPDF (the PDF engine) cannot select a
weight from a variable font, so each weight is downloaded as its own file.

If a font file is missing at runtime the PDF generator falls back to Helvetica
for that element — the certificate still renders, just without the intended
typeface.

## Fonts & licensing

All bundled fonts are sourced from [Google Fonts](https://fonts.google.com) and
are redistributable under the **SIL Open Font License 1.1** or the
**Apache License 2.0**:

| Family | Category | License |
| --- | --- | --- |
| Playfair Display | serif | OFL 1.1 |
| Lora | serif | OFL 1.1 |
| Merriweather | serif | OFL 1.1 |
| Crimson Text | serif | OFL 1.1 |
| Cormorant Garamond | serif | OFL 1.1 |
| Montserrat | sans-serif | OFL 1.1 |
| Open Sans | sans-serif | OFL 1.1 |
| Raleway | sans-serif | OFL 1.1 |
| Roboto | sans-serif | Apache 2.0 |
| Lato | sans-serif | OFL 1.1 |
| Poppins | sans-serif | OFL 1.1 |
| Great Vibes | handwriting | OFL 1.1 |
| Dancing Script | handwriting | OFL 1.1 |
| Pacifico | handwriting | OFL 1.1 |
| Alex Brush | handwriting | OFL 1.1 |
| Allura | handwriting | OFL 1.1 |
| Cinzel | display | OFL 1.1 |

Refer to each family's page on Google Fonts for the authoritative license text.
