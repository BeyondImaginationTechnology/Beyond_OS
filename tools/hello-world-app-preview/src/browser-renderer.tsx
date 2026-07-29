import React from 'react';
import {
  canRenderMediaOnWeb,
  renderMediaOnWeb,
  type RenderMediaOnWebProgress,
} from '@remotion/web-renderer';
import {
  BeyondFrenchVideo,
  defaultBeyondFrenchVideoProps,
  type BeyondFrenchVideoProps,
} from './BeyondFrench';

const VIDEO = {
  id: 'BeyondFrenchPreview',
  component: BeyondFrenchVideo,
  durationInFrames: 300,
  fps: 30,
  width: 1080,
  height: 1920,
  calculateMetadata: null,
  defaultProps: defaultBeyondFrenchVideoProps,
};

type BrowserRenderOptions = {
  props: BeyondFrenchVideoProps;
  audioUrl?: string;
  onProgress?: (progress: number) => void;
};

const getAudioDurationInFrames = async (audioUrl: string) => {
  if (!audioUrl) return VIDEO.durationInFrames;

  const audio = document.createElement('audio');
  audio.preload = 'metadata';
  audio.src = audioUrl;
  const duration = await new Promise<number>((resolve, reject) => {
    const timeout = window.setTimeout(
      () => reject(new Error('Narration metadata timed out.')),
      15000,
    );
    audio.onloadedmetadata = () => {
      window.clearTimeout(timeout);
      resolve(audio.duration);
    };
    audio.onerror = () => {
      window.clearTimeout(timeout);
      reject(new Error('Narration audio could not be loaded for Remotion.'));
    };
  });
  audio.removeAttribute('src');
  audio.load();

  if (!Number.isFinite(duration)) return VIDEO.durationInFrames;
  return Math.max(
    VIDEO.durationInFrames,
    Math.min(1800, Math.ceil(duration * VIDEO.fps) + 15),
  );
};

const support = async (withAudio = false) => {
  const result = await canRenderMediaOnWeb({
    container: 'mp4',
    videoCodec: 'h264',
    audioCodec: withAudio ? 'aac' : null,
    width: VIDEO.width,
    height: VIDEO.height,
    muted: !withAudio,
  });

  return {
    supported: result.canRender,
    message:
      result.issues
        .filter((issue) => issue.severity === 'error')
        .map((issue) => issue.message)
        .join(' ') ||
      'This browser cannot encode the Beyond French MP4.',
  };
};

const render = async ({
  props,
  audioUrl = '',
  onProgress,
}: BrowserRenderOptions): Promise<Blob> => {
  const withAudio = audioUrl !== '';
  const capability = await support(withAudio);
  if (!capability.supported) {
    throw new Error(capability.message);
  }
  const durationInFrames = await getAudioDurationInFrames(audioUrl);

  const result = await renderMediaOnWeb({
    composition: {...VIDEO, durationInFrames},
    inputProps: {
      ...props,
      audioFile: audioUrl,
      brandIcon: '/beyond-french/assets/app-store/AppIcon-1024.png',
    },
    container: 'mp4',
    videoCodec: 'h264',
    audioCodec: withAudio ? 'aac' : null,
    muted: !withAudio,
    videoBitrate: 'very-high',
    audioBitrate: 'high',
    hardwareAcceleration: 'prefer-hardware',
    pageResponsiveness: 'medium',
    onProgress: (progress: RenderMediaOnWebProgress) => {
      onProgress?.(Math.max(0, Math.min(1, progress.progress)));
    },
  });

  return result.getBlob();
};

declare global {
  interface Window {
    BeyondFrenchRemotion?: {
      canRender: typeof support;
      render: typeof render;
      renderer: 'Remotion Web Renderer';
    };
  }
}

window.BeyondFrenchRemotion = {
  canRender: support,
  render,
  renderer: 'Remotion Web Renderer',
};
