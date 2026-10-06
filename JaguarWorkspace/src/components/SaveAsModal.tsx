import React, { useState } from 'react';
import { Save, AlertTriangle, X } from 'lucide-react';

interface SaveAsModalProps {
  isOpen: boolean;
  defaultPath: string;
  defaultFileName: string;
  existingFiles: string[];
  onSave: (newPath: string) => void;
  onCancel: () => void;
}

export const SaveAsModal: React.FC<SaveAsModalProps> = ({
  isOpen,
  defaultPath,
  defaultFileName,
  existingFiles,
  onSave,
  onCancel,
}) => {
  const [fileName, setFileName] = useState(defaultFileName);
  const [showOverwriteWarning, setShowOverwriteWarning] = useState(false);

  if (!isOpen) return null;

  const directory = defaultPath.substring(0, defaultPath.lastIndexOf('\\')) || 'C:\\Projects\\jaguar-sample-project';
  const targetPath = `${directory}\\${fileName}`;
  const fileExists = existingFiles.includes(targetPath);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!fileName.trim()) return;

    if (fileExists && !showOverwriteWarning) {
      setShowOverwriteWarning(true);
      return;
    }

    onSave(targetPath);
  };

  return (
    <div className="fixed inset-0 bg-slate-950/80 backdrop-blur-xs flex items-center justify-center z-50 p-4 select-none">
      <div className="bg-[#0d1322] border border-[#2d3f5e] rounded-xl shadow-2xl w-full max-w-md overflow-hidden">
        <div className="h-10 px-4 bg-[#111827] border-b border-[#1e293b] flex items-center justify-between">
          <div className="flex items-center space-x-2 text-slate-200 text-xs font-semibold">
            <Save className="w-4 h-4 text-amber-400" />
            <span>Save File As</span>
          </div>
          <button
            onClick={onCancel}
            className="p-1 text-slate-400 hover:text-slate-200 hover:bg-[#1e2d47] rounded transition-colors"
          >
            <X className="w-4 h-4" />
          </button>
        </div>

        <form onSubmit={handleSubmit} className="p-4 space-y-4 text-xs">
          <div className="space-y-1">
            <label className="text-slate-400">Target Folder</label>
            <div className="p-2 bg-[#0b0f19] border border-[#1e293b] rounded text-slate-300 font-mono truncate">
              📁 {directory}
            </div>
          </div>

          <div className="space-y-1">
            <label className="text-slate-300 font-medium">File Name</label>
            <input
              type="text"
              value={fileName}
              onChange={(e) => {
                setFileName(e.target.value);
                setShowOverwriteWarning(false);
              }}
              autoFocus
              className="w-full bg-[#162032] border border-[#2d3f5e] focus:border-amber-400 rounded p-2 text-slate-200 text-xs font-mono focus:outline-none"
            />
          </div>

          {showOverwriteWarning && (
            <div className="p-3 bg-amber-500/10 border border-amber-500/30 rounded text-amber-300 flex items-start space-x-2 text-xs">
              <AlertTriangle className="w-4 h-4 text-amber-400 shrink-0 mt-0.5" />
              <div>
                <p className="font-semibold">File already exists!</p>
                <p className="text-[11px] text-amber-200/80">
                  Saving will replace the existing file on disk. Click "Confirm Overwrite" to proceed.
                </p>
              </div>
            </div>
          )}

          <div className="flex items-center justify-end space-x-2 pt-2 border-t border-[#1e293b]">
            <button
              type="button"
              onClick={onCancel}
              className="px-3 py-1.5 bg-[#162032] hover:bg-[#1e2d47] text-slate-300 rounded transition-colors"
            >
              Cancel
            </button>
            <button
              type="submit"
              className={`px-4 py-1.5 rounded font-semibold transition-colors flex items-center space-x-1.5 ${
                showOverwriteWarning
                  ? 'bg-rose-500 hover:bg-rose-400 text-white'
                  : 'bg-amber-500 hover:bg-amber-400 text-slate-950'
              }`}
            >
              <Save className="w-3.5 h-3.5" />
              <span>{showOverwriteWarning ? 'Confirm Overwrite' : 'Save As'}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};
