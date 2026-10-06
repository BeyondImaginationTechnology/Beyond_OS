import React, { useState } from 'react';
import {
  Folder,
  FolderOpen,
  FileCode,
  FileText,
  FileJson,
  Terminal as TerminalIcon,
  ChevronRight,
  ChevronDown,
  FolderPlus,
  RefreshCw,
} from 'lucide-react';
import { FileNode } from '../types/workspace';

interface FileExplorerProps {
  tree: FileNode[];
  activeFilePath?: string;
  onSelectFile: (file: FileNode) => void;
  onOpenFolder: () => void;
}

const getFileIcon = (fileName: string) => {
  const ext = fileName.split('.').pop()?.toLowerCase();
  switch (ext) {
    case 'ts':
    case 'tsx':
    case 'js':
    case 'jsx':
      return <FileCode className="w-4 h-4 text-sky-400 shrink-0" />;
    case 'php':
      return <FileCode className="w-4 h-4 text-indigo-400 shrink-0" />;
    case 'py':
      return <FileCode className="w-4 h-4 text-amber-400 shrink-0" />;
    case 'java':
    case 'kt':
      return <FileCode className="w-4 h-4 text-orange-400 shrink-0" />;
    case 'json':
    case 'yaml':
    case 'yml':
      return <FileJson className="w-4 h-4 text-emerald-400 shrink-0" />;
    case 'html':
    case 'xml':
      return <FileCode className="w-4 h-4 text-rose-400 shrink-0" />;
    case 'css':
      return <FileCode className="w-4 h-4 text-cyan-400 shrink-0" />;
    case 'sh':
      return <TerminalIcon className="w-4 h-4 text-teal-400 shrink-0" />;
    case 'md':
      return <FileText className="w-4 h-4 text-slate-300 shrink-0" />;
    default:
      return <FileText className="w-4 h-4 text-slate-400 shrink-0" />;
  }
};

interface TreeItemProps {
  node: FileNode;
  activeFilePath?: string;
  onSelectFile: (file: FileNode) => void;
  depth: number;
}

const TreeItem: React.FC<TreeItemProps> = ({
  node,
  activeFilePath,
  onSelectFile,
  depth,
}) => {
  const [isOpen, setIsOpen] = useState(true);

  if (node.isFolder) {
    return (
      <div>
        <div
          onClick={() => setIsOpen(!isOpen)}
          className="flex items-center space-x-1.5 px-2 py-1 hover:bg-[#162032] cursor-pointer text-slate-300 text-xs rounded transition-colors group"
          style={{ paddingLeft: `${depth * 12 + 8}px` }}
        >
          {isOpen ? (
            <ChevronDown className="w-3.5 h-3.5 text-slate-400 shrink-0" />
          ) : (
            <ChevronRight className="w-3.5 h-3.5 text-slate-400 shrink-0" />
          )}
          {isOpen ? (
            <FolderOpen className="w-4 h-4 text-amber-400 shrink-0" />
          ) : (
            <Folder className="w-4 h-4 text-amber-500/80 shrink-0" />
          )}
          <span className="truncate font-medium">{node.name}</span>
        </div>

        {isOpen && node.children && (
          <div>
            {node.children.map((child) => (
              <TreeItem
                key={child.id}
                node={child}
                activeFilePath={activeFilePath}
                onSelectFile={onSelectFile}
                depth={depth + 1}
              />
            ))}
          </div>
        )}
      </div>
    );
  }

  const isActive = activeFilePath === node.path;

  return (
    <div
      onClick={() => onSelectFile(node)}
      className={`flex items-center space-x-2 px-2 py-1 cursor-pointer text-xs rounded transition-colors ${
        isActive
          ? 'bg-amber-500/20 text-amber-300 font-medium border-l-2 border-amber-400'
          : 'text-slate-300 hover:bg-[#162032]'
      }`}
      style={{ paddingLeft: `${depth * 12 + 20}px` }}
    >
      {getFileIcon(node.name)}
      <span className="truncate">{node.name}</span>
    </div>
  );
};

export const FileExplorer: React.FC<FileExplorerProps> = ({
  tree,
  activeFilePath,
  onSelectFile,
  onOpenFolder,
}) => {
  return (
    <div className="w-64 bg-[#0d1322] border-r border-[#1e293b] flex flex-col h-full select-none shrink-0">
      <div className="h-9 px-3 border-b border-[#1e293b] flex items-center justify-between text-xs text-slate-400 font-semibold tracking-wider uppercase">
        <span>Explorer</span>
        <div className="flex items-center space-x-1">
          <button
            onClick={onOpenFolder}
            className="p-1 hover:bg-[#1e2d47] hover:text-amber-400 rounded transition-colors"
            title="Open Folder"
          >
            <FolderPlus className="w-3.5 h-3.5" />
          </button>
          <button
            className="p-1 hover:bg-[#1e2d47] hover:text-slate-200 rounded transition-colors"
            title="Refresh Tree"
          >
            <RefreshCw className="w-3.5 h-3.5" />
          </button>
        </div>
      </div>

      <div className="flex-1 overflow-y-auto p-1.5 space-y-0.5">
        {tree.length === 0 ? (
          <div className="p-4 text-center text-xs text-slate-500 space-y-3">
            <p>No project folder opened.</p>
            <button
              onClick={onOpenFolder}
              className="w-full py-1.5 px-3 bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border border-amber-500/30 rounded font-medium transition-colors"
            >
              Open Folder
            </button>
          </div>
        ) : (
          tree.map((node) => (
            <TreeItem
              key={node.id}
              node={node}
              activeFilePath={activeFilePath}
              onSelectFile={onSelectFile}
              depth={0}
            />
          ))
        )}
      </div>
    </div>
  );
};
