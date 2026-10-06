import React, { useState } from 'react';
import {
  Cpu,
  HardDrive,
  Cloud,
  ArrowRight,
  CheckCircle2,
  Send,
  Sparkles,
  ShieldCheck,
  Code2,
} from 'lucide-react';
import { JaguarModelMeta } from '../types/workspace';

interface RuntimeHomepageDemoProps {
  onStartCoding: () => void;
}

const MODEL_FAMILIES: JaguarModelMeta[] = [
  {
    id: 'gemma-4b',
    vendor: 'Google',
    provider: 'Local',
    modelFamily: 'Gemma',
    modelName: 'Gemma 4B Instruct',
    version: '2.0',
    size: '4B',
    license: 'Gemma Terms of Use',
    capability: 'Local / Cloud',
    status: 'Ready',
  },
  {
    id: 'qwen-7b',
    vendor: 'Alibaba',
    provider: 'Ollama',
    modelFamily: 'Qwen',
    modelName: 'Qwen 2.5 Coder 7B',
    version: '2.5',
    size: '7B',
    license: 'Apache 2.0',
    capability: 'Local / Cloud',
    status: 'Ready',
  },
  {
    id: 'llama-8b',
    vendor: 'Meta',
    provider: 'LM Studio',
    modelFamily: 'Llama',
    modelName: 'Llama 3.1 8B Instruct',
    version: '3.1',
    size: '8B',
    license: 'Meta Llama 3.1 Community License',
    capability: 'Local / Cloud',
    status: 'Connected',
  },
  {
    id: 'deepseek-16b',
    vendor: 'DeepSeek',
    provider: 'OpenAI-compatible',
    modelFamily: 'DeepSeek',
    modelName: 'DeepSeek Coder V2 16B',
    version: '2.0',
    size: '16B',
    license: 'MIT',
    capability: 'Local / Cloud',
    status: 'Ready',
  },
  {
    id: 'phi-3b',
    provider: 'Local',
    vendor: 'Microsoft',
    modelFamily: 'Phi',
    modelName: 'Phi-3 Mini 3.8B',
    version: '3.0',
    size: '3.8B',
    license: 'MIT',
    capability: 'Local',
    status: 'Ready',
  },
];

