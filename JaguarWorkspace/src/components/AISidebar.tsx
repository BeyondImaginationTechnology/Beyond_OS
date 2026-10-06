import React, { useState } from 'react';
import {
  Bot,
  Send,
  Square,
  Sparkles,
  FileCode2,
  CheckCircle2,
  Cpu,
  ShieldCheck,
} from 'lucide-react';
import { ChatMessage, JaguarModelMeta } from '../types/workspace';

interface AISidebarProps {
  activeFileName?: string;
  isOpen: boolean;
  onClose: () => void;
}

const SAMPLE_MODELS: JaguarModelMeta[] = [
  {
    id: 'mock-runtime',
    vendor: 'Qwen / Alibaba',
    provider: 'Local',
    modelFamily: 'Qwen',
    modelName: 'Qwen 2.5 Coder 7B (Mock Runtime)',
    version: '2.5.0',
    size: '7B',
    license: 'Apache 2.0',
    capability: 'Local',
    status: 'Ready',
  },
  {
    id: 'gemma-2-9b',
    vendor: 'Google',
    provider: 'Ollama',
    modelFamily: 'Gemma',
    modelName: 'Gemma 2 9B (Ollama)',
    version: '2.0',
    size: '9B',
    license: 'Gemma Terms of Use',
    capability: 'Local / Cloud',
    status: 'Ready',
  },
  {
    id: 'llama-3-8b',
    vendor: 'Meta',
    provider: 'LM Studio',
    modelFamily: 'Llama',
    modelName: 'Llama 3.1 8B Instruct (LM Studio)',
    version: '3.1.0',
    size: '8B',
    license: 'Meta Llama 3.1 Community License',
    capability: 'Local / Cloud',
    status: 'Connected',
  },
  {
    id: 'deepseek-coder',
    vendor: 'DeepSeek',
    provider: 'Local',
    modelFamily: 'DeepSeek',
    modelName: 'DeepSeek Coder V2 (Local server)',
    version: '2.0.0',
    size: '16B',
    license: 'MIT',
    capability: 'Local',
    status: 'Ready',
  },
  {
    id: 'phi-3-mini',
    vendor: 'Microsoft',
    provider: 'Local',
    modelFamily: 'Phi',
    modelName: 'Phi-3 Mini 3.8B (Local)',
    version: '3.0.0',
    size: '3.8B',
    license: 'MIT',
    capability: 'Local',
    status: 'Ready',
  },
];

