---
paths:
  - '{package.json,vite.config.js,.github/workflows/**}'
---

# Workflows

## Production host has no npm/node — ship pre-built assets
Production runs in a jail without npm/node. Never add deploy steps that run npm/vite on the server. Frontend assets (public/build incl. manifest.json) are built locally or in CI and shipped with the deploy; missing manifest => ViteManifestNotFoundException. Tailwind still needs a compile step, so replacing Vite does not remove the build requirement.
