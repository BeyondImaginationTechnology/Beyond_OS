export interface FileNode {
  id: string;
  name: string;
  path: string;
  isFolder: boolean;
  children?: FileNode[];
  extension?: string;
  content?: string;
}

export interface EditorTab {
  id: string;
  path: string;
  name: string;
  content: string;
  savedContent: string;
  language: string;
  isDirty: boolean;
  cursorLine: number;
  cursorColumn: number;
  isOverview?: boolean;
}

export interface JaguarModelMeta {
  id: string;
  vendor: string;
  provider: 'Local' | 'Ollama' | 'LM Studio' | 'OpenAI-compatible' | 'Cloud';
  modelFamily: 'Gemma' | 'Qwen' | 'Llama' | 'DeepSeek' | 'Phi';
  modelName: string;
  version: string;
  size: string;
  license: string;
  licenseUrl?: string;
  capability: 'Local' | 'Cloud' | 'Local / Cloud';
  status: 'Ready' | 'Connected' | 'Download Available';
}

export interface ChatMessage {
  id: string;
  role: 'user' | 'assistant' | 'system';
  content: string;
  timestamp: string;
}

export interface TerminalEntry {
  id: string;
  type: 'stdout' | 'stderr' | 'info' | 'cmd';
  text: string;
  timestamp: string;
}

export type SupportedExtension =
  | '.php'
  | '.js'
  | '.ts'
  | '.tsx'
  | '.jsx'
  | '.html'
  | '.css'
  | '.json'
  | '.md'
  | '.py'
  | '.java'
  | '.kt'
  | '.xml'
  | '.yaml'
  | '.yml'
  | '.sh';

export const LANGUAGE_MAP: Record<string, string> = {
  php: 'php',
  js: 'javascript',
  jsx: 'javascript',
  ts: 'typescript',
  tsx: 'typescript',
  html: 'html',
  css: 'css',
  json: 'json',
  md: 'markdown',
  py: 'python',
  java: 'java',
  kt: 'kotlin',
  xml: 'xml',
  yaml: 'yaml',
  yml: 'yaml',
  sh: 'shell',
};

export function getLanguageFromPath(filePath: string): string {
  const ext = filePath.split('.').pop()?.toLowerCase() || '';
  return LANGUAGE_MAP[ext] || 'plaintext';
}