export const RuntimeHomepageDemo: React.FC<RuntimeHomepageDemoProps> = ({
  onStartCoding,
}) => {
  const [selectedProvider, setSelectedProvider] = useState<
    'Local' | 'Ollama' | 'LM Studio' | 'OpenAI-compatible' | 'Cloud'
  >('Local');
  const [selectedModelId, setSelectedModelId] = useState<string>('gemma-4b');
  const [promptText, setInputText] = useState('Explain this PHP function');
  const [demoOutput, setDemoOutput] = useState<string | null>(
    'Jaguar Runtime Demo:\nThis PHP function validates the incoming JSON payload, checks authorization tokens, and returns a structured response.'
  );
  const [isSimulating, setIsSimulating] = useState(false);

  const activeModel =
    MODEL_FAMILIES.find((m) => m.id === selectedModelId) || MODEL_FAMILIES[0];

  const handleRunDemo = () => {
    if (!promptText.trim()) return;
    setIsSimulating(true);
    setDemoOutput(null);

    setTimeout(() => {
      setDemoOutput(
        `Jaguar Runtime Output [${selectedProvider} → ${activeModel.modelName}]:\nAnalyzing "${promptText}"...\n\nResult:\n1. Execution route established via Jaguar Runtime.\n2. Output produced locally or via configured provider bridge without hardcoding vendor defaults.`
      );
      setIsSimulating(false);
    }, 600);
  };

  return (
    <div className="flex-1 overflow-y-auto bg-[#0b0f19] text-slate-200 select-none p-4 sm:p-8 space-y-12 max-w-6xl mx-auto">
      {/* Hero Section */}
      <section className="text-center space-y-4 pt-4 sm:pt-8">
        <div className="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 text-xs font-mono">
          <Cpu className="w-3.5 h-3.5 text-amber-400" />
          <span>JAGUAR WORKSPACE & RUNTIME</span>
        </div>

        <h1 className="text-3xl sm:text-5xl font-extrabold tracking-tight text-white max-w-3xl mx-auto leading-tight">
          One workspace. <span className="text-amber-400">Your models.</span> Your choice.
        </h1>

        <p className="text-sm sm:text-base text-slate-400 max-w-2xl mx-auto leading-relaxed">
          Build with local and cloud AI through <strong className="text-slate-200">Jaguar Runtime</strong> — a model-neutral execution layer designed for developer control.
        </p>

        <div className="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
          <button
            onClick={onStartCoding}
            className="w-full sm:w-auto px-6 py-2.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs rounded-lg transition-colors shadow-lg shadow-amber-950/40 flex items-center justify-center space-x-2"
          >
            <Code2 className="w-4 h-4" />
            <span>Try Jaguar Workspace</span>
          </button>
          <a
            href="#demo"
            className="w-full sm:w-auto px-6 py-2.5 bg-[#162032] hover:bg-[#1e2d47] text-slate-200 border border-[#2d3f5e] font-semibold text-xs rounded-lg transition-colors flex items-center justify-center space-x-2"
          >
            <span>Explore Runtime</span>
            <ArrowRight className="w-4 h-4 text-amber-400" />
          </a>
        </div>
      </section>

      {/* Interactive Runtime Demo */}
      <section
        id="demo"
        className="bg-[#0d1322] border border-[#1e293b] rounded-2xl p-4 sm:p-6 shadow-2xl space-y-6"
      >
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-[#1e293b] pb-4">
          <div>
            <div className="flex items-center space-x-2">
              <Cpu className="w-5 h-5 text-amber-400" />
              <h2 className="text-base font-bold text-white uppercase tracking-wider">
                Jaguar Runtime Demo
              </h2>
            </div>
            <p className="text-xs text-slate-400 mt-1">
              Select a provider and model family to preview how Jaguar Runtime routes requests.
            </p>
          </div>
          <div className="inline-flex items-center space-x-2 px-3 py-1 bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 rounded-full text-xs font-mono self-start sm:self-auto">
            <CheckCircle2 className="w-3.5 h-3.5" />
            <span>Runtime Active</span>
          </div>
        </div>

        {/* Demo Controls */}
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div className="space-y-1.5">
            <label className="text-xs font-semibold text-slate-300">Provider</label>
            <select
              value={selectedProvider}
              onChange={(e) =>
                setSelectedProvider(
                  e.target.value as 'Local' | 'Ollama' | 'LM Studio' | 'OpenAI-compatible' | 'Cloud'
                )
              }
              aria-label="Select AI Provider"
              className="w-full bg-[#162032] border border-[#2d3f5e] rounded-lg p-2.5 text-xs text-slate-200 focus:outline-none focus:border-amber-400 font-sans"
            >
              <option value="Local">Local (Hardware direct)</option>
              <option value="Ollama">Ollama (Local server)</option>
              <option value="LM Studio">LM Studio (Local port 1234)</option>
              <option value="OpenAI-compatible">OpenAI-compatible API</option>
              <option value="Cloud">Cloud Provider</option>
            </select>
          </div>

          <div className="space-y-1.5">
            <label className="text-xs font-semibold text-slate-300">Model Family</label>
            <select
              value={selectedModelId}
              onChange={(e) => setSelectedModelId(e.target.value)}
              aria-label="Select AI Model Family"
              className="w-full bg-[#162032] border border-[#2d3f5e] rounded-lg p-2.5 text-xs text-slate-200 focus:outline-none focus:border-amber-400 font-sans"
            >
              {MODEL_FAMILIES.map((m) => (
                <option key={m.id} value={m.id}>
                  {m.modelFamily} — {m.modelName} ({m.vendor})
                </option>
              ))}
            </select>
          </div>
        </div>

        {/* Selected Model Status Badge */}
        <div className="p-3 bg-[#121b2d] border border-[#23334d] rounded-xl flex flex-wrap items-center justify-between gap-2 text-xs font-mono">
          <div className="flex items-center space-x-3">
            <span className="text-amber-400 font-bold">JAGUAR RUNTIME</span>
            <span className="text-slate-500">|</span>
            <span className="text-slate-300">Provider: <strong className="text-white">{selectedProvider}</strong></span>
            <span className="text-slate-500">|</span>
            <span className="text-slate-300">Model: <strong className="text-white">{activeModel.modelFamily}</strong></span>
          </div>
          <span className="px-2 py-0.5 bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 rounded text-[11px]">
            ● {activeModel.status}
          </span>
        </div>

        {/* Demo Interactive Prompt */}
        <div className="space-y-2">
          <label className="text-xs font-semibold text-slate-300">Demo Prompt</label>
          <div className="flex items-center space-x-2">
            <input
              type="text"
              value={promptText}
              onChange={(e) => setInputText(e.target.value)}
              aria-label="Demo prompt input"
              className="flex-1 bg-[#162032] border border-[#2d3f5e] rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-amber-400 font-mono"
            />
            <button
              onClick={handleRunDemo}
              disabled={isSimulating}
              className="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs rounded-lg transition-colors flex items-center space-x-1.5 shrink-0"
            >
              <Send className="w-3.5 h-3.5" />
              <span>{isSimulating ? 'Processing...' : 'Run Prompt'}</span>
            </button>
          </div>
        </div>

        {/* Demo Response Console */}
        <div className="bg-[#080c14] border border-[#1e293b] rounded-xl p-4 font-mono text-xs text-slate-300 space-y-2 min-h-[100px]">
          <div className="text-slate-500 text-[11px] flex items-center justify-between border-b border-[#1e293b] pb-2">
            <span>Runtime Execution Output</span>
            <span className="text-amber-400">Simulated Demo</span>
          </div>
          {isSimulating ? (
            <div className="text-amber-300 flex items-center space-x-2 animate-pulse pt-2">
              <Sparkles className="w-4 h-4 text-amber-400 animate-spin" />
              <span>Jaguar Runtime executing request via {selectedProvider}...</span>
            </div>
          ) : (
            <p className="whitespace-pre-wrap leading-relaxed pt-1 text-emerald-300/90">
              {demoOutput}
            </p>
          )}
        </div>
      </section>

      {/* Local vs Cloud Distinction */}
      <section className="space-y-4">
        <h2 className="text-lg font-bold text-white text-center">Local vs Cloud Capabilities</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div className="bg-[#0d1322] border border-[#1e293b] rounded-xl p-5 space-y-3">
            <div className="flex items-center space-x-2 text-amber-400 font-bold text-sm">
              <HardDrive className="w-5 h-5" />
              <span>Local Hardware Models</span>
            </div>
            <ul className="text-xs text-slate-400 space-y-2 list-disc list-inside">
              <li>Model runs directly on user's workstation hardware</li>
              <li>Offline-capable where supported by runtime</li>
              <li>No external cloud inference latency or dependency</li>
            </ul>
          </div>

          <div className="bg-[#0d1322] border border-[#1e293b] rounded-xl p-5 space-y-3">
            <div className="flex items-center space-x-2 text-sky-400 font-bold text-sm">
              <Cloud className="w-5 h-5" />
              <span>Cloud API Providers</span>
            </div>
            <ul className="text-xs text-slate-400 space-y-2 list-disc list-inside">
              <li>Model executes through external provider endpoints</li>
              <li>Requires internet connectivity</li>
              <li>Provider pricing, rate limits, or terms may apply</li>
            </ul>
          </div>
        </div>
      </section>

      {/* Architecture Visualization */}
      <section className="bg-[#0d1322] border border-[#1e293b] rounded-2xl p-6 text-center space-y-4">
        <h2 className="text-xs font-mono text-amber-400 uppercase tracking-widest">
          Jaguar Architecture Pipeline
        </h2>
        <div className="flex flex-col md:flex-row items-center justify-center gap-3 font-mono text-xs font-bold text-slate-200">
          <div className="px-4 py-2 bg-[#162032] border border-[#2d3f5e] rounded-lg w-full md:w-auto">
            Jaguar Workspace
          </div>
          <ArrowRight className="w-4 h-4 text-amber-400 rotate-90 md:rotate-0" />
          <div className="px-4 py-2 bg-amber-500/20 border border-amber-500/40 text-amber-300 rounded-lg w-full md:w-auto">
            Jaguar Runtime
          </div>
          <ArrowRight className="w-4 h-4 text-amber-400 rotate-90 md:rotate-0" />
          <div className="px-4 py-2 bg-[#162032] border border-[#2d3f5e] rounded-lg w-full md:w-auto">
            Provider
          </div>
          <ArrowRight className="w-4 h-4 text-amber-400 rotate-90 md:rotate-0" />
          <div className="px-4 py-2 bg-[#162032] border border-[#2d3f5e] rounded-lg w-full md:w-auto">
            Model
          </div>
        </div>
      </section>

      {/* Model Family Cards */}
      <section className="space-y-4">
        <h2 className="text-lg font-bold text-white text-center">Supported Third-Party Model Families</h2>
        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
          {MODEL_FAMILIES.map((model) => (
            <div
              key={model.id}
              className="bg-[#0d1322] border border-[#1e293b] rounded-xl p-3.5 space-y-2 text-xs hover:border-amber-500/40 transition-colors"
            >
              <div className="flex items-center justify-between font-bold text-white">
                <span>{model.modelFamily}</span>
                <span className="text-[10px] text-amber-400 font-mono">{model.size}</span>
              </div>
              <div className="text-[11px] text-slate-400">{model.vendor}</div>
              <div className="text-[10px] text-slate-400 bg-[#0b0f19] px-2 py-0.5 rounded border border-[#1e293b] font-mono truncate">
                {model.capability}
              </div>
              <div className="pt-1 border-t border-[#1e293b] flex items-center justify-between text-[10px] text-slate-400">
                <span className="flex items-center space-x-1">
                  <ShieldCheck className="w-3 h-3 text-emerald-400" />
                  <span className="truncate max-w-[90px]">{model.license}</span>
                </span>
              </div>
            </div>
          ))}
        </div>
      </section>
    </div>
  );
};
