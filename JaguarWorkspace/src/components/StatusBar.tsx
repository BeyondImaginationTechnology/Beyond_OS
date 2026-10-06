import React from 'react';
import { Cpu, Folder, FileCode2, Binary } from 'lucide-react';

interface StatusBarProps {
  currentFolder: string;
  activeFilePath?: string;
  activeFileLanguage?: string;
  cursorLine: number;
  cursorColumn: number;
}

export const StatusBar: React.FC<StatusBarProps> = ({
  currentFolder,
  activeFilePath,
  activeFileLanguage,
  cursorLine,
  cursorColumn,
}) => {
  return (
    <footer className="h-6 bg-[#0a0d16] border-t border-[#1e293b] flex items-center justify-between px-3 text-[11px] text-slate-400 select-none z-10 shrink-0 font-mono">
      {/* Left: Active Folder & File */}
      <div className="flex items-center space-x-3 truncate">
        <div className="flex items-center space-x-1.5 text-slate-300">
          <Folder className="w-3 h-3 text-amber-400 shrink-0" />
          <span className="truncate max-w-xs">{currentFolder || 'No workspace open'}</span>
        </div>

        {activeFilePath && (
          <>
            <span className="text-slate-600">•</span>
            <div className="flex items-center space-x-1.5 text-amber-300 truncate">
              <FileCode2 className="w-3 h-3 text-amber-400 shrink-0" />
              <span className="truncate max-w-sm">{activeFilePath}</span>
            </div>
          </>
        )}
      </div>

      {/* Right: Language, Line/Col, Encoding, Model Status */}
      <div className="flex items-center space-x-4 shrink-0">
        {activeFilePath && (
          <>
            <span className="uppercase text-amber-400 font-semibold">
              {activeFileLanguage || 'Plain Text'}
            </span>
            <span className="text-slate-600">•</span>
            <span>
              Ln {cursorLine}, Col {cursorColumn}
            </span>
            <span className="text-slate-600">•</span>
            <span className="flex items-center space-x-1 text-slate-400">
              <Binary className="w-3 h-3" />
              <span>UTF-8</span>
            </span>
            <span className="text-slate-600">•</span>
          </>
        )}

        <div className="flex items-center space-x-1.5 text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-2 py-0.5 rounded text-[10px]">
          <Cpu className="w-3 h-3 text-emerald-400" />
          <span>Jaguar Runtime · Connected</span>
        </div>
      </div>
    </footer>
  );
};
