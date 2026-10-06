import React, { useState, useEffect } from 'react';
import { TopBar } from './components/TopBar';
import { FileExplorer } from './components/FileExplorer';
import { CodeEditor } from './components/CodeEditor';
import { AISidebar } from './components/AISidebar';
import { TerminalPanel } from './components/TerminalPanel';
import { StatusBar } from './components/StatusBar';
import { SaveAsModal } from './components/SaveAsModal';
import { FileNode, EditorTab, getLanguageFromPath } from './types/workspace';
import { createSampleProject } from './services/tauriFs';

export const App: React.FC = () => {
  const [currentFolder, setCurrentFolder] = useState<string>('');
  const [fileTree, setFileTree] = useState<FileNode[]>([]);
  const [tabs, setTabs] = useState<EditorTab[]>([]);
  const [activeTabId, setActiveTabId] = useState<string | undefined>(undefined);
  const [isTerminalOpen, setIsTerminalOpen] = useState<boolean>(true);
  const [isAiSidebarOpen, setIsAiSidebarOpen] = useState<boolean>(true);
  const [cursorPos, setCursorPos] = useState({ line: 1, column: 1 });
  const [isSaveAsOpen, setIsSaveAsOpen] = useState<boolean>(false);

  // Auto-initialize sample project & open Overview tab on load
  useEffect(() => {
    const sample = createSampleProject();
    setCurrentFolder(sample.rootPath);
    setFileTree(sample.tree);

    const overviewTab: EditorTab = {
      id: 'overview-tab',
      path: 'overview://runtime',
      name: 'Overview ⚡',
      content: '',
      savedContent: '',
      language: 'plaintext',
      isDirty: false,
      cursorLine: 1,
      cursorColumn: 1,
      isOverview: true,
    };

    setTabs([overviewTab]);
    setActiveTabId(overviewTab.id);
  }, []);

  const handleOpenFolder = () => {
    const sample = createSampleProject();
    setCurrentFolder(sample.rootPath);
    setFileTree(sample.tree);
  };

  const handleOpenOverview = () => {
    const existingOverview = tabs.find((t) => t.isOverview);
    if (existingOverview) {
      setActiveTabId(existingOverview.id);
      return;
    }

    const overviewTab: EditorTab = {
      id: 'overview-tab',
      path: 'overview://runtime',
      name: 'Overview ⚡',
      content: '',
      savedContent: '',
      language: 'plaintext',
      isDirty: false,
      cursorLine: 1,
      cursorColumn: 1,
      isOverview: true,
    };

    setTabs((prev) => [overviewTab, ...prev]);
    setActiveTabId(overviewTab.id);
  };

  const handleStartCoding = () => {
    const firstFile = fileTree[0]?.children?.[0];
    if (firstFile) {
      handleOpenFile(firstFile);
    }
  };

  const handleOpenFile = (file: FileNode) => {
    if (file.isFolder) return;

    const existingTab = tabs.find((t) => t.path === file.path);
    if (existingTab) {
      setActiveTabId(existingTab.id);
      return;
    }

    const initialContent = file.content || `// ${file.name}\n`;

    const newTab: EditorTab = {
      id: Date.now().toString(),
      path: file.path,
      name: file.name,
      content: initialContent,
      savedContent: initialContent,
      language: getLanguageFromPath(file.path),
      isDirty: false,
      cursorLine: 1,
      cursorColumn: 1,
    };

    setTabs((prev) => [...prev, newTab]);
    setActiveTabId(newTab.id);
  };

  const handleSelectTab = (tabId: string) => {
    setActiveTabId(tabId);
  };

  const handleCloseTab = (tabId: string) => {
    setTabs((prev) => {
      const filtered = prev.filter((t) => t.id !== tabId);
      if (activeTabId === tabId) {
        setActiveTabId(filtered[filtered.length - 1]?.id);
      }
      return filtered;
    });
  };

  const handleChangeContent = (newContent: string) => {
    if (!activeTabId) return;
    setTabs((prev) =>
      prev.map((tab) => {
        if (tab.id === activeTabId) {
          const isDirty = newContent !== tab.savedContent;
          return { ...tab, content: newContent, isDirty };
        }
        return tab;
      })
    );
  };

  const handleSaveCurrentFile = () => {
    if (!activeTabId) return;

    setTabs((prev) =>
      prev.map((tab) => {
        if (tab.id === activeTabId && !tab.isOverview) {
          updateFileContentInTree(fileTree, tab.path, tab.content);
          return { ...tab, savedContent: tab.content, isDirty: false };
        }
        return tab;
      })
    );
  };

  const updateFileContentInTree = (nodes: FileNode[], path: string, newContent: string): boolean => {
    for (const node of nodes) {
      if (!node.isFolder && node.path === path) {
        node.content = newContent;
        return true;
      }
      if (node.isFolder && node.children) {
        if (updateFileContentInTree(node.children, path, newContent)) return true;
      }
    }
    return false;
  };

  const handleSaveAsCurrentFile = () => {
    if (!activeTabId) return;
    const activeTab = tabs.find((t) => t.id === activeTabId);
    if (activeTab?.isOverview) return;
    setIsSaveAsOpen(true);
  };

  const getAllExistingPaths = (nodes: FileNode[]): string[] => {
    let paths: string[] = [];
    for (const node of nodes) {
      if (!node.isFolder) paths.push(node.path);
      if (node.isFolder && node.children) {
        paths = paths.concat(getAllExistingPaths(node.children));
      }
    }
    return paths;
  };

  const handleConfirmSaveAs = (newPath: string) => {
    if (!activeTabId) return;

    const activeTab = tabs.find((t) => t.id === activeTabId);
    if (!activeTab || activeTab.isOverview) return;

    const fileName = newPath.substring(newPath.lastIndexOf('\\') + 1) || 'new-file.txt';

    setTabs((prev) =>
      prev.map((tab) => {
        if (tab.id === activeTabId) {
          return {
            ...tab,
            path: newPath,
            name: fileName,
            savedContent: tab.content,
            language: getLanguageFromPath(newPath),
            isDirty: false,
          };
        }
        return tab;
      })
    );

    const newFileNode: FileNode = {
      id: Date.now().toString(),
      name: fileName,
      path: newPath,
      isFolder: false,
      extension: `.${fileName.split('.').pop() || ''}`,
      content: activeTab.content,
    };

    setFileTree((prev) => [...prev, newFileNode]);
    setIsSaveAsOpen(false);
  };

  // Global Keyboard Shortcuts
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      const isCtrl = e.ctrlKey || e.metaKey;

      if (isCtrl && e.shiftKey && e.key.toLowerCase() === 's') {
        e.preventDefault();
        handleSaveAsCurrentFile();
      } else if (isCtrl && e.key.toLowerCase() === 's') {
        e.preventDefault();
        handleSaveCurrentFile();
      } else if (isCtrl && e.key.toLowerCase() === 'w') {
        e.preventDefault();
        if (activeTabId && tabs.length > 1) handleCloseTab(activeTabId);
      } else if (isCtrl && e.key.toLowerCase() === 'o') {
        e.preventDefault();
        handleOpenFolder();
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [activeTabId, fileTree, tabs]);

  const activeTab = tabs.find((t) => t.id === activeTabId);

  return (
    <div className="h-screen w-screen flex flex-col bg-[#0b0f19] text-[#e2e8f0] overflow-hidden font-sans select-none">
      {/* Top Bar */}
      <TopBar
        currentFolder={currentFolder}
        onOpenFolder={handleOpenFolder}
        onSaveCurrentFile={handleSaveCurrentFile}
        onSaveAsCurrentFile={handleSaveAsCurrentFile}
        onOpenOverview={handleOpenOverview}
        hasActiveFile={!!activeTab && !activeTab.isOverview}
        isDirty={!!activeTab?.isDirty}
        isTerminalOpen={isTerminalOpen}
        onToggleTerminal={() => setIsTerminalOpen(!isTerminalOpen)}
        isAiSidebarOpen={isAiSidebarOpen}
        onToggleAiSidebar={() => setIsAiSidebarOpen(!isAiSidebarOpen)}
      />

      {/* Main Workspace Area */}
      <div className="flex-1 flex min-h-0 overflow-hidden relative">
        <FileExplorer
          tree={fileTree}
          activeFilePath={activeTab?.path}
          onSelectFile={handleOpenFile}
          onOpenFolder={handleOpenFolder}
        />

        <div className="flex-1 flex flex-col min-w-0 h-full overflow-hidden">
          <CodeEditor
            tabs={tabs}
            activeTabId={activeTabId}
            onSelectTab={handleSelectTab}
            onCloseTab={handleCloseTab}
            onChangeContent={handleChangeContent}
            onCursorChange={(line, col) => setCursorPos({ line, column: col })}
            onStartCoding={handleStartCoding}
          />

          <TerminalPanel
            workingDir={currentFolder}
            isOpen={isTerminalOpen}
            onToggle={() => setIsTerminalOpen(!isTerminalOpen)}
          />
        </div>

        <AISidebar
          activeFileName={activeTab?.name}
          isOpen={isAiSidebarOpen}
          onClose={() => setIsAiSidebarOpen(false)}
        />
      </div>

      {/* Status Bar */}
      <StatusBar
        currentFolder={currentFolder}
        activeFilePath={activeTab?.isOverview ? undefined : activeTab?.path}
        activeFileLanguage={activeTab?.isOverview ? undefined : activeTab?.language}
        cursorLine={cursorPos.line}
        cursorColumn={cursorPos.column}
      />

      {/* Save As Modal */}
      {activeTab && !activeTab.isOverview && (
        <SaveAsModal
          isOpen={isSaveAsOpen}
          defaultPath={activeTab.path}
          defaultFileName={activeTab.name}
          existingFiles={getAllExistingPaths(fileTree)}
          onSave={handleConfirmSaveAs}
          onCancel={() => setIsSaveAsOpen(false)}
        />
      )}
    </div>
  );
};
