---
name: svn-update
description: Sync this plugin's git repo into its WordPress.org SVN working copy (trunk, a new version tag, and optionally assets such as screenshots, banners and icons), validate it against WordPress.org rules, then ask before committing. Use when the user types "svn-update" / "/svn-update", or asks to publish, release or deploy the plugin to WordPress.org. Optional args - a folder of images to add to SVN assets.
---

# svn-update — publish the plugin to the WordPress.org SVN

Paths (absolute):
- **GIT** = `/Users/ionelcrisu/Documents/www/1_sakura/work/wp-content/plugins/sakura-pixel-menu-embed-for-appetitqr`
- **SVN** = `/Users/ionelcrisu/Documents/1111_wp/menux-wp`, a working copy of `https://plugins.svn.wordpress.org/sakura-pixel-menu-embed-for-appetitqr`
- **MAIN** = `GIT/sakura-pixel-menu-embed-for-appetitqr.php`

These are the WordPress.org rules this skill enforces:
- `trunk/` and `tags/<version>/` hold only the shippable plugin. No hidden files or folders (`.git`, `.claude`, `.gitignore`, `.DS_Store`, …), no `node_modules`, no `vendor` from Composer, no logs or lock files.
- `assets/` holds only WordPress.org listing images, under the official names: `screenshot-N.(png|jpg|jpeg|gif|webp)`, `banner-772x250.*`, `banner-1544x500.*`, `icon-128x128.*`, `icon-256x256.*` and `icon.svg`. Screenshot N maps to caption N in the readme's `== Screenshots ==` section.
- WordPress.org serves the readme from the folder named by `Stable tag`. That means every release needs a new `tags/<version>` folder. Never change an existing tag's code.

Run the steps in order. Stop and report if any check fails.

## 1. Preflight
1. `svn info SVN`. If this fails, stop: it isn't a working copy.
2. `svn update SVN`.
3. `git -C GIT status --porcelain`. If anything is uncommitted, warn the user and ask whether to continue. Only committed work should ship.
4. Read three version values and stop if they don't all match:
   - `Version:` from MAIN
   - the `APPETITQR_VERSION` constant from MAIN
   - `Stable tag:` from `GIT/readme.txt`

   Call the shared value **VER**.
5. Check that `GIT/readme.txt` has a `= VER =` entry under `== Changelog ==`.

## 2. Sync trunk
```bash
rsync -a --delete --delete-excluded \
  --exclude='.*' \
  --exclude='node_modules/' --exclude='/vendor/' \
  --exclude='composer.lock' --exclude='composer.phar' \
  --exclude='*.log' --exclude='Prepros Export/' \
  "GIT/" "SVN/trunk/"
```
`--exclude='.*'` drops every dotfile and dot-folder at any depth. `SVN/.svn` sits only at the working-copy root, so rsync never touches it.

## 3. Assets (only when the user gives images)
If the user names a folder or files:
- Copy each visible image (skip names that start with `.`) into `SVN/assets/` under its WordPress.org name.
- For screenshots, continue from the highest existing `screenshot-N` and keep the user's order. If no order is given, ask.
- Add a matching `N. caption` line to `== Screenshots ==` in `GIT/readme.txt`, then commit that in git and re-run step 2.
- Never overwrite an existing asset without asking.

## 4. Tag
- If `SVN/tags/VER` doesn't exist, run `svn cp SVN/trunk SVN/tags/VER` **after** step 5 has added the trunk changes. Copying first would leave the new files out of the tag.
- If it already exists, the user is updating a tag that has already been released. Ask before doing anything. The only acceptable case is a readme-only fix (`rsync` just `readme.txt` into the tag). Code changes need a version bump instead.

## 5. Register changes with SVN
```bash
cd SVN
svn add --force trunk assets --auto-props --parents -q
svn status | awk '/^!/ {print substr($0, 9)}' | while IFS= read -r f; do svn rm -q "$f"; done
# now step 4: svn cp trunk tags/VER
for f in $(svn status assets | awk '/^A/ {print $2}'); do
  case "$f" in
    *.png) svn propset -q svn:mime-type image/png "$f" ;;
    *.jpg|*.jpeg) svn propset -q svn:mime-type image/jpeg "$f" ;;
    *.gif) svn propset -q svn:mime-type image/gif "$f" ;;
    *.webp) svn propset -q svn:mime-type image/webp "$f" ;;
    *.svg) svn propset -q svn:mime-type image/svg+xml "$f" ;;
  esac
done
```
The `svn:mime-type` property makes WordPress.org's CDN show the images inline instead of serving them as downloads.

## 6. Validate (all must pass)
- `find SVN/trunk SVN/assets SVN/tags/VER -name '.*' -not -path '*/.svn*'` returns nothing.
- `find SVN/trunk -name '*.php' -exec php -l {} \;` reports no errors.
- `diff -rq --exclude='.*' GIT SVN/trunk` reports no differences.
- In `SVN/tags/VER/readme.txt`, `Stable tag:` equals VER.
- Every `screenshot-N` in `SVN/assets` has caption N in the readme, and every caption has an image.

## 7. Review, then ask
Show `svn status SVN` (or a summary if it's long) and `svn diff --summarize SVN`. Then **ask the user for confirmation** before running:
```bash
svn commit SVN -m "Release VER: <one-line summary of changelog>"
```
Never commit without an explicit yes in this run. If the commit asks for credentials, tell the user to run it themselves with `! svn commit …` so they can type their WordPress.org password.

After the commit, run `svn update SVN` and report the new revision. Listing changes can take a few minutes to show up on WordPress.org.
