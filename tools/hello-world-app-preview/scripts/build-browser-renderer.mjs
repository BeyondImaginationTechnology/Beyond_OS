import {build} from 'esbuild';
import {fileURLToPath} from 'node:url';
import path from 'node:path';

const projectDirectory = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const siteRoot = path.resolve(projectDirectory, '..', '..');

await build({
  entryPoints: [path.join(projectDirectory, 'src', 'browser-renderer.tsx')],
  outfile: path.join(
    siteRoot,
    'server',
    'admin',
    'daily-studio',
    'assets',
    'beyond-french-remotion-renderer.js',
  ),
  bundle: true,
  minify: true,
  format: 'iife',
  platform: 'browser',
  target: ['chrome94', 'firefox130', 'safari26'],
  legalComments: 'none',
  sourcemap: false,
});
