import { FileNode } from '../types/workspace';

// Check if running inside Tauri runtime
export function isTauriAvailable(): boolean {
  return typeof window !== 'undefined' && '__TAURI_INTERNALS__' in window;
}

export function createSampleProject(): { rootPath: string; tree: FileNode[] } {
  const rootPath = 'C:\\Projects\\jaguar-sample-project';
  const tree: FileNode[] = [
    {
      id: '1',
      name: 'src',
      path: `${rootPath}\\src`,
      isFolder: true,
      children: [
        {
          id: '1-1',
          name: 'index.ts',
          path: `${rootPath}\\src\\index.ts`,
          isFolder: false,
          extension: '.ts',
          content: `// Jaguar Workspace - Sample TypeScript File\nimport { JaguarAgent } from './agent';\n\nconsole.log('Initializing Jaguar Workspace...');\nconst agent = new JaguarAgent({ model: 'llama-3-local' });\nagent.start();\n`,
        },
        {
          id: '1-2',
          name: 'agent.ts',
          path: `${rootPath}\\src\\agent.ts`,
          isFolder: false,
          extension: '.ts',
          content: `export interface AgentConfig {\n  model: string;\n}\n\nexport class JaguarAgent {\n  constructor(private config: AgentConfig) {}\n\n  public start(): void {\n    console.log(\`Jaguar Agent active with model: \${this.config.model}\`);\n  }\n}\n`,
        },
        {
          id: '1-3',
          name: 'App.tsx',
          path: `${rootPath}\\src\\App.tsx`,
          isFolder: false,
          extension: '.tsx',
          content: `import React from 'react';\n\nexport const App: React.FC = () => {\n  return (\n    <div className="workspace">\n      <h1>Welcome to Jaguar Workspace</h1>\n    </div>\n  );\n};\n`,
        },
        {
          id: '1-4',
          name: 'styles.css',
          path: `${rootPath}\\src\\styles.css`,
          isFolder: false,
          extension: '.css',
          content: `/* Dark Jaguar UI Tokens */\n:root {\n  --jaguar-bg: #0b0f19;\n  --jaguar-amber: #f59e0b;\n}\n\nbody {\n  background-color: var(--jaguar-bg);\n  color: #e2e8f0;\n}\n`,
        },
      ],
    },
    {
      id: '2',
      name: 'api',
      path: `${rootPath}\\api`,
      isFolder: true,
      children: [
        {
          id: '2-1',
          name: 'jaguar.php',
          path: `${rootPath}\\api\\jaguar.php`,
          isFolder: false,
          extension: '.php',
          content: `<?php\ndeclare(strict_types=1);\nheader('Content-Type: application/json; charset=utf-8');\n\n$payload = json_decode((string)file_get_contents('php://input'), true);\n$input = trim((string)($payload['input'] ?? ''));\n\necho json_encode([\n    'ok' => true,\n    'assistant' => 'Jaguar Local Engine',\n    'response' => "Analysis complete for input: " . $input\n]);\n`,
        },
        {
          id: '2-2',
          name: 'model.py',
          path: `${rootPath}\\api\\model.py`,
          isFolder: false,
          extension: '.py',
          content: `# Jaguar Python Inference Bridge\nimport json\nimport sys\n\ndef run_inference(prompt: str) -> str:\n    return f"Jaguar Local Python Output: {prompt[::-1]}"\n\nif __name__ == "__main__":
    prompt = sys.argv[1] if len(sys.argv) > 1 else "Hello Jaguar"
    print(run_inference(prompt))
`,
        },
      ],
    },
    {
      id: '3',
      name: 'scripts',
      path: `${rootPath}\\scripts`,
      isFolder: true,
      children: [
        {
          id: '3-1',
          name: 'build.sh',
          path: `${rootPath}\\scripts\\build.sh`,
          isFolder: false,
          extension: '.sh',
          content: `#!/bin/bash\necho "Building Jaguar Workspace Standalone Executable..."\n npm run build\n echo "Build complete."\n`,
        },
      ],
    },
    {
      id: '4',
      name: 'package.json',
      path: `${rootPath}\\package.json`,
      isFolder: false,
      extension: '.json',
      content: `{\n  "name": "jaguar-sample-project",\n  "version": "1.0.0",\n  "private": true\n}\n`,
    },
    {
      id: '5',
      name: 'README.md',
      path: `${rootPath}\\README.md`,
      isFolder: false,
      extension: '.md',
      content: `# Jaguar Workspace\n\nJaguar Workspace is a lightweight, standalone desktop development environment built with Tauri, React, TypeScript, and Monaco Editor, designed for local AI models.\n\n## Features\n- Standalone Windows UI Shell\n- Integrated Monaco Editor\n- Collapsible Terminal Panel\n- Jaguar AI Sidebar\n`,
    },
  ];

  return { rootPath, tree };
}
