import React from 'react';
import {
  AbsoluteFill,
  Easing,
  Sequence,
  interpolate,
  useCurrentFrame,
  useVideoConfig,
} from 'remotion';
import config from '../public/daily-breath-story.json';

export type DailyBreathStoryBeat = {
  id: string;
  label: string;
  startSeconds: number;
  durationSeconds: number;
  narration: string;
  onScreenText: string;
  visualPrompt: string;
};

export type DailyBreathStorySource = {
  citation: string;
  url: string;
  notes: string;
};

export type DailyBreathStoryProps = {
  brand: string;
  series: string;
  title: string;
  subtitle: string;
  beats: DailyBreathStoryBeat[];
  sources: DailyBreathStorySource[];
  outroText: string;
  sourceSeconds: number;
  outroSeconds: number;
  fps: number;
  width: number;
  height: number;
  palette: {
    background: string;
    foreground: string;
    accent: string;
    muted: string;
  };
};

export const defaultDailyBreathStoryProps =
  config as DailyBreathStoryProps;

export const dailyBreathStoryDurationSeconds = (props: DailyBreathStoryProps) => {
  const storyEnd = props.beats.reduce(
    (end, beat) => Math.max(end, beat.startSeconds + beat.durationSeconds),
    0,
  );
  return storyEnd + props.sourceSeconds + props.outroSeconds;
};

const clamp = {
  extrapolateLeft: 'clamp' as const,
  extrapolateRight: 'clamp' as const,
};

const Scene: React.FC<{
  beat: DailyBreathStoryBeat;
  props: DailyBreathStoryProps;
}> = ({beat, props}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();
  const duration = beat.durationSeconds * fps;
  const fade = Math.max(1, Math.min(18, Math.floor(duration / 4)));
  const opacity = interpolate(frame, [0, fade, duration - fade, duration], [0, 1, 1, 0], clamp);
  const lift = interpolate(frame, [0, fade, duration], [20, 0, -8], {
    ...clamp,
    easing: Easing.out(Easing.cubic),
  });
  const isIntro = beat.id === 'intro';
  const isPeak = beat.id === 'peak';
  const palette = props.palette;
  const totalDurationSeconds = dailyBreathStoryDurationSeconds(props);

  return (
    <AbsoluteFill
      style={{
        alignItems: 'center',
        background: `radial-gradient(ellipse at 50% 34%, ${palette.accent}2b, transparent 58%), linear-gradient(155deg, ${palette.background}, #071c16)`,
        color: palette.foreground,
        display: 'flex',
        justifyContent: 'center',
        overflow: 'hidden',
        padding: '96px 84px',
        textAlign: 'center',
      }}
    >
      <div
        style={{
          border: `1px solid ${palette.accent}48`,
          borderRadius: '50%',
          height: 760,
          opacity: 0.55,
          position: 'absolute',
          transform: `scale(${1 + Math.sin(frame / fps) * 0.025})`,
          width: 760,
        }}
      />
      <div
        style={{
          maxWidth: 900,
          opacity,
          position: 'relative',
          transform: `translateY(${lift}px)`,
        }}
      >
        <div
          style={{
            color: palette.accent,
            fontSize: 24,
            fontWeight: 800,
            letterSpacing: 7,
            textTransform: 'uppercase',
          }}
        >
          {isIntro ? props.brand : beat.label}
        </div>
        {isIntro ? (
          <>
            <div
              style={{
                fontFamily: 'Georgia, Times New Roman, serif',
                fontSize: props.title.length > 42 ? 62 : props.title.length > 28 ? 74 : 86,
                fontWeight: 600,
                lineHeight: 1.05,
                marginTop: 38,
              }}
            >
              {props.title}
            </div>
            <div style={{color: palette.accent, fontSize: 26, marginTop: 17}}>
              {beat.onScreenText}
            </div>
            <div style={{color: palette.muted, fontSize: 30, marginTop: 20}}>
              {props.subtitle}
            </div>
            <div style={{color: palette.muted, fontSize: 27, lineHeight: 1.4, marginTop: 34}}>
              {beat.narration}
            </div>
          </>
        ) : (
          <div
            style={{
              fontFamily: 'Georgia, Times New Roman, serif',
              fontSize: isPeak ? 70 : 56,
              fontWeight: 600,
              lineHeight: 1.14,
              marginTop: 34,
            }}
          >
            <>
              <div>{beat.onScreenText}</div>
              <div
                style={{
                  color: palette.muted,
                  fontFamily: 'Arial, Helvetica, sans-serif',
                  fontSize: beat.narration.length > 260 ? 28 : 32,
                  fontWeight: 400,
                  lineHeight: 1.42,
                  marginTop: 36,
                }}
              >
                {beat.narration}
              </div>
            </>
          </div>
        )}
      </div>
      <div
        style={{
          bottom: 78,
          color: palette.muted,
          fontSize: 19,
          letterSpacing: 2,
          position: 'absolute',
        }}
      >
        {props.series}
      </div>
      <div
        style={{
          background: `${palette.foreground}24`,
          bottom: 35,
          height: 3,
          left: 84,
          position: 'absolute',
          right: 84,
        }}
      >
        <div
          style={{
            background: palette.accent,
            height: '100%',
            transform: `scaleX(${(beat.startSeconds + frame / fps) / totalDurationSeconds})`,
            transformOrigin: 'left',
          }}
        />
      </div>
    </AbsoluteFill>
  );
};

