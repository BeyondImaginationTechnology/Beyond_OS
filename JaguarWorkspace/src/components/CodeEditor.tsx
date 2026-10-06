import React from 'react';
import Editor, { OnMount } from '@monaco-editor/react';
import { X, Code2 } from 'lucide-react';
import { EditorTab, getLanguageFromPath } from '../types/workspace';
import { RuntimeHomepageDemo } from './RuntimeHomepageDemo';

interface CodeEditorProps {
  tabs: EditorTab[];
  activeTabId?: string;
  onSelectTab: (id: string) => void;
  onCloseTab: (id: string) => void;
  onChangeContent: (content: string) => void;
  onCursorChange: (line: number, column: number) => void;
  onStartCoding: () => void;
}

export const CodeEditor: React.FC<CodeEditorProps> = ({
  tabs,
  activeTabId,
  onSelectTab,
  onCloseTab,
  onChangeContent,
  onCursorChange,
  onStartCoding,
}) => {
  const activeTab = tabs.find((t) => t.id === activeTabId);

  const handleEditorMount: OnMount = (editor) => {
    editor.onDidChangeCursorPosition((e) => {
      onCursorChange(e.position.lineNumber, e.position.column);
    });
  };

  if (!activeTab) {
    return (
      <div className="flex-1 bg-[#0b0f19] flex flex-col items-center justify-center text-slate-500 select-none p-6">
        <div className="w-16 h-16 rounded-2xl bg-[#111827] border border-[#1e293b] flex items-center justify-center mb-4 text-amber-500/60">
          <Code2 className="w-8 h-8" />
        </div>
        <h2 className="text-base font-semibold text-slate-300 mb-1">Jaguar Workspace Editor</h2>
        <p className="text-xs text-slate-500 max-w-sm text-center mb-4">
          Select a file from the explorer on the left or open a project folder to start editing code.
        </p>
        <div className="flex items-center space-x-2 text-[11px] font-mono text-slate-600 bg-[#0d1322] px-3 py-1.5 rounded border border-[#1e293b]">
          <span>Save File: <kbd className="text-amber-400">Ctrl + S</kbd></span>
          <span>•</span>
          <span>Open Folder: <kbd className="text-amber-400">Ctrl + O</kbd></span>
        </div>
      </div>
    );
  }

  if (activeTab.isOverview) {
    return (
      <div className="flex-1 flex flex-col h-full bg-[#0b0f19] overflow-hidden min-w-0">
        {/* Tabs Bar */}
        <div className="h-9 bg-[#0d1322] border-b border-[#1e293b] flex items-center overflow-x-auto select-none shrink-0 scrollbar-none">
          {tabs.map((tab) => {
            const isActive = tab.id === activeTabId;
            return (
              <div
                key={tab.id}
                onClick={() => onSelectTab(tab.id)}
                className={`group h-full flex items-center space-x-2 px-3 border-r border-[#1e293b] text-xs cursor-pointer transition-colors max-w-xs shrink-0 ${
                  isActive
                    ? 'bg-[#0b0f19] text-amber-300 font-medium border-t-2 border-t-amber-400'
                    : 'text-slate-400 hover:bg-[#162032] hover:text-slate-200'
                }`}
              >
                <span className="truncate">{tab.name}</span>
                {tab.isDirty && (
                  <span className="w-2 h-2 rounded-full bg-amber-400 shrink-0" title="Unsaved changes" />
                )}
                {tabs.length > 1 && (
                  <button
                    onClick={(e) => {
                      e.stopPropagation();
                      onCloseTab(tab.id);
                    }}
                    className="p-0.5 rounded hover:bg-[#1e2d47] text-slate-500 hover:text-slate-200 transition-colors"
                    title="Close Tab"
                  >
                    <X className="w-3 h-3" />
                  </button>
                )}
              </div>
            );
          })}
        </div>

        <RuntimeHomepageDemo onStartCoding={onStartCoding} />
      </div>
    );
  }

  const language = getLanguageFromPath(activeTab.path);

  return (
    <div className="flex-1 flex flex-col h-full bg-[#0b0f19] overflow-hidden min-w-0">
      {/* Tabs Bar */}
      <div className="h-9 bg-[#0d1322] border-b border-[#1e293b] flex items-center overflow-x-auto select-none shrink-0 scrollbar-none">
        {tabs.map((tab) => {
          const isActive = tab.id === activeTabId;
          return (
            <div
              key={tab.id}
              onClick={() => onSelectTab(tab.id)}
              className={`group h-full flex items-center space-x-2 px-3 border-r border-[#1e293b] text-xs cursor-pointer transition-colors max-w-xs shrink-0 ${
                isActive
                  ? 'bg-[#0b0f19] text-amber-300 font-medium border-t-2 border-t-amber-400'
                  : 'text-slate-400 hover:bg-[#162032] hover:text-slate-200'
              }`}
            >
              <span className="truncate">{tab.name}</span>
              {tab.isDirty && (
                <span className="w-2 h-2 rounded-full bg-amber-400 shrink-0" title="Unsaved changes" />
              )}
              <button
                onClick={(e) => {
                  e.stopPropagation();
                  onCloseTab(tab.id);
                }}
                className="p-0.5 rounded hover:bg-[#1e2d47] text-slate-500 hover:text-slate-200 transition-colors"
                title="Close Tab"
              >
                <X className="w-3 h-3" />
              </button>
            </div>
          );
        })}
      </div>

      {/* Monaco Code Editor */}
      <div className="flex-1 relative">
        <Editor
          height="100%"
          language={language}
          value={activeTab.content}
          theme="vs-dark"
          onMount={handleEditorMount}
          onChange={(value) => onChangeContent(value || '')}
          options={{
            fontSize: 13,
            fontFamily: "'Cascadia Code', 'Fira Code', Consolas, 'Courier New', monospace",
            minimap: { enabled: true },
            scrollBeyondLastLine: false,
            automaticLayout: true,
            tabSize: 2,
            smoothScrolling: true,
            cursorBlinking: 'smooth',
            cursorSmoothCaretAnimation: 'on',
            lineNumbersMinChars: 3,
            renderLineHighlight: 'all',
            padding: { top: 10, bottom: 10 },
          }}
        />
      </div>
    </div>
  );
};
