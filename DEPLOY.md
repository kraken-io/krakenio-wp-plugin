# Releasing to WordPress.org

This plugin ships through the WordPress.org SVN repository
(`https://plugins.svn.wordpress.org/kraken-image-optimizer/`). You can release
two ways. **The automated workflow is the recommended one** — it does exactly
what the old manual `svn` release did, but without the easy-to-make mistakes,
and it has a dry run so you can preview everything before anything goes live.

Nothing you do here can corrupt existing tags or users' sites: WordPress only
serves the version named by **`Stable tag`** in `trunk/readme.txt`, and every
past tag is kept forever. The riskiest action — making a version live — is a
single one-line change you can revert instantly (see Rollback).

---

## One-time setup

Add two repository secrets (GitHub: **Settings → Secrets and variables →
Actions → New repository secret**):

| Secret | Value |
| --- | --- |
| `SVN_USERNAME` | your WordPress.org username (must be a committer: `karim79`) |
| `SVN_PASSWORD` | your WordPress.org password |

---

## Pre-flight (already verified for 3.0.0)

- `Stable tag` in `readme.txt` **equals** the `Version:` header in `kraken.php`.
- `assets/dist/` is built and committed (`npm run wp:scripts:build`).
- Screenshots / banner / icon are current in `.wordpress-org/`.
- Tested in the lab (all WP versions) and on PHP 5.6 (`scripts/test-php56-upgrade.sh`).

---

## Recommended: the GitHub workflow

### Step 1 — Dry run (zero risk)

GitHub → **Actions → Deploy to WordPress.org → Run workflow**, leave **Dry run**
checked, run it. It builds the exact package, lists every file that would ship
to trunk, and stops **before** committing. Read the log; confirm the file list
looks right (only the runtime plugin, no `tests/`, `node_modules/`, etc.).

### Step 2 — Real deploy

When the dry run looks good, publish a **GitHub Release** with the tag set to the
version number, e.g. `3.0.0` (must match `Stable tag`). That — and only that —
commits to WordPress.org: it creates `tags/3.0.0`, syncs `trunk`, uploads the
`.wordpress-org` assets, and makes 3.0.0 the live version. Done.

---

## Fallback: manual SVN (staged, safest order)

Use this only if you prefer doing it by hand. It uploads the tag first and flips
the live version **last**, so 3.0.0 is fully in place and verifiable before any
user is offered it.

```bash
# 0) fresh dist
npm run wp:scripts:build

# 1) clean checkout
svn co https://plugins.svn.wordpress.org/kraken-image-optimizer/ svn-kraken && cd svn-kraken

# 2) mirror trunk to the build, but keep the LIVE version at 2.7.0 for now
rsync -a --delete --exclude='.svn' --exclude='.git' --exclude='node_modules' \
  --exclude='assets/src' --exclude='.wordpress-org' --exclude='vendor' \
  --exclude='tests' --exclude='*.md' --exclude='package*.json' \
  ../krakenio-wp-plugin/ trunk/
sed -i '' 's/^Stable tag: .*/Stable tag: 2.7.0/' trunk/readme.txt   # macOS; Linux: sed -i
svn add --force trunk
svn status | awk '/^!/{print $2}' | xargs -r svn rm

# 3) marketing assets -> repo-root /assets (NOT trunk)
cp ../krakenio-wp-plugin/.wordpress-org/banner-*.png \
   ../krakenio-wp-plugin/.wordpress-org/icon-*.png \
   ../krakenio-wp-plugin/.wordpress-org/screenshot-*.png assets/
svn rm assets/banner-1544x500.jpg assets/banner-772x250.jpg 2>/dev/null
svn add --force assets

# 4) create the tag (its own readme says Stable tag: 3.0.0)
svn cp trunk tags/3.0.0
sed -i '' 's/^Stable tag: .*/Stable tag: 3.0.0/' tags/3.0.0/readme.txt

# 5) first commit — tag + code uploaded, LIVE version still 2.7.0
svn ci -m "Add 3.0.0 (tag + trunk + assets); stable flips after verification" --username karim79

# 6) verify the tag before going live
svn export https://plugins.svn.wordpress.org/kraken-image-optimizer/tags/3.0.0/ /tmp/v300
# zip /tmp/v300, install in a clean WP, smoke-test

# 7) go live — flip trunk's Stable tag to 3.0.0
sed -i '' 's/^Stable tag: .*/Stable tag: 3.0.0/' trunk/readme.txt
svn ci -m "Release 3.0.0: set stable tag" --username karim79
```

---

## Rollback (instant, no re-upload)

If a problem appears after going live, point users back to the previous version
by reverting one line — no files change, every tag still exists:

```bash
sed -i '' 's/^Stable tag: .*/Stable tag: 2.7.0/' trunk/readme.txt
svn ci -m "Roll back stable tag to 2.7.0" --username karim79
```

---

## Why this is safe

- **Past tags are immutable history** — you are only ever *adding* `tags/3.0.0`.
- **The live version is one pointer** (`Stable tag` in `trunk/readme.txt`); the
  workflow's real deploy sets it, and rollback un-sets it in one line.
- **The dry run** shows the exact file manifest before anything is committed.
- **`.distignore`** guarantees dev files (tests, node_modules, sources) never
  reach trunk, so the shipped package stays clean.
