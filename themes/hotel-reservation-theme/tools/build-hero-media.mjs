#!/usr/bin/env node

import { fileURLToPath } from 'node:url';
import path from 'node:path';
import fs from 'node:fs/promises';
import { constants as fsConstants } from 'node:fs';
import globby from 'globby';
import sharp from 'sharp';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const themeRoot = path.resolve(__dirname, '..');

const SOURCE_DIR = path.join(themeRoot, 'storytelling', 'media', 'source');
const OUTPUT_DIR = path.join(themeRoot, 'storytelling', 'media');
const WIDTHS = [480, 768, 1200];
const FORMATS = [
  { extension: 'webp', method: 'webp', options: { quality: 82 } },
  { extension: 'jpg', method: 'jpeg', options: { quality: 88 } },
];

function log(message) {
  process.stdout.write(`[hero-media] ${message}\n`);
}

async function ensureDirectories() {
  await fs.mkdir(SOURCE_DIR, { recursive: true });
  await fs.mkdir(OUTPUT_DIR, { recursive: true });
}

async function removeExistingVariants(basename) {
  const patterns = [`${basename}-*.jpg`, `${basename}-*.jpeg`, `${basename}-*.webp`];
  const matches = await globby(patterns, { cwd: OUTPUT_DIR, absolute: true, onlyFiles: true });
  await Promise.all(
    matches.map(async (filePath) => {
      try {
        await fs.unlink(filePath);
      } catch (error) {
        if (error && error.code !== 'ENOENT') {
          throw error;
        }
      }
    })
  );
}

function normaliseBasename(filePath) {
  const raw = path.basename(filePath, path.extname(filePath));
  return raw
    .toLowerCase()
    .replace(/[^a-z0-9-_]+/g, '-')
    .replace(/-{2,}/g, '-')
    .replace(/^-+|-+$/g, '');
}

async function buildVariants(filePath, basename) {
  const image = sharp(filePath);
  log(`Processing ${path.basename(filePath)} → ${basename}`);

  for (const targetWidth of WIDTHS) {
    const resized = image
      .clone()
      .resize({ width: targetWidth, fit: 'inside', withoutEnlargement: true });

    for (const format of FORMATS) {
      const outputName = `${basename}-${targetWidth}.${format.extension}`;
      const outputPath = path.join(OUTPUT_DIR, outputName);
      await resized.clone()[format.method](format.options).toFile(outputPath);
      log(`  • ${path.relative(themeRoot, outputPath)}`);
    }
  }
}

async function markSourceReadOnly(filePath) {
  try {
    await fs.chmod(filePath, fsConstants.S_IRUSR | fsConstants.S_IWUSR | fsConstants.S_IRGRP | fsConstants.S_IROTH);
  } catch (error) {
    if (error && error.code !== 'EPERM') {
      throw error;
    }
  }
}

async function main() {
  await ensureDirectories();
  const sources = await globby(['*.*'], { cwd: SOURCE_DIR, absolute: true, onlyFiles: true });

  if (sources.length === 0) {
    log(`No source files found in ${path.relative(themeRoot, SOURCE_DIR)}. Drop originals there and re-run.`);
    return;
  }

  for (const filePath of sources) {
    const basename = normaliseBasename(filePath);
    if (!basename) {
      log(`Skipping ${path.basename(filePath)} because the derived basename is empty.`);
      continue;
    }

    await removeExistingVariants(basename);
    await buildVariants(filePath, basename);
    await markSourceReadOnly(filePath);
  }

  log('Hero media build complete. Commit generated variants under storytelling/media/.');
}

main().catch((error) => {
  console.error('[hero-media] Build failed:', error);
  process.exit(1);
});
