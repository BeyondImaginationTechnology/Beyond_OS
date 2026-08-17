#!/usr/bin/env node
import { mkdir, readFile, stat, writeFile } from "node:fs/promises";
import path from "node:path";
import { fileURLToPath } from "node:url";

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");
const targets = [
  {
    name: "BeyondFrench",
    dictionaryPath: path.join(root, "BeyondFrenchApple", "Resources", "dictionary.json"),
    dictionaryOutputRoot: path.join(root, "BeyondFrenchApple", "Resources", "Audio", "dictionary"),
  },
  {
    name: "FrenchQuest",
    dictionaryPath: path.join(root, "FrenchQuestApple", "Resources", "dictionary.json"),
    dictionaryOutputRoot: path.join(root, "FrenchQuestApple", "Resources", "Audio", "dictionary"),
    questOutputRoot: path.join(root, "FrenchQuestApple", "Resources", "Audio", "quest", "fr-FR"),
  },
];

const key = process.env.AZURE_SPEECH_KEY;
const region = process.env.AZURE_SPEECH_REGION || "canadacentral";
const defaultVoice = process.env.AZURE_SPEECH_VOICE || "en-US-JennyMultilingualNeural";
const outputFormat = process.env.AZURE_SPEECH_OUTPUT_FORMAT || "audio-24khz-48kbitrate-mono-mp3";

const languages = [
  ["fr-FR", "french"],
  ["es-ES", "spanish"],
  ["ht-HT", "kreyol"],
  ["en-JM", "patois"],
];

if (!key) {
  console.error("AZURE_SPEECH_KEY is required.");
  process.exit(1);
}

function slugify(value) {
  return value
    .normalize("NFKD")
    .replace(/[\u0300-\u036f]/g, "")
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "");
}

function escapeXml(value) {
  return value
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&apos;");
}

async function existsWithAudio(filePath) {
  try {
    const info = await stat(filePath);
    return info.size > 128;
  } catch {
    return false;
  }
}

function voiceForLocale(locale) {
  const normalized = locale.replace("-", "_");
  return process.env[`AZURE_SPEECH_VOICE_${normalized}`] || defaultVoice;
}

async function synthesize(text, locale) {
  const voice = voiceForLocale(locale);
  const voiceLanguage = voice.match(/^([a-z]{2,3}-[A-Z]{2})-/)?.[1] || locale;
  const ssml = [
    `<speak version="1.0" xml:lang="${voiceLanguage}">`,
    `<voice name="${voice}">`,
    `<prosody rate="-5%">${escapeXml(text)}</prosody>`,
    "</voice>",
    "</speak>",
  ].join("");

  const response = await fetch(`https://${region}.tts.speech.microsoft.com/cognitiveservices/v1`, {
    method: "POST",
    headers: {
      "Ocp-Apim-Subscription-Key": key,
      "Content-Type": "application/ssml+xml",
      "X-Microsoft-OutputFormat": outputFormat,
      "User-Agent": "BeyondFrenchDictionaryBatch",
      "Accept": "audio/mpeg",
    },
    body: ssml,
  });

  if (!response.ok) {
    const detail = await response.text().catch(() => "");
    throw new Error(`Azure TTS ${response.status} ${response.statusText}: ${detail.slice(0, 180)}`);
  }

  const buffer = Buffer.from(await response.arrayBuffer());
  if (buffer.length < 128) {
    throw new Error("Azure returned an invalid audio payload.");
  }
  return buffer;
}

let generated = 0;
let skipped = 0;

async function synthesizeIfMissing(filePath, text, locale) {
  await mkdir(path.dirname(filePath), { recursive: true });
  if (await existsWithAudio(filePath)) {
    skipped += 1;
    return;
  }
  const audio = await synthesize(text, locale);
  await writeFile(filePath, audio);
  generated += 1;
  console.log(`generated ${path.relative(root, filePath)}`);
}

for (const target of targets) {
  const dictionary = JSON.parse(await readFile(target.dictionaryPath, "utf8"));
  for (const entry of dictionary) {
    const baseName = slugify(entry.english);
    for (const [locale, field] of languages) {
      const text = String(entry[field] || "").trim();
      if (!text) continue;
      await synthesizeIfMissing(
        path.join(target.dictionaryOutputRoot, locale, `${baseName}.mp3`),
        text,
        locale
      );
    }
  }

  if (target.questOutputRoot) {
    const academy = JSON.parse(await readFile(path.join(root, "FrenchQuestApple", "Resources", "academy.json"), "utf8"));
    const phrases = new Map();
    for (const module of academy.modules || []) {
      for (const lesson of module.lessons || []) {
        const text = String(lesson.french || "").trim();
        if (text) phrases.set(slugify(text), text);
      }
    }
    for (const text of [
      "Bonjour !",
      "Merci.",
      "S'il vous plait.",
      "De l'eau, s'il vous plait.",
      "J'ai faim.",
      "L'addition, s'il vous plait.",
      "Ou est la gare ?",
      "Un billet.",
      "A gauche.",
    ]) {
      phrases.set(slugify(text), text);
    }
    for (const [baseName, text] of phrases) {
      await synthesizeIfMissing(
        path.join(target.questOutputRoot, `${baseName}.mp3`),
        text,
        "fr-FR"
      );
    }
  }
}

console.log(`Done. Generated ${generated}; skipped ${skipped}; total ${generated + skipped}.`);
