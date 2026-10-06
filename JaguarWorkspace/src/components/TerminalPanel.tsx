import React, { useState, useRef, useEffect } from 'react';
import {
  Terminal as TerminalIcon,
  Trash2,
  Square,
  ChevronDown,
  ChevronUp,
} from 'lucide-react';
import { TerminalEntry } from '../types/workspace';

interface TerminalPanelProps {
  workingDir: string;
  isOpen: boolean;
  onToggle: () => void;
}

export const TerminalPanel: React.FC<TerminalPanelProps> = ({
  workingDir,
  isOpen,
  onToggle,
}) => {
  const [entries, setEntries] = useState<TerminalEntry[]>([
    {
      id: '1',
      type: 'info',
      text: `Jaguar Terminal Shell Initialized. Active Directory: ${workingDir || 'C:\\Projects\\jaguar-sample-project'}`,
      timestamp: new Date().toLocaleTimeString(),
    },
  ]);
  const [inputCommand, setInputCommand] = useState('');
  const [history, setHistory] = useState<string[]>([]);
  const [historyIndex, setHistoryIndex] = useState<number>(-1);
  const [isRunning, setIsRunning] = useState(false);
  const bottomRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (isOpen) {
      bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
    }
  }, [entries, isOpen]);

  if (!isOpen) return null;

  const handleRunCommand = (cmdToRun?: string) => {
    const cmd = (cmdToRun || inputCommand).trim();
    if (!cmd || isRunning) return;

    const cmdEntry: TerminalEntry = {
      id: Date.now().toString(),
      type: 'cmd',
      text: `$ ${cmd}`,
      timestamp: new Date().toLocaleTimeString(),
    };

    setEntries((prev) => [...prev, cmdEntry]);
    setHistory((prev) => [cmd, ...prev]);
    setHistoryIndex(-1);
    setInputCommand('');
    setIsRunning(true);

    // Simulated local command execution output
    setTimeout(() => {
      let outputText = `Executed: ${cmd}`;
      let type: 'stdout' | 'stderr' = 'stdout';

      if (cmd === 'clear' || cmd === 'cls') {
        setEntries([]);
        setIsRunning(false);
        return;
      } else if (cmd.startsWith('npm') || cmd.startsWith('node')) {
        outputText = `[Jaguar CLI Node v24.19.0]\nRunning: ${cmd}\nTask completed successfully.`;
      } else if (cmd.startsWith('git')) {
        outputText = `[git] On branch main. Workspace clean.`;
      } else if (cmd.startsWith('error')) {
        outputText = `[Error] Command failed with exit status 1`;
        type = 'stderr';
      } else {
        outputText = `[Jaguar Output] Running in ${workingDir || 'workspace'}:\n> ${cmd}\nDone.`;
      }

      setEntries((prev) => [
        ...prev,
        {
          id: (Date.now() + 1).toString(),
          type,
          text: outputText,
          timestamp: new Date().toLocaleTimeString(),
        },
      ]);
      setIsRunning(false);
    }, 600);
  };

  const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Enter') {
      handleRunCommand();
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      if (history.length > 0 && historyIndex < history.length - 1) {
        const nextIdx = historyIndex + 1;
        setHistoryIndex(nextIdx);
        setInputCommand(history[nextIdx]);
      }
    } else if (e.key === 'ArrowDown') {
      e.preventDefault();
      if (historyIndex > 0) {
        const nextIdx = historyIndex - 1;
        setHistoryIndex(nextIdx);
        setInputCommand(history[nextIdx]);
      } else if (historyIndex === 0) {
        setHistoryIndex(-1);
        setInputCommand('');
      }
    }
  };

  const handleClear = () => {
    setEntries([]);
  };

  const handleStop = () => {
    setIsRunning(false);
    setEntries((prev) => [
      ...prev,
      {
        id: Date.now().toString(),
        type: 'stderr',
        text: '^C Process terminated by user.',
        timestamp: new Date().toLocaleTimeString(),
      },
    ]);
  };

  return (
    <div className="h-52 bg-[#080c14] border-t border-[#1e293b] flex flex-col select-none font-mono text-xs z-10 shrink-0">
      {/* Terminal Header */}
      <div className="h-8 px-3 bg-[#0d1322] border-b border-[#1e293b] flex items-center justify-between text-slate-400">
        <div className="flex items-center space-x-2">
          <TerminalIcon className="w-3.5 h-3.5 text-amber-400" />
          <span className="font-semibold text-slate-300">Terminal</span>
          <span className="text-[10px] text-slate-500 bg-[#0b0f19] px-2 py-0.5 rounded border border-[#1e293b]">
            📁 {workingDir || 'C:\\Projects\\jaguar-sample-project'}
          </span>
        </div>

        <div className="flex items-center space-x-2">
          {isRunning && (
            <button
              onClick={handleStop}
              className="flex items-center space-x-1 px-2 py-0.5 bg-rose-500/20 hover:bg-rose-500/30 text-rose-300 border border-rose-500/40 rounded text-[11px] transition-colors"
              title="Stop Command"
            >
              <Square className="w-3 h-3" />
              <span>Stop</span>
            </button>
          )}

          <button
            onClick={handleClear}
            className="p-1 hover:bg-[#1e2d47] hover:text-slate-200 rounded transition-colors"
            title="Clear Terminal"
          >
            <Trash2 className="w-3.5 h-3.5" />
          </button>

          <button
            onClick={onToggle}
            className="p-1 hover:bg-[#1e2d47] hover:text-slate-200 rounded transition-colors"
            title="Collapse Terminal"
          >
            {isOpen ? <ChevronDown className="w-3.5 h-3.5" /> : <ChevronUp className="w-3.5 h-3.5" />}
          </button>
        </div>
      </div>

      {/* Output Console */}
      <div className="flex-1 overflow-y-auto p-3 space-y-1 font-mono text-[11px] leading-relaxed">
        {entries.map((entry) => (
          <div
            key={entry.id}
            className={`whitespace-pre-wrap ${
              entry.type === 'cmd'
                ? 'text-amber-300 font-semibold'
                : entry.type === 'stderr'
                ? 'text-rose-400'
                : entry.type === 'info'
                ? 'text-teal-400 italic'
                : 'text-slate-300'
            }`}
          >
            {entry.text}
          </div>
        ))}
        <div ref={bottomRef} />
      </div>

      {/* Command Input Prompt */}
      <div className="p-1.5 bg-[#0d1322] border-t border-[#1e293b] flex items-center space-x-2 px-3">
        <span className="text-amber-400 font-bold">$</span>
        <input
          type="text"
          value={inputCommand}
          onChange={(e) => setInputCommand(e.target.value)}
          onKeyDown={handleKeyDown}
          placeholder="Type terminal command (e.g. npm run build, git status, clear)..."
          className="flex-1 bg-transparent border-none text-slate-200 text-xs focus:outline-none font-mono placeholder-slate-600"
        />
      </div>
    </div>
  );
};