export const AISidebar: React.FC<AISidebarProps> = ({
  activeFileName,
  isOpen,
}) => {
  const [selectedModelId, setSelectedModelId] = useState<string>('mock-runtime');
  const [messages, setMessages] = useState<ChatMessage[]>([
    {
      id: '1',
      role: 'assistant',
      content:
        '👋 Welcome to Jaguar Runtime. I am running provider-neutrally. Select any local or cloud model family (Gemma, Qwen, Llama, DeepSeek, Phi) to assist your coding session.',
      timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
    },
  ]);
  const [inputText, setInputText] = useState('');
  const [isGenerating, setIsGenerating] = useState(false);
  const [includeActiveFile, setIncludeActiveFile] = useState(true);

  if (!isOpen) return null;

  const currentModelMeta =
    SAMPLE_MODELS.find((m) => m.id === selectedModelId) || SAMPLE_MODELS[0];

  const handleSend = (overrideText?: string) => {
    const textToSend = overrideText || inputText;
    if (!textToSend.trim() || isGenerating) return;

    const userMsg: ChatMessage = {
      id: Date.now().toString(),
      role: 'user',
      content: textToSend,
      timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
    };

    setMessages((prev) => [...prev, userMsg]);
    if (!overrideText) setInputText('');
    setIsGenerating(true);

    setTimeout(() => {
      const assistantMsg: ChatMessage = {
        id: (Date.now() + 1).toString(),
        role: 'assistant',
        content: `[Jaguar Runtime Response via ${currentModelMeta.modelName}]\n\nProcessed query for ${
          includeActiveFile && activeFileName ? `file "${activeFileName}"` : 'workspace context'
        }.\n\nSuggestions:\n1. Code structures validated.\n2. All syntax constructs adhere to standard development patterns.`,
        timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
      };
      setMessages((prev) => [...prev, assistantMsg]);
      setIsGenerating(false);
    }, 900);
  };

  const handleStop = () => {
    setIsGenerating(false);
  };

  const quickPrompts = [
    'Explain this file',
    'Find the bug',
    'Refactor the selected code',
    'Write tests for this',
    'Implement this feature',
    'Review this project',
  ];

  return (
    <div className="w-80 bg-[#0d1322] border-l border-[#1e293b] flex flex-col h-full select-none shrink-0 z-10">
      {/* Header */}
      <div className="h-10 px-3 border-b border-[#1e293b] flex items-center justify-between">
        <div className="flex items-center space-x-2">
          <Bot className="w-4 h-4 text-amber-400" />
          <span className="text-xs font-semibold tracking-wider text-slate-200 uppercase">
            Jaguar Runtime
          </span>
        </div>
        <div className="flex items-center space-x-1.5 text-[10px] bg-amber-500/10 text-amber-300 border border-amber-500/30 px-2 py-0.5 rounded font-mono">
          <Cpu className="w-3 h-3 text-amber-400" />
          <span>{currentModelMeta.modelFamily}</span>
        </div>
      </div>

      {/* Model Selector & Metadata */}
      <div className="p-2.5 border-b border-[#1e293b] bg-[#0b0f19] space-y-2 text-xs">
        <div className="flex items-center justify-between text-slate-400">
          <span>Active Model</span>
          <span className="flex items-center space-x-1 text-emerald-400 text-[11px]">
            <CheckCircle2 className="w-3 h-3" />
            <span>Ready</span>
          </span>
        </div>

        <select
          value={selectedModelId}
          onChange={(e) => setSelectedModelId(e.target.value)}
          aria-label="Select Active Model"
          className="w-full bg-[#162032] border border-[#2d3f5e] rounded px-2 py-1 text-xs text-slate-200 focus:outline-none focus:border-amber-400 font-sans"
        >
          {SAMPLE_MODELS.map((m) => (
            <option key={m.id} value={m.id}>
              {m.modelName}
            </option>
          ))}
        </select>

        {/* Model License / Provider Badge */}
        <div className="p-2 bg-[#121b2d] border border-[#23334d] rounded space-y-1 text-[11px] font-mono">
          <div className="flex items-center justify-between text-amber-300">
            <span>Family: {currentModelMeta.modelFamily}</span>
            <span className="text-[10px] text-slate-400">v{currentModelMeta.version}</span>
          </div>
          <div className="text-slate-400 flex items-center space-x-1">
            <ShieldCheck className="w-3 h-3 text-teal-400 shrink-0" />
            <span className="truncate">License: {currentModelMeta.license}</span>
          </div>
        </div>

        {activeFileName && (
          <label className="flex items-center space-x-2 text-slate-300 cursor-pointer pt-0.5">
            <input
              type="checkbox"
              checked={includeActiveFile}
              onChange={(e) => setIncludeActiveFile(e.target.checked)}
              className="rounded bg-[#162032] border-[#2d3f5e] text-amber-500 focus:ring-0"
            />
            <FileCode2 className="w-3.5 h-3.5 text-amber-400" />
            <span className="truncate text-[11px]">Attach active file ({activeFileName})</span>
          </label>
        )}
      </div>

      {/* Chat Messages */}
      <div className="flex-1 overflow-y-auto p-3 space-y-3">
        {messages.map((msg) => (
          <div
            key={msg.id}
            className={`p-2.5 rounded-lg text-xs space-y-1 ${
              msg.role === 'user'
                ? 'bg-amber-500/15 border border-amber-500/30 text-amber-100 ml-4'
                : 'bg-[#162032] border border-[#2d3f5e] text-slate-200 mr-2'
            }`}
          >
            <div className="flex items-center justify-between text-[10px] text-slate-400 font-mono mb-1">
              <span className="font-semibold text-slate-300">
                {msg.role === 'user' ? 'You' : 'Jaguar Runtime'}
              </span>
              <span>{msg.timestamp}</span>
            </div>
            <p className="whitespace-pre-wrap leading-relaxed">{msg.content}</p>
          </div>
        ))}

        {isGenerating && (
          <div className="p-2.5 rounded-lg text-xs bg-[#162032] border border-amber-500/30 text-amber-300 flex items-center space-x-2 animate-pulse">
            <Sparkles className="w-4 h-4 text-amber-400 animate-spin" />
            <span>Jaguar Runtime is generating response...</span>
          </div>
        )}
      </div>

      {/* Quick Prompts */}
      <div className="p-2 border-t border-[#1e293b] bg-[#0b0f19]">
        <div className="text-[10px] uppercase font-semibold text-slate-400 mb-1.5 flex items-center justify-between">
          <span>Quick Prompts</span>
          <Sparkles className="w-3 h-3 text-amber-400" />
        </div>
        <div className="flex flex-wrap gap-1">
          {quickPrompts.map((prompt) => (
            <button
              key={prompt}
              onClick={() => handleSend(prompt)}
              disabled={isGenerating}
              className="text-[10px] px-2 py-0.5 bg-[#162032] hover:bg-amber-500/20 hover:text-amber-300 text-slate-300 border border-[#2d3f5e] rounded transition-colors"
            >
              {prompt}
            </button>
          ))}
        </div>
      </div>

      {/* Input Area */}
      <div className="p-2.5 border-t border-[#1e293b] bg-[#0d1322] space-y-2">
        <textarea
          value={inputText}
          onChange={(e) => setInputText(e.target.value)}
          onKeyDown={(e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
              e.preventDefault();
              handleSend();
            }
          }}
          placeholder="Ask Jaguar Runtime about code, bugs, or refactoring..."
          rows={3}
          className="w-full bg-[#162032] border border-[#2d3f5e] rounded-lg p-2 text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-amber-400 resize-none font-sans"
        />

        <div className="flex items-center justify-between">
          <span className="text-[10px] text-slate-500">Press Enter to send</span>

          {isGenerating ? (
            <button
              onClick={handleStop}
              className="flex items-center space-x-1 px-3 py-1 bg-rose-500/20 hover:bg-rose-500/30 text-rose-300 border border-rose-500/40 text-xs rounded transition-colors font-medium"
            >
              <Square className="w-3 h-3" />
              <span>Stop</span>
            </button>
          ) : (
            <button
              onClick={() => handleSend()}
              disabled={!inputText.trim()}
              className={`flex items-center space-x-1 px-3 py-1 text-xs rounded transition-colors font-medium ${
                inputText.trim()
                  ? 'bg-amber-500 hover:bg-amber-400 text-slate-950 font-semibold'
                  : 'bg-[#162032] text-slate-600 cursor-not-allowed border border-[#2d3f5e]'
              }`}
            >
              <Send className="w-3 h-3" />
              <span>Send</span>
            </button>
          )}
        </div>
      </div>
    </div>
  );
};