const SourcesCard: React.FC<{props: DailyBreathStoryProps}> = ({props}) => (
  <AbsoluteFill
    style={{
      alignItems: 'center',
      background: `linear-gradient(155deg, ${props.palette.background}, #071c16)`,
      color: props.palette.foreground,
      display: 'flex',
      flexDirection: 'column',
      justifyContent: 'center',
      padding: '85px 100px',
      textAlign: 'center',
    }}
  >
    <div style={{color: props.palette.accent, fontSize: 25, fontWeight: 800, letterSpacing: 7}}>
      SOURCES
    </div>
    <div style={{display: 'grid', gap: 18, marginTop: 44, maxWidth: 860}}>
      {props.sources.slice(0, 4).map((source, index) => (
        <div key={`${source.citation}-${index}`} style={{fontSize: 22, lineHeight: 1.3}}>
          <div>{source.citation}</div>
          <div style={{color: props.palette.muted, fontSize: 16, marginTop: 8}}>{source.notes}</div>
        </div>
      ))}
    </div>
    <div style={{bottom: 35, color: props.palette.muted, fontSize: 16, position: 'absolute'}}>
      Full references and notes are available in the story details.
    </div>
  </AbsoluteFill>
);

const OutroCard: React.FC<{props: DailyBreathStoryProps}> = ({props}) => (
  <AbsoluteFill
    style={{
      alignItems: 'center',
      background: `radial-gradient(circle at 50% 38%, ${props.palette.accent}28, transparent 48%), ${props.palette.background}`,
      color: props.palette.foreground,
      display: 'flex',
      flexDirection: 'column',
      justifyContent: 'center',
      textAlign: 'center',
    }}
  >
    <div style={{color: props.palette.accent, fontSize: 28, fontWeight: 850, letterSpacing: 8}}>
      DAILY BREATH
    </div>
    <div style={{fontFamily: 'Georgia, Times New Roman, serif', fontSize: 48, marginTop: 27}}>
      {props.outroText}
    </div>
    <div style={{color: props.palette.muted, fontSize: 22, marginTop: 18}}>{props.title}</div>
    <div style={{bottom: 40, color: props.palette.muted, fontSize: 16, position: 'absolute'}}>
      {props.series}
    </div>
  </AbsoluteFill>
);

export const DailyBreathStory: React.FC<DailyBreathStoryProps> = (props) => {
  const fps = props.fps;
  const storyEndSeconds = props.beats.reduce(
    (end, beat) => Math.max(end, beat.startSeconds + beat.durationSeconds),
    0,
  );
  return (
    <AbsoluteFill style={{background: props.palette.background}}>
      {props.beats.map((beat) => (
        <Sequence
          key={beat.id}
          from={beat.startSeconds * fps}
          durationInFrames={beat.durationSeconds * fps}
        >
          <Scene beat={beat} props={props} />
        </Sequence>
      ))}
      <Sequence from={storyEndSeconds * fps} durationInFrames={props.sourceSeconds * fps}>
        <SourcesCard props={props} />
      </Sequence>
      <Sequence from={(storyEndSeconds + props.sourceSeconds) * fps} durationInFrames={props.outroSeconds * fps}>
        <OutroCard props={props} />
      </Sequence>
    </AbsoluteFill>
  );
};
