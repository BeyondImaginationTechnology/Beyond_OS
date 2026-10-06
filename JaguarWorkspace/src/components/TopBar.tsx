import React from 'react';
import { FolderOpen, Save, FilePlus, Terminal, Bot, Cpu, Sparkles } from 'lucide-react';

interface TopBarProps {
  currentFolder: string;
  onOpenFolder: () => void;
  onSaveCurrentFile: () => void;
  onSaveAsCurrentFile: () => void;
  onOpenOverview: () => void;
  hasActiveFile: boolean;
  isDirty: boolean;
  isTerminalOpen: boolean;
  onToggleTerminal: () => void;
  isAiSidebarOpen: boolean;
  onToggleAiSidebar: () => void;
}

export const TopBar: React.FC<TopBarProps> = ({
  currentFolder,
  onOpenFolder,
  onSaveCurrentFile,
  onSaveAsCurrentFile,
  onOpenOverview,
  hasActiveFile,
  isDirty,
  isTerminalOpen,
  onToggleTerminal,
  isAiSidebarOpen,
  onToggleAiSidebar,
}) => {
  return (
    <header className="h-12 bg-[#0d1322] border-b border-[#1e293b] flex items-center justify-between px-3 select-none z-10">
      {/* Left: Branding & Folder Action */}
      <div className="flex items-center space-x-3">
        <div className="flex items-center space-x-2">
          <div className="w-7 h-7 rounded-lg bg-gradient-to-br from-amber-500 to-amber-700 p-1 flex items-center justify-center shadow-md shadow-amber-950/40">
            <Cpu className="w-4 h-4 text-slate-950 font-bold" />
          </div>
          <span className="font-bold text-sm tracking-wide text-amber-400">
            JAGUAR <span className="text-slate-300 font-normal">WORKSPACE</span>
          </span>
          <span className="text-[10px] bg-amber-500/10 text-amber-400 border border-amber-500/30 px-1.5 py-0.5 rounded font-mono">
            v0.1.0
          </span>
        </div>

        <div className="h-4 w-[1px] bg-[#1e293b] mx-1" />

        <button
          onClick={onOpenFolder}
          className="flex items-center space-x-1.5 px-2.5 py-1 bg-[#162032] hover:bg-[#1e2d47] text-slate-200 text-xs rounded border border-[#2d3f5e] transition-colors"
          title="Open Project Folder (Ctrl+O)"
        >
          <FolderOpen className="w-3.5 h-3.5 text-amber-400" />
          <span>Open Folder</span>
        </button>

        <button
          onClick={onOpenOverview}
          className="flex items-center space-x-1.5 px-2.5 py-1 bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 text-xs rounded border border-amber-500/30 transition-colors"
          title="Explore Jaguar Runtime Overview"
        >
          <Sparkles className="w-3.5 h-3.5 text-amber-400" />
          <span>Explore Runtime</span>
        </button>

        {currentFolder && (
          <span className="text-xs text-slate-400 truncate max-w-xs font-mono bg-[#0b0f19] px-2 py-0.5 rounded border border-[#1e293b] hidden lg:inline-block">
            📁 {currentFolder}
          </span>
        )}
      </div>

      {/* Right: Save, Save As & Panel Toggles */}
      <div className="flex items-center space-x-2">
        <button
          onClick={onSaveCurrentFile}
          disabled={!hasActiveFile}
          className={`flex items-center space-x-1.5 px-2.5 py-1 text-xs rounded border transition-colors ${
            hasActiveFile
              ? isDirty
                ? 'bg-amber-500 text-slate-950 font-medium border-amber-400 shadow-sm shadow-amber-900/50'
                : 'bg-[#162032] hover:bg-[#1e2d47] text-slate-200 border-[#2d3f5e]'
              : 'bg-[#0b0f19] text-slate-600 border-[#1e293b] cursor-not-allowed'
          }`}
          title="Save File (Ctrl+S)"
        >
          <Save className="w-3.5 h-3.5" />
          <span>Save</span>
          {isDirty && <span className="w-2 h-2 rounded-full bg-amber-900 animate-pulse ml-0.5" />}
        </button>

        <button
          onClick={onSaveAsCurrentFile}
          disabled={!hasActiveFile}
          className={`flex items-center space-x-1.5 px-2 py-1 text-xs rounded border transition-colors ${
            hasActiveFile
              ? 'bg-[#162032] hover:bg-[#1e2d47] text-slate-200 border-[#2d3f5e]'
              : 'bg-[#0b0f19] text-slate-600 border-[#1e293b] cursor-not-allowed'
          }`}
          title="Save File As (Ctrl+Shift+S)"
        >
          <FilePlus className="w-3.5 h-3.5 text-amber-400" />
          <span>Save As</span>
        </button>

        <div className="h-4 w-[1px] bg-[#1e293b] mx-1" />

        <button
          onClick={onToggleTerminal}
          className={`p-1.5 rounded text-xs border transition-colors ${
            isTerminalOpen
              ? 'bg-amber-500/20 text-amber-300 border-amber-500/40'
              : 'bg-[#162032] hover:bg-[#1e2d47] text-slate-400 border-[#2d3f5e]'
          }`}
          title="Toggle Terminal Panel"
        >
          <Terminal className="w-4 h-4" />
        </button>

        <button
          onClick={onToggleAiSidebar}
          className={`p-1.5 rounded text-xs border transition-colors ${
            isAiSidebarOpen
              ? 'bg-amber-500/20 text-amber-300 border-amber-500/40'
              : 'bg-[#162032] hover:bg-[#1e2d47] text-slate-400 border-[#2d3f5e]'
          }`}
          title="Toggle Jaguar AI Sidebar"
        >
          <Bot className="w-4 h-4" />
        </button>
      </div>
    </header>
  );
};
